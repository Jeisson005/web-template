import fs from 'node:fs';
import { execSync } from 'node:child_process';

if (!fs.existsSync('.next/BUILD_ID')) {
  console.log('> Production build not found. Running next build automatically...');
  execSync('npm run build', { stdio: 'inherit' });
}
