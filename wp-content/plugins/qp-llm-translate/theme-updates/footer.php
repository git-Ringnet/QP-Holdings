<?php defined( 'ABSPATH' ) || exit; ?>
<?php
$qp_assets   = get_template_directory_uri() . '/assets/site';
$qp_nav_url = function ( string $key ): string {
	if ( function_exists( 'qp_holdings_page_url' ) ) {
		return qp_holdings_page_url( $key );
	}
	$paths = array(
		'home'     => '/',
		'about'    => '/gioi-thieu/',
		'projects' => '/du-an/',
		'news'     => '/tintuc/',
		'careers'  => '/tuyen-dung/',
		'faq'      => '/cau-hoi-thuong-gap/',
		'contact'  => '/lien-he/',
		'terms'    => '/dieu-khoan-su-dung/',
		'privacy'  => '/chinh-sach-bao-mat/',
	);
	return home_url( $paths[ $key ] ?? '/' );
};
?>

<!-- ═══ FOOTER ═══ -->
<footer class="site-footer" id="contact" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'contact_section_aria' ) : 'Thông tin liên hệ và biểu mẫu' ); ?>">
	<div class="container footer-container">

		<div class="footer-main">

			<!-- Row 1: Logo + Newsletter -->
			<div class="footer-top">
				<a href="<?php echo esc_url( $qp_nav_url( 'home' ) ); ?>" class="footer-logo" aria-label="<?php echo esc_attr( qp_holdings_t( 'footer_logo_aria' ) ); ?>">
					<picture>
						<source media="(max-width: 640px)" srcset="<?php echo esc_url( $qp_assets . '/logo-footer-mobile-2460-42265.svg' ); ?>" width="112" height="40">
						<img src="<?php echo esc_url( $qp_assets . '/logo-footer-desktop-2305-16342.svg' ); ?>" alt="QP Holdings" width="281" height="100" decoding="async">
					</picture>
				</a>
				<div class="footer-newsletter">
					<?php
					$qp_nl_status   = isset( $_GET['newsletter'] ) ? sanitize_key( wp_unslash( $_GET['newsletter'] ) ) : '';
					$qp_nl_messages = array(
						'sent'    => qp_holdings_t( 'newsletter_sent' ),
						'exists'  => qp_holdings_t( 'newsletter_exists' ),
						'invalid' => qp_holdings_t( 'newsletter_invalid' ),
						'error'   => qp_holdings_t( 'newsletter_error' ),
					);
					$qp_nl_message  = $qp_nl_messages[ $qp_nl_status ] ?? '';
					$qp_nl_ok       = in_array( $qp_nl_status, array( 'sent', 'exists' ), true );
					?>
					<form class="newsletter-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" aria-label="<?php echo esc_attr( qp_holdings_t( 'newsletter_form_aria' ) ); ?>">
						<?php wp_nonce_field( 'qp_newsletter_form', 'qp_newsletter_nonce' ); ?>
						<input type="hidden" name="action" value="qp_newsletter_submit">
						<input type="text" name="website" class="newsletter-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
						<label for="newsletter-email" class="sr-only"><?php echo esc_html( qp_holdings_t( 'email' ) ); ?></label>
						<input type="email" id="newsletter-email" name="email" class="newsletter-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'newsletter_placeholder' ) ); ?>" required aria-required="true">
						<button type="submit" class="newsletter-btn"><?php echo esc_html( qp_holdings_t( 'subscribe' ) ); ?></button>
					</form>
					<?php if ( $qp_nl_message ) : ?>
						<p class="newsletter-notice newsletter-notice--<?php echo $qp_nl_ok ? 'success' : 'error'; ?>" role="status"><?php echo esc_html( $qp_nl_message ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Row 2: 4 columns -->
			<div class="footer-middle">

				<!-- Column 1: Company info -->
				<div class="footer-col footer-col-company">
					<h3 class="footer-col-title"<?php qp_holdings_te_attrs( 'footer_company_name' ); ?>><?php echo esc_html( qp_holdings_t( 'footer_company_name' ) ); ?></h3>

					<p class="footer-info-row">
						<span><strong>MST:</strong> 5400257584</span>
						<span class="footer-info-sep" aria-hidden="true"></span>
						<span>
							<?php
							$stock_code_text = qp_holdings_t( 'stock_code' );
							$parts           = preg_split( '/(:|：)/u', $stock_code_text, 2 );
							if ( count( $parts ) === 2 ) {
								echo '<strong>' . esc_html( trim( $parts[0] ) ) . ':</strong> <span class="footer-stock-ticker">' . esc_html( trim( $parts[1] ) ) . '</span>';
							} else {
								echo '<strong>' . esc_html( $stock_code_text ) . '</strong>';
							}
							?>
						</span>
					</p>

					<div class="footer-address">
						<p class="footer-address-label">
							<svg class="footer-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" aria-hidden="true">
								<path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
								<circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5" />
							</svg>
							<span><?php echo esc_html( qp_holdings_t( 'head_office' ) ); ?></span>
						</p>
						<address><?php qp_holdings_te( 'footer_head_office_address' ); ?></address>
					</div>

					<div class="footer-address">
						<p class="footer-address-label">
							<svg class="footer-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" aria-hidden="true">
								<path d="M12 21s-7-6.5-7-12a7 7 0 0 1 14 0c0 5.5-7 12-7 12z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
								<circle cx="12" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5" />
							</svg>
							<span><?php echo esc_html( qp_holdings_t( 'binh_duong_office' ) ); ?></span>
						</p>
						<address><?php qp_holdings_te( 'footer_binh_duong_address' ); ?></address>
					</div>

					<?php
					if ( function_exists( 'qp_holdings_render_social_links' ) ) {
						qp_holdings_render_social_links(
							array(
								'class'      => 'footer-socials',
								'aria_label' => qp_holdings_t( 'Mạng xã hội' ),
							)
						);
					}
					?>
			</div>

			<!-- Column 2: About -->
				<div class="footer-col">
					<h3 class="footer-col-title"><?php echo esc_html( qp_holdings_t( 'about_us' ) ); ?></h3>
					<?php
					$qp_profile_url = function_exists( 'qp_holdings_capability_profile_url' ) ? qp_holdings_capability_profile_url() : '';
					?>
					<ul class="footer-nav-list">
						<li><a href="<?php echo esc_url( $qp_nav_url( 'about' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'about' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( $qp_nav_url( 'projects' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'projects' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( $qp_nav_url( 'news' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'news' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( $qp_nav_url( 'careers' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'careers' ) ); ?></a></li>
						<li>
							<a
								href="<?php echo '' !== $qp_profile_url ? esc_url( $qp_profile_url ) : '#'; ?>"
								<?php echo '' !== $qp_profile_url ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>
							><?php echo esc_html( qp_holdings_t( 'Hồ sơ năng lực' ) ); ?></a>
						</li>
						<li><a href="<?php echo esc_url( $qp_nav_url( 'faq' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'faq_page_title' ) ); ?></a></li>
					</ul>
				</div>

				<!-- Column 3: Legal -->
				<div class="footer-col">
					<h3 class="footer-col-title"><?php echo esc_html( qp_holdings_t( 'legal_regs' ) ); ?></h3>
					<ul class="footer-nav-list">
						<li><a href="<?php echo esc_url( $qp_nav_url( 'privacy' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'privacy' ) ); ?></a></li>
						<li><a href="<?php echo esc_url( $qp_nav_url( 'terms' ) ); ?>"><?php echo esc_html( qp_holdings_t( 'terms' ) ); ?></a></li>
					</ul>
				</div>

				<!-- Column 4: Contact -->
				<div class="footer-col footer-col-contact">
					<h3 class="footer-col-title"><?php echo esc_html( qp_holdings_t( 'contact' ) ); ?></h3>

					<div class="footer-contact-block">
						<p class="footer-contact-label"><?php echo esc_html( qp_holdings_t( 'head_office' ) ); ?></p>
						<a href="tel:19000066" class="footer-contact-item">
							<svg class="footer-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" aria-hidden="true">
								<path d="M3.5 5.3c0-1 .8-1.8 1.8-1.8h2.4c.8 0 1.5.5 1.7 1.3l1 3.2c.2.7-.1 1.5-.7 1.9l-1.4.9c.9 2.2 2.7 4 4.9 4.9l.9-1.4c.4-.6 1.2-.9 1.9-.7l3.2 1.0c.8.2 1.3.9 1.3 1.7v2.4c0 1-.8 1.8-1.8 1.8h-1.2c-7.6 0-13.8-6.2-13.8-13.8V5.3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
								<path d="M14 6h5m-2.5-2.5v5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
							</svg>
							<span>19000066</span>
						</a>
					</div>



					<span class="footer-contact-divider" aria-hidden="true"></span>

					<a href="mailto:info@qpholdings.vn" class="footer-contact-item">
						<svg class="footer-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" aria-hidden="true">
							<rect x="2" y="4" width="20" height="16" rx="2" stroke="currentColor" stroke-width="1.5" />
							<path d="M3 6l9 7 9-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
						</svg>
						<span>info@qpholdings.vn</span>
					</a>
				</div>

			</div>
		</div>

		<span class="footer-divider" aria-hidden="true"></span>

		<div class="footer-bottom">
			<p>Copyright @ <?php echo esc_html( gmdate( 'Y' ) ); ?> QP HOLDINGS. All Rights Reserved</p>
		</div>

	</div>
</footer>

<?php
/** Custom footer-area injection slot (see inc/custom-code.php). */
do_action( 'qp_holdings_custom_footer' );
?>
<?php wp_footer(); ?>
</body>
</html>
