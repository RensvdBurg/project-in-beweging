import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { createServer } from 'node:net';
import { readdir, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

export async function extendedChecks({ t, user, client, db, state, env, php, sessions, port }) {
  const account = '/pages/settings/account.php';
  await t.test('uploads: valid PNG, disguised executable, empty and oversized files', async () => {
    await user.request('/pages/activiteit/activiteit.php');
    const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=', 'base64');
    assert.equal((await user.upload(png, 'photo.png', 'image/png')).status, 303);
    const files = await readdir(env.ACTIVITY_UPLOAD_DIR);
    assert.equal(files.length, 1);
    assert.match(files[0], /^[a-f0-9]{32}\.png$/);
    assert.match((await user.request('/pages/activiteit/activiteit.php')).body, /&lt;script&gt;alert\(1\)&lt;\/script&gt;/);
    for (const [data, filename, type, message] of [
      [Buffer.from('<?php echo 1; ?>'), 'fake.png', 'image/png', /geldige JPG/],
      [Buffer.alloc(0), 'empty.png', 'image/png', /maximaal 5 MB/],
      [Buffer.alloc(6 * 1024 * 1024), 'large.png', 'image/png', /maximaal 5 MB/],
    ]) assert.match((await user.upload(data, filename, type)).body, message);
    assert.deepEqual(await readdir(env.ACTIVITY_UPLOAD_DIR), files);
  });
  await t.test('migration can upgrade populated baseline twice without data loss', () => db('migrate-check'));
  await t.test('SQL injection cannot log in; profile XSS is escaped', async () => {
    const attacker = client(); await attacker.request();
    await attacker.post({ action: 'login', email: "' OR 1=1 --", password: 'anything' });
    assert.equal((await attacker.request('/pages/settings/settings-api.php')).status, 401);
    await user.request(account);
    const response = await user.form(account, { action: 'profile', name: '<img src=x onerror=alert(1)>', email: 'ci@example.invalid', id: '999' });
    assert.match(response.body, /&lt;img src=x onerror=alert\(1\)&gt;/);
    assert.doesNotMatch(response.body, /<img src=x onerror=alert\(1\)>/);
    await user.form(account, { action: 'profile', name: 'CI Test', email: 'ci@example.invalid' });
  });
  await t.test('two independent PHP servers cannot award the same XP twice', async () => {
    db('reset-completions');
    const socket = createServer();
    await new Promise((resolve, reject) => { socket.once('error', reject); socket.listen(0, '127.0.0.1', resolve); });
    const secondPort = socket.address().port; await new Promise(resolve => socket.close(resolve));
    const server = spawn(php, ['-d', `session.save_path=${sessions}`, '-S', `127.0.0.1:${secondPort}`, '-t', '.', 'tests/router.php'], { env, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
    let logs = '', failure;
    server.on('error', error => { failure = error; });
    server.stdout.on('data', data => { logs += data; }); server.stderr.on('data', data => { logs += data; });
    const closed = new Promise(resolve => server.once('close', resolve));
    try {
      for (let i = 0; i < 50; i++) {
        if (failure) throw failure;
        try { await fetch(`http://127.0.0.1:${secondPort}/index.php`); break; }
        catch { if (i === 49) throw new Error(logs); await delay(100); }
      }
      const other = client(secondPort); await other.request();
      await other.post({ action: 'login', email: 'ci@example.invalid', password: 'Test-password-123!' });
      const remaining = 5400000 - Date.now() % 5400000;
      if (remaining < 10000) await delay(remaining + 50);
      await Promise.all([user.request(), other.request()]);
      const responses = await Promise.all([user.post({ action: 'complete_challenge' }), other.post({ action: 'complete_challenge' })]);
      assert.deepEqual(responses.map(r => r.status), [303, 303]);
      assert.equal(state().xp, 25); assert.equal(state().completions, 1);
      assert.doesNotMatch(logs, /SQLSTATE|databasefout|Fatal error/);
    } finally { server.kill(); await closed; }
  });
  await t.test('challenge change limit allows two changes and rejects the third', async () => {
    db('extra-challenges');
    for (let i = 0; i < 2; i++) {
      await user.post({ action: 'next_challenge' });
      assert.match((await user.request()).body, /Nieuwe opdracht geladen/);
    }
    await user.post({ action: 'next_challenge' });
    assert.match((await user.request()).body, /directe opdrachtwissels voor vandaag al gebruikt/);
  });
  await t.test('settings stay scoped to the session even with another user ID', async () => {
    const other = client(); await other.request();
    await other.post({ action: 'register', email: 'other@example.invalid', username: 'other_user', fullName: 'Other Test', password: 'Other-password-123!', confirmPassword: 'Other-password-123!', day: '1', month: '1', year: '2000' });
    await other.request();
    await other.settings({ frequency: 120, userId: 1 });
    assert.equal(JSON.parse((await user.request('/pages/settings/settings-api.php?user_id=2')).body).settings.frequency, 30);
    assert.equal(JSON.parse((await other.request('/pages/settings/settings-api.php?user_id=1')).body).settings.frequency, 120);
    await other.request(account);
    assert.equal((await other.form(account, { action: 'delete', confirm_delete: '1' })).status, 303);
  });
  await t.test('leaderboard ordering, tie-break and response budget with 1000 accounts', async () => {
    const ids = JSON.parse(db('rank-fixtures'));
    try {
      for (let i = 0; i < ids.length; i++) {
        const response = await user.request(`/pages/ranglijst/profile.php?id=${ids[i]}`);
        assert.equal(response.status, 200);
        assert.match(response.body, new RegExp('>#' + (i + 1) + '</p>'));
      }
      const durations = [];
      for (let i = 0; i < 10; i++) {
        const start = performance.now();
        const response = await user.request('/index.php?screen=ranglijst');
        assert.equal(response.status, 200); assert.match(response.body, /rank_0000/);
        durations.push(performance.now() - start);
      }
      assert.ok(Math.max(...durations) < 2000, 'Leaderboard exceeded the 2-second smoke budget');
      await writeFile('work/reports/leaderboard-performance.json', JSON.stringify({ rows: 1000, durationsMs: durations }, null, 2));
    } finally { db('clear-rank-fixtures'); }
  });
  await t.test('account edits, password replacement and confirmed deletion', async () => {
    await user.request(account);
    assert.equal((await user.form(account, { action: 'delete' })).status, 400);
    assert.match((await user.form(account, { action: 'password', current_password: 'wrong', new_password: 'New-password-123!', confirm_password: 'New-password-123!' })).body, /huidige wachtwoord is onjuist/);
    assert.match((await user.form(account, { action: 'password', current_password: 'Test-password-123!', new_password: 'New-password-123!', confirm_password: 'New-password-123!' })).body, /wachtwoord is gewijzigd/);
    await user.post({ action: 'logout' }); await user.request();
    await user.post({ action: 'login', email: 'ci@example.invalid', password: 'Test-password-123!' });
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 401);
    await user.request(); await user.post({ action: 'login', email: 'ci@example.invalid', password: 'New-password-123!' });
    await user.request(account);
    assert.equal((await user.form(account, { action: 'delete', confirm_delete: '1' })).status, 303);
    assert.equal((await user.request('/pages/settings/settings-api.php')).status, 401);
    assert.equal(state().users, 0);
  });
  await t.test('login throttling persists across new sessions', async () => {
    for (let i = 0; i < 6; i++) {
      const attacker = client(); await attacker.request();
      const response = await attacker.post({ action: 'login', email: 'unknown@example.invalid', password: 'wrong' });
      assert.equal(response.status, i < 5 ? 303 : 429);
    }
  });
  await t.test('bounded load: 30 requests at concurrency 5 stay within latency budget', async () => {
    const durations = [];
    for (let batch = 0; batch < 6; batch++) await Promise.all(Array.from({ length: 5 }, async () => {
      const start = performance.now();
      const response = await fetch(`http://127.0.0.1:${port}/index.php`, { signal: AbortSignal.timeout(5000) });
      assert.equal(response.status, 200); assert.match(await response.text(), /loginForm/);
      durations.push(performance.now() - start);
    }));
    durations.sort((a, b) => a - b);
    const p95 = durations[Math.ceil(durations.length * .95) - 1];
    await writeFile('work/reports/performance.json', JSON.stringify({ requests: 30, concurrency: 5, p95Ms: p95, budgetMs: 2000 }, null, 2));
    assert.ok(p95 < 2000, `p95 ${p95.toFixed(0)}ms exceeded 2000ms budget`);
  });
}
