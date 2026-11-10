<?php
/**
 * Template Name: QP Holdings - Báo cáo tài chính
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-investor-modern';
	return $classes;
} );

get_header();
get_template_part(
	'template-parts/investor-page',
	null,
	array(
		'page_key' => 'financial_reports',
		'section'  => 'financial-report',
	)
);
get_footer();

return;
?>

<?php get_header(); ?>

<?php /* Page-specific head assets, kept inline so the shared header.php stays generic. */ ?>
<link rel="profile" href="https://gmpg.org/xfn/11">
<link rel="dns-prefetch" href="https://use.fontawesome.com/">
<link rel="dns-prefetch" href="https://fonts.googleapis.com/">
<link href="https://fonts.gstatic.com/" crossorigin="" rel="preconnect">
<link rel="stylesheet" id="font-awesome-svg-styles-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/svg-with-js.css' ); ?>" type="text/css"
		media="all">
<link rel="stylesheet" id="contact-form-7-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/styles.css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="spacing-utilities-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/spacing-utilities.css' ); ?>" type="text/css"
		media="all">
<link rel="stylesheet" id="parent-style-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/style.css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="font-awesome-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/all.min.css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="font-awesome-official-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/all.css' ); ?>" type="text/css" media="all"
		integrity="sha384-EXatlQyrOJgDaM9/a74ArMzy7/2bTMSrZj8ID1IPeVmc3GncfCugefCFWSLj8JL/" crossorigin="anonymous">
<link rel="stylesheet" id="flatsome-main-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/flatsome.css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="flatsome-style-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/style(1).css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="flatsome-googlefonts-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" id="font-awesome-official-v4shim-css" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/v4-shims.css' ); ?>" type="text/css"
		media="all" integrity="sha384-FnTDI00psVcN3KkfAip/6yRCeN17N3R7HZoAj+2+nttp8n1/vHfYUWvlscoUUhGJ"
		crossorigin="anonymous">
<link rel="stylesheet" href="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/dropdown-nav.css' ); ?>" type="text/css" media="all">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<style id="wp-img-auto-sizes-contain-inline-css" type="text/css">
		img:is([sizes=auto i], [sizes^="auto," i]) {
			contain-intrinsic-size: 3000px 1500px
		}

		/*# sourceURL=wp-img-auto-sizes-contain-inline-css */
	</style>
<style id="wp-emoji-styles-inline-css" type="text/css">
		img.wp-smiley,
		img.emoji {
			display: inline !important;
			border: none !important;
			box-shadow: none !important;
			height: 1em !important;
			width: 1em !important;
			margin: 0 0.07em !important;
			vertical-align: -0.1em !important;
			background: none !important;
			padding: 0 !important;
		}

		/*# sourceURL=wp-emoji-styles-inline-css */
	</style>
<style id="wp-block-library-inline-css" type="text/css">
		:root {
			--wp-block-synced-color: #7a00df;
			--wp-block-synced-color--rgb: 122, 0, 223;
			--wp-bound-block-color: var(--wp-block-synced-color);
			--wp-editor-canvas-background: #ddd;
			--wp-admin-theme-color: #007cba;
			--wp-admin-theme-color--rgb: 0, 124, 186;
			--wp-admin-theme-color-darker-10: #006ba1;
			--wp-admin-theme-color-darker-10--rgb: 0, 107, 160.5;
			--wp-admin-theme-color-darker-20: #005a87;
			--wp-admin-theme-color-darker-20--rgb: 0, 90, 135;
			--wp-admin-border-width-focus: 2px
		}

		@media (min-resolution:192dpi) {
			:root {
				--wp-admin-border-width-focus: 1.5px
			}
		}

		.wp-element-button {
			cursor: pointer
		}

		:root .has-very-light-gray-background-color {
			background-color: #eee
		}

		:root .has-very-dark-gray-background-color {
			background-color: #313131
		}

		:root .has-very-light-gray-color {
			color: #eee
		}

		:root .has-very-dark-gray-color {
			color: #313131
		}

		:root .has-vivid-green-cyan-to-vivid-cyan-blue-gradient-background {
			background: linear-gradient(135deg, #00d084, #0693e3)
		}

		:root .has-purple-crush-gradient-background {
			background: linear-gradient(135deg, #34e2e4, #4721fb 50%, #ab1dfe)
		}

		:root .has-hazy-dawn-gradient-background {
			background: linear-gradient(135deg, #faaca8, #dad0ec)
		}

		:root .has-subdued-olive-gradient-background {
			background: linear-gradient(135deg, #fafae1, #67a671)
		}

		:root .has-atomic-cream-gradient-background {
			background: linear-gradient(135deg, #fdd79a, #004a59)
		}

		:root .has-nightshade-gradient-background {
			background: linear-gradient(135deg, #330968, #31cdcf)
		}

		:root .has-midnight-gradient-background {
			background: linear-gradient(135deg, #020381, #2874fc)
		}

		:root {
			--wp--preset--font-size--normal: 16px;
			--wp--preset--font-size--huge: 42px
		}

		.has-regular-font-size {
			font-size: 1em
		}

		.has-larger-font-size {
			font-size: 2.625em
		}

		.has-normal-font-size {
			font-size: var(--wp--preset--font-size--normal)
		}

		.has-huge-font-size {
			font-size: var(--wp--preset--font-size--huge)
		}

		.has-text-align-center {
			text-align: center
		}

		.has-text-align-left {
			text-align: left
		}

		.has-text-align-right {
			text-align: right
		}

		.has-fit-text {
			white-space: nowrap !important
		}

		#end-resizable-editor-section {
			display: none
		}

		.aligncenter {
			clear: both
		}

		.items-justified-left {
			justify-content: flex-start
		}

		.items-justified-center {
			justify-content: center
		}

		.items-justified-right {
			justify-content: flex-end
		}

		.items-justified-space-between {
			justify-content: space-between
		}

		.screen-reader-text {
			border: 0;
			clip-path: inset(50%);
			height: 1px;
			margin: -1px;
			overflow: hidden;
			padding: 0;
			position: absolute;
			width: 1px;
			word-wrap: normal !important
		}

		.screen-reader-text:focus {
			background-color: #ddd;
			clip-path: none;
			color: #444;
			display: block;
			font-size: 1em;
			height: auto;
			left: 5px;
			line-height: normal;
			padding: 15px 23px 14px;
			text-decoration: none;
			top: 5px;
			width: auto;
			z-index: 100000
		}

		html :where(.has-border-color) {
			border-style: solid
		}

		html :where([style*=border-top-color]) {
			border-top-style: solid
		}

		html :where([style*=border-right-color]) {
			border-right-style: solid
		}

		html :where([style*=border-bottom-color]) {
			border-bottom-style: solid
		}

		html :where([style*=border-left-color]) {
			border-left-style: solid
		}

		html :where([style*=border-width]) {
			border-style: solid
		}

		html :where([style*=border-top-width]) {
			border-top-style: solid
		}

		html :where([style*=border-right-width]) {
			border-right-style: solid
		}

		html :where([style*=border-bottom-width]) {
			border-bottom-style: solid
		}

		html :where([style*=border-left-width]) {
			border-left-style: solid
		}

		html :where(img[class*=wp-image-]) {
			height: auto;
			max-width: 100%
		}

		:where(figure) {
			margin: 0 0 1em
		}

		html :where(.is-position-sticky) {
			--wp-admin--admin-bar--position-offset: var(--wp-admin--admin-bar--height, 0px)
		}

		@media screen and (max-width:600px) {
			html :where(.is-position-sticky) {
				--wp-admin--admin-bar--position-offset: 0px
			}
		}

		/*# sourceURL=wp-block-library-inline-css */
	</style>
<style id="font-awesome-svg-styles-default-inline-css" type="text/css">
		.svg-inline--fa {
			display: inline-block;
			height: 1em;
			overflow: visible;
			vertical-align: -.125em;
		}

		/*# sourceURL=font-awesome-svg-styles-default-inline-css */
	</style>
<style id="font-awesome-svg-styles-inline-css" type="text/css">
		.wp-block-font-awesome-icon svg::before,
		.wp-rich-text-font-awesome-icon svg::before {
			content: unset;
		}

		/*# sourceURL=font-awesome-svg-styles-inline-css */
	</style>
<style id="global-styles-inline-css" type="text/css">
		:root {
			--wp--preset--aspect-ratio--square: 1;
			--wp--preset--aspect-ratio--4-3: 4/3;
			--wp--preset--aspect-ratio--3-4: 3/4;
			--wp--preset--aspect-ratio--3-2: 3/2;
			--wp--preset--aspect-ratio--2-3: 2/3;
			--wp--preset--aspect-ratio--16-9: 16/9;
			--wp--preset--aspect-ratio--9-16: 9/16;
			--wp--preset--color--black: #000000;
			--wp--preset--color--cyan-bluish-gray: #abb8c3;
			--wp--preset--color--white: #ffffff;
			--wp--preset--color--pale-pink: #f78da7;
			--wp--preset--color--vivid-red: #cf2e2e;
			--wp--preset--color--luminous-vivid-orange: #ff6900;
			--wp--preset--color--luminous-vivid-amber: #fcb900;
			--wp--preset--color--light-green-cyan: #7bdcb5;
			--wp--preset--color--vivid-green-cyan: #00d084;
			--wp--preset--color--pale-cyan-blue: #8ed1fc;
			--wp--preset--color--vivid-cyan-blue: #0693e3;
			--wp--preset--color--vivid-purple: #9b51e0;
			--wp--preset--color--primary: #0b1f3a;
			--wp--preset--color--secondary: #c9a96e;
			--wp--preset--color--success: #2d6a4f;
			--wp--preset--color--alert: #b91c1c;
			--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple: linear-gradient(135deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%);
			--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan: linear-gradient(135deg, rgb(122, 220, 180) 0%, rgb(0, 208, 130) 100%);
			--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange: linear-gradient(135deg, rgb(252, 185, 0) 0%, rgb(255, 105, 0) 100%);
			--wp--preset--gradient--luminous-vivid-orange-to-vivid-red: linear-gradient(135deg, rgb(255, 105, 0) 0%, rgb(207, 46, 46) 100%);
			--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray: linear-gradient(135deg, rgb(238, 238, 238) 0%, rgb(169, 184, 195) 100%);
			--wp--preset--gradient--cool-to-warm-spectrum: linear-gradient(135deg, rgb(74, 234, 220) 0%, rgb(151, 120, 209) 20%, rgb(207, 42, 186) 40%, rgb(238, 44, 130) 60%, rgb(251, 105, 98) 80%, rgb(254, 248, 76) 100%);
			--wp--preset--gradient--blush-light-purple: linear-gradient(135deg, rgb(255, 206, 236) 0%, rgb(152, 150, 240) 100%);
			--wp--preset--gradient--blush-bordeaux: linear-gradient(135deg, rgb(254, 205, 165) 0%, rgb(254, 45, 45) 50%, rgb(107, 0, 62) 100%);
			--wp--preset--gradient--luminous-dusk: linear-gradient(135deg, rgb(255, 203, 112) 0%, rgb(199, 81, 192) 50%, rgb(65, 88, 208) 100%);
			--wp--preset--gradient--pale-ocean: linear-gradient(135deg, rgb(255, 245, 203) 0%, rgb(182, 227, 212) 50%, rgb(51, 167, 181) 100%);
			--wp--preset--gradient--electric-grass: linear-gradient(135deg, rgb(202, 248, 128) 0%, rgb(113, 206, 126) 100%);
			--wp--preset--gradient--midnight: linear-gradient(135deg, rgb(2, 3, 129) 0%, rgb(40, 116, 252) 100%);
			--wp--preset--font-size--small: 13px;
			--wp--preset--font-size--medium: 20px;
			--wp--preset--font-size--large: 36px;
			--wp--preset--font-size--x-large: 42px;
			--wp--preset--spacing--20: 0.44rem;
			--wp--preset--spacing--30: 0.67rem;
			--wp--preset--spacing--40: 1rem;
			--wp--preset--spacing--50: 1.5rem;
			--wp--preset--spacing--60: 2.25rem;
			--wp--preset--spacing--70: 3.38rem;
			--wp--preset--spacing--80: 5.06rem;
			--wp--preset--shadow--natural: 6px 6px 9px rgba(0, 0, 0, 0.2);
			--wp--preset--shadow--deep: 12px 12px 50px rgba(0, 0, 0, 0.4);
			--wp--preset--shadow--sharp: 6px 6px 0px rgba(0, 0, 0, 0.2);
			--wp--preset--shadow--outlined: 6px 6px 0px -3px rgb(255, 255, 255), 6px 6px rgb(0, 0, 0);
			--wp--preset--shadow--crisp: 6px 6px 0px rgb(0, 0, 0);
		}

		:where(body) {
			margin: 0;
		}

		.wp-site-blocks>.alignleft {
			float: left;
			margin-right: 2em;
		}

		.wp-site-blocks>.alignright {
			float: right;
			margin-left: 2em;
		}

		.wp-site-blocks>.aligncenter {
			justify-content: center;
			margin-left: auto;
			margin-right: auto;
		}

		:where(.is-layout-flex) {
			gap: 0.5em;
		}

		:where(.is-layout-grid) {
			gap: 0.5em;
		}

		.is-layout-flow>.alignleft {
			float: left;
			margin-inline-start: 0;
			margin-inline-end: 2em;
		}

		.is-layout-flow>.alignright {
			float: right;
			margin-inline-start: 2em;
			margin-inline-end: 0;
		}

		.is-layout-flow>.aligncenter {
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.is-layout-constrained>.alignleft {
			float: left;
			margin-inline-start: 0;
			margin-inline-end: 2em;
		}

		.is-layout-constrained>.alignright {
			float: right;
			margin-inline-start: 2em;
			margin-inline-end: 0;
		}

		.is-layout-constrained>.aligncenter {
			margin-left: auto !important;
			margin-right: auto !important;
		}

		.is-layout-constrained> :where(:not(.alignleft):not(.alignright):not(.alignfull)) {
			margin-left: auto !important;
			margin-right: auto !important;
		}

		body .is-layout-flex {
			display: flex;
		}

		.is-layout-flex {
			flex-wrap: wrap;
			align-items: center;
		}

		.is-layout-flex> :is(*, div) {
			margin: 0;
		}

		body .is-layout-grid {
			display: grid;
		}

		.is-layout-grid> :is(*, div) {
			margin: 0;
		}

		body {
			padding-top: 0px;
			padding-right: 0px;
			padding-bottom: 0px;
			padding-left: 0px;
		}

		a:where(:not(.wp-element-button)) {
			text-decoration: none;
		}

		:root :where(.wp-element-button, .wp-block-button__link) {
			background-color: #32373c;
			border-width: 0;
			color: #fff;
			font-family: inherit;
			font-size: inherit;
			font-style: inherit;
			font-weight: inherit;
			letter-spacing: inherit;
			line-height: inherit;
			padding-top: calc(0.667em + 2px);
			padding-right: calc(1.333em + 2px);
			padding-bottom: calc(0.667em + 2px);
			padding-left: calc(1.333em + 2px);
			text-decoration: none;
			text-transform: inherit;
		}

		.has-black-color {
			color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-color {
			color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-color {
			color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-color {
			color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-color {
			color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-color {
			color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-color {
			color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-color {
			color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-color {
			color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-color {
			color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-color {
			color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-color {
			color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-primary-color {
			color: var(--wp--preset--color--primary) !important;
		}

		.has-secondary-color {
			color: var(--wp--preset--color--secondary) !important;
		}

		.has-success-color {
			color: var(--wp--preset--color--success) !important;
		}

		.has-alert-color {
			color: var(--wp--preset--color--alert) !important;
		}

		.has-black-background-color {
			background-color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-background-color {
			background-color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-background-color {
			background-color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-background-color {
			background-color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-background-color {
			background-color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-background-color {
			background-color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-background-color {
			background-color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-background-color {
			background-color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-background-color {
			background-color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-background-color {
			background-color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-background-color {
			background-color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-background-color {
			background-color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-primary-background-color {
			background-color: var(--wp--preset--color--primary) !important;
		}

		.has-secondary-background-color {
			background-color: var(--wp--preset--color--secondary) !important;
		}

		.has-success-background-color {
			background-color: var(--wp--preset--color--success) !important;
		}

		.has-alert-background-color {
			background-color: var(--wp--preset--color--alert) !important;
		}

		.has-black-border-color {
			border-color: var(--wp--preset--color--black) !important;
		}

		.has-cyan-bluish-gray-border-color {
			border-color: var(--wp--preset--color--cyan-bluish-gray) !important;
		}

		.has-white-border-color {
			border-color: var(--wp--preset--color--white) !important;
		}

		.has-pale-pink-border-color {
			border-color: var(--wp--preset--color--pale-pink) !important;
		}

		.has-vivid-red-border-color {
			border-color: var(--wp--preset--color--vivid-red) !important;
		}

		.has-luminous-vivid-orange-border-color {
			border-color: var(--wp--preset--color--luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-amber-border-color {
			border-color: var(--wp--preset--color--luminous-vivid-amber) !important;
		}

		.has-light-green-cyan-border-color {
			border-color: var(--wp--preset--color--light-green-cyan) !important;
		}

		.has-vivid-green-cyan-border-color {
			border-color: var(--wp--preset--color--vivid-green-cyan) !important;
		}

		.has-pale-cyan-blue-border-color {
			border-color: var(--wp--preset--color--pale-cyan-blue) !important;
		}

		.has-vivid-cyan-blue-border-color {
			border-color: var(--wp--preset--color--vivid-cyan-blue) !important;
		}

		.has-vivid-purple-border-color {
			border-color: var(--wp--preset--color--vivid-purple) !important;
		}

		.has-primary-border-color {
			border-color: var(--wp--preset--color--primary) !important;
		}

		.has-secondary-border-color {
			border-color: var(--wp--preset--color--secondary) !important;
		}

		.has-success-border-color {
			border-color: var(--wp--preset--color--success) !important;
		}

		.has-alert-border-color {
			border-color: var(--wp--preset--color--alert) !important;
		}

		.has-vivid-cyan-blue-to-vivid-purple-gradient-background {
			background: var(--wp--preset--gradient--vivid-cyan-blue-to-vivid-purple) !important;
		}

		.has-light-green-cyan-to-vivid-green-cyan-gradient-background {
			background: var(--wp--preset--gradient--light-green-cyan-to-vivid-green-cyan) !important;
		}

		.has-luminous-vivid-amber-to-luminous-vivid-orange-gradient-background {
			background: var(--wp--preset--gradient--luminous-vivid-amber-to-luminous-vivid-orange) !important;
		}

		.has-luminous-vivid-orange-to-vivid-red-gradient-background {
			background: var(--wp--preset--gradient--luminous-vivid-orange-to-vivid-red) !important;
		}

		.has-very-light-gray-to-cyan-bluish-gray-gradient-background {
			background: var(--wp--preset--gradient--very-light-gray-to-cyan-bluish-gray) !important;
		}

		.has-cool-to-warm-spectrum-gradient-background {
			background: var(--wp--preset--gradient--cool-to-warm-spectrum) !important;
		}

		.has-blush-light-purple-gradient-background {
			background: var(--wp--preset--gradient--blush-light-purple) !important;
		}

		.has-blush-bordeaux-gradient-background {
			background: var(--wp--preset--gradient--blush-bordeaux) !important;
		}

		.has-luminous-dusk-gradient-background {
			background: var(--wp--preset--gradient--luminous-dusk) !important;
		}

		.has-pale-ocean-gradient-background {
			background: var(--wp--preset--gradient--pale-ocean) !important;
		}

		.has-electric-grass-gradient-background {
			background: var(--wp--preset--gradient--electric-grass) !important;
		}

		.has-midnight-gradient-background {
			background: var(--wp--preset--gradient--midnight) !important;
		}

		.has-small-font-size {
			font-size: var(--wp--preset--font-size--small) !important;
		}

		.has-medium-font-size {
			font-size: var(--wp--preset--font-size--medium) !important;
		}

		.has-large-font-size {
			font-size: var(--wp--preset--font-size--large) !important;
		}

		.has-x-large-font-size {
			font-size: var(--wp--preset--font-size--x-large) !important;
		}

		/*# sourceURL=global-styles-inline-css */
	</style>
<style id="flatsome-main-inline-css" type="text/css">
		/*# sourceURL=flatsome-main-inline-css */
	</style>
<style>
		#wpadminbar .quicklinks>ul>li>a {
			display: flex !important;
			align-items: center !important;

			svg {
				padding-right: 4px;
			}
		}
	</style>
<style>
			.lazyload[data-src] {
				display: none !important;
			}
		</style>
<style>
		.lazyload {
			background-image: none !important;
		}

		.lazyload:before {
			background-image: none !important;
		}
	</style>
<style id="custom-css" type="text/css">
		:root {
			--primary-color: #0b1f3a;
			--fs-color-primary: #0b1f3a;
			--fs-color-secondary: #c9a96e;
			--fs-color-success: #2d6a4f;
			--fs-color-alert: #b91c1c;
			--fs-color-base: #0b1f3a;
			--fs-experimental-link-color: #0b1f3a;
			--fs-experimental-link-color-hover: #c9a96e;
		}

		.tooltipster-base {
			--tooltip-color: #fff;
			--tooltip-bg-color: #2d6a4f;
		}

		.off-canvas-right .mfp-content,
		.off-canvas-left .mfp-content {
			--drawer-width: 300px;
		}

		.container-width,
		.full-width .ubermenu-nav,
		.container,
		.row {
			max-width: 1250px
		}

		.row.row-collapse {
			max-width: 1220px
		}

		.row.row-small {
			max-width: 1242.5px
		}

		.row.row-large {
			max-width: 1280px
		}

		.header-main {
			height: 140px
		}

		#logo img {
			max-height: 140px
		}

		#logo {
			width: 183px;
		}

		#logo a {
			max-width: 130pxpx;
		}

		.header-bottom {
			min-height: 55px
		}

		.header-top {
			min-height: 30px
		}

		.transparent .header-main {
			height: 140px
		}

		.transparent #logo img {
			max-height: 140px
		}

		.has-transparent+.page-title:first-of-type,
		.has-transparent+#main>.page-title,
		.has-transparent+#main>div>.page-title,
		.has-transparent+#main .page-header-wrapper:first-of-type .page-title {
			padding-top: 140px;
		}

		.header.show-on-scroll,
		.stuck .header-main {
			height: 140px !important
		}

		.stuck #logo img {
			max-height: 140px !important
		}

		.header-bg-color {
			background-color: rgba(255, 255, 255, 0.9)
		}

		.header-bottom {
			background-color: #f1f1f1
		}

		.header-main .nav>li>a {
			line-height: 16px
		}

		.stuck .header-main .nav>li>a {
			line-height: 50px
		}

		.header-bottom-nav>li>a {
			line-height: 16px
		}

		@media (max-width: 549px) {
			.header-main {
				height: 70px
			}

			#logo img {
				max-height: 70px
			}
		}

		h1,
		h2,
		h3,
		h4,
		h5,
		h6,
		.heading-font {
			color: #0b1f3a;
		}

		body {
			font-size: 100%;
		}

		@media screen and (max-width: 549px) {
			body {
				font-size: 100%;
			}
		}

		body {
			font-family: "Open Sans", sans-serif;
		}

		body {
			font-weight: 400;
			font-style: normal;
		}

		.nav>li>a {
			font-family: "Open Sans", sans-serif;
		}

		.mobile-sidebar-levels-2 .nav>li>ul>li>a {
			font-family: "Open Sans", sans-serif;
		}

		.nav>li>a,
		.mobile-sidebar-levels-2 .nav>li>ul>li>a {
			font-weight: 700;
			font-style: normal;
		}

		h1,
		h2,
		h3,
		h4,
		h5,
		h6,
		.heading-font,
		.off-canvas-center .nav-sidebar.nav-vertical>li>a {
			font-family: "Open Sans", sans-serif;
		}

		h1,
		h2,
		h3,
		h4,
		h5,
		h6,
		.heading-font,
		.banner h1,
		.banner h2 {
			font-weight: 700;
			font-style: normal;
		}

		.alt-font {
			font-family: "Open Sans", sans-serif;
		}

		.alt-font {
			font-weight: 400 !important;
			font-style: normal !important;
		}

		.breadcrumbs {
			text-transform: none;
		}

		button,
		.button {
			text-transform: none;
		}

		.section-title span {
			text-transform: none;
		}

		h3.widget-title,
		span.widget-title {
			text-transform: none;
		}

		.widget:where(:not(.widget_shopping_cart)) a {
			color: #0b1f3a;
		}

		.widget:where(:not(.widget_shopping_cart)) a:hover {
			color: #c9a96e;
		}

		.widget .tagcloud a:hover {
			border-color: #c9a96e;
			background-color: #c9a96e;
		}

		.is-divider {
			background-color: rgba(24, 71, 34, 0);
		}

		.absolute-footer,
		html {
			background-color: #ffffff
		}

		.nav-vertical-fly-out>li+li {
			border-top-width: 1px;
			border-top-style: solid;
		}

		.label-new.menu-item>a:after {
			content: "Mới";
		}

		.label-hot.menu-item>a:after {
			content: "Nổi bật";
		}

		.label-sale.menu-item>a:after {
			content: "Giảm giá";
		}

		.label-popular.menu-item>a:after {
			content: "Phổ biến";
		}

		/* === NAVBAR STYLE MATCHING === */
		.header-nav-main.nav-left {
			flex-wrap: nowrap !important;
		}
		.header-nav-main.nav-left > li > a.nav-top-link {
			letter-spacing: 0;
			padding: 10px 5px;
			white-space: nowrap;
		}

		.header-nav-main > li.active > a.nav-top-link,
		.header-nav-main > li.current-menu-item > a.nav-top-link {
			color: #c9a96e !important;
		}

		.header-nav-main > li > a.nav-top-link:hover {
			color: #c9a96e !important;
		}

		.transparent .header-main .header-nav-main > li > a.nav-top-link {
			color: rgba(255, 255, 255, 0.85);
		}

		.transparent .header-main .header-nav-main > li.active > a.nav-top-link,
		.transparent .header-main .header-nav-main > li > a.nav-top-link:hover {
			color: #c9a96e !important;
		}

		.stuck .header-main .header-nav-main > li > a.nav-top-link {
			color: #0b1f3a;
		}

		.stuck .header-main .header-nav-main > li.active > a.nav-top-link,
		.stuck .header-main .header-nav-main > li > a.nav-top-link:hover {
			color: #c9a96e !important;
		}
		/* === END NAVBAR STYLE === */
	</style>
<style type="text/css" data-fbcssmodules="css:fb.css.base css:fb.css.dialog css:fb.css.iframewidget">
		.fb_hidden {
			position: absolute;
			top: -10000px;
			z-index: 10001
		}

		.fb_reposition {
			overflow: hidden;
			position: relative
		}

		.fb_invisible {
			display: none
		}

		.fb_reset {
			background: none;
			border: 0px;
			border-spacing: 0;
			color: #000;
			cursor: auto;
			direction: ltr;
			font-family: lucida grande, tahoma, verdana, arial, sans-serif;
			font-size: 11px;
			font-style: normal;
			font-variant: normal;
			font-weight: 400;
			letter-spacing: normal;
			line-height: 1;
			margin: 0;
			overflow: visible;
			padding: 0;
			text-align: left;
			text-decoration: none;
			text-indent: 0;
			text-shadow: none;
			text-transform: none;
			visibility: visible;
			white-space: normal;
			word-spacing: normal
		}

		.fb_reset>div {
			overflow: hidden
		}

		@keyframes fb_transform {
			0% {
				opacity: 0;
				transform: scale(.95)
			}

			to {
				opacity: 1;
				transform: scale(1)
			}
		}

		.fb_animate {
			animation: fb_transform .3s forwards
		}

		.fb_dialog {
			background: #525252b3;
			position: absolute;
			top: -10000px;
			z-index: 10001
		}

		.fb_dialog_advanced {
			border-radius: 8px;
			padding: 10px
		}

		.fb_dialog_content {
			background: #fff;
			color: #373737
		}

		.fb_dialog_close_icon {
			background: none no-repeat scroll 0 0 transparent;
			cursor: pointer;
			display: block;
			height: 15px;
			position: absolute;
			right: 18px;
			top: 17px;
			width: 15px
		}

		.fb_dialog_mobile .fb_dialog_close_icon {
			left: 5px;
			right: auto;
			top: 5px
		}

		.fb_dialog_padding {
			background-color: transparent;
			position: absolute;
			width: 1px;
			z-index: -1
		}

		.fb_dialog_close_icon:hover {
			background: none no-repeat scroll 0 -15px transparent
		}

		.fb_dialog_close_icon:active {
			background: none no-repeat scroll 0 -30px transparent
		}

		.fb_dialog_iframe {
			line-height: 0
		}

		.fb_dialog_content .dialog_title {
			background: #6d84b4;
			border: 1px solid #365899;
			color: #fff;
			font-size: 14px;
			font-weight: 700;
			margin: 0
		}

		.fb_dialog_content .dialog_title>span {
			background: none no-repeat 5px 50%;
			float: left;
			padding: 5px 0 7px 26px
		}

		body.fb_hidden {
			height: 100%;
			left: 0;
			margin: 0;
			overflow: visible;
			position: absolute;
			top: -10000px;
			transform: none;
			width: 100%
		}

		.fb_dialog.fb_dialog_mobile.loading {
			background: none #fff no-repeat 50% 50%;
			min-height: 100%;
			min-width: 100%;
			overflow: hidden;
			position: absolute;
			top: 0;
			z-index: 10001
		}

		.fb_dialog.fb_dialog_mobile.loading.centered {
			background: none;
			height: auto;
			min-height: initial;
			min-width: initial;
			width: auto
		}

		.fb_dialog.fb_dialog_mobile.loading.centered #fb_dialog_loader_spinner {
			width: 100%
		}

		.fb_dialog.fb_dialog_mobile.loading.centered .fb_dialog_content {
			background: none
		}

		.loading.centered #fb_dialog_loader_close {
			clear: both;
			color: #fff;
			display: block;
			font-size: 18px;
			padding-top: 20px
		}

		#fb-root #fb_dialog_ipad_overlay {
			background: #0006;
			inset: 0;
			min-height: 100%;
			position: absolute;
			width: 100%;
			z-index: 10000
		}

		#fb-root #fb_dialog_ipad_overlay.hidden {
			display: none
		}

		.fb_dialog.fb_dialog_mobile.loading iframe {
			visibility: hidden
		}

		.fb_dialog_mobile .fb_dialog_iframe {
			position: sticky;
			top: 0
		}

		.fb_dialog_content .dialog_header {
			background: linear-gradient(from(#738aba), to(#2c4987));
			border-bottom: 1px solid;
			border-color: #043b87;
			box-shadow: #fff 0 1px 1px -1px inset;
			color: #fff;
			font: 700 14px Helvetica, sans-serif;
			text-overflow: ellipsis;
			text-shadow: rgba(0, 30, 84, .296875) 0px -1px 0px;
			vertical-align: middle;
			white-space: nowrap
		}

		.fb_dialog_content .dialog_header table {
			height: 43px;
			width: 100%
		}

		.fb_dialog_content .dialog_header td.header_left {
			font-size: 12px;
			padding-left: 5px;
			vertical-align: middle;
			width: 60px
		}

		.fb_dialog_content .dialog_header td.header_right {
			font-size: 12px;
			padding-right: 5px;
			vertical-align: middle;
			width: 60px
		}

		.fb_dialog_content .touchable_button {
			background: linear-gradient(from(#4267B2), to(#2a4887));
			background-clip: padding-box;
			border: 1px solid #29487d;
			border-radius: 3px;
			display: inline-block;
			line-height: 18px;
			margin-top: 3px;
			max-width: 85px;
			padding: 4px 12px;
			position: relative
		}

		.fb_dialog_content .dialog_header .touchable_button input {
			background: none;
			border: none;
			color: #fff;
			font: 700 12px Helvetica, sans-serif;
			margin: 2px -12px;
			padding: 2px 6px 3px;
			text-shadow: rgba(0, 30, 84, .296875) 0px -1px 0px
		}

		.fb_dialog_content .dialog_header .header_center {
			color: #fff;
			font-size: 16px;
			font-weight: 700;
			line-height: 18px;
			text-align: center;
			vertical-align: middle
		}

		.fb_dialog_content .dialog_content {
			background: none no-repeat 50% 50%;
			border: 1px solid #4A4A4A;
			border-bottom: 0;
			border-top: 0;
			height: 150px
		}

		.fb_dialog_content .dialog_footer {
			background: #f5f6f7;
			border: 1px solid #4A4A4A;
			border-top-color: #ccc;
			height: 40px
		}

		#fb_dialog_loader_close {
			float: left
		}

		.fb_dialog.fb_dialog_mobile .fb_dialog_close_icon {
			visibility: hidden
		}

		#fb_dialog_loader_spinner {
			animation: rotateSpinner 1.2s linear infinite;
			background-color: transparent;
			background-image: none;
			background-position: 50% 50%;
			background-repeat: no-repeat;
			height: 24px;
			width: 24px
		}

		@keyframes rotateSpinner {
			0% {
				transform: rotate(0)
			}

			to {
				transform: rotate(360deg)
			}
		}

		.fb_iframe_widget {
			display: inline-block;
			position: relative
		}

		.fb_iframe_widget span {
			display: inline-block;
			position: relative;
			text-align: justify
		}

		.fb_iframe_widget iframe {
			position: absolute
		}

		.fb_iframe_widget_fluid_desktop,
		.fb_iframe_widget_fluid_desktop span,
		.fb_iframe_widget_fluid_desktop iframe {
			max-width: 100%
		}

		.fb_iframe_widget_fluid_desktop iframe {
			min-width: 220px;
			position: relative
		}

		.fb_iframe_widget_lift {
			z-index: 1
		}

		.fb_iframe_widget_fluid {
			display: inline
		}

		.fb_iframe_widget_fluid span {
			width: 100%
		}
	</style>
<style>
		/* ============================================ */
		/* FIX: Ensure Flatsome dropdown works on hover */
		/* ============================================ */
		.header-nav .has-dropdown { position: relative; }
		.header-nav .has-dropdown > .nav-dropdown {
			background-color: #fff;
			color: #0b1f3a;
			min-width: 260px;
			padding: 15px 0;
			position: absolute;
			top: 100%;
			left: 0;
			z-index: 999;
			opacity: 0;
			visibility: hidden;
			transform: translateY(6px);
			transition: opacity 0.25s ease, visibility 0.25s ease, transform 0.25s ease;
			box-shadow: 0 6px 20px rgba(0,0,0,0.12);
			border-radius: 4px;
			border: 1px solid #eee;
			list-style: none;
		}
		.header-nav .has-dropdown:hover > .nav-dropdown {
			opacity: 1;
			visibility: visible;
			transform: translateY(0);
		}
		.header-nav .nav-dropdown .menu-item a {
			display: block;
			padding: 8px 24px;
			color: #333;
			text-decoration: none;
			font-size: 13px;
			font-weight: 500;
			white-space: nowrap;
			transition: background-color 0.15s, color 0.15s;
		}
		.header-nav .nav-dropdown .menu-item a:hover {
			background-color: #f8f8f8;
			color: #c9a96e;
		}

		/* ============================================ */
		/* Investor content styles (scoped)             */
		/* ============================================ */

		/* Bootstrap grid (scoped) */
		#investor-content .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; box-sizing: border-box; }
		#investor-content .row { display: flex; flex-wrap: wrap; margin-left: -15px; margin-right: -15px; }
		#investor-content .col-lg-3 { flex: 0 0 25%; max-width: 25%; padding: 0 15px; box-sizing: border-box; }
		#investor-content .col-lg-9 { flex: 0 0 75%; max-width: 75%; padding: 0 15px; box-sizing: border-box; }
		#investor-content .col-md-6 { flex: 0 0 50%; max-width: 50%; padding: 0 15px; box-sizing: border-box; }
		#investor-content .col-xl-6 { flex: 0 0 50%; max-width: 50%; }
		#investor-content .mb-3 { margin-bottom: 1rem; }
		#investor-content .mt-2 { margin-top: 0.5rem; }
		#investor-content .mt-3 { margin-top: 1rem; }
		#investor-content .d-none { display: none; }
		@media (min-width: 992px) {
			#investor-content .d-lg-flex { display: flex !important; }
			#investor-content .mt-lg-0 { margin-top: 0 !important; }
		}
		@media (max-width: 991px) {
			#investor-content .col-lg-3 { flex: 0 0 100%; max-width: 100%; }
			#investor-content .col-lg-9 { flex: 0 0 100%; max-width: 100%; }
		}
		@media (max-width: 767px) {
			#investor-content .col-md-6 { flex: 0 0 100%; max-width: 100%; }
		}

		/* Main content area */
		#investor-content { padding-bottom: 60px; }
		#investor-content > main > #main-content:first-child { padding-top: 30px; }
		#investor-content h1 { font-family: 'Open Sans', sans-serif; font-size: 32px; font-weight: 700; color: #0b1f3a; margin-bottom: 24px; }

		/* Body nav horizontal (sub-nav tabs) */
		#investor-content #top-body-nav { background: #f5f5f5; border-bottom: 1px solid #e0e0e0; position: relative; margin-bottom: 30px; }
		#investor-content #top-body-nav .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
		#investor-content .body-navs-horizontal { display: flex; gap: 0; overflow-x: auto; white-space: nowrap; -webkit-overflow-scrolling: touch; }
		#investor-content .body-nav-item { padding: 0; flex-shrink: 0; }
		#investor-content .body-nav-item a { display: block; padding: 14px 20px; color: #555; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s; border-bottom: 3px solid transparent; }
		#investor-content .body-nav-item a:hover,
		#investor-content .body-nav-item.active a { color: #0b1f3a; border-bottom-color: #c9a96e; font-weight: 600; }
		#investor-content .btn-nav { display: none; }

		/* Sidebar */
		#investor-content .sidebar-page-title-wrap { position: relative; background-size: cover; background-position: center; border-radius: 8px; overflow: hidden; min-height: 200px; display: flex; align-items: flex-end; }
		#investor-content .sidebar-page-title-wrap::before { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(11,31,58,0.8), transparent); }
		#investor-content .sidebar-page-title { position: relative; padding: 20px; width: 100%; }
		#investor-content .sidebar-page-title h2 { color: #fff; font-size: 20px; font-weight: 700; margin: 0; font-family: 'Open Sans', sans-serif; }
		#investor-content .sidebar-nav { display: none; }
		#investor-content .is-sticky-column { position: sticky; top: 150px; }

		/* QHCD items (investor relation document cards) */
		#investor-content .qhcd-item { background: #fff; border: 1px solid #e8e8e8; border-radius: 8px; height: 100%; transition: box-shadow 0.3s, transform 0.2s; }
		#investor-content .qhcd-item:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.08); transform: translateY(-2px); }
		#investor-content .qhcd-item-inner { padding: 20px; display: flex; flex-direction: column; height: 100%; }
		#investor-content .qhcd-item-title { font-size: 15px; font-weight: 600; color: #0b1f3a; margin: 0 0 8px 0; line-height: 1.5; font-family: 'Open Sans', sans-serif; }
		#investor-content h3.qhcd-item-title { font-size: 15px; }
		#investor-content .qhcd-item-date { font-size: 13px; color: #888; margin-bottom: 8px; }
		#investor-content .qhcd-item-inner > .mt-2 a { display: inline-flex; align-items: center; gap: 4px; font-size: 13px; font-weight: 600; color: #c9a96e; text-decoration: none; transition: color 0.2s; }
		#investor-content .qhcd-item-inner > .mt-2 a:hover { color: #0b1f3a; }
		#investor-content .qhcd-item-inner > .mt-2 a span::after { content: ' →'; }

		/* Pagination */
		#investor-content .qhcd-pagination { display: flex; justify-content: center; gap: 4px; margin-top: 32px; padding-bottom: 20px; }
		#investor-content .qhcd-pagination a,
		#investor-content .qhcd-pagination span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 4px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; color: #333; text-decoration: none; transition: all 0.2s; }
		#investor-content .qhcd-pagination span.current { background: #0b1f3a; color: #fff; border-color: #0b1f3a; }
		#investor-content .qhcd-pagination a:hover { background: #f5f5f5; border-color: #c9a96e; }

		/* iframe for stock info */
		#investor-content iframe { max-width: 100%; border: none; }

		/* ============================================ */
		/* Page header / Hero banner                    */
		/* ============================================ */
		#investor-content .page-header {
			position: relative;
			background-size: cover;
			background-position: center;
			padding: 80px 0 60px;
			min-height: 320px;
			display: flex;
			align-items: flex-end;
		}
		#investor-content .page-header::before {
			content: '';
			position: absolute;
			inset: 0;
			background: linear-gradient(to right, rgba(11,31,58,0.88), rgba(11,31,58,0.65), rgba(11,31,58,0.3));
			z-index: 1;
		}
		#investor-content .page-header .container { position: relative; z-index: 2; }
		#investor-content .page-header .page-header-inner { max-width: 700px; }
		#investor-content .page-header .page-subtitle {
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.25em;
			color: #c9a96e;
			text-transform: uppercase;
			margin-bottom: 12px;
		}
		#investor-content .page-header .page-title {
			font-family: 'Open Sans', sans-serif;
			font-size: 42px;
			font-weight: 700;
			color: #fff !important;
			line-height: 1.2;
			margin: 0 0 16px 0;
		}
		#investor-content .page-header .header-description p {
			color: rgba(255,255,255,0.85);
			font-size: 16px;
			line-height: 1.7;
			margin: 0;
		}
		@media (max-width: 767px) {
			#investor-content .page-header { padding: 60px 0 40px; min-height: 240px; }
			#investor-content .page-header .page-title { font-size: 28px; }
			#investor-content .page-header .header-description p { font-size: 14px; }
		}

		/* ============================================ */
		/* Header fix: force dark text on non-transparent header */
		/* ============================================ */
		.header:not(.transparent) .header-nav-main .nav-top-link,
		.header:not(.has-transparent) .header-nav-main .nav-top-link {
			color: #0b1f3a !important;
		}
		.header:not(.transparent) .header-nav-main .nav-top-link:hover,
		.header:not(.has-transparent) .header-nav-main .nav-top-link:hover {
			color: #c9a96e !important;
		}
		.header:not(.transparent) .header-button .button.secondary.is-outline,
		.header:not(.has-transparent) .header-button .button.secondary.is-outline {
			color: #0b1f3a !important;
			border-color: #0b1f3a !important;
		}
		.header:not(.transparent) .header-language-dropdown__link,
		.header:not(.has-transparent) .header-language-dropdown__link {
			color: #0b1f3a !important;
		}
		.header:not(.transparent) .header-main,
		.header:not(.has-transparent) .header-main {
			background-color: #fff;
			box-shadow: 0 2px 10px rgba(0,0,0,0.08);
		}
	</style>
<style>

/* Language dropdown - ultra compact */
.header-language-dropdown > #ux-language-dropdown {
	min-width: 0 !important;
	width: auto !important;
	padding: 2px 0 !important;
	box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
	right: 0 !important;
	left: auto !important;
}
.header-language-dropdown > #ux-language-dropdown li a {
	padding: 3px 10px !important;
	font-size: 12px !important;
	text-align: center !important;
}
</style>
<script>(function (html) { html.className = html.className.replace(/\bno-js\b/, 'js') })(document.documentElement);</script>
<script type="text/javascript" src="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/jquery.min.js' ); ?>" id="jquery-core-js"></script>
<script type="text/javascript" src="<?php echo esc_url( get_template_directory_uri() . '/assets/index_files/jquery-migrate.min.js' ); ?>" id="jquery-migrate-js"></script>

<?php
/* ── Inline i18n for this investor sub-page ─────────────────────────── */
$_qp_lang    = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';
$_qp_defs    = function_exists( 'qp_holdings_page_definitions' ) ? qp_holdings_page_definitions() : array();
$_qp_t       = static function ( array $map ) use ( $_qp_lang ): string {
	return $map[ $_qp_lang ] ?? $map['vi'] ?? '';
};
$_hero_title = $_qp_t( array( 'vi' => 'Quan hệ nhà đầu tư', 'en' => 'Investor Relations', 'zh' => '投资者关系' ) );
$_hero_desc  = '';
$_view_details = $_qp_t( array( 'vi' => 'Chi tiết', 'en' => 'View details', 'zh' => '查看详情' ) );
$_next_page    = $_qp_t( array( 'vi' => 'Tới', 'en' => 'Next', 'zh' => '下一页' ) );
$_nav = array(
	'governance'         => $_qp_defs['governance'][ $_qp_lang ]['title']         ?? 'Quản trị công ty',
	'agm'                => $_qp_defs['agm'][ $_qp_lang ]['title']                ?? 'Đại hội cổ đông',
	'annual_reports'     => $_qp_defs['annual_reports'][ $_qp_lang ]['title']     ?? 'Báo cáo thường niên',
	'financial_reports'  => $_qp_defs['financial_reports'][ $_qp_lang ]['title']  ?? 'Báo cáo tài chính',
	'governance_reports' => $_qp_defs['governance_reports'][ $_qp_lang ]['title'] ?? 'Báo cáo quản trị công ty',
	'disclosures'        => $_qp_defs['disclosures'][ $_qp_lang ]['title']        ?? 'Công bố thông tin',
);
$_fallback_cards = array(
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính năm 2025 đã kiểm toán', 'en' => 'Audited Financial Statements 2025',      'zh' => '2025 年已审计财务报告' ) ),    'date' => '30/03/2026' ),
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính quý 4 năm 2025',        'en' => 'Financial Statements Q4/2025',            'zh' => '2025 年第四季度财务报告' ) ),  'date' => '28/01/2026' ),
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính quý 3 năm 2025',        'en' => 'Financial Statements Q3/2025',            'zh' => '2025 年第三季度财务报告' ) ),  'date' => '25/10/2025' ),
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính bán niên năm 2025',     'en' => 'Semi-Annual Financial Statements 2025',   'zh' => '2025 年半年度财务报告' ) ),    'date' => '28/08/2025' ),
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính quý 2 năm 2025',        'en' => 'Financial Statements Q2/2025',            'zh' => '2025 年第二季度财务报告' ) ),  'date' => '28/07/2025' ),
	array( 'title' => $_qp_t( array( 'vi' => 'Báo cáo tài chính quý 1 năm 2025',        'en' => 'Financial Statements Q1/2025',            'zh' => '2025 年第一季度财务报告' ) ),  'date' => '25/04/2025' ),
);
?>

		<div id="investor-content">
			<main id="main" class="">

            <div class="page-header " style="background-image: url(<?php echo esc_url( get_template_directory_uri() . '/assets/downloaded/hero-qhcd-scaled.webp' ); ?>)">
                <div class="container">
                    <div class="row ">
                        <div class="col-lg-10 col-xl-7">
                            <div class="page-header-inner">
                                <div class="page-header-title">
                                    <h1 class="page-title"><?php echo esc_html( $_hero_title ); ?></h1>
                                </div>
                                <?php if ( '' !== $_hero_desc ) : ?>
                                <div class="header-description">
                                    <p><?php echo esc_html( $_hero_desc ); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="top-body-nav">
                <div id="top-body-nav-left-btn" class="btn-nav"><i class="ph ph-caret-left"></i></div>
                <div class="container">
                    <div class="body-navs-horizontal">
                        <div class="body-nav-item ">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'governance' ) ); ?>" title="<?php echo esc_attr( $_nav['governance'] ); ?>"><?php echo esc_html( $_nav['governance'] ); ?></a>
                        </div>
                        <div class="body-nav-item ">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'agm' ) ); ?>" title="<?php echo esc_attr( $_nav['agm'] ); ?>"><?php echo esc_html( $_nav['agm'] ); ?></a>
                        </div>
                        <div class="body-nav-item ">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'annual_reports' ) ); ?>" title="<?php echo esc_attr( $_nav['annual_reports'] ); ?>"><?php echo esc_html( $_nav['annual_reports'] ); ?></a>
                        </div>
                        <div class="body-nav-item active">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'financial_reports' ) ); ?>" title="<?php echo esc_attr( $_nav['financial_reports'] ); ?>"><?php echo esc_html( $_nav['financial_reports'] ); ?></a>
                        </div>
                        <div class="body-nav-item ">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'governance_reports' ) ); ?>" title="<?php echo esc_attr( $_nav['governance_reports'] ); ?>"><?php echo esc_html( $_nav['governance_reports'] ); ?></a>
                        </div>
                        <div class="body-nav-item ">
                            <a href="<?php echo esc_url( qp_holdings_page_url( 'disclosures' ) ); ?>" title="<?php echo esc_attr( $_nav['disclosures'] ); ?>"><?php echo esc_html( $_nav['disclosures'] ); ?></a>
                        </div>
                    </div>
                </div>
                <div id="top-body-nav-right-btn" class="btn-nav"><i class="ph ph-caret-right"></i></div>
            </div>

            <div id="main-content" class="">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-3">
                            <div id="left-sidebar">
                                <div class="is-sticky-column">
                                    <div class="is-sticky-column__inner">
                                        <div class="sidebar-page-title-wrap d-none d-lg-flex"
                                            style="background-image:url(<?php echo esc_url( get_template_directory_uri() . '/assets/downloaded/Frame-1321317738.webp' ); ?>)">
                                            <div class="sidebar-page-title">
                                                <h2><?php echo esc_html( $_nav['financial_reports'] ); ?></h2>
                                            </div>
                                        </div>
                                        <div class="sidebar-nav"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-9 right-col mt-3 mt-lg-0">
<?php if ( ! function_exists( 'qp_holdings_render_investor_documents' ) || ! qp_holdings_render_investor_documents() ) : ?>
                            <div class="row">
                                <?php foreach ( $_fallback_cards as $_card ) : ?>
                                <div class="col-md-6 col-xl-6 mb-3">
                                    <div class="qhcd-item">
                                        <div class="qhcd-item-inner">
                                            <h3 class="qhcd-item-title"><?php echo esc_html( $_card['title'] ); ?></h3>
                                            <div class="qhcd-item-date"><?php echo esc_html( $_card['date'] ); ?></div>
                                            <div class="mt-2">
                                                <a class="" href="#" title="<?php echo esc_attr( $_card['title'] ); ?>">
                                                    <span><?php echo esc_html( $_view_details ); ?></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-3">
                                <ul class="page-numbers nav-pagination links">
                                    <li><span aria-current="page" class="page-number current">1</span></li>
                                    <li><a class="page-number" href="#">2</a></li>
                                    <li><a class="next page-number" href="#"><?php echo esc_html( $_next_page ); ?> <i class="ph ph-caret-right"></i></a></li>
                                </ul>
                            </div>
                        <?php endif; ?>
</div>
                    </div>
                </div>
            </div>

			</main>
		</div>

<?php get_footer(); ?>
