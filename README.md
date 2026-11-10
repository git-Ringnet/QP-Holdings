# QP-Holdings

Website WordPress của QP Holdings (https://qpholdings.vn).

Repo chứa code tự viết: theme `qp-holdings`, plugin `qp-llm-translate`, mu-plugin branding admin.
Lõi WordPress, plugin bên thứ ba, ảnh uploads, database và `wp-config.php` **không** nằm trong repo.

## Dùng với AI (Claude Code / Gemini CLI)

Mở thư mục dự án bằng Claude Code hoặc Gemini CLI — AI tự đọc `AGENTS.md` (qua `CLAUDE.md` / `GEMINI.md`).

| Việc | Claude Code | Gemini CLI |
|---|---|---|
| Cài đặt chạy local sau khi clone | `/qp-setup-local` | `/qp-setup-local` |
| Đưa bản sửa lên hosting Plesk | `/qp-deploy` | `/qp-deploy` |

Hoặc nói thẳng: "setup dự án này chạy local", "deploy bản sửa lên hosting".

## Làm thủ công

- Cài đặt local: [docs/ai/setup-local.md](docs/ai/setup-local.md)
- Deploy lên Plesk: [docs/ai/deploy-plesk.md](docs/ai/deploy-plesk.md)

Trước khi bắt đầu, cần xin người quản lý **file backup Plesk** (`backup_qpholdings.vn_<số>.tar`), rồi chạy:

```powershell
powershell -ExecutionPolicy Bypass -File tools\restore-plesk-backup.ps1 -Backup "<đường dẫn file .tar>"
```

Script dựng sẵn site local tại `http://localhost/qpholdings` (cần XAMPP đang bật Apache + MySQL, ~7 GB ổ trống).

## Quy tắc quan trọng

Website thật đăng bài + video hằng ngày → **chỉ deploy code**, không bao giờ đưa database hay `uploads/` từ local lên
production. Chi tiết trong [AGENTS.md](AGENTS.md).
