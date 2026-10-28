import test from 'node:test';
import assert from 'node:assert/strict';
import { findInlineCode } from '../scripts/check-template-code.mjs';

test('external JS/CSS, Tailwind classes and HTML comments are permitted', () => {
  assert.deepEqual(findInlineCode(`<!-- <style>example</style> -->
    <link rel="stylesheet" href="css/app.css">
    <script defer src="assets/app.js?v=1"></script>
    <button class="flex hover:bg-orange" data-note="style='example'">OK</button>`), []);
});

for (const [name, html] of [
  ['script block', '<script>alert(1)</script>'],
  ['script body alongside src', '<script src="app.js">alert(1)</script>'],
  ['unclosed script', '<script src="app.js">'],
  ['CSS block', '<STYLE>body { color: red }</STYLE>'],
  ['quoted style', '<div style="color:red">'],
  ['unquoted style', '<div STYLE=color:red>'],
  ['mixed-case multiline event', '<button\n OnClick = "run()">'],
  ['unquoted event', '<input onchange=run()>'],
  ['encoded javascript URL', '<a href="java&#x73;cript&colon;alert(1)">'],
  ['embedded script', '<script src="data:text/javascript,alert(1)"></script>'],
]) test(`rejects ${name}`, () => assert.ok(findInlineCode(html).length > 0));

test('reports source line and ignores greater-than signs in quoted attributes', () => {
  const result = findInlineCode('\n<button title="a > b" onclick="run()">');
  assert.equal(result.length, 1);
  assert.equal(result[0].line, 2);
});
