import assert from 'node:assert/strict';
const target = process.env.STAGING_URL;
assert.ok(target, 'Set STAGING_URL to the authorized staging application URL');
const url = new URL(target);
assert.equal(url.protocol, 'https:', 'Staging must use HTTPS');
assert.equal(url.username + url.password, '', 'Do not put credentials in the URL');
const response = await fetch(url, { signal: AbortSignal.timeout(10000) });
assert.equal(new URL(response.url).protocol, 'https:');
assert.equal(response.status, 200);
assert.match(response.headers.get('content-type') || '', /text\/html/);
assert.ok(response.headers.get('strict-transport-security'), 'Configure HSTS on the HTTPS host');
const cookies = response.headers.getSetCookie();
assert.ok(cookies.some(cookie => /^PHPSESSID=/i.test(cookie) && /;\s*secure/i.test(cookie) && /;\s*httponly/i.test(cookie) && /;\s*samesite=/i.test(cookie)), 'Session cookie needs Secure, HttpOnly and SameSite');
const html = await response.text();
assert.match(html, /loginForm/);
assert.doesNotMatch(html, /(?:src|href)=["']http:\/\//i, 'Mixed HTTP assets');
for (const asset of ['css/output.css', 'css/iconoir.css']) {
  const result = await fetch(new URL(asset, url), { signal: AbortSignal.timeout(10000) });
  assert.equal(result.status, 200, `${asset} missing`);
  assert.ok((await result.text()).length > 100);
}
console.log('Staging HTTPS, session cookie, login and CSS checks passed.');
