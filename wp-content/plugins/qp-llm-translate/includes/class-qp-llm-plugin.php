<?php
defined( 'ABSPATH' ) || exit;

final class QP_LLM_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		QP_LLM_Queue::instance()->boot();
		QP_LLM_Settings::instance()->boot();

		add_action( 'wp_after_insert_post', array( $this, 'maybe_queue_on_publish' ), 40, 4 );
		add_action( 'add_meta_boxes', array( $this, 'register_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_qp_llm_queue_now', array( $this, 'ajax_queue_now' ) );
		add_action( 'wp_ajax_qp_llm_job_status', array( $this, 'ajax_job_status' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( QP_LLM_TRANSLATE_FILE ), array( $this, 'action_links' ) );
	}

	public function action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=qp-llm-translate' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'qp-llm-translate' ) . '</a>' );
		return $links;
	}

	public function enqueue_assets(): void {
		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$post_id = 0;
		if ( is_admin() ) {
			$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : 0;
		} elseif ( is_singular() ) {
			$post_id = (int) get_queried_object_id();
		}

		wp_enqueue_style(
			'qp-llm-translate-admin',
			QP_LLM_TRANSLATE_URL . 'assets/admin.css',
			array(),
			QP_LLM_TRANSLATE_VERSION
		);
		wp_enqueue_script(
			'qp-llm-translate-admin',
			QP_LLM_TRANSLATE_URL . 'assets/admin.js',
			array( 'jquery' ),
			QP_LLM_TRANSLATE_VERSION,
			true
		);
		wp_localize_script(
			'qp-llm-translate-admin',
			'qpLlmTranslate',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'qp_llm_translate' ),
				'postId'  => $post_id,
				'i18n'    => array(
					'queued'    => __( 'Đã đưa vào hàng đợi. Đang dịch ở nền.', 'qp-llm-translate' ),
					'error'     => __( 'Không thể bắt đầu dịch.', 'qp-llm-translate' ),
					'title'     => __( 'Đang dịch', 'qp-llm-translate' ),
					'done'      => __( 'Đã dịch xong', 'qp-llm-translate' ),
					'failed'    => __( 'Dịch lỗi', 'qp-llm-translate' ),
					'queuedJob' => __( 'Chờ', 'qp-llm-translate' ),
					'running'   => __( 'Đang dịch', 'qp-llm-translate' ),
					'doneJob'   => __( 'Xong', 'qp-llm-translate' ),
					'errorJob'  => __( 'Lỗi', 'qp-llm-translate' ),
				),
			)
		);
	}

	public function register_metabox(): void {
		foreach ( QP_LLM_Settings::instance()->post_types() as $type ) {
			add_meta_box(
				'qp-llm-translate',
				__( 'LLM translation', 'qp-llm-translate' ),
				array( $this, 'render_metabox' ),
				$type,
				'side',
				'high'
			);
		}
	}

	public function render_metabox( WP_Post $post ): void {
		$jobs       = QP_LLM_Queue::instance()->jobs_for_post( (int) $post->ID );
		$source     = (string) QP_LLM_Settings::instance()->get()['source_lang'];
		$post_lang  = '';
		if ( function_exists( 'pll_get_post_language' ) ) {
			$post_lang = (string) pll_get_post_language( (int) $post->ID );
		}
		$in_place = ( $post_lang && $post_lang !== $source );

		if ( $in_place ) {
			echo '<p>' . esc_html__( 'Translate this post\'s title and content into the selected language, including when the title is still English.', 'qp-llm-translate' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'Queue EN/ZH (or configured languages) without waiting for the page to finish loading. Titles are translated with the body.', 'qp-llm-translate' ) . '</p>';
		}
		echo '<p><button type="button" class="button button-primary qp-llm-queue-now" data-post="' . esc_attr( (string) $post->ID ) . '">';
		echo esc_html__( 'Translate now', 'qp-llm-translate' );
		echo '</button></p>';
		echo '<div class="qp-llm-queue-status"></div>';

		if ( $jobs ) {
			echo '<ul class="qp-llm-job-list">';
			foreach ( $jobs as $job ) {
				printf(
					'<li><strong>%s</strong> — %s%s</li>',
					esc_html( strtoupper( (string) $job['target_lang'] ) ),
					esc_html( (string) $job['status'] ),
					! empty( $job['error_message'] ) ? ' · ' . esc_html( (string) $job['error_message'] ) : ''
				);
			}
			echo '</ul>';
		}
	}

	public function ajax_queue_now(): void {
		check_ajax_referer( 'qp_llm_translate', 'nonce' );
		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		$queued = QP_LLM_Queue::instance()->enqueue_post( $post_id, true );
		if ( $queued ) {
			QP_LLM_Queue::instance()->spawn_worker();
		}
		wp_send_json_success(
			array(
				'queued' => $queued,
				'jobs'   => QP_LLM_Queue::instance()->status_jobs( $post_id ),
			)
		);
	}

	public function ajax_job_status(): void {
		check_ajax_referer( 'qp_llm_translate', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$post_id = isset( $_REQUEST['post_id'] ) ? (int) $_REQUEST['post_id'] : 0;
		$jobs    = QP_LLM_Queue::instance()->status_jobs( $post_id );
		$busy    = false;
		foreach ( $jobs as $job ) {
			if ( in_array( $job['status'], array( 'queued', 'running' ), true ) ) {
				$busy = true;
				break;
			}
		}
		wp_send_json_success(
			array(
				'jobs' => $jobs,
				'busy' => $busy,
			)
		);
	}

	public function maybe_queue_on_publish( int $post_id, WP_Post $post, bool $update, ?WP_Post $post_before ): void {
		if ( ! empty( $GLOBALS['qp_llm_suppress_queue'] ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// WordPress supplies the previous post here, including for REST and WP-CLI
		// saves. Never infer a first publication from a missing editor form field.
		if ( 'publish' !== $post->post_status
			|| ( $post_before instanceof WP_Post && 'publish' === $post_before->post_status )
			|| ( $update && ! $post_before ) ) {
			return;
		}
		$settings = QP_LLM_Settings::instance()->get();
		if ( empty( $settings['auto_on_publish'] )
			|| ! in_array( $post->post_type, QP_LLM_Settings::instance()->post_types(), true )
			|| QP_LLM_Sync::is_translation_copy( $post_id ) ) {
			return;
		}
		// Automatic jobs belong to the original only. A manually created EN/ZH
		// post must never be translated in place by a Save/Update request.
		if ( function_exists( 'pll_get_post_language' )
			&& (string) pll_get_post_language( $post_id ) !== (string) $settings['source_lang'] ) {
			return;
		}
		if ( QP_LLM_Queue::instance()->enqueue_post( $post_id, false ) ) {
			QP_LLM_Queue::instance()->spawn_worker();
		}
	}
}
