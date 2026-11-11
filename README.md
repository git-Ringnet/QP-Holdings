# QP-Holdings

Website WordPress của QP Holdings (https://qpholdings.vn).

## 👉 Chạy trên máy local (Windows + XAMPP)

```powershell
cd C:\xampp\htdocs
git clone https://github.com/git-Ringnet/QP-Holdings.git qpholdings
```

1. phpMyAdmin → tạo database **`qpholdings`** (`utf8mb4_unicode_ci`) → **Import** file `database\qpholdings.sql.gz`.
2. Mở <http://localhost/qpholdings> — quản trị: **`admin` / `admin123`**.

Chi tiết, lỗi thường gặp: **[docs/CAI-DAT-LOCAL.md](docs/CAI-DAT-LOCAL.md)**.

## Repo có gì

| Đường dẫn | Nội dung |
|---|---|
| `wp-content/themes/qp-holdings/` | Theme tự viết |
| `wp-content/plugins/qp-auto-translate/` | Plugin dịch tự động (Google Cloud Translation NMT) — đang phát triển |
| `wp-content/plugins/qp-llm-translate/` | Plugin dịch cũ (đang chạy trên website thật) |
| `wp-content/plugins/*` (còn lại), `wp-admin/`, `wp-includes/` | Lõi WordPress và plugin bên thứ ba — **không sửa** |
| `wp-config.php`, `.htaccess` | Cấu hình **dành cho máy dev** — không upload lên hosting |
| `database/qpholdings.sql.gz` | Database dev đã làm sạch (không có dữ liệu cá nhân, mật khẩu `admin123`) |
| `tools/` | Đóng gói deploy, làm sạch database, khôi phục từ backup |

Không có trong repo: ảnh uploads (~5,5 GB — bản dev tự lấy ảnh từ qpholdings.vn), cache, log, `wp-config-local.php`.

## Dùng với AI (Claude Code / Gemini CLI)

Mở thư mục dự án bằng Claude Code hoặc Gemini CLI — AI tự đọc `AGENTS.md`.

| Việc | Lệnh |
|---|---|
| Cài đặt chạy local | `/qp-setup-local` |
| Đưa bản sửa lên hosting Plesk | `/qp-deploy` |

## Quy tắc quan trọng

Website thật đăng bài + video hằng ngày → **chỉ deploy code theme/plugin tự viết** (công cụ `tools/make-deploy-zip.ps1`).
Không bao giờ đưa database, ảnh, `wp-config.php` hay `.htaccess` từ local lên hosting.
Quy trình: [docs/ai/deploy-plesk.md](docs/ai/deploy-plesk.md).
