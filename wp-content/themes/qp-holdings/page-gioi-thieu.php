<?php
/**
 * Template Name: QP Holdings - Giới thiệu
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-about';
	return $classes;
} );
?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main class="about-page">

	<!-- Decorative gold blobs spanning the page -->
	<div class="page-blobs" aria-hidden="true">
		<span class="page-blob page-blob-1"></span>
		<span class="page-blob page-blob-2"></span>
		<span class="page-blob page-blob-3"></span>
	</div>

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'Thông điệp QP Holdings' ) ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'about', 'assets/site/about-hero-bg-62c368.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="<?php echo esc_attr( qp_holdings_t( 'Breadcrumb' ) ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'Trang chủ' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( qp_holdings_t( 'Giới thiệu' ) ); ?></span>
			</nav>
		</div>
		<div class="container page-hero-content">
			<div class="page-hero-eyebrow reveal" aria-label="<?php echo esc_attr( qp_holdings_t( 'Thông điệp QP Holdings' ) ); ?>">
				<span class="page-hero-eyebrow-text"<?php qp_holdings_te_attrs( 'THÔNG ĐIỆP QP HOLDINGS' ); ?>><?php echo esc_html( qp_holdings_t( 'THÔNG ĐIỆP QP HOLDINGS' ) ); ?></span>
			</div>
			<h1 class="page-hero-title reveal reveal-delay-1">
				<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'Kiến tạo giá trị,' ); ?>><?php echo esc_html( qp_holdings_t( 'Kiến tạo giá trị,' ) ); ?></span>
				<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'đồng hành thịnh vượng' ); ?>><?php echo esc_html( qp_holdings_t( 'đồng hành thịnh vượng' ) ); ?></span>
			</h1>
			<p class="page-hero-desc reveal reveal-delay-2"><?php qp_holdings_te( 'QP Holdings định hướng phát triển trở thành Tập đoàn đầu tư đa ngành, tập trung xây dựng hệ sinh thái đầu tư hiệu quả, tối ưu giá trị tài sản và gia tăng giá trị bền vững cho đối tác, khách hàng và nhà đầu tư.' ); ?></p>
		</div>
	</section>

	<!-- ═══ INTRO ═══ -->
	<section class="intro-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'Giới thiệu QP Holdings' ) ); ?>">
		<div class="container">
			<div class="intro-grid">
				<h2 class="section-title intro-title reveal">
					<span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'QP Holdings –' ); ?>><?php echo esc_html( qp_holdings_t( 'QP Holdings –' ) ); ?></span>
					<br class="title-break-mobile">
					<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'Kiến tạo giá trị,' ); ?>><?php echo esc_html( qp_holdings_t( 'Kiến tạo giá trị,' ) ); ?></span>
					<br>
					<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'đồng hành thịnh vượng' ); ?>><?php echo esc_html( qp_holdings_t( 'đồng hành thịnh vượng' ) ); ?></span>
				</h2>
				<div class="intro-images reveal reveal-delay-1" role="img" aria-label="<?php echo esc_attr( qp_holdings_t( 'Hình ảnh QP Holdings' ) ); ?>">
					<div class="about-collage">
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-1-3ba564.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Khu cong nghiep va nha may' ), 'class' => 'about-img about-img-1', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 50vw, 33vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-2-62d568.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Nha xuong QP' ), 'class' => 'about-img about-img-2', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 36vw, 24vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-3-3f8b28.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Du an An Binh 7' ), 'class' => 'about-img about-img-3', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 32vw, 22vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-4-afbd86.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Cong khu cong nghiep An Binh 7' ), 'class' => 'about-img about-img-4', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 32vw, 22vw' ); ?>
					</div>
				</div>
				<div class="intro-body collapsible reveal" id="intro-body">
					<p><?php qp_holdings_te( 'Công ty Cổ phần QP Holdings định hướng phát triển trở thành doanh nghiệp đầu tư đa ngành uy tín tại Việt Nam, tập trung vào các lĩnh vực trọng điểm gồm: Bất động sản Dân Dụng, Bất động sản Công Nghiệp, Đầu tư Tài chính.' ); ?></p>
					<p><?php qp_holdings_te( 'Chúng tôi định vị sự phát triển dựa trên ba nền tảng cốt lõi: Tư duy đầu tư dài hạn và hiệu quả, Quản trị minh bạch và vận hành chuyên nghiệp, Đồng hành hợp tác để cùng phát triển và thịnh vượng.' ); ?></p>
					<p><?php qp_holdings_te( 'QP Holdings tập trung đầu tư các dự án linh hoạt, tối ưu nguồn lực và nâng cao hiệu quả khai thác tài sản trong từng lĩnh vực hoạt động. Đồng thời, Công ty chú trọng mở rộng mạng lưới hợp tác chiến lược với các đối tác trong và ngoài nước nhằm gia tăng năng lực cạnh tranh và tạo ra giá trị thực chất cho thị trường.' ); ?></p>
					<p><?php qp_holdings_te( 'Với định hướng phát triển ổn định và tư duy đổi mới liên tục, QP Holdings từng bước khẳng định vị thế thương hiệu thông qua chất lượng đầu tư, uy tín hợp tác và cam kết đồng hành lâu dài cùng đối tác, khách hàng và nhà đầu tư.' ); ?></p>
				</div>
				<button type="button" class="show-more-btn" data-target="#intro-body" aria-expanded="false" aria-controls="intro-body" aria-label="<?php echo esc_attr( qp_holdings_t( 'Xem thêm giới thiệu QP Holdings' ) ); ?>">
					<span class="show-more-label"><?php echo esc_html( qp_holdings_t( 'Xem thêm' ) ); ?></span>
					<img class="show-more-icon" src="<?php echo esc_url( $qp_assets . '/show-more-icon.svg' ); ?>" width="20" height="20" alt="" aria-hidden="true">
				</button>
			</div>
		</div>
	</section>

	<!-- ═══ STATS / 20 YEARS ═══ -->
	<section class="stats-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'Những con số ấn tượng' ) ); ?>">
		<div class="container">
			<div class="stats-grid">
				<div class="stats-copy">
					<div class="stats-header reveal">
						<div class="stats-eyebrow">
							<img src="<?php echo esc_url( $qp_assets . '/stats-eyebrow-icon.svg' ); ?>" width="24" height="24" alt="" aria-hidden="true">
							<span<?php qp_holdings_te_attrs( 'NHỮNG CON SỐ ẤN TƯỢNG' ); ?>><?php echo esc_html( qp_holdings_t( 'NHỮNG CON SỐ ẤN TƯỢNG' ) ); ?></span>
						</div>
						<h2 class="section-title stats-heading"><span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'QP Holdings' ); ?>><?php echo esc_html( qp_holdings_t( 'QP Holdings' ) ); ?></span></h2>
					</div>
					<div class="stats-text reveal reveal-delay-1">
						<h3 class="stats-subheading"<?php qp_holdings_te_attrs( 'Kiến tạo giá trị, đồng hành thịnh vượng' ); ?>><?php echo esc_html( qp_holdings_t( 'Kiến tạo giá trị, đồng hành thịnh vượng' ) ); ?></h3>
						<div class="stats-body collapsible" id="stats-body">
							<p><?php qp_holdings_te( 'Trải qua gần 20 năm hình thành và phát triển, QP Holdings từng bước khẳng định năng lực trong lĩnh vực đầu tư tài chính, phát triển Bất động sản thương mại và Bất động sản khu công nghiệp với nhiều dự án quy mô tại các khu vực trọng điểm.' ); ?></p>
							<p><?php qp_holdings_te( 'Hàng ngàn sản phẩm đã được phát triển và đưa ra thị trường, góp phần kiến tạo giá trị đầu tư bền vững, nâng cao chất lượng sống và gia tăng hiệu quả khai thác cho khách hàng, đối tác và nhà đầu tư.' ); ?></p>
							<p><?php qp_holdings_te( 'QP Holdings không ngừng mở rộng quỹ đất, nâng cao năng lực quản trị và phát triển hệ sinh thái đầu tư theo định hướng chuyên nghiệp, minh bạch và tăng trưởng dài hạn.' ); ?></p>
						</div>
					</div>
				</div>

				<div class="stats-images reveal reveal-delay-1">
					<div class="stats-collage">
						<?php echo qp_holdings_theme_picture( 'assets/site/about-stats-1-1d0681.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Du an' ), 'class' => 'stats-img stats-img-1', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 70vw, 32vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-stats-2-7afa84.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Tang truong ben vung' ), 'class' => 'stats-img stats-img-2', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 70vw, 32vw' ); ?>
						<div class="stats-decor">
							<span class="stats-decor-number"<?php qp_holdings_te_attrs( '8+' ); ?>><?php echo esc_html( qp_holdings_t( '8+' ) ); ?></span>
						</div>
					</div>
				</div>
				<button type="button" class="show-more-btn stats-show-more" data-target="#stats-body" aria-expanded="false" aria-controls="stats-body" aria-label="<?php echo esc_attr( qp_holdings_t( 'Xem thêm những con số ấn tượng' ) ); ?>">
					<span class="show-more-label"><?php echo esc_html( qp_holdings_t( 'Xem thêm' ) ); ?></span>
					<img class="show-more-icon" src="<?php echo esc_url( $qp_assets . '/show-more-icon.svg' ); ?>" width="20" height="20" alt="" aria-hidden="true">
				</button>
			</div>
		</div>
	</section>

	<!-- ═══ HISTORY / TIMELINE ═══ -->
	<section class="history" aria-label="<?php echo esc_attr( qp_holdings_t( 'Lịch sử hình thành' ) ); ?>">
		<div class="history-bg" aria-hidden="true">
			<?php echo qp_holdings_theme_picture( 'assets/site/about-history-bg.png', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="history-bg-overlay"></div>
		</div>
		<div class="container history-content">
			<div class="section-header center history-header reveal">
				<div class="history-eyebrow">
					<img src="<?php echo esc_url( $qp_assets . '/history-eyebrow-icon.svg' ); ?>" width="24" height="24" alt="" aria-hidden="true">
					<span<?php qp_holdings_te_attrs( 'HÀNH TRÌNH KIẾN TẠO GIÁ TRỊ - ĐỒNG HÀNH CÙNG PHÁT TRIỂN' ); ?>><?php echo esc_html( qp_holdings_t( 'HÀNH TRÌNH KIẾN TẠO GIÁ TRỊ - ĐỒNG HÀNH CÙNG PHÁT TRIỂN' ) ); ?></span>
					<img src="<?php echo esc_url( $qp_assets . '/history-eyebrow-icon.svg' ); ?>" width="24" height="24" alt="" aria-hidden="true" class="history-eyebrow-spacer">
				</div>
				<h2 class="section-title history-title navy-gradient-text"<?php qp_holdings_te_attrs( 'Lịch sử hình thành' ); ?>><?php echo esc_html( qp_holdings_t( 'Lịch sử hình thành' ) ); ?></h2>
			</div>

			<div class="timeline reveal reveal-delay-1" id="history-timeline">
				<img class="timeline-curve" src="<?php echo esc_url( $qp_assets . '/timeline-curve.svg' ); ?>" alt="" aria-hidden="true">

				<?php
				// 'text' holds the Vietnamese key string; it is rendered through
				// qp_holdings_te() so admins can inline-edit (and translate) it,
				// matching the 2024–2026 cards below.
				$qp_timeline_rows = array(
					array(
						array( 'year' => '2007', 'gold' => false, 'text' => 'Tiền thân của QP Holdings được thành lập trong lĩnh vực cơ khí và xây dựng, đặt nền móng cho năng lực vận hành và phát triển doanh nghiệp.' ),
						array( 'year' => '2009', 'gold' => true,  'text' => 'Mở rộng sang lĩnh vực sản xuất, chế biến chè và nông sản với nhà máy công suất 2.000 tấn/năm, từng bước đa dạng hóa hoạt động kinh doanh.' ),
						array( 'year' => '2011', 'gold' => false, 'text' => 'Chuyển đổi sang mô hình công ty cổ phần với tên gọi Công ty Cổ phần Chè Hiệp Khánh, định hướng phát triển trong lĩnh vực chế biến và xuất khẩu chè.' ),
						array( 'year' => '2012', 'gold' => true,  'text' => 'Trở thành thành viên Hiệp hội Chè Việt Nam, mở rộng kết nối thị trường và nâng cao năng lực cạnh tranh trên thị trường trong nước và quốc tế.' ),
					),
					array(
						array( 'year' => '2013', 'gold' => false, 'text' => 'Đạt chứng nhận ISO 22000:2005 cho toàn bộ quy trình sản xuất, khẳng định năng lực quản lý chất lượng và vận hành chuyên nghiệp.' ),
						array( 'year' => '2015', 'gold' => true,  'text' => 'Hoàn thiện nền tảng quản trị doanh nghiệp khi hồ sơ đăng ký công ty đại chúng được Ủy ban Chứng khoán Nhà nước phê duyệt theo Văn bản số 2537/UBCK-QLPH.' ),
						array( 'year' => '2016', 'gold' => false, 'text' => 'Cổ phiếu Công ty chính thức niêm yết trên Sở Giao dịch Chứng khoán Hà Nội với mã chứng khoán HKT, đánh dấu bước phát triển mới về uy tín và năng lực huy động vốn.' ),
						array( 'year' => '2019', 'gold' => true,  'text' => 'Mở rộng hoạt động sang các lĩnh vực nông sản, lâm sản và hợp tác phát triển dịch vụ du lịch cùng các đối tác địa phương.' ),
					),
				);
				foreach ( $qp_timeline_rows as $qp_row ) :
					?>
					<div class="timeline-row">
						<?php foreach ( $qp_row as $qp_item ) :
							$gold_class = $qp_item['gold'] ? ' timeline-card-gold' : '';
							?>
							<article class="timeline-card<?php echo esc_attr( $gold_class ); ?>">
								<span class="timeline-year"<?php qp_holdings_te_attrs( $qp_item['year'] ); ?>><?php echo esc_html( qp_holdings_t( $qp_item['year'] ) ); ?></span>
								<img class="timeline-icon" src="<?php echo esc_url( $qp_assets . '/timeline-icon-' . $qp_item['year'] . '.svg' ); ?>" width="64" height="64" alt="" aria-hidden="true">
								<p><?php qp_holdings_te( $qp_item['text'] ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>

				<div class="timeline-row timeline-row-end">
					<article class="timeline-card">
						<span class="timeline-year"<?php qp_holdings_te_attrs( '2024' ); ?>><?php echo esc_html( qp_holdings_t( '2024' ) ); ?></span>
						<img class="timeline-icon" src="<?php echo esc_url( $qp_assets . '/timeline-icon-2024.svg' ); ?>" width="64" height="64" alt="" aria-hidden="true">
						<p><?php qp_holdings_te( 'Thực hiện tái cấu trúc toàn diện về thương hiệu, mô hình hoạt động và định hướng phát triển trong lĩnh vực Đầu tư tài chính và Bất động sản; chính thức đổi tên thành Công ty Cổ phần Đầu tư QP Xanh.' ); ?></p>
					</article>
					<article class="timeline-card timeline-card-gold">
						<span class="timeline-year"<?php qp_holdings_te_attrs( '2025' ); ?>><?php echo esc_html( qp_holdings_t( '2025' ) ); ?></span>
						<img class="timeline-icon" src="<?php echo esc_url( $qp_assets . '/timeline-icon-2025.svg' ); ?>" width="64" height="64" alt="" aria-hidden="true">
						<p><?php qp_holdings_te( 'Ngày 01/10/2025, QP Holdings tổ chức Lễ khởi công Dự án QP Green Park quy mô hơn 13,7ha tại Phường Bình Cơ TP. Hồ Chí Minh, đánh dấu bước phát triển quan trọng trong lĩnh vực Bất động sản.' ); ?></p>
					</article>
					<article class="timeline-card timeline-card-active">
						<span class="timeline-year"<?php qp_holdings_te_attrs( '2026' ); ?>><?php echo esc_html( qp_holdings_t( '2026' ) ); ?></span>
						<img class="timeline-icon" src="<?php echo esc_url( $qp_assets . '/timeline-icon-2026.svg' ); ?>" width="64" height="64" alt="" aria-hidden="true">
						<p><?php qp_holdings_te( 'Chính thức đổi tên thành Công Ty Cổ Phần QP Holdings, đồng thời đẩy mạnh hợp tác phát triển các dự án căn hộ cao cấp QP Luxia One và QP Luxia Premier tại Phường Bình Dương TP. Hồ Chí Minh với quy mô hơn 2ha và 2.079 căn hộ.' ); ?></p>
					</article>
				</div>
			</div>

			<button type="button" class="show-more-btn timeline-show-more" data-target="#history-timeline" aria-expanded="false" aria-controls="history-timeline" aria-label="<?php echo esc_attr( qp_holdings_t( 'Xem thêm lịch sử hình thành' ) ); ?>">
				<span class="show-more-label"><?php echo esc_html( qp_holdings_t( 'Xem thêm' ) ); ?></span>
				<img class="show-more-icon" src="<?php echo esc_url( $qp_assets . '/show-more-icon.svg' ); ?>" width="20" height="20" alt="" aria-hidden="true">
			</button>
		</div>
	</section>

	<!-- ═══ MISSION / VISION / CORE VALUES ═══ -->
	<section class="mvv" aria-label="<?php echo esc_attr( qp_holdings_t( 'Sứ mệnh, Tầm nhìn, Giá trị cốt lõi' ) ); ?>">
		<div class="container">
			<div class="section-header center mvv-header reveal">
				<div class="mvv-eyebrow">
					<img src="<?php echo esc_url( $qp_assets . '/mvv-eyebrow-icon.svg' ); ?>" width="24" height="24" alt="" aria-hidden="true">
					<span<?php qp_holdings_te_attrs( 'SỨ MỆNH - TẦM NHÌN - GIÁ TRỊ CỐT LÕI' ); ?>><?php echo esc_html( qp_holdings_t( 'SỨ MỆNH - TẦM NHÌN - GIÁ TRỊ CỐT LÕI' ) ); ?></span>
				</div>
				<h2 class="section-title mvv-title">
					<span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'Không ngừng tiên phong' ); ?>><?php echo esc_html( qp_holdings_t( 'Không ngừng tiên phong' ) ); ?> </span><span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'kiến tạo' ); ?>><?php echo esc_html( qp_holdings_t( 'kiến tạo' ) ); ?></span><span class="navy-gradient-text"> <?php echo esc_html( qp_holdings_t( 'giá trị đầu tư,' ) ); ?> </span><span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'tăng trưởng bền vững và thịnh vượng.' ); ?>><?php echo esc_html( qp_holdings_t( 'tăng trưởng bền vững và thịnh vượng.' ) ); ?></span>
				</h2>
			</div>

			<div class="mvv-grid">
				<div class="mvv-intro-wrapper reveal">
					<p class="mvv-intro"><?php qp_holdings_te( 'QP Holdings phát triển theo định hướng đầu tư bền vững, tập trung vào đầu tư tài chính, bất động sản và khu công nghiệp.' ); ?><br><?php qp_holdings_te( 'Doanh nghiệp đề cao năng lực triển khai, hiệu quả đầu tư và giá trị dài hạn.' ); ?></p>
				</div>

				<div class="mvv-cards reveal">
					<article class="mvv-card mvv-card-offset">
						<img class="mvv-card-icon" src="<?php echo esc_url( $qp_assets . '/mvv-icon-mission.svg' ); ?>" width="80" height="80" alt="" aria-hidden="true">
						<div class="mvv-card-body">
							<h3<?php qp_holdings_te_attrs( 'Sứ mệnh' ); ?>><?php echo esc_html( qp_holdings_t( 'Sứ mệnh' ) ); ?></h3>
							<p><?php qp_holdings_te( 'Kiến tạo các giá trị đầu tư hiệu quả thông qua phát triển dự án và mở rộng hệ sinh thái đầu tư. Mang đến lợi ích bền vững cho khách hàng, đối tác, cổ đông và cộng đồng.' ); ?></p>
						</div>
					</article>

					<article class="mvv-card">
						<img class="mvv-card-icon" src="<?php echo esc_url( $qp_assets . '/mvv-icon-vision.svg' ); ?>" width="80" height="80" alt="" aria-hidden="true">
						<div class="mvv-card-body">
							<h3<?php qp_holdings_te_attrs( 'Tầm nhìn' ); ?>><?php echo esc_html( qp_holdings_t( 'Tầm nhìn' ) ); ?></h3>
							<p><?php qp_holdings_te( 'Trở thành doanh nghiệp đầu tư uy tín tại Việt Nam trong lĩnh vực Tài chính, Bất động sản và Khu công nghiệp. Không ngừng nâng cao năng lực quản trị, hiệu quả đầu tư và vị thế thương hiệu.' ); ?></p>
						</div>
					</article>

					<article class="mvv-card mvv-card-offset">
						<img class="mvv-card-icon" src="<?php echo esc_url( $qp_assets . '/mvv-icon-values.svg' ); ?>" width="80" height="80" alt="" aria-hidden="true">
						<div class="mvv-card-body">
							<h3<?php qp_holdings_te_attrs( 'Giá trị cốt lõi' ); ?>><?php echo esc_html( qp_holdings_t( 'Giá trị cốt lõi' ) ); ?></h3>
							<p><?php qp_holdings_te( 'Lấy uy tín và năng lực thực thi làm nền tảng cho mọi hoạt động đầu tư và phát triển. Đề cao minh bạch, hiệu quả hợp tác và giá trị đồng hành dài hạn.' ); ?></p>
						</div>
					</article>
				</div>

				<div class="mvv-images reveal reveal-delay-1" role="img" aria-label="<?php echo esc_attr( qp_holdings_t( 'Hình ảnh QP Holdings' ) ); ?>">
					<div class="mvv-collage">
						<?php echo qp_holdings_theme_picture( 'assets/site/mvv-collage-1.png', array( 'class' => 'mvv-tile mvv-tile-1', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 65vw, 42vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/mvv-collage-2.png', array( 'class' => 'mvv-tile mvv-tile-2', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 34vw, 28vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/mvv-collage-3.png', array( 'class' => 'mvv-tile mvv-tile-3', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 34vw, 28vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/mvv-collage-4.png', array( 'class' => 'mvv-tile mvv-tile-4', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 34vw, 28vw' ); ?>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ═══ CAPABILITY PROFILE CTA ═══ -->
	<?php
	/* ═══ CAPABILITY PROFILE CTA ═══ */
	$qp_profile_url = '';
	if ( function_exists( 'get_field' ) ) {
		$qp_profile_file = get_field( 'capability_profile_file' );
		if ( is_array( $qp_profile_file ) && ! empty( $qp_profile_file['url'] ) ) {
			$qp_profile_url = $qp_profile_file['url'];
		} elseif ( is_string( $qp_profile_file ) && '' !== $qp_profile_file ) {
			$qp_profile_url = $qp_profile_file;
		}
		if ( '' === $qp_profile_url ) {
			$qp_profile_link = get_field( 'capability_profile_url' );
			if ( is_string( $qp_profile_link ) && '' !== $qp_profile_link ) {
				$qp_profile_url = $qp_profile_link;
			}
		}
	}
	$qp_has_profile = ( '' !== $qp_profile_url );
	?>
	<section class="profile-cta" aria-label="<?php echo esc_attr( qp_holdings_t( 'Hồ sơ năng lực' ) ); ?>">
		<div class="container">
			<a class="btn-capability reveal" href="<?php echo $qp_has_profile ? esc_url( $qp_profile_url ) : '#'; ?>"<?php echo $qp_has_profile ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> aria-label="<?php echo esc_attr( qp_holdings_t( 'Xem hồ sơ năng lực QP Holdings' ) ); ?>">
				<span<?php qp_holdings_te_attrs( 'XEM HỒ SƠ NĂNG LỰC' ); ?>><?php echo esc_html( qp_holdings_t( 'XEM HỒ SƠ NĂNG LỰC' ) ); ?></span>
			</a>
		</div>
	</section>

	<!-- ═══ BOARD OF DIRECTORS ═══ -->
	<?php
	if ( function_exists( 'qp_holdings_render_board_section' ) ) {
		echo qp_holdings_render_board_section(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<!-- ═══ PARTNERS ═══ -->
	<section class="partners about-partners" aria-label="<?php echo esc_attr( qp_holdings_t( 'Đối tác của chúng tôi' ) ); ?>">
		<div class="container">
			<div class="section-header center reveal">
				<h2 class="section-title navy-gradient-text"<?php qp_holdings_te_attrs( 'Đối tác của chúng tôi' ); ?>><?php echo esc_html( qp_holdings_t( 'Đối tác của chúng tôi' ) ); ?></h2>
			</div>
			<div class="partners-swiper swiper reveal reveal-delay-1" aria-label="<?php echo esc_attr( qp_holdings_t( 'Danh sách đối tác' ) ); ?>">
				<div class="swiper-wrapper partners-row">
					<?php
					echo function_exists( 'qp_holdings_render_partners_slides' )
						? qp_holdings_render_partners_slides() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						: '';
					?>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
