<?php
/**
 * Template Name: QP Holdings - Liên hệ
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', static function ( array $classes ): array {
	$classes[] = 'page-contact';
	return $classes;
} );

$contact_status = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : '';
$contact_messages = array(
	'sent'    => qp_holdings_t( 'contact_sent' ),
	'invalid' => qp_holdings_t( 'contact_invalid' ),
	'error'   => qp_holdings_t( 'contact_error' ),
);
$contact_message = $contact_messages[ $contact_status ] ?? '';
?>
<?php get_header(); ?>
<?php
$qp_assets  = get_template_directory_uri() . '/assets/site';
$qp_map_url = 'https://www.google.com/maps/embed?pb='
	. '!1m14!1m8!1m3!1d6797.824218504927!2d106.7055999!3d10.7755599'
	. '!3m2!1i1024!2i768!4f13.1!3m3!1m2'
	. '!1s0x31752f3705feb7f3%3A0xfc2153a8773ee71e!2zQ8OUTkcgVFkgQ-G7lCBQSOG6pk4gUVAgSE9MRElOR1M'
	. '!5e1!3m2!1svi!2s!4v1781623757946!5m2!1svi!2s';
?>

<main class="contact-page">

	<!-- ═══ PAGE HERO ═══ -->
	<section class="page-hero contact-hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'contact' ) ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'contact', 'assets/site/contact-hero-bg-3476c8.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
			<div class="page-hero-overlay contact-hero-overlay"></div>
		</div>
	</section>

	<!-- ═══ CONTACT CARD (info + form) ═══ -->
	<section class="contact-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'contact_section_aria' ) ); ?>">
		<div class="container">
			<div class="contact-card reveal">

				<!-- Left: Contact Info -->
				<div class="contact-info">
					<header class="contact-info-header">
						<h2 class="contact-info-title navy-gradient-text"<?php qp_holdings_te_attrs( 'company_info' ); ?>><?php echo esc_html( qp_holdings_t( 'company_info' ) ); ?></h2>
						<p class="contact-info-subtitle"><?php qp_holdings_te( 'contact_info_subtitle' ); ?></p>
					</header>

					<div class="contact-list">

						<!-- Address row -->
						<div class="contact-row">
							<div class="contact-row-icon" aria-hidden="true">
								<img src="<?php echo esc_url( $qp_assets . '/contact-icon-map.svg' ); ?>" width="30" height="30" alt="">
							</div>
							<div class="contact-row-body">
								<div class="contact-block">
									<h3 class="contact-block-title"><?php echo esc_html( qp_holdings_t( 'head_office' ) ); ?></h3>
									<address class="contact-block-text"><?php qp_holdings_te( 'footer_head_office_address' ); ?></address>
								</div>
								<div class="contact-block">
									<h3 class="contact-block-title"><?php echo esc_html( qp_holdings_t( 'binh_duong_office' ) ); ?></h3>
									<address class="contact-block-text"><?php qp_holdings_te( 'footer_binh_duong_address' ); ?></address>
								</div>
							</div>
						</div>

						<!-- Email row -->
						<div class="contact-row">
							<div class="contact-row-icon" aria-hidden="true">
								<img src="<?php echo esc_url( $qp_assets . '/contact-icon-mail.svg' ); ?>" width="30" height="30" alt="">
							</div>
							<div class="contact-row-body">
								<h3 class="contact-block-title"><?php echo esc_html( qp_holdings_t( 'email' ) ); ?></h3>
								<a href="mailto:info@qpholdings.vn" class="contact-block-text">info@qpholdings.vn</a>
							</div>
						</div>

						<!-- Phone row -->
						<div class="contact-row">
							<div class="contact-row-icon" aria-hidden="true">
								<img src="<?php echo esc_url( $qp_assets . '/contact-icon-call.svg' ); ?>" width="30" height="30" alt="">
							</div>
							<div class="contact-row-body">
								<h3 class="contact-block-title"><?php echo esc_html( qp_holdings_t( 'contact' ) ); ?></h3>
								<a href="tel:19000066" class="contact-phone">
									<img src="<?php echo esc_url( $qp_assets . '/contact-icon-phone-sm.svg' ); ?>" width="20" height="20" alt="" aria-hidden="true">
									<span>19000066</span>
								</a>
							</div>
						</div>

						<hr class="contact-divider">

						<!-- Socials -->
						<div class="contact-socials">
							<p class="contact-socials-label"><?php echo esc_html( qp_holdings_t( 'social_follow' ) ); ?></p>
							<ul class="contact-socials-list" aria-label="<?php echo esc_attr( qp_holdings_t( 'social_follow' ) ); ?>">
								<?php
								if ( function_exists( 'qp_holdings_get_social_links' ) ) {
									foreach ( qp_holdings_get_social_links() as $qp_social ) {
										$qp_social_icon = qp_holdings_social_icon_src( $qp_social );
										if ( '' === $qp_social_icon ) {
											continue;
										}
										printf(
											'<li><a href="%1$s" target="_blank" rel="noopener" aria-label="%2$s"><img src="%3$s" width="32" height="32" alt="" aria-hidden="true" loading="lazy" decoding="async"></a></li>',
											esc_url( $qp_social['url'] ),
											esc_attr( $qp_social['label'] ),
											esc_url( $qp_social_icon )
										);
									}
								}
								?>
							</ul>
						</div>

					</div>
				</div>

				<!-- Right: Contact Form -->
				<form id="contact-form" class="contact-form<?php echo $contact_message ? ' has-notice' : ''; ?>" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" aria-label="<?php echo esc_attr( qp_holdings_t( 'send_message_title' ) ); ?>">
					<?php wp_nonce_field( 'qp_contact_form', 'qp_contact_nonce' ); ?>
					<input type="hidden" name="action" value="qp_contact_submit">
					<input type="text" name="website" class="contact-form-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<h3 class="contact-form-title"<?php qp_holdings_te_attrs( 'contact_with_us' ); ?>><?php echo esc_html( qp_holdings_t( 'contact_with_us' ) ); ?></h3>
					<?php if ( $contact_message ) : ?>
						<p class="contact-form-notice contact-form-notice--<?php echo 'sent' === $contact_status ? 'success' : 'error'; ?>"><?php echo esc_html( $contact_message ); ?></p>
					<?php endif; ?>

					<div class="contact-form-body">

						<div class="contact-form-fields">
							<div class="contact-form-row">
								<div class="contact-form-field">
									<label for="cf-name" class="contact-form-label">
										<?php echo esc_html( qp_holdings_t( 'full_name' ) ); ?> <span class="required">*</span>
									</label>
									<input id="cf-name" name="name" type="text" class="contact-form-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_name' ) ); ?>" required>
								</div>
								<div class="contact-form-field">
									<label for="cf-email" class="contact-form-label">
										<?php echo esc_html( qp_holdings_t( 'email' ) ); ?> <span class="required">*</span>
									</label>
									<input id="cf-email" name="email" type="email" class="contact-form-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_email' ) ); ?>" required>
								</div>
							</div>

							<div class="contact-form-row">
								<div class="contact-form-field">
									<label for="cf-phone" class="contact-form-label">
										<?php echo esc_html( qp_holdings_t( 'phone_number' ) ); ?> <span class="required">*</span>
									</label>
									<div class="contact-phone-input-wrap">
										<?php echo qp_holdings_phone_country_select( 'phone_country' ); ?>
										<input id="cf-phone" name="phone" type="tel" class="contact-phone-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_phone' ) ); ?>" autocomplete="tel-national" required>
									</div>
								</div>
								<div class="contact-form-field">
									<label for="cf-question" class="contact-form-label">
										<?php echo esc_html( qp_holdings_t( 'your_question' ) ); ?> <span class="required">*</span>
									</label>
									<select id="cf-question" name="question" class="contact-form-select" required>
										<option value="" selected disabled><?php echo esc_html( qp_holdings_t( 'select_question' ) ); ?></option>
										<option value="project"><?php echo esc_html( qp_holdings_t( 'question_project' ) ); ?></option>
										<option value="investor"><?php echo esc_html( qp_holdings_t( 'question_investor' ) ); ?></option>
										<option value="recruitment"><?php echo esc_html( qp_holdings_t( 'question_recruitment' ) ); ?></option>
										<option value="other"><?php echo esc_html( qp_holdings_t( 'question_other' ) ); ?></option>
									</select>
								</div>
							</div>

							<div class="contact-form-field contact-form-field-message">
								<label for="cf-message" class="contact-form-label">
									<?php echo esc_html( qp_holdings_t( 'comment' ) ); ?> <span class="required">*</span>
								</label>
								<textarea id="cf-message" name="message" class="contact-form-textarea" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_message' ) ); ?>" rows="4" required></textarea>
							</div>
						</div>

						<?php echo qp_holdings_turnstile_render(); ?>
					<button type="submit" class="contact-form-submit"><?php echo esc_html( qp_holdings_t( 'submit' ) ); ?></button>

					</div>
				</form>

			</div>
		</div>
	</section>

	<!-- ═══ MAP SECTION ═══ -->
	<section class="map-section" aria-label="<?php echo esc_attr( qp_holdings_t( 'find_us_maps' ) ); ?>">
		<div class="container map-section-header reveal">
			<h2 class="section-title map-title navy-gradient-text"<?php qp_holdings_te_attrs( 'find_us_maps' ); ?>><?php echo esc_html( qp_holdings_t( 'find_us_maps' ) ); ?></h2>
			<p class="map-desc"><?php qp_holdings_te( 'map_desc' ); ?></p>
		</div>
		<div class="map-wrap reveal reveal-delay-1">
			<iframe
				class="map-iframe"
				title="<?php echo esc_attr( qp_holdings_t( 'map_iframe_title' ) ); ?>"
				src="<?php echo esc_url( $qp_map_url ); ?>"
				loading="lazy"
				allowfullscreen
				referrerpolicy="no-referrer-when-downgrade"
			></iframe>
		</div>
	</section>

</main>

<?php get_footer(); ?>
