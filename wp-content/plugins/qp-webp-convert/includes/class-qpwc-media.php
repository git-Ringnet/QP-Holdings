<?php
/**
 * Chuyển đổi khi tải ảnh lên / sửa ảnh, dọn file WebP khi xóa ảnh.
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Media {

	const META_STATUS = '_qpwc_status';
	const META_REASON = '_qpwc_reason';
	const META_SRC    = '_qpwc_src_bytes';
	const META_WEBP   = '_qpwc_webp_bytes';

	public static function init(): void {
		add_filter( 'wp_update_attachment_metadata', array( __CLASS__, 'on_metadata' ), 20, 2 );
		// Chạy một lần khi WordPress đã tạo xong mọi kích thước ảnh mới tải lên (trước khi trả về trình soạn bài).
		add_filter( 'wp_generate_attachment_metadata', array( __CLASS__, 'on_generated' ), 30, 2 );
		add_action( 'delete_attachment', array( __CLASS__, 'on_delete' ), 10, 1 );
	}

	/**
	 * Bật "Ảnh mới tải lên: thay luôn bằng WebP" → ảnh vừa tải lên được đổi sang WebP và xóa ảnh gốc ngay
	 * (ảnh mới chưa có bài nào dùng nên không cần đổi đường dẫn trong bài).
	 *
	 * @param mixed $data
	 */
	public static function on_generated( $data, $attachment_id ) {
		if ( ! QPWC_Settings::enabled() || ! QPWC_Settings::get( 'replace_new' ) || ! is_array( $data ) ) {
			return $data;
		}
		try {
			$agg = self::process_attachment( (int) $attachment_id, $data );
			if ( 'done' === $agg['status'] ) {
				$data = QPWC_Replace::replace_new_upload( (int) $attachment_id, $data );
			}
		} catch ( \Throwable $e ) {
			QPWC_Settings::log( 'Lỗi khi thay ảnh mới bằng WebP: ' . $e->getMessage(), (int) $attachment_id );
		}
		return $data;
	}

	/** @param mixed $data */
	public static function on_metadata( $data, $attachment_id ) {
		if ( ! QPWC_Settings::enabled() || ! is_array( $data ) ) {
			return $data;
		}
		try {
			self::process_attachment( (int) $attachment_id, $data );
		} catch ( \Throwable $e ) {
			// Không để lỗi làm hỏng việc tải ảnh lên (WP-21).
			QPWC_Settings::log( 'Lỗi khi chuyển đổi: ' . $e->getMessage(), (int) $attachment_id );
		}
		return $data;
	}

	/**
	 * Chuyển ảnh gốc + mọi kích thước của một attachment.
	 *
	 * @param array|null $metadata null = tự đọc từ database
	 * @return array kết quả gộp (xem QPWC_Converter::aggregate)
	 */
	public static function process_attachment( int $attachment_id, ?array $metadata = null, bool $force = false ): array {
		$mime = (string) get_post_mime_type( $attachment_id );
		// Ảnh đã được thay bằng WebP (file chính là anh.jpg.webp): đã xong, không xử lý lại.
		if ( 'image/webp' === $mime && ( get_post_meta( $attachment_id, QPWC_Replace::META, true ) || preg_match( '/\.(jpe?g|png)\.webp$/i', (string) get_post_meta( $attachment_id, '_wp_attached_file', true ) ) ) ) {
			return array(
				'status'     => 'done',
				'reason'     => 'replaced',
				'src_bytes'  => (int) get_post_meta( $attachment_id, self::META_SRC, true ),
				'webp_bytes' => (int) get_post_meta( $attachment_id, self::META_WEBP, true ),
			);
		}
		if ( ! in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			// GIF, SVG, PDF... giữ nguyên (WP-12).
			$agg = array(
				'status'     => 'skipped',
				'reason'     => 'unsupported_type',
				'src_bytes'  => 0,
				'webp_bytes' => 0,
			);
			self::store( $attachment_id, $agg );
			return $agg;
		}

		$main = get_attached_file( $attachment_id );
		if ( ! $main ) {
			$agg = array(
				'status'     => 'failed',
				'reason'     => 'missing_file',
				'src_bytes'  => 0,
				'webp_bytes' => 0,
			);
			self::store( $attachment_id, $agg );
			return $agg;
		}

		if ( null === $metadata ) {
			$metadata = wp_get_attachment_metadata( $attachment_id );
			$metadata = is_array( $metadata ) ? $metadata : array();
		}

		$quality = QPWC_Settings::quality();
		$opts    = array( 'force' => $force );
		$results = array( QPWC_Converter::convert( $main, $quality, $opts ) );

		$dir = trailingslashit( dirname( $main ) );
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			$seen = array( basename( $main ) => true );
			foreach ( $metadata['sizes'] as $size ) {
				if ( empty( $size['file'] ) || isset( $seen[ $size['file'] ] ) ) {
					continue;
				}
				$seen[ $size['file'] ] = true;
				$results[]             = QPWC_Converter::convert( $dir . $size['file'], $quality, $opts );
			}
		}

		$agg = QPWC_Converter::aggregate( $results );
		self::store( $attachment_id, $agg );
		// File gốc thiếu (thường gặp trên máy dev chỉ có database) đã hiện trong "Danh sách ảnh lỗi", không ghi nhật ký cho đỡ nhiễu.
		if ( 'failed' === $agg['status'] && 'missing_file' !== $agg['reason'] ) {
			QPWC_Settings::log( 'Chuyển đổi thất bại: ' . $agg['reason'], $attachment_id );
		}
		return $agg;
	}

	private static function store( int $id, array $agg ): void {
		update_post_meta( $id, self::META_STATUS, $agg['status'] );
		update_post_meta( $id, self::META_REASON, $agg['reason'] );
		update_post_meta( $id, self::META_SRC, (int) $agg['src_bytes'] );
		update_post_meta( $id, self::META_WEBP, (int) $agg['webp_bytes'] );
	}

	/** Xóa ảnh khỏi thư viện thì xóa luôn các file .webp đi kèm. */
	public static function on_delete( $attachment_id ): void {
		foreach ( self::webp_files( (int) $attachment_id ) as $file ) {
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
	}

	/** @return string[] đường dẫn các file .webp có thể tồn tại của attachment */
	public static function webp_files( int $attachment_id ): array {
		$main = get_attached_file( $attachment_id );
		if ( ! $main ) {
			return array();
		}
		$files = array( QPWC_Converter::webp_path( $main ) );
		$meta  = wp_get_attachment_metadata( $attachment_id );
		$dir   = trailingslashit( dirname( $main ) );
		if ( is_array( $meta ) && ! empty( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					$files[] = QPWC_Converter::webp_path( $dir . $size['file'] );
				}
			}
		}
		return array_unique( $files );
	}
}
