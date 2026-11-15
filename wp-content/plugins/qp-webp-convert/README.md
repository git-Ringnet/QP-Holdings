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
