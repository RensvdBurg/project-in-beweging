import test from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:net';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

test('public login page responds with a login form and CSRF token', { timeout: 20000 }, async () => {
  const socket = createServer();
  await new Promise((resolve, reject) => { socket.once('error', reject); socket.listen(0, '127.0.0.1', resolve); });
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  const sessions = await mkdtemp(join(tmpdir(), 'in-beweging-ci-'));
  const server = spawn(process.env.PHP_BINARY || 'php', [
    '-d', `session.save_path=${sessions}`, '-d', 'display_errors=1',
    '-S', `127.0.0.1:${port}`, '-t', '.'
  ], { windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'], env: {
    ...process.env, DB_HOST: '127.0.0.1', DB_PORT: '1', DB_NAME: 'ci_unused', DB_USER: 'ci_unused', DB_PASSWORD: 'ci_unused'
  } });
  let logs = '', startupError;
  server.on('error', error => { startupError = error; });
  server.stdout.on('data', chunk => { logs += chunk; });
  server.stderr.on('data', chunk => { logs += chunk; });
  const closed = new Promise(resolve => server.once('close', resolve));
  try {
    let response;
    for (let attempt = 0; attempt < 40; attempt++) {
      if (startupError) throw startupError;
      if (server.exitCode !== null) throw new Error(`PHP server exited: ${logs}`);
      try {
        response = await fetch(`http://127.0.0.1:${port}/index.php`, { redirect: 'manual', signal: AbortSignal.timeout(1000) });
        break;
      } catch { await delay(100); }
    }
    assert.ok(response, `PHP server did not become ready: ${logs}`);
    assert.equal(response.status, 200);
    const html = await response.text();
    assert.match(html, /UpMove/);
    assert.match(html, /id="loginForm"/);
    assert.match(html, /name="action" value="login"/);
    assert.match(html, /name="csrf_token" value="[a-f0-9]{64}"/);
    assert.doesNotMatch(html, /Fatal error|Warning:|Parse error/);
  } catch (error) {
    console.error(logs);
    throw error;
  } finally {
    server.kill();
    await closed;
    await rm(sessions, { recursive: true, force: true });
  }
});
