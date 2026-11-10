# Quy trình: dựng môi trường local sau khi clone

Dành cho AI agent chạy trên máy Windows của người mới. Làm tuần tự, báo người dùng sau mỗi bước.
Bước nào cần thứ người dùng phải cung cấp (file database, uploads, mật khẩu) thì **hỏi**, không tự bịa.

## 0. Kiểm tra điều kiện

- Repo phải nằm ở `C:\xampp\htdocs\qpholdings` (nếu khác thì mọi đường dẫn `/qpholdings` bên dưới đổi theo).
- Có XAMPP với PHP ≥ 8.2 và MySQL/MariaDB: `C:\xampp\php\php.exe -v`, `C:\xampp\mysql\bin\mysql.exe --version`.
  Apache và MySQL phải đang chạy (XAMPP Control Panel).
- Có WP-CLI thì dùng (`wp --info`); không có thì làm thủ công như mô tả.

## 1. Lấy dữ liệu không nằm trong git — hỏi người dùng

Cần **một trong ba**:

- **⭐ Khuyên dùng — Backup Plesk** (`backup_qpholdings.vn_<số>.tar`, ~6 GB, người quản lý tải từ
  Plesk → Backup Manager). Hỏi người dùng đường dẫn file, rồi chạy **một lệnh**:

  ```powershell
  powershell -ExecutionPolicy Bypass -File tools\restore-plesk-backup.ps1 -Backup "C:\Users\<tên>\Downloads\backup_qpholdings.vn_<số>.tar"
  ```

  Script tự làm bước 2–7: giải nén lõi WP + plugin + uploads (không đè code trong git, không lấy
  `wp-config.php` của production), import database (tự sửa lỗi dòng "sandbox mode" mà MariaDB 10.4 của
  XAMPP không đọc được), tạo `wp-config.php` + `.htaccess`, đổi URL `https://qpholdings.vn` →
  `http://localhost/qpholdings` bằng WP-CLI (tự tải nếu chưa có), tắt Wordfence + LiteSpeed Cache ở local.
  - Cần ~7 GB ổ trống, Apache + MySQL đang chạy.
  - Database `qpholdings` đã tồn tại → script dừng; thêm `-Force` để xoá và import lại (hỏi người dùng trước).
  - Tham số khác: `-DbName`, `-LocalUrl`, `-SkipFiles` (chỉ làm lại DB), `-SkipDb` (chỉ giải nén file).
  - Xong thì chuyển thẳng tới **bước 8**.

  Nếu script lỗi giữa chừng: đọc thông báo, sửa nguyên nhân, chạy lại (dùng `-SkipFiles` nếu file đã giải nén xong).
  Muốn làm tay: backup là file tar chứa `backup_user-data_*.tzst` (thư mục `httpdocs/` = gốc website) và
  `databases/<tên-db>/backup_sqldump_*.tzst` (file SQL nén zstd); `tar` có sẵn trong Windows 10+ giải nén được cả hai.

- **A. Gói Duplicator Pro** (`*_archive.zip` + `installer.php`) từ người quản lý site → bỏ hai file vào thư mục
  rỗng rồi mở `http://localhost/<thư-mục>/installer.php`, làm theo trình cài. Gói này đã có đủ lõi WP, plugin,
  uploads, database. Xong thì clone repo đè lên để có code mới nhất và **bỏ qua bước 2–6**.
- **B. File rời**: dump database (`.sql` hoặc `.sql.gz`) + file nén `wp-content/uploads`.
  Nếu người dùng chưa có, hướng dẫn họ xin người quản lý: export từ phpMyAdmin trong Plesk
  (Websites & Domains → Databases) và nén thư mục `httpdocs/wp-content/uploads`.

## 2. Tải lõi WordPress (không đè `wp-content`)

- WP-CLI: `wp core download --version=7.1.4 --skip-content --locale=vi`
- Thủ công: tải `https://wordpress.org/wordpress-7.1.4.zip`, giải nén ra thư mục tạm, copy mọi thứ **trừ**
  `wp-content/` vào thư mục repo.

## 3. Database

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE qpholdings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --binary-mode --default-character-set=utf8mb4 qpholdings < duong\dan\dump.sql"
```

- Dùng `cmd /c` cho lệnh `<` (PowerShell 5.1 không hỗ trợ `<` và làm hỏng dữ liệu nhị phân khi pipe).
- Lỗi `ERROR at line 1: Unknown command '\-'` → dump tạo bởi MariaDB mới có dòng đầu `/*M!999999\- enable the sandbox mode */`.
  Xoá **chỉ dòng đầu** đó (cắt theo byte, đừng mở bằng trình soạn thảo rồi lưu lại — có thể hỏng dữ liệu binary của Wordfence).

Xác định **tiền tố bảng** từ dump (tìm `CREATE TABLE \`xxx_options\`` → tiền tố là `xxx_`) và URL gốc cũ:
`SELECT option_value FROM <prefix>options WHERE option_name IN ('siteurl','home');`

## 4. `wp-config.php`

Copy `wp-config-sample.php` → `wp-config.php`, điền:

- `DB_NAME` = `qpholdings`, `DB_USER` = `root`, `DB_PASSWORD` = `''` (mặc định XAMPP), `DB_HOST` = `localhost`.
- `$table_prefix` = tiền tố tìm ở bước 3.
- Khoá bảo mật: lấy bộ mới tại `https://api.wordpress.org/secret-key/1.1/salt/`.
- Thêm trước dòng `/* That's all, stop editing! */`:

```php
define( 'WP_HOME', 'http://localhost/qpholdings' );
define( 'WP_SITEURL', 'http://localhost/qpholdings' );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

`wp-config.php` đã bị `.gitignore` loại trừ — không được commit.

## 5. Uploads và plugin bên thứ ba

- Giải nén uploads vào `wp-content/uploads/`.
- Cài plugin (bản free trên wordpress.org):

```powershell
wp plugin install advanced-custom-fields classic-editor contact-form-7 flamingo seo-by-rank-math safe-svg real-media-library-lite fluent-smtp litespeed-cache go-live-update-urls
```

  Không có WP-CLI → vào wp-admin → Plugins → Add New và cài lần lượt.
- **Không cần ở local, nên tắt**: Wordfence (firewall ghi đường dẫn tuyệt đối vào `.user.ini`, dễ làm lỗi 500
  trên máy khác), LiteSpeed Cache (XAMPP không phải LiteSpeed), WP Security Audit Log. Duplicator Pro cần license, bỏ qua.
- Nếu gặp lỗi 500 do `.user.ini` / `wordfence-waf.php`: xoá dòng `auto_prepend_file` trong `.user.ini` ở thư mục gốc.

## 6. Đổi URL trong nội dung

Nếu URL cũ (bước 3) khác `http://localhost/qpholdings`:

```powershell
wp search-replace "https://qpholdings.vn" "http://localhost/qpholdings" --all-tables --precise
```

Không có WP-CLI → kích hoạt plugin **Go Live Update URLs** (Tools → Go Live) và thay URL.
Không dùng lệnh `REPLACE()` trong SQL vì sẽ hỏng dữ liệu serialize.

## 7. `.htaccess` và permalink

Tạo `.htaccess` ở thư mục gốc nếu chưa có:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /qpholdings/
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /qpholdings/index.php [L]
</IfModule>
# END WordPress
```

Rồi wp-admin → Settings → Permalinks → **Save Changes** (hoặc `wp rewrite flush`).

## 8. Kiểm tra

- Đảm bảo theme **QP Holdings** đang active (`wp theme activate qp-holdings`) và plugin **QP LLM Translate** đang active.
- Mở và xác nhận không lỗi: trang chủ, `/tin-tuc/` + một bài tin, một trang dự án, `/nha-dau-tu/`, `/wp-admin/`.
- Mở thử màn hình "Tin tức → Thêm bài viết mới": phải thấy Classic Editor với thanh công cụ mở rộng và hộp ACF.
- Xem `wp-content/debug.log` — không được có Fatal error.
- Tài khoản admin: dùng tài khoản trong database đã import (hỏi người dùng). Quên mật khẩu:
  `wp user update <login> --user_pass=<mật-khẩu-mới>`.

Báo cáo cho người dùng: URL local, các bước đã làm, những gì còn thiếu.
