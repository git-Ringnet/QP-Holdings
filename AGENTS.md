# QP Holdings — hướng dẫn cho AI agent (Claude, Gemini, Codex...)

File này là nguồn hướng dẫn chung. `CLAUDE.md` và `GEMINI.md` chỉ import file này.
Trả lời người dùng bằng **tiếng Việt**.

## Dự án là gì

Website WordPress của QP Holdings (qpholdings.vn). Repo chứa đủ để clone về là chạy trên máy dev
(lõi WordPress, plugin, cấu hình dev, database đã làm sạch). **Code tự viết** — chỉ những thứ này được sửa và deploy:

| Đường dẫn | Nội dung |
|---|---|
| `wp-content/themes/qp-holdings/` | Theme classic tự viết (toàn bộ giao diện, CPT, ACF fields, SEO, editor TinyMCE mở rộng) |
| `wp-content/plugins/qp-auto-translate/` | **Plugin dịch theo hợp đồng (đang phát triển)**: Google Cloud Translation NMT, đếm ký tự theo tháng, giới hạn 500.000 ký tự + cảnh báo 80%. Cài đặt → Dịch tự động. API key có thể khai báo `QPAT_GOOGLE_API_KEY` trong wp-config.php |
| `wp-content/plugins/qp-llm-translate/` | Plugin dịch cũ (Gemini/DeepSeek/OpenAI) đang chạy trên production — sẽ được thay bằng `qp-auto-translate`. Không bật cả hai cùng lúc khi plugin mới đã có tự dịch khi đăng bài |
| `wp-content/mu-plugins/qpholdings-admin-branding*` | Must-use plugin: branding trang admin |
| `bin/` | Script import dữ liệu một lần (tài liệu nhà đầu tư) |
| `tools/` | Script hỗ trợ deploy |
| `docs/ai/` | Quy trình chi tiết cho agent |

**Có trong git nhưng KHÔNG sửa, KHÔNG deploy:**
- `wp-admin/`, `wp-includes/`, file `.php` gốc, plugin bên thứ ba trong `wp-content/plugins/` — giống hệt production.
- `wp-config.php`, `.htaccess` — **bản dành cho máy dev** (DB `qpholdings`/root, ảnh thiếu tự lấy từ qpholdings.vn).
  Cấu hình riêng từng máy để trong `wp-config-local.php` (không commit). Production có wp-config/.htaccess riêng.
- `database/qpholdings.sql.gz` — database dev đã làm sạch bằng `tools/sanitize-dev-db.php` (xem `database/README.md`).
  **Không bao giờ** commit dump production chưa làm sạch.

**Không có trong git:** `wp-content/uploads/` (~5.5 GB), cache, log, `wp-config-local.php`.

## Môi trường

- WordPress 7.1.x, PHP 8.4 (local dùng XAMPP trên Windows, `C:\xampp\php\php.exe`).
- Local URL: `http://localhost/qpholdings` (định nghĩa bằng `WP_HOME` / `WP_SITEURL` trong `wp-config.php`).
- Production: **https://qpholdings.vn** trên hosting **Plesk** (CloudLinux). Gốc website trên server là `httpdocs/`
  (không phải `public_html`). MariaDB 10.6, tiền tố bảng `wp_`. Deploy bằng file zip upload qua Plesk File Manager.
- Plugin đang **bật trên production**: ACF, Classic Editor, Polylang, Rank Math SEO, Safe SVG, Real Media Library Lite,
  FluentSMTP, LiteSpeed Cache, Wordfence, WP Security Audit Log, QP LLM Translate.
  Có cài nhưng tắt: Contact Form 7, Flamingo, Duplicator Pro (trả phí), Go Live Update URLs.
- Dựng local: clone → tạo DB `qpholdings` → import `database/qpholdings.sql.gz` (xem `docs/CAI-DAT-LOCAL.md`).
  Tài khoản dev: `admin` / `admin123`. Cần dữ liệu mới hơn: backup Plesk mới → làm lại theo `database/README.md`.

## Quy trình

- Dựng môi trường local cho người mới clone: làm theo **`docs/ai/setup-local.md`**.
- Đưa bản chỉnh sửa lên hosting: làm theo **`docs/ai/deploy-plesk.md`**.

## ⚠️ Bẫy quan trọng: plugin tự ghi đè theme

`qp-llm-translate/includes/class-qp-llm-theme-sync.php` chứa danh sách file theme. Mỗi khi
`QP_LLM_TRANSLATE_VERSION` thay đổi, plugin **copy các file trong `qp-llm-translate/theme-updates/`
đè lên theme đang chạy** trên hosting.

Quy tắc bắt buộc:
1. Sửa file theme nào có trong `theme-updates/` → phải copy bản mới sang `theme-updates/` (giữ hai bản giống hệt).
2. Trước mỗi lần deploy chạy `tools/make-deploy-zip.ps1`; script sẽ **dừng** nếu hai bản lệch nhau.
   Dùng `-SyncThemeUpdates` để copy bản theme sang `theme-updates/` (theme là nguồn đúng).
3. Không bao giờ sửa trực tiếp trong `theme-updates/` rồi để nó đè ngược lên theme.

## ⚠️ Dữ liệu production đang thay đổi hằng ngày

Website thật đăng **khoảng 3 bài viết + video mỗi ngày**. Database và `uploads/` ở local chỉ là ảnh chụp tại
thời điểm backup, luôn cũ hơn production.

- **Chỉ deploy code** (theme, plugin tự viết, mu-plugin). Tuyệt đối không import database local, không dùng
  Duplicator/migrate đẩy cả site, không upload `wp-content/uploads/` từ local lên production.
- Việc gì cần thay đổi **dữ liệu** (nội dung bài, cài đặt, menu, bài dịch của QP LLM Translate, ảnh trong
  uploads) → làm trực tiếp trên wp-admin production, hoặc viết code để tự chạy trên production.
- **Ảnh WebP**: ảnh trong theme → đổi ở local rồi deploy. Ảnh trong bài viết (uploads) → bật WebP trên production
  (LiteSpeed Cache → Image Optimization) để cả bài mới hằng ngày cũng được tối ưu.
- Cần dữ liệu mới để test → xin file backup Plesk mới, chạy lại `tools/restore-plesk-backup.ps1 -Force`.

## Quy ước code

- Theme dùng `filemtime()` làm version asset; sửa CSS/JS thì sửa **cả** bản `.css` và `.min.css` (hoặc `.js`/`.min.js`) nếu cả hai tồn tại.
- Không thêm page builder (Elementor...). Nội dung bài viết soạn bằng Classic Editor (TinyMCE mở rộng ở `inc/dynamic-editor.php`), trường phụ bằng ACF (`inc/dynamic-acf.php`).
- Ảnh mới trong theme: ưu tiên **WebP**, giữ tên file cũ nếu template đang tham chiếu, cập nhật đường dẫn trong template khi đổi đuôi. Kiểm tra lại trang trên local sau khi đổi.
- Không hard-code domain; dùng `home_url()`, `get_template_directory_uri()`.
- Kiểm tra cú pháp trước khi kết thúc: `C:\xampp\php\php.exe -l <file>` cho mỗi file PHP đã sửa.
- Không commit `wp-config.php`, file `.sql`, `uploads/`, API key của DeepSeek/OpenAI/Gemini.
