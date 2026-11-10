<?php
/**
 * Jobs (qp_job) — renderer + seed.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Decode job text that was imported as escaped UTF-8 bytes (xe6... / \xe6...).
 */
function qp_holdings_job_decode_escaped_utf8( string $text ): string {
	return function_exists( 'qp_holdings_decode_escaped_utf8_text' ) ? qp_holdings_decode_escaped_utf8_text( $text ) : $text;
}

function qp_holdings_job_decode_title_filter( string $title, int $post_id = 0 ): string {
	if ( $post_id && 'qp_job' === get_post_type( $post_id ) ) {
		return qp_holdings_job_decode_escaped_utf8( $title );
	}

	return $title;
}
add_filter( 'the_title', 'qp_holdings_job_decode_title_filter', 10, 2 );

function qp_holdings_job_decode_single_post_title_filter( string $title, $post ): string {
	if ( $post instanceof WP_Post && 'qp_job' === $post->post_type ) {
		return qp_holdings_job_decode_escaped_utf8( $title );
	}

	return $title;
}
add_filter( 'single_post_title', 'qp_holdings_job_decode_single_post_title_filter', 10, 2 );

function qp_holdings_job_decode_document_title_parts( array $title ): array {
	if ( is_singular( 'qp_job' ) && isset( $title['title'] ) ) {
		$title['title'] = qp_holdings_job_decode_escaped_utf8( (string) $title['title'] );
	}

	return $title;
}
add_filter( 'document_title_parts', 'qp_holdings_job_decode_document_title_parts' );

function qp_holdings_job_decode_current_job_title_string( string $title ): string {
	return is_singular( 'qp_job' ) ? qp_holdings_job_decode_escaped_utf8( $title ) : $title;
}
add_filter( 'pre_get_document_title', 'qp_holdings_job_decode_current_job_title_string', 99 );
add_filter( 'rank_math/frontend/title', 'qp_holdings_job_decode_current_job_title_string', 99 );
add_filter( 'rank_math/opengraph/facebook/title', 'qp_holdings_job_decode_current_job_title_string', 99 );
add_filter( 'rank_math/opengraph/twitter/title', 'qp_holdings_job_decode_current_job_title_string', 99 );

/**
 * Read a scalar job ACF/meta field as trimmed text.
 */
function qp_holdings_job_text_field( int $post_id, string $field ): string {
	$value = function_exists( 'qp_holdings_acf_or_meta' ) ? qp_holdings_acf_or_meta( $post_id, $field, $field ) : get_post_meta( $post_id, $field, true );
	if ( is_array( $value ) || is_object( $value ) ) {
		return '';
	}
	$value = preg_replace( '#<br\s*/?>#i', "\n", (string) $value );
	$value = trim( qp_holdings_job_decode_escaped_utf8( (string) $value ) );

	$translation_fields = array(
		'department' => 'dept',
		'location'   => 'loc',
		'level'      => 'level',
		'experience' => 'experience',
		'salary'     => 'salary',
	);
	if ( isset( $translation_fields[ $field ] ) && function_exists( 'qp_holdings_seeded_job_translation_for_post' ) ) {
		$translated = qp_holdings_seeded_job_translation_for_post( $post_id, $translation_fields[ $field ], $value );
		$value      = qp_holdings_job_decode_escaped_utf8( $translated );
	}

	return $value;
}

/**
 * Public display title for job cards/detail pages.
 */
function qp_holdings_job_display_title( WP_Post $post ): string {
	$title = qp_holdings_job_text_field( (int) $post->ID, 'job_display_title' );
	return $title ? $title : qp_holdings_job_decode_escaped_utf8( get_the_title( $post ) );
}

/**
 * Display title with the quantity suffix used by the recruitment detail design.
 */
function qp_holdings_job_title_with_quantity( WP_Post $post ): string {
	$title    = qp_holdings_job_display_title( $post );
	$quantity = qp_holdings_job_text_field( (int) $post->ID, 'quantity' );

	if ( ! $quantity || false !== stripos( $title, 'Số lượng' ) || false !== stripos( $title, 'Quantity' ) || false !== stripos( $title, '数量' ) ) {
		return $title;
	}

	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$fmt  = '%s (Số lượng: %s)';
	if ( 'en' === $lang ) {
		$fmt = '%s (Quantity: %s)';
	} elseif ( 'zh' === $lang ) {
		$fmt = '%s (数量: %s)';
	}

	return sprintf( $fmt, $title, $quantity );
}

/**
 * Hero image for a job detail page.
 */
function qp_holdings_job_hero_image_url( int $post_id ): string {
	$image = function_exists( 'qp_holdings_acf_or_meta' ) ? qp_holdings_acf_or_meta( $post_id, 'job_hero_image', 'job_hero_image' ) : get_post_meta( $post_id, 'job_hero_image', true );
	$url   = function_exists( 'qp_holdings_image_url' ) ? qp_holdings_image_url( $image, $post_id, 'full' ) : '';

	return $url ? $url : get_template_directory_uri() . '/assets/site/careers-hero-team-5fdb60.png';
}

/**
 * Short job summary sourced from ACF, then excerpt as editor-managed fallback.
 */
function qp_holdings_job_summary( WP_Post $post ): string {
	$summary = qp_holdings_job_text_field( (int) $post->ID, 'job_summary' );
	if ( $summary ) {
		return $summary;
	}
	$excerpt = get_the_excerpt( $post );
	return is_string( $excerpt ) ? trim( qp_holdings_job_decode_escaped_utf8( wp_strip_all_tags( $excerpt ) ) ) : '';
}

/**
 * Employment type label from the ACF select value.
 */
function qp_holdings_job_employment_type_label( string $employment_type ): string {
	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$labels = array(
		'full-time'  => array( 'vi' => 'Toàn thời gian', 'en' => 'Full-time', 'zh' => '全职' ),
		'part-time'  => array( 'vi' => 'Bán thời gian', 'en' => 'Part-time', 'zh' => '兼职' ),
		'internship' => array( 'vi' => 'Thực tập', 'en' => 'Internship', 'zh' => '实习' ),
		'contract'   => array( 'vi' => 'Hợp đồng', 'en' => 'Contract', 'zh' => '合同' ),
	);

	return $labels[ $employment_type ][ $lang ] ?? $labels[ $employment_type ]['vi'] ?? '';
}

/**
 * Job apply target from ACF URL/email, falling back to the contact page.
 */
function qp_holdings_job_apply_link( int $post_id, string $title ): string {
	$apply_url   = qp_holdings_job_text_field( $post_id, 'apply_url' );
	$apply_email = qp_holdings_job_text_field( $post_id, 'apply_email' );

	if ( $apply_url ) {
		return $apply_url;
	}
	if ( $apply_email ) {
		$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
		$subject_prefix = 'Ứng tuyển: ';
		if ( 'en' === $lang ) {
			$subject_prefix = 'Application: ';
		} elseif ( 'zh' === $lang ) {
			$subject_prefix = '应聘：';
		}
		return 'mailto:' . $apply_email . '?subject=' . rawurlencode( $subject_prefix . wp_strip_all_tags( $title ) );
	}
	return function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'contact' ) : home_url( '/lien-he/' );
}

/**
 * Detail sections for a job, sourced from ACF fields only.
 *
 * @return array<int, array{title:string,text:string}>
 */
function qp_holdings_job_detail_sections( int $post_id ): array {
	$post       = get_post( $post_id );
	$title      = $post instanceof WP_Post ? qp_holdings_job_title_with_quantity( $post ) : get_the_title( $post_id );
	$department = qp_holdings_job_text_field( $post_id, 'department' );
	$reports_to = qp_holdings_job_text_field( $post_id, 'reports_to' );
	$workplace  = qp_holdings_job_text_field( $post_id, 'workplace' );
	$general    = qp_holdings_job_text_field( $post_id, 'job_general_info' );
	$lang       = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

	$labels = array(
		'vi' => array(
			'general'          => '1. Thông tin chung',
			'goal'             => '2. Mục tiêu công việc',
			'responsibilities' => '3. Nhiệm vụ và Trách nhiệm chính',
			'requirements'     => '4. Yêu cầu công việc',
			'benefits'         => '5. Quyền lợi được hưởng',
			'position'         => '- Vị trí chức danh: ',
			'department'       => '- Bộ phận làm việc: ',
			'reports_to'       => '- Quản lý trực tiếp: ',
			'workplace'        => '- Địa điểm làm việc: ',
		),
		'en' => array(
			'general'          => '1. General information',
			'goal'             => '2. Job objective',
			'responsibilities' => '3. Key duties and responsibilities',
			'requirements'     => '4. Job requirements',
			'benefits'         => '5. Benefits',
			'position'         => '- Position title: ',
			'department'       => '- Department: ',
			'reports_to'       => '- Reports to: ',
			'workplace'        => '- Workplace: ',
		),
		'zh' => array(
			'general'          => '1. 基本信息',
			'goal'             => '2. 工作目标',
			'responsibilities' => '3. 主要职责',
			'requirements'     => '4. 任职要求',
			'benefits'         => '5. 福利待遇',
			'position'         => '- 职位名称：',
			'department'       => '- 部门：',
			'reports_to'       => '- 直属上级：',
			'workplace'        => '- 工作地点：',
		),
	);
	$label = $labels[ $lang ] ?? $labels['vi'];

	if ( ! $general ) {
		$lines = array();
		if ( $title ) {
			$lines[] = $label['position'] . $title;
		}
		if ( $department ) {
			$lines[] = $label['department'] . $department;
		}
		if ( $reports_to ) {
			$lines[] = $label['reports_to'] . $reports_to;
		}
		if ( $workplace ) {
			$lines[] = $label['workplace'] . $workplace;
		}
		$general = implode( "\n", $lines );
	}

	$seeded_sections = ( $post instanceof WP_Post && function_exists( 'qp_holdings_job_seeded_content_sections' ) ) ? qp_holdings_job_seeded_content_sections( $post ) : array();
	$responsibilities = $seeded_sections['responsibilities'] ?? ( qp_holdings_job_text_field( $post_id, 'job_main_responsibilities' ) ?: qp_holdings_job_text_field( $post_id, 'responsibilities' ) );
	$requirements     = $seeded_sections['requirements'] ?? ( qp_holdings_job_text_field( $post_id, 'job_requirements_detail' ) ?: qp_holdings_job_text_field( $post_id, 'requirements' ) );
	$benefits         = $seeded_sections['benefits'] ?? ( qp_holdings_job_text_field( $post_id, 'job_benefits_detail' ) ?: qp_holdings_job_text_field( $post_id, 'benefits' ) );

	return array(
		array( 'title' => $label['general'], 'text' => $general ),
		array( 'title' => $label['goal'], 'text' => qp_holdings_job_text_field( $post_id, 'job_goal' ) ),
		array( 'title' => $label['responsibilities'], 'text' => $responsibilities ),
		array( 'title' => $label['requirements'], 'text' => $requirements ),
		array( 'title' => $label['benefits'], 'text' => $benefits ),
	);
}

function qp_holdings_job_seeded_content_sections( WP_Post $post ): array {
	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	if ( 'vi' === $lang || ! function_exists( 'qp_holdings_seeded_job_translation_for_post' ) ) {
		return array();
	}

	$content = qp_holdings_job_decode_escaped_utf8( qp_holdings_seeded_job_translation_for_post( $post, 'content', '' ) );
	if ( '' === $content || ! preg_match_all( '#<h2[^>]*>(.*?)</h2>\s*<ul[^>]*>(.*?)</ul>#is', $content, $matches, PREG_SET_ORDER ) ) {
		return array();
	}

	$sections = array();
	$keys     = array( 'responsibilities', 'requirements', 'benefits' );
	foreach ( $matches as $index => $match ) {
		$key = $keys[ $index ] ?? '';
		if ( ! $key ) {
			continue;
		}

		$items = array();
		if ( preg_match_all( '#<li[^>]*>(.*?)</li>#is', $match[2], $li_matches ) ) {
			foreach ( $li_matches[1] as $item ) {
				$item = trim( wp_strip_all_tags( html_entity_decode( $item, ENT_QUOTES, 'UTF-8' ) ) );
				if ( $item ) {
					$items[] = '- ' . $item;
				}
			}
		}

		if ( $items ) {
			$sections[ $key ] = implode( "\n", $items );
		}
	}

	return $sections;
}

/**
 * Convert ACF textarea content to safe Figma-style body HTML.
 */
function qp_holdings_job_detail_text_html( string $text ): string {
	$text = trim( $text );
	if ( '' === $text ) {
		return '';
	}

	$text = str_replace( array( '{ts1}', '{/ts1}' ), '', $text );
	$html = esc_html( $text );
	$html = preg_replace( '/\*\*(.*?)\*\*/u', '<strong>$1</strong>', $html );
	return nl2br( (string) $html, false );
}

/**
 * Related job posts selected in ACF first, then filled from recent jobs.
 *
 * @return WP_Post[]
 */
function qp_holdings_related_jobs( int $post_id, int $limit = 2 ): array {
	$related = function_exists( 'qp_holdings_acf_or_meta' ) ? qp_holdings_acf_or_meta( $post_id, 'related_jobs', 'related_jobs' ) : get_post_meta( $post_id, 'related_jobs', true );
	$posts   = array();
	$seen    = array( $post_id => true );

	if ( ! is_array( $related ) ) {
		$related = $related ? array( $related ) : array();
	}

	foreach ( $related as $item ) {
		$related_post = null;
		if ( $item instanceof WP_Post ) {
			$related_post = $item;
		} elseif ( is_numeric( $item ) ) {
			$related_post = get_post( (int) $item );
		}

		if ( ! $related_post instanceof WP_Post || 'qp_job' !== $related_post->post_type || 'publish' !== $related_post->post_status || isset( $seen[ $related_post->ID ] ) ) {
			continue;
		}

		$posts[]                  = $related_post;
		$seen[ $related_post->ID ] = true;

		if ( count( $posts ) >= $limit ) {
			return $posts;
		}
	}

	$query_args = array(
		'post_type'           => 'qp_job',
		'post_status'         => 'publish',
		'posts_per_page'      => $limit - count( $posts ),
		'post__not_in'        => array_keys( $seen ),
		'ignore_sticky_posts' => true,
		'orderby'             => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	);
	$query = function_exists( 'qp_holdings_wp_query_with_language_fallback' ) ? qp_holdings_wp_query_with_language_fallback( $query_args ) : new WP_Query( $query_args );

	foreach ( $query->posts as $query_post ) {
		if ( $query_post instanceof WP_Post ) {
			$posts[] = $query_post;
		}
	}
	wp_reset_postdata();

	return $posts;
}

/**
 * Redirect helper for the job application form.
 */
function qp_holdings_job_application_redirect( int $job_id, string $status ): void {
	$base = $job_id ? get_permalink( $job_id ) : home_url( '/tuyen-dung/' );
	if ( ! $base ) {
		$base = home_url( '/tuyen-dung/' );
	}

	wp_safe_redirect( add_query_arg( 'application', $status, $base ) . '#job-application' );
	exit;
}

/**
 * Send a submitted recruitment application to the configured job email.
 */
function qp_holdings_handle_job_application_submit(): void {
	$job_id = isset( $_POST['job_id'] ) ? absint( wp_unslash( $_POST['job_id'] ) ) : 0;

	if ( ! isset( $_POST['qp_job_application_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qp_job_application_nonce'] ) ), 'qp_job_application_' . $job_id ) ) {
		qp_holdings_job_application_redirect( $job_id, 'error' );
	}

	if ( isset( $_POST['website'] ) && '' !== trim( (string) wp_unslash( $_POST['website'] ) ) ) {
		qp_holdings_job_application_redirect( $job_id, 'sent' );
	}

	if ( ! qp_holdings_turnstile_verify() ) {
		qp_holdings_job_application_redirect( $job_id, 'security-error' );
	}

	$job = get_post( $job_id );
	if ( ! $job instanceof WP_Post || 'qp_job' !== $job->post_type ) {
		qp_holdings_job_application_redirect( 0, 'error' );
	}

	$name                 = qp_holdings_submission_post_text( 'applicant_name' );
	$email                = qp_holdings_submission_post_email( 'applicant_email' );
	$phone                = qp_holdings_submission_post_text( 'applicant_phone' );
	$phone_country        = function_exists( 'qp_holdings_submission_phone_country' ) ? qp_holdings_submission_phone_country( 'applicant_phone_country' ) : 'VN';
	$message              = qp_holdings_submission_post_text( 'applicant_message', true );

	if ( ! $name || ! is_email( $email ) || ! $phone || ! $message ) {
		qp_holdings_job_application_redirect( $job_id, 'invalid' );
	}

	$attachment_ids = array();
	$files       = $_FILES['cv_files'] ?? null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! is_array( $files ) || empty( $files['name'] ) ) {
		qp_holdings_job_application_redirect( $job_id, 'invalid' );
	}

	$names     = is_array( $files['name'] ) ? $files['name'] : array( $files['name'] );
	$types     = is_array( $files['type'] ) ? $files['type'] : array( $files['type'] ?? '' );
	$tmp_names = is_array( $files['tmp_name'] ) ? $files['tmp_name'] : array( $files['tmp_name'] ?? '' );
	$errors    = is_array( $files['error'] ) ? $files['error'] : array( $files['error'] ?? UPLOAD_ERR_NO_FILE );
	$sizes     = is_array( $files['size'] ) ? $files['size'] : array( $files['size'] ?? 0 );
	$indexes   = array();

	foreach ( $names as $index => $filename ) {
		if ( '' !== (string) $filename && UPLOAD_ERR_NO_FILE !== (int) ( $errors[ $index ] ?? UPLOAD_ERR_NO_FILE ) ) {
			$indexes[] = (int) $index;
		}
	}

	if ( empty( $indexes ) || count( $indexes ) > 3 ) {
		qp_holdings_job_application_redirect( $job_id, 'invalid-file' );
	}

	foreach ( $indexes as $index ) {
		if ( UPLOAD_ERR_OK !== (int) ( $errors[ $index ] ?? UPLOAD_ERR_NO_FILE ) || (int) ( $sizes[ $index ] ?? 0 ) > 20 * MB_IN_BYTES ) {
			if ( function_exists( 'qp_holdings_delete_submission_attachments' ) ) {
				qp_holdings_delete_submission_attachments( $attachment_ids );
			}
			qp_holdings_job_application_redirect( $job_id, 'invalid-file' );
		}

		if ( ! function_exists( 'qp_holdings_store_uploaded_submission_file' ) ) {
			qp_holdings_job_application_redirect( $job_id, 'error' );
		}

		$attachment_id = qp_holdings_store_uploaded_submission_file(
			array(
				'name'     => sanitize_file_name( (string) $names[ $index ] ),
				'type'     => (string) ( $types[ $index ] ?? '' ),
				'tmp_name' => (string) ( $tmp_names[ $index ] ?? '' ),
				'error'    => (int) ( $errors[ $index ] ?? UPLOAD_ERR_NO_FILE ),
				'size'     => (int) ( $sizes[ $index ] ?? 0 ),
			)
		);

		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			if ( function_exists( 'qp_holdings_delete_submission_attachments' ) ) {
				qp_holdings_delete_submission_attachments( $attachment_ids );
			}
			qp_holdings_job_application_redirect( $job_id, 'invalid-file' );
		}

		$attachment_ids[] = (int) $attachment_id;
	}

	$job_title = qp_holdings_job_display_title( $job );
	if ( ! function_exists( 'qp_holdings_create_job_application_submission' ) ) {
		if ( function_exists( 'qp_holdings_delete_submission_attachments' ) ) {
			qp_holdings_delete_submission_attachments( $attachment_ids );
		}
		qp_holdings_job_application_redirect( $job_id, 'error' );
	}

	$application_id = qp_holdings_create_job_application_submission(
		$job,
		array(
			'name'                 => $name,
			'email'                => $email,
			'phone'                => $phone,
			'phone_country'        => $phone_country,
			'message'              => $message,
		),
		$attachment_ids
	);

	if ( is_wp_error( $application_id ) || ! $application_id ) {
		if ( function_exists( 'qp_holdings_delete_submission_attachments' ) ) {
			qp_holdings_delete_submission_attachments( $attachment_ids );
		}
		qp_holdings_job_application_redirect( $job_id, 'error' );
	}

	if ( function_exists( 'qp_holdings_set_submission_attachment_parent' ) ) {
		qp_holdings_set_submission_attachment_parent( (int) $application_id, $attachment_ids );
	}

	$to        = qp_holdings_job_text_field( $job_id, 'apply_email' );
	$to        = is_email( $to ) ? $to : get_option( 'admin_email' );

	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$subject_fmt = 'Ứng tuyển: %s - %s';
	$body_fmt = "Vị trí: %s\nĐường dẫn: %s\n\nHọ tên: %s\nEmail: %s\nSố điện thoại: %s\n\nMô tả bản thân:\n%s";
	if ( 'en' === $lang ) {
		$subject_fmt = 'Job Application: %s - %s';
		$body_fmt = "Position: %s\nLink: %s\n\nFull Name: %s\nEmail: %s\nPhone Number: %s\n\nSelf-description:\n%s";
	} elseif ( 'zh' === $lang ) {
		$subject_fmt = '职位申请：%s - %s';
		$body_fmt = "应聘职位: %s\n详情链接: %s\n\n姓名: %s\n电子邮箱: %s\n电话号码: %s\n\n自我介绍:\n%s";
	}

	$formatted_phone = function_exists( 'qp_holdings_format_phone_number' ) ? qp_holdings_format_phone_number( $phone, $phone_country ) : '+84 ' . $phone;
	$subject   = sprintf( $subject_fmt, $job_title, $name );
	$body      = sprintf(
		$body_fmt,
		$job_title,
		get_permalink( $job_id ),
		$name,
		$email,
		$formatted_phone,
		$message
	);
	$headers   = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$email_attachments = function_exists( 'qp_holdings_submission_attachment_paths' ) ? qp_holdings_submission_attachment_paths( $attachment_ids ) : array();
	$sent              = wp_mail( $to, $subject, $body, $headers, $email_attachments );
	update_post_meta( (int) $application_id, '_qp_email_sent', $sent ? '1' : '0' );

	qp_holdings_job_application_redirect( $job_id, 'sent' );
}
add_action( 'admin_post_qp_job_application_submit', 'qp_holdings_handle_job_application_submit' );
add_action( 'admin_post_nopriv_qp_job_application_submit', 'qp_holdings_handle_job_application_submit' );

/**
 * Render the full jobs section: filters + accordion-style list grouped by department.
 * Returns true when at least one job is rendered.
 */
function qp_holdings_render_jobs_section(): bool {
	$paged      = max( 1, (int) get_query_var( 'paged' ) );
	$department = qp_holdings_get_query_string( 'dept' );
	$location   = qp_holdings_get_query_string( 'loc' );
	$level      = qp_holdings_get_query_string( 'level' );

	$q_args = array(
		'post_type'      => 'qp_job',
		'posts_per_page' => 18,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'OR',
			array( 'key' => 'deadline', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'deadline', 'compare' => '>=', 'value' => current_time( 'Y-m-d' ), 'type' => 'DATE' ),
			array( 'key' => 'deadline', 'compare' => '=', 'value' => '' ),
		),
	);
	$tax = array();
	if ( $department ) {
		$tax[] = array( 'taxonomy' => 'qp_job_department', 'field' => 'slug', 'terms' => $department );
	}
	if ( $location ) {
		$tax[] = array( 'taxonomy' => 'qp_job_location', 'field' => 'slug', 'terms' => $location );
	}
	if ( $level ) {
		$tax[] = array( 'taxonomy' => 'qp_job_level', 'field' => 'slug', 'terms' => $level );
	}
	if ( $tax ) {
		$q_args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax );
	}

	$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' ) ? qp_holdings_wp_query_with_language_fallback( $q_args ) : new WP_Query( $q_args );

	echo '<div class="qp-section qp-jobs">';

	echo '<div class="qp-filter-group">';
	echo '<div class="qp-filter-group__label">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'department' ) : 'Phòng ban' ) . '</div>';
	qp_holdings_taxonomy_filter( 'qp_job_department', 'dept' );
	echo '</div>';

	echo '<div class="qp-filter-group">';
	echo '<div class="qp-filter-group__label">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'location' ) : 'Địa điểm' ) . '</div>';
	qp_holdings_taxonomy_filter( 'qp_job_location', 'loc' );
	echo '</div>';

	echo '<div class="qp-filter-group">';
	echo '<div class="qp-filter-group__label">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'level' ) : 'Cấp bậc' ) . '</div>';
	qp_holdings_taxonomy_filter( 'qp_job_level', 'level' );
	echo '</div>';

	if ( ! $q->have_posts() ) {
		echo '<p class="qp-empty">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'no_jobs' ) : 'Hiện chưa có vị trí phù hợp với bộ lọc. Vui lòng quay lại sau hoặc thử bộ lọc khác.' ) . '</p>';
		echo '</div>';
		wp_reset_postdata();
		return false;
	}

	echo '<div class="qp-job-list-shell"><div class="qp-job-list">';
	while ( $q->have_posts() ) {
		$q->the_post();
		echo qp_holdings_job_card_html( get_post() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div></div>';

	qp_holdings_pagination( $q );

	echo '</div>';
	wp_reset_postdata();
	return true;
}

/**
 * Job card (used on archive list).
 */
function qp_holdings_job_card_html( WP_Post $post ): string {
	$id  = $post->ID;
	$url = get_permalink( $id );

	/* Raw meta (stored in Vietnamese) */
	$dept_raw       = qp_holdings_acf_or_meta( $id, 'department', 'department' )
		?: qp_holdings_first_term_name( $id, 'qp_job_department' );
	$loc_raw        = qp_holdings_acf_or_meta( $id, 'location', 'location' )
		?: qp_holdings_first_term_name( $id, 'qp_job_location' );
	$level_raw      = qp_holdings_acf_or_meta( $id, 'level', 'level' )
		?: qp_holdings_first_term_name( $id, 'qp_job_level' );
	$salary_raw     = qp_holdings_acf_or_meta( $id, 'salary', 'salary' );
	$experience_raw = qp_holdings_acf_or_meta( $id, 'experience', 'experience' );

	/* Translated meta */
	$dept       = function_exists( 'qp_holdings_seeded_job_translation_for_post' )
		? qp_holdings_seeded_job_translation_for_post( $post, 'dept', $dept_raw )
		: $dept_raw;
	$loc        = function_exists( 'qp_holdings_seeded_job_translation_for_post' )
		? qp_holdings_seeded_job_translation_for_post( $post, 'loc', $loc_raw )
		: $loc_raw;
	$level      = function_exists( 'qp_holdings_seeded_job_translation_for_post' )
		? qp_holdings_seeded_job_translation_for_post( $post, 'level', $level_raw )
		: $level_raw;
	$salary     = function_exists( 'qp_holdings_seeded_job_translation_for_post' )
		? qp_holdings_seeded_job_translation_for_post( $post, 'salary', $salary_raw )
		: $salary_raw;
	$experience = function_exists( 'qp_holdings_seeded_job_translation_for_post' )
		? qp_holdings_seeded_job_translation_for_post( $post, 'experience', $experience_raw )
		: $experience_raw;
	$dept       = qp_holdings_job_decode_escaped_utf8( (string) $dept );
	$loc        = qp_holdings_job_decode_escaped_utf8( (string) $loc );
	$level      = qp_holdings_job_decode_escaped_utf8( (string) $level );
	$salary     = qp_holdings_job_decode_escaped_utf8( (string) $salary );
	$experience = qp_holdings_job_decode_escaped_utf8( (string) $experience );

	$emp_type    = qp_holdings_acf_or_meta( $id, 'employment_type', 'employment_type' );
	$deadline    = qp_holdings_acf_or_meta( $id, 'deadline', 'deadline' );

	$emp_type_label = array(
		'full-time'  => array( 'vi' => 'Toàn thời gian', 'en' => 'Full-time', 'zh' => '全职' ),
		'part-time'  => array( 'vi' => 'Bán thời gian', 'en' => 'Part-time', 'zh' => '兼职' ),
		'internship' => array( 'vi' => 'Thực tập', 'en' => 'Internship', 'zh' => '实习' ),
		'contract'   => array( 'vi' => 'Hợp đồng', 'en' => 'Contract', 'zh' => '合同' ),
	);
	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$emp_text = is_string( $emp_type ) && isset( $emp_type_label[ $emp_type ] ) ? ( $emp_type_label[ $emp_type ][ $lang ] ?? $emp_type_label[ $emp_type ]['vi'] ) : '';

	$deadline_fmt = qp_holdings_format_date( $deadline );

	$out  = '<article class="qp-job">';
	$out .= '<div class="qp-job__head">';
	$out .= '<div class="qp-job__head-main">';
	if ( $dept ) {
		$out .= '<div class="qp-job__dept">' . esc_html( $dept ) . '</div>';
	}
	$out .= '<h3 class="qp-job__title"><a href="' . esc_url( $url ) . '">' . esc_html( qp_holdings_job_decode_escaped_utf8( get_the_title( $id ) ) ) . '</a></h3>';
	$out .= '<div class="qp-job__meta">';
	if ( $loc ) {
		$out .= '<span class="qp-job__meta-item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' . esc_html( $loc ) . '</span>';
	}
	if ( $level ) {
		$out .= '<span class="qp-job__meta-item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z"/></svg>' . esc_html( $level ) . '</span>';
	}
	if ( $emp_text ) {
		$out .= '<span class="qp-job__meta-item"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="6" width="18" height="14" rx="2"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>' . esc_html( $emp_text ) . '</span>';
	}
	if ( $experience ) {
		$out .= '<span class="qp-job__meta-item">' . esc_html( $experience ) . '</span>';
	}
	$out .= '</div>';
	$out .= '</div>';

	$out .= '<div class="qp-job__head-side">';
	if ( $salary ) {
		$out .= '<div class="qp-job__salary">' . esc_html( $salary ) . '</div>';
	}
	if ( $deadline_fmt ) {
		$out .= '<div class="qp-job__deadline">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'deadline' ) : 'Hạn:' ) . ' ' . esc_html( $deadline_fmt ) . '</div>';
	}
	$out .= '<a href="' . esc_url( $url ) . '" class="qp-job__apply">' . esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'view_apply' ) : 'Xem & ứng tuyển' ) . '</a>';
	$out .= '</div>';
	$out .= '</div>';
	$out .= '</article>';
	return $out;
}

/* ===================================================================
 * Seed: 10 jobs across departments / levels.
 * =================================================================== */
function qp_holdings_seed_jobs( bool $force = false ): void {
	$today    = current_time( 'Y-m-d' );
	$plus30   = wp_date( 'Y-m-d', strtotime( '+30 days', strtotime( $today ) ) );
	$plus45   = wp_date( 'Y-m-d', strtotime( '+45 days', strtotime( $today ) ) );
	$plus60   = wp_date( 'Y-m-d', strtotime( '+60 days', strtotime( $today ) ) );

	$items = array(
		array(
			'slug' => 'truong-phong-phan-tich-dau-tu',
			'title' => 'Trưởng phòng Phân tích đầu tư',
			'department' => 'Khối Đầu tư',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Senior',
			'emp_type' => 'full-time',
			'experience' => '5+ năm kinh nghiệm tương đương',
			'salary' => '30 — 50 triệu/tháng + thưởng dự án',
			'deadline' => $plus45,
			'responsibilities' => "Xây dựng và quản lý danh mục cơ hội đầu tư của tập đoàn\nLập mô hình tài chính, phân tích định giá và báo cáo đầu tư\nQuản lý đội ngũ phân tích đầu tư trẻ\nTrình bày khuyến nghị đầu tư cho Ban điều hành",
			'requirements' => "Tốt nghiệp Đại học các ngành Tài chính, Kinh tế, MBA ưu tiên\nCFA Level 2/3, kinh nghiệm tại quỹ đầu tư hoặc IB\nThành thạo định giá DCF, comparable, transaction multiples\nKỹ năng quản lý và lãnh đạo nhóm",
			'benefits' => "Lương thưởng cạnh tranh 14–17 tháng/năm\nGói cổ phiếu ESOP\nChế độ chăm sóc sức khoẻ cao cấp\nNgân sách đào tạo CFA / MBA tài trợ",
			'tax' => array( 'qp_job_department' => array( 'khoi-dau-tu' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'senior' ) ),
		),
		array(
			'slug' => 'chuyen-vien-ir',
			'title' => 'Chuyên viên Quan hệ Nhà đầu tư (IR)',
			'department' => 'Khối Đầu tư',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Trung cấp',
			'emp_type' => 'full-time',
			'experience' => '2–4 năm trong lĩnh vực IR / Equity Research',
			'salary' => 'Thoả thuận',
			'deadline' => $plus60,
			'responsibilities' => "Phụ trách công bố thông tin định kỳ và bất thường theo quy định\nXây dựng presentation, báo cáo, newsletter cho nhà đầu tư\nTổ chức non-deal roadshow, AGM, gặp gỡ phân tích viên\nTheo dõi cổ phiếu, peer benchmark, sentiment thị trường",
			'requirements' => "Tốt nghiệp Đại học Tài chính, Kinh tế, Báo chí\nTiếng Anh tốt (TOEIC 800+ hoặc IELTS 7.0)\nThành thạo PowerPoint, Excel, Bloomberg/Refinitiv ưu tiên\nKỹ năng viết tốt, kỹ năng trình bày trước đám đông",
			'benefits' => "Lương thưởng cạnh tranh\nCơ hội tiếp xúc Ban điều hành & nhà đầu tư tổ chức\nĐào tạo nội bộ + tài trợ CFA",
			'tax' => array( 'qp_job_department' => array( 'khoi-dau-tu' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'mid' ) ),
		),
		array(
			'slug' => 'senior-project-manager-bds',
			'title' => 'Senior Project Manager (Bất động sản)',
			'department' => 'Khối Dự án',
			'location' => 'Bình Dương',
			'level' => 'Senior',
			'emp_type' => 'full-time',
			'experience' => '7+ năm kinh nghiệm quản lý dự án BĐS quy mô lớn',
			'salary' => '40 — 60 triệu/tháng',
			'deadline' => $plus45,
			'responsibilities' => "Quản lý tổng tiến độ, chi phí, chất lượng và an toàn của dự án\nĐiều phối các nhà thầu thiết kế, thi công, giám sát\nLập báo cáo định kỳ cho Ban giám đốc\nKiểm soát và phê duyệt các thay đổi thiết kế / chi phí",
			'requirements' => "Tốt nghiệp Đại học Xây dựng, Kiến trúc hoặc tương đương\nChứng chỉ PMP hoặc tương đương ưu tiên\nKinh nghiệm dự án phức hợp ≥ 50.000 m² sàn\nThành thạo MS Project, Primavera P6",
			'benefits' => "Lương thưởng theo dự án\nXe đưa đón tuyến TP.HCM – Bình Dương\nGói cổ phiếu ESOP cho cấp quản lý dự án",
			'tax' => array( 'qp_job_department' => array( 'khoi-du-an' ), 'qp_job_location' => array( 'binh-duong' ), 'qp_job_level' => array( 'senior' ) ),
		),
		array(
			'slug' => 'ke-toan-truong',
			'title' => 'Kế toán trưởng',
			'department' => 'Khối Tài chính',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Senior',
			'emp_type' => 'full-time',
			'experience' => '8+ năm tại doanh nghiệp niêm yết',
			'salary' => 'Thoả thuận',
			'deadline' => $plus30,
			'responsibilities' => "Quản lý toàn bộ hoạt động kế toán — thuế — kiểm toán\nKý báo cáo tài chính riêng lẻ và hợp nhất\nLàm việc với Big4 và cơ quan thuế\nTham mưu cho CFO về quản trị tài chính",
			'requirements' => "Tốt nghiệp Đại học Kế toán / Kiểm toán\nChứng chỉ Kế toán trưởng, CPA Việt Nam, ACCA ưu tiên\nKinh nghiệm IFRS, hợp nhất, niêm yết\nKỹ năng quản lý 10+ nhân viên",
			'benefits' => "Lương cao cấp, thưởng cuối năm + ESOP\nĐược tài trợ ACCA / CPA cho thành viên team",
			'tax' => array( 'qp_job_department' => array( 'khoi-tai-chinh' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'senior' ) ),
		),
		array(
			'slug' => 'chuyen-vien-phap-che-doanh-nghiep',
			'title' => 'Chuyên viên Pháp chế Doanh nghiệp',
			'department' => 'Khối Pháp chế',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Trung cấp',
			'emp_type' => 'full-time',
			'experience' => '3+ năm tại Tập đoàn / Công ty luật',
			'salary' => '20 — 30 triệu/tháng',
			'deadline' => $plus60,
			'responsibilities' => "Soạn thảo, rà soát hợp đồng và văn bản pháp lý\nTư vấn pháp luật doanh nghiệp, M&A, lao động\nĐại diện làm việc với cơ quan nhà nước\nLưu trữ và quản lý hồ sơ pháp lý doanh nghiệp",
			'requirements' => "Tốt nghiệp Đại học Luật\nChứng chỉ hành nghề Luật sư ưu tiên\nKinh nghiệm BĐS, đầu tư hoặc M&A là điểm cộng\nTiếng Anh pháp lý tốt",
			'benefits' => "Lương cạnh tranh\nMôi trường tiếp xúc các deal đầu tư & M&A\nTài trợ chứng chỉ Luật sư",
			'tax' => array( 'qp_job_department' => array( 'khoi-phap-che' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'mid' ) ),
		),
		array(
			'slug' => 'talent-acquisition-lead',
			'title' => 'Talent Acquisition Lead',
			'department' => 'Khối Nhân sự',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Senior',
			'emp_type' => 'full-time',
			'experience' => '5+ năm tuyển dụng cấp Senior — Manager',
			'salary' => '25 — 40 triệu/tháng',
			'deadline' => $plus45,
			'responsibilities' => "Quản lý chiến lược tuyển dụng tập đoàn (300+ vị trí/năm)\nXây dựng employer branding, headhunt cấp cao\nQuản lý đội ngũ TA Specialist\nKết hợp với COO, BU heads để hoạch định nhân sự",
			'requirements' => "5+ năm tuyển dụng end-to-end\nKinh nghiệm headhunt cấp Senior — Director\nKỹ năng dữ liệu, phân tích pipeline\nTiếng Anh tốt",
			'benefits' => "Lương + KPI bonus + ESOP\nNgân sách employer branding & technology",
			'tax' => array( 'qp_job_department' => array( 'khoi-nhan-su' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'senior' ) ),
		),
		array(
			'slug' => 'tro-ly-tong-giam-doc',
			'title' => 'Trợ lý Tổng Giám đốc',
			'department' => 'Khối Văn phòng',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Trung cấp',
			'emp_type' => 'full-time',
			'experience' => '3+ năm hỗ trợ Ban điều hành',
			'salary' => '18 — 28 triệu/tháng',
			'deadline' => $plus30,
			'responsibilities' => "Hỗ trợ TGĐ trong quản lý lịch làm việc, công văn, hội họp\nSoạn báo cáo, presentation theo yêu cầu\nĐiều phối các cuộc họp Ban điều hành\nTheo dõi tiến độ các sáng kiến chiến lược",
			'requirements' => "Tốt nghiệp Đại học các ngành phù hợp\nKỹ năng viết, trình bày, tổ chức xuất sắc\nTiếng Anh giao tiếp tốt\nThành thạo Office 365, Notion / Asana",
			'benefits' => "Cơ hội học hỏi trực tiếp Ban điều hành\nLương thưởng cạnh tranh",
			'tax' => array( 'qp_job_department' => array( 'khoi-van-phong' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'mid' ) ),
		),
		array(
			'slug' => 'chuyen-vien-truyen-thong-doanh-nghiep',
			'title' => 'Chuyên viên Truyền thông Doanh nghiệp',
			'department' => 'Khối Marketing',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Junior',
			'emp_type' => 'full-time',
			'experience' => '1–3 năm PR / Truyền thông',
			'salary' => '12 — 18 triệu/tháng',
			'deadline' => $plus45,
			'responsibilities' => "Quản lý các kênh truyền thông doanh nghiệp (website, fanpage, LinkedIn)\nLên kế hoạch và viết content quan hệ công chúng\nKết nối báo chí, monitoring truyền thông\nHỗ trợ tổ chức sự kiện",
			'requirements' => "Tốt nghiệp Đại học Báo chí, Truyền thông, Marketing\nKỹ năng viết và biên tập tiếng Việt xuất sắc\nKinh nghiệm content corporate là điểm cộng\nThành thạo Canva, công cụ social analytics",
			'benefits' => "Đào tạo nội bộ + đối tác agency\nMôi trường truyền thông doanh nghiệp niêm yết\nLộ trình phát triển rõ ràng",
			'tax' => array( 'qp_job_department' => array( 'khoi-marketing' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'junior' ) ),
		),
		array(
			'slug' => 'ky-su-ket-cau-cap-cao',
			'title' => 'Kỹ sư Kết cấu Cấp cao',
			'department' => 'Khối Dự án',
			'location' => 'Bình Dương',
			'level' => 'Senior',
			'emp_type' => 'full-time',
			'experience' => '6+ năm kết cấu nhà cao tầng',
			'salary' => '35 — 55 triệu/tháng',
			'deadline' => $plus60,
			'responsibilities' => "Chủ trì thiết kế và kiểm tra kết cấu các dự án cao tầng\nLàm việc với tư vấn nước ngoài & nhà thầu\nĐảm bảo an toàn kết cấu xuyên suốt vòng đời dự án\nQuản lý kỹ sư kết cấu junior",
			'requirements' => "Tốt nghiệp Đại học Xây dựng chuyên ngành Kết cấu\nThành thạo ETABS, SAFE, RAM\nKinh nghiệm dự án 30+ tầng\nKỹ năng đọc tiêu chuẩn ACI, BS, TCVN",
			'benefits' => "Lương + thưởng dự án\nXe đưa đón tuyến TP.HCM – Bình Dương\nCơ hội tham gia các dự án quy mô lớn",
			'tax' => array( 'qp_job_department' => array( 'khoi-du-an' ), 'qp_job_location' => array( 'binh-duong' ), 'qp_job_level' => array( 'senior' ) ),
		),
		array(
			'slug' => 'thuc-tap-sinh-kinh-doanh-bds',
			'title' => 'Thực tập sinh Kinh doanh Bất động sản',
			'department' => 'Khối Kinh doanh',
			'location' => 'TP. Hồ Chí Minh',
			'level' => 'Thực tập sinh',
			'emp_type' => 'internship',
			'experience' => 'Sinh viên năm 3 — năm cuối',
			'salary' => '5 — 7 triệu/tháng',
			'deadline' => $plus30,
			'responsibilities' => "Hỗ trợ nghiên cứu khách hàng, đối thủ, dự án\nTham gia sự kiện mở bán, workshop\nHỗ trợ data sales pipeline\nLàm việc cùng đội ngũ chuyên viên kinh doanh cao cấp",
			'requirements' => "Sinh viên năm 3 — năm cuối ngành Kinh tế, Marketing, BĐS\nTiếng Anh giao tiếp\nNhiệt tình, học hỏi nhanh, kỹ năng làm việc nhóm",
			'benefits' => "Phụ cấp + hoa hồng theo deal\nĐược đào tạo bài bản về sản phẩm BĐS\nCơ hội chuyển full-time sau thực tập",
			'tax' => array( 'qp_job_department' => array( 'khoi-kinh-doanh' ), 'qp_job_location' => array( 'tp-hcm' ), 'qp_job_level' => array( 'intern' ) ),
		),
	);

	foreach ( $items as $item ) {
		$existing = get_page_by_path( $item['slug'], OBJECT, 'qp_job' );
		if ( $existing instanceof WP_Post && ! $force ) {
			continue;
		}

		$content = '';
		if ( $item['responsibilities'] ) {
			$content .= '<h2>Trách nhiệm chính</h2><ul>';
			foreach ( explode( "\n", trim( $item['responsibilities'] ) ) as $line ) {
				$line = trim( $line );
				if ( $line ) {
					$content .= '<li>' . esc_html( $line ) . '</li>';
				}
			}
			$content .= '</ul>';
		}
		if ( $item['requirements'] ) {
			$content .= '<h2>Yêu cầu</h2><ul>';
			foreach ( explode( "\n", trim( $item['requirements'] ) ) as $line ) {
				$line = trim( $line );
				if ( $line ) {
					$content .= '<li>' . esc_html( $line ) . '</li>';
				}
			}
			$content .= '</ul>';
		}
		if ( $item['benefits'] ) {
			$content .= '<h2>Quyền lợi</h2><ul>';
			foreach ( explode( "\n", trim( $item['benefits'] ) ) as $line ) {
				$line = trim( $line );
				if ( $line ) {
					$content .= '<li>' . esc_html( $line ) . '</li>';
				}
			}
			$content .= '</ul>';
		}

		$args = array(
			'post_type'    => 'qp_job',
			'post_status'  => 'publish',
			'post_title'   => $item['title'],
			'post_name'    => $item['slug'],
			'post_content' => $content,
			'post_excerpt' => 'Vị trí ' . $item['title'] . ' — ' . $item['department'] . ' tại ' . $item['location'] . '.',
		);
		if ( $existing instanceof WP_Post ) {
			$args['ID'] = (int) $existing->ID;
		}

		$post_id = wp_insert_post( $args, true );
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( (int) $post_id, 'department', $item['department'] );
		update_post_meta( (int) $post_id, 'location', $item['location'] );
		update_post_meta( (int) $post_id, 'level', $item['level'] );
		update_post_meta( (int) $post_id, 'employment_type', $item['emp_type'] );
		update_post_meta( (int) $post_id, 'experience', $item['experience'] );
		update_post_meta( (int) $post_id, 'salary', $item['salary'] );
		update_post_meta( (int) $post_id, 'deadline', $item['deadline'] );
		update_post_meta( (int) $post_id, 'responsibilities', $item['responsibilities'] );
		update_post_meta( (int) $post_id, 'requirements', $item['requirements'] );
		update_post_meta( (int) $post_id, 'benefits', $item['benefits'] );
		update_post_meta( (int) $post_id, 'apply_email', 'careers@qpholdings.vn' );

		qp_holdings_seed_assign_terms( (int) $post_id, $item['tax'] );
	}
}
