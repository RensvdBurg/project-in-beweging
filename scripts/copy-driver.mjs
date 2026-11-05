import { copyFileSync, mkdirSync } from 'node:fs';

mkdirSync('assets/vendor', { recursive: true });
copyFileSync(
  'node_modules/driver.js/dist/driver.js.iife.js',
  'assets/vendor/driver.js.iife.js',
);
