import { test, expect, request as pwRequest } from '@playwright/test';
import * as path from 'path';
import { AUTH_DIR, createPage, imgHtml, readState, setPluginEnabled, settle, uploadOk } from './helpers';

/**
 * WP-17: hiển thị đúng trên Chrome, Firefox, Safari (WebKit) và điện thoại (Pixel 5, iPhone 13).
 * Chạy lại toàn bộ file này cho từng project trong playwright.config.ts.
 * Lưu ý: WebKit của Playwright mô phỏng Safari; vẫn nên xem thêm 1 lần trên Safari/iPhone thật.
 */
let link = '';

test.beforeAll(async () => {
  setPluginEnabled(true, 80);
  const api = await pwRequest.newContext({ baseURL: readState().base, storageState: path.join(AUTH_DIR, 'state.json') });
  const photo = await uploadOk(api, 'photo.jpg');
  const alpha = await uploadOk(api, 'alpha.png');
  const p = await createPage(api, `qpwc-browser-${Date.now()}`, `<p>WP-17</p>${imgHtml(photo)}${imgHtml(alpha)}`);
  link = p.link;
  await api.dispose();
});

test('WP-17 ảnh hiển thị đầy đủ, dùng WebP, không vỡ ảnh', async ({ page, browserName }, testInfo) => {
  const failed: string[] = [];
  page.on('requestfailed', (r) => { if (r.resourceType() === 'image') failed.push(r.url()); });
  await page.goto(link);
  await settle(page);

  const imgs = page.locator('picture.qpwc img');
  expect(await imgs.count()).toBeGreaterThanOrEqual(2);

  const info = await imgs.evaluateAll((list) =>
    list.map((i) => ({ src: (i as HTMLImageElement).currentSrc, ok: (i as HTMLImageElement).complete && (i as HTMLImageElement).naturalWidth > 0, w: (i as HTMLImageElement).clientWidth })),
  );
  for (const i of info) {
    expect(i.ok, `ảnh không hiển thị được: ${i.src}`).toBeTruthy();
    expect(i.w, 'ảnh có chiều rộng hiển thị').toBeGreaterThan(50);
    expect(i.src, 'trình duyệt chọn WebP').toMatch(/\.webp(\?|$)/);
  }
  expect(failed, 'không có ảnh tải lỗi').toEqual([]);
  // Ảnh không tràn khỏi màn hình điện thoại
  const vw = page.viewportSize()!.width;
  for (const i of info) expect(i.w).toBeLessThanOrEqual(vw);

  await testInfo.attach(`man-hinh-${testInfo.project.name}`, { body: await page.screenshot({ fullPage: true }), contentType: 'image/png' });
  testInfo.annotations.push({ type: 'browser', description: `${browserName} ${testInfo.project.name}: ${info.length} ảnh OK` });
});
