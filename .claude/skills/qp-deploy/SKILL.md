---
name: qp-deploy
description: Đưa bản chỉnh sửa code (theme qp-holdings, plugin qp-llm-translate) của website QP Holdings lên hosting Plesk. Dùng khi người dùng muốn deploy, upload, cập nhật lên hosting/production/web thật.
---

Đọc toàn bộ `docs/ai/deploy-plesk.md` và mục "Bẫy quan trọng" trong `AGENTS.md`, rồi làm theo.

1. Kiểm tra (php -l, debug.log), đảm bảo mọi thay đổi đã commit — hỏi người dùng trước khi commit.
2. Chạy `powershell -ExecutionPolicy Bypass -File tools\make-deploy-zip.ps1` (thêm `-Full` nếu chưa có tag `deploy-*`).
   Script dừng vì theme-updates lệch → chạy với `-SyncThemeUpdates`, cho người dùng xem diff, commit, chạy lại.
3. Cho người dùng xem `FILES.txt` / `DELETED.txt`, rồi gửi hướng dẫn mục 3 (thao tác Plesk) kèm đường dẫn file zip.
4. Chỉ tạo tag `deploy-*` (và push) sau khi người dùng xác nhận site trên hosting chạy tốt.

Không bao giờ đưa database local lên production.
