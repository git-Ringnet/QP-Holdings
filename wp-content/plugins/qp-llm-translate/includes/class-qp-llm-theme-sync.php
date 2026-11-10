<?php
/**
 * Automatically synchronize critical theme fixes with active QP Holdings theme.
 *
 * Ensures theme compatibility without requiring manual theme replacement on live site.
 */

defined( 'ABSPATH' ) || exit;

final class QP_LLM_Theme_Sync {

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'sync_files_if_needed' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'sync_files_if_needed' ) );
	}

	public static function sync_files_if_needed(): void {
		$version = defined( 'QP_LLM_TRANSLATE_VERSION' ) ? QP_LLM_TRANSLATE_VERSION : '1.3.2';
		$synced_ver = get_option( 'qp_llm_theme_synced_version', '' );

		if ( $synced_ver === $version && ! isset( $_GET['qp_force_theme_sync'] ) ) {
			return;
		}

		$source_dir = QP_LLM_TRANSLATE_DIR . 'theme-updates/';
		if ( ! is_dir( $source_dir ) ) {
			return;
		}

		$target_dir = get_template_directory() . '/';
		if ( ! is_dir( $target_dir ) || ! is_writable( $target_dir ) ) {
			return;
		}

		$files = array(
			'functions.php',
			'front-page.php',
			'header.php',
			'header-legacy.php',
			'footer.php',
			'inc/i18n.php',
			'inc/dynamic-investor.php',
			'inc/dynamic-content.php',
			'inc/dynamic-acf.php',
			'inc/dynamic-news.php',
			'inc/dynamic-gallery.php',
			'inc/dynamic-projects.php',
			'inc/dynamic-custom-menu.php',
			'inc/custom-code.php',
			'assets/site-script.js',
			'assets/site-script.min.js',
			'assets/site-styles.css',
			'assets/site-styles.min.css',
			'single-qp_investor_doc.php',
			'single-qp_project.php',
			'page-du-an.php',
			'page-ban-tin.php',
			'page-newsletter.php',
			'page-ban-tin-nha-dau-tu-v2.php',
			'template-parts/content-news-single.php',
			'template-parts/investor-page.php',
		);

		foreach ( $files as $rel ) {
			$src = $source_dir . $rel;
			$dst = $target_dir . $rel;
			if ( file_exists( $src ) ) {
				$dst_dir = dirname( $dst );
				if ( ! is_dir( $dst_dir ) ) {
					wp_mkdir_p( $dst_dir );
				}
				@copy( $src, $dst );
				if ( function_exists( 'opcache_invalidate' ) ) {
					@opcache_invalidate( $dst, true );
				}
			}
		}

		// Sanitize custom code snippets in database (remove any problematic scripts)
		$custom_code = get_option( 'qp_holdings_custom_code', array() );
		if ( is_array( $custom_code ) && ! empty( $custom_code ) ) {
			$changed = false;
			foreach ( $custom_code as $slot => $snippets ) {
				if ( ! is_array( $snippets ) ) {
					continue;
				}
				foreach ( $snippets as $idx => $s ) {
					$content = isset( $s['content'] ) ? (string) $s['content'] : '';
					if ( false !== stripos( $content, 'qp-lightbox-vertical' ) ||
					     false !== stripos( $content, 'is-vertical-video' ) ||
					     false !== stripos( $content, 'MutationObserver' ) ) {
						unset( $custom_code[ $slot ][ $idx ] );
						$changed = true;
					}
				}
				if ( isset( $custom_code[ $slot ] ) ) {
					$custom_code[ $slot ] = array_values( $custom_code[ $slot ] );
				}
			}
			if ( $changed ) {
				update_option( 'qp_holdings_custom_code', $custom_code );
			}
		}

		if ( function_exists( 'opcache_reset' ) ) {
			@opcache_reset();
		}

		// Purge LiteSpeed Cache / WP Super Cache if present
		if ( function_exists( 'litespeed_purge_all' ) ) {
			litespeed_purge_all();
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}

		update_option( 'qp_llm_theme_synced_version', $version );
	}
}
