import { readdirSync } from 'node:fs';
import { join } from 'node:path';
import { spawnSync } from 'node:child_process';
const excluded = new Set(['.git', 'node_modules', 'vendor', 'uploads']);
function files(dir) {
  return readdirSync(dir, { withFileTypes: true }).flatMap(entry => {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) return excluded.has(entry.name) ? [] : files(path);
    return entry.isFile() && entry.name.endsWith('.php') ? [path] : [];
  });
}
const paths = files('.');
if (!paths.length) throw new Error('No PHP files found');
let failed = false;
for (const path of paths) {
  const result = spawnSync(process.env.PHP_BINARY || 'php', ['-l', path], { stdio: 'inherit', windowsHide: true });
  if (result.error) throw result.error;
  if (result.status !== 0) failed = true;
}
console.log(`Checked ${paths.length} PHP files.`);
process.exitCode = failed ? 1 : 0;
