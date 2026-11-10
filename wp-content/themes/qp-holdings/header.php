<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>.header-nav .submenu{visibility:hidden;opacity:0;pointer-events:none;}</style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
/* Helpers to resolve the URL & active state for each nav slot. */
$qp_nav_pages = function_exists( 'qp_holdings_page_definitions' ) ? qp_holdings_page_definitions() : array();
$qp_lang      = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

$qp_nav_url = static function ( string $key ): string {
	if ( function_exists( 'qp_holdings_page_url' ) ) {
		return qp_holdings_page_url( $key );
	}
	$paths = array(
		'home'      => '/',
		'about'     => '/gioi-thieu/',
		'projects'  => '/du-an/',
		'investor'  => '/nha-dau-tu/',
		'stock'     => '/nha-dau-tu/thong-tin-co-phieu/',
		'news'      => '/tintuc/',
		'careers'   => '/tuyen-dung/',
		'faq'       => '/cau-hoi-thuong-gap/',
		'contact'   => '/lien-he/',
	);
	return home_url( $paths[ $key ] ?? '/' );
};

$qp_nav_label = static function ( string $key ) use ( $qp_nav_pages, $qp_lang ): string {
	if ( function_exists( 'qp_holdings_t' ) ) {
		return qp_holdings_t( $key );
	}
	$defaults = array(
		'home'     => 'Trang chủ',
		'about'    => 'Giới thiệu',
		'projects' => 'Dự án',
		'investor' => 'Quan hệ nhà đầu tư',
		'news'     => 'Tin tức',
		'careers'  => 'Tuyển dụng',
		'faq'      => 'FAQ',
		'contact'  => 'Liên hệ',
	);
	return $qp_nav_pages[ $key ][ $qp_lang ]['title'] ?? ( $defaults[ $key ] ?? $key );
};

$qp_current_path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );

$qp_is_nav_active = static function ( string $slug ) use ( $qp_current_path, $qp_nav_pages ): bool {
	if ( 'home' === $slug ) {
		return is_front_page() || '' === $qp_current_path || in_array( $qp_current_path, array( 'en', 'zh' ), true );
	}
	if ( isset( $qp_nav_pages[ $slug ] ) ) {
		foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
			$page_slug = $qp_nav_pages[ $slug ][ $lang ]['slug'] ?? '';
			if ( ! $page_slug ) {
				continue;
			}
			$page_path = $page_slug;
			if ( ! empty( $qp_nav_pages[ $slug ]['parent'] ) ) {
				$parent_key = $qp_nav_pages[ $slug ]['parent'];
				$page_path  = ( $qp_nav_pages[ $parent_key ][ $lang ]['slug'] ?? '' ) . '/' . $page_path;
			}
			$page_path = trim( $page_path, '/' );
			if ( $page_path && ( $page_path === $qp_current_path || 0 === strpos( $qp_current_path, $page_path . '/' ) || 0 === strpos( $qp_current_path, $lang . '/' . $page_path ) ) ) {
				return true;
			}
		}
	}
	if ( in_array( $slug, array( 'projects' ), true ) && is_singular( 'qp_project' ) ) {
		return true;
	}
	if ( in_array( $slug, array( 'news' ), true ) && ( is_singular( 'qp_news' ) || is_singular( 'post' ) ) ) {
		return true;
	}
	if ( in_array( $slug, array( 'careers' ), true ) && is_singular( 'qp_job' ) ) {
		return true;
	}
	return false;
};

$qp_active_class = static function ( string $slug ) use ( $qp_is_nav_active ): string {
	return $qp_is_nav_active( $slug ) ? ' active' : '';
};

$qp_assets    = get_template_directory_uri() . '/assets/site';
$qp_stock_url = 'https://cafef.vn/du-lieu/hastc/hkt-cong-ty-co-phan-qp-holdings.chn';
?>

<!-- ═══ HEADER ═══ -->
<header class="site-header" id="site-header">
	<div class="header-inner">
		<a href="<?php echo esc_url( $qp_nav_url( 'home' ) ); ?>" class="header-logo" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_logo_aria' ) : 'QP Holdings – Trang chủ' ); ?>">
			<img src="<?php echo esc_url( $qp_assets . '/logo-header-figma-2305-12808-logoheader.svg' ); ?>" alt="QP Holdings" width="135" height="48">
		</a>

		<nav class="header-nav" id="main-nav" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'main_menu' ) : 'Điều hướng chính' ); ?>">
			<?php
			$qp_investor_sub_label = static function ( string $key, string $fallback ) use ( $qp_nav_pages, $qp_lang ): string {
				return $qp_nav_pages[ $key ][ $qp_lang ]['title'] ?? $fallback;
			};
			$qp_investor_subs = array(
				array( 'key' => 'stock',              'fallback' => 'Thông tin cổ phiếu' ),
				array( 'key' => 'governance',         'fallback' => 'Quản trị công ty' ),
				array( 'key' => 'agm',                'fallback' => 'Đại hội đồng cổ đông' ),
				array( 'key' => 'annual_reports',     'fallback' => 'Báo cáo thường niên' ),
				array( 'key' => 'financial_reports',  'fallback' => 'Báo cáo tài chính' ),
				array( 'key' => 'governance_reports', 'fallback' => 'Báo cáo quản trị công ty' ),
				array( 'key' => 'disclosures',        'fallback' => 'Công bố thông tin' ),
				array( 'key' => 'newsletter',         'fallback' => 'Bản tin nhà đầu tư' ),
			);
			?>
			<ul>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'home' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'home' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'home' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_home' ); } ?>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'about' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'about' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'about' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_about' ); } ?>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'projects' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'projects' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'projects' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_projects' ); } ?>
				<li class="has-submenu">
					<a href="<?php echo esc_url( $qp_nav_url( 'investor' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'investor' ) ); ?>" aria-haspopup="true" aria-expanded="false">
						<?php echo esc_html( $qp_nav_label( 'investor' ) ); ?>
						<svg class="nav-caret" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>
					</a>
					<ul class="submenu" role="menu">
						<?php foreach ( $qp_investor_subs as $sub ) : 
							$qp_sub_url = $qp_nav_url( $sub['key'] );
							$qp_is_ext  = false;
							if ( 'newsletter' === $sub['key'] && ! empty( $qp_sub_url ) && 0 === strpos( $qp_sub_url, 'http' ) ) {
								$sub_host  = wp_parse_url( $qp_sub_url, PHP_URL_HOST );
								$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
								if ( $sub_host && $site_host && strtolower( $sub_host ) !== strtolower( $site_host ) ) {
									$qp_is_ext = true;
								}
							}
						?>
							<li role="none">
								<a href="<?php echo esc_url( $qp_sub_url ); ?>" role="menuitem"<?php echo $qp_is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $qp_investor_sub_label( $sub['key'], $sub['fallback'] ) ); ?></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_investor' ); } ?>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'news' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'news' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'news' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_news' ); } ?>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'careers' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'careers' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'careers' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'after_careers' ); } ?>
				<li><a href="<?php echo esc_url( $qp_nav_url( 'contact' ) ); ?>" class="nav-link<?php echo esc_attr( $qp_active_class( 'contact' ) ); ?>"><?php echo esc_html( $qp_nav_label( 'contact' ) ); ?></a></li>
				<?php if ( function_exists( 'qp_holdings_render_custom_menu_item' ) ) { qp_holdings_render_custom_menu_item( 'end' ); } ?>
			</ul>
			<!-- Mobile-only actions inside dropdown -->
			<div class="mobile-nav-footer">
				<a href="<?php echo esc_url( $qp_stock_url ); ?>" class="btn-stock" target="_blank" rel="noopener"><?php echo esc_html( qp_holdings_t( 'stock_code' ) ); ?></a>
				<?php if ( function_exists( 'qp_holdings_language_switcher' ) ) : ?>
					<div class="lang-switcher" role="navigation" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'select_language' ) : 'Chọn ngôn ngữ' ); ?>">
						<?php
						$qp_lang_codes = array( 'vi' => 'VN', 'en' => 'EN', 'zh' => '中文' );
						$qp_idx = 0;
						foreach ( $qp_lang_codes as $code => $label ) :
							$is_active = $code === $qp_lang;
							$switch_url = function_exists( 'qp_holdings_language_switch_url' ) ? qp_holdings_language_switch_url( $code ) : '#';
							?>
							<?php if ( $qp_idx++ > 0 ) : ?><span class="lang-sep" aria-hidden="true">|</span><?php endif; ?>
							<a class="lang-btn<?php echo $is_active ? ' active' : ''; ?>" href="<?php echo esc_url( $switch_url ); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div class="lang-switcher">
						<button class="lang-btn active" aria-current="true">VN</button>
						<span class="lang-sep" aria-hidden="true">|</span>
						<button class="lang-btn">EN</button>
						<span class="lang-sep" aria-hidden="true">|</span>
						<button class="lang-btn">中文</button>
					</div>
				<?php endif; ?>
			</div>
		</nav>

		<div class="header-actions">
			<a href="<?php echo esc_url( $qp_stock_url ); ?>" class="btn-stock" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( qp_holdings_t( 'stock_code' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'stock_code' ) ); ?></a>
			<?php if ( function_exists( 'qp_holdings_language_switcher' ) ) : ?>
				<div class="lang-switcher" role="navigation" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'select_language' ) : 'Chọn ngôn ngữ' ); ?>">
					<?php
					$qp_lang_codes = array( 'vi' => 'VN', 'en' => 'EN', 'zh' => '中文' );
					$qp_idx = 0;
					foreach ( $qp_lang_codes as $code => $label ) :
						$is_active = $code === $qp_lang;
						$switch_url = function_exists( 'qp_holdings_language_switch_url' ) ? qp_holdings_language_switch_url( $code ) : '#';
						?>
						<?php if ( $qp_idx++ > 0 ) : ?><span class="lang-sep" aria-hidden="true">|</span><?php endif; ?>
						<a class="lang-btn<?php echo $is_active ? ' active' : ''; ?>" href="<?php echo esc_url( $switch_url ); ?>"<?php echo $is_active ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="lang-switcher" role="navigation" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'select_language' ) : 'Chọn ngôn ngữ' ); ?>">
					<button class="lang-btn active" aria-current="true">VN</button>
					<span class="lang-sep" aria-hidden="true">|</span>
					<button class="lang-btn">EN</button>
					<span class="lang-sep" aria-hidden="true">|</span>
					<button class="lang-btn">中文</button>
				</div>
			<?php endif; ?>
		</div>

		<button class="hamburger" id="hamburger" aria-label="<?php echo esc_attr( qp_holdings_t( 'menu_open' ) ); ?>" aria-expanded="false" aria-controls="main-nav">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
				<path d="M21 6H3C2.448 6 2 6.447 2 7C2 7.553 2.448 8 3 8H21C21.552 8 22 7.553 22 7C22 6.447 21.552 6 21 6Z" fill="currentColor" />
				<path d="M20.5714 11H3.42857C2.64 11 2 11.447 2 12C2 12.553 2.64 13 3.42857 13H20.5714C21.36 13 22 12.553 22 12C22 11.447 21.36 11 20.5714 11Z" fill="currentColor" />
				<path d="M20.75 16H13.25C12.56 16 12 16.447 12 17C12 17.553 12.56 18 13.25 18H20.75C21.44 18 22 17.553 22 17C22 16.447 21.44 16 20.75 16Z" fill="currentColor" />
			</svg>
		</button>
	</div>
</header>
