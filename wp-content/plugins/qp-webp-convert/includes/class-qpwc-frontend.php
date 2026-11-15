<?php
/**
 * Phát ảnh WebP ra website: bọc thẻ <img> trong <picture> khi đã có file .webp tương ứng.
 *
 * - Trình duyệt hỗ trợ WebP dùng <source type="image/webp">, trình duyệt khác dùng <img> gốc (WP-17).
 * - Không phụ thuộc Accept header nên không làm sai cache của LiteSpeed (WP-06).
 * - Tắt plugin hoặc tắt tùy chọn → HTML không bị đổi, website dùng lại ảnh gốc (WP-15).
 */

defined( 'ABSPATH' ) || defined( 'QPWC_TESTING' ) || exit;

final class QPWC_Frontend {

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'start_buffer' ), 2 );
		add_action( 'wp_head', array( __CLASS__, 'print_css' ), 1 );
	}

	public static function print_css(): void {
		if ( QPWC_Settings::enabled() ) {
			echo '<style id="qpwc-css">picture.qpwc{display:contents}</style>' . "\n";
		}
	}

	public static function start_buffer(): void {
		if ( ! QPWC_Settings::enabled() || is_admin() || is_feed() || is_embed() || wp_doing_ajax() || is_robots() ) {
			return;
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}
		ob_start( array( __CLASS__, 'filter_buffer' ) );
	}

	public static function filter_buffer( string $html ): string {
		try {
			return self::transform( $html, array( __CLASS__, 'resolve_webp_url' ) );
		} catch ( \Throwable $e ) {
			return $html;
		}
	}

	/**
	 * URL ảnh JPG/PNG trong uploads → URL .webp nếu file .webp tồn tại, ngược lại null.
	 */
	public static function resolve_webp_url( string $url ): ?string {
		static $cache = array();
		if ( isset( $cache[ $url ] ) ) {
			return $cache[ $url ] ?: null;
		}

		$result = '';
		$parts  = preg_split( '/(?=[?#])/', $url, 2 );
		$clean  = (string) $parts[0];
		$tail   = isset( $parts[1] ) ? (string) $parts[1] : '';

		if ( preg_match( '/\.(jpe?g|png)$/i', $clean ) ) {
			$up   = wp_get_upload_dir();
			$base = preg_replace( '#^https?:#i', '', (string) $up['baseurl'] );
			$cmp  = preg_replace( '#^https?:#i', '', $clean );
			if ( '' !== $base && 0 === strpos( $cmp, $base . '/' ) ) {
				$rel  = rawurldecode( substr( $cmp, strlen( $base ) ) );
				$path = $up['basedir'] . $rel;
				if ( is_file( $path . '.webp' ) ) {
					$result = $clean . '.webp' . $tail;
				}
			}
		}

		$cache[ $url ] = $result;
		return '' === $result ? null : $result;
	}

	/**
	 * Hàm thuần: nhận HTML + hàm tra URL, trả HTML đã bọc <picture>.
	 *
	 * @param callable $resolve fn(string $url): ?string
	 */
	public static function transform( string $html, callable $resolve ): string {
		if ( false === stripos( $html, '<img' ) ) {
			return $html;
		}
		// Bỏ qua <picture> có sẵn, script, style, comment, textarea, noscript.
		$pattern = '~(<!--.*?-->|<picture\b.*?</picture>|<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<noscript\b.*?</noscript>)|(<img\b[^>]*>)~is';
		$out     = preg_replace_callback(
			$pattern,
			static function ( array $m ) use ( $resolve ): string {
				if ( empty( $m[2] ) ) {
					return $m[0];
				}
				return self::wrap_img( $m[2], $resolve );
			},
			$html
		);
		return null === $out ? $html : $out;
	}

	private static function attr( string $tag, string $name ): ?string {
		if ( preg_match( '~\s' . preg_quote( $name, '~' ) . '\s*=\s*(["\'])(.*?)\1~is', $tag, $m ) ) {
			return html_entity_decode( $m[2], ENT_QUOTES );
		}
		return null;
	}

	private static function wrap_img( string $tag, callable $resolve ): string {
		$class = (string) self::attr( $tag, 'class' );
		if ( false !== strpos( $class, 'qpwc-skip' ) ) {
			return $tag;
		}
		$src = self::attr( $tag, 'src' );
		if ( null === $src || '' === $src ) {
			return $tag;
		}
		$webp_src = $resolve( $src );
		if ( null === $webp_src ) {
			return $tag;
		}

		$srcset  = self::attr( $tag, 'srcset' );
		$sizes   = self::attr( $tag, 'sizes' );
		$set_out = null;

		if ( null !== $srcset && '' !== trim( $srcset ) ) {
			$items = array();
			$all   = true;
			foreach ( explode( ',', $srcset ) as $cand ) {
				$cand = trim( $cand );
				if ( '' === $cand ) {
					continue;
				}
				$bits = preg_split( '/\s+/', $cand, 2 );
				$w    = $resolve( (string) $bits[0] );
				if ( null === $w ) {
					$all = false;
					break;
				}
				$items[] = $w . ( isset( $bits[1] ) ? ' ' . $bits[1] : '' );
			}
			if ( $all && ! empty( $items ) ) {
				$set_out = implode( ', ', $items );
			}
		}

		if ( null === $set_out ) {
			// Có srcset nhưng thiếu WebP cho một số cỡ → chỉ dùng ảnh chính, bỏ sizes.
			$set_out = $webp_src;
			$sizes   = null;
		}

		$source = '<source type="image/webp" srcset="' . esc_attr_safe( $set_out ) . '"'
			. ( null !== $sizes && '' !== $sizes ? ' sizes="' . esc_attr_safe( $sizes ) . '"' : '' ) . '>';

		return '<picture class="qpwc">' . $source . $tag . '</picture>';
	}
}

/** esc_attr khi chạy trong WordPress, htmlspecialchars khi chạy unit test độc lập. */
function esc_attr_safe( string $s ): string {
	return function_exists( 'esc_attr' ) ? esc_attr( $s ) : htmlspecialchars( $s, ENT_QUOTES );
}
