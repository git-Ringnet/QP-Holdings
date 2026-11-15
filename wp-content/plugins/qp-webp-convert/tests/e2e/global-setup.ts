import { request, FullConfig } from '@playwright/test';
import { execFileSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';
import { AUTH_DIR, FIX } from './helpers';

export default async function globalSetup(config: FullConfig) {
  fs.mkdirSync(AUTH_DIR, { recursive: true });
  fs.mkdirSync(path.join(__dirname, 'results'), { recursive: true });
  fs.writeFileSync(path.join(AUTH_DIR, 'created.json'), JSON.stringify({ media: [], page: [] }));
  execFileSync(process.env.QP_PHP || 'C:/xampp/php/php.exe', [path.join(__dirname, 'fixtures', 'make-fixtures.php'), FIX]);

  const base = config.projects[0].use.baseURL as string;
  const passwords = [process.env.QP_PASS, 'minhhieu2002', 'admin123'].filter(Boolean) as string[];
  const user = process.env.QP_USER || 'admin';
  const ctx = await request.newContext({ baseURL: base });
  let ok = false;
  for (const pwd of passwords) {
    await ctx.get('wp-login.php');
    await ctx.post('wp-login.php', {
      form: { log: user, pwd, 'wp-submit': 'Log In', redirect_to: base + 'wp-admin/', testcookie: '1' },
    });
    const check = await ctx.get('wp-admin/admin-ajax.php?action=rest-nonce');
    const txt = (await check.text()).trim();
    if (check.ok() && /^[a-f0-9]{10}$/.test(txt)) {
      fs.writeFileSync(path.join(AUTH_DIR, 'nonce.json'), JSON.stringify({ nonce: txt, base }));
      await ctx.storageState({ path: path.join(AUTH_DIR, 'state.json') });
      ok = true;
      break;
    }
  }
  if (!ok) throw new Error('Không đăng nhập được wp-admin (đặt QP_USER / QP_PASS).');
  await ctx.dispose();
}
