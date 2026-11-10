<?php
/**
 * Single template: qp_job.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id       = get_the_ID();
	$post_obj      = get_post( $post_id );
	$display_title = $post_obj instanceof WP_Post && function_exists( 'qp_holdings_job_display_title' ) ? qp_holdings_job_display_title( $post_obj ) : get_the_title();
	$hero_title    = $post_obj instanceof WP_Post && function_exists( 'qp_holdings_job_title_with_quantity' ) ? qp_holdings_job_title_with_quantity( $post_obj ) : $display_title;
	$hero_image    = function_exists( 'qp_holdings_job_hero_image_url' ) ? qp_holdings_job_hero_image_url( $post_id ) : get_template_directory_uri() . '/assets/site/careers-hero-team-5fdb60.png';
	$sections      = function_exists( 'qp_holdings_job_detail_sections' ) ? qp_holdings_job_detail_sections( $post_id ) : array();
	$related_jobs  = function_exists( 'qp_holdings_related_jobs' ) ? qp_holdings_related_jobs( $post_id, 2 ) : array();
	$application_status = isset( $_GET['application'] ) ? sanitize_key( wp_unslash( $_GET['application'] ) ) : '';
	$application_messages = array(
		'sent'           => qp_holdings_t( 'job_apply_sent' ),
		'invalid'        => qp_holdings_t( 'consult_invalid' ),
		'invalid-file'   => qp_holdings_t( 'job_apply_invalid_file' ),
		'security-error' => qp_holdings_t( 'turnstile_error' ),
		'error'          => qp_holdings_t( 'contact_error' ),
	);
	$application_message = $application_messages[ $application_status ] ?? '';
?>

<main id="main" class="job-detail-page">
	<section class="page-hero job-detail-hero" aria-label="<?php echo esc_attr( $display_title ); ?>">
		<div class="page-hero-bg" aria-hidden="true">
			<img src="<?php echo esc_url( $hero_image ); ?>" alt="" loading="eager">
			<div class="page-hero-overlay"></div>
		</div>
		<div class="container page-hero-breadcrumb-container">
			<nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'home' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'home' ) ); ?></a>
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/site/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<a href="<?php echo esc_url( qp_holdings_page_url( 'careers' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( qp_holdings_t( 'careers' ) ); ?></a>
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/site/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
				<span class="page-breadcrumb-current"><?php echo esc_html( $display_title ); ?></span>
			</nav>
		</div>
		<div class="container page-hero-content job-detail-hero-content">
			<h1 class="job-detail-hero-title reveal reveal-delay-1"><?php echo esc_html( $hero_title ); ?></h1>
		</div>
	</section>

	<section class="job-detail-content" aria-label="<?php echo esc_attr( qp_holdings_t( 'job_desc_heading' ) ); ?>">
		<div class="container job-detail-layout">
			<article class="job-detail-main">
				<h2 class="job-detail-heading"><?php echo esc_html( qp_holdings_t( 'job_desc_heading' ) ); ?></h2>
				<div class="job-detail-sections">
					<?php foreach ( $sections as $section ) : ?>
						<section class="job-detail-section<?php echo empty( $section['text'] ) ? ' is-empty' : ''; ?>">
							<h3><?php echo esc_html( $section['title'] ); ?></h3>
							<?php if ( ! empty( $section['text'] ) ) : ?>
								<div class="job-detail-section__text"><?php echo wp_kses_post( function_exists( 'qp_holdings_job_detail_text_html' ) ? qp_holdings_job_detail_text_html( $section['text'] ) : nl2br( esc_html( $section['text'] ), false ) ); ?></div>
							<?php endif; ?>
						</section>
					<?php endforeach; ?>
				</div>
				<button type="button" class="job-detail-apply" data-job-application-open><?php echo esc_html( qp_holdings_t( 'apply_now' ) ); ?></button>
			</article>

			<aside class="job-detail-related" aria-label="<?php echo esc_attr( qp_holdings_t( 'related_jobs' ) ); ?>">
				<?php foreach ( $related_jobs as $rel_post ) : ?>
					<?php
					$rel_id       = (int) $rel_post->ID;
					$rel_title    = function_exists( 'qp_holdings_job_title_with_quantity' ) ? qp_holdings_job_title_with_quantity( $rel_post ) : get_the_title( $rel_post );
					$rel_summary  = function_exists( 'qp_holdings_job_summary' ) ? qp_holdings_job_summary( $rel_post ) : get_the_excerpt( $rel_post );
					$rel_emp_type = function_exists( 'qp_holdings_job_text_field' ) ? qp_holdings_job_text_field( $rel_id, 'employment_type' ) : (string) get_post_meta( $rel_id, 'employment_type', true );
					$rel_time     = function_exists( 'qp_holdings_job_employment_type_label' ) ? qp_holdings_job_employment_type_label( $rel_emp_type ) : $rel_emp_type;
					$rel_location = function_exists( 'qp_holdings_job_text_field' ) ? qp_holdings_job_text_field( $rel_id, 'location' ) : (string) get_post_meta( $rel_id, 'location', true );
					$rel_salary   = function_exists( 'qp_holdings_job_text_field' ) ? qp_holdings_job_text_field( $rel_id, 'salary' ) : (string) get_post_meta( $rel_id, 'salary', true );
					$rel_deadline = function_exists( 'qp_holdings_job_text_field' ) ? qp_holdings_job_text_field( $rel_id, 'deadline' ) : (string) get_post_meta( $rel_id, 'deadline', true );
					?>
					<a class="job-detail-related-card" href="<?php echo esc_url( get_permalink( $rel_id ) ); ?>">
						<?php /* Desktop layout: meta on top, then body, then "view details" */ ?>
						<div class="job-detail-related-card__meta">
							<?php if ( $rel_time ) : ?><span class="job-detail-related-card__meta-item job-detail-related-card__meta-item--time"><span class="job-detail-related-card__meta-text"><?php echo esc_html( $rel_time ); ?></span></span><?php endif; ?>
							<?php if ( $rel_location ) : ?><span class="job-detail-related-card__meta-item job-detail-related-card__meta-item--location"><span class="job-detail-related-card__meta-text"><?php echo esc_html( $rel_location ); ?></span></span><?php endif; ?>
						</div>
						<div class="job-detail-related-card__body">
							<h3><?php echo esc_html( $rel_title ); ?></h3>
							<?php if ( $rel_summary ) : ?><p><?php echo esc_html( $rel_summary ); ?></p><?php endif; ?>
						</div>
						<span class="job-detail-related-card__more"><?php echo esc_html( qp_holdings_t( 'view_details' ) ); ?></span>

						<?php /* Mobile Figma layout: salary + time row → deadline */ ?>
						<div class="job-detail-related-card__mobile-meta">
							<?php if ( $rel_salary ) : ?><span class="job-detail-related-card__mobile-meta-item job-detail-related-card__mobile-meta-item--salary"><?php echo esc_html( $rel_salary ); ?></span><?php endif; ?>
							<?php if ( $rel_time ) : ?><span class="job-detail-related-card__mobile-meta-item job-detail-related-card__mobile-meta-item--time"><?php echo esc_html( $rel_time ); ?></span><?php endif; ?>
						</div>
						<?php if ( $rel_deadline ) : ?><p class="job-detail-related-card__mobile-deadline"><?php echo esc_html( $rel_deadline ); ?></p><?php endif; ?>
					</a>
				<?php endforeach; ?>
			</aside>
		</div>
	</section>

	<div class="job-detail-bottom-bar">
		<button type="button" class="job-detail-bottom-bar__btn" data-job-application-open><?php echo esc_html( qp_holdings_t( 'apply_now' ) ); ?></button>
	</div>

	<div id="job-application" class="job-application-modal<?php echo $application_message ? ' is-open' : ''; ?>" data-job-application-modal<?php echo $application_message ? ' data-job-application-status="1"' : ' hidden'; ?>>
		<div class="job-application-modal__backdrop" data-job-application-close aria-hidden="true"></div>
		<section class="job-application-dialog" role="dialog" aria-modal="true" aria-labelledby="job-application-title">
			<button type="button" class="job-application-close" data-job-application-close aria-label="<?php echo esc_attr( qp_holdings_t( 'close_form' ) ); ?>"></button>
			<h2 id="job-application-title" class="job-application-title"><?php echo esc_html( qp_holdings_t( 'apply_now_title' ) ); ?></h2>
			<?php if ( $application_message ) : ?>
				<p class="job-application-notice job-application-notice--<?php echo 'sent' === $application_status ? 'success' : 'error'; ?>"><?php echo esc_html( $application_message ); ?></p>
			<?php endif; ?>
			<form class="job-application-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
				<?php wp_nonce_field( 'qp_job_application_' . $post_id, 'qp_job_application_nonce' ); ?>
				<input type="hidden" name="action" value="qp_job_application_submit">
				<input type="hidden" name="job_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
				<input type="text" name="website" class="job-application-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

				<div class="job-application-fields">
					<div class="job-application-field">
						<label for="job-app-name" class="job-application-label"><?php echo esc_html( qp_holdings_t( 'full_name' ) ); ?> <span>*</span></label>
						<input id="job-app-name" name="applicant_name" type="text" class="job-application-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_name' ) ); ?>" required>
					</div>

					<div class="job-application-field">
						<label for="job-app-email" class="job-application-label"><?php echo esc_html( qp_holdings_t( 'email' ) ); ?> <span>*</span></label>
						<input id="job-app-email" name="applicant_email" type="email" class="job-application-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_email' ) ); ?>" required>
					</div>

					<div class="job-application-field">
						<label for="job-app-phone" class="job-application-label"><?php echo esc_html( qp_holdings_t( 'phone_number' ) ); ?> <span>*</span></label>
						<div class="job-application-phone">
							<?php echo qp_holdings_phone_country_select( 'applicant_phone_country' ); ?>
							<input id="job-app-phone" name="applicant_phone" type="tel" class="job-application-phone-input" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_phone' ) ); ?>" autocomplete="tel-national" required>
						</div>
					</div>

					<div class="job-application-field">
						<label for="job-app-message" class="job-application-label"><?php echo esc_html( qp_holdings_t( 'job_app_message' ) ); ?> <span>*</span></label>
						<textarea id="job-app-message" name="applicant_message" class="job-application-textarea" placeholder="<?php echo esc_attr( qp_holdings_t( 'placeholder_job_message' ) ); ?>" rows="4" required></textarea>
					</div>

					<div class="job-application-field job-application-field--file">
						<div class="job-application-file-stack">
							<label class="job-application-label">CV <span>*</span></label>
							<label class="job-application-upload">
								<input name="cv_files[]" type="file" accept=".zip,.pdf,.doc,.docx,.png,.jpg,.jpeg" multiple required data-job-application-file>
								<span class="job-application-upload-icon" aria-hidden="true"></span>
								<span data-job-application-file-text><?php echo esc_html( qp_holdings_t( 'upload_file' ) ); ?></span>
							</label>
							<p class="job-application-file-note"><?php echo esc_html( qp_holdings_t( 'upload_file_note' ) ); ?></p>
						</div>
					</div>
				</div>

			<?php echo qp_holdings_turnstile_render(); ?>
			<button type="submit" class="job-application-submit"><?php echo esc_html( qp_holdings_t( 'submit' ) ); ?></button>
		</form>
		</section>
	</div>

	<script>
	(function () {
		var modal = document.querySelector('[data-job-application-modal]');
		if (!modal) return;

		var openers = document.querySelectorAll('[data-job-application-open]');
		var closers = modal.querySelectorAll('[data-job-application-close]');
		var fileInput = modal.querySelector('[data-job-application-file]');
		var fileText = modal.querySelector('[data-job-application-file-text]');
		var lastFocus = null;

		function focusableElements() {
			return Array.prototype.slice.call(modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not(.job-application-hp), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'));
		}

		function openModal(event) {
			if (event) event.preventDefault();
			lastFocus = document.activeElement;
			modal.hidden = false;
			window.requestAnimationFrame(function () {
				modal.classList.add('is-open');
				document.body.classList.add('job-application-modal-open');
				var firstInput = modal.querySelector('#job-app-name');
				if (firstInput) firstInput.focus();
			});
		}

		function closeModal() {
			modal.classList.remove('is-open');
			document.body.classList.remove('job-application-modal-open');
			window.setTimeout(function () {
				modal.hidden = true;
				if (lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
			}, 180);
		}

		openers.forEach(function (button) { button.addEventListener('click', openModal); });
		closers.forEach(function (button) { button.addEventListener('click', closeModal); });

		document.addEventListener('keydown', function (event) {
			if (modal.hidden) return;
			if (event.key === 'Escape') {
				closeModal();
				return;
			}
			if (event.key !== 'Tab') return;

			var focusable = focusableElements();
			if (!focusable.length) return;
			var first = focusable[0];
			var last = focusable[focusable.length - 1];
			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});

		if (fileInput && fileText) {
			var uploadLabel = fileText.closest('.job-application-upload');
			fileInput.addEventListener('change', function () {
				var files = Array.prototype.slice.call(fileInput.files || []);
				var names = files.map(function (file) { return file.name; });
				if (names.length) {
					fileText.textContent = names.slice(0, 3).join(', ');
					if (uploadLabel) uploadLabel.classList.add('has-files');
				} else {
					fileText.textContent = '<?php echo esc_js( qp_holdings_t( 'upload_file' ) ); ?>';
					if (uploadLabel) uploadLabel.classList.remove('has-files');
				}
			});
		}

		var form = modal.querySelector('.job-application-form');
		if (form) {
			// Disable native browser feedback bubbles
			form.setAttribute('novalidate', '');

			/* ── Per-field error messages (multilingual via PHP) ── */
			var errorMessages = {
				'job-app-name':              '<?php echo esc_js( qp_holdings_t( "err_name_required" ) ); ?>',
				'job-app-email':             '<?php echo esc_js( qp_holdings_t( "err_email_required" ) ); ?>',
				'job-app-email-invalid':     '<?php echo esc_js( qp_holdings_t( "err_email_invalid" ) ); ?>',
				'job-app-phone':             '<?php echo esc_js( qp_holdings_t( "err_phone_required" ) ); ?>',
				'job-app-message':           '<?php echo esc_js( qp_holdings_t( "err_message_required" ) ); ?>',
				'job-app-supporting-docs':   '<?php echo esc_js( qp_holdings_t( "err_supporting_required" ) ); ?>',
				'cv-file':                   '<?php echo esc_js( qp_holdings_t( "err_cv_required" ) ); ?>'
			};

			function getErrorMessage(field, value) {
				if (field.type === 'file') return errorMessages['cv-file'];
				if (field.type === 'email' && value !== '') return errorMessages['job-app-email-invalid'];
				return errorMessages[field.id] || '';
			}

			function getTargets(field) {
				var displayTarget = field;
				var errorContainer = null;

				if (field.id === 'job-app-phone') {
					displayTarget = field.closest('.job-application-phone') || field;
					errorContainer = field.closest('.job-application-field');
				} else if (field.type === 'file') {
					displayTarget = field.closest('.job-application-upload') || field;
					errorContainer = field.closest('.job-application-file-stack');
				} else {
					errorContainer = field.closest('.job-application-field');
				}

				return { display: displayTarget, container: errorContainer };
			}

			function clearFieldError(field) {
				var t = getTargets(field);
				t.display.classList.remove('is-invalid');
				var existing = t.container ? t.container.querySelector('.job-application-error') : null;
				if (existing) existing.parentNode.removeChild(existing);
			}

			function showFieldError(field, message) {
				var t = getTargets(field);
				t.display.classList.add('is-invalid');

				// Remove any existing error first
				var existing = t.container ? t.container.querySelector('.job-application-error') : null;
				if (existing) existing.parentNode.removeChild(existing);

				var errorEl = document.createElement('span');
				errorEl.className = 'job-application-error';
				errorEl.setAttribute('role', 'alert');
				errorEl.textContent = message;
				if (t.container) t.container.appendChild(errorEl);
			}

			function validateField(field) {
				var value = (field.value || '').trim();
				var isValid = true;

				if (field.type === 'file') {
					isValid = field.files && field.files.length > 0;
				} else {
					isValid = value !== '';
					if (isValid && field.type === 'email') {
						var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
						isValid = emailRegex.test(value);
					}
				}

				return { valid: isValid, value: value };
			}

			form.addEventListener('submit', function (event) {
				var requiredFields = form.querySelectorAll('[required]');
				var firstInvalid = null;

				requiredFields.forEach(function (field) {
					var result = validateField(field);

					if (!result.valid) {
						showFieldError(field, getErrorMessage(field, result.value));
						if (!firstInvalid) firstInvalid = field;
					} else {
						clearFieldError(field);
					}
				});

				if (firstInvalid) {
					event.preventDefault();
					firstInvalid.focus();
					return;
				}

				var turnstileWidget = form.querySelector('.cf-turnstile');
				if (turnstileWidget) {
					var turnstileTokenInput = form.querySelector('input[name="cf-turnstile-response"]');
					if (turnstileTokenInput && !(turnstileTokenInput.value || '').trim()) {
						event.preventDefault();
						var noticeEl = modal.querySelector('.job-application-notice');
						var msg = '<?php echo esc_js( qp_holdings_t( "turnstile_error" ) ); ?>';
						if (!noticeEl) {
							noticeEl = document.createElement('p');
							noticeEl.className = 'job-application-notice job-application-notice--error';
							var titleEl = modal.querySelector('#job-application-title');
							if (titleEl && titleEl.parentNode) {
								titleEl.parentNode.insertBefore(noticeEl, form);
							}
						} else {
							noticeEl.className = 'job-application-notice job-application-notice--error';
						}
						noticeEl.textContent = msg;
						noticeEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
					}
				}
			});

			form.addEventListener('input', function (event) {
				var target = event.target;
				if (!target.hasAttribute('required')) return;
				var result = validateField(target);
				if (result.valid) clearFieldError(target);
			});

			form.addEventListener('change', function (event) {
				var target = event.target;
				if (target.type === 'file' && target.hasAttribute('required')) {
					if (target.files && target.files.length > 0) {
						clearFieldError(target);
					}
				}
			});
		}

		if (modal.dataset.jobApplicationStatus) {
			openModal();
			var statusParam = new URLSearchParams(window.location.search).get('application');
			if (statusParam === 'sent') {
				window.setTimeout(function () {
					closeModal();
					if (window.history && window.history.replaceState) {
						var cleanUrl = window.location.pathname + window.location.hash;
						window.history.replaceState(null, '', cleanUrl);
					}
				}, 3000);
			}
		}
	})();
	</script>
</main>

<?php
endwhile;

get_footer();
