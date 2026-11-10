# QP Holdings WordPress Theme

Theme này được generate từ folder `site/` và đặt trong folder mới `wordpress-site/wp-content/themes/qp-holdings/`.

## Cài đặt

1. Copy folder `qp-holdings` vào `wp-content/themes/` của WordPress.
2. Vào **Appearance → Themes** và activate theme **QP Holdings**.
3. Theme sẽ tự tạo các page starter nếu chưa có: `gioi-thieu`, `du-an`, `tintuc`, `tuyen-dung`, `lien-he`, và nhóm `nha-dau-tu/*`.
4. Vào **Settings → Permalinks** chọn **Post name** để URL dạng `/gioi-thieu/` hoạt động đúng.
5. Vào **Settings → Reading** kiểm tra homepage đang là trang `Trang Chủ`.

## Chạy local bằng Podman

Từ folder `wordpress-site/`:

```bash
podman-compose up -d db wordpress
./bin/setup-wordpress.sh
```

- Website: <http://localhost:8090>
- Admin: <http://localhost:8090/wp-admin>
- User/pass mặc định local: `admin` / `admin123`

## Ghi chú

- Đây là theme classic giữ nguyên giao diện/HTML/CSS/JS từ bản static export.
- Nội dung hiện tại vẫn là static trong template PHP. Nếu cần CMS hóa từng section, bước tiếp theo nên tách các phần Tin tức/Dự án/Tuyển dụng/Quan hệ nhà đầu tư thành CPT hoặc ACF fields.
- Folder assets nằm ở `assets/` và được tham chiếu bằng `get_template_directory_uri()`.

## Tối ưu asset, SEO và performance

- Theme dùng `filemtime()` làm version cho asset nội bộ để cache trình duyệt chính xác hơn khi CSS/JS thay đổi.
- Các stylesheet trùng từ bản static export được lọc bớt ở output HTML; WordPress enqueue là nguồn asset chính.
- Font Awesome chỉ enqueue bản minified, tránh tải đồng thời `all.min.css` và `all.css`.
- AOS và Swiper chỉ tải ở trang chủ, được enqueue qua WordPress và chạy ở footer thay vì hard-code trực tiếp trong template.
- WordPress emoji script/style và generator meta được tắt để giảm request/byte không cần thiết.
- Theme có lớp SEO frontend riêng: canonical, meta description, meta keywords, Open Graph, Twitter Card và JSON-LD Organization/Corporation/WebSite/BreadcrumbList/WebPage/NewsArticle.
- Nếu Rank Math đang active, theme giữ plugin cho sitemap/robots/admin tools nhưng suppress frontend `<head>` output của Rank Math để khách hàng chỉnh SEO trong meta box tiếng Việt của theme và tránh trùng meta/schema.
- Script setup local hiện cài/activate các plugin nền tảng: ACF, Contact Form 7, Flamingo, Rank Math SEO, Safe SVG và Classic Editor.

## Đa ngôn ngữ

Theme có lớp tích hợp Polylang trong `inc/i18n.php`:

- Ngôn ngữ hỗ trợ: Vietnamese (`vi`), English (`en`), Chinese (`zh`).
- Slug tiếng Anh và tiếng Trung dùng slug English, ví dụ `/en/about/` và `/zh/about/`.
- Header language switcher dùng Polylang khi plugin active, có fallback an toàn khi plugin chưa cài.
- Các CPT/taxonomy custom được khai báo cho Polylang qua filter `pll_get_post_types` và `pll_get_taxonomies`.
- Theme tự tạo starter pages đa ngôn ngữ và liên kết bản dịch nếu Polylang đã active.
- SEO fallback tự đổi `og:locale` và JSON-LD `inLanguage` theo ngôn ngữ hiện tại.

Sau khi cài Polylang, vào **Languages → Languages** tạo 3 ngôn ngữ:

1. `vi` — Tiếng Việt — default language.
2. `en` — English.
3. `zh` — 中文.

Sau đó vào **Settings → Permalinks** bấm **Save Changes** để flush rewrite rules. Nếu theme đã active trước khi cài Polylang, re-activate theme hoặc xóa option `qp_holdings_starter_pages_multilingual_v1` để theme tạo lại starter pages đa ngôn ngữ.
