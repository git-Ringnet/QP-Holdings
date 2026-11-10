<?php
/**
 * Frontend form submissions - contact messages and job applications.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keep private submission data visible only to site administrators.
 */
function qp_holdings_submission_capabilities(): array {
	return array(
		'edit_post'              => 'manage_options',
		'read'                   => 'manage_options',
		'read_post'              => 'manage_options',
		'delete_post'            => 'manage_options',
		'edit_posts'             => 'manage_options',
		'edit_others_posts'      => 'manage_options',
		'edit_private_posts'     => 'manage_options',
		'edit_published_posts'   => 'manage_options',
		'delete_posts'           => 'manage_options',
		'delete_others_posts'    => 'manage_options',
		'delete_private_posts'   => 'manage_options',
		'delete_published_posts' => 'manage_options',
		'publish_posts'          => 'manage_options',
		'read_private_posts'     => 'manage_options',
		'create_posts'           => 'do_not_allow',
	);
}

/**
 * Allow Editors and Administrators to view and manage lead submissions (Contact & Consult),
 * while keeping deletion capabilities restricted to Administrators.
 */
function qp_holdings_lead_submission_capabilities(): array {
	return array(
		'edit_post'              => 'edit_others_posts',
		'read'                   => 'edit_others_posts',
		'read_post'              => 'edit_others_posts',
		'delete_post'            => 'manage_options',
		'edit_posts'             => 'edit_others_posts',
		'edit_others_posts'      => 'edit_others_posts',
		'edit_private_posts'     => 'edit_others_posts',
		'edit_published_posts'   => 'edit_others_posts',
		'delete_posts'           => 'manage_options',
		'delete_others_posts'    => 'manage_options',
		'delete_private_posts'   => 'manage_options',
		'delete_published_posts' => 'manage_options',
		'publish_posts'          => 'edit_others_posts',
		'read_private_posts'     => 'edit_others_posts',
		'create_posts'           => 'do_not_allow',
	);
}

/**
 * Register admin-only CPTs used as the submission inboxes.
 */
function qp_holdings_register_submission_cpts(): void {
	$common_args = array(
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_rest'        => false,
		'show_in_admin_bar'   => false,
		'query_var'           => false,
		'rewrite'             => false,
		'can_export'          => false,
		'supports'            => array( 'title' ),
		'capability_type'     => 'post',
		'capabilities'        => qp_holdings_submission_capabilities(),
		'map_meta_cap'        => false,
	);

	register_post_type(
		'qp_contact_msg',
		array_merge(
			$common_args,
			array(
				'label'         => 'Form liên hệ',
				'labels'        => array(
					'name'          => 'Form liên hệ',
					'singular_name' => 'Liên hệ',
					'menu_name'     => 'Form liên hệ',
					'edit_item'     => 'Xem liên hệ',
					'view_item'     => 'Xem liên hệ',
					'search_items'  => 'Tìm liên hệ',
					'not_found'     => 'Chưa có liên hệ nào.',
				),
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-email-alt2',
				'menu_position' => 25,
				'capabilities'  => qp_holdings_lead_submission_capabilities(),
			)
		)
	);

	register_post_type(
		'qp_job_app',
		array_merge(
			$common_args,
			array(
				'label'        => 'Hồ sơ ứng tuyển',
				'labels'       => array(
					'name'          => 'Hồ sơ ứng tuyển',
					'singular_name' => 'Hồ sơ ứng tuyển',
					'menu_name'     => 'Hồ sơ ứng tuyển',
					'edit_item'     => 'Xem hồ sơ ứng tuyển',
					'view_item'     => 'Xem hồ sơ ứng tuyển',
					'search_items'  => 'Tìm hồ sơ ứng tuyển',
					'not_found'     => 'Chưa có hồ sơ ứng tuyển nào.',
				),
				'show_in_menu' => 'edit.php?post_type=qp_job',
			)
		)
	);

	register_post_type(
		'qp_consult_msg',
		array_merge(
			$common_args,
			array(
				'label'         => 'Đăng ký tư vấn',
				'labels'        => array(
					'name'          => 'Đăng ký tư vấn',
					'singular_name' => 'Đăng ký tư vấn',
					'menu_name'     => 'Đăng ký tư vấn',
					'edit_item'     => 'Xem đăng ký tư vấn',
					'view_item'     => 'Xem đăng ký tư vấn',
					'search_items'  => 'Tìm đăng ký tư vấn',
					'not_found'     => 'Chưa có đăng ký tư vấn nào.',
				),
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-phone',
				'menu_position' => 26,
				'capabilities'  => qp_holdings_lead_submission_capabilities(),
			)
		)
	);

	register_post_type(
		'qp_newsletter',
		array_merge(
			$common_args,
			array(
				'label'         => 'Đăng ký bản tin',
				'labels'        => array(
					'name'          => 'Đăng ký bản tin',
					'singular_name' => 'Đăng ký bản tin',
					'menu_name'     => 'Đăng ký bản tin',
					'edit_item'     => 'Xem đăng ký bản tin',
					'view_item'     => 'Xem đăng ký bản tin',
					'search_items'  => 'Tìm đăng ký bản tin',
					'not_found'     => 'Chưa có đăng ký bản tin nào.',
				),
				'show_in_menu'  => true,
				'menu_icon'     => 'dashicons-email',
				'menu_position' => 27,
			)
		)
	);
}
add_action( 'init', 'qp_holdings_register_submission_cpts', 12 );

/**
 * Read a scalar POST value and sanitize it as a text field.
 */
function qp_holdings_submission_post_text( string $key, bool $textarea = false ): string {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}

	$value = wp_unslash( $_POST[ $key ] );
	if ( is_array( $value ) ) {
		return '';
	}

	return $textarea ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
}

/**
 * Read a scalar POST value and sanitize it as an email address.
 */
function qp_holdings_submission_post_email( string $key ): string {
	if ( ! isset( $_POST[ $key ] ) ) {
		return '';
	}

	$value = wp_unslash( $_POST[ $key ] );
	if ( is_array( $value ) ) {
		return '';
	}

	return sanitize_email( $value );
}

/**
 * Labels for the contact form question select.
 */
function qp_holdings_contact_question_labels(): array {
	return array(
		'project'     => 'Thông tin dự án',
		'investor'    => 'Quan hệ nhà đầu tư',
		'recruitment' => 'Tuyển dụng',
		'other'       => 'Khác',
	);
}

/**
 * Supported country codes for phone inputs.
 */
function qp_holdings_phone_country_options(): array {
	return array(
		'VN' => array(
			'code'  => '+84',
			'flag'  => 'assets/site/flag-vietnam-vn.svg',
			'label' => array( 'vi' => 'Việt Nam', 'en' => 'Vietnam', 'zh' => '越南' ),
		),
		'US' => array(
			'code'  => '+1',
			'flag'  => 'assets/site/flag-united-states-us.svg',
			'label' => array( 'vi' => 'Mỹ', 'en' => 'United States', 'zh' => '美国' ),
		),
		'CN' => array(
			'code'  => '+86',
			'flag'  => 'assets/site/flag-china-cn.svg',
			'label' => array( 'vi' => 'Trung Quốc', 'en' => 'China', 'zh' => '中国' ),
		),
	);
}

/**
 * Return a valid country option, defaulting to Vietnam.
 */
function qp_holdings_phone_country_option( string $country ): array {
	$options = qp_holdings_phone_country_options();
	$country = strtoupper( sanitize_key( $country ) );

	return $options[ $country ] ?? $options['VN'];
}

/**
 * Read and validate a submitted phone country key.
 */
function qp_holdings_submission_phone_country( string $key ): string {
	$country = strtoupper( sanitize_key( qp_holdings_submission_post_text( $key ) ) );
	$options = qp_holdings_phone_country_options();

	return array_key_exists( $country, $options ) ? $country : 'VN';
}

/**
 * Render the country-code selector used before phone inputs.
 * Uses intl-tel-input library for dropdown UI.
 */
function qp_holdings_phone_country_select( string $name, string $selected = 'VN' ): string {
	$options  = qp_holdings_phone_country_options();
	$selected = strtoupper( sanitize_key( $selected ) );
	$selected = array_key_exists( $selected, $options ) ? $selected : 'VN';

	return sprintf(
		'<input type="hidden" name="%1$s" value="%2$s" data-phone-country-input>',
		esc_attr( $name ),
		esc_attr( $selected )
	);
}

/**
 * Format a phone number with its selected country code.
 */
function qp_holdings_format_phone_number( string $phone, string $country = 'VN' ): string {
	$phone = trim( $phone );
	if ( '' === $phone ) {
		return '';
	}

	if ( 0 === strpos( $phone, '+' ) ) {
		return $phone;
	}

	$option = qp_holdings_phone_country_option( $country );

	return trim( $option['code'] . ' ' . $phone );
}

/**
 * Format a stored submission phone number, preserving legacy Vietnam records.
 */
function qp_holdings_submission_formatted_phone( int $post_id, string $phone_key, string $country_key ): string {
	$phone   = (string) get_post_meta( $post_id, $phone_key, true );
	$country = (string) get_post_meta( $post_id, $country_key, true );

	return qp_holdings_format_phone_number( $phone, $country ?: 'VN' );
}

/**
 * Redirect back to the contact form with a status message.
 */
function qp_holdings_contact_submission_redirect( string $status ): void {
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'contact' ) : home_url( '/lien-he/' );
	}

	$redirect = preg_replace( '/#.*$/', '', (string) $redirect );
	$redirect = remove_query_arg( 'contact', $redirect );

	wp_safe_redirect( add_query_arg( 'contact', $status, $redirect ) . '#contact-form' );
	exit;
}

/**
 * Persist a contact form submission for admin review.
 *
 * @param array<string, string> $data Sanitized contact payload.
 * @return int|WP_Error
 */
function qp_holdings_create_contact_submission( array $data ) {
	$submitted_at = current_time( 'mysql' );
	$title        = sprintf( '%s - %s', $data['name'], mysql2date( 'd/m/Y H:i', $submitted_at ) );
	$question     = qp_holdings_contact_question_labels()[ $data['question'] ] ?? $data['question'];

	return wp_insert_post(
		array(
			'post_type'    => 'qp_contact_msg',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $data['message'],
			'meta_input'   => array(
				'_qp_contact_name'          => $data['name'],
				'_qp_contact_email'         => $data['email'],
				'_qp_contact_phone'         => $data['phone'],
				'_qp_contact_phone_country' => $data['phone_country'],
				'_qp_contact_question'      => $data['question'],
				'_qp_contact_label'         => $question,
				'_qp_contact_message'       => $data['message'],
				'_qp_submitted_at'          => $submitted_at,
			),
		),
		true
	);
}

/**
 * Send an email notification for a saved contact submission.
 *
 * @param array<string, string> $data Sanitized contact payload.
 */
function qp_holdings_send_contact_notification( array $data ): bool {
	$to = apply_filters( 'qp_holdings_contact_recipient', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) {
		return false;
	}

	$question = qp_holdings_contact_question_labels()[ $data['question'] ] ?? $data['question'];
	$phone    = qp_holdings_format_phone_number( $data['phone'], $data['phone_country'] );
	$subject  = sprintf( 'Liên hệ mới: %s', $data['name'] );
	$body     = sprintf(
		"Họ tên: %s\nEmail: %s\nSố điện thoại: %s\nCâu hỏi: %s\n\nNội dung:\n%s",
		$data['name'],
		$data['email'],
		$phone,
		$question,
		$data['message']
	);
	$headers  = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
	);

	return wp_mail( $to, $subject, $body, $headers );
}

/**
 * Handle the public contact form submission.
 */
function qp_holdings_handle_contact_submit(): void {
	if ( ! isset( $_POST['qp_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qp_contact_nonce'] ) ), 'qp_contact_form' ) ) {
		qp_holdings_contact_submission_redirect( 'error' );
	}

	if ( '' !== qp_holdings_submission_post_text( 'website' ) ) {
		qp_holdings_contact_submission_redirect( 'sent' );
	}

	if ( ! qp_holdings_turnstile_verify() ) {
		qp_holdings_contact_submission_redirect( 'error' );
	}

	$data = array(
		'name'          => qp_holdings_submission_post_text( 'name' ),
		'email'         => qp_holdings_submission_post_email( 'email' ),
		'phone'         => qp_holdings_submission_post_text( 'phone' ),
		'phone_country' => qp_holdings_submission_phone_country( 'phone_country' ),
		'question'      => sanitize_key( qp_holdings_submission_post_text( 'question' ) ),
		'message'       => qp_holdings_submission_post_text( 'message', true ),
	);

	if ( ! $data['name'] || ! is_email( $data['email'] ) || ! $data['phone'] || ! $data['question'] || ! $data['message'] ) {
		qp_holdings_contact_submission_redirect( 'invalid' );
	}

	if ( ! array_key_exists( $data['question'], qp_holdings_contact_question_labels() ) ) {
		$data['question'] = 'other';
	}

	$submission_id = qp_holdings_create_contact_submission( $data );
	if ( is_wp_error( $submission_id ) || ! $submission_id ) {
		qp_holdings_contact_submission_redirect( 'error' );
	}

	$email_sent = qp_holdings_send_contact_notification( $data );
	update_post_meta( (int) $submission_id, '_qp_email_sent', $email_sent ? '1' : '0' );

	qp_holdings_contact_submission_redirect( 'sent' );
}
add_action( 'admin_post_qp_contact_submit', 'qp_holdings_handle_contact_submit' );
add_action( 'admin_post_nopriv_qp_contact_submit', 'qp_holdings_handle_contact_submit' );

/**
 * Redirect back to the project consult form with a status message.
 */
function qp_holdings_consult_submission_redirect( string $status ): void {
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$project_id = isset( $_POST['project_id'] ) ? absint( wp_unslash( $_POST['project_id'] ) ) : 0;
		$redirect   = $project_id ? get_permalink( $project_id ) : home_url( '/' );
	}

	$redirect = preg_replace( '/#.*$/', '', (string) $redirect );
	$redirect = remove_query_arg( 'consult', $redirect );

	wp_safe_redirect( add_query_arg( 'consult', $status, $redirect ) . '#consult-form' );
	exit;
}

/**
 * Persist a project consultation submission for admin review.
 *
 * @param array<string, string> $data Sanitized consultation payload.
 * @return int|WP_Error
 */
function qp_holdings_create_consult_submission( array $data ) {
	$submitted_at = current_time( 'mysql' );
	$title        = sprintf( '%s - %s', $data['full_name'], $data['project_interest'] );

	return wp_insert_post(
		array(
			'post_type'    => 'qp_consult_msg',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'meta_input'   => array(
				'_qp_consult_name'             => $data['full_name'],
				'_qp_consult_phone'            => $data['phone'],
				'_qp_consult_phone_country'    => $data['phone_country'],
				'_qp_consult_project_interest' => $data['project_interest'],
				'_qp_consult_project_id'       => $data['project_id'],
				'_qp_submitted_at'             => $submitted_at,
			),
		),
		true
	);
}

/**
 * Send an email notification for a saved consultation submission.
 *
 * @param array<string, string> $data Sanitized consultation payload.
 */
function qp_holdings_send_consult_notification( array $data ): bool {
	$to = apply_filters( 'qp_holdings_consult_recipient', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) {
		return false;
	}

	$subject = sprintf( 'Đăng ký tư vấn dự án: %s - %s', $data['project_interest'], $data['full_name'] );
	$phone   = qp_holdings_format_phone_number( $data['phone'], $data['phone_country'] );
	$body    = sprintf(
		"Họ tên: %s\nSố điện thoại: %s\nDự án quan tâm: %s",
		$data['full_name'],
		$phone,
		$data['project_interest']
	);
	$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

	if ( ! empty( $data['project_id'] ) ) {
		$permalink = get_permalink( (int) $data['project_id'] );
		if ( $permalink ) {
			$body .= "\n\nLink dự án: " . $permalink;
		}
	}

	return wp_mail( $to, $subject, $body, $headers );
}

/**
 * Handle the public project consultation form submission.
 */
function qp_holdings_handle_consult_submit(): void {
	$project_id = isset( $_POST['project_id'] ) ? absint( wp_unslash( $_POST['project_id'] ) ) : 0;

	if ( ! isset( $_POST['qp_consult_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qp_consult_nonce'] ) ), 'qp_consult_form_' . $project_id ) ) {
		qp_holdings_consult_submission_redirect( 'error' );
	}

	if ( '' !== qp_holdings_submission_post_text( 'website' ) ) {
		qp_holdings_consult_submission_redirect( 'sent' );
	}

	if ( ! qp_holdings_turnstile_verify() ) {
		qp_holdings_consult_submission_redirect( 'error' );
	}

	$data = array(
		'full_name'        => qp_holdings_submission_post_text( 'full_name' ),
		'phone'            => qp_holdings_submission_post_text( 'phone' ),
		'phone_country'    => qp_holdings_submission_phone_country( 'phone_country' ),
		'project_interest' => qp_holdings_submission_post_text( 'project_interest' ),
		'project_id'       => (string) $project_id,
	);

	if ( ! $data['full_name'] || ! $data['phone'] || ! $data['project_interest'] ) {
		qp_holdings_consult_submission_redirect( 'invalid' );
	}

	$submission_id = qp_holdings_create_consult_submission( $data );
	if ( is_wp_error( $submission_id ) || ! $submission_id ) {
		qp_holdings_consult_submission_redirect( 'error' );
	}

	$email_sent = qp_holdings_send_consult_notification( $data );
	update_post_meta( (int) $submission_id, '_qp_email_sent', $email_sent ? '1' : '0' );

	qp_holdings_consult_submission_redirect( 'sent' );
}
add_action( 'admin_post_qp_consult_submit', 'qp_holdings_handle_consult_submit' );
add_action( 'admin_post_nopriv_qp_consult_submit', 'qp_holdings_handle_consult_submit' );

/**
 * Redirect back to the newsletter form with a status message.
 */
function qp_holdings_newsletter_submission_redirect( string $status ): void {
	$redirect = wp_get_referer();
	if ( ! $redirect ) {
		$redirect = home_url( '/' );
	}

	$redirect = preg_replace( '/#.*$/', '', (string) $redirect );
	$redirect = remove_query_arg( 'newsletter', $redirect );

	wp_safe_redirect( add_query_arg( 'newsletter', $status, $redirect ) . '#contact' );
	exit;
}

/**
 * Find an existing newsletter subscription by email address.
 */
function qp_holdings_find_newsletter_subscriber( string $email ): int {
	$existing = get_posts(
		array(
			'post_type'      => 'qp_newsletter',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'   => '_qp_newsletter_email',
					'value' => $email,
				),
			),
		)
	);

	return $existing ? (int) $existing[0] : 0;
}

/**
 * Persist a newsletter subscription for admin review.
 *
 * @param array<string, string> $data Sanitized newsletter payload.
 * @return int|WP_Error
 */
function qp_holdings_create_newsletter_submission( array $data ) {
	$submitted_at = current_time( 'mysql' );

	return wp_insert_post(
		array(
			'post_type'   => 'qp_newsletter',
			'post_status' => 'publish',
			'post_title'  => $data['email'],
			'meta_input'  => array(
				'_qp_newsletter_email' => $data['email'],
				'_qp_newsletter_lang'  => $data['lang'],
				'_qp_newsletter_page'  => $data['source'],
				'_qp_submitted_at'     => $submitted_at,
			),
		),
		true
	);
}

/**
 * Send an email notification for a saved newsletter subscription.
 *
 * @param array<string, string> $data Sanitized newsletter payload.
 */
function qp_holdings_send_newsletter_notification( array $data ): bool {
	$to = apply_filters( 'qp_holdings_newsletter_recipient', get_option( 'admin_email' ) );
	if ( ! is_email( $to ) ) {
		return false;
	}

	$subject = sprintf( 'Đăng ký bản tin mới: %s', $data['email'] );
	$body    = sprintf(
		"Email: %s\nNgôn ngữ: %s\nTrang đăng ký: %s",
		$data['email'],
		$data['lang'],
		$data['source']
	);
	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $data['email'],
	);

	return wp_mail( $to, $subject, $body, $headers );
}

/**
 * Handle the public newsletter subscription form submission.
 */
function qp_holdings_handle_newsletter_submit(): void {
	if ( ! isset( $_POST['qp_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['qp_newsletter_nonce'] ) ), 'qp_newsletter_form' ) ) {
		qp_holdings_newsletter_submission_redirect( 'error' );
	}

	if ( '' !== qp_holdings_submission_post_text( 'website' ) ) {
		qp_holdings_newsletter_submission_redirect( 'sent' );
	}

	$email = qp_holdings_submission_post_email( 'email' );
	if ( ! is_email( $email ) ) {
		qp_holdings_newsletter_submission_redirect( 'invalid' );
	}

	// Already subscribed — treat as success, do not create a duplicate.
	if ( qp_holdings_find_newsletter_subscriber( $email ) ) {
		qp_holdings_newsletter_submission_redirect( 'exists' );
	}

	$lang   = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
	$source = (string) wp_get_referer();

	$data = array(
		'email'  => $email,
		'lang'   => $lang,
		'source' => $source ?: home_url( '/' ),
	);

	$submission_id = qp_holdings_create_newsletter_submission( $data );
	if ( is_wp_error( $submission_id ) || ! $submission_id ) {
		qp_holdings_newsletter_submission_redirect( 'error' );
	}

	$email_sent = qp_holdings_send_newsletter_notification( $data );
	update_post_meta( (int) $submission_id, '_qp_email_sent', $email_sent ? '1' : '0' );

	qp_holdings_newsletter_submission_redirect( 'sent' );
}
add_action( 'admin_post_qp_newsletter_submit', 'qp_holdings_handle_newsletter_submit' );
add_action( 'admin_post_nopriv_qp_newsletter_submit', 'qp_holdings_handle_newsletter_submit' );

/**
 * Store an uploaded submission file as a Media Library attachment.
 *
 * @param array<string, mixed> $file File upload array.
 * @return int|WP_Error
 */
function qp_holdings_store_uploaded_submission_file( array $file ) {
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$allowed_mimes = apply_filters(
		'qp_holdings_submission_allowed_mimes',
		array(
			'zip'          => 'application/zip',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'png'          => 'image/png',
			'jpg|jpeg|jpe' => 'image/jpeg',
		)
	);

	$upload = wp_handle_upload(
		$file,
		array(
			'test_form' => false,
			'mimes'     => $allowed_mimes,
		)
	);

	if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
		return new WP_Error( 'qp_submission_upload_failed', ! empty( $upload['error'] ) ? (string) $upload['error'] : 'Upload failed.' );
	}

	$file_path = (string) $upload['file'];
	$file_type = wp_check_filetype( basename( $file_path ), $allowed_mimes );
	$mime_type = $file_type['type'] ?: ( $upload['type'] ?? 'application/octet-stream' );

	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $mime_type,
			'post_title'     => sanitize_text_field( pathinfo( $file_path, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		),
		$file_path
	);

	if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
		wp_delete_file( $file_path );
		return is_wp_error( $attachment_id ) ? $attachment_id : new WP_Error( 'qp_submission_attachment_failed', 'Attachment insert failed.' );
	}

	$metadata = wp_generate_attachment_metadata( (int) $attachment_id, $file_path );
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( (int) $attachment_id, $metadata );
	}

	return (int) $attachment_id;
}

/**
 * Delete uploaded attachments when a submission cannot be saved.
 *
 * @param int[] $attachment_ids Attachment IDs.
 */
function qp_holdings_delete_submission_attachments( array $attachment_ids ): void {
	foreach ( $attachment_ids as $attachment_id ) {
		wp_delete_attachment( (int) $attachment_id, true );
	}
}

/**
 * Delete application CV attachments with their parent submission.
 */
function qp_holdings_delete_job_application_attachments( int $post_id, WP_Post $post ): void {
	if ( 'qp_job_app' !== $post->post_type ) {
		return;
	}

	$attachment_ids = get_post_meta( $post_id, '_qp_attachment_ids', true );
	if ( is_array( $attachment_ids ) && $attachment_ids ) {
		qp_holdings_delete_submission_attachments( $attachment_ids );
	}
}
add_action( 'before_delete_post', 'qp_holdings_delete_job_application_attachments', 10, 2 );

/**
 * Return physical file paths for email attachments.
 *
 * @param int[] $attachment_ids Attachment IDs.
 * @return string[]
 */
function qp_holdings_submission_attachment_paths( array $attachment_ids ): array {
	$paths = array();
	foreach ( $attachment_ids as $attachment_id ) {
		$path = get_attached_file( (int) $attachment_id );
		if ( $path && file_exists( $path ) ) {
			$paths[] = $path;
		}
	}

	return $paths;
}

/**
 * Attach stored CV files to the saved application post.
 *
 * @param int[] $attachment_ids Attachment IDs.
 */
function qp_holdings_set_submission_attachment_parent( int $submission_id, array $attachment_ids ): void {
	foreach ( $attachment_ids as $attachment_id ) {
		wp_update_post(
			array(
				'ID'          => (int) $attachment_id,
				'post_parent' => $submission_id,
			)
		);
	}
}

/**
 * Persist a job application for admin review.
 *
 * @param array<string, string> $data Sanitized application payload.
 * @param int[]                 $attachment_ids Uploaded attachment IDs.
 * @return int|WP_Error
 */
function qp_holdings_create_job_application_submission( WP_Post $job, array $data, array $attachment_ids ) {
	$submitted_at = current_time( 'mysql' );
	$job_title    = function_exists( 'qp_holdings_job_display_title' ) ? qp_holdings_job_display_title( $job ) : get_the_title( $job );
	$title        = sprintf( '%s - %s', $data['name'], $job_title );

	return wp_insert_post(
		array(
			'post_type'    => 'qp_job_app',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_content' => $data['message'],
			'meta_input'   => array(
				'_qp_applicant_name'                 => $data['name'],
				'_qp_applicant_email'                => $data['email'],
				'_qp_applicant_phone'                => $data['phone'],
				'_qp_applicant_phone_country'        => $data['phone_country'] ?? 'VN',
				'_qp_applicant_message'              => $data['message'],
				'_qp_job_id'                         => (string) $job->ID,
				'_qp_job_title'                      => $job_title,
				'_qp_job_url'                        => get_permalink( $job ),
				'_qp_attachment_ids'                 => array_map( 'intval', $attachment_ids ),
				'_qp_submitted_at'                   => $submitted_at,
			),
		),
		true
	);
}

/**
 * Contact submission admin columns.
 */
function qp_holdings_contact_submission_columns( array $columns ): array {
	return array(
		'cb'       => $columns['cb'] ?? '',
		'title'    => 'Người gửi',
		'email'    => 'Email',
		'phone'    => 'Số điện thoại',
		'question' => 'Câu hỏi',
		'date'     => 'Ngày gửi',
	);
}
add_filter( 'manage_qp_contact_msg_posts_columns', 'qp_holdings_contact_submission_columns' );

/**
 * Job application admin columns.
 */
function qp_holdings_job_application_columns( array $columns ): array {
	return array(
		'cb'    => $columns['cb'] ?? '',
		'title' => 'Ứng viên',
		'job'   => 'Vị trí',
		'email' => 'Email',
		'phone' => 'Số điện thoại',
		'files' => 'CV / tài liệu',
		'date'  => 'Ngày gửi',
	);
}
add_filter( 'manage_qp_job_app_posts_columns', 'qp_holdings_job_application_columns' );

/**
 * Consultation submission admin columns.
 */
function qp_holdings_consult_submission_columns( array $columns ): array {
	return array(
		'cb'       => $columns['cb'] ?? '',
		'title'    => 'Họ tên',
		'phone'    => 'Số điện thoại',
		'project'  => 'Dự án',
		'date'     => 'Ngày gửi',
	);
}
add_filter( 'manage_qp_consult_msg_posts_columns', 'qp_holdings_consult_submission_columns' );

/**
 * Newsletter subscription admin columns.
 */
function qp_holdings_newsletter_submission_columns( array $columns ): array {
	return array(
		'cb'    => $columns['cb'] ?? '',
		'title' => 'Email',
		'lang'  => 'Ngôn ngữ',
		'date'  => 'Ngày đăng ký',
	);
}
add_filter( 'manage_qp_newsletter_posts_columns', 'qp_holdings_newsletter_submission_columns' );

/**
 * Render contact submission column values.
 */
function qp_holdings_contact_submission_column_value( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'email':
			$email = (string) get_post_meta( $post_id, '_qp_contact_email', true );
			if ( $email ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
			break;
		case 'phone':
			echo esc_html( qp_holdings_submission_formatted_phone( $post_id, '_qp_contact_phone', '_qp_contact_phone_country' ) );
			break;
		case 'question':
			echo esc_html( (string) get_post_meta( $post_id, '_qp_contact_label', true ) );
			break;
	}
}
add_action( 'manage_qp_contact_msg_posts_custom_column', 'qp_holdings_contact_submission_column_value', 10, 2 );

/**
 * Render job application column values.
 */
function qp_holdings_job_application_column_value( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'job':
			$job_id    = (int) get_post_meta( $post_id, '_qp_job_id', true );
			$job_title = (string) get_post_meta( $post_id, '_qp_job_title', true );
			if ( $job_id && get_post( $job_id ) ) {
				echo '<a href="' . esc_url( get_edit_post_link( $job_id ) ) . '">' . esc_html( $job_title ) . '</a>';
			} else {
				echo esc_html( $job_title );
			}
			break;
		case 'email':
			$email = (string) get_post_meta( $post_id, '_qp_applicant_email', true );
			if ( $email ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
			}
			break;
		case 'phone':
			echo esc_html( qp_holdings_submission_formatted_phone( $post_id, '_qp_applicant_phone', '_qp_applicant_phone_country' ) );
			break;
		case 'files':
			$attachment_ids = get_post_meta( $post_id, '_qp_attachment_ids', true );
			$count          = is_array( $attachment_ids ) ? count( $attachment_ids ) : 0;
			echo esc_html( (string) $count );
			break;
	}
}
add_action( 'manage_qp_job_app_posts_custom_column', 'qp_holdings_job_application_column_value', 10, 2 );

/**
 * Render consultation submission column values.
 */
function qp_holdings_consult_submission_column_value( string $column, int $post_id ): void {
	switch ( $column ) {
		case 'phone':
			echo esc_html( qp_holdings_submission_formatted_phone( $post_id, '_qp_consult_phone', '_qp_consult_phone_country' ) );
			break;
		case 'project':
			$project_interest = (string) get_post_meta( $post_id, '_qp_consult_project_interest', true );
			$project_id       = (int) get_post_meta( $post_id, '_qp_consult_project_id', true );
			if ( $project_id && get_post( $project_id ) ) {
				echo '<a href="' . esc_url( get_edit_post_link( $project_id ) ) . '">' . esc_html( $project_interest ) . '</a>';
			} else {
				echo esc_html( $project_interest );
			}
			break;
	}
}
add_action( 'manage_qp_consult_msg_posts_custom_column', 'qp_holdings_consult_submission_column_value', 10, 2 );

/**
 * Map a stored language code to a human label.
 */
function qp_holdings_newsletter_language_label( string $lang ): string {
	$labels = array(
		'vi' => 'Tiếng Việt',
		'en' => 'Tiếng Anh',
		'zh' => 'Tiếng Trung',
	);

	return $labels[ $lang ] ?? ( $lang ?: '—' );
}

/**
 * Render newsletter subscription column values.
 */
function qp_holdings_newsletter_submission_column_value( string $column, int $post_id ): void {
	if ( 'lang' === $column ) {
		$lang = (string) get_post_meta( $post_id, '_qp_newsletter_lang', true );
		echo esc_html( qp_holdings_newsletter_language_label( $lang ) );
	}
}
add_action( 'manage_qp_newsletter_posts_custom_column', 'qp_holdings_newsletter_submission_column_value', 10, 2 );

/**
 * Add read-only detail boxes on submission edit screens.
 */
function qp_holdings_add_submission_meta_boxes(): void {
	add_meta_box( 'qp_contact_submission_details', 'Chi tiết liên hệ', 'qp_holdings_render_contact_submission_meta_box', 'qp_contact_msg', 'normal', 'high' );
	add_meta_box( 'qp_job_application_details', 'Chi tiết hồ sơ ứng tuyển', 'qp_holdings_render_job_application_meta_box', 'qp_job_app', 'normal', 'high' );
	add_meta_box( 'qp_consult_submission_details', 'Chi tiết đăng ký tư vấn', 'qp_holdings_render_consult_submission_meta_box', 'qp_consult_msg', 'normal', 'high' );
	add_meta_box( 'qp_newsletter_submission_details', 'Chi tiết đăng ký bản tin', 'qp_holdings_render_newsletter_submission_meta_box', 'qp_newsletter', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'qp_holdings_add_submission_meta_boxes' );

/**
 * Render a plain table row in admin detail boxes.
 */
function qp_holdings_submission_detail_row( string $label, string $value, bool $multiline = false ): void {
	echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
	if ( $multiline ) {
		echo nl2br( esc_html( $value ) );
	} else {
		echo esc_html( $value );
	}
	echo '</td></tr>';
}

/**
 * Render contact submission details.
 */
function qp_holdings_render_contact_submission_meta_box( WP_Post $post ): void {
	$name     = (string) get_post_meta( $post->ID, '_qp_contact_name', true );
	$email    = (string) get_post_meta( $post->ID, '_qp_contact_email', true );
	$phone    = qp_holdings_submission_formatted_phone( $post->ID, '_qp_contact_phone', '_qp_contact_phone_country' );
	$question = (string) get_post_meta( $post->ID, '_qp_contact_label', true );
	$message  = (string) get_post_meta( $post->ID, '_qp_contact_message', true );
	$sent     = '1' === (string) get_post_meta( $post->ID, '_qp_email_sent', true ) ? 'Đã gửi' : 'Chưa gửi / không xác định';

	echo '<table class="widefat striped"><tbody>';
	qp_holdings_submission_detail_row( 'Họ tên', $name );
	qp_holdings_submission_detail_row( 'Email', $email );
	qp_holdings_submission_detail_row( 'Số điện thoại', $phone );
	qp_holdings_submission_detail_row( 'Câu hỏi', $question );
	qp_holdings_submission_detail_row( 'Email thông báo', $sent );
	qp_holdings_submission_detail_row( 'Nội dung', $message, true );
	echo '</tbody></table>';
}

/**
 * Render job application details.
 */
function qp_holdings_render_job_application_meta_box( WP_Post $post ): void {
	$name                 = (string) get_post_meta( $post->ID, '_qp_applicant_name', true );
	$email                = (string) get_post_meta( $post->ID, '_qp_applicant_email', true );
	$phone                = qp_holdings_submission_formatted_phone( $post->ID, '_qp_applicant_phone', '_qp_applicant_phone_country' );
	$message              = (string) get_post_meta( $post->ID, '_qp_applicant_message', true );
	$job_title            = (string) get_post_meta( $post->ID, '_qp_job_title', true );
	$job_url              = (string) get_post_meta( $post->ID, '_qp_job_url', true );
	$attachment_ids       = get_post_meta( $post->ID, '_qp_attachment_ids', true );
	$sent                 = '1' === (string) get_post_meta( $post->ID, '_qp_email_sent', true ) ? 'Đã gửi' : 'Chưa gửi / không xác định';

	echo '<table class="widefat striped"><tbody>';
	qp_holdings_submission_detail_row( 'Họ tên', $name );
	qp_holdings_submission_detail_row( 'Email', $email );
	qp_holdings_submission_detail_row( 'Số điện thoại', $phone );

	echo '<tr><th scope="row">Vị trí</th><td>';
	if ( $job_url ) {
		echo '<a href="' . esc_url( $job_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $job_title ) . '</a>';
	} else {
		echo esc_html( $job_title );
	}
	echo '</td></tr>';

	qp_holdings_submission_detail_row( 'Email thông báo', $sent );
	qp_holdings_submission_detail_row( 'Mô tả bản thân', $message, true );

	echo '<tr><th scope="row">CV / tài liệu</th><td>';
	if ( is_array( $attachment_ids ) && $attachment_ids ) {
		echo '<ul style="margin:0;">';
		foreach ( $attachment_ids as $attachment_id ) {
			$url = wp_get_attachment_url( (int) $attachment_id );
			if ( ! $url ) {
				continue;
			}
			echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( get_the_title( (int) $attachment_id ) ) . '</a></li>';
		}
		echo '</ul>';
	} else {
		echo esc_html( 'Không có tệp.' );
	}
	echo '</td></tr>';

	echo '</tbody></table>';
}

/**
 * Render consultation submission details.
 */
function qp_holdings_render_consult_submission_meta_box( WP_Post $post ): void {
	$name             = (string) get_post_meta( $post->ID, '_qp_consult_name', true );
	$phone            = qp_holdings_submission_formatted_phone( $post->ID, '_qp_consult_phone', '_qp_consult_phone_country' );
	$project_interest = (string) get_post_meta( $post->ID, '_qp_consult_project_interest', true );
	$project_id       = (int) get_post_meta( $post->ID, '_qp_consult_project_id', true );
	$sent             = '1' === (string) get_post_meta( $post->ID, '_qp_email_sent', true ) ? 'Đã gửi' : 'Chưa gửi / không xác định';

	echo '<table class="widefat striped"><tbody>';
	qp_holdings_submission_detail_row( 'Họ tên', $name );
	qp_holdings_submission_detail_row( 'Số điện thoại', $phone );

	echo '<tr><th scope="row">Dự án quan tâm</th><td>';
	if ( $project_id && get_post( $project_id ) ) {
		echo '<a href="' . esc_url( get_permalink( $project_id ) ) . '">' . esc_html( $project_interest ) . '</a>';
	} else {
		echo esc_html( $project_interest );
	}
	echo '</td></tr>';

	qp_holdings_submission_detail_row( 'Email thông báo', $sent );
	echo '</tbody></table>';
}

/**
 * Render newsletter subscription details.
 */
function qp_holdings_render_newsletter_submission_meta_box( WP_Post $post ): void {
	$email  = (string) get_post_meta( $post->ID, '_qp_newsletter_email', true );
	$lang   = (string) get_post_meta( $post->ID, '_qp_newsletter_lang', true );
	$source = (string) get_post_meta( $post->ID, '_qp_newsletter_page', true );
	$sent   = '1' === (string) get_post_meta( $post->ID, '_qp_email_sent', true ) ? 'Đã gửi' : 'Chưa gửi / không xác định';

	echo '<table class="widefat striped"><tbody>';

	echo '<tr><th scope="row">Email</th><td>';
	if ( $email ) {
		echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
	}
	echo '</td></tr>';

	qp_holdings_submission_detail_row( 'Ngôn ngữ', qp_holdings_newsletter_language_label( $lang ) );

	echo '<tr><th scope="row">Trang đăng ký</th><td>';
	if ( $source ) {
		echo '<a href="' . esc_url( $source ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $source ) . '</a>';
	} else {
		echo '—';
	}
	echo '</td></tr>';

	qp_holdings_submission_detail_row( 'Email thông báo', $sent );
	echo '</tbody></table>';
}

/**
 * Remove frontend-only actions from private submission rows.
 */
function qp_holdings_submission_row_actions( array $actions, WP_Post $post ): array {
	if ( in_array( $post->post_type, array( 'qp_contact_msg', 'qp_job_app', 'qp_consult_msg', 'qp_newsletter' ), true ) ) {
		unset( $actions['view'], $actions['inline hide-if-no-js'] );
	}

	return $actions;
}
add_filter( 'post_row_actions', 'qp_holdings_submission_row_actions', 10, 2 );
