<?php
/**
 * Chẩn đoán hosting: số liệu cho phần A của bảng nghiệm thu (WP-01 … WP-06, WP-08).
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Diagnostics {

	/** WebP 1×1 hợp lệ, dùng khi không có GD để tạo file thử. */
	const TINY_WEBP_B64 = 'UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA';

	/**
	 * @return array<int, array{code:string,label:string,value:string,level:string}> level: ok | warn | fail | info
	 */
	public static function collect( bool $with_http = true ): array {
		$rows = array();
		$add  = static function ( string $code, string $label, string $value, string $level = 'info' ) use ( &$rows ): void {
			$rows[] = array(
				'code'  => $code,
				'label' => $label,
				'value' => $value,
				'level' => $level,
			);
		};

		// WP-01
		$gd_webp = function_exists( 'imagewebp' );
		$gd_ver  = function_exists( 'gd_info' ) ? ( gd_info()['GD Version'] ?? '' ) : '';
		$im_webp = false;
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			try {
				$im_webp = in_array( 'WEBP', \Imagick::queryFormats( 'WEBP' ), true );
			} catch ( \Throwable $e ) {
				$im_webp = false;
			}
		}
		$add( 'WP-01', 'GD hỗ trợ WebP', $gd_webp ? 'Có (' . $gd_ver . ')' : 'Không', $gd_webp ? 'ok' : 'warn' );
		$add( 'WP-01', 'Imagick hỗ trợ WebP', $im_webp ? 'Có' : 'Không / chưa cài', $im_webp ? 'ok' : 'info' );
		$engine = QPWC_Converter::engine();
		$add( 'WP-01', 'Thư viện plugin sẽ dùng', $engine, 'none' === $engine ? 'fail' : 'ok' );
		$add( 'WP-01', 'PHP / WordPress', PHP_VERSION . ' / ' . get_bloginfo( 'version' ), 'info' );
		$add( 'WP-01', 'Máy chủ web', isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '?', 'info' ); // phpcs:ignore

		// Chuyển thử thật một ảnh PNG trong suốt.
		if ( 'none' !== $engine ) {
			$add( 'WP-01', 'Chuyển thử 1 ảnh PNG', self::selftest(), 'ok' === substr( self::selftest(), 0, 2 ) ? 'ok' : 'fail' );
		}

		// WP-02
		$up = wp_get_upload_dir();
		$add( 'WP-02', 'Thư mục uploads', (string) $up['basedir'], 'info' );
		$writable = is_dir( $up['basedir'] ) && is_writable( $up['basedir'] );
		$probe    = trailingslashit( $up['basedir'] ) . 'qpwc-probe.webp';
		$wrote    = false;
		if ( $writable ) {
			$wrote = false !== @file_put_contents( $probe, self::tiny_webp() );
		}
		$add( 'WP-02', 'Ghi được file .webp vào uploads', $wrote ? 'Có' : 'Không', $wrote ? 'ok' : 'fail' );

		// WP-03
		$free  = @disk_free_space( $up['basedir'] );
		$total = @disk_total_space( $up['basedir'] );
		$add( 'WP-03', 'Dung lượng trống của ổ chứa website', false !== $free ? size_format( (float) $free, 2 ) : 'không đọc được', 'info' );
		$add( 'WP-03', 'Tổng dung lượng ổ (có thể là của cả máy chủ, không phải gói hosting)', false !== $total ? size_format( (float) $total, 2 ) : 'không đọc được', 'info' );
		$add( 'WP-03', 'Dung lượng thư mục uploads', 'Bấm nút "Đo dung lượng uploads" bên dưới', 'info' );

		// WP-04
		$add( 'WP-04', 'memory_limit', (string) ini_get( 'memory_limit' ), 'info' );
		$add( 'WP-04', 'WP_MEMORY_LIMIT / WP_MAX_MEMORY_LIMIT', ( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : '?' ) . ' / ' . ( defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : '?' ), 'info' );
		$add( 'WP-04', 'max_execution_time', (string) ini_get( 'max_execution_time' ) . ' giây', 'info' );
		$add( 'WP-04', 'upload_max_filesize', (string) ini_get( 'upload_max_filesize' ), 'info' );
		$add( 'WP-04', 'post_max_size', (string) ini_get( 'post_max_size' ), 'info' );
		$limit = QPWC_Converter::memory_limit_bytes();
		if ( $limit > 0 ) {
			$avail  = max( 0, $limit - memory_get_usage( true ) );
			$max_px = (int) floor( ( $avail - QPWC_Converter::BASE_OVERHEAD ) / QPWC_Converter::BYTES_PER_PIXEL );
			$add( 'WP-04', 'Ảnh lớn nhất xử lý được (ước tính theo RAM hiện có)', number_format_i18n( max( 0, $max_px ) ) . ' điểm ảnh (~' . round( sqrt( max( 0, $max_px ) * 1.5 ) ) . ' × ' . round( sqrt( max( 0, $max_px ) / 1.5 ) ) . ' px)', 'info' );
		}

		// WP-05
		if ( $with_http && $wrote ) {
			$url  = trailingslashit( $up['baseurl'] ) . 'qpwc-probe.webp';
			$resp = wp_remote_head( $url, array( 'timeout' => 10, 'sslverify' => false ) );
			if ( is_wp_error( $resp ) ) {
				$add( 'WP-05', 'Content-Type của file .webp', 'Không gọi được: ' . $resp->get_error_message(), 'warn' );
			} else {
				$ct = (string) wp_remote_retrieve_header( $resp, 'content-type' );
				$add( 'WP-05', 'Content-Type của file .webp (' . wp_remote_retrieve_response_code( $resp ) . ')', $ct, 0 === strpos( $ct, 'image/webp' ) ? 'ok' : 'fail' );
			}
		}
		if ( $wrote ) {
			@unlink( $probe );
		}

		// WP-06
		$lscache = defined( 'LSCWP_V' ) || class_exists( 'LiteSpeed\Core' );
		$add( 'WP-06', 'LiteSpeed Cache', $lscache ? 'Đang bật' . ( defined( 'LSCWP_V' ) ? ' (v' . LSCWP_V . ')' : '' ) : 'Không thấy', 'info' );

		return $rows;
	}

	public static function tiny_webp(): string {
		return (string) base64_decode( self::TINY_WEBP_B64 ); // phpcs:ignore
	}

	/** Tạo PNG trong suốt tạm, chuyển sang WebP rồi xóa. */
	public static function selftest(): string {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}
		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			return $cached = 'Lỗi: thiếu GD để tạo ảnh thử';
		}
		$dir = get_temp_dir();
		$png = $dir . 'qpwc-selftest-' . wp_generate_password( 6, false ) . '.png';
		$im  = imagecreatetruecolor( 300, 300 );
		imagealphablending( $im, false );
		imagesavealpha( $im, true );
		imagefill( $im, 0, 0, imagecolorallocatealpha( $im, 0, 0, 0, 127 ) );
		for ( $i = 0; $i < 300; $i += 3 ) {
			imageline( $im, $i, 0, 300 - $i, 300, imagecolorallocatealpha( $im, $i % 255, 90, 200, 20 ) );
		}
		imagepng( $im, $png );
		imagedestroy( $im );
		$r = QPWC_Converter::convert( $png, 80, array( 'force' => true ) );
		@unlink( $png );
		@unlink( $png . '.webp' );
		if ( QPWC_Converter::CONVERTED === $r['status'] ) {
			return $cached = 'OK (' . $r['engine'] . ': ' . $r['src_bytes'] . ' B → ' . $r['webp_bytes'] . ' B)';
		}
		return $cached = 'Lỗi: ' . $r['status'] . ' / ' . $r['reason'];
	}

	/** Đo dung lượng thư mục uploads (có giới hạn thời gian). */
	public static function uploads_size( int $time_limit = 20 ): array {
		$up    = wp_get_upload_dir();
		$t0    = microtime( true );
		$bytes = 0;
		$webp  = 0;
		$files = 0;
		$full  = true;
		if ( is_dir( $up['basedir'] ) ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $up['basedir'], \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( ! $f->isFile() ) {
					continue;
				}
				$size   = (int) $f->getSize();
				$bytes += $size;
				++$files;
				if ( preg_match( '/\.(jpe?g|png)\.webp$/i', $f->getFilename() ) ) {
					$webp += $size;
				}
				if ( ( microtime( true ) - $t0 ) > $time_limit ) {
					$full = false;
					break;
				}
			}
		}
		return array(
			'bytes'    => $bytes,
			'webp'     => $webp,
			'files'    => $files,
			'complete' => $full,
		);
	}

	/** Văn bản thuần để copy vào cột Ghi chú của Excel. */
	public static function as_text( array $rows ): string {
		$lines = array( 'QP WebP Convert — chẩn đoán ' . wp_date( 'd/m/Y H:i' ) . ' — ' . home_url() );
		foreach ( $rows as $r ) {
			$lines[] = sprintf( '[%s] %s: %s%s', $r['code'], $r['label'], $r['value'], 'fail' === $r['level'] ? '  <-- LỖI' : ( 'warn' === $r['level'] ? '  <-- CẦN XEM' : '' ) );
		}
		return implode( "\n", $lines );
	}
}
