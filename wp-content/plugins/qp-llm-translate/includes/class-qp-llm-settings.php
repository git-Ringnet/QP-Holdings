<?php
defined( 'ABSPATH' ) || exit;

final class QP_LLM_Settings {
	public const OPTION = 'qp_llm_translate_settings';
	public const TOKEN  = 'qp_llm_translate_worker_token';

	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function boot(): void {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_init', array( $this, 'maybe_enable_job_post_type' ), 20 );
		add_action( 'admin_init', array( $this, 'maybe_enable_investor_doc_post_type' ), 21 );
		add_action( 'admin_init', array( $this, 'maybe_enable_project_post_type' ), 22 );
		add_action( 'admin_init', array( $this, 'remove_obsolete_update_setting' ), 23 );
	}

	public static function defaults(): array {
		return array(
			'provider'        => 'deepseek',
			'api_key'         => '',
			'model'           => '',
			'source_lang'     => 'vi',
			'target_langs'    => array( 'en', 'zh' ),
			'post_types'      => array( 'qp_news', 'qp_job', 'qp_investor_doc', 'qp_project' ),
			'auto_on_publish' => 1,
		);
	}

	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		unset( $stored['auto_on_update'] );
		$settings = array_merge( self::defaults(), $stored );
		$settings['target_langs'] = array_values( array_filter( (array) $settings['target_langs'] ) );
		$settings['post_types']   = array_values( array_filter( (array) $settings['post_types'] ) );
		return $settings;
	}

	public function post_types(): array {
		$types = $this->get()['post_types'];
		return $types ? $types : array( 'qp_news', 'qp_job', 'qp_investor_doc', 'qp_project' );
	}

	/**
	 * Existing installs stored post_types as news-only. Enable jobs once.
	 */
	public function maybe_enable_job_post_type(): void {
		if ( get_option( 'qp_llm_translate_jobs_v1' ) ) {
			return;
		}
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$types = array_values( array_filter( array_map( 'sanitize_key', (array) ( $stored['post_types'] ?? array() ) ) ) );
		if ( ! in_array( 'qp_job', $types, true ) ) {
			$types[] = 'qp_job';
			$stored['post_types'] = $types;
			update_option( self::OPTION, $stored );
		}
		update_option( 'qp_llm_translate_jobs_v1', 1, false );
	}

	/**
	 * Existing installs stored post_types without investor docs. Enable once.
	 */
	public function maybe_enable_investor_doc_post_type(): void {
		if ( get_option( 'qp_llm_translate_investor_v1' ) ) {
			return;
		}
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$types = array_values( array_filter( array_map( 'sanitize_key', (array) ( $stored['post_types'] ?? array() ) ) ) );
		if ( ! in_array( 'qp_investor_doc', $types, true ) ) {
			$types[] = 'qp_investor_doc';
			$stored['post_types'] = $types;
			update_option( self::OPTION, $stored );
		}
		update_option( 'qp_llm_translate_investor_v1', 1, false );
	}

	/**
	 * Existing installs stored post_types without projects. Enable once.
	 */
	public function maybe_enable_project_post_type(): void {
		if ( get_option( 'qp_llm_translate_project_v1' ) ) {
			return;
		}
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$types = array_values( array_filter( array_map( 'sanitize_key', (array) ( $stored['post_types'] ?? array() ) ) ) );
		if ( ! in_array( 'qp_project', $types, true ) ) {
			$types[] = 'qp_project';
			$stored['post_types'] = $types;
			update_option( self::OPTION, $stored );
		}
		update_option( 'qp_llm_translate_project_v1', 1, false );
	}

	public function remove_obsolete_update_setting(): void {
		$stored = get_option( self::OPTION, array() );
		if ( is_array( $stored ) && array_key_exists( 'auto_on_update', $stored ) ) {
			unset( $stored['auto_on_update'] );
			update_option( self::OPTION, $stored );
		}
	}


	public function target_langs(): array {
		$langs = $this->get()['target_langs'];
		return $langs ? $langs : array( 'en', 'zh' );
	}

	public static function selectable_post_types(): array {
		$objects = get_post_types(
			array(
				'show_ui' => true,
			),
			'objects'
		);
		$skip = array( 'attachment', 'wp_block', 'wp_navigation', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_font_family', 'wp_font_face', 'acf-field-group', 'acf-field' );
		$out  = array();
		foreach ( $objects as $name => $obj ) {
			if ( in_array( $name, $skip, true ) ) {
				continue;
			}
			$label = $obj->labels->singular_name ?? $obj->label ?? $name;
			$out[ $name ] = sprintf( '%s (%s)', $label, $name );
		}
		return $out;
	}

	public function worker_token(): string {
		$token = (string) get_option( self::TOKEN, '' );
		if ( '' === $token ) {
			$token = wp_generate_password( 32, false, false );
			update_option( self::TOKEN, $token, false );
		}
		return $token;
	}

	public function menu(): void {
		add_options_page(
			__( 'QP LLM Translate', 'qp-llm-translate' ),
			__( 'QP LLM Translate', 'qp-llm-translate' ),
			'manage_options',
			'qp-llm-translate',
			array( $this, 'render' )
		);
	}

	public function register(): void {
		register_setting(
			'qp_llm_translate',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	public function sanitize( $input ): array {
		$current = $this->get();
		$input   = is_array( $input ) ? $input : array();
		$out     = self::defaults();

		$provider = sanitize_key( (string) ( $input['provider'] ?? 'deepseek' ) );
		$out['provider'] = in_array( $provider, array( 'deepseek', 'openai', 'gemini' ), true ) ? $provider : 'deepseek';
		$key = trim( (string) ( $input['api_key'] ?? '' ) );
		$out['api_key'] = ( '' === $key || 0 === strpos( $key, '••••' ) ) ? (string) $current['api_key'] : $key;
		$out['model'] = sanitize_text_field( (string) ( $input['model'] ?? '' ) );
		$out['source_lang'] = sanitize_key( (string) ( $input['source_lang'] ?? 'vi' ) );
		$targets = isset( $input['target_langs'] ) ? (array) $input['target_langs'] : array();
		$out['target_langs'] = array_values( array_intersect( array_map( 'sanitize_key', $targets ), array( 'en', 'zh', 'vi', 'fr', 'ja', 'ko' ) ) );
		$types_raw = isset( $input['post_types'] ) ? (array) $input['post_types'] : array();
		$allowed   = array_keys( self::selectable_post_types() );
		$types     = array_values( array_intersect( array_map( 'sanitize_key', $types_raw ), $allowed ) );
		$out['post_types'] = $types ? $types : array( 'qp_news', 'qp_job', 'qp_investor_doc' );
		$out['auto_on_publish'] = empty( $input['auto_on_publish'] ) ? 0 : 1;
		return $out;
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s     = $this->get();
		$jobs  = QP_LLM_Queue::instance()->recent_jobs( 20 );
		$masked = $s['api_key'] ? '••••••••' . substr( $s['api_key'], -4 ) : '';
		?>
		<div class="wrap qp-llm-settings">
			<img src="<?php echo esc_url( QP_LLM_TRANSLATE_URL . 'assets/icon-256x256.png' ); ?>" alt="" class="qp-llm-logo" width="64" height="64">
			<h1><?php esc_html_e( 'QP LLM Translate', 'qp-llm-translate' ); ?></h1>
			<p><?php esc_html_e( 'Translate news (and other post types) with an LLM. Jobs run in the background: publishing a post does not wait for the API.', 'qp-llm-translate' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'qp_llm_translate' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="qp_llm_provider"><?php esc_html_e( 'Provider', 'qp-llm-translate' ); ?></label></th>
						<td>
							<select id="qp_llm_provider" name="<?php echo esc_attr( self::OPTION ); ?>[provider]">
								<option value="deepseek" <?php selected( $s['provider'], 'deepseek' ); ?>>DeepSeek</option>
								<option value="openai" <?php selected( $s['provider'], 'openai' ); ?>>OpenAI</option>
								<option value="gemini" <?php selected( $s['provider'], 'gemini' ); ?>>Google Gemini</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qp_llm_api_key"><?php esc_html_e( 'API key', 'qp-llm-translate' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" id="qp_llm_api_key" name="<?php echo esc_attr( self::OPTION ); ?>[api_key]" value="<?php echo esc_attr( $masked ); ?>" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Stored in the WordPress database. Leave the masked value unchanged to keep the current key.', 'qp-llm-translate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qp_llm_model"><?php esc_html_e( 'Model (optional)', 'qp-llm-translate' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" id="qp_llm_model" name="<?php echo esc_attr( self::OPTION ); ?>[model]" value="<?php echo esc_attr( (string) $s['model'] ); ?>" placeholder="deepseek-chat / gpt-4o-mini / gemini-2.0-flash">
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="qp_llm_source"><?php esc_html_e( 'Source language', 'qp-llm-translate' ); ?></label></th>
						<td>
							<input type="text" id="qp_llm_source" name="<?php echo esc_attr( self::OPTION ); ?>[source_lang]" value="<?php echo esc_attr( (string) $s['source_lang'] ); ?>" class="small-text">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Target languages', 'qp-llm-translate' ); ?></th>
						<td>
							<?php foreach ( array( 'en' => 'English', 'zh' => '中文', 'vi' => 'Tiếng Việt', 'ja' => '日本語', 'ko' => '한국어', 'fr' => 'Français' ) as $code => $label ) : ?>
								<label style="margin-right:12px;">
									<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[target_langs][]" value="<?php echo esc_attr( $code ); ?>" <?php checked( in_array( $code, $s['target_langs'], true ) ); ?>>
									<?php echo esc_html( $label . ' (' . $code . ')' ); ?>
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Post types', 'qp-llm-translate' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Post types', 'qp-llm-translate' ); ?></legend>
								<?php foreach ( self::selectable_post_types() as $name => $label ) : ?>
									<label style="display:block;margin:4px 0;">
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[post_types][]" value="<?php echo esc_attr( $name ); ?>" <?php checked( in_array( $name, $s['post_types'], true ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</fieldset>
							<p class="description"><?php esc_html_e( 'Chọn loại bài cần dịch. Có thể chọn nhiều.', 'qp-llm-translate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatic queue', 'qp-llm-translate' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[auto_on_publish]" value="1" <?php checked( ! empty( $s['auto_on_publish'] ) ); ?>> <?php esc_html_e( 'Translate the Vietnamese original when it is first published', 'qp-llm-translate' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Recent jobs', 'qp-llm-translate' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>ID</th><th><?php esc_html_e( 'Post', 'qp-llm-translate' ); ?></th><th><?php esc_html_e( 'Lang', 'qp-llm-translate' ); ?></th>
						<th><?php esc_html_e( 'Status', 'qp-llm-translate' ); ?></th><th><?php esc_html_e( 'Error', 'qp-llm-translate' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! $jobs ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No jobs yet.', 'qp-llm-translate' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $jobs as $job ) : ?>
						<tr>
							<td><?php echo esc_html( (string) $job['id'] ); ?></td>
							<td><?php echo esc_html( (string) $job['source_post_id'] ); ?></td>
							<td><?php echo esc_html( (string) $job['target_lang'] ); ?></td>
							<td><?php echo esc_html( (string) $job['status'] ); ?></td>
							<td><?php echo esc_html( (string) $job['error_message'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}