# database/

`qpholdings.sql.gz` — database cho máy dev: bản sao website thật đã **làm sạch** bằng `tools/sanitize-dev-db.php`.

- Nguồn: backup Plesk ngày 04/11/2026.
- Đã xoá: tin nhắn liên hệ, hồ sơ ứng tuyển, đăng ký tư vấn, đăng ký nhận tin, Flamingo, revision, log/bảo mật
  (Wordfence, WP Security Audit Log, email log), backup Duplicator, API key, license, transient.
- Email người dùng → `<login>@example.test`; mọi mật khẩu → `admin123`; email nhận form → `dev@example.test`.
- URL `https://qpholdings.vn` → `http://localhost/qpholdings`.
- Tắt Wordfence, LiteSpeed Cache, WP Security Audit Log.

Import: xem [docs/CAI-DAT-LOCAL.md](../docs/CAI-DAT-LOCAL.md).

## Tạo lại từ backup mới

```powershell
# 1. Import dump production vào database TẠM (không phải database production)
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS qp_devbuild; CREATE DATABASE qp_devbuild CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
#    (xoá dòng đầu "/*M!999999\- enable the sandbox mode */" của file dump nếu MySQL báo lỗi dòng 1)
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --binary-mode --default-character-set=utf8mb4 qp_devbuild < duong\dan\db.sql"

# 2. Làm sạch
C:\xampp\php\php.exe tools\sanitize-dev-db.php qp_devbuild

# 3. Xuất + nén, rồi xoá database tạm
cmd /c "C:\xampp\mysql\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --single-transaction --hex-blob --skip-dump-date qp_devbuild > database\qpholdings.sql"
& "C:\Program Files\Git\usr\bin\gzip.exe" -9 -f database\qpholdings.sql
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE qp_devbuild"
```

**Không bao giờ** commit file `.sql` chưa làm sạch.
