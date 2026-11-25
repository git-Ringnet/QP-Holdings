<?php
/**
 * Plugin Name: QP Phân quyền menu
 * Description: Tick chọn từng mục menu bên trái trang quản trị cho từng tài khoản. Mục không tick bị ẩn khỏi menu và không vào được bằng đường link trực tiếp. Quản trị viên luôn thấy đầy đủ.
 * Version: 1.0.0
 * Author: Ringnet
 * Requires PHP: 7.4
 * License: GPLv2 or later
 */

defined( 'ABSPATH' ) || exit;

final class QP_Menu_Permissions {

	const META_ON    = 'qpmp_enabled'; // 1 = tài khoản này bị giới hạn theo danh sách tick
	const META_ALLOW = 'qpmp_allow';   // mảng khóa: "slug-mục-lớn" và "slug-mục-lớn|slug-mục-con"

	/** Mục luôn được phép (trang đích sau đăng nhập, hồ sơ cá nhân) để không khóa người dùng ra ngoài. */
	const ALWAYS = array( 'index.php', 'profile.php', 'qph-overview' ); // qph-overview: trang Tổng quan của mu-plugin branding

	/** Menu gốc của người dùng hiện tại, chụp lại trước khi ẩn bớt (để chặn truy cập trực tiếp). */
	private static array $snapshot_menu    = array();
	private static array $snapshot_submenu = array();

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'filter_menu' ), PHP_INT_MAX );
		add_action( 'current_screen', array( __CLASS__, 'guard_request' ) );
		add_action( 'admin_bar_menu', array( __CLASS__, 'filter_admin_bar' ), PHP_INT_MAX );

		add_action( 'edit_user_profile', array( __CLASS__, 'render_user_box' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_user_box' ) );
	}

	/* ------------------------------------------------------------ ai bị giới hạn */

	/** Tài khoản có bị giới hạn không. Quản trị viên (manage_options) không bao giờ bị giới hạn. */
	public static function is_restricted( int $user_id ): bool {
		if ( ! $user_id || user_can( $user_id, 'manage_options' ) ) {
			return false;
		}
		return (bool) get_user_meta( $user_id, self::META_ON, true );
	}

	/** @return string[] */
	public static function allowed( int $user_id ): array {
		$a = get_user_meta( $user_id, self::META_ALLOW, true );
		return is_array( $a ) ? array_values( array_map( 'strval', $a ) ) : array();
	}

	/** Menu WordPress ghi "&amp;" trong slug (vd. danh mục), trình duyệt gửi về "&": đưa về một dạng. */
	public static function norm( string $slug ): string {
		return str_replace( '&amp;', '&', $slug );
	}

	public static function key( string $top, string $sub = '' ): string {
		$top = self::norm( $top );
		return '' === $sub ? $top : $top . '|' . self::norm( $sub );
	}

	private static function is_allowed( array $allow, string $top, string $sub = '' ): bool {
		$allow = array_map( array( __CLASS__, 'norm' ), $allow );
		$top   = self::norm( $top );
		$sub   = self::norm( $sub );
		if ( in_array( $top, self::ALWAYS, true ) || ( '' !== $sub && in_array( $sub, self::ALWAYS, true ) ) ) {
			return true;
		}
		if ( ! in_array( $top, $allow, true ) ) {
			return false; // mục lớn không tick → ẩn cả các mục con
		}
		return '' === $sub || in_array( self::key( $top, $sub ), $allow, true );
	}

	/* ------------------------------------------------------------ ẩn menu */

	public static function filter_menu(): void {
		global $menu, $submenu;
		self::$snapshot_menu    = is_array( $menu ) ? $menu : array();
		self::$snapshot_submenu = is_array( $submenu ) ? $submenu : array();

		$uid = get_current_user_id();
		if ( ! self::is_restricted( $uid ) ) {
			return;
		}
		$allow = self::allowed( $uid );

		foreach ( self::$snapshot_menu as $item ) {
			$top = (string) ( $item[2] ?? '' );
			if ( '' === $top || self::is_separator( $item ) ) {
				continue;
			}
			if ( ! self::is_allowed( $allow, $top ) ) {
				remove_menu_page( $top );
				continue;
			}
			foreach ( (array) ( self::$snapshot_submenu[ $top ] ?? array() ) as $sub_item ) {
				$sub = (string) ( $sub_item[2] ?? '' );
				if ( '' !== $sub && ! self::is_allowed( $allow, $top, $sub ) ) {
					remove_submenu_page( $top, $sub );
				}
			}
		}
	}

	private static function is_separator( array $item ): bool {
		return false !== strpos( (string) ( $item[4] ?? '' ), 'wp-menu-separator' );
	}

	/* ------------------------------------------------------------ chặn link trực tiếp */

	/**
	 * Tìm mục menu ứng với trang đang mở rồi kiểm tra quyền.
	 * Trang không nằm trong menu nào (admin-ajax, hồ sơ, tải ảnh…) không bị chặn.
	 */
	public static function guard_request( $screen ): void {
		$uid = get_current_user_id();
		if ( ! self::is_restricted( $uid ) || wp_doing_ajax() ) {
			return;
		}
		$found = self::locate( self::candidates( $screen ) );
		if ( ! $found ) {
			return;
		}
		[ $top, $sub ] = $found;
		if ( ! self::is_allowed( self::allowed( $uid ), $top, $sub ) ) {
			wp_die(
				esc_html__( 'Tài khoản của bạn không được phân quyền vào mục này. Vui lòng liên hệ quản trị viên.', 'qp-menu-permissions' ),
				esc_html__( 'Không có quyền truy cập', 'qp-menu-permissions' ),
				array(
					'response'  => 403,
					'back_link' => true,
				)
			);
		}
	}

	/**
	 * Các "slug menu" có thể ứng với trang đang mở, ưu tiên cụ thể trước.
	 *
	 * @return string[]
	 */
	private static function candidates( $screen ): array {
		global $pagenow;
		$file = (string) $pagenow;
		$get  = static function ( string $k ): string {
			return isset( $_GET[ $k ] ) ? sanitize_text_field( wp_unslash( $_GET[ $k ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		};

		$out = array();
		if ( '' !== $get( 'page' ) ) {
			$out[] = $get( 'page' );
			$out[] = $file . '?page=' . $get( 'page' );
		}

		$post_type = $screen && ! empty( $screen->post_type ) ? (string) $screen->post_type : $get( 'post_type' );
		$taxonomy  = $screen && ! empty( $screen->taxonomy ) ? (string) $screen->taxonomy : $get( 'taxonomy' );

		if ( in_array( $file, array( 'edit-tags.php', 'term.php' ), true ) && '' !== $taxonomy ) {
			if ( '' !== $post_type ) {
				$out[] = 'edit-tags.php?taxonomy=' . $taxonomy . '&post_type=' . $post_type;
				$out[] = 'edit-tags.php?taxonomy=' . $taxonomy . '&amp;post_type=' . $post_type;
			}
			$out[] = 'edit-tags.php?taxonomy=' . $taxonomy;
		}

		if ( in_array( $file, array( 'post-new.php' ), true ) ) {
			$out[] = '' === $post_type || 'post' === $post_type ? 'post-new.php' : 'post-new.php?post_type=' . $post_type;
		}
		if ( in_array( $file, array( 'edit.php', 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' ), true ) && '' !== $post_type ) {
			if ( 'attachment' === $post_type ) {
				$out[] = 'upload.php';
			} elseif ( 'post' === $post_type ) {
				$out[] = 'edit.php';
			} else {
				$out[] = 'edit.php?post_type=' . $post_type;
			}
		}
		if ( 'media-new.php' === $file ) {
			$out[] = 'media-new.php';
			$out[] = 'upload.php';
		}

		$out[] = $file;
		return array_values( array_unique( array_filter( $out ) ) );
	}

	/**
	 * Tìm slug trong menu gốc (đã chụp trước khi ẩn).
	 *
	 * @return array{0:string,1:string}|null [mục lớn, mục con ('' nếu là mục lớn)]
	 */
	private static function locate( array $candidates ): ?array {
		foreach ( $candidates as $c ) {
			foreach ( self::$snapshot_submenu as $top => $items ) {
				foreach ( (array) $items as $it ) {
					if ( isset( $it[2] ) && (string) $it[2] === $c ) {
						return array( (string) $top, $c );
					}
				}
			}
			foreach ( self::$snapshot_menu as $it ) {
				if ( isset( $it[2] ) && (string) $it[2] === $c ) {
					return array( $c, '' );
				}
			}
		}
		return null;
	}

	/* ------------------------------------------------------------ thanh quản trị (+ Mới) */

	public static function filter_admin_bar( \WP_Admin_Bar $bar ): void {
		$uid = get_current_user_id();
		if ( ! is_admin_bar_showing() || ! self::is_restricted( $uid ) ) {
			return;
		}
		$allow = self::allowed( $uid );
		foreach ( get_post_types( array( 'show_in_admin_bar' => true ), 'names' ) as $pt ) {
			// Ưu tiên mục con "Thêm mới", không có thì theo mục danh sách.
			if ( 'post' === $pt ) {
				$cands = array( 'post-new.php', 'edit.php' );
			} elseif ( 'attachment' === $pt ) {
				$cands = array( 'media-new.php', 'upload.php' );
			} else {
				$cands = array( 'post-new.php?post_type=' . $pt, 'edit.php?post_type=' . $pt );
			}
			$found = self::locate( $cands );
			if ( $found && ! self::is_allowed( $allow, $found[0], $found[1] ) ) {
				$bar->remove_node( 'new-' . ( 'attachment' === $pt ? 'media' : $pt ) );
			}
		}
		// Mục "Mới" không còn mục con nào → bỏ luôn.
		$kids = array_filter(
			$bar->get_nodes() ?: array(),
			static function ( $n ) {
				return 'new-content' === $n->parent;
			}
		);
		if ( ! $kids ) {
			$bar->remove_node( 'new-content' );
		}
	}

	/* ------------------------------------------------------------ giao diện tick chọn */

	public static function render_user_box( \WP_User $user ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		global $menu, $submenu;
		echo '<h2 id="qpmp">' . esc_html__( 'Phân quyền menu quản trị', 'qp-menu-permissions' ) . '</h2>';

		if ( user_can( $user, 'manage_options' ) ) {
			echo '<div class="notice notice-info inline" style="margin:8px 0 16px;max-width:820px"><p>'
				. wp_kses(
					__( 'Tài khoản này đang có vai trò <strong>Quản trị viên</strong> nên luôn thấy đầy đủ các mục, không giới hạn được.<br>Muốn phân quyền menu cho tài khoản này: đổi <strong>Vai trò</strong> (mục Name ở trên) sang <strong>Biên tập viên</strong> hoặc <strong>Tác giả</strong> → bấm <strong>Cập nhật</strong> → danh sách tick chọn sẽ hiện ở đây.', 'qp-menu-permissions' ),
					array(
						'strong' => array(),
						'br'     => array(),
					)
				)
				. '</p></div>';
			return;
		}

		$on    = (bool) get_user_meta( $user->ID, self::META_ON, true );
		$allow = self::allowed( $user->ID );
		wp_nonce_field( 'qpmp_save_' . $user->ID, 'qpmp_nonce' );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Giới hạn menu', 'qp-menu-permissions' ); ?></th>
				<td>
					<label><input type="checkbox" name="qpmp_enabled" id="qpmp-enabled" value="1" <?php checked( $on ); ?>>
					<?php esc_html_e( 'Chỉ cho tài khoản này thấy và dùng các mục được tick bên dưới', 'qp-menu-permissions' ); ?></label>
					<p class="description"><?php esc_html_e( 'Không tick: tài khoản dùng đầy đủ quyền theo vai trò. Mục không tick sẽ bị ẩn khỏi menu và không vào được bằng đường link trực tiếp. "Tổng quan" và "Hồ sơ" luôn hiện. Tick chỉ ẩn bớt, không cấp thêm quyền ngoài vai trò của tài khoản.', 'qp-menu-permissions' ); ?></p>
				</td>
			</tr>
			<tr id="qpmp-list"<?php echo $on ? '' : ' style="opacity:.5"'; ?>>
				<th scope="row"><?php esc_html_e( 'Mục được phép', 'qp-menu-permissions' ); ?>
					<p style="font-weight:400"><a href="#" id="qpmp-all"><?php esc_html_e( 'Chọn tất cả', 'qp-menu-permissions' ); ?></a> · <a href="#" id="qpmp-none"><?php esc_html_e( 'Bỏ chọn tất cả', 'qp-menu-permissions' ); ?></a></p>
				</th>
				<td>
					<div style="columns:2 320px;column-gap:32px">
					<?php
					foreach ( (array) $menu as $item ) {
						$top = (string) ( $item[2] ?? '' );
						if ( '' === $top || self::is_separator( $item ) ) {
							continue;
						}
						$always  = in_array( $top, self::ALWAYS, true );
						$can     = user_can( $user, (string) ( $item[1] ?? 'read' ) );
						foreach ( (array) ( $submenu[ $top ] ?? array() ) as $sub_item ) {
							// Có quyền ở một mục con (vd. "Mã tùy chỉnh" trong Cấu hình) thì mục lớn cũng tick được.
							$can = $can || user_can( $user, (string) ( $sub_item[1] ?? 'read' ) );
						}
						$checked = $always || in_array( self::key( $top ), array_map( array( __CLASS__, 'norm' ), $allow ), true );
						echo '<fieldset class="qpmp-group" style="break-inside:avoid;margin:0 0 12px">';
						printf(
							'<label style="font-weight:600"><input type="checkbox" class="qpmp-top" name="qpmp_allow[]" value="%1$s" %2$s %3$s> %4$s</label>%5$s',
							esc_attr( self::key( $top ) ),
							checked( $checked, true, false ),
							disabled( $always || ! $can, true, false ),
							esc_html( self::label( (string) ( $item[0] ?? $top ) ) ),
							$always ? ' <em>' . esc_html__( '(luôn hiện)', 'qp-menu-permissions' ) . '</em>' : ( $can ? '' : ' <em>' . esc_html__( '(vai trò không có quyền)', 'qp-menu-permissions' ) . '</em>' )
						);
						$subs = (array) ( $submenu[ $top ] ?? array() );
						if ( count( $subs ) > 1 && ! $always ) {
							echo '<div style="margin:4px 0 0 24px">';
							foreach ( $subs as $sub_item ) {
								$sub = (string) ( $sub_item[2] ?? '' );
								if ( '' === $sub ) {
									continue;
								}
								$k     = self::key( $top, $sub );
								$can_s = user_can( $user, (string) ( $sub_item[1] ?? 'read' ) );
								printf(
									'<label style="display:block;margin:2px 0"><input type="checkbox" class="qpmp-sub" name="qpmp_allow[]" value="%1$s" %2$s %3$s> %4$s%5$s</label>',
									esc_attr( $k ),
									checked( in_array( $k, array_map( array( __CLASS__, 'norm' ), $allow ), true ), true, false ),
									disabled( ! $can_s, true, false ),
									esc_html( self::label( (string) ( $sub_item[0] ?? $sub ) ) ),
									$can_s ? '' : ' <em>' . esc_html__( '(vai trò không có quyền)', 'qp-menu-permissions' ) . '</em>'
								);
							}
							echo '</div>';
						}
						echo '</fieldset>';
					}
					?>
					</div>
				</td>
			</tr>
		</table>
		<script>
		( function () {
			var on = document.getElementById( 'qpmp-enabled' ), list = document.getElementById( 'qpmp-list' );
			on.addEventListener( 'change', function () { list.style.opacity = on.checked ? 1 : .5; } );
			list.querySelectorAll( '.qpmp-group' ).forEach( function ( g ) {
				var top = g.querySelector( '.qpmp-top' ), subs = g.querySelectorAll( '.qpmp-sub' );
				// Tick / bỏ tick mục lớn → tick / bỏ tick cả mục con; tick mục con → tự tick mục lớn.
				top.addEventListener( 'change', function () { subs.forEach( function ( s ) { if ( ! s.disabled ) { s.checked = top.checked; } } ); } );
				subs.forEach( function ( s ) { s.addEventListener( 'change', function () { if ( s.checked && ! top.disabled ) { top.checked = true; } } ); } );
			} );
			function all( v ) { list.querySelectorAll( 'input[type=checkbox]' ).forEach( function ( c ) { if ( ! c.disabled ) { c.checked = v; } } ); }
			document.getElementById( 'qpmp-all' ).addEventListener( 'click', function ( e ) { e.preventDefault(); all( true ); } );
			document.getElementById( 'qpmp-none' ).addEventListener( 'click', function ( e ) { e.preventDefault(); all( false ); } );
		}() );
		</script>
		<?php
	}

	/** Bỏ thẻ HTML và số đếm (bong bóng "18" cạnh tên mục) khỏi tên menu. */
	private static function label( string $html ): string {
		$html = preg_replace( '#<span[^>]*class="[^"]*(count|pending|update-plugins|awaiting-mod|menu-counter)[^"]*"[^>]*>.*?</span>(\s*</span>)?#is', '', $html );
		return trim( wp_strip_all_tags( (string) $html ) );
	}

	public static function save_user_box( int $user_id ): void {
		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['qpmp_nonce'] ) ) {
			return;
		}
		check_admin_referer( 'qpmp_save_' . $user_id, 'qpmp_nonce' );
		if ( user_can( $user_id, 'manage_options' ) ) {
			return;
		}
		$on    = ! empty( $_POST['qpmp_enabled'] );
		$allow = isset( $_POST['qpmp_allow'] ) && is_array( $_POST['qpmp_allow'] )
			? array_values( array_unique( array_map( array( __CLASS__, 'norm' ), array_map( 'sanitize_text_field', wp_unslash( $_POST['qpmp_allow'] ) ) ) ) )
			: array();
		update_user_meta( $user_id, self::META_ON, $on ? 1 : 0 );
		update_user_meta( $user_id, self::META_ALLOW, $allow );
	}
}

QP_Menu_Permissions::init();
