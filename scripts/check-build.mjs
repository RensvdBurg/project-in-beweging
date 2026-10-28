import { statSync, readFileSync } from 'node:fs';
for (const file of ['css/output.css', 'css/iconoir.css', 'css/iconoir-LICENSE.txt']) {
  const output = statSync(file);
  if (!output.isFile() || output.size === 0) throw new Error(file + ' is empty or missing');
  console.log('Build verified: ' + file + ', ' + output.size + ' bytes');
}
if (!readFileSync('css/iconoir.css', 'utf8').includes('.iconoir-')) throw new Error('Missing Iconoir definitions');
