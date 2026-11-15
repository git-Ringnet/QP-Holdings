import { request } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { AUTH_DIR, readState, sql } from './helpers';

/** Dọn mọi dữ liệu thử: trang, ảnh (kèm file WebP), thống kê bulk, về cài đặt mặc định. */
export default async function globalTeardown() {
  try {
    const st = readState();
    const created = JSON.parse(fs.readFileSync(path.join(AUTH_DIR, 'created.json'), 'utf8'));
    const ctx = await request.newContext({ baseURL: st.base, storageState: path.join(AUTH_DIR, 'state.json') });
    for (const id of created.page) await ctx.delete(`wp-json/wp/v2/pages/${id}?force=true`, { headers: { 'X-WP-Nonce': st.nonce } });
    for (const id of created.media) await ctx.delete(`wp-json/wp/v2/media/${id}?force=true`, { headers: { 'X-WP-Nonce': st.nonce } });
    await ctx.dispose();
  } catch (e) {
    console.warn('teardown:', (e as Error).message);
  }
  sql(`DELETE FROM wp_postmeta WHERE meta_key IN ('_qpwc_status','_qpwc_reason','_qpwc_src_bytes','_qpwc_webp_bytes')`);
  sql(`DELETE FROM wp_options WHERE option_name IN ('qpwc_bulk','qpwc_log','qpwc_settings')`);
}
