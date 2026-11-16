import { readdirSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { execFileSync } from 'node:child_process';

export function findInlineCode(source) {
  const html = source.replace(/<!--[\s\S]*?-->/g, comment => comment.replace(/[^\r\n]/g, ' '));
  const violations = [];
  const report = (index, message) => violations.push({ line: html.slice(0, index).split('\n').length, message });
  const tags = /<([a-z][\w:-]*)\b((?:"[^"]*"|'[^']*'|[^'">])*)>/gi;
  for (const tag of html.matchAll(tags)) {
    const name = tag[1].toLowerCase();
    const attributes = new Map();
    const attrs = /([^\s=\/'"<>`]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g;
    for (const attr of tag[2].matchAll(attrs)) {
      const key = attr[1].toLowerCase();
      const value = attr[2] ?? attr[3] ?? attr[4] ?? '';
      attributes.set(key, value);
      if (key === 'style') report(tag.index, 'Inline style attribute: move CSS to a .css file.');
      if (/^on[a-z]+$/.test(key)) report(tag.index, `Inline ${key} handler: use addEventListener in a .js file.`);
      if (['href', 'src', 'action', 'formaction', 'xlink:href'].includes(key)) {
        const decoded = value.replace(/&#(?:x([\da-f]+)|(\d+));?/gi, (_, hex, decimal) => String.fromCodePoint(parseInt(hex || decimal, hex ? 16 : 10)))
          .replace(/&colon;/gi, ':').replace(/&(?:tab|newline);/gi, '').replace(/[\u0000-\u0020]/g, '');
        if (/^javascript:/i.test(decoded)) report(tag.index, 'JavaScript URL: move behavior to a .js file.');
        if (key === 'src' && name === 'script' && /^data:/i.test(decoded)) report(tag.index, 'Embedded script data URL: use an external .js file.');
      }
    }
    if (name === 'style') report(tag.index, 'Inline <style> block: use an external stylesheet.');
    if (name === 'script') {
      if (!attributes.has('src')) report(tag.index, 'Inline <script> block: use a script with src.');
      const after = html.slice(tag.index + tag[0].length);
      const close = after.search(/<\/script\s*>/i);
      if (close < 0 || after.slice(0, close).trim()) report(tag.index, 'Script tags must be closed and have no inline body, including when src is present.');
    }
  }
  return violations;
}

function phpFiles(dir) {
  return readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) return ['.git', 'node_modules', 'vendor', 'uploads', 'work'].includes(entry.name) ? [] : phpFiles(path);
    return entry.isFile() && entry.name.endsWith('.php') ? [path] : [];
  });
}

if (process.argv[1] && resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const files = phpFiles('.');
  if (!files.length) throw new Error('No PHP templates found');
  let failures = 0;
  for (const file of files) {
    const html = execFileSync(process.env.PHP_BINARY || 'php', ['scripts/template-source.php', file], { encoding: 'utf8', windowsHide: true });
    for (const error of findInlineCode(html)) {
      console.error(`${file}:${error.line}: ${error.message}`);
      failures++;
    }
  }
  console.log(`Checked ${files.length} PHP files for inline JavaScript/CSS; ${failures} violation(s).`);
  process.exitCode = failures ? 1 : 0;
}
