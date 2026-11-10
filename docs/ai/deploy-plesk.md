# Quy trình: đưa bản chỉnh sửa lên hosting Plesk

Production: **https://qpholdings.vn** trên hosting **Plesk** (CloudLinux). Thư mục gốc website trên server là
**`httpdocs`** (`/var/www/vhosts/qpholdings.vn/httpdocs`) — không phải `public_html` như cPanel.
Database MariaDB 10.6, tiền tố bảng `wp_`.

Agent **không** đăng nhập Plesk: agent chuẩn bị gói zip và hướng dẫn người dùng thao tác từng bước.

Chỉ deploy **code** (theme, plugin tự viết, mu-plugin). **Không bao giờ** đưa database local lên
production — sẽ ghi đè bài viết thật mà khách đã đăng.

## 1. Chuẩn bị trên máy local

1. Kiểm tra cú pháp mọi file PHP đã sửa: `C:\xampp\php\php.exe -l <file>`.
2. Mở thử các trang liên quan trên `http://localhost/qpholdings`, xem `wp-content/debug.log` không có lỗi mới.
3. Sửa CSS/JS có cặp `.min` → đã cập nhật cả bản `.min` chưa.
4. Commit hết thay đổi (script từ chối chạy khi còn file chưa commit).

## 2. Tạo gói deploy

```powershell
powershell -ExecutionPolicy Bypass -File tools\make-deploy-zip.ps1
```

- Mặc định: lấy các file thay đổi **kể từ tag `deploy-*` gần nhất** đến `HEAD`.
- Lần deploy đầu tiên (chưa có tag): thêm `-Full` để đóng gói toàn bộ code.
- Muốn chọn mốc khác: `-Since <commit-hoặc-tag>`.
- Script **dừng** nếu `qp-llm-translate/theme-updates/` lệch với theme (xem "Bẫy" trong `AGENTS.md`).
  Khi đó chạy lại với `-SyncThemeUpdates`, commit thay đổi, rồi chạy lại lần nữa.

Kết quả trong `dist\deploy-<thời-gian>\`:
- `qp-deploy-<thời-gian>.zip` — đường dẫn bên trong bắt đầu bằng `wp-content/...`
- `FILES.txt` — danh sách file sẽ ghi đè
- `DELETED.txt` — file đã xoá trong git, cần xoá tay trên hosting (có thể rỗng)

Đọc `FILES.txt` cho người dùng xem trước. Nếu có thay đổi file plugin chính `qp-llm-translate.php` hoặc
`QP_LLM_TRANSLATE_VERSION`, báo rõ cho người dùng.

## 3. Hướng dẫn người dùng thao tác trên Plesk

Gửi người dùng đúng các bước sau:

1. **Sao lưu**: Plesk → **Websites & Domains** → qpholdings.vn → **File Manager** → vào `httpdocs/wp-content/themes/`.
   Tick ô `qp-holdings` → **Add to Archive** → đặt tên `qp-holdings-backup-<ngày>.zip`. Làm tương tự với
   `httpdocs/wp-content/plugins/qp-llm-translate`. (Hoặc Plesk → **Backup Manager** → **Back Up** toàn bộ.)
2. **Upload**: vào thư mục **`httpdocs`** (cùng cấp `wp-config.php`, **không** phải trong `wp-content`),
   bấm **+** / **Upload File** → chọn `qp-deploy-....zip`.
3. **Giải nén**: tick file zip → **Extract Files** → đích là `httpdocs`, **tick "Replace existing files"** → OK.
4. **Xoá file thừa**: nếu `DELETED.txt` có nội dung, xoá từng file trong danh sách.
5. **Xoá file zip** vừa upload và các file backup `.zip` vừa tạo (tải về máy trước nếu muốn giữ) khỏi `httpdocs`
   — không để file backup nằm trong thư mục web công khai.
6. **Xoá cache**: đăng nhập wp-admin → thanh trên cùng **LiteSpeed Cache → Purge All**.
   Nếu dùng Cloudflare/CDN thì purge thêm ở đó.
7. **Kiểm tra**: mở trang chủ, trang tin tức, các trang vừa sửa ở cửa sổ ẩn danh (Ctrl+Shift+N).
   Lỗi trắng trang / 500 → Plesk → **Logs** của domain (hoặc `wp-content/debug.log`) và khôi phục từ backup bước 1
   (Extract file backup đè lại).

## 4. Sau khi người dùng xác nhận site chạy tốt

Đánh dấu mốc deploy để lần sau chỉ đóng gói phần thay đổi:

```powershell
git tag deploy-<yyyyMMdd-HHmmss>
git push origin --tags
```

(Chỉ push khi người dùng đồng ý.)

## Ghi chú

- **ACF fields** khai báo bằng code (`inc/dynamic-acf.php`) → tự có hiệu lực sau khi upload, không cần migrate database.
- **Thay đổi cần dữ liệu** (tạo trang mới, đổi menu, cài đặt plugin...) → làm tay trong wp-admin production,
  ghi lại các bước cho người dùng.
- **Tăng `QP_LLM_TRANSLATE_VERSION`** sẽ kích hoạt cơ chế copy `theme-updates/` đè lên theme ở lần tải trang
  kế tiếp. Chỉ tăng khi `theme-updates/` đã giống hệt theme (script đã kiểm tra).
- **Ảnh WebP trong `uploads/`** (ảnh khách đăng) không nằm trong git: dùng tính năng Image Optimization / WebP
  của LiteSpeed Cache trên production thay vì upload ảnh từ local.
- Tài khoản hosting có shell SSH (chroot). Nếu người dùng bật được SSH trong Plesk, có thể dùng SFTP/`scp`
  upload zip rồi `unzip -o` trong `httpdocs` thay cho File Manager — vẫn theo đúng các bước sao lưu/kiểm tra ở trên.
