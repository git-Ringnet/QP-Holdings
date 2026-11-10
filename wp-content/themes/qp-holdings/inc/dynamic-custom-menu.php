<?php
/**
 * Dynamic Custom Menu Link configuration for QP Holdings.
 *
 * Provides:
 *  1. An admin Settings page (Cài đặt / Cấu hình → Menu tùy chỉnh) that lets
 *     administrators configure a custom menu link (URL ngoài hoặc nội bộ) with
 *     multilingual titles and URLs (VI, EN, ZH), position selection, and new-tab toggle.
 *  2. Render helpers for both the modern header (header.php) and legacy header
 *     (header-legacy.php).
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/** Single option key holding the custom menu config array. */
const QP_HOLDINGS_CUSTOM_MENU_OPTION = 'qp_holdings_custom_menu_settings';

/**
 * Available positions for the custom menu item.
 *
 * @return array<string, string> Key => Label.
 */
function qp_holdings_custom_menu_positions(): array {
	return array(
		'end'            => 'Sau Liên hệ (Cuối menu - Mặc định)',
		'after_home'     => 'Sau Trang chủ',
		'after_about'    => 'Sau Giới thiệu',
		'after_projects' => 'Sau Dự án',
		'after_investor' => 'Sau Quan hệ nhà đầu tư',
		'after_news'     => 'Sau Tin tức',
		'after_careers'  => 'Sau Tuyển dụng',
	);
}

/**
 * Default settings for the custom menu link.
 *
 * @return array{enabled:bool,url:string,url_en:string,url_zh:string,title_vi:string,title_en:string,title_zh:string,position:string,new_tab:bool}
 */
function qp_holdings_custom_menu_defaults(): array {
	return array(
		'enabled'  => false,
		'url'      => '',
		'url_en'   => '',
		'url_zh'   => '',
		'title_vi' => '',
		'title_en' => '',
		'title_zh' => '',
		'position' => 'end',
		'new_tab'  => true,
	);
}

/**
 * Read the stored custom menu config with safe defaults.
 *
 * @return array{enabled:bool,url:string,url_en:string,url_zh:string,title_vi:string,title_en:string,title_zh:string,position:string,new_tab:bool}
 */
function qp_holdings_custom_menu_get(): array {
	$raw = get_option( QP_HOLDINGS_CUSTOM_MENU_OPTION, array() );
	$raw = is_array( $raw ) ? $raw : array();

	$defaults = qp_holdings_custom_menu_defaults();

	return array(
		'enabled'  => ! empty( $raw['enabled'] ),
		'url'      => isset( $raw['url'] ) ? esc_url_raw( trim( (string) $raw['url'] ) ) : $defaults['url'],
		'url_en'   => isset( $raw['url_en'] ) ? esc_url_raw( trim( (string) $raw['url_en'] ) ) : $defaults['url_en'],
		'url_zh'   => isset( $raw['url_zh'] ) ? esc_url_raw( trim( (string) $raw['url_zh'] ) ) : $defaults['url_zh'],
		'title_vi' => isset( $raw['title_vi'] ) ? sanitize_text_field( (string) $raw['title_vi'] ) : $defaults['title_vi'],
		'title_en' => isset( $raw['title_en'] ) ? sanitize_text_field( (string) $raw['title_en'] ) : $defaults['title_en'],
		'title_zh' => isset( $raw['title_zh'] ) ? sanitize_text_field( (string) $raw['title_zh'] ) : $defaults['title_zh'],
		'position' => isset( $raw['position'] ) && array_key_exists( $raw['position'], qp_holdings_custom_menu_positions() )
			? (string) $raw['position']
			: $defaults['position'],
		'new_tab'  => isset( $raw['new_tab'] ) ? ! empty( $raw['new_tab'] ) : $defaults['new_tab'],
	);
}

/**
 * Register settings page under Settings menu.
 */
function qp_holdings_custom_menu_register_page(): void {
	add_options_page(
		'Menu tùy chỉnh',
		'Menu tùy chỉnh',
		'manage_options',
		'qp-custom-menu',
		'qp_holdings_custom_menu_render_page'
	);
}
add_action( 'admin_menu', 'qp_holdings_custom_menu_register_page' );

/**
 * Register the setting with the Settings API.
 */
function qp_holdings_custom_menu_register_setting(): void {
	register_setting(
		'qp_custom_menu_group',
		QP_HOLDINGS_CUSTOM_MENU_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'qp_holdings_custom_menu_sanitize',
			'default'           => qp_holdings_custom_menu_defaults(),
		)
	);
}
add_action( 'admin_init', 'qp_holdings_custom_menu_register_setting' );

/**
 * Sanitize submitted settings.
 *
 * @param mixed $input Submitted POST data.
 * @return array{enabled:bool,url:string,url_en:string,url_zh:string,title_vi:string,title_en:string,title_zh:string,position:string,new_tab:bool}
 */
function qp_holdings_custom_menu_sanitize( $input ): array {
	if ( ! current_user_can( 'manage_options' ) ) {
		return qp_holdings_custom_menu_get();
	}

	$input = is_array( $input ) ? $input : array();

	$url    = isset( $input['url'] ) ? esc_url_raw( trim( (string) $input['url'] ) ) : '';
	$url_en = isset( $input['url_en'] ) ? esc_url_raw( trim( (string) $input['url_en'] ) ) : '';
	$url_zh = isset( $input['url_zh'] ) ? esc_url_raw( trim( (string) $input['url_zh'] ) ) : '';

	$pos = isset( $input['position'] ) ? sanitize_key( (string) $input['position'] ) : 'end';
	if ( ! array_key_exists( $pos, qp_holdings_custom_menu_positions() ) ) {
		$pos = 'end';
	}

	return array(
		'enabled'  => ! empty( $input['enabled'] ),
		'url'      => $url,
		'url_en'   => $url_en,
		'url_zh'   => $url_zh,
		'title_vi' => isset( $input['title_vi'] ) ? sanitize_text_field( wp_unslash( $input['title_vi'] ) ) : '',
		'title_en' => isset( $input['title_en'] ) ? sanitize_text_field( wp_unslash( $input['title_en'] ) ) : '',
		'title_zh' => isset( $input['title_zh'] ) ? sanitize_text_field( wp_unslash( $input['title_zh'] ) ) : '',
		'position' => $pos,
		'new_tab'  => ! empty( $input['new_tab'] ),
	);
}

/**
 * Render the settings admin page.
 */
function qp_holdings_custom_menu_render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'qp-holdings' ) );
	}

	$config    = qp_holdings_custom_menu_get();
	$positions = qp_holdings_custom_menu_positions();
	?>
	<div class="wrap">
		<h1>Cấu hình Menu tùy chỉnh (Custom Link)</h1>
		<p>Cho phép thêm một liên kết tùy chỉnh (link ra trang web ngoài, link landing page, liên kết Canva...) vào thanh menu chính của website mà không cần tạo thêm Trang (Page) mới.</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'qp_custom_menu_group' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">Trạng thái kích hoạt</th>
						<td>
							<label for="qp-menu-enabled">
								<input type="checkbox" id="qp-menu-enabled" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[enabled]" value="1" <?php checked( $config['enabled'] ); ?>>
								<strong>Bật hiển thị liên kết này trên thanh Menu</strong>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-url">Đường dẫn Tiếng Việt (URL VI) <span class="description">(bắt buộc)</span></label>
						</th>
						<td>
							<input type="url" id="qp-menu-url" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[url]" value="<?php echo esc_attr( $config['url'] ); ?>" placeholder="https://qpholdings.vn/ho-so-doanh-nghiep/" class="regular-text code" style="width: 100%; max-width: 500px;">
							<p class="description">Đường dẫn khi khách duyệt bằng Tiếng Việt. Ví dụ: <code>https://qpholdings.vn/ho-so-doanh-nghiep/</code> hoặc <code>https://qpgroup.garastem.com/</code></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-title-vi">Tiêu đề Tiếng Việt (VI) <span class="description">(bắt buộc)</span></label>
						</th>
						<td>
							<input type="text" id="qp-menu-title-vi" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[title_vi]" value="<?php echo esc_attr( $config['title_vi'] ); ?>" placeholder="Ví dụ: Hồ sơ doanh nghiệp" class="regular-text">
							<p class="description">Tên hiển thị trên menu khi khách duyệt website bằng Tiếng Việt.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-title-en">Tiêu đề Tiếng Anh (EN)</label>
						</th>
						<td>
							<input type="text" id="qp-menu-title-en" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[title_en]" value="<?php echo esc_attr( $config['title_en'] ); ?>" placeholder="Ví dụ: Corporate Profile" class="regular-text">
							<p class="description">Tên hiển thị khi khách chuyển sang Tiếng Anh (nếu để trống sẽ dùng tên Tiếng Việt).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-url-en">Đường dẫn Tiếng Anh (URL EN)</label>
						</th>
						<td>
							<input type="url" id="qp-menu-url-en" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[url_en]" value="<?php echo esc_attr( $config['url_en'] ); ?>" placeholder="https://qpholdings.vn/Corporate-Profile/" class="regular-text code" style="width: 100%; max-width: 500px;">
							<p class="description">Đường dẫn riêng cho Tiếng Anh. Nếu để trống sẽ tự động dùng đường dẫn Tiếng Việt.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-title-zh">Tiêu đề Tiếng Trung (ZH)</label>
						</th>
						<td>
							<input type="text" id="qp-menu-title-zh" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[title_zh]" value="<?php echo esc_attr( $config['title_zh'] ); ?>" placeholder="Ví dụ: 企业概况" class="regular-text">
							<p class="description">Tên hiển thị khi khách chuyển sang Tiếng Trung (nếu để trống sẽ dùng tên Tiếng Việt).</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-url-zh">Đường dẫn Tiếng Trung (URL ZH)</label>
						</th>
						<td>
							<input type="url" id="qp-menu-url-zh" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[url_zh]" value="<?php echo esc_attr( $config['url_zh'] ); ?>" placeholder="https://qpholdings.vn/zh/profile/" class="regular-text code" style="width: 100%; max-width: 500px;">
							<p class="description">Đường dẫn riêng cho Tiếng Trung. Nếu để trống sẽ tự động dùng đường dẫn Tiếng Việt.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="qp-menu-position">Vị trí hiển thị trên Menu</label>
						</th>
						<td>
							<select id="qp-menu-position" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[position]">
								<?php foreach ( $positions as $pos_key => $pos_label ) : ?>
									<option value="<?php echo esc_attr( $pos_key ); ?>" <?php selected( $config['position'], $pos_key ); ?>>
										<?php echo esc_html( $pos_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">Chọn vị trí đứng của liên kết trên thanh điều hướng Header.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Mở trong tab mới</th>
						<td>
							<label for="qp-menu-new-tab">
								<input type="checkbox" id="qp-menu-new-tab" name="<?php echo esc_attr( QP_HOLDINGS_CUSTOM_MENU_OPTION ); ?>[new_tab]" value="1" <?php checked( $config['new_tab'] ); ?>>
								Mở liên kết trong tab mới (<code>target="_blank" rel="noopener noreferrer"</code>) - khuyên dùng cho link ngoài.
							</label>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'Lưu cấu hình Menu' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Render the custom menu link item if enabled and matching position.
 *
 * @param string $position Vị trí hiện tại cần render (after_home, after_about, ...).
 * @param string $context  Ngữ cảnh giao diện: 'modern' (header.php), 'legacy-desktop' hoặc 'legacy-mobile' (header-legacy.php).
 */
function qp_holdings_render_custom_menu_item( string $position, string $context = 'modern' ): void {
	$config = qp_holdings_custom_menu_get();

	if ( empty( $config['enabled'] ) || empty( $config['url'] ) ) {
		return;
	}

	if ( $config['position'] !== $position ) {
		return;
	}

	$lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

	// Resolve Title
	$title = $config['title_vi'];
	if ( 'en' === $lang && ! empty( $config['title_en'] ) ) {
		$title = $config['title_en'];
	} elseif ( 'zh' === $lang && ! empty( $config['title_zh'] ) ) {
		$title = $config['title_zh'];
	}

	if ( '' === trim( $title ) ) {
		return;
	}

	// Resolve URL
	$url = $config['url'];
	if ( 'en' === $lang && ! empty( $config['url_en'] ) ) {
		$url = $config['url_en'];
	} elseif ( 'zh' === $lang && ! empty( $config['url_zh'] ) ) {
		$url = $config['url_zh'];
	}

	$target    = $config['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
	$is_active = false;

	// Check active state if this is an internal link.
	$current_url = home_url( add_query_arg( array() ) );
	if ( untrailingslashit( $url ) === untrailingslashit( $current_url ) ) {
		$is_active = true;
	}

	if ( 'legacy-mobile' === $context ) {
		$active_attr = $is_active ? ' active current-menu-item' : '';
		?>
		<li class="qp-mobile-menu__item menu-item menu-item-type-custom menu-item-object-custom<?php echo esc_attr( $active_attr ); ?>">
			<a href="<?php echo esc_url( $url ); ?>" class="qp-mobile-menu__link"<?php echo $target; ?>>
				<span><?php echo esc_html( $title ); ?></span>
			</a>
		</li>
		<?php
	} elseif ( 'legacy-desktop' === $context ) {
		$active_attr = $is_active ? ' active current-menu-item' : '';
		?>
		<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-design-default<?php echo esc_attr( $active_attr ); ?>">
			<a href="<?php echo esc_url( $url ); ?>" class="nav-top-link"<?php echo $target; ?>><?php echo esc_html( $title ); ?></a>
		</li>
		<?php
	} else {
		// Modern header.
		$active_class = $is_active ? ' active' : '';
		?>
		<li><a href="<?php echo esc_url( $url ); ?>" class="nav-link<?php echo esc_attr( $active_class ); ?>"<?php echo $target; ?>><?php echo esc_html( $title ); ?></a></li>
		<?php
	}
}
