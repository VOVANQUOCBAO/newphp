import assert from 'node:assert/strict';
import http from 'node:http';
import { spawn } from 'node:child_process';
import { createBridge } from './server.mjs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(fileURLToPath(new URL('../..', import.meta.url)));
const token = 'a'.repeat(64);
let count = 0;
const pass = name => { count++; console.log('PASS ' + name); };
assert.throws(() => createBridge({ DB_NAME: 'demo', DB_BRIDGE_TOKEN: 'short' }));
pass('Short bridge tokens cannot start a service');
const production = createBridge({ DB_NAME: 'demo', DB_BRIDGE_TOKEN: token });
await new Promise(resolve => production.server.listen(0, '127.0.0.1', resolve));
const url = `http://127.0.0.1:${production.server.address().port}/rpc`;
const blocked = await fetch(url, { method: 'POST', headers: { Authorization: `Bearer ${token}` }, body: '{"op":"connect"}' });
assert.equal(blocked.status, 400);
pass('Production bridge rejects plaintext transport');
await production.close();

let queries = 0;
const fixture = http.createServer(async (req, res) => {
  let raw = ''; for await (const c of req) raw += c;
  const body = JSON.parse(raw);
  res.setHeader('Content-Type', 'application/json');
  if (body.op === 'connect') return res.end(JSON.stringify({ ok: true, session: 'fixture-session' }));
  if (body.op === 'close') return res.end('{"ok":true}');
  queries++;
  if (body.sql === 'disconnect') return req.socket.destroy();
  if (body.sql === 'invalid-json') return res.end('<html>Gateway failure</html>');
  res.statusCode = 500;
  res.end(JSON.stringify({ ok: false, error: 'Database operation failed', sqlstate: '23000', errno: 1062 }));
});
await new Promise(resolve => fixture.listen(0, '127.0.0.1', resolve));
const bridgeUrl = `http://127.0.0.1:${fixture.address().port}`;
const php = async code => {
  let stderr = '';
  const exit = await new Promise((resolve, reject) => {
    const child = spawn('D:/data/php/php.exe', ['-r', `require 'support/lib/HttpsDatabase.php'; ${code}`],
      { cwd: root, env: { ...process.env, DB_BRIDGE_LOCAL_TEST: '1', EDGE_URL: bridgeUrl, EDGE_TOKEN: token } });
    child.stderr.on('data', c => { stderr += c; });
    child.on('error', reject); child.on('exit', resolve);
  });
  assert.equal(exit, 0, stderr);
};
try {
  await php("try { new HttpsDatabase('http://example.com',getenv('EDGE_TOKEN'),'demo'); exit(1); } catch (InvalidArgumentException) {}");
  pass('PHP refuses external plaintext URLs even with local fixture flag');
  await php("try { new HttpsDatabase('https://example.com:4000',getenv('EDGE_TOKEN'),'demo'); exit(1); } catch (InvalidArgumentException) {}");
  pass('PHP requires external HTTPS on standard port 443');
  await php("$d=new HttpsDatabase(getenv('EDGE_URL'),getenv('EDGE_TOKEN'),'demo'); try {$d->query('duplicate');exit(1);}catch(PDOException $e){if($e->getCode()!=='23000'||$e->errorInfo[1]!==1062)exit(2);}");
  pass('PDO callers retain SQLSTATE and duplicate-key error code');
  await php("$d=new HttpsDatabase(getenv('EDGE_URL'),getenv('EDGE_TOKEN'),'demo'); try{$d->query('invalid-json');exit(1);}catch(PDOException){}");
  pass('Malformed gateway responses fail explicitly');
  const before = queries;
  await php("$d=new HttpsDatabase(getenv('EDGE_URL'),getenv('EDGE_TOKEN'),'demo'); for($i=0;$i<2;$i++){try{$d->query('disconnect');exit(1);}catch(PDOException){}}");
  assert.equal(queries-before, 1);
  pass('Lost connections are never automatically replayed');
} finally {
  fixture.closeAllConnections();
  await new Promise(resolve => fixture.close(resolve));
}
console.log(`${count} edge checks passed`);
