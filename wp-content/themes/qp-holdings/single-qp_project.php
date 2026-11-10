<?php
/**
 * Single template: qp_project.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id     = get_the_ID();
	$title       = get_the_title();

	// Helper closure: returns translated value if available, otherwise the raw meta/ACF value.
	$_pt = function( string $field, string $fallback ) use ( $post_id ): string {
		if ( function_exists( 'qp_holdings_seeded_project_translation_for_post' ) ) {
			return qp_holdings_seeded_project_translation_for_post( $post_id, $field, $fallback );
		}
		return $fallback;
	};

	$_raw_cat    = qp_holdings_acf_or_meta( $post_id, 'category_info', 'category_info' )
		?: qp_holdings_first_term_name( $post_id, 'qp_project_type' );
	$cat         = $_pt( 'category', (string) $_raw_cat );
	$status_name = qp_holdings_first_term_name( $post_id, 'qp_project_status' );
	$loc_name    = qp_holdings_first_term_name( $post_id, 'qp_project_location' );
	$subtitle    = $_pt( 'subtitle',    (string) qp_holdings_acf_or_meta( $post_id, 'subtitle', 'subtitle' ) );
	$address     = $_pt( 'address',     (string) qp_holdings_acf_or_meta( $post_id, 'address', 'address' ) );
	$scale       = $_pt( 'scale',       (string) qp_holdings_acf_or_meta( $post_id, 'scale', 'scale' ) );
	$area_total  = $_pt( 'area_total',  (string) qp_holdings_acf_or_meta( $post_id, 'area_total', 'area_total' ) );
	$investor    = $_pt( 'investor',    (string) qp_holdings_acf_or_meta( $post_id, 'investor_name', 'investor_name' ) );
	$developer   = $_pt( 'developer',   (string) qp_holdings_acf_or_meta( $post_id, 'developer', 'developer' ) );
	$completion  = $_pt( 'completion',  (string) qp_holdings_acf_or_meta( $post_id, 'completion_year', 'completion_year' ) );
	$handover    = $_pt( 'handover',    (string) qp_holdings_acf_or_meta( $post_id, 'handover', 'handover' ) );
	$_raw_hl     = (string) qp_holdings_acf_or_meta( $post_id, 'highlights', 'highlights' );
	$highlights  = $_pt( 'highlights',  $_raw_hl );
	$display_title = $_pt( 'display_title', (string) qp_holdings_acf_or_meta( $post_id, 'display_title', 'display_title' ) );
	if ( '' === trim( $display_title ) ) {
		$upper_title    = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $title ) : strtoupper( $title );
		$upper_subtitle = $subtitle ? ( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $subtitle ) : strtoupper( $subtitle ) ) : '';
		$display_title  = $upper_subtitle ? $upper_title . ': ' . $upper_subtitle : $upper_title;
	}
	$img         = qp_holdings_acf_or_meta( $post_id, 'card_image', 'card_image' );
	$img_url     = qp_holdings_image_url( $img, $post_id, 'full' );
	$banner      = qp_holdings_acf_or_meta( $post_id, 'banner_image', 'banner_image' );
	$banner_url  = qp_holdings_image_url( $banner, $post_id, 'full' );
	$gallery     = qp_holdings_acf_or_meta( $post_id, 'gallery', 'gallery' );
	$qp_assets   = get_template_directory_uri() . '/assets/site';

	// Hero highlights (new design): repeater preferred, textarea fallback.
	$hero_heading = $_pt( 'hero_heading', (string) qp_holdings_acf_or_meta( $post_id, 'hero_heading', 'hero_heading' ) );
	if ( '' === trim( $hero_heading ) ) {
		$hero_heading = qp_holdings_t( 'project_hero_default_heading' );
	}
	$hero_image     = qp_holdings_acf_or_meta( $post_id, 'hero_image', 'hero_image' );
	$hero_image_url = qp_holdings_image_url( $hero_image, $post_id, 'full' );
	$hero_cta_label = (string) qp_holdings_acf_or_meta( $post_id, 'hero_cta_label', 'hero_cta_label' );
	$hero_cta_url   = (string) qp_holdings_acf_or_meta( $post_id, 'hero_cta_url', 'hero_cta_url' );

	$hero_highlights = array();
	if ( function_exists( 'get_field' ) ) {
		$repeater = get_field( 'hero_highlights', $post_id );
		if ( is_array( $repeater ) ) {
			foreach ( $repeater as $row ) {
				$h_title = isset( $row['highlight_title'] ) ? trim( (string) $row['highlight_title'] ) : '';
				$h_short = isset( $row['highlight_short_description'] ) ? trim( (string) $row['highlight_short_description'] ) : '';
				$h_body  = isset( $row['highlight_body'] ) ? trim( (string) $row['highlight_body'] ) : '';
				if ( '' !== $h_short ) {
					$h_body = $h_short;
				}
				if ( '' !== $h_title || '' !== $h_body ) {
					$hero_highlights[] = array( 'title' => $h_title, 'body' => $h_body );
				}
			}
		}
	}
	// Fallback: indexed post meta written by the custom highlights meta box
	// (ACF free has no repeater field type).
	if ( ! $hero_highlights ) {
		$hl_count = (int) get_post_meta( $post_id, 'hero_highlights', true );
		for ( $i = 0; $i < $hl_count; $i++ ) {
			$prefix  = 'hero_highlights_' . $i . '_';
			$h_title = trim( (string) get_post_meta( $post_id, $prefix . 'highlight_title', true ) );
			$h_short = trim( (string) get_post_meta( $post_id, $prefix . 'highlight_short_description', true ) );
			$h_body  = trim( (string) get_post_meta( $post_id, $prefix . 'highlight_body', true ) );
			if ( '' !== $h_short ) {
				$h_body = $h_short;
			}
			if ( '' !== $h_title || '' !== $h_body ) {
				$hero_highlights[] = array( 'title' => $h_title, 'body' => $h_body );
			}
		}
	}
?>

<main id="main" class="qp-project-single qp-project-detail-page">

	<section class="page-hero qp-project-detail-hero" aria-label="<?php echo esc_attr( $title ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php if ( $banner_url ) : ?>
				<img src="<?php echo esc_url( $banner_url ); ?>" alt="" loading="eager" fetchpriority="high" decoding="async">
			<?php else : ?>
				<?php echo qp_holdings_theme_picture( 'assets/site/project-hero-bg.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<?php endif; ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'home' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'projects' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'projects' ) ); ?></a>
				<img src="<?php echo esc_url( $qp_assets . '/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( $title ); ?></span>
			</nav>
		</div>
		<div class="container page-hero-content page-hero-content--centered">
			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $subtitle ) : ?>
				<p class="page-hero-desc reveal reveal-delay-2"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php
	// "Quy mô dự án" stat-card section (Figma redesign).
	if ( function_exists( 'qp_holdings_render_project_scale_section' ) ) {
		echo qp_holdings_render_project_scale_section( (int) $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>

	<?php if ( $hero_highlights || $hero_image_url || trim( (string) $highlights ) !== '' ) : ?>
	<section class="qp-project-hero-block" aria-label="<?php echo esc_attr( wp_strip_all_tags( $hero_heading ) ); ?>">
		<div class="container">
			<div class="qp-project-hero-block__inner">
				<?php if ( $hero_image_url ) : ?>
					<div class="qp-project-hero-block__media reveal">
						<img src="<?php echo esc_url( $hero_image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy" decoding="async">
					</div>
				<?php endif; ?>
				<div class="qp-project-hero-block__content">
					<?php if ( trim( (string) $hero_heading ) !== '' ) : ?>
						<?php $hero_heading_html = implode( '<br>', array_map( 'esc_html', preg_split( '/\R/', $hero_heading ) ) ); ?>
						<h2 class="qp-project-hero-block__heading reveal"><?php echo $hero_heading_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
					<?php endif; ?>

					<?php if ( $hero_highlights ) : ?>
						<div class="qp-project-hero-block__list">
							<?php foreach ( $hero_highlights as $i => $hl ) : ?>
								<div class="qp-project-hl reveal">
									<span class="qp-project-hl__num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
									<div class="qp-project-hl__text">
										<?php if ( '' !== $hl['title'] ) : ?>
											<h3 class="qp-project-hl__title"><?php echo esc_html( $hl['title'] ); ?></h3>
										<?php endif; ?>
										<?php if ( '' !== $hl['body'] ) : ?>
											<p class="qp-project-hl__body"><?php echo esc_html( $hl['body'] ); ?></p>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php else : ?>
						<?php
						$lines = array_filter( array_map( 'trim', explode( "\n", (string) $highlights ) ) );
						$fallback_short_description = trim( wp_strip_all_tags( get_the_excerpt( $post_id ) ) );
						if ( '' === $fallback_short_description ) {
							$fallback_short_description = trim( (string) $subtitle );
						}
						if ( $lines ) :
							?>
							<div class="qp-project-hero-block__list">
								<?php foreach ( $lines as $i => $line ) : ?>
									<div class="qp-project-hl reveal">
										<span class="qp-project-hl__num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
										<div class="qp-project-hl__text">
											<h3 class="qp-project-hl__title"><?php echo esc_html( $line ); ?></h3>
											<?php if ( '' !== $fallback_short_description ) : ?>
												<p class="qp-project-hl__body"><?php echo esc_html( $fallback_short_description ); ?></p>
											<?php endif; ?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							<?php
						endif;
						?>
					<?php endif; ?>

					<?php $cta_url = '' !== trim( $hero_cta_url ) ? $hero_cta_url : '#consult-form'; ?>
					<a class="qp-project-hero-block__cta" href="<?php echo esc_url( $cta_url ); ?>">
						<span><?php echo esc_html( $hero_cta_label ?: qp_holdings_t( 'view_project_detail' ) ); ?></span>
						<img src="<?php echo esc_url( $qp_assets . '/project-card-arrow.svg' ); ?>" alt="" width="24" height="24" aria-hidden="true">
					</a>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php
	$gallery_urls = array();
	$add_gallery_url = static function ( $url ) use ( &$gallery_urls ): void {
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( '' !== $url && ! in_array( $url, $gallery_urls, true ) ) {
			$gallery_urls[] = $url;
		}
	};
	if ( is_array( $gallery ) && ! empty( $gallery ) ) {
		foreach ( $gallery as $g ) {
			$gurl = is_array( $g ) && ! empty( $g['url'] ) ? $g['url'] : ( is_numeric( $g ) ? wp_get_attachment_image_url( (int) $g, 'full' ) : ( is_string( $g ) ? $g : '' ) );
			$add_gallery_url( $gurl );
		}
	}
	if ( ! $gallery_urls ) {
		$add_gallery_url( $hero_image_url );
		$add_gallery_url( $img_url );

		$fallback_gallery_args = array(
			'post_type'      => 'qp_project',
			'post_status'    => 'publish',
			'posts_per_page' => 8,
			'post__not_in'   => array( $post_id ),
			'no_found_rows'  => true,
		);
		$fallback_gallery_posts = function_exists( 'qp_holdings_wp_query_with_language_fallback' )
			? qp_holdings_wp_query_with_language_fallback( $fallback_gallery_args )->posts
			: get_posts( $fallback_gallery_args );
		foreach ( $fallback_gallery_posts as $fallback_gallery_post ) {
			if ( ! $fallback_gallery_post instanceof WP_Post ) {
				continue;
			}
			$fallback_img = qp_holdings_acf_or_meta( $fallback_gallery_post->ID, 'card_image', 'card_image' );
			$add_gallery_url( qp_holdings_image_url( $fallback_img, $fallback_gallery_post->ID, 'full' ) );
			if ( count( $gallery_urls ) >= 6 ) {
				break;
			}
		}
	}
	if ( $gallery_urls && count( $gallery_urls ) < 3 ) {
		$source_gallery_urls = $gallery_urls;
		while ( count( $gallery_urls ) < 3 ) {
			$gallery_urls[] = $source_gallery_urls[ count( $gallery_urls ) % count( $source_gallery_urls ) ];
		}
	}
	if ( $gallery_urls ) :
		?>
		<section class="qp-project-gallery" id="project-gallery" aria-label="<?php echo esc_attr( qp_holdings_t( 'gallery' ) ); ?>">
			<div class="container">
				<h2 class="qp-project-gallery__heading"><?php echo esc_html( qp_holdings_t( 'gallery' ) ); ?></h2>
				<div class="qp-project-gallery__swiper swiper" data-project-gallery>
					<div class="swiper-wrapper">
						<?php foreach ( $gallery_urls as $gi => $gurl ) : ?>
							<div class="swiper-slide">
								<a class="qp-project-gallery__item" href="<?php echo esc_url( $gurl ); ?>" data-gallery-lightbox data-index="<?php echo esc_attr( $gi ); ?>">
									<img src="<?php echo esc_url( $gurl ); ?>" alt="<?php echo esc_attr( $title . ' - ' . ( $gi + 1 ) ); ?>" loading="lazy" decoding="async">
								</a>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="swiper-pagination qp-project-gallery__pagination"></div>
					<button type="button" class="swiper-button-prev qp-project-gallery__nav" aria-label="<?php echo esc_attr( qp_holdings_t( 'prev' ) ); ?>"></button>
					<button type="button" class="swiper-button-next qp-project-gallery__nav" aria-label="<?php echo esc_attr( qp_holdings_t( 'next' ) ); ?>"></button>
				</div>
			</div>
		</section>
		<?php
	endif;
	?>

	<?php
	$consult_status  = isset( $_GET['consult'] ) ? sanitize_key( wp_unslash( $_GET['consult'] ) ) : '';
	$consult_messages = array(
		'sent'    => qp_holdings_t( 'consult_sent' ),
		'invalid' => qp_holdings_t( 'consult_invalid' ),
		'error'   => qp_holdings_t( 'consult_error' ),
	);
	$consult_message  = $consult_messages[ $consult_status ] ?? '';
	?>
	<section class="qp-project-consult" aria-label="<?php echo esc_attr( qp_holdings_t( 'consult_title' ) ); ?>" id="consult-form">
		<div class="qp-project-consult__inner">
			<?php echo qp_holdings_theme_picture( 'assets/site/project-consultant-21e48d.png', array( 'class' => 'qp-project-consult__image', 'alt' => '', 'aria-hidden' => 'true', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 80vw, 40vw' ); ?>
			<form class="qp-project-consult__form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<?php wp_nonce_field( 'qp_consult_form_' . $post_id, 'qp_consult_nonce' ); ?>
				<input type="hidden" name="action" value="qp_consult_submit">
				<input type="hidden" name="project_id" value="<?php echo esc_attr( $post_id ); ?>">
				<input type="text" name="website" class="qp-project-consult__hp" tabindex="-1" autocomplete="off" aria-hidden="true">
				<h2><?php echo esc_html( qp_holdings_t( 'consult_title' ) ); ?></h2>
				<?php if ( $consult_message ) : ?>
					<p class="qp-project-consult__notice qp-project-consult__notice--<?php echo 'sent' === $consult_status ? 'success' : 'error'; ?>"><?php echo esc_html( $consult_message ); ?></p>
				<?php endif; ?>
				<div class="qp-project-consult__fields">
					<label>
						<span><?php echo esc_html( qp_holdings_t( 'full_name' ) ); ?> <b aria-hidden="true">*</b></span>
						<input type="text" name="full_name" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_name' ) ); ?>" autocomplete="name" required>
					</label>
					<label>
						<span><?php echo esc_html( qp_holdings_t( 'phone_number' ) ); ?> <b aria-hidden="true">*</b></span>
						<div class="qp-project-consult__phone qp-phone-input-wrap">
							<?php echo qp_holdings_phone_country_select( 'phone_country' ); ?>
							<input type="tel" name="phone" class="qp-project-consult__phone-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_phone' ) ); ?>" autocomplete="tel-national" required>
						</div>
					</label>
					<label>
						<span><?php echo esc_html( qp_holdings_t( 'project_type' ) ); ?> <b aria-hidden="true">*</b></span>
						<select name="project_interest" required>
							<option value=""><?php echo esc_html( qp_holdings_t( 'select_project_interest' ) ); ?></option>
							<?php
							$consult_args = array(
								'post_type'      => 'qp_project',
								'post_status'    => 'publish',
								'posts_per_page' => -1,
								'orderby'        => 'title',
								'order'          => 'ASC',
							);
							$consult_query = function_exists( 'qp_holdings_wp_query_with_language_fallback' )
								? qp_holdings_wp_query_with_language_fallback( $consult_args )
								: new WP_Query( $consult_args );
							$consult_projects = $consult_query->posts;
							foreach ( $consult_projects as $cp ) :
								$cp_title = get_the_title( $cp );
								?>
								<option value="<?php echo esc_attr( $cp_title ); ?>" <?php selected( $cp->ID, $post_id ); ?>><?php echo esc_html( $cp_title ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
				<?php echo qp_holdings_turnstile_render(); ?>
			<button type="submit"><?php echo esc_html( qp_holdings_t( 'submit' ) ); ?></button>
			</form>
		</div>
	</section>

	<?php
	$related_terms = wp_get_post_terms( $post_id, 'qp_project_type', array( 'fields' => 'ids' ) );
	$related_posts = array();
	$exclude_ids   = array( $post_id );

	$collect_related = static function ( array $args ) use ( &$related_posts, &$exclude_ids ): void {
		$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' )
			? qp_holdings_wp_query_with_language_fallback( $args )
			: new WP_Query( $args );
		foreach ( $q->posts as $project ) {
			if ( $project instanceof WP_Post && ! in_array( (int) $project->ID, $exclude_ids, true ) ) {
				$related_posts[] = $project;
				$exclude_ids[]   = (int) $project->ID;
			}
			if ( count( $related_posts ) >= 3 ) {
				break;
			}
		}
		wp_reset_postdata();
	};

	$base_related_args = array(
		'post_type'      => 'qp_project',
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'post__not_in'   => $exclude_ids,
		'no_found_rows'  => true,
	);

	if ( ! is_wp_error( $related_terms ) && $related_terms ) {
		$typed_args              = $base_related_args;
		$typed_args['tax_query'] = array(
			array(
				'taxonomy' => 'qp_project_type',
				'field'    => 'term_id',
				'terms'    => $related_terms,
			),
		);
		$collect_related( $typed_args );
	}

	if ( count( $related_posts ) < 3 ) {
		$fallback_args                   = $base_related_args;
		$fallback_args['posts_per_page'] = 3 - count( $related_posts );
		$fallback_args['post__not_in']   = $exclude_ids;
		$collect_related( $fallback_args );
	}

	if ( $related_posts ) :
		?>
		<section class="qp-project-related" aria-label="<?php echo esc_attr( qp_holdings_t( 'related_projects' ) ); ?>">
			<div class="container">
				<h2><?php echo esc_html( qp_holdings_t( 'related_projects' ) ); ?></h2>
				<div class="project-figma-grid">
					<?php foreach ( $related_posts as $related_post ) : ?>
						<?php
						if ( function_exists( 'qp_holdings_project_figma_card_html' ) ) {
							echo qp_holdings_project_figma_card_html( $related_post, 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo qp_holdings_project_card_html( $related_post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
	endif;
	?>

</main>

<?php
endwhile;

get_footer();
