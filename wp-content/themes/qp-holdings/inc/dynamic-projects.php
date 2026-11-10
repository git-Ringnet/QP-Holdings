<?php
/**
 * Projects (qp_project) — renderer + seed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Compact project cards (homepage / sliders). Returns false if empty.
 */
function qp_holdings_render_project_cards( array $args = array() ): bool {
	$defaults = array(
		'posts_per_page' => 3,
		'type'           => '',
		'location'       => '',
		'status'         => '',
	);
	$args = array_merge( $defaults, $args );

	$q_args = array(
		'post_type'      => 'qp_project',
		'posts_per_page' => (int) $args['posts_per_page'],
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	);
	
	$tax = array();
	if ( $args['type'] ) {
		$tax[] = array( 'taxonomy' => 'qp_project_type', 'field' => 'slug', 'terms' => $args['type'] );
	}
	if ( $args['location'] ) {
		$tax[] = array( 'taxonomy' => 'qp_project_location', 'field' => 'slug', 'terms' => $args['location'] );
	}
	if ( $args['status'] ) {
		$tax[] = array( 'taxonomy' => 'qp_project_status', 'field' => 'slug', 'terms' => $args['status'] );
	}
	
	if ( $tax ) {
		$q_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
	}
	
	$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' ) ? qp_holdings_wp_query_with_language_fallback( $q_args ) : new WP_Query( $q_args );
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return false;
	}

	echo '<div class="qp-project-grid qp-project-grid--3">';
	while ( $q->have_posts() ) {
		$q->the_post();
		echo qp_holdings_project_card_html( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';

	wp_reset_postdata();
	return true;
}

/**
 * Resolve which Figma archive section a project belongs to.
 */
function qp_holdings_project_fixed_archive_section( WP_Post $post ): string {
	$source_slug = function_exists( 'qp_holdings_translation_source_slug' ) ? qp_holdings_translation_source_slug( $post ) : '';
	$slugs       = array_filter(
		array_map(
			'sanitize_title',
			array(
				$source_slug,
				(string) get_post_field( 'post_name', $post ),
				get_the_title( $post ),
			)
		)
	);

	$in_progress_slugs = array( 'qp-green-park', 'qp-binh-duong-center', 'qp-luxia-one' );
	foreach ( $slugs as $slug ) {
		foreach ( $in_progress_slugs as $in_progress_slug ) {
			if ( $in_progress_slug === $slug || false !== strpos( $slug, $in_progress_slug ) ) {
				return 'in_progress';
			}
		}
	}

	$investment_development_slugs = array(
		'hiep-thanh-city',
		'qp-idc',
		'khu-do-thi-quang-phuc-4',
		'khu-phuc-hop-cao-tang-markup',
		'cum-cong-nghiep-an-binh-7',
	);

	foreach ( $slugs as $slug ) {
		foreach ( $investment_development_slugs as $investment_development_slug ) {
			if ( $investment_development_slug === $slug || false !== strpos( $slug, $investment_development_slug ) ) {
				return 'investment_development';
			}
		}
	}

	return '';
}

function qp_holdings_project_default_archive_section( WP_Post $post ): string {
	$fixed_section = qp_holdings_project_fixed_archive_section( $post );
	if ( $fixed_section ) {
		return $fixed_section;
	}

	return 'investment_development';
}

function qp_holdings_project_archive_section( WP_Post $post ): string {
	// 1. Check taxonomy terms first (if admin explicitly assigned status in wp-admin)
	if ( taxonomy_exists( 'qp_project_status' ) ) {
		$terms = wp_get_object_terms( $post->ID, 'qp_project_status', array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			if ( in_array( 'dang-trien-khai', $terms, true ) ) {
				return 'in_progress';
			}
			if ( in_array( 'sap-ra-mat', $terms, true ) || in_array( 'quy-hoach', $terms, true ) ) {
				return 'investment_development';
			}
		}
	}

	// 2. Check ACF field / post meta 'archive_section' if set
	$valid_sections = array( 'in_progress', 'investment_development' );
	$section        = qp_holdings_acf_or_meta( $post->ID, 'archive_section', 'archive_section' );

	if ( is_array( $section ) ) {
		$section = reset( $section );
	}

	$section = is_string( $section ) ? sanitize_key( $section ) : '';

	if ( in_array( $section, $valid_sections, true ) ) {
		return $section;
	}

	// 3. Fall back to default fixed section mapping
	$fixed_section = qp_holdings_project_fixed_archive_section( $post );
	if ( $fixed_section ) {
		return $fixed_section;
	}

	return qp_holdings_project_default_archive_section( $post );
}

/**
 * Backfill the new ACF section field for existing project data.
 */
function qp_holdings_ensure_project_archive_sections(): void {
	$flag = 'qp_holdings_project_archive_sections_v1';
	if ( get_option( $flag ) ) {
		return;
	}

	$projects = get_posts(
		array(
			'post_type'      => 'qp_project',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $projects as $project ) {
		if ( ! $project instanceof WP_Post ) {
			continue;
		}

		$default_section = qp_holdings_project_default_archive_section( $project );
		if (
			'in_progress' === $default_section
			&& taxonomy_exists( 'qp_project_status' )
			&& term_exists( 'dang-trien-khai', 'qp_project_status' )
		) {
			wp_set_object_terms( $project->ID, array( 'dang-trien-khai' ), 'qp_project_status', false );
		}

		$current = qp_holdings_acf_or_meta( $project->ID, 'archive_section', 'archive_section' );
		if ( is_array( $current ) ) {
			$current = reset( $current );
		}

		$current = is_string( $current ) ? sanitize_key( $current ) : '';
		if ( in_array( $current, array( 'in_progress', 'investment_development' ), true ) ) {
			continue;
		}

		qp_holdings_seed_project_field_value(
			$project->ID,
			'archive_section',
			'field_qp_project_archive_section',
			$default_section
		);
	}

	update_option( $flag, 1 );
}
add_action( 'init', 'qp_holdings_ensure_project_archive_sections', 35 );

/**
 * One-time migration:
 * - Refresh post_content of the 8 seeded projects from the cleaned HTML files
 *   (info grid is no longer in the editor; it's rendered by the template from ACF).
 * - Backfill investment_total + sync category_info/area_total/handover/address
 *   for those projects (only when the ACF field is empty, so admin edits are preserved).
 */
function qp_holdings_migrate_project_info_grid_to_acf(): void {
	$flag = 'qp_holdings_project_info_grid_acf_v4';
	if ( get_option( $flag ) ) {
		return;
	}

	$slug_to_file = array(
		'qp-binh-duong-center'         => 'qp-binh-duong-center.html',
		'qp-luxia-one'                 => 'qp-luxia-one.html',
		'hiep-thanh-city'              => 'hiep-thanh-city.html',
		'qp-idc'                       => 'qp-idc.html',
		'khu-do-thi-quang-phuc-4'      => 'quang-phuc-4.html',
		'qp-green-park'                => 'qp-green-park.html',
		'khu-phuc-hop-cao-tang-markup' => 'markup.html',
		'cum-cong-nghiep-an-binh-7'    => 'an-binh-7.html',
	);

	// Slug → field overrides (mirrors the values previously hardcoded in the editor grid).
	$overrides = array(
		'qp-binh-duong-center'         => array(
			'category_info'    => 'TTTM',
			'area_total'       => '0,6 ha',
			'investment_total' => '1.800 tỷ đồng',
			'handover'         => 'Khởi công xây dựng Quý 1/2027',
			'address'          => 'Phường Thủ Dầu Một – TPHCM',
		),
		'qp-luxia-one'                 => array(
			'category_info'    => 'Khu phức hợp cao tầng',
			'area_total'       => '1,94ha',
			'investment_total' => '4.000 tỷ đồng',
			'handover'         => 'Khởi công xây dựng Quý 1/2027',
			'address'          => 'Phường Hòa Phú, Thủ Dầu Một - TPHCM',
		),
		'hiep-thanh-city'              => array(
			'category_info'    => 'Chung cư phức hợp',
			'area_total'       => '7,5 ha',
			'investment_total' => '11.000 tỷ đồng',
			'handover'         => 'Khởi công xây dựng Quý 1/2027',
			'address'          => 'Phường P. Thủ Dầu Một - TPHCM',
		),
		'qp-idc'                       => array(
			'category_info'    => 'Nhà xưởng cho thuê',
			'area_total'       => '20ha',
			'investment_total' => '12.000 tỷ đồng',
			'handover'         => 'Đang vận hành',
			'address'          => 'Nam Tân Uyên, Bình Dương',
		),
		'khu-do-thi-quang-phuc-4'      => array(
			'category_info'    => 'Khu đô thị đa chức năng',
			'area_total'       => '26 ha',
			'investment_total' => '4.000 tỷ đồng',
			'handover'         => 'Đang triển khai',
			'address'          => 'Bắc Tân Uyên, tỉnh Bình Dương',
		),
		'qp-green-park'                => array(
			'category_info'    => 'Khu nhà ở thấp tầng',
			'area_total'       => '13,7 ha',
			'investment_total' => '1.300 tỷ đồng',
			'handover'         => 'Hoàn thiện hạ tầng',
			'address'          => 'Bắc Tân Uyên, Thành Phố Hồ Chí Minh',
		),
		'khu-phuc-hop-cao-tang-markup' => array(
			'category_info'    => 'Khu phức hợp cao tầng',
			'area_total'       => '15,42ha',
			'investment_total' => '10.400 tỷ đồng',
			'handover'         => 'Khởi công xây dựng Quý I/2028',
			'address'          => 'Phường Thới Hòa, Thành phố Hồ Chí Minh',
		),
		'cum-cong-nghiep-an-binh-7'    => array(
			'category_info'    => 'Nhà xưởng cho thuê',
			'area_total'       => '50ha',
			'investment_total' => '12.000 tỷ đồng',
			'handover'         => 'Đưa vào hoạt động Quý I/2030',
			'address'          => 'Xã Phú Giáo, Thành phố Hồ Chí Minh',
		),
	);

	$field_keys = array(
		'category_info'    => 'field_qp_project_category_info',
		'area_total'       => 'field_qp_project_area',
		'investment_total' => 'field_qp_project_investment_total',
		'handover'         => 'field_qp_project_handover',
		'address'          => 'field_qp_project_address',
	);

	$projects = get_posts(
		array(
			'post_type'      => 'qp_project',
			'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	foreach ( $projects as $project ) {
		if ( ! $project instanceof WP_Post ) {
			continue;
		}

		$slug = (string) $project->post_name;
		if ( ! isset( $slug_to_file[ $slug ] ) ) {
			// Best-effort cleanup for any other project that might contain the grid markup.
			$content = (string) $project->post_content;
			if ( false !== strpos( $content, 'qp-project-info-' ) ) {
				$stripped = qp_holdings_strip_project_info_grid_from_html( $content );
				if ( $stripped !== $content ) {
					wp_update_post( array(
						'ID'           => $project->ID,
						'post_content' => $stripped,
					) );
				}
			}
			continue;
		}

		// Refresh post_content from the cleaned file (source of truth for these 8 projects).
		$clean_content = qp_holdings_load_project_doc_content( $slug_to_file[ $slug ] );
		if ( '' !== $clean_content ) {
			wp_update_post( array(
				'ID'           => $project->ID,
				'post_content' => $clean_content,
			) );
		}

		// Backfill ACF values, only when empty so admin edits are preserved.
		foreach ( $overrides[ $slug ] as $field_name => $value ) {
			$current = qp_holdings_acf_or_meta( $project->ID, $field_name, $field_name );
			if ( '' === (string) $current ) {
				qp_holdings_seed_project_field_value( $project->ID, $field_name, $field_keys[ $field_name ], $value );
			}
		}
	}

	update_option( $flag, 1 );
}
add_action( 'init', 'qp_holdings_migrate_project_info_grid_to_acf', 36 );

/**
 * Best-effort regex-only strip of qp-project-info-* markup from HTML.
 * Used as fallback for unknown projects that contain the legacy grid.
 */
function qp_holdings_strip_project_info_grid_from_html( string $html ): string {
	if ( false === strpos( $html, 'qp-project-info-' ) ) {
		return $html;
	}

	// Strip self-contained leaves first.
	$html = preg_replace( '#\s*<div class="qp-project-info-icon">\s*<img[^>]*/?>\s*</div>\s*#s', '', $html );
	$html = preg_replace( '#\s*<div class="qp-project-info-icon">\s*</div>\s*#s', '', $html );
	$html = preg_replace( '#\s*<div class="qp-project-info-label">[^<]*</div>\s*#s', '', $html );
	$html = preg_replace( '#\s*<div class="qp-project-info-value">[^<]*</div>\s*#s', '', $html );
	$html = preg_replace( '#\s*<div class="qp-project-info-item">\s*</div>\s*#s', '', $html );
	$html = preg_replace( '#\s*<div class="qp-project-info-grid">\s*</div>\s*#s', '', $html );

	// Strip orphan opening tags + leftover closing tags from the broken state.
	$html = preg_replace( '#\s*<div class="qp-project-info-(?:grid|item)">\s*#s', '', $html );
	// Up to 8 consecutive orphan </div> tags that used to close items/grid.
	$html = preg_replace( '#(?:\s*</div>){1,8}\s*(?=<div\s|<p\s|<h\d|$)#s', "\n", $html, 1 );

	return $html;
}

/**
 * Sort project posts within each Figma section according to desired grid layout order.
 */
function qp_holdings_sort_project_section_posts( array $posts, string $section ): array {
	if ( count( $posts ) <= 1 ) {
		return $posts;
	}

	$order_maps = array(
		'investment_development' => array(
			'khu-do-thi-quang-phuc-4'      => 10, // Vị trí 1: Khu đô thị Quang Phúc 4
			'khu-phuc-hop-cao-tang-markup' => 20, // Vị trí 2: Khu phức hợp Tài Phát
			'hiep-thanh-city'              => 30, // Vị trí 3: Hiệp Thành City
			'cum-cong-nghiep-an-binh-7'    => 40, // Vị trí 4: Cụm công nghiệp An Bình 7
			'qp-idc'                       => 50, // Vị trí 5: Khu nhà xưởng QP IDC
		),
		'in_progress' => array(
			'qp-green-park'        => 10,
			'qp-binh-duong-center' => 20,
			'qp-luxia-one'         => 30,
		),
	);

	if ( ! isset( $order_maps[ $section ] ) ) {
		return $posts;
	}

	$map = $order_maps[ $section ];

	usort(
		$posts,
		static function( WP_Post $a, WP_Post $b ) use ( $map ): int {
			$slug_a = function_exists( 'qp_holdings_translation_source_slug' ) ? qp_holdings_translation_source_slug( $a ) : (string) $a->post_name;
			$slug_b = function_exists( 'qp_holdings_translation_source_slug' ) ? qp_holdings_translation_source_slug( $b ) : (string) $b->post_name;
			if ( function_exists( 'qp_holdings_resolve_project_canonical_slug' ) ) {
				$slug_a = qp_holdings_resolve_project_canonical_slug( $slug_a );
				$slug_b = qp_holdings_resolve_project_canonical_slug( $slug_b );
			}

			$weight_a = 999;
			foreach ( $map as $k => $w ) {
				if ( $k === $slug_a || false !== strpos( $slug_a, $k ) ) {
					$weight_a = $w;
					break;
				}
			}

			$weight_b = 999;
			foreach ( $map as $k => $w ) {
				if ( $k === $slug_b || false !== strpos( $slug_b, $k ) ) {
					$weight_b = $w;
					break;
				}
			}

			if ( $weight_a === $weight_b ) {
				return ( $a->menu_order === $b->menu_order ) ? 0 : ( $a->menu_order < $b->menu_order ? -1 : 1 );
			}

			return ( $weight_a < $weight_b ) ? -1 : 1;
		}
	);

	return $posts;
}

/**
 * Full project archive page renderer — used by page-du-an.php.
 * Keeps project data dynamic while matching the Figma section layout.
 */
function qp_holdings_render_project_archive(): bool {
	$type     = qp_holdings_get_query_string( 'type' );
	$location = qp_holdings_get_query_string( 'loc' );
	$status   = qp_holdings_get_query_string( 'status' );

	$q_args = array(
		'post_type'      => 'qp_project',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
	);

	$tax = array();
	if ( $type ) {
		$tax[] = array( 'taxonomy' => 'qp_project_type', 'field' => 'slug', 'terms' => $type );
	}
	if ( $location ) {
		$tax[] = array( 'taxonomy' => 'qp_project_location', 'field' => 'slug', 'terms' => $location );
	}
	if ( $status ) {
		$tax[] = array( 'taxonomy' => 'qp_project_status', 'field' => 'slug', 'terms' => $status );
	}
	
	if ( $tax ) {
		$q_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
	}

	$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' ) ? qp_holdings_wp_query_with_language_fallback( $q_args ) : new WP_Query( $q_args );

	if ( ! $q->posts ) {
		echo '<div class="projects-figma-sections">';
		echo '<p class="qp-empty">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_projects' ) : 'Chưa có dự án phù hợp với bộ lọc.' ) . '</p>';
		echo '</div>';
		wp_reset_postdata();
		return false;
	}

	$projects_by_section = array(
		'in_progress'            => array(),
		'investment_development' => array(),
	);

	foreach ( $q->posts as $project ) {
		if ( ! $project instanceof WP_Post ) {
			continue;
		}

		$sec = qp_holdings_project_archive_section( $project );
		if ( ! isset( $projects_by_section[ $sec ] ) ) {
			$sec = 'investment_development';
		}
		$projects_by_section[ $sec ][] = $project;
	}

	if ( function_exists( 'qp_holdings_sort_project_section_posts' ) ) {
		$projects_by_section['in_progress']            = qp_holdings_sort_project_section_posts( $projects_by_section['in_progress'], 'in_progress' );
		$projects_by_section['investment_development'] = qp_holdings_sort_project_section_posts( $projects_by_section['investment_development'], 'investment_development' );
	}

	echo '<div class="projects-figma-sections">';

	if ( $projects_by_section['in_progress'] ) {
		$featured_title = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'featured_projects_title' ) : 'Dự án đang triển khai';
		echo '<section class="project-figma-section project-figma-section--in-progress" aria-label="' . esc_attr( $featured_title ) . '">';
		echo '<div class="project-figma-header">';
		echo '<div class="project-figma-title-wrap project-figma-title-wrap--progress"><h2 class="project-figma-title"' . qp_holdings_te_attr_string( 'featured_projects_title' ) . '>' . esc_html( $featured_title ) . '</h2></div>';
		echo '</div>';
		if ( 1 === count( $projects_by_section['in_progress'] ) ) {
			echo qp_holdings_project_figma_card_html( $projects_by_section['in_progress'][0], 'featured' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			echo '<div class="project-figma-grid">';
			foreach ( $projects_by_section['in_progress'] as $project ) {
				echo qp_holdings_project_figma_card_html( $project, 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
		}
		echo '</section>';
	}

	if ( $projects_by_section['investment_development'] ) {
		$allied_title = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'allied_projects_title' ) : 'Dự án hợp tác đầu tư & phát triển';
		echo '<section class="project-figma-section" aria-label="' . esc_attr( $allied_title ) . '">';
		echo '<div class="project-figma-header">';
		echo '<div class="project-figma-title-wrap project-figma-title-wrap--wide"><h2 class="project-figma-title"' . qp_holdings_te_attr_string( 'allied_projects_title' ) . '>' . esc_html( $allied_title ) . '</h2></div>';
		echo '</div>';
		echo '<div class="project-figma-grid">';
		foreach ( $projects_by_section['investment_development'] as $project ) {
			echo qp_holdings_project_figma_card_html( $project, 'grid' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo '</div>';
		echo '</section>';
	}

	qp_holdings_render_project_news_section();

	echo '</div>';

	wp_reset_postdata();
	return true;
}

/**
 * Project page card matching the Figma archive design.
 */
function qp_holdings_project_figma_card_html( WP_Post $post, string $variant = 'grid' ): string {
	$id       = $post->ID;
	$external = qp_holdings_acf_or_meta( $id, 'external_url', 'external_url' );
	$external = is_string( $external ) ? trim( $external ) : '';
	$url      = $external ? $external : get_permalink( $id );
	$target   = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
	$title    = get_the_title( $id );

	$_raw_sub = (string) qp_holdings_acf_or_meta( $id, 'subtitle', 'subtitle' );
	$address  = (string) qp_holdings_acf_or_meta( $id, 'address', 'address' );
	$scale    = (string) qp_holdings_acf_or_meta( $id, 'scale', 'scale' );

	if ( function_exists( 'qp_holdings_seeded_project_translation_for_post' ) ) {
		$subtitle = qp_holdings_seeded_project_translation_for_post( $post, 'subtitle', $_raw_sub );
		$address  = qp_holdings_seeded_project_translation_for_post( $post, 'address', $address );
		$scale    = qp_holdings_seeded_project_translation_for_post( $post, 'scale', $scale );
	} else {
		$subtitle = $_raw_sub;
	}

	if ( ! $address ) {
		$address = qp_holdings_first_term_name( $id, 'qp_project_location' );
	}

	$img     = qp_holdings_acf_or_meta( $id, 'card_image', 'card_image' );
	$img_url = qp_holdings_image_url( $img, $id, 'large' );
	$assets  = get_template_directory_uri() . '/assets/site';
	$classes = 'project-figma-card project-figma-card--' . sanitize_html_class( $variant );
	$location_icon = $assets . '/project-location-icon.svg';
	$floor_icon    = $assets . '/project-floor-icon.svg';
	$arrow_icon    = $assets . '/project-card-arrow.svg';

	$out  = '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $classes ) . '"' . $target . '>';
	$out .= '<div class="project-figma-card__media">';
	if ( $img_url ) {
		$out .= '<img src="' . esc_url( $img_url ) . '" alt="" loading="lazy" aria-hidden="true">';
	}
	$out .= '</div>';
	$out .= '<div class="project-figma-card__content">';
	$out .= '<div class="project-figma-card__body">';
	$out .= '<h3 class="project-figma-card__title">' . esc_html( $title ) . '</h3>';
	if ( $subtitle ) {
		$out .= '<p class="project-figma-card__excerpt">' . esc_html( $subtitle ) . '</p>';
	}
	$out .= '<div class="project-figma-card__meta">';
	if ( $address ) {
		$out .= '<span class="project-figma-card__meta-item"><img src="' . esc_url( $location_icon ) . '" alt="" width="16" height="16" aria-hidden="true">' . esc_html( $address ) . '</span>';
	}
	if ( $scale ) {
		$out .= '<span class="project-figma-card__meta-item"><img src="' . esc_url( $floor_icon ) . '" alt="" width="16" height="16" aria-hidden="true">' . esc_html( $scale ) . '</span>';
	}
	$out .= '</div>';
	$out .= '</div>';
	$view_details_text = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'view_details' ) : 'Xem chi tiết';
	$out .= '<span class="project-figma-card__footer"><span>' . esc_html( $view_details_text ) . '</span><img src="' . esc_url( $arrow_icon ) . '" alt="" width="24" height="24" aria-hidden="true"></span>';
	$out .= '</div>';
	$out .= '</a>';

	return $out;
}

/**
 * Project news section on the project archive page.
 */
function qp_holdings_render_project_news_section(): void {
	if ( ! function_exists( 'qp_holdings_figma_horizontal_card' ) ) {
		return;
	}

	$q_args = array(
		'post_type'      => 'qp_news',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
		'no_found_rows'  => true,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => 'qp_news_category',
				'field'    => 'slug',
				'terms'    => array( 'su-kien', 'nha-dau-tu' ),
			),
		),
	);

	$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' ) ? qp_holdings_wp_query_with_language_fallback( $q_args ) : new WP_Query( $q_args );
	if ( ! $q->posts ) {
		wp_reset_postdata();
		return;
	}

	$news_title = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'project_news_title' ) : 'Tin dự án';
	echo '<section class="project-figma-section project-figma-news" aria-label="' . esc_attr( $news_title ) . '">';
	echo '<div class="project-figma-header">';
	echo '<div class="project-figma-title-wrap"><h2 class="project-figma-title">' . esc_html( $news_title ) . '</h2></div>';
	echo '</div>';
	echo '<div class="project-news-2col-row">';
	foreach ( $q->posts as $post ) {
		if ( $post instanceof WP_Post ) {
			echo qp_holdings_figma_horizontal_card( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo '</div>';
	echo '</section>';

	wp_reset_postdata();
}

/**
 * Single project card markup.
 */
function qp_holdings_project_card_html( WP_Post $post ): string {
	$id      = $post->ID;
	$url     = qp_holdings_acf_or_meta( $id, 'external_url', 'external_url' ) ?: get_permalink( $id );
	$_raw_cat = qp_holdings_acf_or_meta( $id, 'category_info', 'category_info' )
		?: qp_holdings_first_term_name( $id, 'qp_project_type' )
		?: '';
	$status_name = qp_holdings_first_term_name( $id, 'qp_project_status' );
	$loc_name    = qp_holdings_first_term_name( $id, 'qp_project_location' );
	$img         = qp_holdings_acf_or_meta( $id, 'card_image', 'card_image' );
	$img_url     = qp_holdings_image_url( $img, $id, 'large' );
	$_raw_sub    = (string) qp_holdings_acf_or_meta( $id, 'subtitle', 'subtitle' );
	$scale       = qp_holdings_acf_or_meta( $id, 'scale', 'scale' );
	$title       = get_the_title( $id ); // filtered by the_title hook — already translated.

	// Apply project translations for card fields (subtitle, category, scale).
	if ( function_exists( 'qp_holdings_seeded_project_translation_for_post' ) ) {
		$cat      = qp_holdings_seeded_project_translation_for_post( $post, 'category', (string) $_raw_cat );
		$subtitle = qp_holdings_seeded_project_translation_for_post( $post, 'subtitle', $_raw_sub );
		$scale    = qp_holdings_seeded_project_translation_for_post( $post, 'scale', (string) $scale );
	} else {
		$cat      = (string) $_raw_cat;
		$subtitle = $_raw_sub;
		$scale    = (string) $scale;
	}

	$external_url = qp_holdings_acf_or_meta( $id, 'external_url', 'external_url' );
	$target = ( is_string( $external_url ) && $external_url ) ? ' target="_blank" rel="noopener noreferrer"' : '';

	$out  = '<a href="' . esc_url( $url ) . '" class="qp-project-card"' . $target . '>';
	$out .= '<div class="qp-project-card__media">';
	$out .= $img_url ? '<img src="' . esc_url( $img_url ) . '" alt="" loading="lazy" aria-hidden="true">' : '';
	if ( $status_name ) {
		$out .= '<span class="qp-project-card__status qp-project-card__status--' . esc_attr( sanitize_html_class( strtolower( str_replace( ' ', '-', remove_accents( $status_name ) ) ) ) ) . '">' . esc_html( $status_name ) . '</span>';
	}
	$out .= '</div>';
	$out .= '<div class="qp-project-card__body">';
	if ( $cat ) {
		$out .= '<div class="qp-project-card__cat">' . esc_html( $cat ) . '</div>';
	}
	$out .= '<h3 class="qp-project-card__title">' . esc_html( $title ) . '</h3>';
	if ( $subtitle ) {
		$out .= '<p class="qp-project-card__subtitle">' . esc_html( $subtitle ) . '</p>';
	}
	$out .= '<div class="qp-project-card__meta">';
	if ( $loc_name ) {
		$out .= '<span class="qp-project-card__meta-item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' . esc_html( $loc_name ) . '</span>';
	}
	if ( $scale ) {
		$out .= '<span class="qp-project-card__meta-item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 3v18"/></svg>' . esc_html( $scale ) . '</span>';
	}
	$out .= '</div>';
	$out .= '</div>';
	$out .= '</a>';
	return $out;
}

/**
 * Load exact project content from a generated HTML file.
 */
function qp_holdings_load_project_doc_content( string $file ): string {
	$path = get_template_directory() . '/assets/project-content/' . ltrim( $file, '/\\' );
	if ( ! file_exists( $path ) ) {
		return '';
	}

	$content = file_get_contents( $path );
	if ( ! is_string( $content ) ) {
		return '';
	}

	// Replace placeholder with actual theme URI for asset references.
	$content = str_replace( '{{THEME_URI}}', esc_url( get_template_directory_uri() ), $content );

	return $content;
}

/**
 * Build the project info icon grid from ACF fields.
 * Returns empty string if no field has a value.
 */
function qp_holdings_render_project_info_grid( int $post_id ): string {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$_pt = static function ( string $field, string $fallback ) use ( $post ): string {
		if ( function_exists( 'qp_holdings_seeded_project_translation_for_post' ) ) {
			return qp_holdings_seeded_project_translation_for_post( $post, $field, $fallback );
		}
		return $fallback;
	};

	$type       = (string) qp_holdings_acf_or_meta( $post_id, 'category_info', 'category_info' );
	if ( ! $type ) {
		$type = qp_holdings_first_term_name( $post_id, 'qp_project_type' );
	}
	$type       = $_pt( 'category', $type );
	$area       = $_pt( 'area_total', (string) qp_holdings_acf_or_meta( $post_id, 'area_total', 'area_total' ) );
	$investment = $_pt( 'investment_total', (string) qp_holdings_acf_or_meta( $post_id, 'investment_total', 'investment_total' ) );
	$handover   = $_pt( 'handover', (string) qp_holdings_acf_or_meta( $post_id, 'handover', 'handover' ) );
	$address    = $_pt( 'address', (string) qp_holdings_acf_or_meta( $post_id, 'address', 'address' ) );

	$rows = array(
		array( 'icon' => 'project-info-ico1.svg', 'label' => 'Loại hình',          'value' => $type ),
		array( 'icon' => 'project-info-ico2.svg', 'label' => 'Quy mô',             'value' => $area ),
		array( 'icon' => 'project-info-ico3.svg', 'label' => 'Tổng vốn đầu tư dự kiến',    'value' => $investment ),
		array( 'icon' => 'project-info-ico4.svg', 'label' => 'Tiến độ',            'value' => $handover ),
		array( 'icon' => 'project-info-ico5.svg', 'label' => 'Địa chỉ',            'value' => $address ),
	);

	$visible_rows = array_values( array_filter( $rows, static function ( $row ) {
		return '' !== trim( (string) $row['value'] );
	} ) );

	if ( ! $visible_rows ) {
		return '';
	}

	$assets_uri = get_template_directory_uri() . '/assets/site';

	$out  = '<div class="qp-project-info-grid">';
	foreach ( $visible_rows as $row ) {
		$icon_url = $assets_uri . '/' . $row['icon'];
		$out .= '<div class="qp-project-info-item">';
		$out .= '<div class="qp-project-info-icon"><img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $row['label'] ) . '" /></div>';
		$out .= '<div class="qp-project-info-label">' . esc_html( $row['label'] ) . '</div>';
		$out .= '<div class="qp-project-info-value">' . esc_html( $row['value'] ) . '</div>';
		$out .= '</div>';
	}
	$out .= '</div>';

	return $out;
}

/**
 * Build the "Quy mô dự án" stat-card section (Figma redesign).
 * 5 cards (Loại hình / Quy mô / Tổng vốn đầu tư / Tiến độ / Địa chỉ), amber icons.
 * Returns empty string if no field has a value.
 */
function qp_holdings_render_project_scale_section( int $post_id ): string {
	$post = get_post( $post_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$_pt = static function ( string $field, string $fallback ) use ( $post ): string {
		if ( function_exists( 'qp_holdings_seeded_project_translation_for_post' ) ) {
			return qp_holdings_seeded_project_translation_for_post( $post, $field, $fallback );
		}
		return $fallback;
	};

	$type = (string) qp_holdings_acf_or_meta( $post_id, 'category_info', 'category_info' );
	if ( ! $type ) {
		$type = qp_holdings_first_term_name( $post_id, 'qp_project_type' );
	}
	$type       = $_pt( 'category', $type );
	$area       = $_pt( 'area_total', (string) qp_holdings_acf_or_meta( $post_id, 'area_total', 'area_total' ) );
	$investment = $_pt( 'investment_total', (string) qp_holdings_acf_or_meta( $post_id, 'investment_total', 'investment_total' ) );
	$handover   = $_pt( 'handover', (string) qp_holdings_acf_or_meta( $post_id, 'handover', 'handover' ) );
	$address    = $_pt( 'address', (string) qp_holdings_acf_or_meta( $post_id, 'address', 'address' ) );

	$t = static function ( string $key, string $fallback ): string {
		$val = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( $key ) : '';
		return ( $val && $val !== $key ) ? $val : $fallback;
	};

	$rows = array(
		array( 'icon' => 'stat-loai-hinh.svg', 'label' => $t( 'project_stat_type', 'Loại hình' ),          'value' => $type ),
		array( 'icon' => 'stat-quy-mo.svg',    'label' => $t( 'project_stat_scale', 'Quy mô' ),            'value' => $area ),
		array( 'icon' => 'stat-tong-von.svg',  'label' => $t( 'project_stat_investment', 'Tổng vốn đầu tư dự kiến' ), 'value' => $investment ),
		array( 'icon' => 'stat-tien-do.svg',   'label' => $t( 'project_stat_progress', 'Tiến độ' ),        'value' => $handover ),
		array( 'icon' => 'stat-dia-chi.svg',   'label' => $t( 'project_stat_address', 'Địa chỉ' ),         'value' => $address ),
	);

	$visible_rows = array_values( array_filter( $rows, static function ( $row ) {
		return '' !== trim( (string) $row['value'] );
	} ) );

	if ( ! $visible_rows ) {
		return '';
	}

	$assets_uri  = get_template_directory_uri() . '/assets/site/project-stats';
	$heading     = $t( 'project_scale', 'Quy mô dự án' );

	$out  = '<div class="qp-project-scale__grid">';
	foreach ( $visible_rows as $row ) {
		$icon_url = $assets_uri . '/' . $row['icon'];
		$out .= '<div class="qp-project-scale__card">';
		$out .= '<span class="qp-project-scale__icon"><img src="' . esc_url( $icon_url ) . '" alt="" width="40" height="40" aria-hidden="true" loading="lazy"></span>';
		$out .= '<div class="qp-project-scale__text">';
		$out .= '<span class="qp-project-scale__label">' . esc_html( $row['label'] ) . '</span>';
		$out .= '<span class="qp-project-scale__value">' . esc_html( $row['value'] ) . '</span>';
		$out .= '</div>';
		$out .= '</div>';
	}
	$out .= '</div>';

	return '<section class="qp-project-scale" aria-label="' . esc_attr( $heading ) . '">'
		. '<div class="container">'
		. '<h2 class="qp-project-scale__heading">' . esc_html( $heading ) . '</h2>'
		. $out
		. '</div>'
		. '</section>';
}

/**
 * Import a theme asset into the Media Library and reuse it on repeat runs.
 */
function qp_holdings_seed_import_theme_attachment( string $asset_uri_or_path, string $title = '', string $source_subdir = 'assets/downloaded' ): int {
	static $cache = array();

	$path_part = wp_parse_url( $asset_uri_or_path, PHP_URL_PATH );
	$filename  = basename( is_string( $path_part ) && $path_part ? $path_part : $asset_uri_or_path );
	$source    = get_template_directory() . '/' . trim( $source_subdir, '/' ) . '/' . $filename;

	if ( ! file_exists( $source ) ) {
		return 0;
	}

	if ( isset( $cache[ $source ] ) ) {
		return (int) $cache[ $source ];
	}

	$existing = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_qp_theme_asset_source',
			'meta_value'     => $source,
		)
	);
	if ( ! empty( $existing[0] ) ) {
		$cache[ $source ] = (int) $existing[0];
		return (int) $existing[0];
	}

	if ( ! function_exists( 'wp_upload_bits' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	if ( ! function_exists( 'wp_mkdir_p' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$contents = file_get_contents( $source );
	if ( false === $contents ) {
		return 0;
	}

	$custom_base_dir = get_template_directory() . '/assets/seed-media';
	$custom_base_url = get_template_directory_uri() . '/assets/seed-media';
	$upload_dir_filter = static function( array $dirs ) use ( $custom_base_dir, $custom_base_url ): array {
		$subdir = isset( $dirs['subdir'] ) ? (string) $dirs['subdir'] : '';
		$dirs['basedir'] = $custom_base_dir;
		$dirs['baseurl'] = $custom_base_url;
		$dirs['path']    = $custom_base_dir . $subdir;
		$dirs['url']     = $custom_base_url . $subdir;
		return $dirs;
	};

	add_filter( 'upload_dir', $upload_dir_filter );
	wp_mkdir_p( $custom_base_dir . wp_upload_dir()['subdir'] );
	$upload = wp_upload_bits( $filename, null, $contents );
	remove_filter( 'upload_dir', $upload_dir_filter );
	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return 0;
	}

	$file_path = (string) $upload['file'];
	$file_type = wp_check_filetype( basename( $file_path ), null );
	$mime_type = $file_type['type'] ?: ( $upload['type'] ?? 'application/octet-stream' );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $mime_type,
			'post_title'     => $title ? $title : sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $upload['url'],
		),
		$file_path
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		if ( function_exists( 'wp_delete_file' ) ) {
			wp_delete_file( $file_path );
		}
		return 0;
	}

	if ( function_exists( 'update_attached_file' ) ) {
		update_attached_file( (int) $attachment_id, $file_path );
	}

	$metadata = wp_generate_attachment_metadata( (int) $attachment_id, $file_path );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( (int) $attachment_id, $metadata );
	}

	update_post_meta( (int) $attachment_id, '_qp_theme_asset_source', $source );
	$cache[ $source ] = (int) $attachment_id;

	return (int) $attachment_id;
}

/**
 * Seed a single ACF field if ACF is active, otherwise fall back to post meta.
 */
function qp_holdings_seed_project_field_value( int $post_id, string $field_name, string $field_key, $value ): void {
	if ( function_exists( 'update_field' ) ) {
		update_field( $field_key, $value, $post_id );
	}

	// Keep raw meta in sync too. This covers migrations that run before/without ACF
	// field loading and prevents orphaned `_field` references with empty values.
	update_post_meta( $post_id, $field_name, $value );
	update_post_meta( $post_id, '_' . $field_name, $field_key );
}

/**
 * Compose the default display title (UPPERCASE "TITLE: SUBTITLE") used in the
 * project detail hero. Editors can override per-project via the ACF field.
 */
function qp_holdings_compose_project_display_title( string $title, string $subtitle ): string {
	$upper_title    = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $title, 'UTF-8' ) : strtoupper( $title );
	$upper_subtitle = '';
	if ( '' !== trim( $subtitle ) ) {
		$upper_subtitle = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $subtitle, 'UTF-8' ) : strtoupper( $subtitle );
	}
	return $upper_subtitle ? $upper_title . ': ' . $upper_subtitle : $upper_title;
}

/**
 * One-time migration: backfill the new `display_title` field for projects that
 * existed before this field was introduced. Runs once per site.
 */
function qp_holdings_migrate_project_display_titles(): void {
	$flag = 'qp_holdings_project_display_title_migrated_v1';
	if ( get_option( $flag ) ) {
		return;
	}

	$ids = get_posts( array(
		'post_type'      => 'qp_project',
		'post_status'    => array( 'publish', 'draft', 'private', 'pending', 'future' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );

	foreach ( $ids as $pid ) {
		$pid     = (int) $pid;
		$current = (string) qp_holdings_acf_or_meta( $pid, 'display_title', 'display_title' );
		if ( '' !== trim( $current ) ) {
			continue;
		}
		// Use the raw post_title to avoid HTML entity-encoded characters (e.g. "&amp;").
		$post_obj = get_post( $pid );
		$title    = $post_obj instanceof WP_Post ? (string) $post_obj->post_title : (string) get_the_title( $pid );
		$subtitle = (string) qp_holdings_acf_or_meta( $pid, 'subtitle', 'subtitle' );
		qp_holdings_seed_project_field_value(
			$pid,
			'display_title',
			'field_qp_project_display_title',
			qp_holdings_compose_project_display_title( $title, $subtitle )
		);
	}

	update_option( $flag, time(), false );
}
add_action( 'init', 'qp_holdings_migrate_project_display_titles', 30 );

/**
 * Seed the full ACF payload for a project.
 */
function qp_holdings_seed_project_acf_values( int $post_id, array $item, int $card_image_id = 0 ): void {
	$gallery_ids = array();
	if ( ! empty( $item['gallery_ids'] ) && is_array( $item['gallery_ids'] ) ) {
		$gallery_ids = array_values( array_filter( array_map( 'intval', $item['gallery_ids'] ) ) );
	}
	$archive_section = (string) ( $item['archive_section'] ?? '' );
	if ( '' === $archive_section ) {
		$archive_section = 'investment_development';
		if ( in_array( (string) ( $item['slug'] ?? '' ), array( 'qp-green-park', 'qp-binh-duong-center', 'qp-luxia-one' ), true ) ) {
			$archive_section = 'in_progress';
		}
	}
	if ( ! in_array( $archive_section, array( 'in_progress', 'investment_development' ), true ) ) {
		$archive_section = 'investment_development';
	}

	qp_holdings_seed_project_field_value( $post_id, 'card_image', 'field_qp_project_card_image', $card_image_id > 0 ? $card_image_id : '' );
	qp_holdings_seed_project_field_value( $post_id, 'gallery', 'field_qp_project_gallery', $gallery_ids );
	qp_holdings_seed_project_field_value( $post_id, 'subtitle', 'field_qp_project_subtitle', (string) $item['subtitle'] );
	qp_holdings_seed_project_field_value(
		$post_id,
		'display_title',
		'field_qp_project_display_title',
		(string) ( $item['display_title'] ?? qp_holdings_compose_project_display_title( (string) $item['title'], (string) $item['subtitle'] ) )
	);
	qp_holdings_seed_project_field_value( $post_id, 'address', 'field_qp_project_address', (string) $item['address'] );
	qp_holdings_seed_project_field_value( $post_id, 'scale', 'field_qp_project_scale', (string) $item['scale'] );
	qp_holdings_seed_project_field_value( $post_id, 'area_total', 'field_qp_project_area', (string) $item['area_total'] );
	qp_holdings_seed_project_field_value( $post_id, 'investor_name', 'field_qp_project_investor', (string) $item['investor'] );
	qp_holdings_seed_project_field_value( $post_id, 'developer', 'field_qp_project_developer', (string) $item['developer'] );
	qp_holdings_seed_project_field_value( $post_id, 'completion_year', 'field_qp_project_year', (int) preg_replace( '/\D+/', '', (string) $item['completion'] ) );
	qp_holdings_seed_project_field_value( $post_id, 'handover', 'field_qp_project_handover', (string) $item['handover'] );
	qp_holdings_seed_project_field_value( $post_id, 'investment_total', 'field_qp_project_investment_total', (string) ( $item['investment'] ?? '' ) );
	qp_holdings_seed_project_field_value( $post_id, 'highlights', 'field_qp_project_highlights', (string) $item['highlights'] );
	qp_holdings_seed_project_field_value( $post_id, 'external_url', 'field_qp_project_external_url', (string) ( $item['external_url'] ?? '' ) );
	qp_holdings_seed_project_field_value( $post_id, 'category_info', 'field_qp_project_category_info', (string) $item['category'] );
	qp_holdings_seed_project_field_value( $post_id, 'archive_section', 'field_qp_project_archive_section', $archive_section );

	if ( $card_image_id > 0 ) {
		set_post_thumbnail( $post_id, $card_image_id );
	}
}

/* ===================================================================
 * Seed: 8 projects across types, locations, statuses.
 * =================================================================== */
function qp_holdings_seed_projects( bool $force = false ): void {
	$assets_i = get_template_directory_uri() . '/assets/index_files/';
	$downloaded = get_template_directory_uri() . '/assets/downloaded/';
	$doc_files = array(
		'qp-binh-duong-center'      => 'qp-binh-duong-center.html',
		'qp-luxia-one'              => 'qp-luxia-one.html',
		'hiep-thanh-city'           => 'hiep-thanh-city.html',
		'qp-idc'                    => 'qp-idc.html',
		'khu-do-thi-quang-phuc-4'   => 'quang-phuc-4.html',
		'qp-green-park'             => 'qp-green-park.html',
		'khu-phuc-hop-cao-tang-markup' => 'markup.html',
		'cum-cong-nghiep-an-binh-7' => 'an-binh-7.html',
	);
	$allowed_slugs = array_keys( $doc_files );

	if ( $force ) {
		$existing_projects = get_posts(
			array(
				'post_type'      => 'qp_project',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $existing_projects as $existing_project_id ) {
			$existing_slug = get_post_field( 'post_name', (int) $existing_project_id );
			if ( ! in_array( $existing_slug, $allowed_slugs, true ) ) {
				wp_delete_post( (int) $existing_project_id, true );
			}
		}
	}

	$items = array(
		array(
			'slug'        => 'qp-binh-duong-center',
			'title'       => 'QP Bình Dương Center',
			'subtitle'    => 'Biểu tượng thương mại hiện đại giữa trung tâm Thủ Dầu Một',
			'category'        => 'TTTM',
			'archive_section' => 'in_progress',
			'address'         => 'Phường Thủ Dầu Một – TPHCM',
			'scale'       => '0,6 ha — 1.800 tỷ đồng',
			'area_total'  => '0,6 ha',
			'investment'  => '1.800 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2027',
			'handover'    => 'Khởi công xây dựng Quý 1/2027',
			'image'       => $downloaded . 'qp-binh-duong-center.jpg',
			'highlights'  => "Trung tâm thương mại hiện đại tại trung tâm Thủ Dầu Một\nHệ sinh thái trải nghiệm đa chiều: mua sắm, giải trí, ẩm thực\nTổng vốn đầu tư 1.800 tỷ đồng\nVị trí trung tâm — kết nối nhịp sống đô thị năng động",
			'tax'         => array(
				'qp_project_type'     => array( 'trung-tam-thuong-mai' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'sap-ra-mat' ),
			),
			'content'     => '<p>QP Bình Dương Center được định vị là một trung tâm thương mại hiện đại mang dấu ấn đẳng cấp tại TP. Thủ Dầu Một, Bình Dương — nơi hội tụ giữa tư duy phát triển thương mại mới, trải nghiệm tiêu dùng hiện đại và tầm nhìn kiến tạo một điểm đến sôi động giữa trung tâm đô thị năng động.</p>'
				. '<h2>Vị thế trung tâm</h2><p>Tọa lạc tại TP. Thủ Dầu Một, tỉnh Bình Dương, QP Bình Dương Center hiện diện giữa khu vực giữ vai trò động lực tăng trưởng về kinh tế, dịch vụ và phát triển đô thị của địa phương. Sở hữu lợi thế kết nối giữa nhịp sống năng động của thành phố, dự án được kỳ vọng trở thành điểm đến mới dành cho cộng đồng cư dân, doanh nghiệp, khách hàng và các thương hiệu trong nước lẫn quốc tế.</p>'
				. '<h2>Quy mô đầu tư 1.800 tỷ đồng</h2><p>Được phát triển trên quy mô khoảng 0,6 ha với tổng vốn đầu tư dự kiến 1.800 tỷ đồng, QP Bình Dương Center được định hướng trở thành một trong những công trình thương mại nổi bật tại khu vực trung tâm TP. Thủ Dầu Một. Dự án được đầu tư theo định hướng hiện đại, chú trọng sự cân bằng giữa thiết kế kiến trúc, tối ưu công năng, trải nghiệm người dùng và khả năng vận hành thương mại hiệu quả.</p>'
				. '<h2>Hệ sinh thái trải nghiệm</h2><p>QP Bình Dương Center hướng đến việc phát triển một môi trường thương mại đa trải nghiệm — nơi các hoạt động tiêu dùng, giải trí, giao lưu và tận hưởng cuộc sống cùng được nâng tầm. Dự án sẽ là nơi quy tụ hàng loạt thương hiệu thuộc các lĩnh vực ẩm thực, thời trang và vui chơi giải trí.</p>'
				. '<h2>Tầm nhìn</h2><p>Mang theo khát vọng kiến tạo giá trị dài hạn, QP Bình Dương Center không chỉ phát triển một trung tâm thương mại. Đó là hành trình xây dựng một điểm đến trải nghiệm mới, một môi trường thương mại được đầu tư bài bản, một không gian kết nối giữa thương hiệu, khách hàng và phong cách sống đô thị.</p>',
		),
		array(
			'slug'        => 'qp-luxia-one',
			'title'       => 'QP Luxia One & QP Luxia Premier',
			'subtitle'    => 'Biểu tượng sống cao cấp giữa trung tâm Thủ Dầu Một',
			'category'        => 'Khu phức hợp cao tầng',
			'archive_section' => 'in_progress',
			'address'         => 'Phường Hòa Phú, Thủ Dầu Một - TPHCM',
			'scale'       => '1,94 ha — 16.000 – 18.000 cư dân',
			'area_total'  => '1,94ha',
			'investment'  => '4.000 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2027',
			'handover'    => 'Khởi công xây dựng Quý 1/2027',
			'image'       => $downloaded . 'qp-luxia-one.jpg',
			'highlights'  => "Khu phức hợp cao tầng tại trung tâm Thủ Dầu Một\nTổng vốn đầu tư 4.000 tỷ đồng\nQuy mô dân số 16.000 – 18.000 cư dân\nChuẩn sống Premium dành cho cộng đồng hiện đại",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-phuc-hop' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'sap-ra-mat' ),
			),
			'content'     => '<p>QP Luxia One & QP Luxia Premier được định vị là tổ hợp căn hộ cao cấp mang dấu ấn sống hiện đại tại trung tâm Thủ Dầu Một — nơi hội tụ giữa tư duy phát triển đô thị mới, chuẩn sống premium và phong cách sống dành cho cộng đồng cư dân năng động của thời đại mới.</p>'
				. '<h2>Vị trí trung tâm</h2><p>Tọa lạc tại phường Hòa Phú, P. Thủ Dầu Một, dự án sở hữu lợi thế hiện diện tại khu vực trung tâm năng động, nơi tập trung hạ tầng đô thị, hành chính, thương mại và các hoạt động kinh tế trọng điểm. Khả năng kết nối thuận tiện cùng việc tiếp cận nhanh chóng đến hệ thống tiện ích hiện hữu mở ra một chuẩn sống linh hoạt.</p>'
				. '<h2>Quy mô 1,94 ha — Kiến tạo không gian sống Premium</h2><p>Được phát triển trên quỹ đất khoảng 1,94 ha cùng tổng mức đầu tư dự kiến 4.000 tỷ đồng, QP Luxia One & QP Luxia Premier được định hướng trở thành một trong những tổ hợp căn hộ cao cấp đáng chú ý tại khu vực Thủ Dầu Một. Dự án được nghiên cứu theo tư duy quy hoạch hiện đại, chú trọng sự đồng bộ giữa kiến trúc, cảnh quan, công năng sử dụng và trải nghiệm cư dân.</p>'
				. '<h2>Triết lý phát triển</h2><p>Mang tinh thần của một dòng sản phẩm bất động sản cao cấp, QP Luxia One & QP Luxia Premier được phát triển theo định hướng lấy trải nghiệm cư dân, chất lượng sống và giá trị sở hữu làm trọng tâm. Dự án hướng đến việc xây dựng một môi trường cư trú nơi con người có thể tận hưởng sự tiện nghi của đô thị hiện đại nhưng vẫn tìm thấy sự thư thái, kết nối và cảm giác thuộc về trong đời sống hàng ngày.</p>'
				. '<h2>Giá trị đầu tư</h2><p>Việc sở hữu vị trí trung tâm, quy mô đầu tư lớn cùng định hướng phát triển cao cấp giúp QP Luxia One & QP Luxia Premier không chỉ đáp ứng nhu cầu an cư mà còn mở ra tiềm năng giá trị dành cho chiến lược đầu tư trung và dài hạn.</p>',
		),
		array(
			'slug'        => 'hiep-thanh-city',
			'title'       => 'Khu chung cư phức hợp Hiệp Thành City',
			'subtitle'    => 'Kiến tạo chuẩn sống đẳng cấp trong lõi đô thị Thủ Dầu Một',
			'category'    => 'Chung cư phức hợp',
			'address'     => 'Phường P. Thủ Dầu Một - TPHCM',
			'scale'       => '7,5 ha — 11.000 tỷ đồng',
			'area_total'  => '7,5 ha',
			'investment'  => '11.000 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2027',
			'handover'    => 'Khởi công xây dựng Quý 1/2027',
			'image'       => $downloaded . 'hiep-thanh-city.jpg',
			'highlights'  => "Tổ hợp căn hộ cao cấp tại trung tâm Thủ Dầu Một\nTổng vốn đầu tư 11.000 tỷ đồng\nQuy mô 7,5 ha — hệ sinh thái sống toàn diện\nHệ tiện ích nội khu – ngoại khu đồng bộ, sang trọng",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-phuc-hop' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'sap-ra-mat' ),
			),
			'content'     => '<p>Khu chung cư phức hợp Hiệp Thành City được phát triển với định hướng trở thành một tổ hợp căn hộ cao cấp quy mô lớn tại TP. Thủ Dầu Một, Bình Dương — nơi giao thoa giữa tư duy quy hoạch hiện đại, môi trường sống toàn diện và chuẩn mực sống đô thị dành cho thế hệ cư dân mới.</p>'
				. '<h2>Vị thế trung tâm</h2><p>Tọa lạc tại TP. Thủ Dầu Một, tỉnh Bình Dương, Hiệp Thành City hiện diện giữa khu vực giữ vai trò trung tâm phát triển kinh tế, hành chính và đô thị của địa phương. Sự thuận lợi trong kết nối giao thông cùng khả năng tiếp cận hệ thống tiện ích hiện hữu giúp mở ra một môi trường sống linh hoạt — nơi cư dân có thể tối ưu khoảng cách giữa công việc, gia đình, giáo dục, mua sắm và các nhu cầu trải nghiệm hàng ngày.</p>'
				. '<h2>Quy mô 7,5 ha</h2><p>Được phát triển trên quỹ đất khoảng 7,5 ha cùng tổng vốn đầu tư dự kiến 11.000 tỷ đồng, Hiệp Thành City mang tầm vóc của một khu phức hợp căn hộ cao cấp được đầu tư bài bản và dài hạn. Dự án được quy hoạch theo định hướng phát triển hiện đại, ưu tiên sự hài hòa giữa kiến trúc, công năng, cảnh quan và trải nghiệm cư dân.</p>'
				. '<h2>Triết lý phát triển</h2><p>Hiệp Thành City được định hướng phát triển như một môi trường sống nơi chất lượng cư trú được đặt ở vị trí trung tâm. Toàn khu phức hợp được quy hoạch với hệ tiện ích nội khu – ngoại khu hiện đại, đồng bộ và sang trọng.</p>'
				. '<h2>Hệ tiện ích đồng bộ</h2><p>Hiệp Thành City được định hướng phát triển như một khu phức hợp tích hợp đầy đủ hệ tiện ích hiện đại nhằm đáp ứng đa dạng nhu cầu sinh hoạt, thư giãn, kết nối và tận hưởng cuộc sống của cộng đồng cư dân tương lai.</p>',
		),
		array(
			'slug'        => 'qp-idc',
			'title'       => 'Khu nhà xưởng QP IDC',
			'subtitle'    => 'Giải pháp hạ tầng công nghiệp hiện đại cho doanh nghiệp thời đại mới',
			'category'    => 'Nhà xưởng cho thuê',
			'address'     => 'Nam Tân Uyên, Bình Dương',
			'scale'       => '20 ha',
			'area_total'  => '20ha',
			'investment'  => '12.000 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2025',
			'handover'    => 'Đang vận hành',
			'image'       => $downloaded . 'qp-idc.jpg',
			'highlights'  => "Quy mô 20 ha tại KCN Nam Tân Uyên — Bình Dương\nTổng vốn đầu tư 1.200 tỷ đồng\nMô hình nhà xưởng cho thuê hiện đại\nVị trí chiến lược — tâm điểm phát triển công nghiệp Bình Dương",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-cong-nghiep' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'dang-trien-khai' ),
			),
			'content'     => '<p>QP IDC được phát triển như một mô hình nhà xưởng cho thuê hiện đại tại Bình Dương — nơi kết hợp giữa lợi thế công nghiệp, tư duy phát triển hạ tầng bài bản và giải pháp vận hành phù hợp với nhu cầu của doanh nghiệp trong kỷ nguyên sản xuất mới.</p>'
				. '<h2>Vị thế chiến lược</h2><p>Tọa lạc tại Khu công nghiệp Nam Tân Uyên — Bình Dương, QP IDC sở hữu lợi thế hiện diện tại một trong những khu vực phát triển công nghiệp năng động của miền Nam. Bình Dương từ lâu đã khẳng định vị thế là một trong những trung tâm công nghiệp trọng điểm của cả nước.</p>'
				. '<h2>Quy mô 20 ha</h2><p>Được phát triển trên quy mô 20 ha cùng tổng vốn đầu tư dự kiến 1.200 tỷ đồng, QP IDC được định hướng trở thành một trong những mô hình hạ tầng công nghiệp tiêu biểu tại Bình Dương. Không gian nhà xưởng được nghiên cứu nhằm tối ưu công năng sử dụng, nâng cao hiệu quả khai thác và phù hợp với đa dạng lĩnh vực sản xuất, công nghiệp hỗ trợ và logistics.</p>'
				. '<h2>Triết lý phát triển</h2><p>QP IDC được định hướng phát triển theo mô hình nhà xưởng cho thuê hiện đại, nơi hạ tầng không chỉ phục vụ hoạt động sản xuất mà còn đóng vai trò giải pháp hỗ trợ doanh nghiệp tối ưu nguồn lực đầu tư và nâng cao hiệu quả vận hành.</p>',
		),
		array(
			'slug'        => 'khu-do-thi-quang-phuc-4',
			'title'       => 'Khu đô thị Quang Phúc 4',
			'subtitle'    => 'Dấu ấn đô thị kiểu mẫu — Kiến tạo chuẩn sống thông minh cho thế hệ tương lai',
			'category'    => 'Khu đô thị đa chức năng',
			'address'     => 'Bắc Tân Uyên, tỉnh Bình Dương',
			'scale'       => '26 ha',
			'area_total'  => '26 ha',
			'investment'  => '4.000 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2028',
			'handover'    => 'Đang triển khai',
			'image'       => $downloaded . 'quang-phuc-4.jpg',
			'highlights'  => "Khu đô thị đa chức năng 26 ha — 4.000 tỷ đồng\nĐô thị kiểu mẫu thông minh tại Bắc Tân Uyên\nHệ sinh thái tiện ích toàn diện\nKhông gian xanh — giá trị cốt lõi của môi trường sống",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-dan-cu' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'dang-trien-khai' ),
			),
			'content'     => '<p>Khu đô thị Quang Phúc 4 ra đời với định hướng trở thành một đô thị kiểu mẫu thông minh, nơi hội tụ giữa không gian sống hiện đại, hệ sinh thái tiện ích toàn diện và những chuẩn mực phát triển đô thị của tương lai. Dự án đại diện cho khát vọng phát triển của QP Group trong hành trình kiến tạo những cộng đồng sống chất lượng, văn minh và phát triển bền vững tại Bình Dương.</p>'
				. '<h2>Vị thế chiến lược</h2><p>Tọa lạc tại Bắc Tân Uyên, tỉnh Bình Dương, Quang Phúc 4 hiện diện trong khu vực được xem là một trong những tâm điểm tăng trưởng mới của tỉnh — nơi hội tụ nhiều động lực phát triển về công nghiệp, hạ tầng, dân cư và thương mại.</p>'
				. '<h2>Quy mô 26 ha</h2><p>Được quy hoạch trên quỹ đất khoảng 26 ha cùng tổng mức đầu tư dự kiến 4.000 tỷ đồng, Quang Phúc 4 mang tầm vóc của một khu đô thị đa chức năng được đầu tư bài bản và dài hạn. Dự án được định hướng phát triển theo mô hình đô thị hiện đại, tích hợp nhiều chức năng trong cùng một hệ sinh thái sống.</p>'
				. '<h2>Triết lý đô thị thông minh</h2><p>Tại Quang Phúc 4, yếu tố "thông minh" không đơn thuần là ứng dụng công nghệ, mà được xem như một triết lý phát triển xuyên suốt toàn bộ dự án. Đó là sự thông minh trong quy hoạch, thông minh trong tổ chức không gian sống, thông minh trong cách con người kết nối với môi trường, cộng đồng và các tiện ích đô thị.</p>'
				. '<h2>Hệ tiện ích toàn diện</h2><p>Với định hướng phát triển khu đô thị đa chức năng, Quang Phúc 4 chú trọng đầu tư hệ thống tiện ích nội khu và tận dụng lợi thế kết nối với mạng lưới tiện ích ngoại khu đa dạng của khu vực.</p>',
		),
		array(
			'slug'        => 'qp-green-park',
			'title'       => 'Khu dân cư QP Green Park',
			'subtitle'    => 'Dấu ấn đô thị xanh — Kiến tạo chuẩn mực sống mới tại Bắc Tân Uyên',
			'category'    => 'Khu nhà ở thấp tầng',
			'archive_section' => 'in_progress',
			'address'     => 'Bắc Tân Uyên, Thành Phố Hồ Chí Minh',
			'scale'       => '13,7 ha — 568 nhà phố + 68 shophouse',
			'area_total'  => '13,7 ha',
			'investment'  => '1.300 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2026',
			'handover'    => 'Hoàn thiện hạ tầng',
			'image'       => $downloaded . 'qp-green-park-google-doc.jpg',
			'highlights'  => "Khu nhà ở thấp tầng 13,7 ha — 1.300 tỷ đồng\n568 căn nhà phố + 68 căn shophouse thương mại\nĐịnh hướng đô thị xanh, đồng bộ, hiện đại\nVị trí chiến lược trên trục ĐT742 — Bắc Tân Uyên",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-dan-cu' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'dang-trien-khai' ),
			),
			'content'     => '<p>QP Green Park ra đời với định hướng trở thành một khu đô thị đồng bộ, hiện đại và giàu giá trị nhân văn tại Bắc Tân Uyên, Bình Dương — nơi hội tụ hài hòa giữa không gian sống xanh, tiện ích toàn diện và tiềm năng phát triển dài hạn.</p>'
				. '<h2>Tầm nhìn phát triển</h2><p>Nắm bắt vận động của thị trường và nhu cầu sống ngày càng nâng cao của khách hàng, QP Green Park được quy hoạch với mục tiêu hình thành một khu đô thị mang tính kết nối, hiện đại và phát triển bền vững — nơi giá trị an cư được đặt song hành cùng tầm nhìn đầu tư dài hạn.</p>'
				. '<h2>Quy hoạch đồng bộ</h2><p>Được phát triển trên quy mô khoảng 13,7 ha, QP Green Park cung cấp hệ sản phẩm đa dạng gồm 568 căn nhà phố và 68 căn shophouse thương mại. Mỗi phân khu được nghiên cứu theo tiêu chí tối ưu công năng sử dụng, khả năng kết nối không gian và giá trị khai thác lâu dài.</p>'
				. '<h2>Triết lý đô thị xanh</h2><p>QP Green Park được phát triển với tinh thần "lấy con người làm trung tâm", đặt trọng tâm vào trải nghiệm sống và chất lượng môi trường cư trú. Các yếu tố cảnh quan, không gian mở và hệ thống mảng xanh được chú trọng trong tổng thể quy hoạch nhằm mang đến môi trường sống hài hòa, gần gũi thiên nhiên nhưng vẫn đáp ứng đầy đủ yêu cầu của đời sống hiện đại.</p>'
				. '<h2>Giá trị đầu tư bền vững</h2><p>QP Green Park hiện diện tại khu vực đang ghi nhận nhiều động lực phát triển về công nghiệp, hạ tầng và dân cư — những yếu tố đóng vai trò quan trọng trong quá trình hình thành giá trị bất động sản tương lai.</p>',
		),
		array(
			'slug'        => 'khu-phuc-hop-cao-tang-markup',
			'title'       => 'Khu phức hợp cao tầng Markup',
			'subtitle'    => 'Biểu tượng đô thị mới giữa trung tâm phát triển năng động phía Bắc TP.HCM',
			'category'    => 'Khu phức hợp cao tầng',
			'address'     => 'Phường Thới Hòa, Thành phố Hồ Chí Minh',
			'scale'       => '15,42 ha — 16.000 – 18.000 cư dân',
			'area_total'  => '15,42ha',
			'investment'  => '10.400 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2028',
			'handover'    => 'Khởi công xây dựng Quý I/2028',
			'image'       => $downloaded . 'markup.jpg',
			'highlights'  => "Khu phức hợp cao tầng 15,42 ha — 10.400 tỷ đồng\nDân số dự kiến 16.000 – 18.000 cư dân\nTích hợp căn hộ cao tầng, TTTM, giáo dục mầm non\nVị trí chiến lược tại phường Thới Hòa",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-phuc-hop' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'quy-hoach' ),
			),
			'content'     => '<p>Khu phức hợp cao tầng Markup được phát triển như một tổ hợp đô thị đa chức năng hiện đại tại phường Thới Hòa – khu vực phát triển công nghiệp, đô thị và dịch vụ năng động phía Bắc Thành phố Hồ Chí Minh. Markup hướng tới việc kiến tạo một chuẩn mực phát triển đô thị mới — nơi không gian sống, thương mại, cộng đồng và giá trị phát triển cùng hội tụ trong một hệ sinh thái hiện đại và đồng bộ.</p>'
				. '<h2>Vị thế chiến lược</h2><p>Tọa lạc tại phường Thới Hòa, TP. Hồ Chí Minh, dự án sở hữu lợi thế hiện diện tại khu vực trung tâm phát triển công nghiệp – đô thị trọng điểm. Markup hưởng lợi từ khả năng liên kết thuận tiện với các khu công nghiệp và đô thị đã hình thành như Mỹ Phước 2, Mỹ Phước 3 cùng hệ thống hạ tầng giao thông ngày càng hoàn thiện.</p>'
				. '<h2>Quy mô 15,42 ha</h2><p>Được phát triển trên quy mô khoảng 15,42 ha cùng tổng mức đầu tư dự kiến 10.400 tỷ đồng, Khu phức hợp cao tầng Markup được định hướng trở thành một trong những tổ hợp cao tầng quy mô lớn mang tính chiến lược tại khu vực phía Bắc TP. Hồ Chí Minh. Dự án tích hợp khu căn hộ cao tầng hiện đại, trung tâm thương mại – dịch vụ, không gian giáo dục mầm non, quảng trường trung tâm và hệ thống tiện ích nội khu đồng bộ.</p>'
				. '<h2>Giá trị đầu tư</h2><p>Việc sở hữu lợi thế vị trí trung tâm phát triển, quỹ đất quy mô lớn cùng định hướng quy hoạch hiện đại giúp Markup không chỉ đáp ứng nhu cầu an cư – thương mại – dịch vụ mà còn mở ra tiềm năng phát triển dài hạn dành cho nhà đầu tư và cộng đồng cư dân tương lai.</p>',
		),
		array(
			'slug'        => 'cum-cong-nghiep-an-binh-7',
			'title'       => 'Cụm công nghiệp An Bình 7',
			'subtitle'    => 'Tổ hợp công nghiệp hiện đại — Kiến tạo động lực phát triển sản xuất thế hệ mới',
			'category'    => 'Nhà xưởng cho thuê',
			'address'     => 'Xã Phú Giáo, Thành phố Hồ Chí Minh',
			'scale'       => '50 ha',
			'area_total'  => '50ha',
			'investment'  => '12.000 tỷ đồng',
			'investor'    => 'QP Holdings',
			'developer'   => 'QP Holdings',
			'completion'  => '2030',
			'handover'    => 'Đưa vào hoạt động Quý I/2030',
			'image'       => $downloaded . 'an-binh-7.jpg',
			'highlights'  => "Quy mô 50 ha — tổng vốn đầu tư 12.000 tỷ đồng\nMô hình cụm công nghiệp hiện đại, đồng bộ\nHạ tầng kỹ thuật, kho bãi, logistics tích hợp\nVị trí kết nối chiến lược tại khu vực phía Bắc TP.HCM",
			'tax'         => array(
				'qp_project_type'     => array( 'khu-cong-nghiep' ),
				'qp_project_location' => array( 'binh-duong' ),
				'qp_project_status'   => array( 'quy-hoach' ),
			),
			'content'     => '<p>Cụm công nghiệp An Bình 7 được phát triển như một tổ hợp nhà xưởng cho thuê quy mô lớn tại khu vực phía Bắc Thành phố Hồ Chí Minh — nơi kết hợp giữa lợi thế vị trí chiến lược, quy mô đầu tư lớn và hạ tầng được quy hoạch đồng bộ theo tư duy phát triển công nghiệp hiện đại.</p>'
				. '<h2>Vị trí kết nối chiến lược</h2><p>Tọa lạc tại xã Phú Giáo, TP. Hồ Chí Minh, dự án sở hữu lợi thế hiện diện tại khu vực giàu tiềm năng phát triển công nghiệp, thuận lợi kết nối đến các trung tâm sản xuất, logistics và hệ thống giao thông liên vùng phía Nam.</p>'
				. '<h2>Quy mô 50 ha</h2><p>Được đầu tư trên quy mô khoảng 50 ha cùng tổng vốn đầu tư dự kiến 12.000 tỷ đồng, Cụm công nghiệp An Bình 7 được định hướng trở thành một trong những tổ hợp công nghiệp quy mô lớn mang tính chiến lược tại khu vực phía Nam. Dự án được quy hoạch theo mô hình cụm công nghiệp hiện đại, ưu tiên sự đồng bộ giữa hạ tầng kỹ thuật, tổ chức không gian vận hành và khả năng đáp ứng đa dạng nhu cầu sản xuất.</p>'
				. '<h2>Triết lý phát triển</h2><p>An Bình 7 được định hướng phát triển theo mô hình cụm công nghiệp đồng bộ, nơi hạ tầng không chỉ phục vụ hoạt động sản xuất mà còn đóng vai trò nền tảng nâng cao hiệu quả vận hành cho doanh nghiệp. Dự án tích hợp khu nhà xưởng cho thuê quy mô lớn, hệ thống kho bãi và logistics, hạ tầng kỹ thuật đồng bộ, giao thông nội khu hiện đại và khu điều hành dịch vụ hỗ trợ doanh nghiệp.</p>'
				. '<h2>Giá trị đầu tư</h2><p>Việc sở hữu lợi thế vị trí, quy mô đầu tư lớn cùng định hướng phát triển rõ nét giúp Cụm công nghiệp An Bình 7 không chỉ đáp ứng nhu cầu vận hành thực tế mà còn mở ra tiềm năng phát triển dài hạn cho cộng đồng doanh nghiệp trong và ngoài nước.</p>',
		),
	);

	foreach ( $items as $item ) {
		$existing = get_page_by_path( $item['slug'], OBJECT, 'qp_project' );
		if ( $existing instanceof WP_Post && ! $force ) {
			continue;
		}
		$args = array(
			'post_type'    => 'qp_project',
			'post_status'  => 'publish',
			'post_title'   => $item['title'],
			'post_name'    => $item['slug'],
			'post_excerpt' => $item['subtitle'],
			'post_content' => isset( $doc_files[ $item['slug'] ] ) ? qp_holdings_load_project_doc_content( $doc_files[ $item['slug'] ] ) : (string) $item['content'],
		);
		if ( $existing instanceof WP_Post ) {
			$args['ID'] = (int) $existing->ID;
		}
		$post_id = wp_insert_post( $args, true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		$card_image_id = qp_holdings_seed_import_theme_attachment( (string) $item['image'], (string) $item['title'] );
		qp_holdings_seed_project_acf_values( (int) $post_id, $item, $card_image_id );

		qp_holdings_seed_assign_terms( (int) $post_id, $item['tax'] );
	}
}
