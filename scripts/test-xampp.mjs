// Runs tests against a new MariaDB data directory, never the XAMPP data directory.
import { mkdtemp, mkdir, rm } from 'node:fs/promises';
import { join } from 'node:path';
import { createServer, connect } from 'node:net';
import { spawn, execFileSync } from 'node:child_process';
import { setTimeout as delay } from 'node:timers/promises';

if (process.platform !== 'win32') throw new Error('Use npm test with TEST_DB_PORT on Linux; this helper is for Windows XAMPP.');
const bin = process.env.XAMPP_MYSQL_BIN || 'C:/xampp/mysql/bin';
// Older MariaDB Windows binaries cannot bootstrap reliably in long OS temp paths.
const tempRoot = process.env.TEST_TMP_DIR || join(process.cwd(), 'work');
await mkdir(tempRoot, { recursive: true });
const data = await mkdtemp(join(tempRoot, 'mariadb-'));
let server, closed, logs = '', startupError;
try {
  const socket = createServer();
  await new Promise((resolve, reject) => { socket.once('error', reject); socket.listen(0, '127.0.0.1', resolve); });
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  execFileSync(join(bin, 'mysql_install_db.exe'), [`--datadir=${data}`, `--port=${port}`], { windowsHide: true, timeout: 60000, stdio: 'pipe' });
  server = spawn(join(bin, 'mysqld.exe'), [`--defaults-file=${join(data, 'my.ini')}`, '--bind-address=127.0.0.1', `--port=${port}`, '--console'], {
    cwd: data, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'],
  });
  closed = new Promise(resolve => server.once('close', resolve));
  server.on('error', error => { startupError = error; });
  for (const stream of [server.stdout, server.stderr]) stream.on('data', chunk => { logs = (logs + chunk).slice(-16000); });
  for (let i = 0; i < 100; i++) {
    if (startupError) throw startupError;
    if (server.exitCode !== null) throw new Error(logs);
    try {
      await new Promise((resolve, reject) => {
        const connection = connect(port, '127.0.0.1');
        connection.once('connect', () => { connection.destroy(); resolve(); });
        connection.once('error', reject);
        connection.setTimeout(500, () => { connection.destroy(); reject(new Error('Database readiness timeout')); });
      });
      break;
    } catch { if (i === 99) throw new Error(logs); await delay(100); }
  }
  const tests = spawn(process.execPath, ['--test', 'tests/template-code.test.mjs', 'tests/login-smoke.test.mjs', 'tests/integration.test.mjs'], {
    windowsHide: true, stdio: 'inherit', env: { ...process.env, TEST_DB_PORT: String(port), TEST_DB_USER: 'root', TEST_DB_PASSWORD: '',
      PHP_BINARY: process.env.PHP_BINARY || 'C:/xampp/php/php.exe' },
  });
  const exitCode = await new Promise((resolve, reject) => { tests.once('error', reject); tests.once('close', resolve); });
  process.exitCode = exitCode ?? 1;
} finally {
  if (server) {
    // Graceful shutdown of only the server created above, using its private config.
    try { execFileSync(join(bin, 'mysqladmin.exe'), [`--defaults-file=${join(data, 'my.ini')}`, '--host=127.0.0.1', '--user=root', 'shutdown'], { windowsHide: true, timeout: 10000, stdio: 'pipe' }); }
    catch { server.kill(); }
    await closed;
  }
  await rm(data, { recursive: true, force: true, maxRetries: 10, retryDelay: 200 });
}
