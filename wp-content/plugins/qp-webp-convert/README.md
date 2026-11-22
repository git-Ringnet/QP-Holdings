# QP WebP Convert

Plugin của Ringnet cho website QP Holdings: tự chuyển ảnh JPG/PNG sang WebP, giữ nguyên ảnh gốc.

## Cách hoạt động

- File WebP lưu **cạnh** ảnh gốc: `anh.jpg` → `anh.jpg.webp` (cả ảnh gốc lẫn mọi kích thước thumbnail/medium/large).
- Hook `wp_update_attachment_metadata`: tải ảnh lên hoặc sửa ảnh thì chuyển tự động. Lỗi nào cũng bị bắt, ghi nhật ký, **không làm hỏng việc tải ảnh**.
- Phát ra website: output buffer bọc `<img>` thành `<picture><source type="image/webp">` khi file `.webp` tồn tại. Trình duyệt không hỗ trợ WebP dùng `<img>` gốc. Không phụ thuộc `Accept` header nên không làm sai cache LiteSpeed.
- Quy tắc: chất lượng mặc định 80; WebP lớn hơn hoặc bằng ảnh gốc thì bỏ WebP; GIF/SVG bỏ qua; PNG giữ kênh trong suốt; kiểm tra RAM trước khi mở ảnh lớn.
- Tắt tùy chọn **Bật chuyển đổi WebP** hoặc tắt plugin → website dùng lại ảnh gốc ngay (file `.webp` vẫn nằm đó, không ảnh hưởng).

## Giao diện: Media → Chuyển WebP

1. Cài đặt (bật/tắt, chất lượng).
2. Chẩn đoán hosting (phần A của bảng nghiệm thu) + khung văn bản để dán vào Excel.
3. Chuyển hàng loạt ảnh cũ: theo lô, dừng/chạy tiếp, **bắt buộc tick xác nhận đã sao lưu**.
4. Xóa toàn bộ file `.jpg.webp` / `.png.webp` do plugin tạo (không đụng ảnh gốc).
5. Nhật ký lỗi.

WP-CLI: `wp qpwc bulk --backup-done`

## Test

```
C:\xampp\php\php.exe tests/unit/run.php          # unit test, cần PHP có GD + WebP, không cần WordPress
cd tests/e2e && npm install && npx playwright install && npx playwright test
```

Chi tiết nghiệm thu: `docs/NGHIEM-THU-WEBP.md` ở thư mục gốc dự án.

## Thay ảnh gốc bằng WebP (từ 1.1.0) — giảm dung lượng hosting

Media → Chuyển WebP → mục **4. Thay ảnh gốc bằng WebP**. Chỉ đụng ảnh JPG/PNG (PDF, video, GIF, SVG giữ nguyên).

Mỗi ảnh làm theo thứ tự an toàn (dừng giữa chừng ở bước nào website vẫn đủ ảnh):
1. Tạo WebP cho ảnh gốc + mọi kích thước + `original_image` (ảnh trước khi WP thu nhỏ). Ảnh chính không có WebP nhỏ hơn → giữ nguyên cả ảnh; cỡ nào WebP không nhỏ hơn → giữ JPG/PNG của cỡ đó.
2. Đổi đường dẫn trong database: `posts.post_content/post_excerpt` (mọi loại bài trừ attachment, gồm bản dịch, revision), `postmeta`, `termmeta`, `options` (bỏ transient) — xử lý cả dữ liệu serialize, JSON (`\/`). Chỉ đổi đúng tên file (`anh.jpg` → `anh.jpg.webp`), không đụng `anh.jpg.bak`, `xanh.jpg`; chạy lại không bị `.webp.webp`.
3. Cập nhật bản ghi ảnh: `_wp_attached_file`, metadata (`file`, `sizes[*].file/mime-type/filesize`, `original_image`), `post_mime_type = image/webp`, `guid` — cho mọi attachment dùng chung file (Polylang).
4. Chuyển file gốc vào `uploads/qpwc-originals/<cùng đường dẫn>` (chưa xóa). Lưu thông tin hoàn tác vào meta `_qpwc_replaced`.

Sau đó: **Hoàn tác** (trả file gốc + đổi ngược đường dẫn) hoặc **Xóa vĩnh viễn ảnh gốc** (giải phóng dung lượng, không hoàn tác được nữa).

- Link cũ `.jpg/.png` (bài chia sẻ, chỗ sót) → 301 sang `.jpg.webp` khi file gốc không còn.
- Tùy chọn **Ảnh mới tải lên: thay luôn bằng WebP** (tự bật khi bắt đầu thay): chạy ở `wp_generate_attachment_metadata` (sau khi WP tạo xong mọi kích thước), đổi metadata trước khi trả về trình soạn bài, xóa ảnh gốc ngay.
- Nút "Xóa toàn bộ file WebP" bị chặn khi đã có ảnh được thay.
- Trên LiteSpeed: tự gọi `litespeed_purge_all` sau mỗi ảnh.
- WP-CLI: `wp qpwc replace --backup-done` · `wp qpwc replace --undo` · `wp qpwc replace --purge`

Test tích hợp (WordPress local, tự dọn dữ liệu thử): `C:\xampp\php\php.exe wp-content/plugins/qp-webp-convert/tests/replace/run.php`
