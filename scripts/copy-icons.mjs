import { copyFileSync, mkdirSync } from 'node:fs';
mkdirSync('css', { recursive: true });
copyFileSync('node_modules/iconoir/css/iconoir.css', 'css/iconoir.css');
copyFileSync('node_modules/iconoir/LICENSE', 'css/iconoir-LICENSE.txt');
