<?php
/**
 * Chuyển một file JPG/PNG thành file .webp nằm cạnh (anh.jpg → anh.jpg.webp).
 *
 * Lớp này KHÔNG phụ thuộc WordPress (để chạy unit test độc lập). Không bao giờ ném exception ra ngoài:
 * mọi lỗi trả về dạng mảng kết quả để phía gọi ghi log, website không bị trắng trang.
 */

defined( 'ABSPATH' ) || defined( 'QPWC_TESTING' ) || exit;

final class QPWC_Converter {

	const CONVERTED = 'converted';
	const SKIPPED   = 'skipped';
	const FAILED    = 'failed';

	/** Bộ nhớ ước tính cho mỗi điểm ảnh khi giải nén + mã hóa (byte). */
	const BYTES_PER_PIXEL = 6;
	/** Bộ nhớ nền cho thư viện (byte). */
	const BASE_OVERHEAD = 16777216;

	/** Đường dẫn file WebP tương ứng với file gốc. */
	public static function webp_path( string $src ): string {
		return $src . '.webp';
	}

	/** Thư viện sẽ dùng: imagick | gd | none. */
	public static function engine(): string {
		if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
			try {
				if ( in_array( 'WEBP', \Imagick::queryFormats( 'WEBP' ), true ) ) {
					return 'imagick';
				}
			} catch ( \Throwable $e ) {
				// bỏ qua, thử GD
			}
		}
		if ( function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' ) ) {
			return 'gd';
		}
		return 'none';
	}

	/** Đọc memory_limit (byte). -1 = không giới hạn. */
	public static function memory_limit_bytes(): int {
		$v = trim( (string) ini_get( 'memory_limit' ) );
		if ( '' === $v || '-1' === $v ) {
			return -1;
		}
		$n    = (int) $v;
		$unit = strtolower( substr( $v, -1 ) );
		if ( 'g' === $unit ) {
			return $n * 1024 * 1024 * 1024;
		}
		if ( 'm' === $unit ) {
			return $n * 1024 * 1024;
		}
		if ( 'k' === $unit ) {
			return $n * 1024;
		}
		return $n;
	}

	/** Bộ nhớ cần để xử lý ảnh w×h. */
	public static function estimate_memory( int $width, int $height ): int {
		return $width * $height * self::BYTES_PER_PIXEL + self::BASE_OVERHEAD;
	}

	/**
	 * @param array{force?:bool, memory_limit?:int} $opts
	 * @return array{status:string, reason:string, src_bytes:int, webp_bytes:int, webp_path:string, engine:string}
	 */
	public static function convert( string $src, int $quality = 80, array $opts = array() ): array {
		$res = array(
			'status'     => self::FAILED,
			'reason'     => '',
			'src_bytes'  => 0,
			'webp_bytes' => 0,
			'webp_path'  => self::webp_path( $src ),
			'engine'     => 'none',
		);

		try {
			$quality = max( 1, min( 100, $quality ) );

			if ( ! is_file( $src ) || ! is_readable( $src ) ) {
				$res['reason'] = 'missing_file';
				return $res;
			}
			$res['src_bytes'] = (int) filesize( $src );

			// Dựa vào nội dung file, không tin đuôi file.
			$info = @getimagesize( $src );
			if ( false === $info ) {
				$res['reason'] = 'not_image';
				return $res;
			}
			$type = (int) $info[2];
			if ( IMAGETYPE_JPEG !== $type && IMAGETYPE_PNG !== $type ) {
				$res['status'] = self::SKIPPED;
				$res['reason'] = 'unsupported_type'; // GIF, SVG, WebP, ...
				return $res;
			}

			$dest = $res['webp_path'];
			if ( empty( $opts['force'] ) && is_file( $dest ) && filemtime( $dest ) >= filemtime( $src ) ) {
				$res['status']     = self::CONVERTED;
				$res['reason']     = 'exists';
				$res['webp_bytes'] = (int) filesize( $dest );
				$res['engine']     = 'existing';
				return $res;
			}

			$engine = self::engine();
			if ( 'none' === $engine ) {
				$res['reason'] = 'no_engine';
				return $res;
			}
			$res['engine'] = $engine;

			$width  = (int) $info[0];
			$height = (int) $info[1];
			if ( $width < 1 || $height < 1 ) {
				$res['reason'] = 'not_image';
				return $res;
			}

			// Tăng giới hạn bộ nhớ nếu WordPress cho phép, rồi kiểm tra có đủ không.
			if ( ! isset( $opts['memory_limit'] ) && function_exists( 'wp_raise_memory_limit' ) ) {
				wp_raise_memory_limit( 'image' );
			}
			$limit = isset( $opts['memory_limit'] ) ? (int) $opts['memory_limit'] : self::memory_limit_bytes();
			if ( $limit > 0 ) {
				$available = $limit - memory_get_usage( true );
				if ( self::estimate_memory( $width, $height ) > $available ) {
					$res['reason'] = 'memory';
					return $res;
				}
			}

			$dir = dirname( $dest );
			if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
				$res['reason'] = 'not_writable';
				return $res;
			}

			$tmp = $dest . '.tmp' . substr( md5( uniqid( '', true ) ), 0, 8 );
			$ok  = 'imagick' === $engine
				? self::write_imagick( $src, $tmp, $quality )
				: self::write_gd( $src, $tmp, $quality, $type );

			if ( ! $ok || ! is_file( $tmp ) || 0 === (int) filesize( $tmp ) ) {
				if ( is_file( $tmp ) ) {
					@unlink( $tmp );
				}
				$res['reason'] = 'encode_failed';
				return $res;
			}

			$webp_bytes = (int) filesize( $tmp );
			// WebP lớn hơn hoặc bằng ảnh gốc thì giữ ảnh gốc (WP-20).
			if ( $webp_bytes >= $res['src_bytes'] ) {
				@unlink( $tmp );
				if ( is_file( $dest ) ) {
					@unlink( $dest );
				}
				$res['status'] = self::SKIPPED;
				$res['reason'] = 'larger';
				return $res;
			}

			if ( ! @rename( $tmp, $dest ) ) {
				@unlink( $tmp );
				$res['reason'] = 'rename_failed';
				return $res;
			}

			$res['status']     = self::CONVERTED;
			$res['reason']     = 'converted';
			$res['webp_bytes'] = $webp_bytes;
			return $res;
		} catch ( \Throwable $e ) {
			$res['status'] = self::FAILED;
			$res['reason'] = 'exception: ' . $e->getMessage();
			return $res;
		}
	}

	private static function write_gd( string $src, string $tmp, int $quality, int $type ): bool {
		$im = IMAGETYPE_PNG === $type ? @imagecreatefrompng( $src ) : @imagecreatefromjpeg( $src );
		if ( ! $im ) {
			return false;
		}
		try {
			if ( IMAGETYPE_JPEG === $type ) {
				$im = self::apply_exif_orientation( $im, $src );
			}
			if ( ! imageistruecolor( $im ) ) {
				imagepalettetotruecolor( $im );
			}
			// Giữ kênh trong suốt của PNG (WP-11).
			imagealphablending( $im, false );
			imagesavealpha( $im, true );
			return (bool) @imagewebp( $im, $tmp, $quality );
		} finally {
			imagedestroy( $im );
		}
	}

	/** JPEG gốc có thể mang thẻ xoay EXIF; WebP do GD tạo ra không có nên phải xoay thủ công. */
	private static function apply_exif_orientation( $im, string $src ) {
		if ( ! function_exists( 'exif_read_data' ) ) {
			return $im;
		}
		$exif = @exif_read_data( $src );
		$o    = is_array( $exif ) && isset( $exif['Orientation'] ) ? (int) $exif['Orientation'] : 1;
		$deg  = 0;
		if ( 3 === $o ) {
			$deg = 180;
		} elseif ( 6 === $o ) {
			$deg = -90;
		} elseif ( 8 === $o ) {
			$deg = 90;
		}
		if ( 0 !== $deg ) {
			$rot = @imagerotate( $im, $deg, 0 );
			if ( $rot ) {
				imagedestroy( $im );
				return $rot;
			}
		}
		return $im;
	}

	private static function write_imagick( string $src, string $tmp, int $quality ): bool {
		$im = new \Imagick( $src );
		try {
			if ( method_exists( $im, 'autoOrient' ) ) {
				$im->autoOrient();
			}
			$im->setImageFormat( 'webp' );
			$im->setImageCompressionQuality( $quality );
			$im->setOption( 'webp:method', '4' );
			return (bool) $im->writeImage( 'webp:' . $tmp );
		} finally {
			$im->clear();
			$im->destroy();
		}
	}

	/**
	 * Gộp kết quả của các file (ảnh gốc + các kích thước) thành trạng thái cho một attachment.
	 *
	 * done    : ảnh gốc đã có WebP
	 * skipped : không cần/không nên chuyển (GIF/SVG, WebP lớn hơn ảnh gốc)
	 * failed  : lỗi (thiếu file, ảnh hỏng, thiếu RAM, ...) — hiện trong danh sách ảnh lỗi
	 *
	 * @param array<int, array> $results kết quả convert(), phần tử đầu là ảnh gốc
	 * @return array{status:string, reason:string, src_bytes:int, webp_bytes:int}
	 */
	public static function aggregate( array $results ): array {
		$out = array(
			'status'     => 'skipped',
			'reason'     => 'no_files',
			'src_bytes'  => 0,
			'webp_bytes' => 0,
		);
		if ( empty( $results ) ) {
			return $out;
		}

		$errors = array();
		$done   = 0;
		foreach ( $results as $r ) {
			if ( self::FAILED === $r['status'] ) {
				$errors[] = $r['reason'];
			}
			if ( self::CONVERTED === $r['status'] ) {
				++$done;
				$out['src_bytes']  += (int) $r['src_bytes'];
				$out['webp_bytes'] += (int) $r['webp_bytes'];
			}
		}

		if ( ! empty( $errors ) ) {
			$out['status'] = 'failed';
			$out['reason'] = implode( ', ', array_unique( $errors ) );
			return $out;
		}
		if ( $done > 0 ) {
			$out['status'] = 'done';
			$out['reason'] = 'ok';
			return $out;
		}
		$out['reason'] = (string) $results[0]['reason'];
		return $out;
	}
}
