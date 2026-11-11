import { APIRequestContext, Page, expect } from '@playwright/test';
import { execFileSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

export const AUTH_DIR = path.join(__dirname, '.auth');
export const FIX = path.join(__dirname, 'fixtures', 'out');
export const PLUGIN_ROOT = path.resolve(__dirname, '..', '..');
export const WP_ROOT = path.resolve(PLUGIN_ROOT, '..', '..', '..');
export const UPLOADS = process.env.QP_UPLOADS || path.join(WP_ROOT, 'wp-content', 'uploads');
export const MYSQL = process.env.QP_MYSQL || 'C:/xampp/mysql/bin/mysql.exe';
export const DB = process.env.QP_DB || 'qph90680_db';

export type State = { nonce: string; base: string };
export const readState = (): State => JSON.parse(fs.readFileSync(path.join(AUTH_DIR, 'nonce.json'), 'utf8'));

export function sql(query: string): string {
  return execFileSync(MYSQL, ['-uroot', DB, '-N', '-B', '-e', query], { encoding: 'utf8' }).trim();
}

/** Đường dẫn trên đĩa của một URL trong uploads. */
export function diskPath(url: string): string {
  const rel = decodeURIComponent(new URL(url).pathname).replace(/^.*\/wp-content\/uploads\//, '');
  return path.join(UPLOADS, rel);
}

export function mime(file: string): string {
  const ext = path.extname(file).toLowerCase();
  const map: Record<string, string> = {
    '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg', '.png': 'image/png', '.gif': 'image/gif', '.svg': 'image/svg+xml',
  };
  return map[ext] || 'application/octet-stream';
}

const TRACK = path.join(AUTH_DIR, 'created.json');
export function track(kind: 'media' | 'page', id: number) {
  const cur = fs.existsSync(TRACK) ? JSON.parse(fs.readFileSync(TRACK, 'utf8')) : { media: [], page: [] };
  cur[kind].push(id);
  fs.writeFileSync(TRACK, JSON.stringify(cur));
}

export async function uploadMedia(api: APIRequestContext, file: string, name?: string) {
  const buf = fs.readFileSync(path.join(FIX, file));
  return api.post('wp-json/wp/v2/media', {
    headers: {
      'X-WP-Nonce': readState().nonce,
      'Content-Disposition': `attachment; filename="${name || file}"`,
      'Content-Type': mime(file),
    },
    data: buf,
    timeout: 150_000,
  });
}

/** Tải lên, đảm bảo thành công, tự ghi nhớ để dọn sau. Tên file được làm duy nhất. */
export async function uploadOk(api: APIRequestContext, file: string) {
  const unique = file.replace(/(\.[a-z]+)$/i, `-${Date.now()}-${Math.floor(Math.random() * 1e4)}$1`);
  const res = await uploadMedia(api, file, unique);
  expect(res.status(), await res.text()).toBe(201);
  const j = await res.json();
  track('media', j.id);
  return j;
}

export async function createPage(api: APIRequestContext, title: string, html: string, lang?: string) {
  const res = await api.post('wp-json/wp/v2/pages' + (lang ? `?lang=${lang}` : ''), {
    headers: { 'X-WP-Nonce': readState().nonce },
    data: { title, content: html, status: 'publish' },
  });
  expect(res.status(), await res.text()).toBeLessThan(300);
  const j = await res.json();
  track('page', j.id);
  return j;
}

export async function updatePage(api: APIRequestContext, id: number, html: string) {
  const res = await api.post(`wp-json/wp/v2/pages/${id}`, { headers: { 'X-WP-Nonce': readState().nonce }, data: { content: html } });
  expect(res.status()).toBeLessThan(300);
  return res.json();
}

/** Thẻ img có srcset giống WordPress sinh ra trong nội dung bài. */
export function imgHtml(media: any, size = 'large'): string {
  const sizes = media.media_details?.sizes || {};
  const parts = Object.values<any>(sizes)
    .filter((s) => s.source_url && !/\.webp$/.test(s.source_url))
    .map((s) => `${s.source_url} ${s.width}w`);
  const main = sizes[size]?.source_url || media.source_url;
  return `<img src="${main}" srcset="${parts.join(', ')}" sizes="(max-width: 800px) 100vw, 800px" alt="qpwc-test" class="wp-image-${media.id}">`;
}

/** Bật/tắt plugin bằng cách ghi thẳng option (nhanh, không phụ thuộc giao diện). */
export function setPluginEnabled(enabled: boolean, quality = 80) {
  const cur = sql(`SELECT option_value FROM wp_options WHERE option_name='qpwc_settings'`);
  const backup = /s:9:"backup_at";i:(\d+);/.exec(cur)?.[1] || '0';
  const v = `a:3:{s:7:"enabled";i:${enabled ? 1 : 0};s:7:"quality";i:${quality};s:9:"backup_at";i:${backup};}`;
  sql(`DELETE FROM wp_options WHERE option_name='qpwc_settings'`);
  sql(`INSERT INTO wp_options (option_name, option_value, autoload) VALUES ('qpwc_settings', '${v}', 'no')`);
}

/** Chờ trang tải xong và các ảnh trong uploads đã tải xong (không dùng networkidle vì theme có kết nối nền). */
export async function settle(page: Page) {
  await page.waitForLoadState('load');
  await page.waitForFunction(
    () => [...document.images].filter((i) => /uploads/.test(i.currentSrc || i.src)).every((i) => i.complete),
    null,
    { timeout: 30_000 },
  );
}
