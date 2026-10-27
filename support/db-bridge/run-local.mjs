import mysql from 'mysql2/promise';
import { randomBytes } from 'node:crypto';
import { spawn } from 'node:child_process';
import { createBridge } from './server.mjs';
import { resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { writeFile, readFile, unlink } from 'node:fs/promises';

const root = resolve(fileURLToPath(new URL('../..', import.meta.url)));
const cfg = { host: '127.0.0.1', port: 3307, user: 'root', password: '' };
const database = `phpweb_bridge_control_${randomBytes(6).toString('hex')}`;
const admin = await mysql.createConnection(cfg);
await admin.query(`CREATE DATABASE \`${database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
const token = randomBytes(32).toString('hex');
const bridge = createBridge({ DB_HOST: cfg.host, DB_PORT: String(cfg.port), DB_USER: cfg.user,
  DB_PASS: cfg.password, DB_NAME: database, DB_BRIDGE_TOKEN: token, BRIDGE_LOCAL_TEST: '1' });
await new Promise(resolve => bridge.server.listen(0, '127.0.0.1', resolve));
const url = `http://127.0.0.1:${bridge.server.address().port}`;
const rpc = async (body, auth = token) => {
  const response = await fetch(url + '/rpc', { method: 'POST', headers: { 'Content-Type': 'application/json',
    Authorization: `Bearer ${auth}` }, body: JSON.stringify(body) });
  return { status: response.status, data: await response.json() };
};
let fails = 0;
const check = (name, ok) => { console.log(`${ok ? 'PASS' : 'FAIL'} bridge: ${name}`); if (!ok) fails++; };
let exitCode = 1;
const bankFile = resolve(root, 'support/config/bank.local.php');
const oldBank = await readFile(bankFile).catch(() => null);
try {
  check('reject unauthenticated SQL', (await rpc({ op: 'connect' }, 'wrong')).status === 401);
  check('reject other databases', (await rpc({ op: 'connect', database: 'mysql' })).status === 403);
  const { data } = await rpc({ op: 'connect' });
  const session = data.session;
  check('connect with secret token', typeof session === 'string' && session.length === 64);
  const sql = (query, params = []) => rpc({ op: 'query', session, sql: query, params });
  await sql('CREATE TABLE bridge_probe (id INT PRIMARY KEY, value VARCHAR(255))');
  await rpc({ op: 'begin', session });
  await sql('INSERT INTO bridge_probe VALUES (?, ?)', [1, "Tiếng Việt ' OR 1=1 --"]);
  check('read own uncommitted changes', (await sql('SELECT value FROM bridge_probe WHERE id=?', [1])).data.rows[0][0] === "Tiếng Việt ' OR 1=1 --");
  await rpc({ op: 'rollback', session });
  check('rollback removes writes', Number((await sql('SELECT COUNT(*) FROM bridge_probe')).data.rows[0][0]) === 0);
  await rpc({ op: 'begin', session });
  await sql('INSERT INTO bridge_probe VALUES (?, ?)', [2, 'committed']);
  await rpc({ op: 'commit', session });
  check('commit persists writes', Number((await sql('SELECT COUNT(*) FROM bridge_probe')).data.rows[0][0]) === 1);
  check('duplicate key preserves SQLSTATE', (await sql('INSERT INTO bridge_probe VALUES (?, ?)', [2, 'duplicate'])).data.sqlstate === '23000');
  await rpc({ op: 'begin', session });
  await sql('INSERT INTO bridge_probe VALUES (?, ?)', [3, 'abandoned']);
  await rpc({ op: 'close', session });
  const [closedRows] = await admin.query(`SELECT COUNT(*) AS n FROM \`${database}\`.bridge_probe`);
  check('disconnect rolls back active transaction', closedRows[0].n === 1);
  check('closed session fails without replay', (await rpc({ op: 'commit', session })).status === 410);
  await admin.query(`DROP TABLE \`${database}\`.bridge_probe`);
  if (fails) throw new Error('Bridge contract checks failed');
  // Dummy account for offline QR tests only. No request to a banking service or transfer.
  await writeFile(bankFile, "<?php\nreturn ['enabled'=>true,'bank_bin'=>'970422','bank_name'=>'TEST ONLY','account_number'=>'0000000000','account_name'=>'OFFLINE TEST ONLY'];\n");
  const env = { ...process.env, DB_DRIVER: 'https', DB_BRIDGE_URL: url, DB_BRIDGE_TOKEN: token,
    DB_BRIDGE_LOCAL_TEST: '1', DB_HOST: cfg.host, DB_PORT: String(cfg.port), DB_USER: cfg.user,
    DB_PASS: cfg.password, DB_NAME: database };
  const suites = process.env.BRIDGE_TEST_SUITE === 'paypal'
    ? ['support/tests/test_paypal.php'] : ['support/tests/test_luong.php','support/tests/test_paypal.php'];
  for (const suite of suites) {
    exitCode = await new Promise((resolve, reject) => {
      const child = spawn('D:/data/php/php.exe', [suite], { cwd: root, env, stdio: 'inherit' });
      child.once('error', reject); child.once('exit', code => resolve(code ?? 1));
    });
    if (exitCode) break;
  }
} finally {
  const report = { date: new Date().toISOString(), transport: 'PHP cURL -> HTTP loopback -> Node mysql2 -> MariaDB 10.4',
    cloudVerified: false, bridgeContractFailures: fails, applicationSuiteExitCode: exitCode,
    metrics: bridge.metrics, openSessionsBeforeCleanup: bridge.sessions.size };
  await bridge.close();
  await admin.query(`DROP DATABASE \`${database}\``);
  await admin.end();
  if (oldBank) await writeFile(bankFile, oldBank); else await unlink(bankFile).catch(() => {});
  await writeFile(resolve(root, 'support/tests/ketqua_bridge_local.json'), JSON.stringify(report, null, 2));
  console.log(JSON.stringify(report, null, 2));
}
process.exitCode = exitCode || (fails ? 1 : 0);
