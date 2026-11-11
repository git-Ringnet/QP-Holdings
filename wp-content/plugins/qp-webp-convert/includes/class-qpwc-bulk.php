<?php
/**
 * Chuyển đổi hàng loạt ảnh đang có: theo lô nhỏ, dừng/chạy tiếp được, có báo cáo.
 */

defined( 'ABSPATH' ) || exit;

final class QPWC_Bulk {

	const STATE      = 'qpwc_bulk';
	const BATCH_SIZE = 25;
	const TIME_LIMIT = 8; // giây cho mỗi lô, tránh timeout (WP-14)

	public static function init(): void {
		add_action( 'wp_ajax_qpwc_bulk', array( __CLASS__, 'ajax' ) );
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'qpwc bulk', array( __CLASS__, 'cli' ) );
		}
	}

	public static function default_state(): array {
		return array(
			'status'    => 'idle', // idle | running | paused | done
			'last_id'   => 0,
			'processed' => 0,
			'ok'        => 0,
			'skipped'   => 0,
			'failed'    => 0,
			'started'   => 0,
		);
	}

	public static function state(): array {
		$s = get_option( self::STATE, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::default_state() );
	}

	private static function save( array $s ): void {
		update_option( self::STATE, $s, false );
	}

	/** Bắt đầu chạy mới từ đầu. Chặn nếu chưa xác nhận đã sao lưu (WP-07). */
	public static function start(): array {
		if ( ! QPWC_Settings::get( 'backup_at' ) ) {
			return array(
				'error' => 'Chưa xác nhận đã sao lưu thư mục ảnh và database. Hãy sao lưu rồi tick xác nhận trước khi chạy hàng loạt.',
			);
		}
		$s           = self::default_state();
		$s['status'] = 'running';
		$s['started'] = time();
		self::save( $s );
		return $s;
	}

	public static function pause(): array {
		$s = self::state();
		if ( 'running' === $s['status'] ) {
			$s['status'] = 'paused';
			self::save( $s );
		}
		return $s;
	}

	/** Chạy tiếp từ ID đã xử lý cuối cùng — không làm lại ảnh đã xong. */
	public static function resume(): array {
		$s = self::state();
		if ( 'paused' === $s['status'] ) {
			$s['status'] = 'running';
			self::save( $s );
		}
		return $s;
	}

	public static function reset(): array {
		$s = self::default_state();
		self::save( $s );
		return $s;
	}

	/** Xử lý một lô. */
	public static function step( int $batch = self::BATCH_SIZE, int $time_limit = self::TIME_LIMIT ): array {
		global $wpdb;
		$s = self::state();
		if ( 'running' !== $s['status'] ) {
			return $s;
		}

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE %s AND ID > %d ORDER BY ID ASC LIMIT %d",
				'image/%',
				(int) $s['last_id'],
				$batch
			)
		);

		if ( empty( $ids ) ) {
			$s['status'] = 'done';
			self::save( $s );
			return $s;
		}

		$t0 = microtime( true );
		foreach ( $ids as $id ) {
			$id  = (int) $id;
			$agg = QPWC_Media::process_attachment( $id );
			if ( 'done' === $agg['status'] ) {
				++$s['ok'];
			} elseif ( 'failed' === $agg['status'] ) {
				++$s['failed'];
			} else {
				++$s['skipped'];
			}
			++$s['processed'];
			$s['last_id'] = $id;
			if ( ( microtime( true ) - $t0 ) > $time_limit ) {
				break;
			}
		}
		self::save( $s );
		return $s;
	}

	public static function ajax(): void {
		check_ajax_referer( 'qpwc_bulk', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'error' => 'Không đủ quyền.' ), 403 );
		}

		$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : 'status'; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'confirm_backup' === $do ) {
			QPWC_Settings::update( array( 'backup_at' => time() ) );
			$out = self::state();
		} elseif ( 'start' === $do ) {
			$out = self::start();
		} elseif ( 'pause' === $do ) {
			$out = self::pause();
		} elseif ( 'resume' === $do ) {
			$out = self::resume();
		} elseif ( 'reset' === $do ) {
			$out = self::reset();
		} elseif ( 'step' === $do ) {
			$out = self::step();
		} else {
			$out = self::state();
		}

		if ( isset( $out['error'] ) ) {
			wp_send_json_error( $out );
		}
		wp_send_json_success(
			array(
				'state'  => $out,
				'report' => self::report(),
			)
		);
	}

	/**
	 * Báo cáo tổng: Tổng ảnh = Thành công + Lỗi + Bỏ qua + Chưa xử lý (WP-13).
	 */
	public static function report(): array {
		global $wpdb;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type LIKE %s", 'image/%' )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.meta_value AS status, COUNT(*) AS n
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'attachment' AND p.post_mime_type LIKE %s
				 WHERE pm.meta_key = %s GROUP BY pm.meta_value",
				'image/%',
				QPWC_Media::META_STATUS
			),
			ARRAY_A
		);
		$by = array(
			'done'    => 0,
			'skipped' => 0,
			'failed'  => 0,
		);
		foreach ( (array) $rows as $r ) {
			if ( isset( $by[ $r['status'] ] ) ) {
				$by[ $r['status'] ] = (int) $r['n'];
			}
		}
		$pending = max( 0, $total - array_sum( $by ) );

		$sizes = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(CAST(s.meta_value AS UNSIGNED)),0) AS src, COALESCE(SUM(CAST(w.meta_value AS UNSIGNED)),0) AS webp
				 FROM {$wpdb->postmeta} st
				 INNER JOIN {$wpdb->postmeta} s ON s.post_id = st.post_id AND s.meta_key = %s
				 INNER JOIN {$wpdb->postmeta} w ON w.post_id = st.post_id AND w.meta_key = %s
				 WHERE st.meta_key = %s AND st.meta_value = 'done'",
				QPWC_Media::META_SRC,
				QPWC_Media::META_WEBP,
				QPWC_Media::META_STATUS
			),
			ARRAY_A
		);

		return array(
			'total'      => $total,
			'done'       => $by['done'],
			'skipped'    => $by['skipped'],
			'failed'     => $by['failed'],
			'pending'    => $pending,
			'src_bytes'  => (int) ( $sizes['src'] ?? 0 ),
			'webp_bytes' => (int) ( $sizes['webp'] ?? 0 ),
		);
	}

	/** Danh sách ảnh lỗi (tối đa $limit). */
	public static function failed_list( int $limit = 50 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID, p.post_title, r.meta_value AS reason
				 FROM {$wpdb->postmeta} st
				 INNER JOIN {$wpdb->posts} p ON p.ID = st.post_id
				 LEFT JOIN {$wpdb->postmeta} r ON r.post_id = st.post_id AND r.meta_key = %s
				 WHERE st.meta_key = %s AND st.meta_value = 'failed' ORDER BY p.ID DESC LIMIT %d",
				QPWC_Media::META_REASON,
				QPWC_Media::META_STATUS,
				$limit
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Xóa toàn bộ file .webp do plugin tạo (anh.jpg.webp, anh.png.webp) và dữ liệu thống kê.
	 * Không đụng tới file .webp khác (ảnh WebP do người dùng tải lên).
	 */
	public static function cleanup(): int {
		global $wpdb;
		$up      = wp_get_upload_dir();
		$deleted = 0;
		if ( is_dir( $up['basedir'] ) ) {
			$it = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $up['basedir'], \FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( $f->isFile() && preg_match( '/\.(jpe?g|png)\.webp$/i', $f->getFilename() ) ) {
					if ( @unlink( $f->getPathname() ) ) {
						++$deleted;
					}
				}
			}
		}
		foreach ( array( QPWC_Media::META_STATUS, QPWC_Media::META_REASON, QPWC_Media::META_SRC, QPWC_Media::META_WEBP ) as $key ) {
			$wpdb->delete( $wpdb->postmeta, array( 'meta_key' => $key ) ); // phpcs:ignore WordPress.DB
		}
		self::reset();
		return $deleted;
	}

	/** wp qpwc bulk --backup-done  (bắt buộc xác nhận đã sao lưu, giống giao diện) */
	public static function cli( array $args, array $assoc ): void {
		if ( ! empty( $assoc['backup-done'] ) ) {
			QPWC_Settings::update( array( 'backup_at' => time() ) );
		}
		$started = self::start();
		if ( isset( $started['error'] ) ) {
			\WP_CLI::error( $started['error'] . ' (thêm --backup-done nếu đã sao lưu)' );
		}
		do {
			$s = self::step( 20, 20 );
			\WP_CLI::log( sprintf( 'Đã xử lý %d (ok %d, bỏ qua %d, lỗi %d)', $s['processed'], $s['ok'], $s['skipped'], $s['failed'] ) );
		} while ( 'running' === $s['status'] );
		\WP_CLI::success( 'Hoàn tất.' );
	}
}
