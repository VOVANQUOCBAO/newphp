import http from 'node:http';
import { randomBytes, timingSafeEqual } from 'node:crypto';
import { readFileSync } from 'node:fs';
import mysql from 'mysql2/promise';
import { pathToFileURL } from 'node:url';

// Run one instance: connection sessions are deliberately kept in this process.
export function createBridge(env = process.env) {
  const token = env.DB_BRIDGE_TOKEN || '';
  if (Buffer.byteLength(token) < 32) throw new Error('DB_BRIDGE_TOKEN must contain at least 32 bytes');
  const database = env.DB_NAME || '';
  if (!/^[a-zA-Z0-9_]+$/.test(database)) throw new Error('DB_NAME is required');
  const local = env.BRIDGE_LOCAL_TEST === '1';
  const ttl = Number(env.BRIDGE_SESSION_TTL_MS || 120000);
  if (!Number.isFinite(ttl) || ttl < 1000 || ttl > 300000) throw new Error('Invalid session TTL');
  let connecting = 0;
  const sessions = new Map();
  const metrics = { requests: 0, queries: 0, begins: 0, commits: 0, rollbacks: 0, expired: 0 };
  const equal = (a, b) => a.length === b.length && timingSafeEqual(a, b);
  const reply = (res, status, data) => {
    if (res.destroyed) return;
    res.writeHead(status, { 'Content-Type': 'application/json', 'Cache-Control': 'no-store' });
    res.end(JSON.stringify(data));
  };
  const dispose = async (id) => {
    const s = sessions.get(id);
    if (!s) return;
    sessions.delete(id);
    // Disconnecting rolls back any uncommitted transaction, including after a PHP timeout.
    s.connection.destroy();
  };
  const timer = setInterval(() => {
    for (const [id, s] of sessions) {
      if (!s.busy && Date.now() - s.touched > ttl) {
        metrics.expired++;
        void dispose(id);
      }
    }
  }, Math.min(1000, ttl));
  timer.unref();

  const server = http.createServer(async (req, res) => {
    metrics.requests++;
    if (req.method === 'GET' && req.url === '/health') return reply(res, 200, { ok: true });
    if (req.method !== 'POST' || req.url !== '/rpc') return reply(res, 404, { ok: false });
    const supplied = Buffer.from(req.headers.authorization || '');
    if (!equal(supplied, Buffer.from(`Bearer ${token}`))) {
      req.resume();
      return reply(res, 401, { ok: false, error: 'Unauthorized' });
    }
    // Only the explicitly enabled loopback fixture may use plaintext HTTP.
    const loopback = env.BRIDGE_ALLOW_LOOPBACK_HTTP === '1'
      && ['127.0.0.1', '::1'].includes(req.socket.remoteAddress);
    if (!local && !loopback && req.headers['x-forwarded-proto'] !== 'https' && !req.socket.encrypted) {
      req.resume();
      return reply(res, 400, { ok: false, error: 'HTTPS is required' });
    }
    let session;
    try {
      let size = 0;
      const chunks = [];
      for await (const chunk of req) {
        size += chunk.length;
        if (size > 2 * 1024 * 1024) {
          return reply(res, 413, { ok: false, error: 'Request too large' });
        }
        chunks.push(chunk);
      }
      let body;
      try { body = JSON.parse(Buffer.concat(chunks).toString('utf8')); }
      catch { return reply(res, 400, { ok: false, error: 'Invalid JSON' }); }
      if (body === null || typeof body !== 'object' || Array.isArray(body)) {
        return reply(res, 400, { ok: false, error: 'Invalid request' });
      }
      if (body.op === 'connect') {
        if (sessions.size + connecting >= 50) return reply(res, 503, { ok: false, error: 'Connection limit reached' });
        const selected = body.database || database;
        // Test prefixes are available only on a loopback fixture, never on the cloud service.
        if (selected !== database && !(local && /^webbanhang_mvc_test_[a-f0-9]{12}$/.test(selected))) {
          return reply(res, 403, { ok: false, error: 'Database is not allowed' });
        }
        connecting++;
        let connection;
        try {
        connection = await mysql.createConnection({
          host: env.DB_HOST,
          port: Number(env.DB_PORT || 4000),
          user: env.DB_USER,
          password: env.DB_PASS || '',
          database: selected,
          charset: 'utf8mb4', dateStrings: true, supportBigNumbers: true, bigNumberStrings: true,
          flags: ['-FOUND_ROWS'], connectTimeout: 10000,
          // Multi-statement schema imports are a local fixture feature only.
          multipleStatements: local,
          ssl: local ? undefined : {
            rejectUnauthorized: true,
            ...(env.DB_SSL_CA_FILE ? { ca: readFileSync(env.DB_SSL_CA_FILE) } : {}),
          },
        });
        await connection.query("SET time_zone = '+07:00'");
        if (!local) await connection.query("SET SESSION tidb_txn_mode = 'pessimistic'");
        } catch (error) { connection?.destroy(); throw error; }
        finally { connecting--; }
        const id = randomBytes(32).toString('hex');
        connection.on('error', () => { sessions.delete(id); connection.destroy(); });
        sessions.set(id, { connection, touched: Date.now(), busy: false });
        if (res.destroyed) { await dispose(id); return; }
        return reply(res, 200, { ok: true, session: id });
      }
      if (!['query', 'exec', 'begin', 'commit', 'rollback', 'close'].includes(body.op)) {
        return reply(res, 400, { ok: false, error: 'Unknown operation' });
      }
      session = sessions.get(body.session);
      if (!session) return reply(res, 410, { ok: false, error: 'Database session expired; request was not replayed' });
      if (session.busy) return reply(res, 409, { ok: false, error: 'Concurrent session use is not supported' });
      session.busy = true;
      session.touched = Date.now();
      const conn = session.connection;
      if (body.op === 'close') {
        await dispose(body.session);
        return reply(res, 200, { ok: true });
      }
      if (['begin', 'commit', 'rollback'].includes(body.op)) {
        const method = { begin: 'beginTransaction', commit: 'commit', rollback: 'rollback' }[body.op];
        await conn[method]();
        metrics[{ begin: 'begins', commit: 'commits', rollback: 'rollbacks' }[body.op]]++;
        return reply(res, 200, { ok: true });
      }
      if (typeof body.sql !== 'string' || !body.sql.trim() || !Array.isArray(body.params || [])) {
        return reply(res, 400, { ok: false, error: 'Invalid query' });
      }
      metrics.queries++;
      const options = { sql: body.sql, rowsAsArray: true, timeout: 20000 };
      // Execute parameters server-side; never concatenate user values into SQL in PHP.
      const [rows, fields] = body.op === 'exec'
        ? await conn.query(options)
        : await conn.execute(options, (body.params || []).map(v => typeof v === 'boolean' ? Number(v) : v));
      const hasRows = Array.isArray(rows) && Array.isArray(fields)
        && fields.every(f => f && typeof f.name === 'string');
      return reply(res, 200, {
        ok: true, rows: hasRows ? rows : [], columns: hasRows ? fields.map(f => f.name) : [],
        affected: hasRows ? rows.length : (rows.affectedRows || 0),
        insertId: hasRows ? '0' : String(rows.insertId || '0'),
      });
    } catch (error) {
      // A lost SQL connection cannot safely continue the PHP transaction.
      const connectionLost = Boolean(error.fatal) || !error.sqlState;
      if (connectionLost && session) await dispose(bodySession(session));
      // SQL and credentials never appear in HTTP errors or service logs.
      reply(res, 500, { ok: false, error: 'Database operation failed',
        sqlstate: /^[A-Z0-9]{5}$/.test(error.sqlState || '') ? error.sqlState : 'HY000',
        errno: Number(error.errno || 0), connectionLost });
    } finally {
      if (session) { session.busy = false; session.touched = Date.now(); }
    }
  });
  server.requestTimeout = 30000;
  function bodySession(session) {
    for (const [id, value] of sessions) if (value === session) return id;
  }
  return { server, metrics, sessions, close: async () => {
    clearInterval(timer);
    await Promise.all([...sessions.keys()].map(dispose));
    server.closeAllConnections();
    await new Promise(resolve => server.close(resolve));
  } };
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
  const bridge = createBridge();
  const host = process.env.BRIDGE_LOCAL_TEST === '1' ? '127.0.0.1' : '0.0.0.0';
  bridge.server.listen(Number(process.env.PORT || 8787), host, () => console.log('Database bridge ready'));
  process.on('SIGTERM', async () => { await bridge.close(); process.exit(0); });
}
