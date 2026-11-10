<?php
/**
 * 404 — Không tìm thấy trang.
 * Sử dụng cùng ngôn ngữ thiết kế với các trang khác:
 *   - Hero full-bleed: ảnh + lớp gradient navy (#0b1f3a)
 *   - Breadcrumb trắng / chevron vàng
 *   - Eyebrow uppercase 12px tracking-[0.25em] màu gold #c9a96e
 *   - Container max-width 1250px, Open Sans
 *   - Card border #e8e8e8, radius 4px, padding 32px 24px
 */

defined( 'ABSPATH' ) || exit;

get_header();

$qp_404_hero_bg   = get_template_directory_uri() . '/assets/index_files/about-banner.jpg';
$qp_404_card_icon = static function ( string $svg ): string {
	return '<span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:999px;background:rgba(201,169,110,0.12);color:#c9a96e;flex:none;">' . $svg . '</span>';
};

$qp_404_links = array(
	array(
		'label' => 'Giới thiệu',
		'desc'  => 'Hai thập kỷ kiến tạo giá trị bền vững cùng QP Holdings.',
		'url'   => qp_holdings_page_url( 'about' ),
		'icon'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1"/><path d="M14 9h1"/><path d="M9 13h1"/><path d="M14 13h1"/><path d="M9 17h1"/><path d="M14 17h1"/></svg>',
	),
	array(
		'label' => 'Dự án',
		'desc'  => 'Các công trình trọng điểm và hệ sinh thái dự án của tập đoàn.',
		'url'   => qp_holdings_page_url( 'projects' ),
		'icon'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
	),
	array(
		'label' => 'Tin tức',
		'desc'  => 'Cập nhật hoạt động, sự kiện và tin nội bộ mới nhất.',
		'url'   => qp_holdings_page_url( 'news' ),
		'icon'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/></svg>',
	),
	array(
		'label' => 'Liên hệ',
		'desc'  => 'Để lại thông tin, đội ngũ QP Holdings sẽ phản hồi sớm nhất.',
		'url'   => qp_holdings_page_url( 'contact' ),
		'icon'  => '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.37 1.9.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.35 1.85.59 2.81.72A2 2 0 0 1 22 16.92Z"/></svg>',
	),
);
?>

<style id="qp-404-styles">
	.qp-404-hero {
		position: relative;
		min-height: 560px;
		display: flex;
		align-items: center;
		background-image: url('<?php echo esc_url( $qp_404_hero_bg ); ?>');
		background-size: cover;
		background-position: center;
		overflow: hidden;
	}

	.qp-404-hero::before {
		content: "";
		position: absolute;
		inset: 0;
		background: linear-gradient(to right, rgba(11, 31, 58, 0.94), rgba(11, 31, 58, 0.78) 50%, rgba(11, 31, 58, 0.55));
	}

	.qp-404-hero::after {
		content: "";
		position: absolute;
		inset: 0;
		background: radial-gradient(ellipse at 20% 50%, rgba(201, 169, 110, 0.18), transparent 55%);
		pointer-events: none;
	}

	.qp-404-hero__container {
		position: relative;
		z-index: 2;
		width: 100%;
		max-width: 1250px;
		margin: 0 auto;
		padding: 120px 20px 90px;
		display: grid;
		grid-template-columns: minmax(0, 1fr) auto;
		align-items: center;
		gap: 56px;
	}

	.qp-404-hero__copy {
		max-width: 640px;
		color: #fff;
	}

	.qp-404-breadcrumb {
		display: flex;
		align-items: center;
		gap: 8px;
		font-size: 12px;
		color: rgba(255, 255, 255, 0.7);
		margin-bottom: 18px;
	}

	.qp-404-breadcrumb a {
		color: rgba(255, 255, 255, 0.7);
		text-decoration: none;
		transition: color .2s ease;
	}

	.qp-404-breadcrumb a:hover {
		color: #fff;
	}

	.qp-404-breadcrumb span.sep {
		color: #c9a96e;
		display: inline-flex;
	}

	.qp-404-breadcrumb span.current {
		color: #fff;
	}

	.qp-404-eyebrow {
		font-size: 12px;
		font-weight: 700;
		letter-spacing: 0.28em;
		color: #c9a96e;
		text-transform: uppercase;
		display: inline-flex;
		align-items: center;
		gap: 10px;
	}

	.qp-404-eyebrow::before {
		content: "";
		width: 28px;
		height: 1px;
		background: #c9a96e;
		display: inline-block;
	}

	.qp-404-title {
		margin: 18px 0 0;
		font-family: 'Open Sans', sans-serif;
		font-size: 44px;
		line-height: 1.15;
		font-weight: 700;
		color: #fff;
	}

	.qp-404-lead {
		margin: 18px 0 0;
		font-size: 17px;
		line-height: 1.75;
		color: rgba(255, 255, 255, 0.82);
		max-width: 560px;
	}

	.qp-404-actions {
		margin-top: 32px;
		display: flex;
		flex-wrap: wrap;
		gap: 14px;
	}

	.qp-404-btn {
		display: inline-flex;
		align-items: center;
		gap: 10px;
		padding: 15px 26px;
		font-size: 13px;
		font-weight: 700;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		text-decoration: none;
		border-radius: 2px;
		transition: background .25s ease, color .25s ease, border-color .25s ease, transform .25s ease;
	}

	.qp-404-btn--primary {
		background: #c9a96e;
		color: #0b1f3a;
		border: 1px solid #c9a96e;
	}

	.qp-404-btn--primary:hover {
		background: #b8985d;
		border-color: #b8985d;
		color: #0b1f3a;
		transform: translateY(-1px);
	}

	.qp-404-btn--ghost {
		background: transparent;
		color: #fff;
		border: 1px solid rgba(255, 255, 255, 0.4);
	}

	.qp-404-btn--ghost:hover {
		background: rgba(255, 255, 255, 0.08);
		border-color: #fff;
		color: #fff;
	}

	.qp-404-numeral {
		font-family: 'Open Sans', sans-serif;
		font-weight: 700;
		font-size: 240px;
		line-height: 0.85;
		letter-spacing: -0.04em;
		color: transparent;
		-webkit-text-stroke: 1.5px rgba(201, 169, 110, 0.65);
		background: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0) 60%);
		-webkit-background-clip: text;
		background-clip: text;
		user-select: none;
		text-shadow: 0 18px 60px rgba(0, 0, 0, 0.25);
	}

	/* Search row */
	.qp-404-search {
		margin-top: 28px;
		max-width: 460px;
	}

	.qp-404-search form {
		display: flex;
		align-items: stretch;
		gap: 0;
		width: 100%;
		margin: 0;
		padding: 0;
		background: rgba(255, 255, 255, 0.08);
		border: 1px solid rgba(255, 255, 255, 0.25);
		border-radius: 2px;
		overflow: hidden;
		transition: border-color .25s ease, background .25s ease;
		box-sizing: border-box;
	}

	.qp-404-search form:focus-within {
		border-color: #c9a96e;
		background: rgba(255, 255, 255, 0.12);
	}

	.qp-404-search input[type="search"],
	.qp-404-search .search-field {
		flex: 1 1 auto;
		min-width: 0;
		width: 100%;
		height: 48px;
		padding: 0 16px;
		margin: 0;
		background: transparent;
		border: 0;
		border-radius: 0;
		color: #fff;
		font-size: 14px;
		outline: none;
		box-shadow: none;
		box-sizing: border-box;
		-webkit-appearance: none;
		appearance: none;
	}

	.qp-404-search input[type="search"]::placeholder,
	.qp-404-search .search-field::placeholder {
		color: rgba(255, 255, 255, 0.55);
	}

	.qp-404-search button {
		flex: 0 0 auto;
		width: 48px;
		height: 48px;
		min-width: 0;
		padding: 0;
		margin: 0;
		border: 0;
		border-radius: 0;
		background: #c9a96e;
		color: #0b1f3a;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		line-height: 1;
		box-shadow: none;
		transition: background .25s ease;
	}

	.qp-404-search button:hover {
		background: #b8985d;
		color: #0b1f3a;
	}

	/* Suggested links */
	.qp-404-suggest {
		padding: 80px 0;
		background: #fff;
	}

	.qp-404-suggest__container {
		max-width: 1250px;
		margin: 0 auto;
		padding: 0 20px;
	}

	.qp-404-suggest__header {
		max-width: 720px;
		margin-bottom: 40px;
	}

	.qp-404-suggest__title {
		margin: 16px 0 0;
		font-family: 'Open Sans', sans-serif;
		font-size: 32px;
		font-weight: 700;
		color: #0b1f3a;
		line-height: 1.25;
	}

	.qp-404-suggest__lead {
		margin: 14px 0 0;
		font-size: 15px;
		line-height: 1.75;
		color: #5b6678;
	}

	.qp-404-grid {
		display: grid;
		grid-template-columns: repeat(4, 1fr);
		gap: 24px;
	}

	.qp-404-card {
		display: flex;
		flex-direction: column;
		gap: 16px;
		padding: 32px 24px;
		background: #fff;
		border: 1px solid #e8e8e8;
		border-radius: 4px;
		text-decoration: none;
		color: inherit;
		transition: border-color .3s ease, box-shadow .3s ease, transform .3s ease;
	}

	.qp-404-card:hover {
		border-color: #c9a96e;
		box-shadow: 0 18px 40px rgba(11, 31, 58, 0.08);
		transform: translateY(-3px);
	}

	.qp-404-card__title {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin: 0;
		font-size: 18px;
		font-weight: 700;
		color: #0b1f3a;
		font-family: 'Open Sans', sans-serif;
	}

	.qp-404-card__title svg.chev {
		width: 16px;
		height: 16px;
		color: #c9a96e;
		opacity: 0;
		transform: translateX(-6px);
		transition: opacity .25s ease, transform .25s ease;
	}

	.qp-404-card:hover .qp-404-card__title svg.chev {
		opacity: 1;
		transform: translateX(0);
	}

	.qp-404-card__desc {
		margin: 0;
		font-size: 14px;
		line-height: 1.7;
		color: #666;
	}

	/* CTA strip */
	.qp-404-contact {
		padding: 56px 0 72px;
		background: #f7f8fb;
	}

	.qp-404-contact__inner {
		max-width: 1250px;
		margin: 0 auto;
		padding: 0 20px;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-between;
		gap: 20px;
	}

	.qp-404-contact__copy {
		max-width: 720px;
	}

	.qp-404-contact__eyebrow {
		font-size: 12px;
		font-weight: 700;
		letter-spacing: 0.28em;
		color: #c9a96e;
		text-transform: uppercase;
	}

	.qp-404-contact__title {
		margin: 12px 0 0;
		font-size: 26px;
		font-weight: 700;
		color: #0b1f3a;
		font-family: 'Open Sans', sans-serif;
		line-height: 1.3;
	}

	.qp-404-contact__btn {
		display: inline-flex;
		align-items: center;
		gap: 10px;
		padding: 15px 26px;
		font-size: 13px;
		font-weight: 700;
		letter-spacing: 0.06em;
		text-transform: uppercase;
		text-decoration: none;
		background: #0b1f3a;
		color: #fff;
		border-radius: 2px;
		transition: background .25s ease;
	}

	.qp-404-contact__btn:hover {
		background: #c9a96e;
		color: #0b1f3a;
	}

	/* ===== Responsive ===== */
	@media (max-width: 1024px) {
		.qp-404-hero__container {
			grid-template-columns: 1fr;
			gap: 24px;
			padding-top: 110px;
			padding-bottom: 70px;
		}

		.qp-404-numeral {
			font-size: 200px;
			order: -1;
		}

		.qp-404-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (max-width: 720px) {
		.qp-404-hero {
			min-height: 520px;
		}

		.qp-404-hero__container {
			padding: 96px 18px 56px;
		}

		.qp-404-title {
			font-size: 32px;
		}

		.qp-404-lead {
			font-size: 15px;
		}

		.qp-404-numeral {
			font-size: 150px;
			-webkit-text-stroke-width: 1px;
		}

		.qp-404-actions {
			gap: 10px;
		}

		.qp-404-btn {
			padding: 13px 20px;
			font-size: 12px;
		}

		.qp-404-suggest {
			padding: 56px 0;
		}

		.qp-404-suggest__title {
			font-size: 26px;
		}

		.qp-404-grid {
			grid-template-columns: 1fr;
		}

		.qp-404-contact__inner {
			flex-direction: column;
			align-items: flex-start;
		}
	}
</style>

<main id="main" class="qp-404-page">

	<!-- HERO -->
	<section class="qp-404-hero" aria-labelledby="qp-404-title">
		<div class="qp-404-hero__container">

			<div class="qp-404-hero__copy">
				<nav class="qp-404-breadcrumb" aria-label="Breadcrumb">
					<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>">Trang chủ</a>
					<span class="sep" aria-hidden="true">
						<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
					</span>
					<span class="current">404 — Không tìm thấy</span>
				</nav>

				<span class="qp-404-eyebrow">LỖI 404</span>

				<h1 id="qp-404-title" class="qp-404-title">Rất tiếc, trang bạn tìm không tồn tại</h1>

				<p class="qp-404-lead">
					Liên kết có thể đã được di chuyển, đổi tên hoặc tạm thời không khả dụng.
					Bạn có thể quay lại trang chủ, sử dụng thanh tìm kiếm bên dưới hoặc khám phá các chuyên mục được đề xuất.
				</p>

				<div class="qp-404-search" role="search">
					<form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label class="screen-reader-text" for="qp-404-s"><?php esc_html_e( 'Tìm kiếm', 'qp-holdings' ); ?></label>
						<input type="search" id="qp-404-s" class="search-field" name="s" placeholder="Tìm thông tin, dự án, tin tức…" value="<?php echo esc_attr( get_search_query() ); ?>" />
						<button type="submit" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'qp-holdings' ); ?>">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
						</button>
					</form>
				</div>

				<div class="qp-404-actions">
					<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="qp-404-btn qp-404-btn--primary">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12 12 3l9 9"/><path d="M5 10v10h14V10"/></svg>
						Về trang chủ
					</a>
					<a href="<?php echo esc_url( qp_holdings_page_url( 'contact' ) ); ?>" class="qp-404-btn qp-404-btn--ghost">
						Liên hệ hỗ trợ
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
					</a>
				</div>
			</div>

			<div class="qp-404-numeral" aria-hidden="true">404</div>

		</div>
	</section>

	<!-- SUGGESTED LINKS -->
	<section class="qp-404-suggest" aria-labelledby="qp-404-suggest-title">
		<div class="qp-404-suggest__container">
			<div class="qp-404-suggest__header">
				<span class="qp-404-eyebrow" style="color:#0b1f3a;">GỢI Ý CHO BẠN</span>
				<h2 id="qp-404-suggest-title" class="qp-404-suggest__title">Khám phá những nội dung nổi bật</h2>
				<p class="qp-404-suggest__lead">Một vài lối tắt đến những thông tin được xem nhiều nhất tại QP Holdings.</p>
			</div>

			<div class="qp-404-grid">
				<?php foreach ( $qp_404_links as $item ) : ?>
					<a class="qp-404-card" href="<?php echo esc_url( $item['url'] ); ?>">
						<?php echo $qp_404_card_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline SVG icons are static & safe. ?>
						<h3 class="qp-404-card__title">
							<span><?php echo esc_html( $item['label'] ); ?></span>
							<svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
						</h3>
						<p class="qp-404-card__desc"><?php echo esc_html( $item['desc'] ); ?></p>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- CONTACT STRIP -->
	<section class="qp-404-contact" aria-label="Liên hệ hỗ trợ">
		<div class="qp-404-contact__inner">
			<div class="qp-404-contact__copy">
				<span class="qp-404-contact__eyebrow">CẦN HỖ TRỢ?</span>
				<h2 class="qp-404-contact__title">Đội ngũ QP Holdings luôn sẵn sàng đồng hành cùng bạn.</h2>
			</div>
			<a href="<?php echo esc_url( qp_holdings_page_url( 'contact' ) ); ?>" class="qp-404-contact__btn">
				Liên hệ ngay
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
			</a>
		</div>
	</section>

</main>

<?php get_footer(); ?>
