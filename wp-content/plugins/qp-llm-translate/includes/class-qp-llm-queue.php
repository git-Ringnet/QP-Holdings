<?php
defined( 'ABSPATH' ) || exit;

final class QP_LLM_Queue {
	public const TABLE_SUFFIX = 'qp_llm_translate_jobs';
	public const CRON_HOOK    = 'qp_llm_translate_tick';
	public const AJAX_RUN     = 'qp_llm_run_worker';
	public const SCHEMA_OPTION = 'qp_llm_translate_jobs_schema_version';

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function table(): string {
		global $wpdb;
		$prefix = ( ! empty( $wpdb ) && isset( $wpdb->prefix ) ) ? $wpdb->prefix : 'wp_';
		return $prefix . self::TABLE_SUFFIX;
	}

	public static function activate(): void {
		self::install_schema();
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'minute', self::CRON_HOOK );
		}
		QP_LLM_Settings::instance()->worker_token();
	}

	public static function maybe_upgrade_schema(): void {
		if ( ! is_admin() ) {
			return;
		}
		if ( 3 !== (int) get_option( self::SCHEMA_OPTION, 0 ) ) {
			self::install_schema();
		}
	}

	private static function install_schema(): void {
		global $wpdb;
		if ( empty( $wpdb ) || ! is_object( $wpdb ) ) {
			return;
		}
		$table   = self::table();
		$charset = method_exists( $wpdb, 'get_charset_collate' ) ? $wpdb->get_charset_collate() : '';
		$sql     = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_post_id bigint(20) unsigned NOT NULL,
			target_lang varchar(12) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'queued',
			attempts smallint(5) unsigned NOT NULL DEFAULT 0,
			manual_request tinyint(1) unsigned NOT NULL DEFAULT 0,
			source_hash char(64) NOT NULL DEFAULT '',
			target_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
			target_hash char(64) NOT NULL DEFAULT '',
			error_message text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY source_lang (source_post_id, target_lang),
			KEY status (status)
		) {$charset};";
		if ( file_exists( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			if ( function_exists( 'dbDelta' ) ) {
				dbDelta( $sql );
			}
		}
		$columns = method_exists( $wpdb, 'get_col' ) ? (array) $wpdb->get_col( "SHOW COLUMNS FROM {$table}", 0 ) : array();
		if ( in_array( 'manual_request', $columns, true )
			&& in_array( 'source_hash', $columns, true )
			&& in_array( 'target_post_id', $columns, true )
			&& in_array( 'target_hash', $columns, true ) ) {
			update_option( self::SCHEMA_OPTION, 3, false );
			delete_option( 'qp_llm_translate_jobs_schema_v2' );
		}
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	public function boot(): void {
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade_schema' ) );
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'process_batch' ) );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_RUN, array( $this, 'ajax_run' ) );
		add_action( 'wp_ajax_' . self::AJAX_RUN, array( $this, 'ajax_run' ) );
		add_action( 'init', array( $this, 'maybe_reschedule' ) );
	}

	public function cron_schedules( array $schedules ): array {
		if ( empty( $schedules['minute'] ) ) {
			$schedules['minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every minute', 'qp-llm-translate' ),
			);
		}
		return $schedules;
	}

	public function maybe_reschedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'minute', self::CRON_HOOK );
		}
	}

	public function enqueue_post( int $post_id, bool $force ): int {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return 0;
		}
		$settings = QP_LLM_Settings::instance()->get();
		$source   = (string) $settings['source_lang'];

		$post_lang = '';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$post_lang = (string) pll_get_post_language( $post_id );
		}

		$langs = array();
		if ( $post_lang && $post_lang !== $source ) {
			if ( ! $force ) {
				return 0;
			}
			$langs[] = $post_lang;
		} else {
			foreach ( QP_LLM_Settings::instance()->target_langs() as $lang ) {
				if ( $lang !== $source ) {
					$langs[] = $lang;
				}
			}
		}

		$queued = 0;
		foreach ( $langs as $lang ) {
			if ( $this->has_active( $post_id, $lang ) ) {
				continue;
			}
			if ( ! $force && $this->has_active_or_done( $post_id, $lang ) ) {
				continue;
			}
			if ( $this->insert_job( $post, $lang, $force ) ) {
				++$queued;
			}
		}
		return $queued;
	}

	private function has_active( int $post_id, string $lang ): bool {
		global $wpdb;
		$table = self::table();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE source_post_id = %d AND target_lang = %s AND status IN ('queued','running')",
				$post_id,
				$lang
			)
		);
		return $count > 0;
	}

	private function has_active_or_done( int $post_id, string $lang ): bool {
		global $wpdb;
		$table = self::table();
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE source_post_id = %d AND target_lang = %s AND status IN ('queued','running','done')",
				$post_id,
				$lang
			)
		);
		return $count > 0;
	}

	private function insert_job( WP_Post $post, string $lang, bool $manual ): bool {
		global $wpdb;
		$now = current_time( 'mysql' );
		$post_lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( (int) $post->ID ) : '';
		$target_id = $post_lang === $lang ? (int) $post->ID : QP_LLM_Sync::existing_translation( $post, $lang );
		$target = $target_id ? get_post( $target_id ) : null;
		if ( $target_id && ! $target ) {
			return false;
		}
		return false !== $wpdb->insert(
			self::table(),
			array(
				'source_post_id' => $post->ID,
				'target_lang'    => $lang,
				'status'         => 'queued',
				'attempts'       => 0,
				'manual_request' => $manual ? 1 : 0,
				'source_hash'    => self::source_hash( $post ),
				'target_post_id' => $target_id,
				'target_hash'    => $target ? self::source_hash( $target ) : 'none',
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	private static function source_hash( WP_Post $post ): string {
		$parts = array( $post->post_type, $post->post_status, $post->post_title, $post->post_excerpt, $post->post_content );
		if ( 'qp_news' === $post->post_type ) {
			foreach ( QP_LLM_Sync::news_meta_keys() as $meta_key ) {
				$parts[] = (string) get_post_meta( (int) $post->ID, $meta_key, true );
			}
		} elseif ( 'qp_job' === $post->post_type ) {
			foreach ( QP_LLM_Sync::job_meta_keys() as $meta_key ) {
				$parts[] = (string) get_post_meta( (int) $post->ID, $meta_key, true );
			}
		} elseif ( 'qp_project' === $post->post_type ) {
			foreach ( QP_LLM_Sync::project_meta_keys() as $meta_key ) {
				$parts[] = (string) get_post_meta( (int) $post->ID, $meta_key, true );
			}
		} elseif ( 'qp_investor_doc' === $post->post_type ) {
			$count = (int) get_post_meta( (int) $post->ID, 'doc_files', true );
			for ( $i = 0; $i < $count; $i++ ) {
				$parts[] = (string) get_post_meta( (int) $post->ID, 'doc_files_' . $i . '_title', true );
			}
		}
		return hash( 'sha256', implode( "\0", $parts ) );
	}

	/**
	 * Fire-and-forget HTTP ping so work starts immediately.
	 * Does not block the editor request.
	 */
	public function spawn_worker(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 1, self::CRON_HOOK );
		}
		spawn_cron();

		$token = QP_LLM_Settings::instance()->worker_token();
		$url   = add_query_arg(
			array(
				'action' => self::AJAX_RUN,
				'token'  => $token,
			),
			admin_url( 'admin-ajax.php' )
		);

		wp_remote_post(
			$url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'headers'   => array( 'X-QP-LLM-Token' => $token ),
				'body'      => array( 'token' => $token ),
			)
		);
	}

	public function ajax_run(): void {
		$token = QP_LLM_Settings::instance()->worker_token();
		$given = isset( $_REQUEST['token'] ) ? (string) wp_unslash( $_REQUEST['token'] ) : '';
		$header = isset( $_SERVER['HTTP_X_QP_LLM_TOKEN'] ) ? (string) wp_unslash( $_SERVER['HTTP_X_QP_LLM_TOKEN'] ) : '';
		if ( ! hash_equals( $token, $given ) && ! hash_equals( $token, $header ) ) {
			wp_die( 'forbidden', '', 403 );
		}

		ignore_user_abort( true );
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}
		$this->process_batch();
		wp_die( 'ok' );
	}

	public function process_batch(): void {
		$this->recover_stale_jobs();
		$lock = 'qp_llm_translate_lock';
		if ( get_transient( $lock ) ) {
			return;
		}
		set_transient( $lock, 1, 8 * MINUTE_IN_SECONDS );

		try {
			$processed = 0;
			while ( $processed < 2 ) {
				$job = $this->claim_next();
				if ( ! $job ) {
					break;
				}
				$this->run_job( $job );
				++$processed;
			}
			if ( $this->pending_count() > 0 ) {
				$this->spawn_worker();
			}
		} finally {
			delete_transient( $lock );
		}
	}

	private function recover_stale_jobs(): void {
		global $wpdb;
		$table = self::table();
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - 6 * MINUTE_IN_SECONDS );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'queued', error_message = 'stale running recovered' WHERE status = 'running' AND updated_at < %s",
				$cutoff
			)
		);
	}

	private function pending_count(): int {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'queued'" );
	}

	private function claim_next(): ?array {
		global $wpdb;
		$table = self::table();
		$job   = $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'queued' ORDER BY id ASC LIMIT 1", ARRAY_A );
		if ( ! $job ) {
			return null;
		}
		$updated = $wpdb->update(
			$table,
			array(
				'status'     => 'running',
				'attempts'   => (int) $job['attempts'] + 1,
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'id'     => (int) $job['id'],
				'status' => 'queued',
			),
			array( '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		if ( ! $updated ) {
			return null;
		}
		$job['status']   = 'running';
		$job['attempts'] = (int) $job['attempts'] + 1;
		return $job;
	}

	private function run_job( array $job ): void {
		$post_id = (int) $job['source_post_id'];
		$lang    = (string) $job['target_lang'];
		$post    = get_post( $post_id );
		if ( ! $post ) {
			$this->fail_job( (int) $job['id'], 'source post missing' );
			return;
		}

		$settings = QP_LLM_Settings::instance()->get();
		$manual   = ! empty( $job['manual_request'] );
		if ( ! self::job_is_current( $post, $job, (string) $settings['source_lang'] ) ) {
			$this->fail_job( (int) $job['id'], 'post or translation changed, or automatic in-place translation blocked' );
			return;
		}
		if ( ! $manual && QP_LLM_Sync::existing_translation( $post, $lang ) ) {
			$this->fail_job( (int) $job['id'], 'Existing translation requires an explicit manual request.' );
			return;
		}
		try {
			$translated = QP_LLM_Client::translate(
				QP_LLM_Sync::collect_fields( $post ),
				(string) $settings['source_lang'],
				$lang,
				$settings
			);
			// An editor may save the post while the LLM request is in flight.
			clean_post_cache( $post_id );
			$current = get_post( $post_id );
			if ( ! $current || ! self::job_is_current( $current, $job, (string) $settings['source_lang'] ) ) {
				throw new RuntimeException( 'post or translation changed while translation was running' );
			}
			QP_LLM_Sync::upsert_translation( $current, $lang, $translated, $manual );
			$this->complete_job( (int) $job['id'] );
		} catch ( Throwable $e ) {
			$message = $e->getMessage();
			if ( (int) $job['attempts'] < 4 && class_exists( 'QP_LLM_Client' ) && QP_LLM_Client::is_transient_error( $message ) ) {
				$this->requeue_job( (int) $job['id'], $message );
				return;
			}
			$this->fail_job( (int) $job['id'], $message );
		}
	}

	private static function job_is_current( WP_Post $post, array $job, string $source_lang ): bool {
		$expected = (string) ( $job['source_hash'] ?? '' );
		if ( '' === $expected || ! hash_equals( $expected, self::source_hash( $post ) ) ) {
			return false;
		}
		$post_lang = function_exists( 'pll_get_post_language' ) ? (string) pll_get_post_language( (int) $post->ID ) : '';
		$target_lang = (string) $job['target_lang'];
		$target_id = $post_lang === $target_lang ? (int) $post->ID : QP_LLM_Sync::existing_translation( $post, $target_lang );
		if ( $target_id !== (int) ( $job['target_post_id'] ?? 0 ) ) {
			return false;
		}
		if ( $target_id ) {
			clean_post_cache( $target_id );
			$target = get_post( $target_id );
			if ( ! $target || ! hash_equals( (string) ( $job['target_hash'] ?? '' ), self::source_hash( $target ) ) ) {
				return false;
			}
		} elseif ( 'none' !== (string) ( $job['target_hash'] ?? '' ) ) {
			return false;
		}
		if ( function_exists( 'pll_get_post_language' ) ) {
			if ( $post_lang === $target_lang ) {
				return ! empty( $job['manual_request'] );
			}
			if ( $post_lang !== $source_lang ) {
				return false;
			}
		}
		return ! empty( $job['manual_request'] ) || 'publish' === $post->post_status;
	}

	private function complete_job( int $id ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'        => 'done',
				'error_message' => '',
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	private function requeue_job( int $id, string $message ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'        => 'queued',
				'error_message' => substr( 'retry: ' . $message, 0, 1000 ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	private function fail_job( int $id, string $message ): void {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array(
				'status'        => 'error',
				'error_message' => substr( $message, 0, 1000 ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public function jobs_for_post( int $post_id ): array {
		global $wpdb;
		$table = self::table();
		$rows  = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE source_post_id = %d ORDER BY id DESC LIMIT 12", $post_id ),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	public function recent_jobs( int $limit = 20 ): array {
		global $wpdb;
		$table = self::table();
		$limit = max( 1, min( 100, $limit ) );
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC LIMIT {$limit}", ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Jobs for the toast: active queue plus recently finished rows.
	 */
	public function status_jobs( int $post_id = 0 ): array {
		global $wpdb;
		$table = self::table();
		$sql   = "SELECT * FROM {$table} WHERE status IN ('queued','running')";
		$args  = array();
		if ( $post_id > 0 ) {
			$sql   .= ' AND source_post_id = %d';
			$args[] = $post_id;
		}
		$sql .= ' ORDER BY id ASC';
		$rows = $args
			? $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A )
			: $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$recent_sql = "SELECT * FROM {$table} WHERE status IN ('done','error') AND updated_at >= %s";
		$recent_args = array( wp_date( 'Y-m-d H:i:s', time() - 3 * MINUTE_IN_SECONDS ) );
		if ( $post_id > 0 ) {
			$recent_sql    .= ' AND source_post_id = %d';
			$recent_args[]  = $post_id;
		}
		$recent_sql .= ' ORDER BY updated_at DESC LIMIT 20';
		$recent = $wpdb->get_results( $wpdb->prepare( $recent_sql, $recent_args ), ARRAY_A );
		if ( is_array( $recent ) ) {
			$seen = array();
			foreach ( $rows as $row ) {
				$seen[ (int) $row['id'] ] = true;
			}
			foreach ( $recent as $row ) {
				$id = (int) $row['id'];
				if ( empty( $seen[ $id ] ) ) {
					$rows[] = $row;
				}
			}
		}

		$out = array();
		foreach ( $rows as $row ) {
			$pid   = (int) $row['source_post_id'];
			$title = get_the_title( $pid );
			$out[] = array(
				'id'             => (int) $row['id'],
				'post_id'        => $pid,
				'title'          => $title ? wp_strip_all_tags( $title ) : ( '#' . $pid ),
				'target_lang'    => (string) $row['target_lang'],
				'status'         => (string) $row['status'],
				'error_message'  => (string) $row['error_message'],
			);
		}
		return $out;
	}
}
