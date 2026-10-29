import { extendedChecks } from './extended.mjs';
import { browserChecks } from './browser.mjs';
import test from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync, spawn } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { createServer } from 'node:net';
import { mkdtemp, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

test('isolated database and real HTTP account lifecycle', { timeout: 180000 }, async t => {
  assert.ok(process.env.TEST_DB_PORT, 'Set TEST_DB_PORT for a disposable loopback database server');
  const php = process.env.PHP_BINARY || 'php';
  const env = { ...process.env, DB_HOST: '127.0.0.1',
    DB_PORT: process.env.TEST_DB_PORT, DB_NAME: `in_beweging_test_${randomBytes(8).toString('hex')}`,
    ACTIVITY_UPLOAD_DIR: '', DB_USER: process.env.TEST_DB_USER || 'root', DB_PASSWORD: process.env.TEST_DB_PASSWORD || '' };
  const db = operation => execFileSync(php, ['tests/database.php', operation], { env, windowsHide: true }).toString();
  db('create');
  t.after(() => db('drop'));
  db('init');
  const state = () => JSON.parse(db('inspect'));
  const sessions = await mkdtemp(join(tmpdir(), 'in-beweging-integration-'));
  env.ACTIVITY_UPLOAD_DIR = join(sessions, 'uploads');
  const socket = createServer();
  await new Promise((resolve, reject) => { socket.once('error', reject); socket.listen(0, '127.0.0.1', resolve); });
  const port = socket.address().port;
  await new Promise(resolve => socket.close(resolve));
  const server = spawn(php, ['-d', `session.save_path=${sessions}`, '-d', 'display_errors=1',
    '-d', 'upload_max_filesize=8M', '-d', 'post_max_size=10M', '-S', `127.0.0.1:${port}`, '-t', '.', 'tests/router.php'], { env, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
  let logs = '', startupError;
  server.on('error', error => { startupError = error; });
  server.stdout.on('data', data => { logs += data; });
  server.stderr.on('data', data => { logs += data; });
  const closed = new Promise(resolve => server.once('close', resolve));
  t.after(async () => { server.kill(); await closed; await rm(sessions, { recursive: true, force: true }); });
  for (let i = 0; i < 50; i++) {
    if (startupError) throw startupError;
    if (server.exitCode !== null) throw new Error(logs);
    try { await fetch(`http://127.0.0.1:${port}/index.php`, { signal: AbortSignal.timeout(500) }); break; }
    catch { if (i === 49) throw new Error(logs); await delay(100); }
  }
  const client = (serverPort = port) => {
    let cookie = '', token = '';
    return {
      async request(path = '/index.php', options = {}) {
        const response = await fetch(`http://127.0.0.1:${serverPort}${path}`, {
          ...options, redirect: 'manual', signal: AbortSignal.timeout(5000),
          headers: { Cookie: cookie, ...options.headers },
        });
        const setCookie = response.headers.getSetCookie().find(value => value.startsWith('PHPSESSID='));
        if (setCookie) cookie = setCookie.split(';')[0];
        const body = await response.text();
        assert.doesNotMatch(body, /Fatal error|Warning:|Parse error/);
        const match = body.match(/name="csrf_token" value="([a-f0-9]{64})"/)
          || body.match(/&quot;csrfToken&quot;:&quot;([a-f0-9]{64})&quot;/);
        if (match) token = match[1];
        return { status: response.status, body };
      },
      post(values) { return this.request('/index.php', { method: 'POST', body: new URLSearchParams({ csrf_token: token, ...values }) }); },
      form(path, values) { return this.request(path, { method: 'POST', body: new URLSearchParams({ csrf_token: token, ...values }) }); },
      upload(data, filename, type) {
        const body = new FormData();
        for (const [key, value] of Object.entries({ csrf_token: token, title: '<script>alert(1)</script>', description: 'CI image' })) body.set(key, value);
        body.set('photo', new Blob([data], { type }), filename);
        return this.request('/pages/activiteit/activiteit.php', { method: 'POST', body });
      },
      settings(settings, validToken = true) { return this.request('/pages/settings/settings-api.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': validToken ? token : 'invalid' },
        body: JSON.stringify({ settings }),
      }); },
    };
  };
  const user = client(), stranger = client();
  const registration = { action: 'register', email: 'ci@example.invalid', username: 'ci_user', fullName: 'CI Test',
    password: 'Test-password-123!', confirmPassword: 'Test-password-123!', day: '1', month: '1', year: '2000' };
  await t.test('unauthenticated settings and home are protected', async () => {
    assert.equal((await stranger.request('/pages/settings/settings-api.php')).status, 401);
    assert.equal((await stranger.request('/pages/activiteit/interactions.php?post_id=1')).status, 401);
    assert.equal((await stranger.request('/pages/home/home.php')).status, 303);
  });
  await user.request();
  await t.test('invalid CSRF cannot register', async () => {
    assert.equal((await user.post({ ...registration, csrf_token: 'invalid' })).status, 303);
    assert.equal(state().users, 0);
    assert.match((await user.request()).body, /sessie is verlopen/);
  });
  await t.test('registration stores a password hash and logs in', async () => {
    assert.equal((await user.post(registration)).status, 303);
    assert.deepEqual(state(), { users: 1, passwordValid: true, hashed: true, xp: 0, completions: 0 });
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 200);
    assert.match((await user.request()).body, /CI beweegpauze/);
  });
  await t.test('duplicate email and username are rejected separately', async () => {
    await stranger.request();
    for (const changes of [{ username: 'another_user' }, { email: 'another@example.invalid' }]) {
      assert.equal((await stranger.post({ ...registration, ...changes })).status, 303);
      assert.match((await stranger.request()).body, /al in gebruik/);
      assert.equal(state().users, 1);
    }
  });
  const settings = { frequency: 30, darkMode: true, workOnly: true, workStart: '09:00', workEnd: '16:00', workDays: [1, 3, 5] };
  await t.test('settings persist and invalid CSRF cannot change them', async () => {
    assert.equal((await user.settings(settings)).status, 200);
    assert.deepEqual(JSON.parse((await user.request('/pages/settings/settings-api.php')).body).settings, settings);
    assert.equal((await user.settings({ frequency: 120 }, false)).status, 400);
    assert.deepEqual(JSON.parse((await user.request('/pages/settings/settings-api.php')).body).settings, settings);
  });
  await t.test('completion awards XP once and rejects invalid CSRF', async () => {
    // Avoid crossing the application's 90-minute time boundary during the pair of requests.
    const remaining = 5400000 - Date.now() % 5400000;
    if (remaining < 5000) await delay(remaining + 50);
    await user.request();
    await user.post({ action: 'complete_challenge', csrf_token: 'invalid' });
    assert.equal(state().xp, 0);
    assert.equal((await user.post({ action: 'complete_challenge' })).status, 303);
    assert.equal(state().xp, 25);
    assert.equal((await user.post({ action: 'complete_challenge' })).status, 303);
    assert.equal(state().xp, 25);
    assert.equal(state().completions, 1);
    assert.match((await user.request()).body, /al voltooid/);
  });
  await t.test('logout protects account and login rejects incorrect passwords', async () => {
    assert.equal((await user.post({ action: 'logout' })).status, 303);
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 401);
    await user.request();
    await user.post({ action: 'login', email: registration.email, password: 'incorrect' });
    assert.match((await user.request()).body, /wachtwoord is onjuist/);
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 401);
    await user.post({ action: 'login', email: registration.email, password: registration.password });
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 200);
    assert.deepEqual(JSON.parse((await user.request('/pages/settings/settings-api.php')).body).settings, settings);
  });
  await browserChecks(t, `http://127.0.0.1:${port}`);
  await extendedChecks({ t, user, client, db, state, env, php, sessions, port });
  assert.doesNotMatch(logs, /PHP (?:Warning|Fatal|Parse)|databasefout|SQLSTATE/);
});
