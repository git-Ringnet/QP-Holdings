<?php
/**
 * Centralised email configuration for QP Holdings.
 *
 * Provides:
 *  1. A global From address/name for every wp_mail() call (so messages no longer
 *     go out as wordpress@<server-host>, which fails SPF/DKIM on shared hosting
 *     and lands in spam). Overridable per-environment via the QP_MAIL_FROM_EMAIL
 *     / QP_MAIL_FROM_NAME constants.
 *  2. An admin Settings page (Cài đặt → Cấu hình Email) that lets a non-technical
 *     admin choose the From identity and the notification recipients for the
 *     contact, consultation, and newsletter forms — hooked into the existing
 *     qp_holdings_*_recipient filters in inc/dynamic-submissions.php.
 *
 * Actual SMTP delivery is handled by a dedicated SMTP plugin (FluentSMTP),
 * configured in wp-admin. This module only sets the message identity/recipients;
 * it does not open SMTP connections itself.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/** Single option key holding the whole mail config array. */
const QP_HOLDINGS_MAIL_OPTION = 'qp_holdings_mail_settings';

/**
 * Built-in default From email. A no-reply mailbox on the site's own domain so
 * SPF/DKIM align once FluentSMTP authenticates against the production mailbox.
 */
function qp_holdings_mail_default_from_email(): string {
	if ( defined( 'QP_MAIL_FROM_EMAIL' ) && is_email( QP_MAIL_FROM_EMAIL ) ) {
		return QP_MAIL_FROM_EMAIL;
	}
	return 'no-reply@qpholdings.vn';
}

/** Built-in default From name. */
function qp_holdings_mail_default_from_name(): string {
	if ( defined( 'QP_MAIL_FROM_NAME' ) && '' !== QP_MAIL_FROM_NAME ) {
		return QP_MAIL_FROM_NAME;
	}
	return 'QP Holdings';
}

/**
 * Read the stored mail config, normalised with safe defaults.
 *
 * @return array{from_name:string,from_email:string,contact_to:string,consult_to:string,newsletter_to:string}
 */
function qp_holdings_mail_get(): array {
	$raw = get_option( QP_HOLDINGS_MAIL_OPTION, array() );
	$raw = is_array( $raw ) ? $raw : array();

	$admin = (string) get_option( 'admin_email' );

	$from_email = isset( $raw['from_email'] ) ? (string) $raw['from_email'] : '';
	$from_name  = isset( $raw['from_name'] ) ? (string) $raw['from_name'] : '';

	return array(
		'from_name'     => '' !== trim( $from_name ) ? $from_name : qp_holdings_mail_default_from_name(),
		'from_email'    => is_email( $from_email ) ? $from_email : qp_holdings_mail_default_from_email(),
		'contact_to'    => isset( $raw['contact_to'] ) && is_email( $raw['contact_to'] ) ? $raw['contact_to'] : $admin,
		'consult_to'    => isset( $raw['consult_to'] ) && is_email( $raw['consult_to'] ) ? $raw['consult_to'] : $admin,
		'newsletter_to' => isset( $raw['newsletter_to'] ) && is_email( $raw['newsletter_to'] ) ? $raw['newsletter_to'] : $admin,
	);
}

/* -------------------------------------------------------------------------
 * Global From identity for every outgoing email.
 * ---------------------------------------------------------------------- */

add_filter(
	'wp_mail_from',
	static function ( string $email ): string {
		$config = qp_holdings_mail_get();
		return is_email( $config['from_email'] ) ? $config['from_email'] : $email;
	},
	20
);

add_filter(
	'wp_mail_from_name',
	static function ( string $name ): string {
		$config = qp_holdings_mail_get();
		return '' !== trim( $config['from_name'] ) ? $config['from_name'] : $name;
	},
	20
);

/* -------------------------------------------------------------------------
 * Per-form notification recipients (hook the existing submission filters).
 * ---------------------------------------------------------------------- */

add_filter(
	'qp_holdings_contact_recipient',
	static function ( $to ) {
		$config = qp_holdings_mail_get();
		return is_email( $config['contact_to'] ) ? $config['contact_to'] : $to;
	}
);

add_filter(
	'qp_holdings_consult_recipient',
	static function ( $to ) {
		$config = qp_holdings_mail_get();
		return is_email( $config['consult_to'] ) ? $config['consult_to'] : $to;
	}
);

add_filter(
	'qp_holdings_newsletter_recipient',
	static function ( $to ) {
		$config = qp_holdings_mail_get();
		return is_email( $config['newsletter_to'] ) ? $config['newsletter_to'] : $to;
	}
);

/* -------------------------------------------------------------------------
 * Settings page (Cài đặt → Cấu hình Email).
 * ---------------------------------------------------------------------- */

/**
 * Register the settings page under the Settings menu.
 */
function qp_holdings_mail_register_page(): void {
	add_options_page(
		'Cấu hình Email',
		'Cấu hình Email',
		'manage_options',
		'qp-mail-settings',
		'qp_holdings_mail_render_page'
	);
}
add_action( 'admin_menu', 'qp_holdings_mail_register_page' );

/**
 * Register the setting + sanitiser with the Settings API.
 */
function qp_holdings_mail_register_setting(): void {
	register_setting(
		'qp_mail_settings_group',
		QP_HOLDINGS_MAIL_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'qp_holdings_mail_sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'qp_holdings_mail_register_setting' );

/**
 * Sanitise + validate the submitted mail config.
 *
 * Email fields that are blank or invalid fall back to sensible defaults so the
 * site never ends up with a broken From address or an unreachable recipient.
 *
 * @param mixed $input Raw POSTed array.
 * @return array<string,string>
 */
function qp_holdings_mail_sanitize( $input ): array {
	if ( ! current_user_can( 'manage_options' ) ) {
		return qp_holdings_mail_get();
	}

	$input = is_array( $input ) ? $input : array();
	$admin = (string) get_option( 'admin_email' );

	$from_name  = isset( $input['from_name'] ) ? sanitize_text_field( wp_unslash( $input['from_name'] ) ) : '';
	$from_email = isset( $input['from_email'] ) ? sanitize_email( wp_unslash( $input['from_email'] ) ) : '';

	$contact_to    = isset( $input['contact_to'] ) ? sanitize_email( wp_unslash( $input['contact_to'] ) ) : '';
	$consult_to    = isset( $input['consult_to'] ) ? sanitize_email( wp_unslash( $input['consult_to'] ) ) : '';
	$newsletter_to = isset( $input['newsletter_to'] ) ? sanitize_email( wp_unslash( $input['newsletter_to'] ) ) : '';

	$flag_invalid = static function ( string $value, string $field, string $label ): void {
		if ( '' !== trim( $value ) && ! is_email( $value ) ) {
			add_settings_error(
				QP_HOLDINGS_MAIL_OPTION,
				'invalid_' . $field,
				sprintf( 'Email "%s" không hợp lệ và đã được bỏ qua (dùng lại giá trị mặc định).', $label ),
				'error'
			);
		}
	};

	$flag_invalid( isset( $input['from_email'] ) ? (string) wp_unslash( $input['from_email'] ) : '', 'from_email', 'Email gửi đi' );
	$flag_invalid( isset( $input['contact_to'] ) ? (string) wp_unslash( $input['contact_to'] ) : '', 'contact_to', 'Nhận liên hệ' );
	$flag_invalid( isset( $input['consult_to'] ) ? (string) wp_unslash( $input['consult_to'] ) : '', 'consult_to', 'Nhận tư vấn dự án' );
	$flag_invalid( isset( $input['newsletter_to'] ) ? (string) wp_unslash( $input['newsletter_to'] ) : '', 'newsletter_to', 'Nhận đăng ký bản tin' );

	return array(
		'from_name'     => '' !== trim( $from_name ) ? $from_name : qp_holdings_mail_default_from_name(),
		'from_email'    => is_email( $from_email ) ? $from_email : qp_holdings_mail_default_from_email(),
		'contact_to'    => is_email( $contact_to ) ? $contact_to : $admin,
		'consult_to'    => is_email( $consult_to ) ? $consult_to : $admin,
		'newsletter_to' => is_email( $newsletter_to ) ? $newsletter_to : $admin,
	);
}

/**
 * Render a single labelled text/email input row.
 *
 * @param string $name        Field key within the option array.
 * @param string $label       Field label.
 * @param string $value       Current value.
 * @param string $type        Input type (text|email).
 * @param string $placeholder Placeholder/help hint.
 * @param string $description  Help text under the field.
 */
function qp_holdings_mail_render_field( string $name, string $label, string $value, string $type, string $placeholder, string $description ): void {
	$field = QP_HOLDINGS_MAIL_OPTION;
	?>
	<tr>
		<th scope="row">
			<label for="qp-mail-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
		</th>
		<td>
			<input
				type="<?php echo esc_attr( $type ); ?>"
				id="qp-mail-<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $field ); ?>[<?php echo esc_attr( $name ); ?>]"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="regular-text" />
			<?php if ( '' !== $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * Render the settings page.
 */
function qp_holdings_mail_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'qp-holdings' ) );
	}

	$config = qp_holdings_mail_get();
	?>
	<div class="wrap">
		<h1>Cấu hình Email</h1>
		<p>Thiết lập danh tính người gửi (From) cho mọi email hệ thống và địa chỉ nhận thông báo từ các biểu mẫu trên website. Để email gửi đi không vào hộp thư rác, hãy cài và cấu hình plugin <strong>FluentSMTP</strong> (Cài đặt → FluentSMTP) trỏ tới hộp thư SMTP trên domain <code>qpholdings.vn</code>, đồng thời đặt địa chỉ <em>Email gửi đi</em> bên dưới trùng với hộp thư đó.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'qp_mail_settings_group' ); ?>

			<h2>Danh tính người gửi (From)</h2>
			<table class="form-table" role="presentation">
				<tbody>
					<?php
					qp_holdings_mail_render_field(
						'from_name',
						'Tên người gửi',
						$config['from_name'],
						'text',
						'QP Holdings',
						'Tên hiển thị trong mục người gửi của email.'
					);
					qp_holdings_mail_render_field(
						'from_email',
						'Email gửi đi',
						$config['from_email'],
						'email',
						'no-reply@qpholdings.vn',
						'Nên dùng hộp thư trên chính domain qpholdings.vn và trùng với cấu hình FluentSMTP.'
					);
					?>
				</tbody>
			</table>

			<h2>Địa chỉ nhận thông báo</h2>
			<table class="form-table" role="presentation">
				<tbody>
					<?php
					qp_holdings_mail_render_field(
						'contact_to',
						'Nhận form liên hệ',
						$config['contact_to'],
						'email',
						(string) get_option( 'admin_email' ),
						'Email nhận thông báo khi có người gửi form Liên hệ.'
					);
					qp_holdings_mail_render_field(
						'consult_to',
						'Nhận form tư vấn dự án',
						$config['consult_to'],
						'email',
						(string) get_option( 'admin_email' ),
						'Email nhận thông báo khi có người đăng ký tư vấn dự án.'
					);
					qp_holdings_mail_render_field(
						'newsletter_to',
						'Nhận đăng ký bản tin',
						$config['newsletter_to'],
						'email',
						(string) get_option( 'admin_email' ),
						'Email nhận thông báo khi có người đăng ký nhận bản tin.'
					);
					?>
				</tbody>
			</table>

			<p class="description">Thông báo ứng tuyển việc làm được gửi tới email cấu hình riêng trong từng tin tuyển dụng (mục “Email nhận hồ sơ”). Nếu để trống, hệ thống dùng email quản trị.</p>

			<?php submit_button( 'Lưu thay đổi' ); ?>
		</form>
	</div>
	<?php
}
