import { test, expect, Page } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import { createPage, diskPath, imgHtml, readState, setPluginEnabled, settle, sql, updatePage, uploadMedia, uploadOk } from './helpers';

/**
 * Kiểm thử chức năng — mỗi test mang mã WP-xx của bảng nghiệm thu (tab "2. Ảnh WebP").
 * Chạy tuần tự vì các test dùng chung dữ liệu và cài đặt plugin.
 */
test.describe.configure({ mode: 'serial' });

const METRICS = path.join(__dirname, 'results', 'metrics.json');
function metric(key: string, value: unknown) {
  const cur = fs.existsSync(METRICS) ? JSON.parse(fs.readFileSync(METRICS, 'utf8')) : {};
  cur[key] = value;
  fs.writeFileSync(METRICS, JSON.stringify(cur, null, 2));
}

let photo: any;
let alpha: any;
let pageA: any;

test.beforeAll(async () => {
  setPluginEnabled(true, 80);
});

test('WP-09 JPG và PNG tải lên được tự động chuyển sang WebP', async ({ request }) => {
  photo = await uploadOk(request, 'photo.jpg');
  alpha = await uploadOk(request, 'alpha.png');
  expect(fs.existsSync(diskPath(photo.source_url) + '.webp'), 'JPG → .jpg.webp').toBeTruthy();
  expect(fs.existsSync(diskPath(alpha.source_url) + '.webp'), 'PNG → .png.webp').toBeTruthy();
  // Ảnh gốc còn nguyên
  expect(fs.existsSync(diskPath(photo.source_url))).toBeTruthy();
  expect(fs.existsSync(diskPath(alpha.source_url))).toBeTruthy();
});

test('WP-10 mọi kích thước WordPress tạo ra đều có WebP', async () => {
  for (const m of [photo, alpha]) {
    const sizes = Object.entries<any>(m.media_details.sizes);
    expect(sizes.length).toBeGreaterThan(1);
    const missing = sizes.filter(([, s]) => !fs.existsSync(diskPath(s.source_url) + '.webp')).map(([k]) => k);
    expect(missing, `cỡ thiếu WebP của #${m.id}: ${missing}`).toEqual([]);
  }
});

test('WP-11 PNG trong suốt giữ nền trong suốt', async ({ page, request }) => {
  const webpUrl = alpha.source_url + '.webp';
  const res = await request.get(webpUrl);
  expect(res.status()).toBe(200);
  await page.goto('about:blank');
  const alphaVals = await page.evaluate(async (url) => {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    await new Promise((ok, err) => { img.onload = ok; img.onerror = err; img.src = url; });
    const c = document.createElement('canvas');
    c.width = img.naturalWidth; c.height = img.naturalHeight;
    const ctx = c.getContext('2d')!;
    ctx.drawImage(img, 0, 0);
    const corner = ctx.getImageData(3, Math.floor(c.height / 2), 1, 1).data[3];
    const center = ctx.getImageData(Math.floor(c.width / 2), Math.floor(c.height / 2), 1, 1).data[3];
    return { corner, center };
  }, webpUrl).catch(() => null);
  if (alphaVals) {
    expect(alphaVals.corner, 'góc ảnh phải trong suốt').toBeLessThan(30);
    expect(alphaVals.center, 'tâm hình phải đặc').toBeGreaterThan(200);
  } else {
    // Trang about:blank không đọc được ảnh khác nguồn: kiểm tra qua trang cùng nguồn.
    await page.goto('wp-login.php');
    const v = await page.evaluate(async (url) => {
      const img = new Image();
      await new Promise((ok, err) => { img.onload = ok; img.onerror = err; img.src = url; });
      const c = document.createElement('canvas');
      c.width = img.naturalWidth; c.height = img.naturalHeight;
      const ctx = c.getContext('2d')!;
      ctx.drawImage(img, 0, 0);
      return { corner: ctx.getImageData(3, Math.floor(c.height / 2), 1, 1).data[3], center: ctx.getImageData(Math.floor(c.width / 2), Math.floor(c.height / 2), 1, 1).data[3] };
    }, webpUrl);
    expect(v.corner).toBeLessThan(30);
    expect(v.center).toBeGreaterThan(200);
  }
});

test('WP-12 GIF và SVG giữ nguyên, hiển thị đúng, không báo lỗi', async ({ request }) => {
  const gif = await uploadOk(request, 'anim.gif');
  expect(fs.existsSync(diskPath(gif.source_url) + '.webp'), 'GIF không được chuyển').toBeFalsy();
  const g = await request.get(gif.source_url);
  expect(g.status()).toBe(200);
  expect(g.headers()['content-type']).toContain('image/gif');

  const svgRes = await uploadMedia(request, 'vector.svg', `vector-${Date.now()}.svg`);
  if (svgRes.status() === 201) {
    const svg = await svgRes.json();
    sql(`SELECT 1`);
    expect(fs.existsSync(diskPath(svg.source_url) + '.webp'), 'SVG không được chuyển').toBeFalsy();
    const s = await request.get(svg.source_url);
    expect(s.status()).toBe(200);
    await request.delete(`wp-json/wp/v2/media/${svg.id}?force=true`, { headers: { 'X-WP-Nonce': readState().nonce } });
  } else {
    test.info().annotations.push({ type: 'note', description: `SVG bị WordPress từ chối tải lên (HTTP ${svgRes.status()}): site không cho SVG → Không áp dụng` });
  }
});

test('WP-21 ảnh hỏng không làm hỏng việc tải lên, website không trắng trang', async ({ request, page }) => {
  const bad = await uploadMedia(request, 'broken.jpg', `broken-${Date.now()}.jpg`);
  expect(bad.status(), 'không được lỗi máy chủ 5xx').toBeLessThan(500);
  if (bad.status() === 201) {
    const j = await bad.json();
    const del = await request.delete(`wp-json/wp/v2/media/${j.id}?force=true`, { headers: { 'X-WP-Nonce': readState().nonce } });
    expect(del.status()).toBeLessThan(300);
  }
  // Sau đó vẫn tải ảnh tốt bình thường và trang chủ vẫn hiển thị.
  const good = await uploadOk(request, 'photo3.jpg');
  expect(fs.existsSync(diskPath(good.source_url) + '.webp')).toBeTruthy();
  const resp = await page.goto('');
  expect(resp!.status()).toBe(200);
  expect(((await page.content()).length)).toBeGreaterThan(5000);
  // Nhật ký lỗi hiển thị được trong trang quản trị
  await page.goto('wp-admin/upload.php?page=qp-webp-convert');
  await expect(page.locator('#qpwc-log')).toBeVisible();
});

test('WP-04 ảnh rất lớn (6000×4000) vẫn xử lý xong, không hết bộ nhớ', async ({ request }) => {
  const t0 = Date.now();
  const big = await uploadOk(request, 'big.jpg');
  const secs = (Date.now() - t0) / 1000;
  metric('WP-04_big_upload_seconds', secs);
  expect(fs.existsSync(diskPath(big.source_url) + '.webp') || Object.values<any>(big.media_details.sizes).every((s) => fs.existsSync(diskPath(s.source_url) + '.webp'))).toBeTruthy();
  const st = sql(`SELECT meta_value FROM wp_postmeta WHERE post_id=${big.id} AND meta_key='_qpwc_status'`);
  expect(['done', 'skipped'], 'trạng thái ảnh lớn').toContain(st);
});

test.describe('hiển thị trên website', () => {
  test('WP-16 + WP-05 trang tải ảnh WebP, đúng Content-Type', async ({ page, request }) => {
    pageA = await createPage(request, `qpwc-A-${Date.now()}`, `<p>Thử</p>${imgHtml(photo)}${imgHtml(alpha)}`);
    const seen: { url: string; type: string; status: number; size: number }[] = [];
    page.on('response', async (r) => {
      if (r.request().resourceType() === 'image') {
        const body = await r.body().catch(() => Buffer.alloc(0));
        seen.push({ url: r.url(), type: r.headers()['content-type'] || '', status: r.status(), size: body.length });
      }
    });
    await page.goto(pageA.link);
    await settle(page);
    const pics = page.locator('picture.qpwc source[type="image/webp"]');
    expect(await pics.count()).toBeGreaterThanOrEqual(2);
    const mine = seen.filter((s) => /qpwc|photo|alpha/.test(s.url) && /uploads/.test(s.url));
    expect(mine.length).toBeGreaterThan(0);
    for (const s of mine) {
      expect(s.url, 'URL ảnh tải về').toMatch(/\.webp(\?|$)/);
      expect(s.type, 'Content-Type').toContain('image/webp');
      expect(s.status).toBe(200);
    }
    // Ảnh thực sự hiển thị
    const ok = await page.evaluate(() => [...document.querySelectorAll('picture.qpwc img')].every((i) => (i as HTMLImageElement).complete && (i as HTMLImageElement).naturalWidth > 0));
    expect(ok).toBeTruthy();
    const cur = await page.evaluate(() => [...document.querySelectorAll('picture.qpwc img')].map((i) => (i as HTMLImageElement).currentSrc));
    for (const u of cur) expect(u).toMatch(/\.webp(\?|$)/);
  });

  test('WP-20 đo mức giảm dung lượng ảnh của trang (bật so với tắt WebP)', async ({ browser }) => {
    const measure = async () => {
      // Mỗi lần đo dùng một trình duyệt sạch (không cache) để số liệu công bằng.
      const ctx = await browser.newContext();
      const page = await ctx.newPage();
      let total = 0;
      let n = 0;
      const urls: string[] = [];
      const pending: Promise<void>[] = [];
      const h = (r: any) => {
        if (r.request().resourceType() === 'image' && /uploads/.test(r.url())) {
          pending.push(
            r.body().then((b: Buffer) => {
              total += b.length;
              n++;
              urls.push(`${r.url().split('/').pop()} ${b.length}B`);
            }, () => undefined),
          );
        }
      };
      page.on('response', h);
      await page.goto(pageA.link + (pageA.link.includes('?') ? '&' : '?') + 'nocache=' + Date.now());
      await settle(page);
      page.off('response', h);
      await Promise.all(pending);
      await ctx.close();
      return { total, n, urls };
    };
    setPluginEnabled(false);
    const off = await measure();
    setPluginEnabled(true);
    const on = await measure();
    metric('WP-20_page_image_bytes_off', off.total);
    metric('WP-20_page_image_bytes_on', on.total);
    metric('WP-20_saving_percent', off.total ? +(100 - (100 * on.total) / off.total).toFixed(1) : 0);
    expect(off.n).toBeGreaterThan(0);
    expect(on.total, `bật=${on.total}B [${on.urls}] tắt=${off.total}B [${off.urls}]`).toBeLessThan(off.total);
  });

  test('WP-19 chất lượng ảnh chấp nhận được (PSNR giữa ảnh gốc và WebP)', async ({ page }) => {
    await page.goto('wp-login.php');
    const psnr = await page.evaluate(async ({ a, b }) => {
      const load = (u: string) => new Promise<HTMLImageElement>((ok, err) => { const i = new Image(); i.onload = () => ok(i); i.onerror = err; i.src = u; });
      const [x, y] = await Promise.all([load(a), load(b)]);
      const w = 800, h = Math.round((800 * x.naturalHeight) / x.naturalWidth);
      const data = (im: HTMLImageElement) => { const c = document.createElement('canvas'); c.width = w; c.height = h; const g = c.getContext('2d')!; g.drawImage(im, 0, 0, w, h); return g.getImageData(0, 0, w, h).data; };
      const d1 = data(x), d2 = data(y);
      let mse = 0;
      for (let i = 0; i < d1.length; i += 4) for (let k = 0; k < 3; k++) { const d = d1[i + k] - d2[i + k]; mse += d * d; }
      mse /= (d1.length / 4) * 3;
      return mse === 0 ? 99 : 10 * Math.log10((255 * 255) / mse);
    }, { a: photo.source_url, b: photo.source_url + '.webp' });
    metric('WP-19_psnr_db_q80', +psnr.toFixed(1));
    expect(psnr, 'PSNR ≥ 30 dB nghĩa là không thấy khác biệt rõ rệt').toBeGreaterThan(30);
  });

  test('WP-18 các bản ngôn ngữ vi / en / zh dùng đúng ảnh WebP, đổi ảnh không gọi API dịch', async ({ page, request }) => {
    const jobsBefore = sql(`SELECT COUNT(*) FROM wp_qp_llm_translate_jobs`);
    const charsBefore = sql(`SELECT COALESCE(SUM(chars),0) FROM wp_qpat_usage`);

    const newImg = await uploadOk(request, 'photo2.jpg'); // đổi/tải ảnh mới
    const html = `<p>Thử</p>${imgHtml(newImg)}`;
    const links: Record<string, string> = {};
    for (const lang of ['vi', 'en', 'zh']) {
      const p = await createPage(request, `qpwc-lang-${lang}-${Date.now()}`, html, lang);
      links[lang] = p.link;
    }
    // Trong lúc đó tắt/bật ảnh không được sinh thêm công việc dịch
    const jobsMid = sql(`SELECT COUNT(*) FROM wp_qp_llm_translate_jobs`);

    for (const lang of ['vi', 'en', 'zh']) {
      await page.goto(links[lang]);
      await settle(page);
      const src = await page.locator('picture.qpwc img').first().evaluate((i: HTMLImageElement) => i.currentSrc);
      expect(src, `ảnh trang ${lang}`).toMatch(/\.webp(\?|$)/);
      expect(src).toContain(path.basename(newImg.source_url).replace(/\.jpg$/, ''));
    }
    metric('WP-18_pages', links);
    // Chỉ riêng việc tải ảnh lên + đổi ảnh KHÔNG được tăng ký tự dịch (so sánh số ký tự Google NMT).
    const charsAfter = sql(`SELECT COALESCE(SUM(chars),0) FROM wp_qpat_usage`);
    expect(charsAfter, 'số ký tự dịch Google không đổi').toBe(charsBefore);
    metric('WP-18_llm_jobs', { before: jobsBefore, afterCreatePages: jobsMid });
    // Riêng thao tác tải ảnh: tải thêm 1 ảnh và đảm bảo bảng job không tăng.
    const j1 = sql(`SELECT COUNT(*) FROM wp_qp_llm_translate_jobs`);
    await uploadOk(request, 'photo3.jpg');
    expect(sql(`SELECT COUNT(*) FROM wp_qp_llm_translate_jobs`), 'tải ảnh không tạo job dịch').toBe(j1);
  });

  test('WP-06 đổi ảnh trong trang thì hiển thị ảnh mới ngay (không kẹt cache)', async ({ page, request }) => {
    const other = await uploadOk(request, 'flat.png');
    await updatePage(request, pageA.id, `<p>Đổi</p>${imgHtml(other)}`);
    await page.goto(pageA.link + '?x=' + Date.now());
    const src = await page.locator('picture.qpwc img').first().evaluate((i: HTMLImageElement) => i.currentSrc);
    expect(src).toContain(path.basename(other.source_url).replace(/\.png$/, ''));
    expect(src).toMatch(/\.webp(\?|$)/);
  });

  test('WP-15 tắt chức năng WebP thì website quay về ảnh gốc, không mất ảnh', async ({ page, request }) => {
    const p = await createPage(request, `qpwc-off-${Date.now()}`, imgHtml(photo));
    // Tắt qua giao diện quản trị
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-enabled').uncheck();
    await page.locator('#submit').click();
    await page.waitForLoadState('load');
    await expect(page.locator('#qpwc-enabled')).not.toBeChecked();

    await page.goto(p.link + '?x=' + Date.now());
    expect(await page.locator('picture.qpwc').count(), 'không còn picture').toBe(0);
    const info = await page.locator('.entry-content img, article img, main img').first().evaluate((i: HTMLImageElement) => ({ src: i.currentSrc, ok: i.complete && i.naturalWidth > 0 }));
    expect(info.src).toMatch(/\.(jpe?g|png)(\?|$)/);
    expect(info.ok, 'ảnh gốc hiển thị bình thường').toBeTruthy();
    expect(fs.existsSync(diskPath(photo.source_url))).toBeTruthy();

    // Bật lại
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-enabled').check();
    await page.locator('#submit').click();
    await page.waitForLoadState('load');
    await page.goto(p.link + '?x=' + Date.now());
    expect(await page.locator('picture.qpwc').count()).toBeGreaterThan(0);
  });
});

test.describe('chẩn đoán hosting (WP-01…06)', () => {
  test('WP-01/02/05 trang Chẩn đoán báo đủ và đạt trên máy này', async ({ page }) => {
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    const rows = page.locator('#qpwc-diag tbody tr');
    expect(await rows.count()).toBeGreaterThan(10);
    const text = await page.locator('#qpwc-diag').innerText();
    expect(text).toMatch(/GD hỗ trợ WebP\s+Có/);
    expect(text).toMatch(/Ghi được file \.webp vào uploads\s+Có/);
    expect(text).toMatch(/Chuyển thử 1 ảnh PNG\s+OK/);
    expect(text).toMatch(/Content-Type của file \.webp.*image\/webp/);
    const note = await page.locator('#qpwc-diag-text').inputValue();
    expect(note).toContain('[WP-01]');
    // Không để lại file thử
    expect(fs.existsSync(path.join(process.env.QP_UPLOADS || diskPath('http://x/wp-content/uploads/'), 'qpwc-probe.webp'))).toBeFalsy();
  });

  test('WP-03 đo được dung lượng uploads', async ({ page }) => {
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-measure').click();
    await expect(page.locator('#qpwc-size-row')).toContainText(/(B|KB|MB|GB)/);
  });
});

async function runBulkUntilDone(page: Page, timeoutMs = 280_000) {
  await expect(page.locator('#qpwc-state')).toHaveAttribute('data-status', /done|idle/, { timeout: timeoutMs });
}

test.describe('chuyển đổi hàng loạt (WP-07/13/14)', () => {
  let bulkMedia: any[] = [];
  const files: string[] = [];

  test('chuẩn bị: tải 6 ảnh khi plugin đang tắt (giả lập ảnh cũ chưa có WebP)', async ({ request }) => {
    if (!pageA) {
      setPluginEnabled(true);
      const ph = await uploadOk(request, 'photo.jpg');
      pageA = await createPage(request, `qpwc-bulk-${Date.now()}`, imgHtml(ph));
    }
    setPluginEnabled(false);
    for (const f of ['photo.jpg', 'photo2.jpg', 'photo3.jpg', 'alpha.png', 'flat.png', 'anim.gif']) bulkMedia.push(await uploadOk(request, f));
    setPluginEnabled(true);
    for (const m of bulkMedia) {
      expect(fs.existsSync(diskPath(m.source_url) + '.webp'), 'ảnh cũ chưa có WebP').toBeFalsy();
      files.push(diskPath(m.source_url) + '.webp');
    }
  });

  test('WP-07 chưa xác nhận sao lưu thì không cho chạy hàng loạt', async ({ page }) => {
    sql(`UPDATE wp_options SET option_value = REPLACE(option_value, CONCAT('s:9:"backup_at";i:', SUBSTRING_INDEX(SUBSTRING_INDEX(option_value,'s:9:"backup_at";i:',-1),';',1), ';'), 's:9:"backup_at";i:0;') WHERE option_name='qpwc_settings'`);
    sql(`DELETE FROM wp_options WHERE option_name='qpwc_bulk'`); // bắt đầu từ trạng thái sạch
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await expect(page.locator('#qpwc-backup')).not.toBeChecked();
    await page.locator('#qpwc-start').click();
    await expect(page.locator('#qpwc-msg')).toContainText('sao lưu');
    expect(sql(`SELECT COUNT(*) FROM wp_options WHERE option_name='qpwc_bulk' AND option_value LIKE '%running%'`)).toBe('0');
  });

  test('WP-13 chạy hàng loạt: Tổng = thành công + lỗi + bỏ qua + chưa xử lý; có danh sách lỗi', async ({ page }) => {
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-backup').check();
    await expect(page.locator('#qpwc-backup-at')).toContainText('xác nhận');
    await page.locator('#qpwc-start').click();
    // trang tự tải lại khi xong
    await page.waitForFunction(() => document.querySelector('#qpwc-state')?.getAttribute('data-status') === 'done', null, { timeout: 280_000 }).catch(() => {});
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await runBulkUntilDone(page);
    const n = async (id: string) => parseInt((await page.locator(id).innerText()).replace(/\D/g, ''), 10);
    const total = await n('#r-total'), done = await n('#r-done'), skipped = await n('#r-skipped'), failed = await n('#r-failed'), pending = await n('#r-pending');
    metric('WP-13_report', { total, done, skipped, failed, pending });
    expect(done + skipped + failed + pending, 'tổng phải khớp').toBe(total);
    expect(pending).toBe(0);
    expect(done).toBeGreaterThanOrEqual(5); // 5 ảnh JPG/PNG
    // GIF → bỏ qua; ảnh cũ trên dữ liệu dev thiếu file → nằm trong danh sách lỗi
    expect(skipped).toBeGreaterThanOrEqual(1);
    for (const f of files.slice(0, 5)) expect(fs.existsSync(f), f).toBeTruthy();
    expect(fs.existsSync(files[5]), 'GIF không có WebP').toBeFalsy();
    if (failed > 0) {
      const listed = await page.locator('#qpwc-failed tbody tr').count();
      expect(listed).toBeGreaterThan(0);
      expect(await page.locator('#qpwc-failed').innerText()).toMatch(/missing_file|memory|not_image|exception/);
    }
  });

  test('WP-14 chạy lại không xử lý lại ảnh đã xong (file WebP giữ nguyên)', async ({ page }) => {
    const before = files.slice(0, 5).map((f) => fs.statSync(f).mtimeMs);
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-start').click();
    await expect(page.locator('#qpwc-status')).toHaveText('running'); // tránh đọc nhầm trạng thái "done" cũ
    await page.waitForFunction(() => document.querySelector('#qpwc-state')?.getAttribute('data-status') === 'done', null, { timeout: 280_000 });
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await runBulkUntilDone(page);
    const after = files.slice(0, 5).map((f) => fs.statSync(f).mtimeMs);
    expect(after).toEqual(before);
  });

  test('WP-14 website vẫn truy cập được khi chạy; dừng rồi chạy tiếp được', async ({ page, request, browser }) => {
    // Làm thêm một số ảnh "mới chưa có WebP" để lần chạy này có việc để làm
    setPluginEnabled(false);
    const extra: any[] = [];
    for (let i = 0; i < 4; i++) extra.push(await uploadOk(request, i % 2 ? 'photo.jpg' : 'photo2.jpg'));
    setPluginEnabled(true);

    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await page.locator('#qpwc-reset').click();
    await page.locator('#qpwc-start').click();
    await page.locator('#qpwc-pause').click();
    await expect(page.locator('#qpwc-status')).toHaveText('paused');
    const processedPaused = parseInt(await page.locator('#qpwc-processed').innerText(), 10);

    // Website phía khách vẫn mở được trong lúc đang dừng giữa chừng
    const visitor = await browser.newContext({ storageState: { cookies: [], origins: [] } });
    const vp = await visitor.newPage();
    const t0 = Date.now();
    const r = await vp.goto(pageA.link);
    expect(r!.status()).toBe(200);
    expect(Date.now() - t0, 'trang mở dưới 15 giây').toBeLessThan(15000);

    await page.locator('#qpwc-resume').click();
    // truy cập tiếp trong lúc đang chạy
    const r2 = await vp.goto(pageA.link + '?y=' + Date.now());
    expect(r2!.status()).toBe(200);
    await visitor.close();

    await page.waitForFunction(() => document.querySelector('#qpwc-state')?.getAttribute('data-status') === 'done', null, { timeout: 280_000 }).catch(() => {});
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    await runBulkUntilDone(page);
    const processedEnd = parseInt(await page.locator('#qpwc-processed').innerText(), 10);
    expect(processedEnd, 'chạy tiếp, không quay lại từ đầu').toBeGreaterThanOrEqual(processedPaused);
    for (const m of extra) expect(fs.existsSync(diskPath(m.source_url) + '.webp'), 'ảnh mới đã có WebP sau khi chạy tiếp').toBeTruthy();
  });

  test('WP-15 nút "Xóa toàn bộ file WebP" đưa website về ảnh gốc', async ({ page }) => {
    await page.goto('wp-admin/upload.php?page=qp-webp-convert');
    page.once('dialog', (d) => d.accept());
    await page.locator('#qpwc-cleanup').click();
    await expect(page.locator('.notice-success')).toContainText('Đã xóa');
    for (const f of files.slice(0, 5)) expect(fs.existsSync(f)).toBeFalsy();
    // ảnh gốc còn
    for (const m of bulkMedia) expect(fs.existsSync(diskPath(m.source_url))).toBeTruthy();
    const pg = await page.goto(pageA.link + '?z=' + Date.now());
    expect(pg!.status()).toBe(200);
    expect(await page.locator('picture.qpwc').count(), 'không có WebP thì không bọc picture').toBe(0);
  });
});
