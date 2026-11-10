---
name: qp-setup-local
description: Dựng môi trường chạy local cho website QP Holdings sau khi clone repo (XAMPP, lõi WordPress, database, uploads, wp-config, plugin). Dùng khi người dùng muốn cài đặt, chạy thử, setup dự án trên máy mới.
---

Đọc toàn bộ `docs/ai/setup-local.md` rồi làm theo tuần tự từng bước.
Nếu người dùng có file backup Plesk (`backup_qpholdings.vn_*.tar`) thì dùng `tools\restore-plesk-backup.ps1` như hướng dẫn.

- Báo tiến độ cho người dùng sau mỗi bước.
- Thứ không có trong git (dump database, uploads, gói Duplicator, mật khẩu admin): hỏi người dùng, không tự bịa.
- Không commit `wp-config.php` hay bất kỳ file dữ liệu nào.
- Kết thúc bằng bước 8 (Kiểm tra) và báo cáo URL local cùng những gì còn thiếu.
