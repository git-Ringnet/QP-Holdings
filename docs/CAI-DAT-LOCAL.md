# Hướng dẫn chạy website QP Holdings trên máy local (Windows)

Repo đã có sẵn **lõi WordPress, plugin, theme, cấu hình dev và database**. Clone về, tạo database,
import 1 file là chạy — khoảng 10 phút.

---

## Bước 0. Chuẩn bị

- [XAMPP](https://www.apachefriends.org/) (PHP 8.2 trở lên), cài vào `C:\xampp`.
- [Git for Windows](https://git-scm.com/download/win).
- Quyền truy cập repo `git-Ringnet/QP-Holdings` (nhờ trưởng nhóm thêm tài khoản GitHub).

Mở **XAMPP Control Panel** → **Start** **Apache** và **MySQL** (chuyển màu xanh).

## Bước 1. Clone code

```powershell
cd C:\xampp\htdocs
git clone https://github.com/git-Ringnet/QP-Holdings.git qpholdings
```

Thư mục **phải tên là `qpholdings`** (website chạy ở `http://localhost/qpholdings`).

## Bước 2. Tạo database và import

1. Mở <http://localhost/phpmyadmin>.
2. Tab **Databases** → tên **`qpholdings`**, collation **`utf8mb4_unicode_ci`** → **Create**.
3. Chọn database `qpholdings` → tab **Import** → chọn file
   `C:\xampp\htdocs\qpholdings\database\qpholdings.sql.gz` → **Import** (để nguyên các tuỳ chọn).

Hoặc bằng lệnh (PowerShell):

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE qpholdings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
cmd /c """C:\Program Files\Git\usr\bin\gzip.exe"" -dc C:\xampp\htdocs\qpholdings\database\qpholdings.sql.gz | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 qpholdings"
```

## Bước 3. Mở website

- Website: <http://localhost/qpholdings>
- Quản trị: <http://localhost/qpholdings/wp-admin> — tài khoản **`admin`**, mật khẩu **`admin123`**
  (tài khoản `marketing`, `phongnguyen` cũng mật khẩu `admin123`).

Xong. Không cần sửa `wp-config.php` nếu XAMPP dùng mặc định (user `root`, không mật khẩu).

---

## Máy bạn cấu hình khác?

Tạo file **`C:\xampp\htdocs\qpholdings\wp-config-local.php`** (file này không đưa lên Git), chỉ ghi phần khác:

```php
<?php
define( 'DB_PASSWORD', 'mat-khau-mysql-cua-ban' );   // MySQL có mật khẩu
// define( 'DB_NAME', 'ten_database_khac' );         // đặt database tên khác
```

## Bản dev này khác website thật thế nào

| | |
|---|---|
| **Ảnh** | Thư mục ảnh (~5,5 GB) không nằm trong Git. Ảnh nào không có trên máy sẽ **tự lấy từ qpholdings.vn** — cần có internet. Ảnh bạn tải lên ở local vẫn lưu và hiển thị bình thường. |
| **Database** | Bản sao website thật đã **làm sạch**: không có tin nhắn liên hệ, hồ sơ ứng tuyển, đăng ký của khách; không có API key; email người dùng là email giả; mọi mật khẩu là `admin123`. |
| **Email** | Email nhận form đặt là `dev@example.test` — máy dev không gửi mail tới khách thật. |
| **Plugin** | Wordfence, LiteSpeed Cache, WP Security Audit Log tắt sẵn (không cần ở local). |

---

## Gặp lỗi thường gặp

| Hiện tượng | Cách xử lý |
|---|---|
| `Error establishing a database connection` | MySQL chưa bật; database chưa tên `qpholdings`; hoặc MySQL có mật khẩu → tạo `wp-config-local.php` (xem trên) |
| Bị chuyển sang `qpholdings.vn` hoặc trang trắng | Thư mục clone không tên `qpholdings` → clone lại đúng tên |
| Trang con báo 404 | wp-admin → **Cài đặt → Đường dẫn tĩnh** (Settings → Permalinks) → **Lưu thay đổi** |
| Ảnh không hiện | Kiểm tra internet (ảnh lấy từ qpholdings.vn) |
| Lỗi khác | Xem `wp-content\debug.log` |

---

## Làm việc hằng ngày

- Đầu ngày: `git pull`.
- Sửa code trong `wp-content\themes\qp-holdings\` và `wp-content\plugins\qp-auto-translate\`.
- Xong việc: `git add -A` → `git commit -m "mô tả"` → `git push`.
- Database trong Git có cập nhật (`database\qpholdings.sql.gz` thay đổi sau `git pull`)? Xoá database `qpholdings`
  rồi làm lại Bước 2 nếu muốn dữ liệu mới.
- **Không commit**: `wp-config-local.php`, ảnh trong `uploads`, file `.sql` tự export (đã chặn sẵn trong `.gitignore`).
- Đưa bản sửa lên website thật: [docs/ai/deploy-plesk.md](ai/deploy-plesk.md) — chỉ đóng gói code theme/plugin tự viết.
  **Không bao giờ** upload `wp-config.php`, `.htaccess` của repo, database hay ảnh từ máy local lên hosting.
