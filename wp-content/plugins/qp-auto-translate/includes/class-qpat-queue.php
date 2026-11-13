<?php
/**
 * Background translation queue (one row per post × target language).
 *
 * Publishing only inserts rows; the work runs in WP-Cron and in a
 * fire-and-forget loopback request, so the editor never waits for the API.
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Queue {
	const SCHEMA_VERSION = 2;

	/** Job statuses shown in the log. */
	const STATUSES = array(
		'queued'    => 'Đang chờ',
		'running'   => 'Đang dịch',
		'done'      => 'Hoàn thành',
		'review'    => 'Cần duyệt',
		'error'     => 'Thất bại',
		'paused'    => 'Tạm dừng (hết hạn mức)',
		'cancelled' => 'Đã huỷ',
		'skipped'   => 'Bỏ qua',
	);

	/** Kinds of failure, to tell connection / quota / data problems apart. */
	const ERROR_KINDS = array(
		'connection' => 'Lỗi kết nối',
		'quota'      => 'Hết hạn mức',
		'config'     => 'Sai cấu hình / API key',
		'data'       => 'Dữ liệu không hợp lệ',
	);
	const SCHEMA_OPTION  = 'qpat_jobs_schema_version';
	const CRON_HOOK      = 'qpat_tick';
	const CRON_SCHEDULE  = 'qpat_minute';
	const AJAX_RUN       = 'qpat_run_worker';
	const LOCK           = 'qpat_worker_lock';

	/** Jobs handled per worker run (each can take up to ~1 minute). */
	const BATCH = 2;

	/** Transient failures (rate limit, network) are retried this many times. */
	const MAX_ATTEMPTS = 6;

	private static ?QPAT_Queue $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'qpat_jobs';
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source_post_id bigint(20) unsigned NOT NULL,
				target_lang varchar(12) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'queued',
				overwrite tinyint(1) unsigned NOT NULL DEFAULT 0,
				attempts smallint(5) unsigned NOT NULL DEFAULT 0,
				engine varchar(20) NOT NULL DEFAULT '',
				target_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
				message text NULL,
				error_kind varchar(20) NOT NULL DEFAULT '',
				chars int(10) unsigned NOT NULL DEFAULT 0,
				run_after datetime NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY source_lang (source_post_id, target_lang),
				KEY status_run (status, run_after)
			) {$charset};"
		);
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		if ( (int) get_option( self::SCHEMA_OPTION, 0 ) < self::SCHEMA_VERSION ) {
			self::install();
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public function boot(): void {
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'process_batch' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_RUN, array( $this, 'ajax_run' ) );
		add_action( 'wp_ajax_' . self::AJAX_RUN, array( $this, 'ajax_run' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	public function cron_schedules( array $schedules ): array {
		$schedules[ self::CRON_SCHEDULE ] = array(
			'interval' => 60,
			'display'  => 'QP Auto Translate: mỗi phút',
		);
		return $schedules;
	}

	/**
	 * Minute tick. wp_next_scheduled() reads the autoloaded cron option, so this
	 * costs no database query on normal page loads.
	 */
	public function maybe_schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK );
		}
	}

	private static function now(): string {
		return current_time( 'mysql', true );
	}

	/**
	 * Queue translations of a Vietnamese post.
	 *
	 * $overwrite = false (automatic, on publish): only languages that have no
	 * translation yet, so manual edits to EN/ZH (e.g. replaced images) are kept.
	 * $overwrite = true (editor clicked "Dịch lại"): refresh existing copies too.
	 *
	 * @return int Number of jobs queued.
	 */
	public function enqueue_post( int $post_id, bool $overwrite, ?array $only_langs = null ): int {
		$post = get_post( $post_id );
		if ( ! $post || ! QPAT_Sync::is_source_post( $post ) ) {
			return 0;
		}
		$queued = 0;
		$langs  = QPAT_Settings::get()['target_langs'];
		if ( null !== $only_langs ) {
			$langs = array_values( array_intersect( $langs, $only_langs ) );
		}
		foreach ( $langs as $lang ) {
			// A job already waiting will read the latest text when it runs.
			if ( $this->has_status( $post_id, $lang, array( 'queued', 'paused' ) ) ) {
				continue;
			}
			// Update mode: nothing to do when the copy already matches the current text.
			if ( ! $overwrite ) {
				$copy = QPAT_Sync::existing_translation( $post, $lang );
				if ( $copy && null !== QPAT_Updater::map( $copy ) && ! QPAT_Sync::copy_outdated( $post, $copy ) ) {
					continue;
				}
			}
			if ( $this->insert_job( $post_id, $lang, $overwrite ) ) {
				++$queued;
			}
		}
		if ( $queued ) {
			$this->maybe_schedule();
		}
		return $queued;
	}

	private function has_status( int $post_id, string $lang, array $statuses ): bool {
		global $wpdb;
		$in = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . self::table() . " WHERE source_post_id = %d AND target_lang = %s AND status IN ($in)", // phpcs:ignore WordPress.DB.PreparedSQL
				array_merge( array( $post_id, $lang ), $statuses )
			)
		) > 0;
	}

	private function insert_job( int $post_id, string $lang, bool $overwrite ): bool {
		global $wpdb;
		$now = self::now();
		return false !== $wpdb->insert(
			self::table(),
			array(
				'source_post_id' => $post_id,
				'target_lang'    => $lang,
				'status'         => 'queued',
				'overwrite'      => $overwrite ? 1 : 0,
				'attempts'       => 0,
				'run_after'      => $now,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Fire-and-forget request so work starts right away instead of waiting
	 * for the next cron tick. Never blocks the editor.
	 */
	public function spawn_worker(): void {
		$token = QPAT_Settings::worker_token();
		wp_remote_post(
			add_query_arg( 'action', self::AJAX_RUN, admin_url( 'admin-ajax.php' ) ),
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'body'      => array( 'token' => $token ),
			)
		);
	}

	public function ajax_run(): void {
		$given = isset( $_POST['token'] ) ? (string) wp_unslash( $_POST['token'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! hash_equals( QPAT_Settings::worker_token(), $given ) ) {
			wp_die( 'forbidden', '', 403 );
		}
		ignore_user_abort( true );
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}
		$this->process_batch();
		wp_die( 'ok' );
	}

	public function process_batch(): void {
		$this->recover_stale();
		if ( get_transient( self::LOCK ) ) {
			return;
		}
		set_transient( self::LOCK, 1, 8 * MINUTE_IN_SECONDS );
		try {
			for ( $i = 0; $i < self::BATCH; $i++ ) {
				$job = $this->claim_next();
				if ( ! $job ) {
					break;
				}
				$this->run_job( $job );
			}
			$this->purge_old();
			QPAT_Status::backfill();
		} finally {
			delete_transient( self::LOCK );
		}
		if ( $this->pending_count( true ) > 0 ) {
			$this->spawn_worker();
		}
	}

	/** Jobs left "running" by a killed PHP process go back to the queue. */
	private function recover_stale(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . " SET status = 'queued', message = 'Khôi phục sau khi tiến trình bị ngắt' WHERE status = 'running' AND updated_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL
				gmdate( 'Y-m-d H:i:s', time() - 10 * MINUTE_IN_SECONDS )
			)
		);
	}

	/** Remove finished rows older than 60 days. */
	private function purge_old(): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . self::table() . " WHERE status IN ('done','skipped') AND updated_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL
				gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS )
			)
		);
	}

	/**
	 * @param bool $ready_only Only jobs whose retry time has come.
	 */
	public function pending_count( bool $ready_only ): int {
		global $wpdb;
		if ( ! $ready_only ) {
			return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE status = 'queued'" ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . " WHERE status = 'queued' AND run_after <= %s", self::now() ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	private function claim_next(): ?array {
		global $wpdb;
		$table = self::table();
		$job   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'queued' AND run_after <= %s ORDER BY id ASC LIMIT 1", self::now() ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		if ( ! $job ) {
			return null;
		}
		$claimed = $wpdb->update(
			$table,
			array(
				'status'     => 'running',
				'attempts'   => (int) $job['attempts'] + 1,
				'updated_at' => self::now(),
			),
			array(
				'id'     => (int) $job['id'],
				'status' => 'queued',
			),
			array( '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		if ( ! $claimed ) {
			return null;
		}
		$job['attempts'] = (int) $job['attempts'] + 1;
		return $job;
	}

	public function run_job( array $job ): void {
		$id   = (int) $job['id'];
		$lang = (string) $job['target_lang'];
		$post = get_post( (int) $job['source_post_id'] );

		if ( ! $post || 'trash' === $post->post_status ) {
			$this->finish( $id, 'skipped', 'Bài gốc không còn tồn tại.' );
			return;
		}
		if ( ! QPAT_Sync::is_source_post( $post ) ) {
			$this->finish( $id, 'skipped', 'Không phải bài tiếng Việt gốc.' );
			return;
		}
		$full = ! empty( $job['overwrite'] );

		try {
			// Paragraph-level update (or a full re-translation when asked for).
			// If the editor saves again while this runs, the new save queues
			// another job that picks up the remaining changes.
			$r = QPAT_Updater::run( $post, $lang, $full );
			$this->finish( $id, $r['status'], $r['message'], $r['engine'], $r['target_id'], '', $r['sent'] );
		} catch ( QPAT_Quota_Exception $e ) {
			// Stop here: no more API calls until the limit is changed in the settings.
			$this->finish( $id, 'paused', $e->getMessage(), '', 0, 'quota' );
		} catch ( QPAT_Transient_Exception $e ) {
			if ( (int) $job['attempts'] < self::MAX_ATTEMPTS ) {
				// 2, 4, 8, 16, 30 minutes.
				$this->retry( $id, $job, $e->getMessage(), min( 30, 2 ** (int) $job['attempts'] ) * MINUTE_IN_SECONDS );
				return;
			}
			$this->finish( $id, 'error', $e->getMessage(), '', 0, 'connection' );
		} catch ( Throwable $e ) {
			$this->finish( $id, 'error', $e->getMessage(), '', 0, self::error_kind( $e ) );
		}
	}

	private static function error_kind( Throwable $e ): string {
		$msg = $e->getMessage();
		if ( preg_match( '/API key|Chưa nhập|HTTP 40[013]|\(40[013]\)|PERMISSION_DENIED|not found|model/i', $msg ) ) {
			return 'config';
		}
		if ( preg_match( '/kết nối|timed out|cURL|resolve/i', $msg ) ) {
			return 'connection';
		}
		return 'data';
	}

	/** Latest job of a post × language, or null. */
	public function latest_job( int $post_id, string $lang ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE source_post_id = %d AND target_lang = %s ORDER BY id DESC LIMIT 1', $post_id, $lang ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return is_array( $row ) ? $row : null;
	}

	public function get_job( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return is_array( $row ) ? $row : null;
	}

	/** Failed / paused / cancelled job → back to the queue. */
	public function retry_job( int $id ): bool {
		global $wpdb;
		$job = $this->get_job( $id );
		if ( ! $job || ! in_array( $job['status'], array( 'error', 'paused', 'cancelled', 'skipped' ), true ) ) {
			return false;
		}
		$ok = (bool) $wpdb->update(
			self::table(),
			array(
				'status'     => 'queued',
				'attempts'   => 0,
				'error_kind' => '',
				'message'    => 'Thử lại theo yêu cầu.',
				'run_after'  => self::now(),
				'updated_at' => self::now(),
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		QPAT_Status::refresh( (int) $job['source_post_id'] );
		$this->maybe_schedule();
		return $ok;
	}

	/** Waiting job → cancelled (never sent). */
	public function cancel_job( int $id ): bool {
		global $wpdb;
		$job = $this->get_job( $id );
		if ( ! $job || ! in_array( $job['status'], array( 'queued', 'paused' ), true ) ) {
			return false;
		}
		$ok = (bool) $wpdb->update(
			self::table(),
			array(
				'status'     => 'cancelled',
				'message'    => 'Đã huỷ bởi ' . wp_get_current_user()->user_login . '.',
				'updated_at' => self::now(),
			),
			array(
				'id'     => $id,
				'status' => $job['status'],
			),
			array( '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		QPAT_Status::refresh( (int) $job['source_post_id'] );
		return $ok;
	}

	/** After the settings change (e.g. a higher limit), paused jobs run again. */
	public function resume_paused(): int {
		global $wpdb;
		$n = (int) $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . self::table() . " SET status = 'queued', attempts = 0, message = 'Tiếp tục sau khi đổi cấu hình.', run_after = %s, updated_at = %s WHERE status = 'paused'", // phpcs:ignore WordPress.DB.PreparedSQL
				self::now(),
				self::now()
			)
		);
		if ( $n ) {
			$this->maybe_schedule();
		}
		return $n;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list_jobs( string $status = '', int $page = 1, int $per_page = 30 ): array {
		global $wpdb;
		$where = '';
		$args  = array();
		if ( '' !== $status && isset( self::STATUSES[ $status ] ) ) {
			$where  = 'WHERE status = %s';
			$args[] = $status;
		}
		$args[] = $per_page;
		$args[] = max( 0, ( $page - 1 ) * $per_page );
		$rows   = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . " {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $args ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	public function count_jobs( string $status = '' ): int {
		global $wpdb;
		if ( '' !== $status ) {
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE status = %s', $status ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL
	}

	private function retry( int $id, array $job, string $message, int $delay_seconds ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'     => 'queued',
				'message'    => substr( 'Thử lại: ' . $message, 0, 1000 ),
				'run_after'  => gmdate( 'Y-m-d H:i:s', time() + max( 0, $delay_seconds ) ),
				'updated_at' => self::now(),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		$this->maybe_schedule();
	}

	private function finish( int $id, string $status, string $message, string $engine = '', int $target_id = 0, string $kind = '', int $chars = 0 ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'         => $status,
				'message'        => substr( $message, 0, 1000 ),
				'engine'         => $engine,
				'target_post_id' => $target_id,
				'error_kind'     => $kind,
				'chars'          => $chars,
				'updated_at'     => self::now(),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
		$job = $this->get_job( $id );
		if ( $job ) {
			QPAT_Status::refresh( (int) $job['source_post_id'] );
		}
	}

	/** @return array<int, array<string, mixed>> */
	public function jobs_for_post( int $post_id, int $limit = 6 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE source_post_id = %d ORDER BY id DESC LIMIT %d', $post_id, $limit ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/** @return array<int, array<string, mixed>> */
	public function recent_jobs( int $limit = 20 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT %d', max( 1, min( 100, $limit ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Jobs for the editor toast: active ones plus those finished in the last 3 minutes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function status_jobs(): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . " WHERE status IN ('queued','running') OR updated_at >= %s ORDER BY id ASC LIMIT 30", // phpcs:ignore WordPress.DB.PreparedSQL
				gmdate( 'Y-m-d H:i:s', time() - 3 * MINUTE_IN_SECONDS )
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $row ) {
			$pid   = (int) $row['source_post_id'];
			$title = get_the_title( $pid );
			$out[] = array(
				'id'      => (int) $row['id'],
				'post_id' => $pid,
				'title'   => $title ? wp_strip_all_tags( $title ) : '#' . $pid,
				'lang'    => (string) $row['target_lang'],
				'status'  => (string) $row['status'],
				'message' => (string) $row['message'],
			);
		}
		return $out;
	}
}
