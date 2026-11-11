# Bảng tra: mã nghiệm thu WP-09…21 → code → commit

Dùng để báo cáo hằng ngày. Nhánh: `feature/webp-convert`. Plugin: `wp-content/plugins/qp-webp-convert/`.
Số dòng đúng tại thời điểm commit; đổi code sau đó thì số dòng có thể lệch, dùng tên hàm để tìm.

## Các commit

| Commit | Nội dung | File chính |
|---|---|---|
| `26c4ca9` | Lõi chuyển JPG/PNG sang WebP + unit test | `includes/class-qpwc-converter.php`, `tests/unit/run.php` |
| `a4b5740` | Cài đặt, nhật ký lỗi, hook chuyển khi tải ảnh lên | `includes/class-qpwc-settings.php`, `includes/class-qpwc-media.php` |
| `e8331c8` | Phát ảnh WebP ra website bằng `<picture>` | `includes/class-qpwc-frontend.php` |
| `77c991a` | Trang quản trị, chuyển hàng loạt, chẩn đoán hosting | `includes/class-qpwc-bulk.php`, `class-qpwc-diagnostics.php`, `class-qpwc-admin.php`, `assets/admin.js` |
| `567c276` | File chính, uninstall, README của plugin | `qp-webp-convert.php`, `uninstall.php`, `README.md` |
| `669dff7` | Bộ test Playwright WP-09…21 | `tests/e2e/*` |
| `e147b89` | Thêm plugin vào gói deploy | `tools/make-deploy-zip.ps1` |
| `d157ec0` | Tài liệu nghiệm thu, mô tả trong AGENTS.md | `docs/NGHIEM-THU-WEBP.md`, `AGENTS.md` |

## Từng mã WP

| Mã | Yêu cầu | Code (file : dòng) | Commit | Test |
|---|---|---|---|---|
| WP-09 | Ảnh JPG/PNG tải lên tự chuyển WebP | `class-qpwc-media.php:16,21`; `class-qpwc-converter.php:73`; `class-qpwc-settings.php:14` | `a4b5740`, `26c4ca9` | unit `run.php:74-88`; e2e `functional.spec.ts:27` |
| WP-10 | Mọi kích thước đều có WebP | `class-qpwc-media.php:76-84` | `a4b5740` | e2e `functional.spec.ts:37` |
| WP-11 | PNG trong suốt giữ nền | `class-qpwc-converter.php:200-203` | `26c4ca9` | unit `run.php:96`; e2e `:46` |
| WP-12 | GIF/SVG giữ nguyên | `class-qpwc-converter.php:99-102`; `class-qpwc-media.php:42-52` | `26c4ca9`, `a4b5740` | unit `run.php:110`; e2e `:83` |
| WP-13 | Hàng loạt, báo cáo khớp, danh sách ảnh lỗi | `class-qpwc-bulk.php:163,219`; `class-qpwc-admin.php:171,184`; `class-qpwc-converter.php:261` | `77c991a`, `26c4ca9` | unit `run.php:225-242`; e2e `:339` |
| WP-14 | Hàng loạt không làm đơ site, dừng/chạy tiếp, không làm lại | `class-qpwc-bulk.php:11-12,56,66,82`; `class-qpwc-converter.php:105-111`; `assets/admin.js` | `77c991a`, `26c4ca9` | unit `run.php:200`; e2e `:365,377` |
| WP-15 | Tắt thì về ảnh gốc, khôi phục | `class-qpwc-media.php:22`; `class-qpwc-frontend.php:26`; `class-qpwc-bulk.php:241`; `class-qpwc-admin.php:118` | `a4b5740`, `e8331c8`, `77c991a` | e2e `:257,413` |
| WP-16 | Trang tải ảnh WebP | `class-qpwc-frontend.php:46,79,105` | `e8331c8` | unit `run.php:254-305`; e2e `:133` |
| WP-17 | Hiển thị đúng trên các trình duyệt | `class-qpwc-frontend.php:105-150` (img gốc làm dự phòng) | `e8331c8` | e2e `browsers.spec.ts:22` (5 cấu hình) |
| WP-18 | 3 ngôn ngữ đúng ảnh, đổi ảnh không gọi API dịch | Không có code riêng; hook chỉ ở `class-qpwc-media.php:16` | `a4b5740` | e2e `functional.spec.ts:216` |
| WP-19 | Chất lượng chấp nhận được | `class-qpwc-settings.php:38-52`; `class-qpwc-converter.php:77` | `a4b5740`, `26c4ca9` | unit `run.php:189`; e2e `:199` |
| WP-20 | Đo mức giảm; WebP lớn hơn thì giữ ảnh gốc | `class-qpwc-converter.php:163-171`; `class-qpwc-media.php:96`; `class-qpwc-admin.php:171` | `26c4ca9`, `a4b5740`, `77c991a` | unit `run.php:174`; e2e `:160` |
| WP-21 | Ảnh lỗi không làm hỏng việc tải lên | `class-qpwc-media.php:27-30,91`; `class-qpwc-converter.php:132-139,182`; `class-qpwc-settings.php:58` | `a4b5740`, `26c4ca9` | unit `run.php:134,157`; e2e `:103,122` |

## Phần A (hỗ trợ đo trên hosting)

| Mã | Code | Commit |
|---|---|---|
| WP-01…06 | `includes/class-qpwc-diagnostics.php` (trang Chẩn đoán) | `77c991a` |
| WP-07 | `class-qpwc-bulk.php:44` (chặn chạy hàng loạt khi chưa xác nhận sao lưu) | `77c991a` |
| WP-08 | Không có code, chạy lại trang Chẩn đoán trên hosting QP | — |

## Lỗi đã bắt được và sửa trong lúc test (để báo cáo)
- Nút "đã sao lưu" không lưu được trong trang quản trị (`class-qpwc-settings.php`, hàm `sanitize`).
- Chạy hàng loạt rất chậm vì lô 10 ảnh và ghi nhật ký từng ảnh thiếu file (`class-qpwc-bulk.php`, `class-qpwc-media.php`).
- Bấm "Bắt đầu" rồi "Tạm dừng" ngay thì lệnh dừng bị bỏ qua do yêu cầu đến sai thứ tự (`assets/admin.js`, hàng đợi yêu cầu).
- Cả ba chỗ sửa đã nằm sẵn trong các commit ở trên (không có commit sửa riêng), vì lỗi được phát hiện và sửa trước khi commit.
