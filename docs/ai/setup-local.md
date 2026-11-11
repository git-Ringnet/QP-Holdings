# Quy trình: dựng môi trường local sau khi clone

Dành cho AI agent trên máy Windows của người dùng. Hướng dẫn cho người: `docs/CAI-DAT-LOCAL.md`.
Repo đã có lõi WordPress, plugin, `wp-config.php` + `.htaccess` bản dev và `database/qpholdings.sql.gz`.

## Các bước

1. **Kiểm tra**
   - Repo nằm ở `C:\xampp\htdocs\qpholdings` (khác tên thư mục → URL trong database và `WP_HOME` không khớp;
     đề nghị người dùng clone lại đúng tên).
   - XAMPP: `C:\xampp\php\php.exe -v` (≥ 8.2), Apache + MySQL đang chạy
     (`curl -s -o NUL -w "%{http_code}" http://localhost/` trả về mã HTTP).
2. **Database** (hỏi người dùng trước nếu database `qpholdings` đã tồn tại — không tự xoá):
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE qpholdings CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
   cmd /c """C:\Program Files\Git\usr\bin\gzip.exe"" -dc C:\xampp\htdocs\qpholdings\database\qpholdings.sql.gz | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 qpholdings"
   ```
   MySQL có mật khẩu / tên database khác → tạo `wp-config-local.php` chỉ chứa các `define()` khác mặc định
   (`DB_PASSWORD`, `DB_NAME`...). Không sửa `wp-config.php` của repo.
3. **Kiểm tra**
   - `http://localhost/qpholdings/` trả 200; `http://localhost/qpholdings/tintuc/` trả 200.
   - `http://localhost/qpholdings/wp-login.php` trả 200; đăng nhập `admin` / `admin123`.
   - `wp-content/debug.log` không có Fatal error.
4. Báo người dùng: URL, tài khoản, những gì đã làm.

## Lưu ý

- Ảnh trong `uploads` không có trên máy là bình thường: `.htaccess` dev chuyển hướng sang `https://qpholdings.vn`.
- Không commit `wp-config-local.php`, dump `.sql` chưa làm sạch, ảnh uploads.
- Dữ liệu mới hơn: cần backup Plesk mới, làm theo `database/README.md`
  (`tools/restore-plesk-backup.ps1` là cách cũ để dựng nguyên site từ backup; vẫn dùng được nhưng không cần cho dev).
