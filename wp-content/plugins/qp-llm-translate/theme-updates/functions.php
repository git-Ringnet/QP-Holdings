<?php
/**
 * Theme functions for QP Holdings.
 */

defined( 'ABSPATH' ) || exit;

/**
 * When the request comes through *.dev-prototype.cloud, rewrite localhost
 * absolute URLs so favicons, TinyMCE plugins, and enqueued assets load
 * over the tunnel HTTPS host instead of https://localhost:8090.
 */
if ( ! empty( $_SERVER['HTTP_HOST'] ) && false !== strpos( (string) $_SERVER['HTTP_HOST'], 'dev-prototype.cloud' ) ) {
	$qp_tunnel_proto = ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] )
		|| ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== $_SERVER['HTTPS'] )
		|| ( ! empty( $_SERVER['SERVER_PORT'] ) && 443 === (int) $_SERVER['SERVER_PORT'] )
		? 'https'
		: 'http';
	$qp_tunnel_url = $qp_tunnel_proto . '://' . $_SERVER['HTTP_HOST'];

	add_filter( 'option_siteurl', static fn() => $qp_tunnel_url, 20 );
	add_filter( 'option_home', static fn() => $qp_tunnel_url, 20 );

	$qp_replace_host = static function ( $url ) use ( $qp_tunnel_url ) {
		return is_string( $url ) && '' !== $url
			? preg_replace( '#^https?://(?:localhost:8090|127\.0\.0\.1:8090)#i', $qp_tunnel_url, $url )
			: $url;
	};

	foreach ( array( 'site_url', 'home_url', 'plugins_url', 'content_url', 'includes_url', 'theme_root_uri', 'stylesheet_directory_uri', 'template_directory_uri', 'script_loader_src', 'style_loader_src', 'site_icon_url', 'mce_css', 'pll_home_url', 'page_link', 'post_type_link' ) as $qp_url_filter ) {
		add_filter( $qp_url_filter, $qp_replace_host, 99 );
	}

	add_filter(
		'upload_dir',
		static function ( $uploads ) use ( $qp_replace_host ) {
			if ( is_array( $uploads ) ) {
				foreach ( array( 'url', 'baseurl' ) as $key ) {
					if ( ! empty( $uploads[ $key ] ) ) {
						$uploads[ $key ] = $qp_replace_host( $uploads[ $key ] );
					}
				}
			}
			return $uploads;
		},
		99
	);

	add_action(
		'init',
		static function () use ( $qp_tunnel_url ): void {
			if ( 'cli' === PHP_SAPI ) {
				return;
			}
			ob_start(
				static function ( $html ) use ( $qp_tunnel_url ): string {
					if ( ! is_string( $html ) || '' === $html ) {
						return '';
					}
					return preg_replace( '#https?://(?:localhost:8090|127\.0\.0\.1:8090)#i', $qp_tunnel_url, $html );
				}
			);
		},
		0
	);
}

$qp_holdings_i18n = get_template_directory() . '/inc/i18n.php';
if ( file_exists( $qp_holdings_i18n ) ) {
    require_once $qp_holdings_i18n;
}

$qp_holdings_inline_edit = get_template_directory() . '/inc/inline-edit.php';
if ( file_exists( $qp_holdings_inline_edit ) ) {
    require_once $qp_holdings_inline_edit;
}

$qp_holdings_inline_hero = get_template_directory() . '/inc/inline-hero.php';
if ( file_exists( $qp_holdings_inline_hero ) ) {
    require_once $qp_holdings_inline_hero;
}

$qp_holdings_legal_pages = get_template_directory() . '/inc/legal-pages.php';
if ( file_exists( $qp_holdings_legal_pages ) ) {
    require_once $qp_holdings_legal_pages;
}

$qp_holdings_custom_code = get_template_directory() . '/inc/custom-code.php';
if ( file_exists( $qp_holdings_custom_code ) ) {
    require_once $qp_holdings_custom_code;
}

$qp_holdings_mail = get_template_directory() . '/inc/dynamic-mail.php';
if ( file_exists( $qp_holdings_mail ) ) {
    require_once $qp_holdings_mail;
}

$qp_holdings_socials = get_template_directory() . '/inc/dynamic-socials.php';
if ( file_exists( $qp_holdings_socials ) ) {
    require_once $qp_holdings_socials;
}

$qp_holdings_custom_menu = get_template_directory() . '/inc/dynamic-custom-menu.php';
if ( file_exists( $qp_holdings_custom_menu ) ) {
    require_once $qp_holdings_custom_menu;
}

function qp_holdings_setup(): void {
    load_theme_textdomain( 'qp-holdings', get_template_directory() . '/languages' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );

    // Enables the "Site Icon" control in Customizer → Site Identity. A Site Icon
    // is the favicon source search engines (Bing/Google) prefer; once set,
    // WordPress core emits the proper <link rel="icon"> tags and serves
    // /favicon.ico automatically.
    add_theme_support( 'custom-logo', array(
        'height'      => 192,
        'width'       => 192,
        'flex-height' => true,
        'flex-width'  => true,
    ) );

    // News card image sizes. Cropped so they fill the fixed-ratio card frames
    // (object-fit: cover) without forcing the browser to upscale a too-small
    // source on high-DPR phones. `wp_get_attachment_image()` emits srcset from
    // these plus the default sizes, so retina screens get a sharp candidate.
    add_image_size( 'qp_news_featured', 1280, 800, true ); // featured card (near full-width hero)
    add_image_size( 'qp_news_card', 768, 512, true );      // vertical / horizontal list cards

    register_nav_menus(
        array(
            'primary' => __( 'Primary Menu', 'qp-holdings' ),
            'footer'  => __( 'Footer Menu', 'qp-holdings' ),
        )
    );
}
add_action( 'after_setup_theme', 'qp_holdings_setup' );

/**
 * Return a cache-busting version for a theme asset.
 */
function qp_holdings_asset_version( string $relative_path ): string {
    $path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

    if ( file_exists( $path ) ) {
        return (string) filemtime( $path );
    }

    return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Output favicon markup in the document head.
 *
 * If the admin has set a Site Icon (Customizer → Site Identity), WordPress core
 * already emits the icon tags via `wp_site_icon`, so we skip our fallback to
 * avoid duplicate / conflicting <link rel="icon"> tags. Otherwise we emit the
 * theme-bundled favicons. We intentionally do NOT point at a bare
 * `/favicon.ico` unless one is actually served (see qp_holdings_serve_favicon_ico),
 * because a 404 on /favicon.ico is exactly what stops Bing from showing an icon.
 */
function qp_holdings_output_favicons(): void {
    // Core handles everything when a Site Icon is configured.
    if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
        return;
    }

    $favicon_32  = 'assets/index_files/favicon-32.png';
    $favicon_192 = 'assets/index_files/favicon-192.png';
    $favicon_512 = 'assets/index_files/favicon-512.png';

    if ( ! file_exists( get_template_directory() . '/' . $favicon_32 ) || ! file_exists( get_template_directory() . '/' . $favicon_192 ) ) {
        return;
    }

    $favicon_ico_url = home_url( '/favicon.ico' );
    $favicon_32_url  = get_template_directory_uri() . '/' . $favicon_32 . '?v=' . rawurlencode( qp_holdings_asset_version( $favicon_32 ) );
    $favicon_192_url = get_template_directory_uri() . '/' . $favicon_192 . '?v=' . rawurlencode( qp_holdings_asset_version( $favicon_192 ) );
    $favicon_512_url = get_template_directory_uri() . '/' . $favicon_512 . '?v=' . rawurlencode( qp_holdings_asset_version( $favicon_512 ) );

    // /favicon.ico is served dynamically by qp_holdings_serve_favicon_ico().
    echo '<link rel="icon" href="' . esc_url( $favicon_ico_url ) . '" sizes="any">' . "\n";
    echo '<link rel="icon" href="' . esc_url( $favicon_32_url ) . '" sizes="32x32" type="image/png">' . "\n";
    echo '<link rel="icon" href="' . esc_url( $favicon_192_url ) . '" sizes="192x192" type="image/png">' . "\n";
    if ( file_exists( get_template_directory() . '/' . $favicon_512 ) ) {
        echo '<link rel="icon" href="' . esc_url( $favicon_512_url ) . '" sizes="512x512" type="image/png">' . "\n";
    }
    echo '<link rel="apple-touch-icon" href="' . esc_url( $favicon_192_url ) . '">' . "\n";
}
add_action( 'wp_head', 'qp_holdings_output_favicons', 1 );

/**
 * Keep the physical root `/favicon.ico` in sync with the current Site Icon.
 *
 * LiteSpeed serves a real web-root favicon before WordPress runs, so
 * the file itself must be updated whenever the admin changes Site Identity.
 */
function qp_holdings_sync_root_favicon( ...$unused_args ): void {
    $favicon_path = trailingslashit( ABSPATH ) . 'favicon.ico';
    $source_path  = '';

    if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
        $icon_id      = absint( get_option( 'site_icon' ) );
        $attached_path = $icon_id > 0 ? get_attached_file( $icon_id ) : '';

        if ( is_string( $attached_path ) && '' !== $attached_path && file_exists( $attached_path ) ) {
            $source_path = $attached_path;
        }
    }

    if ( '' === $source_path ) {
        $fallback_path = get_template_directory() . '/assets/index_files/favicon-192.png';
        if ( file_exists( $fallback_path ) ) {
            $source_path = $fallback_path;
        }
    }

    if ( '' === $source_path ) {
        return;
    }

    if ( class_exists( 'Imagick' ) ) {
        try {
            $image = new Imagick( $source_path );
            $image->setBackgroundColor( new ImagickPixel( 'transparent' ) );
            $image->mergeImageLayers( Imagick::LAYERMETHOD_MERGE );
            $image->resizeImage( 64, 64, Imagick::FILTER_LANCZOS, 1, true );
            $image->setImageFormat( 'ico' );
            $image->writeImage( $favicon_path );
            $image->clear();
            $image->destroy();
            return;
        } catch ( Throwable $e ) {
            // Fall through to a plain file copy when Imagick cannot build the ICO.
        }
    }

    copy( $source_path, $favicon_path );
}
add_action( 'update_option_site_icon', 'qp_holdings_sync_root_favicon', 10, 3 );
add_action( 'delete_option_site_icon', 'qp_holdings_sync_root_favicon', 10, 1 );

/**
 * Serve a real `/favicon.ico` at the site root.
 *
 * Search engines — Bing in particular — request `https://domain/favicon.ico`
 * directly. WordPress does not create this file, and the repo only bind-mounts
 * the theme, so a physical root favicon.ico is not an option. Instead we detect
 * the request early and stream the theme favicon (or the Site Icon if set),
 * turning what was a 404 into a valid icon response.
 */
function qp_holdings_serve_favicon_ico(): void {
    $request = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
    if ( '/favicon.ico' !== $request ) {
        return;
    }

    // Prefer the configured Site Icon when available.
    $icon_path = '';
    $icon_type = 'image/png';
    if ( function_exists( 'get_site_icon_url' ) && has_site_icon() ) {
        // Redirect to the Site Icon URL; lets WP/CDN handle caching + format.
        $url = get_site_icon_url( 192 );
        if ( $url ) {
            wp_safe_redirect( $url, 301 );
            exit;
        }
    }

    $candidate = get_template_directory() . '/assets/index_files/favicon-192.png';
    if ( file_exists( $candidate ) ) {
        $icon_path = $candidate;
    }

    if ( '' === $icon_path ) {
        return; // Let WordPress 404 as before.
    }

    nocache_headers();
    header( 'Content-Type: ' . $icon_type );
    header( 'Cache-Control: public, max-age=86400' );
    header( 'Content-Length: ' . (string) filesize( $icon_path ) );
    readfile( $icon_path );
    exit;
}
add_action( 'init', 'qp_holdings_serve_favicon_ico', 0 );

/**
 * Resolve the relative path to its `.min.{css,js}` counterpart when one
 * exists alongside the source file. Lets `bin/build-assets.mjs` ship
 * pre-minified assets without forcing devs to edit minified output.
 */
function qp_holdings_minified_asset_path( string $relative_path ): string {
    $relative_path = ltrim( $relative_path, '/' );

    if ( preg_match( '/\.min\.(css|js)$/i', $relative_path ) ) {
        return $relative_path;
    }

    if ( ! preg_match( '/\.(css|js)$/i', $relative_path, $ext_match ) ) {
        return $relative_path;
    }

    $candidate = preg_replace( '/\.(css|js)$/i', '.min.$1', $relative_path );
    if ( ! is_string( $candidate ) ) {
        return $relative_path;
    }

    $candidate_full = get_template_directory() . '/' . $candidate;

    return file_exists( $candidate_full ) ? $candidate : $relative_path;
}

/**
 * Enqueue a stylesheet only when the file exists, with filemtime cache busting.
 * Automatically prefers a sibling `.min.css` if one is present.
 */
function qp_holdings_enqueue_style_asset( string $handle, string $relative_path, array $deps = array(), string $media = 'all' ): void {
    $relative_path = qp_holdings_minified_asset_path( $relative_path );
    $path          = get_template_directory() . '/' . ltrim( $relative_path, '/' );
    if ( ! file_exists( $path ) ) {
        return;
    }

    wp_enqueue_style(
        $handle,
        get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ),
        $deps,
        qp_holdings_asset_version( $relative_path ),
        $media
    );
}

/**
 * Enqueue a script only when the file exists, with filemtime cache busting.
 * Automatically prefers a sibling `.min.js` if one is present.
 */
function qp_holdings_enqueue_script_asset( string $handle, string $relative_path, array $deps = array(), bool $in_footer = true ): void {
    $relative_path = qp_holdings_minified_asset_path( $relative_path );
    $path          = get_template_directory() . '/' . ltrim( $relative_path, '/' );
    if ( ! file_exists( $path ) ) {
        return;
    }

    wp_enqueue_script(
        $handle,
        get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ),
        $deps,
        qp_holdings_asset_version( $relative_path ),
        $in_footer
    );
}

/**
 * Output the site-wide cookie consent popup.
 */
function qp_holdings_cookie_consent_markup(): void {
    if ( is_admin() ) {
        return;
    }

    $privacy_url = function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'privacy' ) : home_url( '/chinh-sach-bao-mat/' );
    ?>
    <aside
        class="cookie-consent"
        data-cookie-consent-banner
        data-storage-key="qp_cookie_consent_v1"
        data-customize-label="<?php echo esc_attr( qp_holdings_t( 'cookie_customize' ) ); ?>"
        data-save-label="<?php echo esc_attr( qp_holdings_t( 'cookie_save_preferences' ) ); ?>"
        role="region"
        aria-label="<?php echo esc_attr( qp_holdings_t( 'cookie_consent_aria' ) ); ?>"
        hidden
    >
        <div class="cookie-consent__copy">
            <p>
                <span class="cookie-consent__brand"><?php echo esc_html( qp_holdings_t( 'cookie_brand' ) ); ?></span><?php echo esc_html( qp_holdings_t( 'cookie_body' ) ); ?>
            </p>
            <a class="cookie-consent__link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( qp_holdings_t( 'cookie_learn_more' ) ); ?></a>
        </div>

        <div class="cookie-consent__settings" id="cookie-consent-settings" data-cookie-settings hidden>
            <div class="cookie-consent__option cookie-consent__option--locked">
                <span class="cookie-consent__option-copy">
                    <strong><?php echo esc_html( qp_holdings_t( 'cookie_required_title' ) ); ?></strong>
                    <small><?php echo esc_html( qp_holdings_t( 'cookie_required_desc' ) ); ?></small>
                </span>
                <span class="cookie-consent__switch is-on" aria-hidden="true"></span>
            </div>
            <label class="cookie-consent__option">
                <span class="cookie-consent__option-copy">
                    <strong><?php echo esc_html( qp_holdings_t( 'cookie_analytics_title' ) ); ?></strong>
                    <small><?php echo esc_html( qp_holdings_t( 'cookie_analytics_desc' ) ); ?></small>
                </span>
                <input class="sr-only" type="checkbox" data-cookie-category="analytics">
                <span class="cookie-consent__switch" aria-hidden="true"></span>
            </label>
            <label class="cookie-consent__option">
                <span class="cookie-consent__option-copy">
                    <strong><?php echo esc_html( qp_holdings_t( 'cookie_marketing_title' ) ); ?></strong>
                    <small><?php echo esc_html( qp_holdings_t( 'cookie_marketing_desc' ) ); ?></small>
                </span>
                <input class="sr-only" type="checkbox" data-cookie-category="marketing">
                <span class="cookie-consent__switch" aria-hidden="true"></span>
            </label>
        </div>

        <div class="cookie-consent__actions">
            <button class="cookie-consent__button cookie-consent__button--outline" type="button" data-cookie-consent="reject"><?php echo esc_html( qp_holdings_t( 'cookie_reject' ) ); ?></button>
            <button class="cookie-consent__button cookie-consent__button--outline" type="button" data-cookie-consent="customize" aria-expanded="false" aria-controls="cookie-consent-settings"><?php echo esc_html( qp_holdings_t( 'cookie_customize' ) ); ?></button>
            <button class="cookie-consent__button cookie-consent__button--accept" type="button" data-cookie-consent="accept"><?php echo esc_html( qp_holdings_t( 'cookie_accept_all' ) ); ?></button>
        </div>
    </aside>
    <?php
}
add_action( 'wp_footer', 'qp_holdings_cookie_consent_markup', 5 );

/**
 * Build escaped image attributes from a theme asset.
 */
function qp_holdings_theme_image_attributes( string $relative_path, array $args = array() ): string {
	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );

	if ( ! file_exists( $path ) ) {
		return '';
	}

	$attributes = array(
		'src' => get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ),
	);

	$size = wp_getimagesize( $path );
	if ( is_array( $size ) ) {
		$attributes['width']  = (string) $size[0];
		$attributes['height'] = (string) $size[1];
	}

	foreach ( $args as $key => $value ) {
		if ( null === $value || '' === $value ) {
			continue;
		}

		$attributes[ $key ] = (string) $value;
	}

	$parts = array();
	foreach ( $attributes as $key => $value ) {
		$parts[] = sprintf( '%s="%s"', sanitize_key( $key ), esc_attr( $value ) );
	}

	return implode( ' ', $parts );
}

/**
 * Return generated responsive image candidates for a theme asset.
 *
 * @return array<int, array<string, string>>
 */
function qp_holdings_theme_image_sources( string $relative_path, string $format = 'webp' ): array {
	$relative_path = ltrim( $relative_path, '/' );
	$path_info     = pathinfo( $relative_path );
	$base_name     = $path_info['filename'] ?? '';
	$format        = strtolower( $format );

	if ( '' === $base_name || ! in_array( $format, array( 'webp', 'avif' ), true ) ) {
		return array();
	}

	$sources = array();
	foreach ( array( 240, 320, 480, 640, 768, 1024, 1440, 1920 ) as $width ) {
		$candidate_relative = 'assets/site/responsive/' . $base_name . '-' . $width . '.' . $format;
		$candidate_path     = get_template_directory() . '/' . $candidate_relative;
		if ( ! file_exists( $candidate_path ) ) {
			continue;
		}

		$sources[] = array(
			'url'   => get_template_directory_uri() . '/' . $candidate_relative,
			'width' => (string) $width,
		);
	}

	return $sources;
}

/**
 * Return the largest available responsive source URL for a theme asset.
 *
 * Used as the full-size target for the gallery lightbox so the overlay shows a
 * crisp image without serving the bulky original PNG. Falls back to the WebP
 * candidates first (smallest payload), then the original file.
 */
function qp_holdings_theme_image_largest_src( string $relative_path ): string {
	$webp = qp_holdings_theme_image_sources( $relative_path, 'webp' );
	if ( ! empty( $webp ) ) {
		$largest = end( $webp );
		return $largest['url'];
	}

	$path = get_template_directory() . '/' . ltrim( $relative_path, '/' );
	return file_exists( $path ) ? get_template_directory_uri() . '/' . ltrim( $relative_path, '/' ) : '';
}

/**
 * Render responsive picture markup for a theme asset.
 */
function qp_holdings_theme_picture( string $relative_path, array $img_args = array(), string $sizes = '100vw' ): string {
	$img_attrs = qp_holdings_theme_image_attributes( $relative_path, $img_args );
	if ( '' === $img_attrs ) {
		return '';
	}

	// Use smallest WebP as fallback src instead of bulky original PNG.
	$webp_sources = qp_holdings_theme_image_sources( $relative_path, 'webp' );
	if ( ! empty( $webp_sources ) ) {
		$fallback_src = $webp_sources[0]['url'];
		$img_attrs    = preg_replace( '/\bsrc="[^"]*"/', 'src="' . esc_url( $fallback_src ) . '"', $img_attrs );
	}

	$parts = array( '<picture>' );
	foreach ( array( 'avif' => 'image/avif', 'webp' => 'image/webp' ) as $format => $mime ) {
		$sources = qp_holdings_theme_image_sources( $relative_path, $format );
		if ( empty( $sources ) ) {
			continue;
		}

		$srcset = array();
		foreach ( $sources as $source ) {
			$srcset[] = esc_url( $source['url'] ) . ' ' . esc_attr( $source['width'] ) . 'w';
		}

		$parts[] = sprintf(
			'<source type="%s" srcset="%s" sizes="%s">',
			esc_attr( $mime ),
			esc_attr( implode( ', ', $srcset ) ),
			esc_attr( $sizes )
		);
	}

	$parts[] = '<img ' . $img_attrs . '>';
	$parts[] = '</picture>';

	return implode( '', $parts );
}

/**
 * Remove small WordPress defaults that add bytes/requests but are not used here.
 */
function qp_holdings_disable_unused_core_assets(): void {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'wp_generator' );
}
add_action( 'init', 'qp_holdings_disable_unused_core_assets' );
add_filter( 'emoji_svg_url', '__return_false' );

/**
 * Add resource hints and preloads for critical assets.
 */
function qp_holdings_resource_hints( array $urls, string $relation_type ): array {
    $hints = array();

    if ( 'dns-prefetch' === $relation_type ) {
        $hints = array(
            'https://cdn.jsdelivr.net',
            'https://unpkg.com',
        );
    }

    if ( 'preconnect' === $relation_type ) {
        // Preserve the preconnect to fonts.gstatic.com only when 3rd-party
        // Google Fonts are still in play. Self-hosted now, so we drop them.
        $hints = array();
    }

    return array_values( array_unique( array_merge( $urls, $hints ), SORT_REGULAR ) );
}
add_filter( 'wp_resource_hints', 'qp_holdings_resource_hints', 10, 2 );

/**
 * Preload critical above-the-fold assets for faster LCP.
 */
function qp_holdings_output_critical_preloads(): void {
    $theme_uri      = get_template_directory_uri();
    $responsive_dir = '/assets/site/responsive/';
    $hero_base      = get_template_directory() . $responsive_dir;

    // Use a viewport-aware hero preload so mobile fetches the small AVIF
    // (≈11 KB) and desktop fetches the larger one without doubling up.
    // The hero filename differs per template, so pick the right base slug for
    // the current page — otherwise that template's LCP image is discovered
    // late (only after the deferred CSS / DOM parse) and LCP balloons.
    $hero_slug = 'hero-bg-1b89f1';
    if ( is_page_template( 'page-gioi-thieu.php' ) ) {
        $hero_slug = 'about-hero-bg-62c368';
    } elseif ( is_page_template( 'page-lien-he.php' ) ) {
        $hero_slug = 'contact-hero-bg-3476c8';
	} elseif ( is_page_template( 'page-cau-hoi-thuong-gap.php' ) ) {
		$hero_slug = 'careers-hero-team-5fdb60';
    }

    $hero_variants = array();
    foreach ( array( 480, 768, 1024, 1440, 1920 ) as $w ) {
        $candidate = $hero_base . $hero_slug . '-' . $w . '.avif';
        if ( file_exists( $candidate ) ) {
            $hero_variants[ $w ] = $theme_uri . $responsive_dir . $hero_slug . '-' . $w . '.avif';
        }
    }

    if ( ! empty( $hero_variants ) ) {
        $imagesrcset = array();
        foreach ( $hero_variants as $w => $url ) {
            $imagesrcset[] = esc_url( $url ) . ' ' . (int) $w . 'w';
        }
        $fallback_url = $hero_variants[ 1024 ] ?? reset( $hero_variants );
        echo '<link rel="preload" as="image" type="image/avif" href="' . esc_url( $fallback_url ) . '" imagesrcset="' . esc_attr( implode( ', ', $imagesrcset ) ) . '" imagesizes="100vw" fetchpriority="high">' . "\n";
    }

    // Preload the latin and Vietnamese woff2 subsets so first paint already
    // has the brand font available — eliminates the FOUT-driven layout shift
    // that Lighthouse flagged on production (Δ CLS ≈ 0.16).
    $font_dir = $theme_uri . '/assets/fonts';
    $preload_fonts = array( 'noto-sans-latin-400.woff2', 'noto-sans-latin-ext-400.woff2', 'noto-sans-vi-400.woff2' );
    // Preload the Simplified-Chinese subset only on zh so vi/en visitors don't
    // pay for the ~1 MB CJK file. The @font-face still loads it on demand
    // elsewhere; this just removes the FOUT on the Chinese site.
    if ( function_exists( 'qp_holdings_current_language' ) && 'zh' === qp_holdings_current_language() ) {
        $preload_fonts[] = 'noto-sans-sc-400.woff2';
    }
    foreach ( $preload_fonts as $font_file ) {
        echo '<link rel="preload" as="font" type="font/woff2" crossorigin="anonymous" href="' . esc_url( $font_dir . '/' . $font_file ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'qp_holdings_output_critical_preloads', 2 );

/**
 * Inline a minimal critical-CSS bundle in <head> so first paint can happen
 * before the full site-styles.css/style.css load asynchronously. Source
 * lives at assets/critical.css and is hand-tuned for header + hero only.
 */
function qp_holdings_output_critical_css(): void {
    static $printed = false;

    if ( $printed || is_admin() ) {
        return;
    }

    $critical_path = get_template_directory() . '/assets/critical.css';
    if ( ! file_exists( $critical_path ) ) {
        return;
    }

    $css = file_get_contents( $critical_path );
    if ( false === $css ) {
        return;
    }

    // Strip block comments + collapse whitespace once at runtime.
    $css = preg_replace( '~/\*(?!!)[\s\S]*?\*/~', '', $css );
    $css = preg_replace( '/\s+/', ' ', $css );
    $css = trim( (string) $css );

    // Inline the @font-face declarations too. Keeping noto-sans.css as a
    // separate <link> made it a render-blocking request in the critical chain
    // (~200 ms under mobile throttling) that delayed the hero <h1> LCP. The
    // woff2 files are already preloaded, so inlining the tiny @font-face block
    // here lets them hydrate with no extra round-trip. Relative url()s are
    // rewritten to absolute theme URLs since this lives in <head>, not the
    // fonts/ directory.
    $font_path = get_template_directory() . '/assets/fonts/noto-sans.css';
    if ( file_exists( $font_path ) ) {
        $font_css = (string) file_get_contents( $font_path );
        if ( '' !== $font_css ) {
            $fonts_uri = get_template_directory_uri() . '/assets/fonts/';
            $font_css  = preg_replace( '~/\*(?!!)[\s\S]*?\*/~', '', $font_css );
            $font_css  = preg_replace_callback(
                "~url\\(\\s*'([^']+\\.woff2)'\\s*\\)~",
                static function ( $m ) use ( $fonts_uri ) {
                    return "url('" . esc_url( $fonts_uri . $m[1] ) . "')";
                },
                $font_css
            );
            $font_css = preg_replace( '/\s+/', ' ', (string) $font_css );
            $css     .= ' ' . trim( (string) $font_css );
        }
    }

    echo '<style id="qp-critical-css">' . $css . '</style>' . "\n";
    $printed = true;
}
add_action( 'wp_head', 'qp_holdings_output_critical_css', 1 );

/**
 * Sticky-header + mobile-menu JS used by the legacy Flatsome header.
 * Registered as inline so it doesn't ship on modern pages.
 */
function qp_holdings_enqueue_legacy_inline_scripts(): void {
    $sticky_js = <<<'JS'
(function(){
  function init(){
    var hdr = document.getElementById('header');
    if (!hdr) return;
    var threshold = 80;
    var hadTransparent = hdr.classList.contains('transparent');
    function onScroll(){
      var y = window.scrollY || window.pageYOffset || 0;
      if (y > threshold) {
        hdr.classList.add('stuck');
        if (hadTransparent) hdr.classList.remove('transparent');
      } else {
        hdr.classList.remove('stuck');
        if (hadTransparent) hdr.classList.add('transparent');
      }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
JS;

    $mobile_js = <<<'JS'
(function(){
  function init(){
    var body = document.body;
    var menu = document.getElementById('main-menu');
    var toggle = document.querySelector('.qp-mobile-menu-toggle');
    if (!body || !menu || !toggle) return;
    var closeEls = document.querySelectorAll('[data-qp-mobile-menu-close]');
    var submenuToggles = menu.querySelectorAll('.qp-mobile-menu__submenu-toggle');
    function setOpen(open){
      body.classList.toggle('qp-mobile-menu-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    }
    toggle.addEventListener('click', function(e){ e.preventDefault(); setOpen(!body.classList.contains('qp-mobile-menu-open')); });
    closeEls.forEach(function(el){ el.addEventListener('click', function(){ setOpen(false); }); });
    submenuToggles.forEach(function(btn){
      btn.addEventListener('click', function(){
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      });
    });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') setOpen(false); });
    window.addEventListener('resize', function(){ if (window.innerWidth >= 850) setOpen(false); });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
JS;

    wp_register_script( 'qp-legacy-sticky', '', array(), '1.0.0', true );
    wp_enqueue_script( 'qp-legacy-sticky' );
    wp_add_inline_script( 'qp-legacy-sticky', $sticky_js );

    wp_register_script( 'qp-legacy-mobile', '', array(), '1.0.0', true );
    wp_enqueue_script( 'qp-legacy-mobile' );
    wp_add_inline_script( 'qp-legacy-mobile', $mobile_js );
}

/**
 * Return true when the current request is a legacy exported page that still
 * needs to keep its original Flatsome look.
 */
function qp_holdings_is_legacy_template(): bool {
    if ( ! is_page() ) {
        return false;
    }

    $legacy_templates = array(
        'page-tuyen-dung.php',
    );

    $tpl = (string) get_page_template_slug( get_queried_object_id() );
    if ( $tpl && in_array( $tpl, $legacy_templates, true ) ) {
        return true;
    }

    // Fallback: detect by page slug (in case Template Name wasn't synced).
    $legacy_slugs = array(
        'tuyen-dung',
    );
    $slug = (string) get_post_field( 'post_name', get_queried_object_id() );

    return in_array( $slug, $legacy_slugs, true );
}

function qp_holdings_enqueue_root_style(): void {
    $version  = wp_get_theme()->get( 'Version' );
    $is_legacy = qp_holdings_is_legacy_template();
    $needs_swiper = is_front_page() || is_page_template( 'page-gioi-thieu.php' ) || is_page_template( 'page-tintuc.php' ) || is_singular( 'qp_project' );
    $needs_chartjs = is_page_template( 'page-thong-tin-co-phieu.php' );
    $needs_intl_tel_input = is_page_template( 'page-lien-he.php' ) || is_singular( 'qp_project' ) || is_singular( 'qp_job' );

    // ── Always-loaded: new clean header/footer styling ────────────────────
    if ( $needs_swiper ) {
        wp_enqueue_style( 'qp-swiper', 'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css', array(), '12' );
    }

    if ( $needs_intl_tel_input ) {
        wp_enqueue_style( 'qp-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/css/intlTelInput.css', array(), '24.5.0' );
    }

    // Noto Sans (primary brand font per Figma design system). Self-hosted from
    // assets/fonts/ to avoid the ~250 KB cascade off fonts.googleapis.com →
    // fonts.gstatic.com that was triggering FOUT-driven layout shifts.
    // The @font-face block is inlined into <head> by
    // qp_holdings_output_critical_css() instead of being enqueued as a separate
    // render-blocking <link>, so the preloaded woff2 subsets hydrate the hero
    // <h1> (the LCP element) without an extra round-trip in the critical chain.

    // Main site stylesheet (clean export from /html). Used by the new header,
    // footer and modern page sections; legacy pages use scoped overrides so
    // its globals don't break the Flatsome body.
    qp_holdings_enqueue_style_asset( 'qp-site', 'assets/site-styles.css' );

    if ( $is_legacy ) {
        // ── Legacy exported sub-pages keep their Flatsome body styling ────
        // Flatsome stack is loaded AFTER qp-site so its body/typography wins
        // inside the body content area. The new header/footer (`.site-header`,
        // `.site-footer`) are uniquely scoped by class names and remain unaffected.
        qp_holdings_enqueue_style_asset( 'qp-fa-svg', 'assets/index_files/svg-with-js.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-spacing', 'assets/index_files/spacing-utilities.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-parent', 'assets/index_files/style.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-fa', 'assets/index_files/all.min.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-flatsome', 'assets/index_files/flatsome.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-flatsome-style', 'assets/index_files/style(1).css', array( 'qp-flatsome' ) );
        qp_holdings_enqueue_style_asset( 'qp-googlefonts', 'assets/index_files/css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-fa-shims', 'assets/index_files/v4-shims.css', array( 'qp-site' ) );
        qp_holdings_enqueue_style_asset( 'qp-dropdown-nav', 'assets/index_files/dropdown-nav.css', array( 'qp-site' ) );

        $shared_styles = get_template_directory() . '/assets/index_files/qp-shared-styles.css';
        if ( file_exists( $shared_styles ) ) {
            wp_enqueue_style(
                'qp-shared-page',
                get_template_directory_uri() . '/assets/index_files/qp-shared-styles.css',
                array( 'qp-flatsome-style' ),
                filemtime( $shared_styles )
            );
        }

        // Core jQuery (legacy inline scripts depend on it).
        wp_enqueue_script( 'jquery' );
    }

    // Dynamic content styling (CPT listings, single, filters, pagination).
    $dynamic_css_path = get_template_directory() . '/assets/index_files/qp-dynamic.css';
    if ( file_exists( $dynamic_css_path ) ) {
        wp_enqueue_style(
            'qp-dynamic',
            get_template_directory_uri() . '/assets/index_files/qp-dynamic.css',
            array( 'qp-site' ),
            filemtime( $dynamic_css_path )
        );
    }

    // Always-loaded theme stylesheet for WordPress-specific overrides.
    // Prefer the pre-built `.min.css` if `bin/build-assets.mjs` produced one.
    $theme_css_min = get_stylesheet_directory() . '/style.min.css';
    if ( file_exists( $theme_css_min ) ) {
        $theme_css_url  = get_stylesheet_directory_uri() . '/style.min.css';
        $theme_css_path = $theme_css_min;
    } else {
        $theme_css_url  = get_stylesheet_uri();
        $theme_css_path = get_stylesheet_directory() . '/style.css';
    }
    $theme_css_ver = file_exists( $theme_css_path ) ? (string) filemtime( $theme_css_path ) : $version;
    $theme_deps    = $is_legacy ? array( 'qp-site', 'qp-flatsome-style' ) : array( 'qp-site' );
    wp_enqueue_style( 'qp-holdings-theme', $theme_css_url, $theme_deps, $theme_css_ver );

    if ( $needs_swiper ) {
        wp_enqueue_script( 'qp-swiper', 'https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js', array(), '12', true );
    }

    qp_holdings_enqueue_script_asset( 'qp-site', 'assets/site-script.js', $needs_swiper ? array( 'qp-swiper' ) : array() );

    wp_localize_script( 'qp-site', 'qpFormI18n', array(
        'required'       => qp_holdings_t( 'err_required' ),
        'selectRequired' => qp_holdings_t( 'err_select_required' ),
        'nameRequired'   => qp_holdings_t( 'err_name_required' ),
        'emailRequired'  => qp_holdings_t( 'err_email_required' ),
        'emailInvalid'   => qp_holdings_t( 'err_email_invalid' ),
        'phoneRequired'  => qp_holdings_t( 'err_phone_required' ),
        'commentRequired' => qp_holdings_t( 'err_comment_required' ),
    ) );

    if ( $needs_intl_tel_input ) {
        wp_enqueue_script( 'qp-intl-tel-input', 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/intlTelInput.min.js', array(), '24.5.0', true );
        wp_localize_script( 'qp-intl-tel-input', 'qpIntlTelInput', array(
            'utilsUrl' => 'https://cdn.jsdelivr.net/npm/intl-tel-input@24.5.0/build/js/utils.js',
        ) );
    }

    if ( $needs_chartjs ) {
        wp_enqueue_script(
            'qp-chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js',
            array(),
            '4.4.6',
            true
        );
        qp_holdings_enqueue_script_asset( 'qp-investor-charts', 'assets/site/investor-charts.js', array( 'qp-chartjs' ) );
    }
}
add_action( 'wp_enqueue_scripts', 'qp_holdings_enqueue_root_style' );


/**
 * Contact Form 7 registers its CSS + four JS files (hooks, i18n, swv, cf7) on
 * every front-end page, even ones with no `[contact-form-7]` shortcode. The
 * QP contact form is a custom POST handler (qp_contact_nonce), not a CF7 form,
 * so the only page that actually embeds a CF7 form is the contact template.
 * Everywhere else those four render-blocking scripts just pad the critical
 * request chain and depress the simulated LCP. Dequeue them unless the current
 * page genuinely needs CF7.
 */
function qp_holdings_maybe_dequeue_cf7_assets(): void {
    if ( is_admin() ) {
        return;
    }

    // Pages that embed a real CF7 form. Add template slugs here if a CF7
    // shortcode is ever placed on another page.
    $needs_cf7 = is_page_template( 'page-lien-he.php' );

    /**
     * Allow other code (e.g. a page that adds a CF7 shortcode via the editor)
     * to force CF7 assets back on.
     */
    if ( apply_filters( 'qp_holdings_needs_cf7', $needs_cf7 ) ) {
        return;
    }

    foreach ( array( 'contact-form-7', 'wpcf7-recaptcha', 'swv' ) as $handle ) {
        wp_dequeue_script( $handle );
        wp_dequeue_style( $handle );
    }
}
add_action( 'wp_enqueue_scripts', 'qp_holdings_maybe_dequeue_cf7_assets', 100 );


/**
 * Add defer to non-critical frontend scripts we control.
 */
function qp_holdings_defer_scripts( string $tag, string $handle, string $src ): string {
    $defer_handles = array( 'qp-swiper', 'qp-site', 'qp-chartjs', 'qp-investor-charts', 'qp-intl-tel-input' );
    if ( in_array( $handle, $defer_handles, true ) && false === strpos( $tag, ' defer' ) ) {
        return str_replace( '<script ', '<script defer ', $tag );
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'qp_holdings_defer_scripts', 10, 3 );

/**
 * Make non-critical stylesheets load asynchronously so they don't block first paint.
 * Critical above-the-fold styles are inlined via qp_holdings_output_critical_css(),
 * so the full site-styles.css can be deferred too.
 *
 * `qp-noto-sans` is intentionally NOT in this list: the @font-face rules
 * have to be parsed before the preloaded woff2 files can hydrate the page.
 * Deferring this CSS would defeat the whole self-hosted-font win.
 */
function qp_holdings_defer_noncritical_css( string $tag, string $handle, string $href, string $media ): string {
    $defer_handles = array( 'qp-site', 'qp-holdings-theme', 'qp-dynamic', 'qp-swiper', 'qp-intl-tel-input', 'contact-form-7' );
    if ( in_array( $handle, $defer_handles, true ) ) {
        $tag = str_replace( "media='all'", "media='print' onload=\"this.media='all'\"", $tag );
        if ( false === strpos( $tag, 'onload' ) ) {
            $tag = str_replace( '/>', "media='print' onload=\"this.media='all'\" />", $tag );
        }
        $tag .= '<noscript>' . str_replace( array( "media='print' onload=\"this.media='all'\"", "media=\"print\" onload=\"this.media='all'\"" ), "media='all'", $tag ) . '</noscript>';
    }
    return $tag;
}
add_filter( 'style_loader_tag', 'qp_holdings_defer_noncritical_css', 10, 4 );

/**
 * Legacy exported templates include duplicate stylesheet tags after get_header().
 * Keep the canonical wp_enqueue_* output and strip only those known duplicate tags.
 */
function qp_holdings_strip_legacy_duplicate_assets( string $html ): string {
    // Legacy exported sub-pages need the Flatsome stylesheet tags inline — keep them.
    if ( function_exists( 'qp_holdings_is_legacy_template' ) && qp_holdings_is_legacy_template() ) {
        $html = qp_holdings_inject_missing_seo_markup( $html );
        if ( function_exists( 'qp_holdings_translate_static_html' ) ) {
            $html = qp_holdings_translate_static_html( $html );
        }
        return $html;
    }

    $legacy_ids = array(
        'font-awesome-svg-styles-css',
        'spacing-utilities-css',
        'parent-style-css',
        'font-awesome-css',
        'font-awesome-official-css',
        'flatsome-main-css',
        'flatsome-style-css',
        'flatsome-googlefonts-css',
        'font-awesome-official-v4shim-css',
    );

    foreach ( $legacy_ids as $id ) {
        $html = preg_replace( '/<link\b(?=[^>]*\bid=["\']' . preg_quote( $id, '/' ) . '["\'])[^>]*>\s*/i', '', $html );
    }

    // Remove local jQuery copies from the static export; WordPress core jQuery is enqueued once.
    $html = preg_replace( '/<script\b(?=[^>]*\bsrc=["\'][^"\']*\/assets\/index_files\/jquery(?:-migrate)?\.min\.js["\'])[^>]*>\s*<\/script>\s*/i', '', $html );

    // Exported templates preload logo bitmaps, but the active header/footer use inline SVG logos.
    $html = preg_replace( '/<link\b(?=[^>]*\brel=["\']preload["\'])(?=[^>]*\bas=["\']image["\'])(?=[^>]*logo-qp-group)[^>]*>\s*/i', '', $html );

    $html = qp_holdings_inject_missing_seo_markup( $html );

    if ( function_exists( 'qp_holdings_translate_static_html' ) ) {
        $html = qp_holdings_translate_static_html( $html );
    }

    return $html;
}

function qp_holdings_start_legacy_asset_buffer(): void {
    if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || is_feed() || is_trackback() || is_embed() || is_robots() ) {
        return;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    if ( false !== stripos( $request_uri, '/robots.txt' ) ) {
        return;
    }

    ob_start( 'qp_holdings_strip_legacy_duplicate_assets' );
}
add_action( 'template_redirect', 'qp_holdings_start_legacy_asset_buffer', 0 );

/**
 * Detect common SEO plugins and avoid duplicate meta/schema output when active.
 */
function qp_holdings_seo_plugin_active(): bool {
    return defined( 'RANK_MATH_VERSION' )
        || defined( 'WPSEO_VERSION' )
        || defined( 'AIOSEO_VERSION' )
        || class_exists( 'RankMath' )
        || class_exists( 'WPSEO_Frontend' )
        || function_exists( 'aioseo' );
}

/**
 * Plugins that reliably own the full document head. Rank Math is intentionally
 * not included here because, in the current setup, it outputs canonicals but no
 * descriptions/Open Graph until configured per site.
 */
function qp_holdings_full_seo_plugin_active(): bool {
    return defined( 'WPSEO_VERSION' )
        || defined( 'AIOSEO_VERSION' )
        || class_exists( 'WPSEO_Frontend' )
        || function_exists( 'aioseo' );
}

function qp_holdings_clean_meta_text( string $text, int $words = 32 ): string {
    $text = wp_strip_all_tags( strip_shortcodes( $text ) );
    $text = preg_replace( '/\s+/u', ' ', $text ) ?: '';
    $text = trim( $text );

    return wp_trim_words( $text, $words, '…' );
}

/**
 * Per-page meta description defaults keyed by page slug (vi/en/zh).
 * Extracted from qp_holdings_meta_description() so the admin SEO meta box can
 * preview the exact default each page will use when left blank.
 *
 * @return array<string,array<string,string>>
 */
function qp_holdings_seo_page_descriptions(): array {
    return array(
        'gioi-thieu' => array(
            'vi' => 'Tầm nhìn, sứ mệnh và năng lực cốt lõi của QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'Vision, mission, and core capabilities of QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
            'zh' => 'QP Holdings 的愿景、使命与核心能力——以高端企业（luxury corporate）为定位的投资与房地产集团，为股东、合作伙伴和社区创造可持续价值。',
        ),
        'about' => array(
            'vi' => 'Tầm nhìn, sứ mệnh và năng lực cốt lõi của QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'Vision, mission, and core capabilities of QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
            'zh' => 'QP Holdings 的愿景、使命与核心能力——以高端企业（luxury corporate）为定位的投资与房地产集团，为股东、合作伙伴和社区创造可持续价值。',
        ),
        'du-an' => array(
            'vi' => 'Hệ sinh thái dự án bất động sản, khu đô thị và khu công nghiệp của QP Holdings - phát triển theo chuẩn luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'QP Holdings’ real estate, urban, and industrial project portfolio - developed to a luxury corporate standard, creating sustainable value for shareholders, partners, and communities.',
            'zh' => 'QP Holdings 的房地产、城市与工业项目生态——以高端企业（luxury corporate）标准开发，为股东、合作伙伴和社区创造可持续价值。',
        ),
        'projects' => array(
            'vi' => 'Hệ sinh thái dự án bất động sản, khu đô thị và khu công nghiệp của QP Holdings - phát triển theo chuẩn luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'QP Holdings’ real estate, urban, and industrial project portfolio - developed to a luxury corporate standard, creating sustainable value for shareholders, partners, and communities.',
            'zh' => 'QP Holdings 的房地产、城市与工业项目生态——以高端企业（luxury corporate）标准开发，为股东、合作伙伴和社区创造可持续价值。',
        ),
        'tintuc' => array(
            'vi' => 'Tin tức, hoạt động doanh nghiệp và quan hệ nhà đầu tư của QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông và cộng đồng.',
            'en' => 'News, corporate activities, and investor relations from QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders and communities.',
            'zh' => 'QP Holdings 的新闻、企业动态与投资者关系——以高端企业（luxury corporate）为定位的投资与房地产集团，为股东与社区创造可持续价值。',
        ),
        'news' => array(
            'vi' => 'Tin tức, hoạt động doanh nghiệp và quan hệ nhà đầu tư của QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông và cộng đồng.',
            'en' => 'News, corporate activities, and investor relations from QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders and communities.',
            'zh' => 'QP Holdings 的新闻、企业动态与投资者关系——以高端企业（luxury corporate）为定位的投资与房地产集团，为股东与社区创造可持续价值。',
        ),
        'tuyen-dung' => array(
            'vi' => 'Cơ hội nghề nghiệp tại QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, cùng kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'Careers at QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
            'zh' => '加入 QP Holdings——以高端企业（luxury corporate）为定位的投资与房地产集团，与我们一同为股东、合作伙伴和社区创造可持续价值。',
        ),
        'careers' => array(
            'vi' => 'Cơ hội nghề nghiệp tại QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, cùng kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'en' => 'Careers at QP Holdings - an investment and real estate group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
            'zh' => '加入 QP Holdings——以高端企业（luxury corporate）为定位的投资与房地产集团，与我们一同为股东、合作伙伴和社区创造可持续价值。',
        ),
		'cau-hoi-thuong-gap' => array(
			'vi' => 'Giải đáp các câu hỏi thường gặp về lĩnh vực hoạt động, tuyển dụng, tư vấn và quy trình hợp tác với QP Holdings.',
			'en' => 'Answers to common questions about QP Holdings operations, careers, consultation, and collaboration process.',
			'zh' => '解答有关 QP Holdings 业务领域、招聘、咨询和合作流程的常见问题。',
		),
		'faq' => array(
			'vi' => 'Giải đáp các câu hỏi thường gặp về lĩnh vực hoạt động, tuyển dụng, tư vấn và quy trình hợp tác với QP Holdings.',
			'en' => 'Answers to common questions about QP Holdings operations, careers, consultation, and collaboration process.',
			'zh' => '解答有关 QP Holdings 业务领域、招聘、咨询和合作流程的常见问题。',
		),
        'lien-he' => array(
            'vi' => 'Kết nối với QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, sẵn sàng đồng hành kiến tạo giá trị bền vững cùng cổ đông, đối tác và cộng đồng.',
            'en' => 'Contact QP Holdings - an investment and real estate group with a luxury corporate orientation, partnering with shareholders, partners, and communities to create sustainable value.',
            'zh' => '联系 QP Holdings——以高端企业（luxury corporate）为定位的投资与房地产集团，与股东、合作伙伴和社区携手创造可持续价值。',
        ),
        'contact' => array(
            'vi' => 'Kết nối với QP Holdings - tập đoàn đầu tư & bất động sản theo định hướng luxury corporate, sẵn sàng đồng hành kiến tạo giá trị bền vững cùng cổ đông, đối tác và cộng đồng.',
            'en' => 'Contact QP Holdings - an investment and real estate group with a luxury corporate orientation, partnering with shareholders, partners, and communities to create sustainable value.',
            'zh' => '联系 QP Holdings——以高端企业（luxury corporate）为定位的投资与房地产集团，与股东、合作伙伴和社区携手创造可持续价值。',
        ),
        'thong-tin-co-phieu' => array(
            'vi' => 'Thông tin cổ phiếu, dữ liệu giao dịch và tài liệu công bố của QP Holdings - tập đoàn luxury corporate cam kết kiến tạo giá trị bền vững cho cổ đông và nhà đầu tư.',
            'en' => 'Stock information, trading data, and disclosure documents from QP Holdings - a luxury corporate group committed to creating sustainable value for shareholders and investors.',
            'zh' => 'QP Holdings 的股票信息、交易数据与披露文件——高端企业（luxury corporate）集团，致力于为股东与投资者创造可持续价值。',
        ),
        'stock-information' => array(
            'vi' => 'Thông tin cổ phiếu, dữ liệu giao dịch và tài liệu công bố của QP Holdings - tập đoàn luxury corporate cam kết kiến tạo giá trị bền vững cho cổ đông và nhà đầu tư.',
            'en' => 'Stock information, trading data, and disclosure documents from QP Holdings - a luxury corporate group committed to creating sustainable value for shareholders and investors.',
            'zh' => 'QP Holdings 的股票信息、交易数据与披露文件——高端企业（luxury corporate）集团，致力于为股东与投资者创造可持续价值。',
        ),
        'dieu-khoan-su-dung' => array(
            'vi' => 'Điều khoản sử dụng website QP Holdings - quy định về truy cập, sử dụng dịch vụ và chia sẻ thông tin trên hệ sinh thái luxury corporate của tập đoàn.',
            'en' => 'Terms of use for the QP Holdings website - rules on access, service use, and information sharing across the group’s luxury corporate ecosystem.',
            'zh' => 'QP Holdings 网站使用条款——关于访问、服务使用与信息共享的规定，适用于集团高端企业（luxury corporate）生态。',
        ),
        'terms-of-use' => array(
            'vi' => 'Điều khoản sử dụng website QP Holdings - quy định về truy cập, sử dụng dịch vụ và chia sẻ thông tin trên hệ sinh thái luxury corporate của tập đoàn.',
            'en' => 'Terms of use for the QP Holdings website - rules on access, service use, and information sharing across the group’s luxury corporate ecosystem.',
            'zh' => 'QP Holdings 网站使用条款——关于访问、服务使用与信息共享的规定，适用于集团高端企业（luxury corporate）生态。',
        ),
        'chinh-sach-bao-mat' => array(
            'vi' => 'Chính sách bảo mật của QP Holdings - cam kết thu thập, xử lý và bảo vệ dữ liệu cá nhân theo chuẩn luxury corporate, đảm bảo quyền lợi của khách hàng và đối tác.',
            'en' => 'QP Holdings privacy policy - commitment to collecting, processing, and protecting personal data to a luxury corporate standard, safeguarding the rights of customers and partners.',
            'zh' => 'QP Holdings 隐私政策——以高端企业（luxury corporate）标准承诺收集、处理与保护个人数据，保障客户与合作伙伴的权益。',
        ),
        'privacy-policy' => array(
            'vi' => 'Chính sách bảo mật của QP Holdings - cam kết thu thập, xử lý và bảo vệ dữ liệu cá nhân theo chuẩn luxury corporate, đảm bảo quyền lợi của khách hàng và đối tác.',
            'en' => 'QP Holdings privacy policy - commitment to collecting, processing, and protecting personal data to a luxury corporate standard, safeguarding the rights of customers and partners.',
            'zh' => 'QP Holdings 隐私政策——以高端企业（luxury corporate）标准承诺收集、处理与保护个人数据，保障客户与合作伙伴的权益。',
        ),
    );
}

function qp_holdings_meta_description(): string {
    $lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

    // Per-page manual override from the "Tối ưu SEO" meta box wins over defaults.
    if ( is_singular() ) {
        $override = trim( (string) get_post_meta( get_queried_object_id(), '_qp_seo_description', true ) );
        if ( '' !== $override ) {
            return $override;
        }
    }

    $fallbacks = array(
        'vi' => array(
            'front'   => 'QP Holdings là tập đoàn đầu tư và phát triển bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
            'default' => 'QP Holdings - Tập đoàn đầu tư và phát triển bất động sản theo định hướng luxury corporate, kiến tạo giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
        ),
        'en' => array(
            'front'   => 'QP Holdings is an investment and real estate development group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
            'default' => 'QP Holdings - An investment and real estate development group with a luxury corporate orientation, creating sustainable value for shareholders, partners, and communities.',
        ),
        'zh' => array(
            'front'   => 'QP Holdings 是一家以高端企业（luxury corporate）为定位的投资与房地产开发集团，为股东、合作伙伴与社区持续创造可持续价值。',
            'default' => 'QP Holdings - 以高端企业（luxury corporate）为定位的投资与房地产开发集团，为股东、合作伙伴与社区创造可持续价值。',
        ),
    );

    if ( is_front_page() ) {
        return $fallbacks[ $lang ]['front'] ?? $fallbacks['vi']['front'];
    }

    if ( is_page() ) {
        $slug = (string) get_post_field( 'post_name', get_queried_object_id() );
        $descriptions = qp_holdings_seo_page_descriptions();

        if ( isset( $descriptions[ $slug ] ) ) {
            return $descriptions[ $slug ][ $lang ] ?? $descriptions[ $slug ]['vi'];
        }
    }

    if ( is_singular() ) {
        $post = get_post();
        if ( $post instanceof WP_Post ) {
            $source = has_excerpt( $post ) ? (string) get_the_excerpt( $post ) : (string) $post->post_content;
            $desc   = qp_holdings_clean_meta_text( $source );
            if ( $desc ) {
                return $desc;
            }
        }
    }

    $tagline = get_bloginfo( 'description' );
    return $tagline ? qp_holdings_clean_meta_text( $tagline ) : ( $fallbacks[ $lang ]['default'] ?? $fallbacks['vi']['default'] );
}

function qp_holdings_canonical_url(): string {
    if ( is_singular() ) {
        return get_permalink();
    }

    if ( is_front_page() ) {
        return home_url( '/' );
    }

    if ( is_tax() || is_category() || is_tag() ) {
        $term_link = get_term_link( get_queried_object() );
        if ( ! is_wp_error( $term_link ) ) {
            return $term_link;
        }
    }

    return get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
}

function qp_holdings_seo_image_url(): string {
    if ( is_singular() && has_post_thumbnail() ) {
        $image = wp_get_attachment_image_url( get_post_thumbnail_id(), 'full' );
        if ( $image ) {
            return $image;
        }
    }

    return get_template_directory_uri() . '/assets/index_files/BG-2.jpg';
}

function qp_holdings_disable_core_canonical_when_needed(): void {
    if ( ! qp_holdings_seo_plugin_active() ) {
        remove_action( 'wp_head', 'rel_canonical' );
    }
}
add_action( 'wp', 'qp_holdings_disable_core_canonical_when_needed' );

function qp_holdings_fix_rank_math_canonical( string $url ): string {
    if ( is_singular() ) {
        return get_permalink();
    }
    return $url;
}
add_filter( 'rank_math/frontend/canonical', 'qp_holdings_fix_rank_math_canonical', 20 );

function qp_holdings_build_seo_fallback_markup( bool $include_canonical, bool $include_description, bool $include_og, bool $include_twitter, bool $include_schema, bool $include_keywords = true ): string {
    $title       = wp_get_document_title();
    $description = qp_holdings_meta_description();
    $canonical   = qp_holdings_canonical_url();
    $image       = qp_holdings_seo_image_url();
    $type        = is_singular( array( 'post', 'qp_news' ) ) ? 'article' : 'website';
    $keywords    = function_exists( 'qp_holdings_seo_effective_keywords' ) ? qp_holdings_seo_effective_keywords() : '';

    $markup = "\n<!-- QP Holdings SEO fallback -->\n";
    if ( $include_canonical ) {
        $markup .= sprintf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );
    }
    if ( $include_description ) {
        $markup .= sprintf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
    }
    if ( $include_keywords && '' !== $keywords ) {
        $markup .= sprintf( '<meta name="keywords" content="%s">' . "\n", esc_attr( $keywords ) );
    }
    if ( $include_og ) {
        $markup .= sprintf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( function_exists( 'qp_holdings_language_locale' ) ? qp_holdings_language_locale( 'og' ) : 'vi_VN' ) );
        $markup .= sprintf( '<meta property="og:type" content="%s">' . "\n", esc_attr( $type ) );
        $markup .= sprintf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
        $markup .= sprintf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
        $markup .= sprintf( '<meta property="og:url" content="%s">' . "\n", esc_url( $canonical ) );
        $markup .= sprintf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
        $markup .= sprintf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image ) );
    }
    if ( $include_twitter ) {
        $markup .= sprintf( '<meta name="twitter:card" content="summary_large_image">' . "\n" );
        $markup .= sprintf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
        $markup .= sprintf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
        $markup .= sprintf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image ) );
    }

    if ( ! $include_schema ) {
        return $markup;
    }

    $schema_ctx = array(
        'title'       => $title,
        'description' => $description,
        'canonical'   => $canonical,
        'image'       => $image,
        'is_article'  => is_singular( array( 'post', 'qp_news' ) ),
    );

    if ( function_exists( 'qp_holdings_seo_schema_graph' ) ) {
        // Rich graph: Organization + Corporation (HKT), WebSite, BreadcrumbList, WebPage/NewsArticle.
        $schema_graph = qp_holdings_seo_schema_graph( $schema_ctx );
    } else {
        // Minimal fallback if the SEO module is unavailable.
        $schema_graph = array(
            array(
                '@type' => 'Organization',
                '@id'   => home_url( '/#organization' ),
                'name'  => 'QP Holdings',
                'url'   => home_url( '/' ),
                'logo'  => array(
                    '@type' => 'ImageObject',
                    'url'   => ( function_exists( 'has_site_icon' ) && has_site_icon() ) ? get_site_icon_url( 192 ) : get_template_directory_uri() . '/assets/index_files/favicon-192.png',
                ),
            ),
            array(
                '@type'       => 'WebSite',
                '@id'         => home_url( '/#website' ),
                'url'         => home_url( '/' ),
                'name'        => get_bloginfo( 'name' ),
                'description' => $description,
                'publisher'   => array( '@id' => home_url( '/#organization' ) ),
                'inLanguage'  => function_exists( 'qp_holdings_language_locale' ) ? qp_holdings_language_locale( 'schema' ) : 'vi-VN',
            ),
            array(
                '@type'       => $schema_ctx['is_article'] ? 'NewsArticle' : 'WebPage',
                '@id'         => $canonical . '#webpage',
                'url'         => $canonical,
                'name'        => $title,
                'headline'    => $title,
                'description' => $description,
                'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
                'inLanguage'  => function_exists( 'qp_holdings_language_locale' ) ? qp_holdings_language_locale( 'schema' ) : 'vi-VN',
            ),
        );

        if ( is_singular() ) {
            $schema_graph[2]['datePublished'] = get_the_date( DATE_W3C );
            $schema_graph[2]['dateModified']  = get_the_modified_date( DATE_W3C );
            $schema_graph[2]['image']         = array( $image );
        }
    }

    $markup .= '<script type="application/ld+json">' . wp_json_encode(
        array(
            '@context' => 'https://schema.org',
            '@graph'   => $schema_graph,
        ),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    ) . '</script>' . "\n";

    return $markup;
}

function qp_holdings_head_has( string $html, string $pattern ): bool {
    return 1 === preg_match( $pattern, $html );
}

function qp_holdings_inject_missing_seo_markup( string $html ): string {
    if ( is_404() || false === stripos( $html, '</head>' ) ) {
        return $html;
    }

    $include_canonical   = ! qp_holdings_head_has( $html, '/<link\b[^>]*\brel=["\']canonical["\'][^>]*>/i' );
    $include_description = ! qp_holdings_head_has( $html, '/<meta\b[^>]*\bname=["\']description["\'][^>]*>/i' );
    $include_og          = ! qp_holdings_head_has( $html, '/<meta\b[^>]*\bproperty=["\']og:title["\'][^>]*>/i' );
    $include_twitter     = ! qp_holdings_head_has( $html, '/<meta\b[^>]*\bname=["\']twitter:card["\'][^>]*>/i' );
    $include_schema      = ! qp_holdings_head_has( $html, '/<script\b[^>]*\btype=["\']application\/ld\+json["\'][^>]*>/i' );
    $include_keywords    = ! qp_holdings_head_has( $html, '/<meta\b[^>]*\bname=["\']keywords["\'][^>]*>/i' );

    if ( ! $include_canonical && ! $include_description && ! $include_og && ! $include_twitter && ! $include_schema && ! $include_keywords ) {
        return $html;
    }

    $markup = qp_holdings_build_seo_fallback_markup( $include_canonical, $include_description, $include_og, $include_twitter, $include_schema, $include_keywords );

    return preg_replace( '/<\/head>/i', $markup . '</head>', $html, 1 ) ?: $html;
}

function qp_holdings_find_or_create_page( string $title, string $slug, int $parent_id = 0 ): int {
    $path = $parent_id ? get_post_field( 'post_name', $parent_id ) . '/' . $slug : $slug;
    $existing = get_page_by_path( $path, OBJECT, 'page' );

    if ( $existing instanceof WP_Post ) {
        return (int) $existing->ID;
    }

    $page_id = wp_insert_post(
        array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_type'    => 'page',
            'post_status'  => 'publish',
            'post_parent'  => $parent_id,
            'post_content' => '',
        ),
        true
    );

    return is_wp_error( $page_id ) ? 0 : (int) $page_id;
}

function qp_holdings_create_starter_pages(): void {
    if ( ! function_exists( 'qp_holdings_page_definitions' ) ) {
        $home_id = qp_holdings_find_or_create_page( 'Trang Chủ', 'trang-chu' );
        if ( $home_id ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $home_id );
        }
        return;
    }

    $defs     = qp_holdings_page_definitions();
    $templates = function_exists( 'qp_holdings_page_template_map' ) ? qp_holdings_page_template_map() : array();
    $page_ids = array();

    foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
        foreach ( $defs as $key => $def ) {
            if ( ! empty( $def['parent'] ) ) {
                continue;
            }
            $page_ids[ $key ][ $lang ] = qp_holdings_find_or_create_page( $def[ $lang ]['title'], qp_holdings_page_storage_slug( $key, $lang ) );
            if ( $page_ids[ $key ][ $lang ] && function_exists( 'pll_set_post_language' ) ) {
                pll_set_post_language( $page_ids[ $key ][ $lang ], $lang );
            }
            if ( $page_ids[ $key ][ $lang ] && ! empty( $templates[ $key ] ) ) {
                update_post_meta( $page_ids[ $key ][ $lang ], '_wp_page_template', $templates[ $key ] );
            }
        }
    }

    foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
        foreach ( $defs as $key => $def ) {
            if ( empty( $def['parent'] ) ) {
                continue;
            }
            $parent_id = $page_ids[ $def['parent'] ][ $lang ] ?? 0;
            $page_ids[ $key ][ $lang ] = qp_holdings_find_or_create_page( $def[ $lang ]['title'], qp_holdings_page_storage_slug( $key, $lang ), $parent_id );
            if ( $page_ids[ $key ][ $lang ] && function_exists( 'pll_set_post_language' ) ) {
                pll_set_post_language( $page_ids[ $key ][ $lang ], $lang );
            }
            if ( $page_ids[ $key ][ $lang ] && ! empty( $templates[ $key ] ) ) {
                update_post_meta( $page_ids[ $key ][ $lang ], '_wp_page_template', $templates[ $key ] );
            }
        }
    }

    if ( function_exists( 'pll_save_post_translations' ) ) {
        foreach ( $page_ids as $translations ) {
            $translations = array_filter( array_map( 'intval', $translations ) );
            if ( count( $translations ) > 1 ) {
                pll_save_post_translations( $translations );
            }
        }
    }

    $home_id = $page_ids['home']['vi'] ?? 0;
    if ( $home_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $home_id );
    }
}
add_action( 'after_switch_theme', 'qp_holdings_create_starter_pages' );

function qp_holdings_multilingual_pages_missing(): bool {
    if ( ! function_exists( 'qp_holdings_page_definitions' ) ) {
        return false;
    }

    $defs = qp_holdings_page_definitions();
    foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
        foreach ( $defs as $key => $def ) {
            $path = qp_holdings_page_storage_slug( $key, $lang );
            if ( ! $path ) {
                continue;
            }
            if ( ! empty( $def['parent'] ) ) {
                $parent_key = $def['parent'];
                $path = qp_holdings_page_storage_slug( $parent_key, $lang ) . '/' . $path;
            }
            if ( ! get_page_by_path( trim( $path, '/' ), OBJECT, 'page' ) ) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Ensure starter pages and seed data exist even without theme re-activation.
 * Runs once per theme version at priority 30 (after taxonomies + default terms).
 */
function qp_holdings_ensure_starter_pages_on_init(): void {
	$flag = 'qp_holdings_starter_pages_multilingual_v1';
	if ( get_option( $flag ) && ! qp_holdings_multilingual_pages_missing() ) {
		return;
	}
	qp_holdings_create_starter_pages();
	if ( function_exists( 'qp_holdings_seed_all' ) ) {
		qp_holdings_seed_all( false );
	}
	flush_rewrite_rules();
	update_option( $flag, 1 );
}
add_action( 'init', 'qp_holdings_ensure_starter_pages_on_init', 30 );

function qp_holdings_flush_multilingual_rewrites_once(): void {
    $flag = 'qp_holdings_multilingual_rewrites_v1';
    if ( get_option( $flag ) ) {
        return;
    }
    flush_rewrite_rules();
    update_option( $flag, 1 );
}
add_action( 'init', 'qp_holdings_flush_multilingual_rewrites_once', 40 );

function qp_holdings_is_local_environment(): bool {
    if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
        return true;
    }
    $host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( (string) wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
    if ( '' === $host ) {
        return false;
    }
    $parts     = explode( ':', $host );
    $host_name = $parts[0];
    return in_array( $host_name, array( 'localhost', '127.0.0.1' ), true )
        || str_ends_with( $host_name, '.local' )
        || str_ends_with( $host_name, '.test' );
}

function qp_holdings_turnstile_site_key(): string {
    if ( defined( 'QP_TURNSTILE_SITE_KEY' ) && QP_TURNSTILE_SITE_KEY ) {
        return QP_TURNSTILE_SITE_KEY;
    }
    if ( qp_holdings_is_local_environment() ) {
        return '1x00000000000000000000AA';
    }
    return '0x4AAAAAADW9Ack0qUrc6nWh';
}

function qp_holdings_turnstile_secret_key(): string {
    if ( defined( 'QP_TURNSTILE_SECRET_KEY' ) && QP_TURNSTILE_SECRET_KEY ) {
        return QP_TURNSTILE_SECRET_KEY;
    }
    if ( qp_holdings_is_local_environment() ) {
        return '1x0000000000000000000000000000000AA';
    }
    return '0x4AAAAAADW9AVXuGAop9lfamJ8LT3TEJ7w';
}

function qp_holdings_turnstile_render(): string {
    // Inline min-height reserves the widget's 65px footprint at parse time so
    // the async Turnstile iframe doesn't shift the submit button (CLS). The
    // inline style is intentional: the equivalent rule lives in the deferred
    // stylesheet, which can arrive after the widget has already rendered.
    return '<div class="cf-turnstile" style="display:block;min-height:65px" data-sitekey="' . esc_attr( qp_holdings_turnstile_site_key() ) . '" data-theme="light"></div>';
}

function qp_holdings_turnstile_verify(): bool {
    $token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
    if ( '' === $token ) {
        if ( qp_holdings_is_local_environment() ) {
            return true;
        }
        return false;
    }

    if ( qp_holdings_is_local_environment() && '1x0000000000000000000000000000000AA' === qp_holdings_turnstile_secret_key() ) {
        return true;
    }

    $response = wp_remote_post(
        'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        array(
            'timeout' => 10,
            'body'    => array(
                'secret'   => qp_holdings_turnstile_secret_key(),
                'response' => $token,
                'remoteip' => sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        if ( qp_holdings_is_local_environment() ) {
            return true;
        }
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return ! empty( $body['success'] );
}

function qp_holdings_enqueue_turnstile(): void {
    if ( is_singular( array( 'qp_job', 'qp_project' ) ) || is_page_template( 'page-lien-he.php' ) ) {
        wp_enqueue_script( 'cf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
    }
}
add_action( 'wp_enqueue_scripts', 'qp_holdings_enqueue_turnstile' );

// Dynamic content registration (CPTs, ACF fields, seed data, render helpers).
$qp_holdings_dynamic = get_template_directory() . '/inc/dynamic-content.php';
if ( file_exists( $qp_holdings_dynamic ) ) {
    require_once $qp_holdings_dynamic;
}

add_action( 'admin_head', function() {
            echo '<style>
            #flatsome-notice { display: none !important; }
            tr[data-slug="duplicator-pro"] + tr.plugin-update-tr { display: none !important; }
            .flatsome-panel .notice.notice-warning,
            .flatsome-panel .notice.notice-warning p,
            .flatsome-panel .ux-status-overview__list-item-icon.critical,
            .flatsome-registration-form .wp-clearfix:has(.notice-error),
            .dpro-admin-notice { display: none !important; }
            #wpadminbar .quicklinks > ul > li > a { display: flex !important; align-items: center !important; }
            .flatsome-panel tr:has(.critical) { display: none !important; }
            tr:has(a[href*="duplicator-pro-settings"][href*="licensing"]) { display: none !important; }
            </style>';
        } );