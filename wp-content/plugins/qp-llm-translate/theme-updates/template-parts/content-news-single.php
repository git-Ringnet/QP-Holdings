<?php
/**
 * News detail body template. Uses shared header.php / footer.php.
 */

defined( 'ABSPATH' ) || exit;

$args = isset( $args ) && is_array( $args ) ? $args : array();
$args = wp_parse_args(
	$args,
	array(
		'crumb_label'       => qp_holdings_t( 'Tin tức' ),
		'back_label'        => qp_holdings_t( 'Về danh sách' ),
		'back_url'          => function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ),
		'related_title'     => qp_holdings_t( 'Bài viết liên quan' ),
		'related_post_type' => 'qp_news',
		'related_taxonomy'  => 'qp_news_category',
	)
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'single-news-detail';
		return $classes;
	}
);

get_header();

if ( ! have_posts() ) :
	?>
	<main id="main" class="news-detail-page">
		<div class="container">
			<div class="news-detail-empty">
				<p class="news-detail-eyebrow">QP Holdings</p>
				<h1><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_not_found_title' ) : 'Không tìm thấy nội dung' ); ?></h1>
				<p><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_not_found_desc' ) : 'Bài viết này hiện không còn hoặc đã được di chuyển.' ); ?></p>
				<a class="btn-gold-pill" href="<?php echo esc_url( $args['back_url'] ); ?>"><?php echo esc_html( $args['back_label'] ); ?></a>
			</div>
		</div>
	</main>
	<?php
	get_footer();
	return;
endif;

while ( have_posts() ) :
	the_post();

	$post_id    = (int) get_the_ID();
	$title      = get_the_title( $post_id );
	$taxonomy   = (string) $args['related_taxonomy'];
	$date_iso   = get_the_date( 'Y-m-d', $post_id );
	$lang       = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$date_label = 'vi' === $lang ? get_the_date( 'd/m/Y', $post_id ) : get_the_date( 'M j, Y', $post_id );
	$post_obj   = get_post( $post_id );
	$excerpt    = ( $post_obj instanceof WP_Post && ! empty( $post_obj->post_excerpt ) ) ? trim( (string) $post_obj->post_excerpt ) : '';
	$hero_image = function_exists( 'qp_holdings_news_image_url' )
		? qp_holdings_news_image_url( $post_id, 'full' )
		: ( get_the_post_thumbnail_url( $post_id, 'full' ) ?: get_template_directory_uri() . '/assets/site/news-fallback-logo.png' );
	$custom_cat = trim( (string) get_post_meta( $post_id, 'category_info', true ) );
	if ( '' === $custom_cat && function_exists( 'get_field' ) ) {
		$custom_cat = trim( (string) get_field( 'category_info', $post_id ) );
	}
	if ( '' !== $custom_cat ) {
		$term_label = $custom_cat;
	} else {
		$term_label = function_exists( 'qp_holdings_news_group_label' )
			? qp_holdings_news_group_label( $post_id, $taxonomy )
			: ( function_exists( 'qp_holdings_news_category_label' )
				? qp_holdings_news_category_label( $post_id, $taxonomy )
				: ( function_exists( 'qp_holdings_first_term_name' ) ? qp_holdings_first_term_name( $post_id, $taxonomy ) : $args['crumb_label'] ) );
	}
	if ( 'vi' !== $lang && function_exists( 'qp_holdings_t' ) ) {
		$term_label = qp_holdings_t( $term_label );
	}
	$term_url   = function_exists( 'qp_holdings_news_group_url' )
		? qp_holdings_news_group_url( $post_id, $taxonomy )
		: ( function_exists( 'qp_holdings_news_category_url' ) ? qp_holdings_news_category_url( $post_id, $taxonomy ) : $args['back_url'] );
	$term       = function_exists( 'qp_holdings_news_term' ) ? qp_holdings_news_term( $post_id, $taxonomy ) : null;

	// Author name resolution: Custom field > Post author > Default "Ban Truyền thông"
	$author_name = trim( (string) get_post_meta( $post_id, 'author_name', true ) );
	if ( '' === $author_name && function_exists( 'get_field' ) ) {
		$author_name = trim( (string) get_field( 'author_name', $post_id ) );
	}
	if ( '' === $author_name && $post_obj instanceof WP_Post && ! empty( $post_obj->post_author ) ) {
		$author_display = trim( (string) get_the_author_meta( 'display_name', (int) $post_obj->post_author ) );
		if ( '' !== $author_display && ! in_array( strtolower( $author_display ), array( 'admin', 'administrator', 'root' ), true ) ) {
			$author_name = $author_display;
		}
	}
	if ( '' === $author_name ) {
		$author_name = 'Ban Truyền thông';
	}
	if ( 'vi' !== $lang && function_exists( 'qp_holdings_t' ) ) {
		$author_name = qp_holdings_t( $author_name );
	}

	// Publication date & time formatted: Custom field > dd-mm-yyyy - hh:mm AM/PM
	$custom_datetime = trim( (string) get_post_meta( $post_id, 'custom_datetime', true ) );
	if ( '' === $custom_datetime && function_exists( 'get_field' ) ) {
		$custom_datetime = trim( (string) get_field( 'custom_datetime', $post_id ) );
	}
	if ( '' !== $custom_datetime ) {
		$post_datetime_label = $custom_datetime;
		if ( 'en' === $lang ) {
			$post_datetime_label = str_ireplace( array( 'Sáng', 'Chiều', 'Tối', 'ngày ', 'tháng ', 'năm ' ), array( 'AM', 'PM', 'PM', '', '/', '' ), $post_datetime_label );
		} elseif ( 'zh' === $lang ) {
			$post_datetime_label = str_ireplace( array( 'Sáng', 'Chiều', 'Tối', 'AM', 'PM' ), array( '上午', '下午', '晚上', '上午', '下午' ), $post_datetime_label );
		}
	} else {
		$post_timestamp = strtotime( $post_obj->post_date ?? 'now' );
		$post_date_formatted = date( 'd-m-Y', $post_timestamp );
		if ( 'en' === $lang ) {
			$post_time_formatted = date( 'h:i A', $post_timestamp );
			$post_datetime_label = $post_date_formatted . ' - ' . $post_time_formatted;
		} elseif ( 'zh' === $lang ) {
			$hour = (int) date( 'H', $post_timestamp );
			$ampm_zh = ( $hour < 12 ) ? '上午' : '下午';
			$post_time_formatted = $ampm_zh . ' ' . date( 'h:i', $post_timestamp );
			$post_datetime_label = $post_date_formatted . ' - ' . $post_time_formatted;
		} else {
			$post_time_formatted = get_the_time( 'h:i A', $post_id );
			$post_datetime_label = $post_date_formatted . ' - ' . $post_time_formatted;
		}
	}

	// Facebook share URL & label
	$share_url    = get_permalink( $post_id );
	$fb_share_url = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $share_url );
	$share_label  = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news_share', 'Share', '分享' ) : ( 'en' === $lang ? 'Share' : ( 'zh' === $lang ? '分享' : 'Chia sẻ' ) );
	$share_aria   = 'en' === $lang ? 'Share on Facebook' : ( 'zh' === $lang ? '分享到Facebook' : 'Chia sẻ lên Facebook' );

	$related_base_args = array(
		'post_type'           => (string) $args['related_post_type'],
		'post_status'         => 'publish',
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);
	if ( $term instanceof WP_Term && taxonomy_exists( $taxonomy ) ) {
		$related_base_args['tax_query'] = array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array( (int) $term->term_id ),
			),
		);
	}
	$related_base_args = function_exists( 'qp_holdings_add_polylang_query_lang' ) ? qp_holdings_add_polylang_query_lang( $related_base_args ) : $related_base_args;

	$related_query_args                   = $related_base_args;
	$related_query_args['posts_per_page'] = 3;
	$related_query                        = new WP_Query( $related_query_args );
	?>

	<style>
		.news-detail-meta-bar {
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 12px 16px;
			margin-top: 14px;
			margin-bottom: 22px;
			padding-bottom: 14px;
			border-bottom: 1px solid #eef2f6;
			font-size: 13.5px;
			line-height: 1.5;
			color: #556987;
			font-family: inherit;
		}
		.news-detail-meta-info {
			display: inline-flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 6px 8px;
			color: #556987;
			font-size: 13.5px;
			font-weight: 400;
		}
		.news-detail-meta-author {
			font-weight: 600;
			color: #2c3e50;
		}
		.news-detail-meta-sep {
			color: #cbd5e1;
			user-select: none;
			font-weight: 300;
		}
		.news-detail-meta-datetime {
			color: #556987;
			white-space: nowrap;
		}
		.news-detail-meta-category {
			color: #0b1b3d;
			font-weight: 500;
			text-decoration: none;
			transition: color 0.2s ease;
		}
		.news-detail-meta-category:hover {
			color: #c5a880;
			text-decoration: underline;
		}
		.news-detail-meta-actions {
			display: inline-flex;
			align-items: center;
			flex-shrink: 0;
		}
		.news-share-fb-btn {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 5px 12px;
			background-color: #1877f2;
			color: #ffffff !important;
			font-size: 12.5px;
			font-weight: 600;
			line-height: 1.2;
			border-radius: 4px;
			text-decoration: none !important;
			box-shadow: 0 1px 3px rgba(24, 119, 242, 0.25);
			transition: background-color 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
			cursor: pointer;
		}
		.news-share-fb-btn:hover {
			background-color: #166fe5;
			box-shadow: 0 3px 8px rgba(24, 119, 242, 0.35);
			transform: translateY(-1px);
			color: #ffffff !important;
		}
		.news-share-fb-btn:active {
			transform: translateY(0);
		}
		.news-share-fb-icon {
			width: 13px;
			height: 13px;
			fill: currentColor;
			flex-shrink: 0;
		}
		@media (max-width: 640px) {
			.news-detail-meta-bar {
				flex-direction: row;
				justify-content: space-between;
				align-items: center;
				gap: 10px;
				margin-top: 10px;
				margin-bottom: 18px;
				padding-bottom: 12px;
				font-size: 12px;
			}
			.news-detail-meta-info {
				font-size: 12px;
				gap: 4px 6px;
			}
			.news-share-fb-btn {
				padding: 4px 9px;
				font-size: 11.5px;
			}
		}
	</style>

	<main id="main" class="news-detail-page">
		<div class="container">
			<nav class="page-breadcrumb news-detail-breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'home' ) : home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<a href="<?php echo esc_url( $args['back_url'] ); ?>" class="page-breadcrumb-link"><?php echo esc_html( $args['crumb_label'] ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<a href="<?php echo esc_url( $term_url ); ?>" class="page-breadcrumb-link"><?php echo esc_html( $term_label ); ?></a>
				<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
					<path d="M7.5 4.167L13.333 10 7.5 15.833" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
				<span class="page-breadcrumb-current"><?php echo esc_html( $title ); ?></span>
			</nav>

			<div class="news-detail-layout">
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'news-detail-article' ); ?>>
					<header class="news-detail-header">
						<h1 class="news-detail-title"><?php echo esc_html( $title ); ?></h1>

						<div class="news-detail-meta-bar">
							<div class="news-detail-meta-info">
								<span class="news-detail-meta-author"><?php echo esc_html( $author_name ); ?></span>
								<span class="news-detail-meta-sep" aria-hidden="true">|</span>
								<time class="news-detail-meta-datetime" datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $post_datetime_label ); ?></time>
								<?php if ( ! empty( $term_label ) ) : ?>
									<span class="news-detail-meta-sep" aria-hidden="true">|</span>
									<a href="<?php echo esc_url( $term_url ); ?>" class="news-detail-meta-category"><?php echo esc_html( $term_label ); ?></a>
								<?php endif; ?>
							</div>
							<div class="news-detail-meta-actions">
								<a href="<?php echo esc_url( $fb_share_url ); ?>" 
								   target="_blank" 
								   rel="noopener noreferrer" 
								   class="news-share-fb-btn" 
								   onclick="window.open(this.href, 'fb-share-dialog', 'width=626,height=436,location=no,menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no'); return false;"
								   title="<?php echo esc_attr( $share_label ); ?>"
								   aria-label="<?php echo esc_attr( $share_aria ); ?>">
									<svg class="news-share-fb-icon" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
										<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
									</svg>
									<span class="news-share-fb-text"><?php echo esc_html( $share_label ); ?></span>
								</a>
							</div>
						</div>
						<?php
						$can_edit_lede = function_exists( 'qp_holdings_can_inline_edit' )
							&& qp_holdings_can_inline_edit()
							&& current_user_can( 'edit_post', $post_id );
						if ( $excerpt || $can_edit_lede ) :
							$lede_attrs = '';
							if ( $can_edit_lede ) {
								$lede_attrs  = ' data-qp-editable="1" data-qp-multiline="1"';
								$lede_attrs .= ' data-qp-post-id="' . (int) $post_id . '"';
								$lede_attrs .= ' data-qp-post-field="excerpt"';
							}
							?>
							<p class="news-detail-lede"<?php echo $lede_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attrs built from ints/literals. ?>><?php echo esc_html( $excerpt ); ?></p>
						<?php endif; ?>
					</header>

					<?php if ( $hero_image && ! qp_holdings_news_content_starts_with_image( $post_id ) ) : ?>
					<img class="news-detail-hero-image" src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="eager">
					<?php endif; ?>

					<div class="news-detail-content">
						<?php
						the_content();
						wp_link_pages(
							array(
								'before' => '<div class="news-detail-page-links">',
								'after'  => '</div>',
							)
						);
						?>
					</div>

					<?php
					$news_source = trim( (string) get_post_meta( $post_id, 'source', true ) );
					if ( '' === $news_source ) {
						$news_source = trim( (string) get_post_meta( $post_id, 'news_source', true ) );
					}
					if ( '' === $news_source && function_exists( 'get_field' ) ) {
						$news_source = trim( (string) get_field( 'source', $post_id ) );
					}
					if ( '' === $news_source && function_exists( 'get_field' ) ) {
						$news_source = trim( (string) get_field( 'news_source', $post_id ) );
					}
					?>
					<div class="news-detail-contact-box">
						<h4 class="news-detail-contact-title"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'QP HOLDINGS – NHÀ PHÁT TRIỂN BẤT ĐỘNG SẢN', 'QP HOLDINGS – REAL ESTATE DEVELOPER', 'QP HOLDINGS – 房地产开发商' ) : 'QP HOLDINGS – NHÀ PHÁT TRIỂN BẤT ĐỘNG SẢN' ); ?></h4>
						<div class="news-detail-contact-list">
							<p class="news-detail-contact-item">
								<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Địa chỉ:', 'Address:', '地址：' ) : 'Địa chỉ:' ); ?></span>
								<span><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, P. Sài Gòn, TP. Hồ Chí Minh', 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City', 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市' ) : 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, P. Sài Gòn, TP. Hồ Chí Minh' ); ?></span>
							</p>
							<div class="news-detail-contact-grid">
								<div class="news-detail-contact-col">
									<p class="news-detail-contact-item">
										<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Website:', 'Website:', '官方网站：' ) : 'Website:' ); ?></span>
										<a href="https://qpholdings.vn" target="_blank" rel="noopener noreferrer">qpholdings.vn</a>
									</p>
									<p class="news-detail-contact-item">
										<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Email:', 'Email:', '电子邮箱：' ) : 'Email:' ); ?></span>
										<a href="mailto:info@qpholdings.vn">info@qpholdings.vn</a>
									</p>
								</div>
								<div class="news-detail-contact-col">
									<p class="news-detail-contact-item">
										<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Hotline:', 'Hotline:', '服务热线：' ) : 'Hotline:' ); ?></span>
										<a href="tel:19000066"><strong>1900 0066</strong></a>
									</p>
									<p class="news-detail-contact-item">
										<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Mã chứng khoán:', 'Stock ticker:', '证券代码：' ) : 'Mã chứng khoán:' ); ?></span>
										<strong>HKT</strong>
									</p>
								</div>
							</div>
							<p class="news-detail-contact-item">
								<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Fanpage FB:', 'Fanpage FB:', 'Facebook 官方专页：' ) : 'Fanpage FB:' ); ?></span>
								<a href="https://www.facebook.com/qpholdings.vn" target="_blank" rel="noopener noreferrer">https://www.facebook.com/qpholdings.vn</a>
							</p>
							<?php if ( '' !== $news_source ) : ?>
								<p class="news-detail-contact-item news-detail-contact-source">
									<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Nguồn số liệu tham khảo:', 'Reference sources:', '参考数据来源：' ) : 'Nguồn số liệu tham khảo:' ); ?></span>
									<span><?php echo esc_html( $news_source ); ?></span>
								</p>
							<?php endif; ?>
							<p class="news-detail-contact-item news-detail-contact-editor">
								<span class="news-detail-contact-label"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Tổng hợp và biên soạn nội dung:', 'Compiled & edited by:', '内容编辑与整理：' ) : 'Tổng hợp và biên soạn nội dung:' ); ?></span>
								<strong><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'Ban Truyền thông - QP Holdings', 'Communications Department - QP Holdings', '传媒部 - QP Holdings' ) : 'Ban Truyền thông - QP Holdings' ); ?></strong>
							</p>
						</div>
					</div>

					<footer class="news-detail-meta-footer">
						<span class="figma-card-sep" aria-hidden="true"></span>
						<time class="figma-card-date" datetime="<?php echo esc_attr( $date_iso ); ?>"><?php echo esc_html( $date_label ); ?></time>
					</footer>
				</article>
			</div>

			<?php if ( $related_query->have_posts() ) : ?>
				<section class="news-detail-related" aria-label="<?php echo esc_attr( $args['related_title'] ); ?>">
					<div class="news-cat-grid-row news-detail-related-grid">
						<?php
						while ( $related_query->have_posts() ) :
							$related_query->the_post();
							echo qp_holdings_figma_vertical_card( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
			<?php endif; ?>
		</div>
	</main>

	<?php
endwhile;

get_footer();
