<?php
/**
 * Custom full-page admin dashboard with stats, quick links, charts and activity.
 *
 * Replaces the native WordPress dashboard (index.php) with a dedicated, full-width
 * overview page so the layout is fully controlled (no metabox/postbox machinery).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Capability required to view lead/applicant (PII-bearing) data.
 */
function qph_dashboard_pii_cap(): string {
	return 'manage_options';
}

/**
 * Register the custom overview page and hide the native dashboard.
 */
function qph_dashboard_register_page(): void {
	add_menu_page(
		'Tổng quan',
		'Tổng quan',
		'read',
		'qph-overview',
		'qph_dashboard_render_page',
		'dashicons-chart-area',
		2
	);
}
add_action( 'admin_menu', 'qph_dashboard_register_page' );

/**
 * Boot the native WordPress dashboard-widget system on the custom overview page.
 *
 * Loads the core dashboard include, registers all standard + plugin widgets
 * against the current screen and enqueues the scripts/styles they rely on, so
 * the widgets can be rendered at the bottom of the custom page via wp_dashboard().
 */
function qph_dashboard_boot_widgets(): void {
	require_once ABSPATH . 'wp-admin/includes/dashboard.php';

	wp_enqueue_script( 'dashboard' );
	wp_enqueue_style( 'dashboard' );

	if ( current_user_can( 'install_plugins' ) ) {
		wp_enqueue_script( 'plugin-install' );
		wp_enqueue_script( 'updates' );
	}
	if ( current_user_can( 'edit_theme_options' ) ) {
		wp_enqueue_script( 'customize-loader' );
	}

	// Wire up collapse/expand + drag handles once the DOM is ready.
	wp_add_inline_script(
		'dashboard',
		'jQuery(function(){ if ( window.postboxes ) { postboxes.add_postbox_toggles( pagenow ); } });'
	);

	wp_dashboard_setup();
}
add_action( 'load-toplevel_page_qph-overview', 'qph_dashboard_boot_widgets' );

/**
 * Redirect the native dashboard to the custom overview page.
 */
function qph_dashboard_redirect_index(): void {
	if ( wp_doing_ajax() ) {
		return;
	}

	wp_safe_redirect( admin_url( 'admin.php?page=qph-overview' ) );
	exit;
}
add_action( 'load-index.php', 'qph_dashboard_redirect_index' );

/**
 * Remove the default WordPress welcome panel (defensive; the dashboard is bypassed).
 */
function qph_dashboard_remove_welcome_panel(): void {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
add_action( 'admin_init', 'qph_dashboard_remove_welcome_panel' );

/**
 * Enqueue dashboard styles, self-hosted Chart.js and chart bootstrap, scoped to the page.
 */
function qph_dashboard_assets( string $hook ): void {
	if ( 'toplevel_page_qph-overview' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'qph-dashboard',
		qph_admin_branding_asset_url( 'dashboard.css' ),
		array(),
		qph_admin_branding_asset_version( 'dashboard.css' )
	);

	wp_enqueue_script(
		'qph-chartjs',
		qph_admin_branding_asset_url( 'chart.umd.min.js' ),
		array(),
		'4.4.6',
		true
	);

	wp_enqueue_script(
		'qph-dashboard-charts',
		qph_admin_branding_asset_url( 'dashboard-charts.js' ),
		array( 'qph-chartjs' ),
		qph_admin_branding_asset_version( 'dashboard-charts.js' ),
		true
	);

	wp_localize_script( 'qph-dashboard-charts', 'qphDashboardData', qph_dashboard_chart_payload() );
}
add_action( 'admin_enqueue_scripts', 'qph_dashboard_assets' );

/**
 * Brand the admin sidebar/admin-bar across all admin screens.
 */
function qph_dashboard_admin_chrome(): void {
	wp_enqueue_style(
		'qph-admin-chrome',
		qph_admin_branding_asset_url( 'admin.css' ),
		array(),
		qph_admin_branding_asset_version( 'admin.css' )
	);
}
add_action( 'admin_enqueue_scripts', 'qph_dashboard_admin_chrome' );

/* -------------------------------------------------------------------------
 * Data layer
 * ---------------------------------------------------------------------- */

/**
 * Count published/private posts for a post type.
 */
function qph_dashboard_post_type_total( string $post_type ): int {
	$counts = wp_count_posts( $post_type );
	if ( ! $counts ) {
		return 0;
	}

	return (int) ( $counts->publish ?? 0 ) + (int) ( $counts->private ?? 0 );
}

/**
 * Build (and cache) all dashboard data: stats + chart datasets.
 *
 * Counts all languages (raw business volume) and does not filter by Polylang
 * language. Cached for one hour and busted whenever a relevant post is saved.
 */
function qph_dashboard_get_data(): array {
	$cached = get_transient( 'qph_dashboard_data' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;

	$can_pii = current_user_can( qph_dashboard_pii_cap() );

	// --- Stat tiles ---
	$stats = array(
		array( 'label' => 'Trang', 'value' => qph_dashboard_post_type_total( 'page' ), 'icon' => 'dashicons-admin-page' ),
		array( 'label' => 'Tin tức', 'value' => qph_dashboard_post_type_total( 'qp_news' ), 'icon' => 'dashicons-megaphone' ),
		array( 'label' => 'Dự án', 'value' => qph_dashboard_post_type_total( 'qp_project' ), 'icon' => 'dashicons-building' ),
		array( 'label' => 'Tuyển dụng', 'value' => qph_dashboard_post_type_total( 'qp_job' ), 'icon' => 'dashicons-businessperson' ),
		array( 'label' => 'Tài liệu NĐT', 'value' => qph_dashboard_post_type_total( 'qp_investor_doc' ), 'icon' => 'dashicons-portfolio' ),
		array( 'label' => 'Liên hệ', 'value' => qph_dashboard_post_type_total( 'qp_contact_msg' ), 'icon' => 'dashicons-email-alt2' ),
		array( 'label' => 'Đăng ký tư vấn', 'value' => qph_dashboard_post_type_total( 'qp_consult_msg' ), 'icon' => 'dashicons-phone' ),
		array( 'label' => 'Hồ sơ ứng tuyển', 'value' => qph_dashboard_post_type_total( 'qp_job_app' ), 'icon' => 'dashicons-id' ),
	);

	// --- Time buckets: last 12 months (YYYY-MM) ---
	$months  = array();
	$labels  = array();
	$cursor  = new DateTimeImmutable( 'first day of this month' );
	for ( $i = 11; $i >= 0; $i-- ) {
		$dt        = $cursor->modify( "-{$i} month" );
		$key       = $dt->format( 'Y-m' );
		$months[]  = $key;
		$labels[]  = $dt->format( 'm/Y' );
	}
	$since = ( $cursor->modify( '-11 month' ) )->format( 'Y-m-01 00:00:00' );

	// --- Chart 1: submissions over time (stacked) — PII-gated ---
	$submissions = array(
		'labels'   => $labels,
		'series'   => array(),
		'gated'    => ! $can_pii,
	);

	if ( $can_pii ) {
		$sub_types = array(
			'qp_contact_msg'  => 'Liên hệ',
			'qp_consult_msg'  => 'Đăng ký tư vấn',
			'qp_job_app'      => 'Hồ sơ ứng tuyển',
		);

		$placeholders = implode( ',', array_fill( 0, count( $sub_types ), '%s' ) );
		$args         = array_merge( array_keys( $sub_types ), array( $since ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_type, DATE_FORMAT(post_date, '%%Y-%%m') AS ym, COUNT(*) AS n
				 FROM {$wpdb->posts}
				 WHERE post_type IN ($placeholders)
				   AND post_status = 'publish'
				   AND post_date >= %s
				 GROUP BY post_type, ym",
				$args
			)
		);

		$by_type = array();
		foreach ( array_keys( $sub_types ) as $type ) {
			$by_type[ $type ] = array_fill_keys( $months, 0 );
		}
		foreach ( (array) $rows as $row ) {
			if ( isset( $by_type[ $row->post_type ][ $row->ym ] ) ) {
				$by_type[ $row->post_type ][ $row->ym ] = (int) $row->n;
			}
		}
		foreach ( $sub_types as $type => $label ) {
			$submissions['series'][] = array(
				'label' => $label,
				'data'  => array_values( $by_type[ $type ] ),
			);
		}
	}

	// --- Chart 2: content publishing cadence (stacked bar) ---
	$content_types = array(
		'qp_news'         => 'Tin tức',
		'qp_project'      => 'Dự án',
		'qp_job'          => 'Tuyển dụng',
		'qp_investor_doc' => 'Tài liệu NĐT',
	);

	$placeholders = implode( ',', array_fill( 0, count( $content_types ), '%s' ) );
	$args         = array_merge( array_keys( $content_types ), array( $since ) );

	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_type, DATE_FORMAT(post_date, '%%Y-%%m') AS ym, COUNT(*) AS n
			 FROM {$wpdb->posts}
			 WHERE post_type IN ($placeholders)
			   AND post_status IN ('publish','private')
			   AND post_date >= %s
			 GROUP BY post_type, ym",
			$args
		)
	);

	$by_type = array();
	foreach ( array_keys( $content_types ) as $type ) {
		$by_type[ $type ] = array_fill_keys( $months, 0 );
	}
	foreach ( (array) $rows as $row ) {
		if ( isset( $by_type[ $row->post_type ][ $row->ym ] ) ) {
			$by_type[ $row->post_type ][ $row->ym ] = (int) $row->n;
		}
	}
	$publishing = array( 'labels' => $labels, 'series' => array() );
	foreach ( $content_types as $type => $label ) {
		$publishing['series'][] = array(
			'label' => $label,
			'data'  => array_values( $by_type[ $type ] ),
		);
	}

	// --- Chart 3: jobs by department (horizontal bar) ---
	$jobs_by_dept = array( 'labels' => array(), 'data' => array() );
	$terms        = get_terms(
		array(
			'taxonomy'   => 'qp_job_department',
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		// Sort by count desc.
		usort(
			$terms,
			static function ( $a, $b ) {
				return (int) $b->count <=> (int) $a->count;
			}
		);
		foreach ( $terms as $term ) {
			$jobs_by_dept['labels'][] = $term->name;
			$jobs_by_dept['data'][]   = (int) $term->count;
		}
	}

	$data = array(
		'stats'       => $stats,
		'submissions' => $submissions,
		'publishing'  => $publishing,
		'jobsByDept'  => $jobs_by_dept,
	);

	set_transient( 'qph_dashboard_data', $data, HOUR_IN_SECONDS );

	return $data;
}

/**
 * Chart-only payload passed to JS (stats are rendered server-side).
 */
function qph_dashboard_chart_payload(): array {
	$data = qph_dashboard_get_data();

	return array(
		'submissions' => $data['submissions'],
		'publishing'  => $data['publishing'],
		'jobsByDept'  => $data['jobsByDept'],
		'palette'     => array(
			'navy'     => '#193660',
			'navyMid'  => '#265D90',
			'gold'     => '#D3A049',
			'goldSoft' => '#FFDDA4',
			'teal'     => '#3E7C8C',
			'grid'     => 'rgba(25,54,96,0.08)',
			'text'     => '#667085',
		),
	);
}

/**
 * Bust the dashboard cache whenever relevant content changes.
 */
function qph_dashboard_bust_cache( $post_id ): void {
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	delete_transient( 'qph_dashboard_data' );
}
add_action( 'save_post', 'qph_dashboard_bust_cache' );
add_action( 'deleted_post', 'qph_dashboard_bust_cache' );

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

/**
 * Render the full-page overview screen.
 */
function qph_dashboard_render_page(): void {
	$data    = qph_dashboard_get_data();
	$can_pii = current_user_can( qph_dashboard_pii_cap() );

	$quick_links = array(
		array( 'label' => 'Trang nội dung', 'url' => admin_url( 'edit.php?post_type=page' ), 'icon' => 'dashicons-admin-page' ),
		array( 'label' => 'Thêm tin tức', 'url' => admin_url( 'post-new.php?post_type=qp_news' ), 'icon' => 'dashicons-megaphone' ),
		array( 'label' => 'Thêm dự án', 'url' => admin_url( 'post-new.php?post_type=qp_project' ), 'icon' => 'dashicons-building' ),
		array( 'label' => 'Thêm tuyển dụng', 'url' => admin_url( 'post-new.php?post_type=qp_job' ), 'icon' => 'dashicons-businessperson' ),
		array( 'label' => 'Tài liệu nhà đầu tư', 'url' => admin_url( 'edit.php?post_type=qp_investor_doc' ), 'icon' => 'dashicons-portfolio' ),
		array( 'label' => 'Form liên hệ', 'url' => admin_url( 'edit.php?post_type=qp_contact_msg' ), 'icon' => 'dashicons-email-alt2' ),
	);
	?>
	<div class="wrap qph-overview">
		<h1 class="screen-reader-text">Tổng quan quản trị</h1>

		<div class="qph-hero">
			<div class="qph-hero__text">
				<p class="qph-hero__kicker">Bảng điều khiển nội dung</p>
				<h2 class="qph-hero__title">Quản trị website QP Holdings</h2>
				<p class="qph-hero__desc">Theo dõi nội dung, dự án, tuyển dụng và lượng liên hệ từ một màn hình tổng quan trực quan.</p>
			</div>
			<a class="qph-hero__cta" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener noreferrer">
				<span class="dashicons dashicons-external" aria-hidden="true"></span>
				Xem website
			</a>
		</div>

		<div class="qph-stats">
			<?php foreach ( $data['stats'] as $stat ) : ?>
				<div class="qph-stat">
					<span class="qph-stat__icon dashicons <?php echo esc_attr( $stat['icon'] ); ?>" aria-hidden="true"></span>
					<strong class="qph-stat__value"><?php echo esc_html( (string) $stat['value'] ); ?></strong>
					<span class="qph-stat__label"><?php echo esc_html( $stat['label'] ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>

		<div class="qph-charts">
			<?php if ( $can_pii ) : ?>
				<section class="qph-card qph-card--wide">
					<header class="qph-card__head">
						<h3>Lượt gửi biểu mẫu</h3>
						<span class="qph-card__hint">12 tháng gần nhất</span>
					</header>
					<div class="qph-card__body">
						<canvas id="qphChartSubmissions" height="240" role="img" aria-label="Biểu đồ lượt gửi biểu mẫu theo tháng"></canvas>
					</div>
				</section>
			<?php endif; ?>

			<section class="qph-card<?php echo $can_pii ? '' : ' qph-card--wide'; ?>">
				<header class="qph-card__head">
					<h3>Nhịp xuất bản nội dung</h3>
					<span class="qph-card__hint">12 tháng gần nhất</span>
				</header>
				<div class="qph-card__body">
					<canvas id="qphChartPublishing" height="240" role="img" aria-label="Biểu đồ nội dung xuất bản theo tháng"></canvas>
				</div>
			</section>

			<section class="qph-card">
				<header class="qph-card__head">
					<h3>Tuyển dụng theo khối</h3>
					<span class="qph-card__hint">Số vị trí hiện có</span>
				</header>
				<div class="qph-card__body">
					<canvas id="qphChartJobs" height="240" role="img" aria-label="Biểu đồ tuyển dụng theo khối"></canvas>
				</div>
			</section>
		</div>

		<div class="qph-lower">
			<section class="qph-card qph-quicklinks">
				<header class="qph-card__head">
					<h3>Lối tắt biên tập</h3>
				</header>
				<div class="qph-card__body">
					<div class="qph-links">
						<?php foreach ( $quick_links as $link ) : ?>
							<a class="qph-link" href="<?php echo esc_url( $link['url'] ); ?>">
								<span class="dashicons <?php echo esc_attr( $link['icon'] ); ?>" aria-hidden="true"></span>
								<?php echo esc_html( $link['label'] ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>

			<section class="qph-card qph-activity">
				<header class="qph-card__head">
					<h3>Hoạt động mới</h3>
				</header>
				<div class="qph-card__body">
					<?php qph_dashboard_render_activity( $can_pii ); ?>
				</div>
			</section>
		</div>

		<?php qph_dashboard_render_native_widgets(); ?>
	</div>
	<?php
}

/**
 * Render the native WordPress dashboard widgets (core + plugin) below the
 * custom overview, inside the standard metabox container so collapse, drag
 * and AJAX behaviour all work as on the default dashboard.
 */
function qph_dashboard_render_native_widgets(): void {
	if ( ! function_exists( 'wp_dashboard' ) ) {
		return;
	}

	// Mirror the markup wp_dashboard() expects (id="dashboard-widgets-wrap").
	?>
	<section class="qph-card qph-modules">
		<header class="qph-card__head">
			<h3>Mô-đun WordPress</h3>
			<span class="qph-card__hint">Tiện ích bảng tin &amp; plugin</span>
		</header>
		<div class="qph-card__body qph-modules__body">
			<div id="dashboard-widgets-wrap">
				<?php wp_dashboard(); ?>
			</div>
		</div>
	</section>
	<?php
}

/**
 * Render the recent activity list. PII-bearing submission rows are only included
 * for users with the required capability.
 */
function qph_dashboard_render_activity( bool $can_pii ): void {
	$content_types    = array( 'qp_news', 'qp_project', 'qp_job', 'qp_investor_doc' );
	$submission_types = array( 'qp_contact_msg', 'qp_consult_msg', 'qp_job_app' );

	$post_types = $can_pii ? array_merge( $submission_types, $content_types ) : $content_types;

	$items = get_posts(
		array(
			'post_type'      => $post_types,
			'post_status'    => array( 'publish', 'private', 'draft' ),
			'posts_per_page' => 8,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	if ( ! $items ) {
		echo '<p class="qph-empty">Chưa có hoạt động mới.</p>';
		return;
	}

	echo '<ul class="qph-activity-list">';
	foreach ( $items as $item ) {
		$type_object = get_post_type_object( $item->post_type );
		$type_label  = $type_object ? $type_object->labels->singular_name : $item->post_type;
		$edit_link   = get_edit_post_link( $item->ID );
		$title       = get_the_title( $item );
		if ( '' === trim( wp_strip_all_tags( $title ) ) ) {
			$title = '(không tiêu đề)';
		}

		echo '<li>';
		echo '<span class="qph-activity-list__type">' . esc_html( $type_label ) . '</span>';
		if ( $edit_link ) {
			echo '<a class="qph-activity-list__title" href="' . esc_url( $edit_link ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo '<strong class="qph-activity-list__title">' . esc_html( $title ) . '</strong>';
		}
		echo '<time class="qph-activity-list__time" datetime="' . esc_attr( get_the_date( DATE_W3C, $item ) ) . '">' . esc_html( get_the_date( 'd/m/Y H:i', $item ) ) . '</time>';
		echo '</li>';
	}
	echo '</ul>';
}
