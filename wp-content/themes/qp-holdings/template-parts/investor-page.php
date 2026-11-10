<?php
/**
 * Shared template for investor sub-pages.
 *
 * @param array $args {
 *     @type string $page_key Investor page key from qp_holdings_page_definitions().
 *     @type string $section  Investor document section slug.
 * }
 */
defined('ABSPATH') || exit;

$args = wp_parse_args(
	$args ?? array(),
	array(
		'page_key' => 'stock',
		'section' => 'stock-info',
	)
);

$qp_assets = get_template_directory_uri() . '/assets/site';
$qp_lang = function_exists('qp_holdings_current_language') ? qp_holdings_current_language() : 'vi';
$qp_defs = function_exists('qp_holdings_page_definitions') ? qp_holdings_page_definitions() : array();
$qp_t = static function (array $map) use ($qp_lang): string {
	return $map[$qp_lang] ?? $map['vi'] ?? '';
};

$qp_page_key = (string) $args['page_key'];
$qp_section = (string) $args['section'];

$qp_hero_title = $qp_t(
	array(
		'vi' => 'Quan hệ nhà đầu tư',
		'en' => 'Investor Relations',
		'zh' => '投资者关系',
	)
);
$qp_hero_desc_key = 'investor_hero_desc';
$qp_page_id       = get_queried_object_id();
$qp_custom_desc   = $qp_page_id ? trim( (string) get_post_field( 'post_excerpt', $qp_page_id ) ) : '';
$qp_current_title = $qp_defs[$qp_page_key][$qp_lang]['title'] ?? get_the_title();
$qp_home_label = function_exists('qp_holdings_t') ? qp_holdings_t('home') : 'Trang chủ';

$qp_tabs = array(
	'stock',
	'governance',
	'agm',
	'annual_reports',
	'financial_reports',
	'governance_reports',
	'disclosures',
	'newsletter',
);

$qp_doc_titles = array(
	'stock' => function_exists('qp_holdings_t') ? qp_holdings_t('stock_disclosure_title') : 'Thông tin công bố',
	'governance' => $qp_defs['governance'][$qp_lang]['title'] ?? 'Quản trị công ty',
	'agm' => $qp_defs['agm'][$qp_lang]['title'] ?? 'Đại hội đồng cổ đông',
	'annual_reports' => $qp_defs['annual_reports'][$qp_lang]['title'] ?? 'Báo cáo thường niên',
	'financial_reports' => $qp_defs['financial_reports'][$qp_lang]['title'] ?? 'Báo cáo tài chính',
	'governance_reports' => $qp_defs['governance_reports'][$qp_lang]['title'] ?? 'Báo cáo quản trị công ty',
	'disclosures' => $qp_defs['disclosures'][$qp_lang]['title'] ?? 'Công bố thông tin',
	'newsletter' => $qp_defs['newsletter'][$qp_lang]['title'] ?? 'Bản tin nhà đầu tư',
);

$qp_get_stock_meta = static function (string $key, string $default) {
	if ( function_exists( 'get_field' ) ) {
		$lang = function_exists('qp_holdings_current_language') ? qp_holdings_current_language() : 'vi';
		$val = get_field( $key . '_' . $lang );
		if ( $val !== null && $val !== '' ) {
			return (string) $val;
		}
	}
	return $default;
};

$qp_format_percent = static function (float $value): string {
	return abs( $value ) < 0.005 ? '0' : number_format( $value, 2, '.', '' );
};

$qp_parse_percent = static function (string $value, float $default): float {
	$value = trim( $value );
	if ( '' === $value ) {
		return $default;
	}

	$value = str_replace( array( '%', ' ' ), '', $value );
	$value = str_replace( ',', '.', $value );
	if ( ! is_numeric( $value ) ) {
		return $default;
	}

	return max( 0, min( 100, (float) $value ) );
};

$qp_foreign_limit_value = trim( (string) $qp_get_stock_meta( 'foreign_limit', '0%' ) );
$qp_foreign_limit_value = preg_replace( '/\s+/', '', $qp_foreign_limit_value ) ?: '0';
if ( preg_match( '/^0(?:[.,]0+)?%?$/', $qp_foreign_limit_value ) ) {
	$qp_foreign_limit_value = '0%';
}

$qp_foreign_investor_ratio = $qp_parse_percent( $qp_get_stock_meta( 'foreign_investor_ratio', '0' ), 0 );
$qp_domestic_investor_ratio = $qp_parse_percent( $qp_get_stock_meta( 'domestic_investor_ratio', '100' ), 100 );

$qp_stock_metrics = array(
	array(
		'label' => $qp_t(array('vi' => 'Mệnh giá cổ phiếu', 'en' => 'Par value', 'zh' => '股票面值')),
		'value' => $qp_get_stock_meta('par_value', $qp_t(array('vi' => '10.000 đồng/cổ phiếu', 'en' => 'VND 10,000/share', 'zh' => '10,000 越盾/股'))),
		'icon' => 'par-value',
		'shape' => 'left-top',
	),
	array(
		'label' => $qp_t(array('vi' => 'Số lượng cổ phiếu quỹ', 'en' => 'Treasury shares', 'zh' => '库存股数量')),
		'value' => $qp_get_stock_meta('treasury_shares', $qp_t(array('vi' => '0 cổ phiếu', 'en' => '0 shares', 'zh' => '0 股'))),
		'icon' => 'treasury',
		'shape' => 'left-bottom',
	),
	array(
		'label' => $qp_t(array('vi' => 'Số lượng cổ phiếu đang lưu hành', 'en' => 'Outstanding shares', 'zh' => '流通股数量')),
		'value' => $qp_get_stock_meta('outstanding_shares', '33.335.029'),
		'icon' => 'outstanding',
		'shape' => 'right-top',
	),
	array(
		'label' => $qp_t(array('vi' => 'Tỷ lệ sở hữu nước ngoài tối đa', 'en' => 'Maximum foreign ownership ratio', 'zh' => '外资持股比例上限')),
		'value' => $qp_foreign_limit_value,
		'icon' => 'foreign',
		'shape' => 'right-bottom',
		'label_color' => '#FFFFFF',
	),
);

$qp_stock_icon_svg = static function (string $icon): string {
	$icons = array(
		'charter-capital' => <<<'SVG'
<svg class="investor-stock-icon-svg" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
<path d="M20.9427 16.8354C20.2864 12.8866 18.2432 9.94613 16.467 8.219C15.9501 7.71642 15.6917 7.46513 15.1208 7.23257C14.5499 7 14.0592 7 13.0778 7H10.9222C9.94081 7 9.4501 7 8.87922 7.23257C8.30834 7.46513 8.04991 7.71642 7.53304 8.219C5.75682 9.94613 3.71361 12.8866 3.05727 16.8354C2.56893 19.7734 5.27927 22 8.30832 22H15.6917C18.7207 22 21.4311 19.7734 20.9427 16.8354Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M7.25662 4.44287C7.05031 4.14258 6.75128 3.73499 7.36899 3.64205C8.00392 3.54651 8.66321 3.98114 9.30855 3.97221C9.89237 3.96413 10.1898 3.70519 10.5089 3.33548C10.8449 2.94617 11.3652 2 12 2C12.6348 2 13.1551 2.94617 13.4911 3.33548C13.8102 3.70519 14.1076 3.96413 14.6914 3.97221C15.3368 3.98114 15.9961 3.54651 16.631 3.64205C17.2487 3.73499 16.9497 4.14258 16.7434 4.44287L15.8105 5.80064C15.4115 6.38146 15.212 6.67187 14.7944 6.83594C14.3769 7 13.8373 7 12.7582 7H11.2418C10.1627 7 9.6231 7 9.20556 6.83594C8.78802 6.67187 8.5885 6.38146 8.18945 5.80064L7.25662 4.44287Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
<path d="M13.6267 12.9186C13.4105 12.1205 12.3101 11.4003 10.9892 11.9391C9.66829 12.4778 9.45847 14.2113 11.4565 14.3955C12.3595 14.4787 12.9483 14.2989 13.4873 14.8076C14.0264 15.3162 14.1265 16.7308 12.7485 17.112C11.3705 17.4932 10.006 16.8976 9.85742 16.0517M11.8417 10.9927V11.7531M11.8417 17.2293V17.9927" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
SVG,
		'par-value' => <<<'SVG'
<svg class="investor-stock-icon-svg" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
<path d="M11.4745 20.5V18.852C8.68543 18.695 6.67692 17.2923 6.51041 15.0263L6.5 14.8889H8.92479L8.9456 14.9968C9.13292 15.9778 9.8614 16.7331 11.4745 16.8901V12.9368L10.8917 12.8191C8.1859 12.2501 6.77058 11.0828 6.77058 9.05222V9.0326C6.77058 6.80583 8.69584 5.31477 11.4745 5.14801V3.5H12.5984V5.14801C15.2938 5.32458 17.1254 6.73716 17.3127 8.93451L17.3231 9.09146H14.8983L14.8879 9.00317C14.7318 7.93393 13.8888 7.26688 12.5984 7.11973V10.916L13.2332 11.0436C16.0535 11.642 17.5 12.7308 17.5 14.8301V14.8497C17.5 17.2825 15.4082 18.695 12.5984 18.852V20.5H11.4745ZM9.27862 8.87565C9.27862 9.73889 9.88221 10.3373 11.4745 10.6806V7.10993C10.0279 7.25707 9.27862 8.02222 9.27862 8.86584V8.87565ZM15.0024 15.0459C15.0024 14.1238 14.4612 13.5744 12.5984 13.1722V16.8999C14.2947 16.7822 15.0024 16.0366 15.0024 15.0655V15.0459Z" fill="currentColor"/>
</svg>
SVG,
		'treasury' => <<<'SVG'
<svg class="investor-stock-icon-svg" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
<path d="M18 8.75556V6.8C18 4.53726 18 3.40589 17.2247 2.70294C16.4494 2 15.2016 2 12.7059 2H8.29412C5.79845 2 4.55061 2 3.77531 2.70294C3 3.40589 3 4.53726 3 6.8V13.2C3 15.4627 3 16.5941 3.77531 17.2971C4.55061 18 5.79845 18 8.29412 18H12.7059" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
<path d="M6 6H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M6 10H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M10 10H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M14 10H15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M6 14H7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M10 14H11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M20.7057 15.0043C20.4315 14.3089 19.7265 13.4939 18.1202 13.4939C16.2537 13.4939 15.4684 14.349 15.309 14.8061C15.0604 15.4174 15.0369 16.7356 17.2978 16.8114C19.9979 16.9019 21.1279 17.2682 20.9886 18.7482C20.8492 20.2282 19.293 20.4336 18.1201 20.5136C16.9153 20.4794 15.4252 20.2274 15 18.9491M17.9938 12V13.4364M18.0025 20.5089V22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
</svg>
SVG,
		'outstanding' => <<<'SVG'
<svg class="investor-stock-icon-svg" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
<path d="M5.48092 17.1906L2.60283 20.2902C2.59415 20.2996 2.58894 20.312 2.58894 20.3263V21.6709C2.58894 21.7021 2.60109 21.7301 2.62018 21.75H5.53183C5.5515 21.7301 5.56364 21.7021 5.56364 21.6709L5.56307 17.2274C5.56307 17.1962 5.53935 17.1757 5.51447 17.1757C5.50232 17.1757 5.49017 17.1807 5.48092 17.1906Z" fill="currentColor"/>
<path d="M13.6987 17.4628L11.8902 19.4096C11.8717 19.4295 11.8411 19.4295 11.8226 19.4096L10.8888 18.4044C10.8582 18.3714 10.8067 18.3951 10.8067 18.4406V21.6709C10.8067 21.7021 10.8183 21.7301 10.8379 21.75H13.7496C13.7692 21.7301 13.7808 21.7021 13.7808 21.6709V17.4989C13.7808 17.4678 13.7565 17.4472 13.7322 17.4472C13.7201 17.4472 13.7085 17.4522 13.6987 17.4628Z" fill="currentColor"/>
<path d="M7.51149 15.0047L6.71141 15.866C6.70215 15.8753 6.69695 15.8884 6.69695 15.9021L6.69752 21.6709C6.69752 21.7021 6.70967 21.7301 6.72876 21.75H9.64041C9.66008 21.7301 9.67223 21.7021 9.67223 21.6709L9.67165 17.1159C9.67165 17.1022 9.66702 17.0891 9.65719 17.0791L7.73017 15.0047C7.70008 14.9716 7.66075 14.9555 7.62083 14.9555C7.58091 14.9555 7.54157 14.9716 7.51149 15.0047Z" fill="currentColor"/>
<path d="M17.8072 13.0385L14.9297 16.1375C14.9205 16.1475 14.9153 16.1599 14.9153 16.1742V21.6709C14.9153 21.7021 14.9274 21.7301 14.9471 21.75H17.8587C17.8778 21.7301 17.89 21.7021 17.89 21.6709L17.8894 13.0759C17.8894 13.0441 17.8657 13.0236 17.8408 13.0236C17.8287 13.0236 17.8171 13.0286 17.8072 13.0385Z" fill="currentColor"/>
<path d="M20.1219 10.5468L19.0383 11.7139C19.0285 11.7232 19.0239 11.7363 19.0239 11.7506L19.0244 21.6709C19.0244 21.7021 19.0366 21.7301 19.0557 21.75H21.9679C21.987 21.7301 21.9991 21.7021 21.9991 21.6709L21.998 12.3522C21.998 12.3379 21.9934 12.3254 21.9841 12.3148L20.3417 10.5468C20.3116 10.5144 20.2717 10.4976 20.2318 10.4976C20.1924 10.4976 20.1525 10.5144 20.1219 10.5468Z" fill="currentColor"/>
<path d="M22.4284 2.26373L17.2044 3.34613C16.8209 3.42522 16.6757 3.93404 16.9516 4.23173L17.7453 5.08557C17.9571 5.31351 17.9571 5.68344 17.7453 5.912L12.3496 11.7207C12.0777 12.014 11.6351 12.014 11.3632 11.7207L8.14612 8.25803C7.8557 7.94539 7.38537 7.94539 7.09554 8.25803L0.967375 14.8552C0.677542 15.1672 0.677542 15.6735 0.967375 15.9862L2.02027 17.119C2.3101 17.4316 2.78101 17.4316 3.07084 17.119L7.12736 12.7514C7.39984 12.4581 7.84182 12.4581 8.1143 12.7514L11.3308 16.2153C11.6212 16.5267 12.0916 16.5267 12.3814 16.2153L19.8488 8.17582C20.0605 7.94726 20.4036 7.94726 20.6159 8.17582L21.5045 9.13304C21.7405 9.38714 22.1426 9.25324 22.2062 8.90137L23.2372 3.13438C23.321 2.66667 22.9826 2.25003 22.5614 2.25003C22.5181 2.25003 22.4735 2.25439 22.4284 2.26373Z" fill="currentColor"/>
</svg>
SVG,
		'foreign' => <<<'SVG'
<svg class="investor-stock-icon-svg" width="40" height="40" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
<path d="M9.11008 5.07999C9.98008 4.81999 10.9401 4.64999 12.0001 4.64999C16.7901 4.64999 20.6701 8.52999 20.6701 13.32C20.6701 18.11 16.7901 21.99 12.0001 21.99C7.21008 21.99 3.33008 18.11 3.33008 13.32C3.33008 11.54 3.87008 9.87999 4.79008 8.49999" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M7.87012 5.32L10.7601 2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M7.87012 5.32001L11.2401 7.78001" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"/>
<path d="M11.7133 19V18.1275C10.1921 18.0444 9.0965 17.3018 9.00568 16.1021L9 16.0294H10.3226L10.334 16.0866C10.4361 16.6059 10.8335 17.0058 11.7133 17.0889V14.996L11.3955 14.9336C9.91958 14.6324 9.14759 14.0144 9.14759 12.9394V12.929C9.14759 11.7501 10.1977 10.9608 11.7133 10.8725V10H12.3264V10.8725C13.7966 10.966 14.7956 11.7138 14.8978 12.8771L14.9035 12.9602H13.5809L13.5752 12.9134C13.4901 12.3474 13.0303 11.9942 12.3264 11.9163V13.9261L12.6727 13.9937C14.211 14.3104 15 14.8869 15 15.9983V16.0087C15 17.2966 13.859 18.0444 12.3264 18.1275V19H11.7133ZM10.5156 12.8459C10.5156 13.3029 10.8448 13.6197 11.7133 13.8015V11.9111C10.9243 11.989 10.5156 12.3941 10.5156 12.8407V12.8459ZM13.6377 16.1125C13.6377 15.6244 13.3425 15.3335 12.3264 15.1206V17.0941C13.2517 17.0317 13.6377 16.637 13.6377 16.1229V16.1125Z" fill="currentColor"/>
</svg>
SVG,
	);

	return $icons[$icon] ?? '';
};

$qp_stock_metric_shape_svg = static function (string $shape): string {
	$shapes = array(
		'left-top' => <<<'SVG'
<svg class="investor-stock-metric__shape" width="496" height="120" viewBox="0 0 496 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
<path d="M42.3295 0L496.001 0L423.583 119.463H42.3295L0 59.7316L42.3295 0Z" fill="url(#qp-investor-card-left-top)"/>
<defs><linearGradient id="qp-investor-card-left-top" x1="0" y1="59.7316" x2="496.001" y2="59.7316" gradientUnits="userSpaceOnUse"><stop stop-color="#0B1B2A"/><stop offset="1" stop-color="#265D90"/></linearGradient></defs>
</svg>
SVG,
		'left-bottom' => <<<'SVG'
<svg class="investor-stock-metric__shape" width="496" height="120" viewBox="0 0 496 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
<path d="M42.3295 119.463L496.001 119.463L423.583 0.000251847H42.3295L0 59.7318L42.3295 119.463Z" fill="url(#qp-investor-card-left-bottom)"/>
<defs><linearGradient id="qp-investor-card-left-bottom" x1="0" y1="59.7318" x2="496.001" y2="59.7318" gradientUnits="userSpaceOnUse"><stop stop-color="#0B1B2A"/><stop offset="1" stop-color="#265D90"/></linearGradient></defs>
</svg>
SVG,
		'right-top' => <<<'SVG'
<svg class="investor-stock-metric__shape" width="496" height="120" viewBox="0 0 496 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
<path d="M453.671 0L-0.000580012 0L72.4173 119.463H453.671L496 59.7316L453.671 0Z" fill="url(#qp-investor-card-right-top)"/>
<defs><linearGradient id="qp-investor-card-right-top" x1="496" y1="59.7316" x2="-0.000580012" y2="59.7316" gradientUnits="userSpaceOnUse"><stop stop-color="#0B1B2A"/><stop offset="1" stop-color="#265D90"/></linearGradient></defs>
</svg>
SVG,
		'right-bottom' => <<<'SVG'
<svg class="investor-stock-metric__shape" width="496" height="120" viewBox="0 0 496 120" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
<path d="M453.671 119.463L-0.000580012 119.463L72.4173 0.000183162H453.671L496 59.7318L453.671 119.463Z" fill="url(#qp-investor-card-right-bottom)"/>
<defs><linearGradient id="qp-investor-card-right-bottom" x1="496" y1="59.7318" x2="-0.000580012" y2="59.7318" gradientUnits="userSpaceOnUse"><stop stop-color="#0B1B2A"/><stop offset="1" stop-color="#265D90"/></linearGradient></defs>
</svg>
SVG,
	);

	return $shapes[$shape] ?? '';
};

$qp_stock_capital_shape_svg = <<<'SVG'
<svg class="investor-stock-capital__shape" width="290" height="252" viewBox="0 0 290 252" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false" preserveAspectRatio="none">
<path d="M290 125.573L217.5 251.147L72.5 251.147L-6.33815e-06 125.573L72.5 -0.000452012L217.5 -0.000445674L290 125.573Z" fill="url(#qp-investor-capital-card)"/>
<defs><linearGradient id="qp-investor-capital-card" x1="154.879" y1="-29.646" x2="156.014" y2="271.255" gradientUnits="userSpaceOnUse"><stop stop-color="#D3A049"/><stop offset="1" stop-color="#FFDDA4"/></linearGradient></defs>
</svg>
SVG;

$qp_shareholder_defaults = array(
	array(
		'name'   => $qp_t(array('vi' => 'Vi Thị Dung', 'en' => 'Vi Thị Dung', 'zh' => 'Vi Thị Dung')),
		'value'  => 60.30,
		'shares' => '20,100,000',
		'color'  => '#3565CC',
	),
	array(
		'name'   => $qp_t(array('vi' => 'Dương Thị Chín', 'en' => 'Dương Thị Chín', 'zh' => 'Dương Thị Chín')),
		'value'  => 21.00,
		'shares' => '7,000,000',
		'color'  => '#F4B842',
	),
	array(
		'name'   => $qp_t(array('vi' => 'Khác', 'en' => 'Other', 'zh' => '其他')),
		'value'  => 18.70,
		'shares' => '6,233,630',
		'color'  => '#B5B5B6',
	),
	array(
		'name'   => $qp_t(array('vi' => '', 'en' => '', 'zh' => '')),
		'value'  => 0,
		'shares' => '',
		'color'  => '#5BA3D0',
	),
	array(
		'name'   => $qp_t(array('vi' => '', 'en' => '', 'zh' => '')),
		'value'  => 0,
		'shares' => '',
		'color'  => '#E07A5F',
	),
	array(
		'name'   => $qp_t(array('vi' => '', 'en' => '', 'zh' => '')),
		'value'  => 0,
		'shares' => '',
		'color'  => '#81B29A',
	),
	array(
		'name'   => $qp_t(array('vi' => '', 'en' => '', 'zh' => '')),
		'value'  => 0,
		'shares' => '',
		'color'  => '#9B5DE5',
	),
	array(
		'name'   => $qp_t(array('vi' => '', 'en' => '', 'zh' => '')),
		'value'  => 0,
		'shares' => '',
		'color'  => '#C9A227',
	),
);

$qp_shareholders = array();
foreach ( $qp_shareholder_defaults as $qp_index => $qp_default_shareholder ) {
	$qp_shareholder_number = $qp_index + 1;
	$qp_shareholders[] = array(
		'name'   => $qp_get_stock_meta( 'shareholder_' . $qp_shareholder_number . '_name', $qp_default_shareholder['name'] ),
		'value'  => $qp_parse_percent( $qp_get_stock_meta( 'shareholder_' . $qp_shareholder_number . '_ratio', (string) $qp_default_shareholder['value'] ), (float) $qp_default_shareholder['value'] ),
		'shares' => $qp_get_stock_meta( 'shareholder_' . $qp_shareholder_number . '_shares', $qp_default_shareholder['shares'] ),
		'color'  => $qp_default_shareholder['color'],
	);
}

$qp_shareholders = array_values( array_filter( $qp_shareholders, static function ( $qp_s ) {
	return '' !== trim( (string) $qp_s['name'] ) && (float) $qp_s['value'] > 0;
} ) );

$qp_investor_types = array(
	array(
		'label' => $qp_t(array('vi' => 'Nhà đầu tư nước ngoài', 'en' => 'Foreign investors', 'zh' => '外国投资者')),
		'value' => $qp_foreign_investor_ratio,
		'color' => '#3565CC',
	),
	array(
		'label' => $qp_t(array('vi' => 'Nhà đầu tư trong nước', 'en' => 'Domestic investors', 'zh' => '国内投资者')),
		'value' => $qp_domestic_investor_ratio,
		'color' => '#F4B842',
	),
);
?>
<main class="investor-page investor-page--<?php echo esc_attr($qp_page_key); ?>">

	<section class="page-hero investor-hero" aria-label="<?php echo esc_attr($qp_hero_title); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'investor', 'assets/site/about-hero-bg-62c368.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url(home_url('/')); ?>"
					class="page-breadcrumb-link"><?php echo esc_html($qp_home_label); ?></a>
				<img src="<?php echo esc_url($qp_assets . '/breadcrumb-arrow.svg'); ?>" alt="" width="20" height="20"
					aria-hidden="true">
				<a href="<?php echo esc_url(function_exists('qp_holdings_page_url') ? qp_holdings_page_url('investor') : home_url('/')); ?>"
					class="page-breadcrumb-link"><?php echo esc_html($qp_hero_title); ?></a>
				<img src="<?php echo esc_url($qp_assets . '/breadcrumb-arrow.svg'); ?>" alt="" width="20" height="20"
					aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html($qp_current_title); ?></span>
			</nav>
		</div>

		<div class="container page-hero-content page-hero-content--centered">
			<h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1">
				<?php echo esc_html($qp_hero_title); ?></h1>
			<?php
			$qp_hero_desc_text = ( '' !== $qp_custom_desc ) ? $qp_custom_desc : ( ( '' !== $qp_hero_desc_key && function_exists( 'qp_holdings_t' ) ) ? qp_holdings_t( $qp_hero_desc_key ) : '' );
			if ( '' !== $qp_hero_desc_text ) :
			?>
				<p class="page-hero-desc reveal reveal-delay-2"<?php if ( '' === $qp_custom_desc && '' !== $qp_hero_desc_key ) { qp_holdings_te_attrs( $qp_hero_desc_key, array( 'multiline' => true ) ); } ?>><?php echo esc_html( $qp_hero_desc_text ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<nav class="investor-tabs-wrap" aria-label="<?php echo esc_attr($qp_hero_title); ?>">
		<div class="container">
			<div class="investor-tabs">
				<button class="investor-tabs-mobile-trigger" id="investor-tabs-mobile-trigger"
					aria-expanded="false" aria-controls="investor-tabs-mobile-panel" type="button">
					<span class="investor-tabs-mobile-trigger__label"><?php echo esc_html($qp_current_title); ?></span>
					<svg class="investor-tabs-mobile-trigger__arrow" width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
						<path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>

				<div class="investor-tabs-list" id="investor-tabs-mobile-panel">
					<?php foreach ($qp_tabs as $qp_tab_key): ?>
						<?php
						$qp_tab_title = $qp_defs[$qp_tab_key][$qp_lang]['title'] ?? $qp_tab_key;
						$qp_tab_url = function_exists('qp_holdings_page_url') ? qp_holdings_page_url($qp_tab_key) : home_url('/');
						$qp_active = $qp_page_key === $qp_tab_key;
						$is_ext = false;
						if ( 'newsletter' === $qp_tab_key && ! empty( $qp_tab_url ) && 0 === strpos( $qp_tab_url, 'http' ) ) {
							$tab_host  = wp_parse_url( $qp_tab_url, PHP_URL_HOST );
							$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
							if ( $tab_host && $site_host && strtolower( $tab_host ) !== strtolower( $site_host ) ) {
								$is_ext = true;
							}
						}
						?>
						<a class="investor-tab<?php echo $qp_active ? ' is-active' : ''; ?>"
							href="<?php echo esc_url($qp_tab_url); ?>" <?php echo $qp_active ? ' aria-current="page"' : ''; ?><?php echo $is_ext ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
							<?php echo esc_html($qp_tab_title); ?>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</nav>


	<section class="investor-content" aria-label="<?php echo esc_attr($qp_current_title); ?>">
		<div class="container">
			<?php if ('stock' === $qp_page_key): ?>
				<section class="investor-stock-overview" aria-label="<?php echo esc_attr($qp_current_title); ?>">
					<div class="investor-stock-metrics investor-stock-metrics--left">
						<?php foreach (array_slice($qp_stock_metrics, 0, 2) as $qp_metric): ?>
							<article class="investor-stock-metric">
								<?php echo $qp_stock_metric_shape_svg($qp_metric['shape']); ?>
								<div class="investor-stock-metric__icon" aria-hidden="true">
									<?php echo $qp_stock_icon_svg($qp_metric['icon']); ?>
								</div>
								<div class="investor-stock-metric__text">
									<p<?php echo ! empty( $qp_metric['label_color'] ) ? ' style="color: ' . esc_attr( $qp_metric['label_color'] ) . ';"' : ''; ?>><?php echo esc_html($qp_metric['label']); ?></p>
									<strong class="stat-value"><?php echo esc_html($qp_metric['value']); ?></strong>
								</div>
							</article>
						<?php endforeach; ?>
					</div>

					<div class="investor-stock-capital">
						<?php echo $qp_stock_capital_shape_svg; ?>
						<div class="investor-stock-capital__icon" aria-hidden="true">
							<?php echo $qp_stock_icon_svg('charter-capital'); ?>
						</div>
						<div class="investor-stock-capital__text">
							<p><?php echo esc_html($qp_t(array('vi' => 'Vốn điều lệ', 'en' => 'Charter capital', 'zh' => '注册资本'))); ?></p>
							<?php
							$qp_capital_raw = $qp_get_stock_meta('charter_capital', $qp_t(array('vi' => '333.350.290.000 đồng', 'en' => 'VND 333,350,290,000', 'zh' => '333,350,290,000 越盾')));
							/*
							 * Keep only the numeric core (digits + grouping separators) on the
							 * value line. The currency unit — whether it leads (EN "VND") or
							 * trails (VI "đồng", ZH "越盾") — is moved up to its own line above
							 * the number so the long figure sits alone inside the gold hexagon.
							 */
							$qp_capital_number   = $qp_capital_raw;
							$qp_capital_currency = '';
							if ( preg_match( '/^\s*(.*?)([\d.,]+)\s*(.*?)\s*$/u', $qp_capital_raw, $qp_cap_m ) ) {
								$qp_capital_number   = trim( $qp_cap_m[2] );
								$qp_capital_currency = trim( '' !== trim( $qp_cap_m[1] ) ? $qp_cap_m[1] : $qp_cap_m[3] );
							}
							?>
							<strong class="stat-value"><?php echo esc_html( $qp_capital_number ); ?></strong>
							<?php if ( '' !== $qp_capital_currency ) : ?>
								<span class="investor-stock-capital__currency"><?php echo esc_html( $qp_capital_currency ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="investor-stock-metrics investor-stock-metrics--right">
						<?php foreach (array_slice($qp_stock_metrics, 2) as $qp_metric): ?>
							<article class="investor-stock-metric investor-stock-metric--reverse">
								<?php echo $qp_stock_metric_shape_svg($qp_metric['shape']); ?>
								<div class="investor-stock-metric__text">
									<p<?php echo ! empty( $qp_metric['label_color'] ) ? ' style="color: ' . esc_attr( $qp_metric['label_color'] ) . ';"' : ''; ?>><?php echo esc_html($qp_metric['label']); ?></p>
									<strong class="stat-value"><?php echo esc_html($qp_metric['value']); ?></strong>
								</div>
								<div class="investor-stock-metric__icon" aria-hidden="true">
									<?php echo $qp_stock_icon_svg($qp_metric['icon']); ?>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="investor-stock-card" aria-labelledby="investor-shareholders-title">
					<div class="investor-section-title-wrap">
						<h2 id="investor-shareholders-title" class="investor-section-title">
							<?php echo esc_html($qp_t(array('vi' => 'Danh sách các Cổ đông của chúng tôi', 'en' => 'Our Shareholders', 'zh' => '我们的股东名单'))); ?>
						</h2>
					</div>
					<div class="investor-shareholder-grid">
						<article class="investor-shareholder-chart-card">
							<div class="investor-shareholder-chart-card__chart"
								data-qp-chart="shareholders"
								data-qp-chart-data='<?php echo esc_attr(wp_json_encode($qp_shareholders)); ?>'>
								<canvas role="img" width="480" height="480"
									aria-label="<?php echo esc_attr($qp_t(array('vi' => 'Biểu đồ cơ cấu cổ đông', 'en' => 'Shareholder structure chart', 'zh' => '股东结构图'))); ?>"></canvas>
							</div>
							<ul class="investor-shareholder-chart-card__legend">
								<?php foreach ($qp_shareholders as $qp_shareholder): ?>
									<li>
										<span class="dot" aria-hidden="true"
											style="background-color: <?php echo esc_attr($qp_shareholder['color']); ?>;"></span>
										<span class="name"><?php echo esc_html($qp_shareholder['name']); ?></span>
										<span class="pct">(<?php echo esc_html($qp_format_percent((float) $qp_shareholder['value'])); ?>%)</span>
									</li>
								<?php endforeach; ?>
							</ul>
						</article>

						<article class="investor-shareholder-types-card">
							<div class="investor-shareholder-types-card__chart"
								data-qp-chart="investor-types"
								data-qp-chart-data='<?php echo esc_attr(wp_json_encode($qp_investor_types)); ?>'>
								<canvas role="img" width="104" height="104"
									aria-label="<?php echo esc_attr($qp_t(array('vi' => 'Tỷ lệ nhà đầu tư trong nước và nước ngoài', 'en' => 'Domestic vs foreign investor ratio', 'zh' => '国内外投资者比例'))); ?>"></canvas>
							</div>
							<ul class="investor-shareholder-types-card__legend">
								<?php foreach ($qp_investor_types as $qp_type): ?>
									<li>
										<span class="dot" aria-hidden="true"
											style="background-color: <?php echo esc_attr($qp_type['color']); ?>;"></span>
										<span class="label"><?php echo esc_html($qp_type['label']); ?></span>
										<span class="value"><?php echo esc_html($qp_format_percent((float) $qp_type['value'])); ?>%</span>
									</li>
								<?php endforeach; ?>
							</ul>
						</article>
					</div>
				</section>
			<?php else: ?>
				<?php
				if ($qp_section && function_exists('qp_holdings_render_investor_documents')) {
					qp_holdings_render_investor_documents($qp_section);
				}
				?>
			<?php endif; ?>
		</div>
	</section>

</main>
