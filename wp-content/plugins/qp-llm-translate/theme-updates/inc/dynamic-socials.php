<?php
/**
 * Editable social icon links and favicon admin guidance.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

const QP_HOLDINGS_SOCIAL_LINKS_OPTION = 'qp_holdings_social_links';

/**
 * Built-in icon catalog. The Figma exports are the visual source of truth.
 *
 * @return array<string,array{label:string,asset:string}>
 */
function qp_holdings_social_icon_catalog(): array {
	return array(
		'facebook'  => array( 'label' => 'Facebook', 'asset' => 'social-facebook-figma.svg' ),
		'tiktok'    => array( 'label' => 'TikTok', 'asset' => 'social-tiktok-figma.svg' ),
		'zalo'      => array( 'label' => 'Zalo', 'asset' => 'social-zalo-figma.svg' ),
		'youtube'   => array( 'label' => 'YouTube', 'asset' => 'social-youtube-figma.svg' ),
		'linkedin'  => array( 'label' => 'LinkedIn', 'asset' => 'social-linkedin-figma.svg' ),
		'instagram' => array( 'label' => 'Instagram', 'asset' => 'social-instagram.svg' ),
		'custom'    => array( 'label' => 'Icon tùy chỉnh', 'asset' => '' ),
	);
}

/**
 * Default social rows shown before an admin saves custom values.
 *
 * @return array<int,array{label:string,url:string,icon:string,icon_url:string}>
 */
function qp_holdings_social_links_defaults(): array {
	return array(
		array(
			'label'    => 'Facebook',
			'url'      => 'https://www.facebook.com/qpholdings.vn',
			'icon'     => 'facebook',
			'icon_url' => '',
		),
		array(
			'label'    => 'TikTok',
			'url'      => 'https://www.tiktok.com/@qp.holdings',
			'icon'     => 'tiktok',
			'icon_url' => '',
		),
		array(
			'label'    => 'Zalo',
			'url'      => 'https://zalo.me/qpholdings',
			'icon'     => 'zalo',
			'icon_url' => '',
		),
		array(
			'label'    => 'YouTube',
			'url'      => 'https://www.youtube.com/channel/UCPFzaFQuP5-jwCTeNMu7-Tw',
			'icon'     => 'youtube',
			'icon_url' => '',
		),
		array(
			'label'    => 'LinkedIn',
			'url'      => 'https://www.linkedin.com/in/qp-holdings-608a6740a/',
			'icon'     => 'linkedin',
			'icon_url' => '',
		),
	);
}

/**
 * Sanitize social rows from admin input or stored options.
 *
 * @param mixed $items Raw rows.
 * @return array<int,array{label:string,url:string,icon:string,icon_url:string}>
 */
function qp_holdings_sanitize_social_links( $items ): array {
	if ( ! is_array( $items ) ) {
		return array();
	}

	$catalog = qp_holdings_social_icon_catalog();
	$output  = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		if ( ! empty( $item['delete'] ) ) {
			continue;
		}

		$url = isset( $item['url'] ) ? esc_url_raw( trim( (string) wp_unslash( $item['url'] ) ) ) : '';
		if ( '' === $url ) {
			continue;
		}

		$icon = isset( $item['icon'] ) ? sanitize_key( wp_unslash( $item['icon'] ) ) : 'custom';
		if ( ! isset( $catalog[ $icon ] ) ) {
			$icon = 'custom';
		}

		$label = isset( $item['label'] ) ? sanitize_text_field( wp_unslash( $item['label'] ) ) : '';
		if ( '' === $label ) {
			$label = $catalog[ $icon ]['label'];
		}

		$icon_url = isset( $item['icon_url'] ) ? esc_url_raw( trim( (string) wp_unslash( $item['icon_url'] ) ) ) : '';
		if ( 'custom' === $icon && '' === $icon_url ) {
			continue;
		}

		$output[] = array(
			'label'    => $label,
			'url'      => $url,
			'icon'     => $icon,
			'icon_url' => $icon_url,
		);
	}

	return $output;
}

/**
 * Get social rows. Defaults are used until an admin explicitly saves the page.
 *
 * @return array<int,array{label:string,url:string,icon:string,icon_url:string}>
 */
function qp_holdings_get_social_links(): array {
	$stored = get_option( QP_HOLDINGS_SOCIAL_LINKS_OPTION, null );
	if ( null === $stored ) {
		return qp_holdings_social_links_defaults();
	}

	return qp_holdings_sanitize_social_links( $stored );
}

/**
 * Resolve the public URL for a social icon.
 *
 * @param array{icon:string,icon_url:string} $item Social row.
 */
function qp_holdings_social_icon_src( array $item ): string {
	// A media-picked / custom icon URL always wins, regardless of the preset.
	if ( ! empty( $item['icon_url'] ) ) {
		return esc_url_raw( $item['icon_url'] );
	}

	$icon = isset( $item['icon'] ) ? sanitize_key( $item['icon'] ) : 'custom';

	$catalog = qp_holdings_social_icon_catalog();
	if ( empty( $catalog[ $icon ]['asset'] ) ) {
		return '';
	}

	$asset = 'assets/site/' . $catalog[ $icon ]['asset'];
	$url   = get_template_directory_uri() . '/' . $asset;
	if ( function_exists( 'qp_holdings_asset_version' ) ) {
		$url = add_query_arg( 'v', qp_holdings_asset_version( $asset ), $url );
	}

	return $url;
}

/**
 * Social URLs for Organization schema sameAs.
 *
 * @return string[]
 */
function qp_holdings_social_same_as_urls(): array {
	$urls = array();
	foreach ( qp_holdings_get_social_links() as $item ) {
		$url = $item['url'];
		if ( preg_match( '#^https?://#i', $url ) ) {
			$urls[] = $url;
		}
	}

	return array_values( array_unique( $urls ) );
}

/**
 * Render a social icon list.
 *
 * @param array{class?:string,aria_label?:string} $args Render args.
 */
function qp_holdings_render_social_links( array $args = array() ): void {
	$links = qp_holdings_get_social_links();
	if ( ! $links ) {
		return;
	}

	$class      = isset( $args['class'] ) ? sanitize_html_class( $args['class'] ) : 'footer-socials';
	$aria_label = isset( $args['aria_label'] ) ? (string) $args['aria_label'] : 'Mạng xã hội';

	echo '<ul class="' . esc_attr( $class ) . '" aria-label="' . esc_attr( $aria_label ) . '">';
	foreach ( $links as $item ) {
		$icon_src = qp_holdings_social_icon_src( $item );
		if ( '' === $icon_src ) {
			continue;
		}

		echo '<li>';
		echo '<a href="' . esc_url( $item['url'] ) . '" target="_blank" rel="noopener" aria-label="' . esc_attr( $item['label'] ) . '">';
		echo '<img src="' . esc_url( $icon_src ) . '" width="32" height="32" alt="" aria-hidden="true" loading="lazy" decoding="async">';
		echo '</a>';
		echo '</li>';
	}
	echo '</ul>';
}

/** Register admin page under Appearance. */
function qp_holdings_social_admin_menu(): void {
	$hook = add_theme_page(
		'QP Social Icons & Favicon',
		'QP Social Icons',
		'edit_theme_options',
		'qp-holdings-social-icons',
		'qp_holdings_social_admin_page'
	);

	// Load the WordPress media frame scripts only on this settings screen.
	if ( $hook ) {
		add_action(
			'admin_enqueue_scripts',
			static function ( string $current ) use ( $hook ): void {
				if ( $current === $hook ) {
					wp_enqueue_media();
				}
			}
		);
	}
}
add_action( 'admin_menu', 'qp_holdings_social_admin_menu' );

/** Save social settings from the admin page. */
function qp_holdings_save_social_links(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'qp-holdings' ) );
	}

	check_admin_referer( 'qp_holdings_save_social_links', 'qp_holdings_social_nonce' );

	$raw   = isset( $_POST['social_links'] ) ? wp_unslash( $_POST['social_links'] ) : array();
	$links = qp_holdings_sanitize_social_links( $raw );
	update_option( QP_HOLDINGS_SOCIAL_LINKS_OPTION, $links, false );

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'qp-holdings-social-icons',
				'updated' => '1',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
add_action( 'admin_post_qp_holdings_save_social_links', 'qp_holdings_save_social_links' );

/** Render one admin table row. */
function qp_holdings_social_admin_row( $index, array $item = array() ): void {
	$catalog  = qp_holdings_social_icon_catalog();
	$label    = isset( $item['label'] ) ? (string) $item['label'] : '';
	$url      = isset( $item['url'] ) ? (string) $item['url'] : '';
	$icon     = isset( $item['icon'] ) ? (string) $item['icon'] : 'facebook';
	$icon_url = isset( $item['icon_url'] ) ? (string) $item['icon_url'] : '';
	$field    = 'social_links[' . (string) $index . ']';
	?>
	<tr class="qp-social-row">
		<td>
			<input type="text" class="regular-text" name="<?php echo esc_attr( $field ); ?>[label]" value="<?php echo esc_attr( $label ); ?>" placeholder="Facebook">
		</td>
		<td>
			<input type="url" class="large-text" name="<?php echo esc_attr( $field ); ?>[url]" value="<?php echo esc_attr( $url ); ?>" placeholder="https://...">
		</td>
		<td>
			<select class="qp-social-icon-select" name="<?php echo esc_attr( $field ); ?>[icon]">
				<?php foreach ( $catalog as $key => $icon_meta ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon, $key ); ?>><?php echo esc_html( $icon_meta['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<div class="qp-social-icon-field">
				<span class="qp-social-icon-preview" aria-hidden="true">
					<?php if ( '' !== $icon_url ) : ?>
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="32" height="32">
					<?php endif; ?>
				</span>
				<input type="url" class="large-text qp-social-icon-url" name="<?php echo esc_attr( $field ); ?>[icon_url]" value="<?php echo esc_attr( $icon_url ); ?>" placeholder="https://.../icon.svg">
				<button type="button" class="button qp-social-media-btn">Chọn từ thư viện</button>
				<button type="button" class="button-link qp-social-media-clear"<?php echo '' === $icon_url ? ' style="display:none;"' : ''; ?>>Xóa icon</button>
			</div>
			<p class="description">Chọn ảnh từ <strong>Thư viện Media</strong> hoặc dán URL. Khi có icon tùy chỉnh, hệ thống tự dùng icon này thay cho icon mặc định.</p>
		</td>
		<td>
			<label><input type="checkbox" name="<?php echo esc_attr( $field ); ?>[delete]" value="1"> Xóa</label>
		</td>
	</tr>
	<?php
}

/** Render admin page. */
function qp_holdings_social_admin_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$links       = qp_holdings_get_social_links();
	$favicon_url = get_template_directory_uri() . '/assets/index_files/favicon-192.png?v=' . rawurlencode( function_exists( 'qp_holdings_asset_version' ) ? qp_holdings_asset_version( 'assets/index_files/favicon-192.png' ) : wp_get_theme()->get( 'Version' ) );
	?>
	<div class="wrap qp-social-admin">
		<h1>QP Social Icons & Favicon</h1>
		<style>
			.qp-social-icon-field { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
			.qp-social-icon-field .qp-social-icon-url { flex: 1 1 220px; }
			.qp-social-icon-preview { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 1px solid #dcdcde; border-radius: 6px; background: #193660; flex: 0 0 40px; }
			.qp-social-icon-preview img { display: block; width: 32px; height: 32px; object-fit: contain; }
		</style>
		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Đã lưu cấu hình icon mạng xã hội.</p></div>
		<?php endif; ?>

		<div class="card" style="max-width: 980px;">
			<h2>Favicon</h2>
			<p>Theme đang có favicon fallback mới. Nếu muốn thay favicon trong admin, vào <strong>Giao diện → Tùy biến → Site Identity → Site Icon</strong>. Khi Site Icon được set, WordPress sẽ tự xuất favicon và theme sẽ ưu tiên icon đó thay fallback.</p>
			<p><img src="<?php echo esc_url( $favicon_url ); ?>" width="64" height="64" alt="QP Holdings favicon preview" style="border-radius: 8px; vertical-align: middle; margin-right: 12px;"><a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>">Mở Site Icon</a></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="qp_holdings_save_social_links">
			<?php wp_nonce_field( 'qp_holdings_save_social_links', 'qp_holdings_social_nonce' ); ?>

			<h2>Icon mạng xã hội</h2>
			<p>Thêm, xóa hoặc sửa link/icon hiển thị ở footer và trang Liên hệ. Mỗi dòng có thể dùng icon mặc định (xuất từ Figma) hoặc <strong>chọn ảnh riêng từ Thư viện Media</strong>.</p>

			<table class="widefat striped" style="max-width: 1180px;">
				<thead>
					<tr>
						<th style="width: 16%;">Tên</th>
						<th style="width: 26%;">Link</th>
						<th style="width: 16%;">Icon mặc định</th>
						<th>Icon tùy chỉnh (Media)</th>
						<th style="width: 70px;">Xóa</th>
					</tr>
				</thead>
				<tbody id="qp-social-rows">
					<?php foreach ( $links as $index => $item ) : ?>
						<?php qp_holdings_social_admin_row( (int) $index, $item ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" id="qp-social-add-row">Thêm icon</button>
				<?php submit_button( 'Lưu thay đổi', 'primary', 'submit', false ); ?>
			</p>
		</form>

		<table style="display:none;"><tbody id="qp-social-row-template">
			<?php qp_holdings_social_admin_row( '__INDEX__' ); ?>
		</tbody></table>
	</div>
	<script>
	(function () {
		var addButton = document.getElementById('qp-social-add-row');
		var rows = document.getElementById('qp-social-rows');
		var template = document.getElementById('qp-social-row-template');

		function bindMedia(scope) {
			var buttons = (scope || document).querySelectorAll('.qp-social-media-btn');
			Array.prototype.forEach.call(buttons, function (btn) {
				if (btn.dataset.qpBound) {
					return;
				}
				btn.dataset.qpBound = '1';
				btn.addEventListener('click', function () {
					if (typeof wp === 'undefined' || !wp.media) {
						return;
					}
					var field = btn.closest('.qp-social-icon-field');
					var input = field.querySelector('.qp-social-icon-url');
					var preview = field.querySelector('.qp-social-icon-preview');
					var clearBtn = field.querySelector('.qp-social-media-clear');
					var frame = wp.media({
						title: 'Chọn icon',
						button: { text: 'Dùng icon này' },
						library: { type: 'image' },
						multiple: false
					});
					frame.on('select', function () {
						var att = frame.state().get('selection').first().toJSON();
						var src = att.url;
						if (att.sizes && att.sizes.thumbnail) {
							src = att.sizes.thumbnail.url;
						}
						input.value = att.url;
						preview.innerHTML = '<img src="' + src + '" alt="" width="32" height="32">';
						if (clearBtn) {
							clearBtn.style.display = '';
						}
					});
					frame.open();
				});
			});

			var clears = (scope || document).querySelectorAll('.qp-social-media-clear');
			Array.prototype.forEach.call(clears, function (clearBtn) {
				if (clearBtn.dataset.qpBound) {
					return;
				}
				clearBtn.dataset.qpBound = '1';
				clearBtn.addEventListener('click', function () {
					var field = clearBtn.closest('.qp-social-icon-field');
					field.querySelector('.qp-social-icon-url').value = '';
					field.querySelector('.qp-social-icon-preview').innerHTML = '';
					clearBtn.style.display = 'none';
				});
			});
		}

		bindMedia(document);

		if (addButton && rows && template) {
			addButton.addEventListener('click', function () {
				var index = 'new_' + Date.now();
				var html = template.innerHTML.replaceAll('__INDEX__', index);
				rows.insertAdjacentHTML('beforeend', html);
				bindMedia(rows.lastElementChild);
			});
		}
	})();
	</script>
	<?php
}
