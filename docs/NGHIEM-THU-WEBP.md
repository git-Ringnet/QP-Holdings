# Nghiệm thu "Tự động chuyển đổi ảnh sang WebP" (tab 2 của file Excel)

Plugin: `wp-content/plugins/qp-webp-convert/` — giao diện ở **Media → Chuyển WebP**.
Kết quả test tự động chạy ngày 11/11/2026 trên máy dev (XAMPP, Apache, PHP 8.4 + GD, Chrome/Firefox/WebKit qua Playwright).

Ký hiệu: **ĐẠT-local** = đã có test tự động chạy đạt trên máy dev. **CẦN HOSTING** = phải làm lại trên hosting thật (MAXSPEED 1, rồi WP V5 Business). **THỦ CÔNG** = người nghiệm thu tự làm.

## Bảng tổng hợp

| Mã | Trước đây | Hiện tại | Đã test thế nào | Còn phải làm |
|---|---|---|---|---|
| WP-01 | Chưa có công cụ | ĐẠT-local | Trang Chẩn đoán đọc GD/Imagick; chuyển thử 1 PNG thật | **CẦN HOSTING** |
| WP-02 | Chưa có | ĐẠT-local | Ghi/xóa file `.webp` thử trong uploads; tải ảnh lên sinh được WebP | **CẦN HOSTING** |
| WP-03 | Chưa có | Có công cụ đo | Nút "Đo dung lượng uploads" chạy đúng | Ghi số GB trước/sau trên hosting. **CHƯA có số liệu thật** |
| WP-04 | Chưa có | ĐẠT-local | Ảnh 6000×4000 xử lý ~9 giây, không lỗi RAM (memory_limit 512M). Unit test: ảnh quá lớn so với RAM thì dừng an toàn | **CẦN HOSTING** (ảnh lớn nhất của site, RAM của gói QP) |
| WP-05 | Chưa có | ĐẠT-local | Playwright đọc header: `image/webp` (Apache) | **CẦN HOSTING** (LiteSpeed) |
| WP-06 | Chưa có | Một phần | Đổi ảnh trong trang thì hiện ảnh mới ngay (Apache, không có cache LiteSpeed) | **CẦN HOSTING**: bật LiteSpeed Cache, đổi ảnh, xóa cache |
| WP-07 | Chưa có | ĐẠT-local (phần cổng chặn) | Chưa tick "đã sao lưu" thì server từ chối chạy hàng loạt | THỦ CÔNG: sao lưu thật (Plesk), ghi giờ |
| WP-08 | Chưa có | Chưa làm | — | **CẦN HOSTING QP**: chạy lại trang Chẩn đoán |
| WP-09 | Chưa có | ĐẠT-local | Tải JPG và PNG qua WordPress → có `.jpg.webp`, `.png.webp`; ảnh gốc còn nguyên | — |
| WP-10 | Chưa có | ĐẠT-local | Mọi kích thước (thumbnail, medium, large…) đều có WebP | — |
| WP-11 | Chưa có | ĐẠT-local | Đọc kênh alpha của WebP trong trình duyệt: góc trong suốt, tâm đặc | THỦ CÔNG: xem trên nền tối |
| WP-12 | Chưa có | ĐẠT-local | GIF: không sinh WebP, vẫn hiển thị `image/gif`. SVG: nếu site cho tải thì cũng giữ nguyên | — |
| WP-13 | Chưa có | ĐẠT-local | Báo cáo Tổng = Thành công + Lỗi + Bỏ qua + Chưa xử lý; có danh sách ảnh lỗi | Chạy lại trên hosting (xem lưu ý bên dưới) |
| WP-14 | Chưa có | ĐẠT-local | Chạy theo lô; dừng rồi chạy tiếp; chạy lại không đổi file WebP đã xong; khách vẫn mở được site khi đang chạy | Thử với số lượng ảnh thật trên hosting |
| WP-15 | Chưa có | ĐẠT-local | Tắt chức năng → không còn `<picture>`, ảnh gốc hiển thị; nút "Xóa toàn bộ file WebP" không đụng ảnh gốc | — |
| WP-16 | Chưa có | ĐẠT-local (ảnh trong uploads) | Network: ảnh tải về là `.webp`, `image/webp`, status 200 | **CẦN HOSTING**: mở trang chủ, dự án, tin bằng DevTools. Ảnh cố định trong theme không do plugin này xử lý |
| WP-17 | Chưa có | ĐẠT-local | Chromium, Firefox, WebKit, Pixel 5, iPhone 13 (giả lập): ảnh hiển thị, dùng WebP, không tràn màn hình | THỦ CÔNG: Edge, Safari thật, iPhone thật |
| WP-18 | Chưa có | ĐẠT-local | vi/en/zh cùng dùng đúng ảnh WebP. Số ký tự dịch Google (`qpat_usage`) và số job dịch không đổi khi tải/đổi ảnh | — |
| WP-19 | Chưa có | Một phần | Chất lượng 80; PSNR 31,6 dB trên ảnh mẫu tổng hợp | THỦ CÔNG: so 5 ảnh thật bằng mắt |
| WP-20 | Chưa có | Một phần | Trang mẫu: 205.572 B → 93.636 B (giảm 54,5%). WebP lớn hơn ảnh gốc thì giữ ảnh gốc (unit test) | THỦ CÔNG: đo 3 trang thật. Không cam kết tỷ lệ |
| WP-21 | Chưa có | ĐẠT-local | File ảnh hỏng không gây lỗi 5xx; sau đó tải ảnh tốt bình thường; có nhật ký lỗi | **CẦN HOSTING**: thử ảnh vượt giới hạn upload của hosting |

## Điều phải nói rõ khi nghiệm thu

1. Số 54,5% và 31,6 dB lấy từ **ảnh mẫu tự tạo**, không dùng làm cam kết. Phải đo lại bằng ảnh thật của site.
2. Trên máy dev, báo cáo hàng loạt có hàng trăm ảnh "lỗi: missing_file" vì **thư mục uploads không có trong git** (database nhớ ảnh nhưng file không có). Trên hosting thật số này sẽ khác. Danh sách ảnh lỗi chính là chức năng cần nghiệm thu ở WP-13.
3. Đường Imagick chưa được test (máy dev không có Imagick). Đường GD đã test.
4. Chưa test trên PHP 7.4 (chỉ PHP 8.4), chưa test trên LiteSpeed thật.
5. Plugin chỉ bọc thẻ `<img>`. Ảnh nền CSS (`background-image`), ảnh lazy-load dùng `data-src`, ảnh ở domain khác không được chuyển. Ảnh cố định trong theme đã có cơ chế WebP riêng (`qp_holdings_theme_picture`).
6. Việc WebP/chịu tải **chưa có trong Phụ lục 01** của báo giá (theo file Excel). Nên chốt phạm vi bằng văn bản với khách.

## Hướng dẫn nghiệm thu (làm theo thứ tự)

### Chuẩn bị
1. Cài plugin: đưa thư mục `qp-webp-convert` vào `wp-content/plugins/`, kích hoạt.
2. Mở **Media → Chuyển WebP**. Phải thấy mục "Chẩn đoán hosting".
3. Nếu có khung đỏ "Máy chủ chưa hỗ trợ tạo WebP" → dừng, báo nhà hosting bật GD/Imagick có WebP.

### Phần A: hosting (WP-01…08)
1. Trong mục **2. Chẩn đoán**, đọc từng dòng. Mã WP-01, 02, 05 phải ở trạng thái "Đạt".
2. Bấm **Đo dung lượng uploads** → ghi số GB cho WP-03. Cộng thêm ước tính phần WebP (khoảng 30–60% dung lượng ảnh JPG/PNG), so với 20 GB (test) và 18 GB (QP).
3. Ghi `memory_limit`, `max_execution_time`, `upload_max_filesize` (WP-04). Tải thử ảnh lớn nhất của site, xem có sinh WebP.
4. WP-06: bật LiteSpeed Cache, thay ảnh trong một trang, xóa cache, tải lại xem ảnh mới.
5. WP-07: sao lưu thư mục ảnh và database trên Plesk, ghi giờ.
6. Copy khung "Văn bản để dán vào cột Ghi chú" vào Excel. Lặp lại mục này trên hosting QP (WP-08).

### Phần B: chức năng (WP-09…21)
1. **WP-09/10/11/12**: Media → Add New, tải 1 JPG, 1 PNG nền trong suốt, 1 GIF. Kiểm tra trong thư mục uploads: JPG/PNG có thêm file `.jpg.webp`/`.png.webp` (cả các kích thước `-150x150`, `-300x…`), GIF thì không.
2. **WP-16**: tạo bài/trang có ảnh vừa tải, mở bằng Chrome DevTools → Network → lọc Img: ảnh phải có đuôi `.webp`, Type `webp`.
3. **WP-17**: lặp lại trên Chrome, Edge, Firefox, Safari, điện thoại.
4. **WP-18**: mở trang cùng ảnh ở `/`, `/en/`, `/zh/`. Vào **Cài đặt → Dịch tự động**, số ký tự đã dùng không được tăng khi chỉ đổi ảnh.
5. **WP-19/20**: so 5 ảnh gốc với WebP; đo tổng KB ảnh của 3 trang (Network → tổng dòng Img) lúc tắt rồi bật plugin.
6. **WP-13/14**: tick "Tôi đã sao lưu" → **Bắt đầu chạy hàng loạt**. Thử **Tạm dừng** rồi **Chạy tiếp**, mở website ở tab khác trong lúc chạy. Cuối cùng xem bảng báo cáo (tổng khớp) và danh sách ảnh lỗi. Bấm **Bắt đầu** lần nữa: ảnh đã xong không bị tạo lại.
7. **WP-15**: bỏ tick **Bật chuyển đổi WebP**, Lưu → tải lại trang: ảnh phải hiển thị bình thường dạng gốc, không còn `<picture>`.
8. **WP-21**: tải lên 1 file giả đổi đuôi `.jpg` và 1 ảnh vượt giới hạn upload. Phải có thông báo lỗi của WordPress hoặc ghi ở **5. Nhật ký lỗi**, website không trắng trang.

## Chạy lại test tự động
```
C:\xampp\php\php.exe wp-content\plugins\qp-webp-convert\tests\unit\run.php
cd wp-content\plugins\qp-webp-convert\tests\e2e
npm install
npx playwright install
npx playwright test --project=functional        # ~12 phút (phần hàng loạt chạy lâu)
npx playwright test --project=chromium --project=firefox --project=webkit --project=mobile-chrome --project=mobile-safari
```
Test tự dọn dữ liệu thử (trang, ảnh, thống kê) khi xong. Đăng nhập mặc định thử `admin` với `minhhieu2002` rồi `admin123` (đổi bằng `QP_USER`, `QP_PASS`).
