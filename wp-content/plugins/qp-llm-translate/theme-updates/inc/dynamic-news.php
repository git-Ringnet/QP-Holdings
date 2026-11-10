<?php
/**
 * News (qp_news) — renderer + seed.
 */

defined('ABSPATH') || exit;

/**
 * Render the feature + side news cards used on the homepage and other
 * "news teaser" sections. Returns true when at least one card is rendered.
 *
 * @param array $args Optional: posts_per_page, category (term slug), show_filter.
 */
function qp_holdings_render_news_cards(array $args = array()): bool
{
	$defaults = array(
		'posts_per_page' => 4,
		'category' => '',
		'show_filter' => false,
	);
	$args = array_merge($defaults, $args);

	$q_args = array(
		'post_type' => 'qp_news',
		'posts_per_page' => (int) $args['posts_per_page'],
		'post_status' => 'publish',
		'no_found_rows' => true,
	);
	if ($args['category']) {
		$q_args['tax_query'] = array(
			array(
				'taxonomy' => 'qp_news_category',
				'field' => 'slug',
				'terms' => $args['category'],
			),
		);
	}
	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);
	if (!$q->have_posts()) {
		wp_reset_postdata();
		return false;
	}

	$posts = $q->posts;
	$first = array_shift($posts);

	echo '<div class="qp-news-feature">';

	// Feature card.
	if ($first instanceof WP_Post) {
		echo qp_holdings_news_card_html($first, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	// Side stack.
	echo '<div class="qp-news-side">';
	foreach ($posts as $post) {
		echo qp_holdings_news_card_html($post, false); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>'; // .qp-news-side

	echo '</div>'; // .qp-news-feature

	wp_reset_postdata();
	return true;
}

/**
 * Render the paginated news group/category archive using the Figma category layout.
 */
function qp_holdings_render_site_news_archive(int $per_page = 10): bool
{
	$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
	$cat = qp_holdings_get_query_string('cat');
	$group = qp_holdings_get_query_string('group');

	// Resolve the active group (if any) to its VI category slugs. A ?group=
	// filter takes precedence over a single ?cat= filter.
	$group_def = $group && function_exists('qp_holdings_news_group_by_key') ? qp_holdings_news_group_by_key($group) : null;
	$group_slugs = $group_def ? $group_def['slugs'] : array();

	$source_category_map = array();
	$source_posts = get_posts(
		array(
			'post_type' => 'qp_news',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'suppress_filters' => false,
			/* All languages; exclude EN/ZH duplicate copies via meta so the map
			 * keys come from canonical VI originals even when a source post was
			 * never assigned a Polylang language. */
			'lang' => '',
			'meta_query' => array(
				array(
					'key' => '_qp_translation_lang',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ($source_posts as $source_post) {
		$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($source_post) : $source_post->post_name;
		$terms = wp_get_post_terms($source_post->ID, 'qp_news_category', array('fields' => 'slugs'));
		if (!is_wp_error($terms) && $terms) {
			$source_category_map[$source_slug] = array_map('strval', $terms);
		}
	}

	$q_args = array(
		'post_type' => 'qp_news',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
	);

	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);

	$all_posts = $q->posts;
	$posts = $all_posts;
	if ($group_slugs) {
		// Filter to any of the group's VI category slugs.
		$posts = array_values(
			array_filter(
				$posts,
				static function ($post) use ($group_slugs, $source_category_map): bool {
					if (!$post instanceof WP_Post) {
						return false;
					}

					$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($post) : $post->post_name;
					$source_terms = $source_category_map[$source_slug] ?? array();

					return (bool) array_intersect($group_slugs, $source_terms);
				}
			)
		);
	} elseif ($cat) {
		$vi_cat = $cat;
		$current_term = get_term_by('slug', $cat, 'qp_news_category');
		if ($current_term instanceof WP_Term) {
			$lang = function_exists('qp_holdings_current_language') ? qp_holdings_current_language() : 'vi';
			if ('vi' !== $lang && function_exists('pll_get_term')) {
				$vi_term_id = pll_get_term((int) $current_term->term_id, 'vi');
				if ($vi_term_id) {
					$vi_term = get_term($vi_term_id, 'qp_news_category');
					if ($vi_term instanceof WP_Term) {
						$vi_cat = $vi_term->slug;
					}
				}
			}
		}
		$posts = array_values(
			array_filter(
				$posts,
				static function ($post) use ($vi_cat, $source_category_map): bool {
					if (!$post instanceof WP_Post) {
						return false;
					}

					$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($post) : $post->post_name;
					$source_terms = $source_category_map[$source_slug] ?? array();

					return in_array($vi_cat, $source_terms, true);
				}
			)
		);
	}

	$total_posts = count($posts);
	$max_pages = (int) ceil($total_posts / $per_page);
	$posts = array_slice($posts, ($paged - 1) * $per_page, $per_page);
	$archive_label = $group_def ? $group_def['title'] : '';
	if ('' === $archive_label && $cat) {
		$term = get_term_by('slug', $cat, 'qp_news_category');
		if ($term instanceof WP_Term) {
			$archive_label = $term->name;
		}
	}
	if ('' === $archive_label) {
		$archive_label = function_exists('qp_holdings_t') ? qp_holdings_t('news') : 'Tin tức';
	}

	echo '<section class="news-category-archive" aria-label="' . esc_attr($archive_label) . '">';
	echo '<div class="container">';

	if (!$posts) {
		echo '<p class="news-empty">' . esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('no_posts') : 'Chưa có bài viết nào.') . '</p>';
		echo '</div></section>';
		wp_reset_postdata();
		return false;
	}

	$featured = array_shift($posts);
	echo '<div class="news-category-content">';
	echo '<div class="news-category-results">';
	echo '<div class="news-category-list">';
	if ($featured instanceof WP_Post) {
		echo '<div class="news-category-featured">';
		echo qp_holdings_figma_featured_card($featured, true); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	if (!empty($posts)) {
		echo '<div class="news-category-grid-row">';
		foreach ($posts as $post) {
			if ($post instanceof WP_Post) {
				echo qp_holdings_figma_vertical_card($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div>';
	}
	echo '</div>'; // .news-category-list

	if (function_exists('qp_holdings_pagination')) {
		$q->found_posts = $total_posts;
		$q->max_num_pages = $max_pages;
		$q->set('paged', $paged);
		qp_holdings_pagination($q);
	}
	echo '</div>'; // .news-category-results

	if ($group_def) {
		qp_holdings_render_news_followup_group_section($group_def['key'], $all_posts, $source_category_map);
	}

	echo '</div>'; // .news-category-content
	echo '</div></section>';

	wp_reset_postdata();
	return true;
}

/**
 * Render the paginated news archive (legacy markup, kept for back-compat).
 */
function qp_holdings_render_news_archive(): bool
{
	$paged = max(1, (int) get_query_var('paged'));
	$cat = qp_holdings_get_query_string('cat');

	$q_args = array(
		'post_type' => 'qp_news',
		'posts_per_page' => 9,
		'paged' => $paged,
		'post_status' => 'publish',
	);
	if ($cat) {
		$q_args['tax_query'] = array(
			array(
				'taxonomy' => 'qp_news_category',
				'field' => 'slug',
				'terms' => $cat,
			),
		);
	}
	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);

	echo '<div class="qp-section">';
	qp_holdings_taxonomy_filter('qp_news_category', 'cat');

	if (!$q->have_posts()) {
		echo '<p class="qp-empty">' . esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('no_posts') : 'Chưa có bài viết nào.') . '</p>';
		echo '</div>';
		wp_reset_postdata();
		return false;
	}

	echo '<div class="qp-news-grid">';
	while ($q->have_posts()) {
		$q->the_post();
		echo qp_holdings_news_card_html(get_post(), false, 'grid'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	echo '</div>';

	qp_holdings_pagination($q);

	echo '</div>';
	wp_reset_postdata();
	return true;
}

/**
 * Single news card markup. $variant: 'feature' | 'side' | 'grid'.
 */
function qp_holdings_news_card_html(WP_Post $post, bool $feature, string $variant = ''): string
{
	$id = $post->ID;
	$url = qp_holdings_acf_or_meta($id, 'external_url', 'external_url') ?: get_permalink($id);
	$cat = function_exists('qp_holdings_news_category_label')
		? qp_holdings_news_category_label($id)
		: (function_exists('qp_holdings_t') ? qp_holdings_t('news') : 'Tin tức');
	$img = qp_holdings_acf_or_meta($id, 'card_image', 'card_image');
	$img_url = qp_holdings_image_url($img, $id, 'large');
	$date = get_the_date('d/m/Y', $id);
	$excerpt = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);
	$title = get_the_title($id);

	$is_external = false;
	$external_url = qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
	if (is_string($external_url) && $external_url) {
		$is_external = true;
	}
	$target = $is_external ? ' target="_blank" rel="noopener noreferrer"' : '';

	if ($feature) {
		$out = '<a href="' . esc_url($url) . '" class="qp-news-card qp-news-card--feature"' . $target . '>';
		$out .= '<div class="qp-news-card__media">';
		$out .= $img_url ? '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($title) . '" loading="lazy">' : '';
		$out .= '<span class="qp-news-card__badge">' . esc_html($cat) . '</span>';
		$out .= '</div>';
		$out .= '<div class="qp-news-card__body">';
		$out .= '<div class="qp-news-card__meta"><span>' . esc_html($date) . '</span></div>';
		$out .= '<h3 class="qp-news-card__title">' . esc_html($title) . '</h3>';
		$out .= '<p class="qp-news-card__excerpt">' . esc_html(wp_trim_words($excerpt, 28)) . '</p>';
		$out .= '<span class="qp-news-card__more">' . esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('read_more') : 'Đọc tiếp →') . '</span>';
		$out .= '</div>';
		$out .= '</a>';
		return $out;
	}

	if ('grid' === $variant) {
		$out = '<a href="' . esc_url($url) . '" class="qp-news-card qp-news-card--grid"' . $target . '>';
		$out .= '<div class="qp-news-card__media">';
		$out .= $img_url ? '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($title) . '" loading="lazy">' : '';
		$out .= '<span class="qp-news-card__badge">' . esc_html($cat) . '</span>';
		$out .= '</div>';
		$out .= '<div class="qp-news-card__body">';
		$out .= '<div class="qp-news-card__meta"><span>' . esc_html($date) . '</span></div>';
		$out .= '<h3 class="qp-news-card__title">' . esc_html($title) . '</h3>';
		$out .= '<p class="qp-news-card__excerpt">' . esc_html(wp_trim_words($excerpt, 22)) . '</p>';
		$out .= '</div>';
		$out .= '</a>';
		return $out;
	}

	// Side / horizontal card.
	$out = '<a href="' . esc_url($url) . '" class="qp-news-card qp-news-card--side"' . $target . '>';
	$out .= '<div class="qp-news-card__media">';
	$out .= $img_url ? '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($title) . '" loading="lazy">' : '';
	$out .= '</div>';
	$out .= '<div class="qp-news-card__body">';
	$out .= '<div class="qp-news-card__meta"><span class="qp-news-card__cat">' . esc_html($cat) . '</span><span aria-hidden="true">·</span><span>' . esc_html($date) . '</span></div>';
	$out .= '<h4 class="qp-news-card__title qp-news-card__title--sm">' . esc_html($title) . '</h4>';
	$out .= '</div>';
	$out .= '</a>';
	return $out;
}

/**
 * Local fallback used when a dynamic news item has no card/featured image.
 */
function qp_holdings_news_fallback_image_url(): string
{
	return get_template_directory_uri() . '/assets/site/news-fallback-logo.png';
}

/**
 * Keep the news "Ảnh đại diện (card)" ACF field and WordPress Featured Image
 * in sync, in both directions.
 *
 * Editors sometimes set only the Featured Image (so the public hero shows an
 * image) without filling the ACF card_image, or vice-versa — leaving the
 * editor preview and the front end disagreeing. On save we copy whichever side
 * has an attachment ID into the empty side, so both always match.
 *
 * card_image is an ACF image field (return_format = url) whose RAW meta value
 * is the attachment ID; we read the raw meta to avoid resolving to a URL.
 */
function qp_holdings_news_sync_featured_card_image(int $post_id): int
{
	if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
		return 0;
	}
	if ('qp_news' !== get_post_type($post_id)) {
		return 0;
	}

	$featured_id = (int) get_post_thumbnail_id($post_id);

	// Raw card_image meta: attachment ID (ACF) or possibly a URL string.
	$card_raw = get_post_meta($post_id, 'card_image', true);
	$card_id = 0;
	if (is_numeric($card_raw)) {
		$card_id = (int) $card_raw;
	} elseif (is_string($card_raw) && '' !== $card_raw) {
		$maybe = attachment_url_to_postid($card_raw);
		$card_id = $maybe ? (int) $maybe : 0;
	}

	$changed = 0;

	if ($featured_id && !$card_id) {
		// Featured set, ACF empty → copy featured into ACF card_image.
		update_post_meta($post_id, 'card_image', $featured_id);
		// Keep ACF's field-key reference in sync so the field shows up populated.
		update_post_meta($post_id, '_card_image', 'field_qp_news_card_image');
		$changed = 1;
	} elseif ($card_id && !$featured_id) {
		// ACF set, no featured → use the ACF image as the featured image too.
		set_post_thumbnail($post_id, $card_id);
		$changed = 1;
	}

	return $changed;
}
add_action('save_post_qp_news', 'qp_holdings_news_sync_featured_card_image', 20);
// ACF saves its meta on this hook; run after so card_image is already stored.
add_action('acf/save_post', 'qp_holdings_news_sync_featured_card_image', 20);

/**
 * One-shot backfill: sync featured ↔ card_image for every existing news post.
 *
 * @return array{processed:int,changed:int}
 */
function qp_holdings_news_backfill_images(): array
{
	$posts = get_posts(
		array(
			'post_type' => 'qp_news',
			'post_status' => 'any',
			'posts_per_page' => -1,
			'fields' => 'ids',
		)
	);
	$changed = 0;
	foreach ($posts as $pid) {
		$changed += qp_holdings_news_sync_featured_card_image((int) $pid);
	}
	return array('processed' => count($posts), 'changed' => $changed);
}

/**
 * Resolve the preferred image for a news post.
 */
function qp_holdings_news_image_url(int $post_id, string $size = 'large'): string
{
	$card_image = function_exists('qp_holdings_acf_or_meta')
		? qp_holdings_acf_or_meta($post_id, 'card_image', 'card_image')
		: '';

	$img_url = function_exists('qp_holdings_image_url')
		? qp_holdings_image_url($card_image, $post_id, $size)
		: '';

	if (!$img_url && has_post_thumbnail($post_id)) {
		$img_url = (string) get_the_post_thumbnail_url($post_id, $size);
	}

	return $img_url ? $img_url : qp_holdings_news_fallback_image_url();
}

/**
 * Resolve the Media Library attachment ID backing a news card image.
 *
 * Prefers the ACF `card_image` field (array / ID), then the post thumbnail.
 * Seeded theme assets (served straight from the theme directory, not the
 * uploads dir) are skipped — they have no generated sub-sizes, so they can't
 * produce a srcset and must be rendered from their raw, full-size URL instead.
 *
 * @return int Attachment ID, or 0 when no srcset-capable attachment exists.
 */
function qp_holdings_news_image_attachment_id(int $post_id): int
{
	$card_image = function_exists('qp_holdings_acf_or_meta')
		? qp_holdings_acf_or_meta($post_id, 'card_image', 'card_image')
		: '';

	$attachment_id = 0;
	if (is_array($card_image) && !empty($card_image['ID'])) {
		$attachment_id = (int) $card_image['ID'];
	} elseif (is_numeric($card_image)) {
		$attachment_id = (int) $card_image;
	}

	if (!$attachment_id && has_post_thumbnail($post_id)) {
		$attachment_id = (int) get_post_thumbnail_id($post_id);
	}

	if ($attachment_id <= 0) {
		return 0;
	}

	// Seeded theme-asset attachments resolve to a theme URL with no sub-sizes;
	// skip them so the caller falls back to the raw full-size <img>.
	if (
		function_exists('qp_holdings_seed_media_attachment_url')
		&& '' !== qp_holdings_seed_media_attachment_url($attachment_id)
	) {
		return 0;
	}

	return $attachment_id;
}

/**
 * Render a responsive news card <img>.
 *
 * When a real Media Library attachment backs the card, this uses
 * wp_get_attachment_image() so WordPress emits srcset + sizes and high-DPR
 * phones pick a sharp candidate (fixes blurry featured/list images on mobile).
 * Otherwise it falls back to a plain <img> pointing at the raw, full-size
 * source URL (seeded theme assets / external URLs) rather than a downscaled
 * 'medium'/'large' derivative.
 *
 * @param int    $post_id    News post ID.
 * @param string $size       Registered image size for the attachment path.
 * @param string $sizes_attr Value for the `sizes` attribute.
 * @param array  $attrs      Extra <img> attributes (class, alt, loading, ...).
 */
function qp_holdings_news_card_image(int $post_id, string $size, string $sizes_attr, array $attrs = array()): string
{
	$attachment_id = qp_holdings_news_image_attachment_id($post_id);

	if (empty($attrs['alt'])) {
		$attrs['alt'] = (string) get_the_title($post_id);
	}

	if ($attachment_id > 0) {
		$attrs['sizes'] = $sizes_attr;
		$html = wp_get_attachment_image($attachment_id, $size, false, $attrs);
		if (is_string($html) && '' !== trim($html)) {
			return $html;
		}
	}

	// Fallback: raw full-size source or theme default fallback image.
	$img_url = qp_holdings_news_image_url($post_id, 'full');
	if ('' === $img_url) {
		$img_url = qp_holdings_news_fallback_image_url();
	}

	$html_attrs = '';
	foreach ($attrs as $key => $value) {
		$html_attrs .= ' ' . $key . '="' . esc_attr((string) $value) . '"';
	}

	return '<img src="' . esc_url($img_url) . '"' . $html_attrs . '>';
}

/**
 * Whether the article body already opens with an image/figure.
 * Used to skip the cropped page-hero so layout matches the editor canvas.
 */
function qp_holdings_news_content_starts_with_image(int $post_id): bool
{
	$content = ltrim((string) get_post_field('post_content', $post_id));
	if ('' === $content) {
		return false;
	}

	return (bool) preg_match('/^(?:<!--[\s\S]*?-->\s*)*(?:<figure\b|<img\b|\[caption\b)/i', $content);
}

/**
 * Trim a news excerpt from the explicit excerpt or post content.
 *
 * Language-aware: on EN/ZH pages the canonical Vietnamese source post is what
 * gets queried, so reading its raw post_content would leak Vietnamese text into
 * the card excerpt. Route non-VI requests through the seeded-translation helper
 * (which prefers the linked translation copy / hard-coded map) and never fall
 * back to the Vietnamese source body.
 */
function qp_holdings_news_excerpt(int $post_id, int $words = 24): string
{
	$lang = function_exists('qp_holdings_current_language') ? qp_holdings_current_language() : 'vi';

	if ('vi' !== $lang && function_exists('qp_holdings_seeded_news_translation_for_post')) {
		// Ask the translation layer for an excerpt. It returns '' when there is
		// no usable (non-Vietnamese) translation, in which case we show no
		// excerpt rather than leaking the Vietnamese source text.
		$translated = qp_holdings_seeded_news_translation_for_post($post_id, 'excerpt', '');
		$translated = trim((string) $translated);
		if ('' === $translated) {
			return '';
		}
		return wp_trim_words(wp_strip_all_tags($translated), $words);
	}

	$excerpt = get_the_excerpt($post_id);

	if ('' === trim((string) $excerpt)) {
		$content = get_post_field('post_content', $post_id);
		$excerpt = wp_trim_words(wp_strip_all_tags(strip_shortcodes((string) $content)), $words);
	}

	return wp_trim_words(wp_strip_all_tags((string) $excerpt), $words);
}

/**
 * AJAX: save the news-detail lede into post_excerpt (current language copy).
 */
function qp_holdings_handle_save_news_lede(): void
{
	if (!is_user_logged_in() || !function_exists('qp_holdings_can_inline_edit') || !qp_holdings_can_inline_edit()) {
		wp_send_json_error(array('message' => 'forbidden'), 403);
	}

	check_ajax_referer('qp_inline_text', 'nonce');

	$post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
	if ($post_id <= 0 || 'qp_news' !== get_post_type($post_id) || !current_user_can('edit_post', $post_id)) {
		wp_send_json_error(array('message' => 'forbidden'), 403);
	}

	$raw = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';
	if (!is_string($raw)) {
		$raw = '';
	}
	if (strlen($raw) > 2000) {
		wp_send_json_error(array('message' => 'too_long'), 400);
	}

	$value = sanitize_textarea_field($raw);
	$target = $post_id;
	$lang = isset($_POST['lang']) ? sanitize_key(wp_unslash($_POST['lang'])) : '';
	if ($lang && 'vi' !== $lang && function_exists('pll_get_post')) {
		$translated = (int) pll_get_post($post_id, $lang);
		if ($translated > 0) {
			$target = $translated;
		}
	}

	$updated = wp_update_post(
		array(
			'ID' => $target,
			'post_excerpt' => $value,
		),
		true
	);
	if (is_wp_error($updated)) {
		wp_send_json_error(array('message' => 'save_failed'), 500);
	}

	wp_send_json_success(
		array(
			'value' => $value,
			'isOverride' => '' !== $value,
		)
	);
}
add_action('wp_ajax_qp_save_news_lede', 'qp_holdings_handle_save_news_lede');

/**
 * Show the news lede as a textarea under the title on the edit screen.
 * Native excerpt metabox is hidden by default in Screen Options.
 */
function qp_holdings_news_excerpt_after_title(WP_Post $post): void
{
	if ('qp_news' !== $post->post_type) {
		return;
	}
	?>
	<div id="qp-news-lede" class="postbox">
		<div class="postbox-header">
			<h2 class="hndle">Tóm tắt dưới tiêu đề</h2>
		</div>
		<div class="inside">
			<label class="screen-reader-text" for="excerpt"><?php esc_html_e('Excerpt'); ?></label>
			<textarea name="excerpt" id="excerpt" rows="3"
				class="large-text"><?php echo esc_textarea($post->post_excerpt); ?></textarea>
			<p class="description">Hiện ngay dưới tiêu đề trên trang bài viết. Để trống sẽ tự cắt từ nội dung.</p>
		</div>
	</div>
	<?php
}
add_action('edit_form_after_title', 'qp_holdings_news_excerpt_after_title');

/**
 * Drop the default excerpt metabox so the after-title field is the only input.
 */
function qp_holdings_news_remove_default_excerpt_box(): void
{
	remove_meta_box('postexcerpt', 'qp_news', 'normal');
}
add_action('add_meta_boxes', 'qp_holdings_news_remove_default_excerpt_box', 99);

/**
 * Register post metadata (Author & publish info) metabox for qp_news and post.
 */
function qp_holdings_news_register_author_meta_box(): void
{
	if (function_exists('acf_add_local_field_group') || class_exists('ACF')) {
		return;
	}
	foreach (array('qp_news', 'post') as $screen) {
		add_meta_box(
			'qp_news_author_meta_box',
			'Thông tin tác giả & Ngày đăng (Post Metadata)',
			'qp_holdings_news_render_author_meta_box',
			$screen,
			'normal',
			'high'
		);
	}
}
add_action('add_meta_boxes', 'qp_holdings_news_register_author_meta_box');

function qp_holdings_news_render_author_meta_box(WP_Post $post): void
{
	wp_nonce_field('qp_news_author_meta_save', 'qp_news_author_meta_nonce');

	$author_name = get_post_meta($post->ID, 'author_name', true);
	$custom_datetime = get_post_meta($post->ID, 'custom_datetime', true);
	$category_info = get_post_meta($post->ID, 'category_info', true);
	?>
	<table class="form-table" style="margin: 0;">
		<tr>
			<th scope="row" style="width: 220px;">
				<label for="qp_author_name"><strong>Tên tác giả / Người viết</strong></label>
			</th>
			<td>
				<input type="text" id="qp_author_name" name="author_name" value="<?php echo esc_attr($author_name); ?>"
					class="regular-text" style="width: 100%; max-width: 420px;"
					placeholder="Ví dụ: Hà Linh, Ban Truyền thông...">
				<p class="description">Hiển thị ở đầu bài viết (thay thế cho mặc định "Ban Truyền thông"). Nếu để trống sẽ
					lấy theo tên tác giả bài viết hoặc mặc định "Ban Truyền thông".</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="qp_custom_datetime"><strong>Tùy chỉnh ngày & giờ hiển thị</strong></label>
			</th>
			<td>
				<input type="text" id="qp_custom_datetime" name="custom_datetime"
					value="<?php echo esc_attr($custom_datetime); ?>" class="regular-text"
					style="width: 100%; max-width: 420px;" placeholder="Ví dụ: 28-09-2026 - 00:05 AM">
				<p class="description">Nếu để trống, hệ thống sẽ tự động lấy thời gian xuất bản ở ô <em>"Đã xuất bản
						lúc"</em> trong khung bên phải.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="qp_category_info"><strong>Tùy chỉnh nhãn chuyên mục</strong></label>
			</th>
			<td>
				<input type="text" id="qp_category_info" name="category_info"
					value="<?php echo esc_attr($category_info); ?>" class="regular-text"
					style="width: 100%; max-width: 420px;" placeholder="Ví dụ: Thị trường chứng khoán, Tin tập đoàn...">
				<p class="description">Nếu để trống, hệ thống sẽ tự động lấy theo chuyên mục bạn chọn trong hộp <em>"Danh
						mục tin tức"</em> bên phải.</p>
			</td>
		</tr>
	</table>
	<?php
}

function qp_holdings_news_save_author_meta_box(int $post_id): void
{
	if (!isset($_POST['qp_news_author_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['qp_news_author_meta_nonce'])), 'qp_news_author_meta_save')) {
		return;
	}
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (isset($_POST['author_name'])) {
		$author = sanitize_text_field(wp_unslash($_POST['author_name']));
		if ('' !== $author) {
			update_post_meta($post_id, 'author_name', $author);
		} else {
			delete_post_meta($post_id, 'author_name');
		}
	}

	if (isset($_POST['custom_datetime'])) {
		$dt = sanitize_text_field(wp_unslash($_POST['custom_datetime']));
		if ('' !== $dt) {
			update_post_meta($post_id, 'custom_datetime', $dt);
		} else {
			delete_post_meta($post_id, 'custom_datetime');
		}
	}

	if (isset($_POST['category_info'])) {
		$cat = sanitize_text_field(wp_unslash($_POST['category_info']));
		if ('' !== $cat) {
			update_post_meta($post_id, 'category_info', $cat);
		} else {
			delete_post_meta($post_id, 'category_info');
		}
	}
}
add_action('save_post', 'qp_holdings_news_save_author_meta_box');




/**
 * Return the first assigned news term.
 */
function qp_holdings_news_term(int $post_id, string $taxonomy = 'qp_news_category'): ?WP_Term
{
	if (!taxonomy_exists($taxonomy)) {
		return null;
	}

	$terms = get_the_terms($post_id, $taxonomy);
	if (is_array($terms) && !is_wp_error($terms) && !empty($terms) && $terms[0] instanceof WP_Term) {
		return $terms[0];
	}

	return null;
}

/**
 * Display label for a news category badge/breadcrumb.
 */
function qp_holdings_news_category_label(int $post_id, string $taxonomy = 'qp_news_category'): string
{
	/*
	 * The taxonomy term is the source of truth: listings group posts by their
	 * qp_news_category term, so the badge/breadcrumb must match that term.
	 * Prefer it over the free-text `category_info` meta, which can drift away
	 * from the assigned term and otherwise mislabels the category (and is what
	 * caused EN/ZH breadcrumbs to show the wrong category). The slug-based
	 * lookup also gives a clean translation key per language.
	 */
	$term = qp_holdings_news_term($post_id, $taxonomy);
	if ($term instanceof WP_Term) {
		if (function_exists('qp_holdings_t')) {
			$vi_slug = $term->slug;
			if (function_exists('pll_get_term')) {
				$vi_term_id = pll_get_term((int) $term->term_id, 'vi');
				if ($vi_term_id) {
					$vi_term = get_term($vi_term_id, $taxonomy);
					if ($vi_term instanceof WP_Term) {
						$vi_slug = $vi_term->slug;
					}
				}
			}
			$trans = qp_holdings_t($vi_slug);
			if ($trans !== $vi_slug) {
				return $trans;
			}
		}
		return $term->name;
	}

	// Fall back to the free-text meta only when no term is assigned.
	$label = function_exists('qp_holdings_acf_or_meta')
		? qp_holdings_acf_or_meta($post_id, 'category_info', 'category_info')
		: '';
	$label = is_string($label) ? trim($label) : '';

	if ('' !== $label) {
		if (function_exists('qp_holdings_t')) {
			$trans = qp_holdings_t(sanitize_title($label));
			if ($trans !== sanitize_title($label)) {
				return $trans;
			}
			$trans2 = qp_holdings_t($label);
			if ($trans2 !== $label) {
				return $trans2;
			}
		}
		return $label;
	}

	return function_exists('qp_holdings_t') ? qp_holdings_t('news') : 'Tin tức';
}

/**
 * Link target for the first news category, falling back to the news page.
 */
function qp_holdings_news_category_url(int $post_id, string $taxonomy = 'qp_news_category'): string
{
	$term = qp_holdings_news_term($post_id, $taxonomy);
	if ($term instanceof WP_Term) {
		$url = get_term_link($term);
		if (!is_wp_error($url)) {
			return (string) $url;
		}
	}

	return function_exists('qp_holdings_page_url') ? qp_holdings_page_url('news') : home_url('/tintuc/');
}

/**
 * Resolve a post's category group (the section it shows under on the listing).
 *
 * Listings bucket the granular qp_news_category terms into the three groups
 * defined by qp_holdings_news_category_groups() (e.g. the "Market" and "Media"
 * terms both live under the "Corporate News" group). The breadcrumb should
 * reflect the group the reader navigated from, not the granular term — so this
 * maps the post's first term slug back to its group definition.
 *
 * @return array{key:string, title:string, slugs:array<int,string>}|null
 */
function qp_holdings_news_post_group(int $post_id, string $taxonomy = 'qp_news_category'): ?array
{
	$term = qp_holdings_news_term($post_id, $taxonomy);
	if (!$term instanceof WP_Term) {
		return null;
	}

	// Normalise to the canonical VI term slug so the group lookup matches
	// regardless of the post's language copy.
	$vi_slug = $term->slug;
	if (function_exists('pll_get_term')) {
		$vi_term_id = pll_get_term((int) $term->term_id, 'vi');
		if ($vi_term_id) {
			$vi_term = get_term($vi_term_id, $taxonomy);
			if ($vi_term instanceof WP_Term) {
				$vi_slug = $vi_term->slug;
			}
		}
	}

	foreach (qp_holdings_news_category_groups() as $group) {
		if (in_array($vi_slug, $group['slugs'], true)) {
			return $group;
		}
	}

	return null;
}

/**
 * Breadcrumb label for a post: its category group (e.g. "Corporate News"),
 * falling back to the granular category label when no group matches.
 */
function qp_holdings_news_group_label(int $post_id, string $taxonomy = 'qp_news_category'): string
{
	$group = qp_holdings_news_post_group($post_id, $taxonomy);
	if ($group && !empty($group['title'])) {
		return (string) $group['title'];
	}

	return qp_holdings_news_category_label($post_id, $taxonomy);
}

/**
 * Breadcrumb URL for a post: the group archive (?group=<key>) it belongs to,
 * falling back to the granular term archive.
 */
function qp_holdings_news_group_url(int $post_id, string $taxonomy = 'qp_news_category'): string
{
	$group = qp_holdings_news_post_group($post_id, $taxonomy);
	if ($group && !empty($group['key'])) {
		$news_base = function_exists('qp_holdings_page_url')
			? qp_holdings_page_url('news')
			: home_url('/tintuc/');

		return add_query_arg('group', $group['key'], $news_base);
	}

	return qp_holdings_news_category_url($post_id, $taxonomy);
}

/**
 * Render a single qp_news_category archive in the Figma category layout.
 */
function qp_holdings_render_news_category_archive(WP_Term $term, int $per_page = 10): bool
{
	if ('qp_news_category' !== $term->taxonomy) {
		return false;
	}

	$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));

	$q_args = array(
		'post_type' => 'qp_news',
		'post_status' => 'publish',
		'posts_per_page' => $per_page,
		'paged' => $paged,
		'orderby' => 'date',
		'order' => 'DESC',
		'tax_query' => array(
			array(
				'taxonomy' => 'qp_news_category',
				'field' => 'term_id',
				'terms' => array((int) $term->term_id),
			),
		),
	);
	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);
	if (!$q->have_posts()) {
		echo '<p class="news-empty">' . esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('no_posts') : 'Chưa có bài viết nào.') . '</p>';
		wp_reset_postdata();
		return false;
	}

	$posts = $q->posts;
	$featured = array_shift($posts);

	echo '<div class="news-category-content">';
	echo '<div class="news-category-results">';
	echo '<div class="news-category-list">';
	if ($featured instanceof WP_Post) {
		echo '<div class="news-category-featured">';
		echo qp_holdings_figma_featured_card($featured); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	if (!empty($posts)) {
		echo '<div class="news-category-grid-row">';
		foreach ($posts as $post) {
			if ($post instanceof WP_Post) {
				echo qp_holdings_figma_vertical_card($post); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
		echo '</div>';
	}
	echo '</div>'; // .news-category-list

	qp_holdings_pagination($q);
	echo '</div>'; // .news-category-results
	echo '</div>';

	wp_reset_postdata();
	return true;
}

/**
 * Output news cards using the new site design (`.news-card`).
 * Used by the homepage news section and other static pages.
 *
 * @param int $count Number of posts to display (first item is rendered as `featured`).
 */
function qp_holdings_render_site_news_cards(int $count = 3): bool
{
	// Only query the ACF-backed qp_news CPT. Built-in `post` is intentionally excluded.
	$q_args = array(
		'post_type' => 'qp_news',
		'posts_per_page' => $count,
		'post_status' => 'publish',
		'no_found_rows' => true,
		'orderby' => 'date',
		'order' => 'DESC',
	);
	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);

	if (!$q->have_posts()) {
		wp_reset_postdata();
		return false;
	}

	$index = 0;
	while ($q->have_posts()) {
		$q->the_post();
		$id = (int) get_the_ID();
		if ($id <= 0) {
			continue;
		}

		// ACF-first reads. qp_holdings_acf_or_meta() prefers get_field() then
		// falls back to post_meta when ACF is inactive.
		$external_url = qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
		$external_url = is_string($external_url) ? trim($external_url) : '';

		$category_label = function_exists('qp_holdings_news_category_label')
			? (string) qp_holdings_news_category_label($id)
			: (function_exists('qp_holdings_t') ? (string) qp_holdings_t('news') : 'Tin tức');

		$url = $external_url !== '' ? $external_url : (string) get_permalink($id);
		$title = (string) get_the_title($id);
		if ('' === trim($title)) {
			$title = function_exists('qp_holdings_t') ? (string) qp_holdings_t('news') : 'Tin tức';
		}
		$details_label = function_exists('qp_holdings_t') ? (string) qp_holdings_t('view_details') : 'Chi tiết';
		$date_iso = (string) get_the_date('Y-m-d', $id);
		if ('' === trim($date_iso)) {
			$date_iso = current_time('Y-m-d');
		}
		$date_str = (string) get_the_date('d/m/Y', $id);
		if ('' === trim($date_str)) {
			$date_str = current_time('d/m/Y');
		}

		$target_attr = $external_url !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
		$is_featured = (0 === $index);
		$delay_class = $index ? ' reveal-delay-' . $index : '';
		$card_classes = 'news-card' . ($is_featured ? ' featured' : '') . ' reveal' . $delay_class . ' swiper-slide';

		$aria = $is_featured ? ' aria-label="Bài viết nổi bật"' : '';

		$card_img_html = qp_holdings_news_card_image(
			$id,
			$is_featured ? 'qp_news_featured' : 'qp_news_card',
			$is_featured
			? '(max-width: 640px) 100vw, (max-width: 1024px) 60vw, 33vw'
			: '(max-width: 640px) 242px, (max-width: 1024px) 33vw, 25vw',
			array('alt' => $title, 'loading' => 'lazy', 'decoding' => 'async')
		);
		?>
		<article class="<?php echo esc_attr($card_classes); ?>" <?php echo $aria; // phpcs:ignore ?>>
			<div class="news-card-img">
				<?php echo $card_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ($category_label): ?>
					<span class="news-card-cat"><?php echo esc_html($category_label); ?></span>
				<?php endif; ?>
			</div>
			<div class="news-card-body">
				<time class="news-date<?php echo $is_featured ? ' featured' : ''; ?>"
					datetime="<?php echo esc_attr($date_iso); ?>"><?php echo esc_html($date_str); ?></time>
				<h3 class="news-title"><?php echo esc_html($title); ?></h3>
			</div>
			<a href="<?php echo esc_url($url); ?>" class="news-link<?php echo $is_featured ? ' news-link-featured' : ''; ?>"
				aria-label="<?php echo esc_attr($details_label . ': ' . $title); ?>" <?php echo $target_attr; // phpcs:ignore ?>>
				<span><?php echo esc_html($details_label); ?></span>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
						stroke-linejoin="round" />
				</svg>
			</a>
		</article>
		<?php
		$index++;
	}

	wp_reset_postdata();
	return true;
}

/**
 * Legacy homepage news grid (Flatsome markup) — kept for backwards compatibility
 * but no longer used by the new front-page template.
 *
 * @param int $count Number of posts to display.
 */
function qp_holdings_render_homepage_news(int $count = 3): bool
{
	$q_args = array(
		'post_type' => 'qp_news',
		'posts_per_page' => $count,
		'post_status' => 'publish',
		'no_found_rows' => true,
	);
	$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);
	if (!$q->have_posts()) {
		wp_reset_postdata();
		return false;
	}

	$index = 0;
	while ($q->have_posts()) {
		$q->the_post();
		$id = get_the_ID();
		$url = qp_holdings_acf_or_meta($id, 'external_url', 'external_url') ?: get_permalink($id);
		$cat = function_exists('qp_holdings_news_category_label')
			? qp_holdings_news_category_label($id)
			: (function_exists('qp_holdings_t') ? qp_holdings_t('news') : 'Tin tức');
		$img = qp_holdings_acf_or_meta($id, 'card_image', 'card_image');
		$img_url = qp_holdings_image_url($img, $id, 'large');
		$title = get_the_title($id);

		$is_external = is_string(qp_holdings_acf_or_meta($id, 'external_url', 'external_url'))
			&& qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
		$target = $is_external ? ' target="_blank" rel="noopener noreferrer"' : '';

		$post_ts = get_post_timestamp($id);
		$lang = function_exists('qp_holdings_current_language') ? qp_holdings_current_language() : 'vi';
		if ('vi' === $lang) {
			// Vietnamese month names for matching the original format "06 Tháng 3, 2026".
			$months_vi = array(
				1 => 'Tháng 1',
				2 => 'Tháng 2',
				3 => 'Tháng 3',
				4 => 'Tháng 4',
				5 => 'Tháng 5',
				6 => 'Tháng 6',
				7 => 'Tháng 7',
				8 => 'Tháng 8',
				9 => 'Tháng 9',
				10 => 'Tháng 10',
				11 => 'Tháng 11',
				12 => 'Tháng 12',
			);
			$date_str = $post_ts
				? wp_date('d', $post_ts) . ' ' . $months_vi[(int) wp_date('n', $post_ts)] . ', ' . wp_date('Y', $post_ts)
				: get_the_date('d/m/Y', $id);
		} else {
			$date_str = $post_ts
				? wp_date('M j, Y', $post_ts)
				: get_the_date('', $id);
		}

		$aos_delay = 100 + ($index * 150);
		$index++;
		?>
		<div class="col post-item" data-aos="fade-up" data-aos-duration="800"
			data-aos-delay="<?php echo esc_attr((string) $aos_delay); ?>">
			<div class="col-inner">
				<div class="box box-overlay dark box-text-bottom box-blog-post has-hover">
					<div class="box-image">
						<div class="image-cover" style="padding-top:75%;">
							<a href="<?php echo esc_url($url); ?>" class="plain" <?php echo $target; // phpcs:ignore ?>>
								<?php if ($img_url): ?>
									<img decoding="async" width="1024" height="576" src="<?php echo esc_url($img_url); ?>"
										class="attachment-medium size-medium wp-post-image" alt="<?php echo esc_attr($title); ?>"
										loading="lazy">
								<?php endif; ?>
							</a>
							<div class="overlay" style="background-color: rgba(0,0,0,.25)"></div>
						</div>
					</div>
					<div class="box-text text-left">
						<div class="box-text-inner blog-post-inner">
							<p class="cat-label tag-label is-xxsmall op-7 uppercase"><?php echo esc_html($cat); ?></p>
							<h5 class="post-title is-large">
								<a href="<?php echo esc_url($url); ?>" class="plain" <?php echo $target; // phpcs:ignore ?>><?php echo esc_html($title); ?></a>
							</h5>
							<div class="post-meta is-small op-8"><?php echo esc_html($date_str); ?></div>
							<div class="is-divider"></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	wp_reset_postdata();
	return true;
}

/**
 * Build a lightweight article figure for seeded news content.
 */
function qp_holdings_news_figure_html(string $src, string $caption = ''): string
{
	$safe_src = esc_url($src);
	if ('' === $safe_src) {
		return '';
	}

	$out = '<figure class="qp-news-content-figure">';
	$out .= '<img src="' . $safe_src . '" alt="' . esc_attr(wp_strip_all_tags($caption)) . '" loading="lazy">';
	if ('' !== $caption) {
		$out .= '<figcaption>' . esc_html($caption) . '</figcaption>';
	}
	$out .= '</figure>';

	return $out;
}

/**
 * Build a small source note for curated/crawled public data.
 */
function qp_holdings_news_source_html(string $label, string $url): string
{
	$safe_url = esc_url($url);
	if ('' === $safe_url) {
		return '';
	}

	return '<p><em>Nguồn dữ liệu tham khảo: <a href="' . $safe_url . '" target="_blank" rel="noopener noreferrer">' . esc_html($label) . '</a>. Nội dung được QP Holdings biên tập, tổng hợp lại để phù hợp với chuyên mục tin tức.</em></p>';
}

/**
 * Detect the original short placeholder seed posts so the rich-content refresh
 * does not overwrite manually edited production posts that merely share a slug.
 */
function qp_holdings_news_is_legacy_seeded_post(WP_Post $post): bool
{
	if ('rich-news-v2' === get_post_meta($post->ID, '_qp_seed_content_version', true)) {
		return false;
	}

	if (false !== strpos((string) $post->post_content, 'qp-news-content-figure')) {
		return false;
	}

	$legacy_markers = array(
		'vn-index-bung-no-thanh-khoan-ky-luc' => 'Thị trường chứng khoán Việt Nam ghi nhận diễn biến tích cực',
		'bon-ong-lon-cong-nghe-thai-carbon-vi-ai' => 'Sự phát triển nhanh của trí tuệ nhân tạo kéo theo nhu cầu lớn',
		'lng-my-muon-viet-nam-thanh-trung-tam-asean' => 'Các dự án năng lượng quy mô lớn tiếp tục thu hút sự quan tâm',
		'qp-holdings-cong-bo-ket-qua-quy-1-2026' => 'QP Holdings vừa công bố báo cáo tài chính quý 1/2026',
		'qp-green-park-mo-ban-giai-doan-2' => 'Ngày 08/03, QP Holdings tổ chức sự kiện ra mắt giai đoạn 2',
		'qp-holdings-ky-mou-doi-tac-nhat-ban' => 'Ngày 20/02 tại TP. Hồ Chí Minh, QP Holdings và Nikko Urban Group',
		'esg-trach-nhiem-doanh-nghiep' => 'QP Holdings vừa công bố báo cáo phát triển bền vững năm 2025',
		'qp-holdings-top-50-doanh-nghiep-niem-yet' => 'QP Holdings tiếp tục được Forbes Việt Nam đưa vào danh sách',
	);

	$marker = $legacy_markers[$post->post_name] ?? '';
	if ('' === $marker) {
		return false;
	}

	return false !== stripos(wp_strip_all_tags((string) $post->post_content), $marker);
}

/* ===================================================================
 * Render the category-grouped news archive (Figma design).
 * Used by page-tintuc.php (v2 layout).
 * =================================================================== */

/**
 * Canonical news category groups shown on the news page.
 *
 * Each group bundles one or more VI category slugs under a single section
 * title. The `key` is a stable, language-neutral identifier used by the
 * "Xem tất cả" buttons (?group=<key>) so the full archive can be filtered to
 * exactly the categories that make up the group.
 *
 * @return array<int, array{key:string, title:string, slugs:array<int,string>}>
 */
function qp_holdings_news_category_groups(): array
{
	return array(
		array(
			'key' => 'tin-du-an',
			'title' => function_exists('qp_holdings_t') ? qp_holdings_t('news_group_project') : 'Tin dự án',
			'slugs' => array('su-kien', 'truyen-thong'),
		),
		array(
			'key' => 'tin-tap-doan',
			'title' => function_exists('qp_holdings_t') ? qp_holdings_t('news_group_corporate') : 'Tin tập đoàn',
			'slugs' => array('nha-dau-tu', 'thi-truong'),
		),
		array(
			'key' => 'thong-bao',
			'title' => function_exists('qp_holdings_t') ? qp_holdings_t('news_group_announcements') : 'Thông báo',
			'slugs' => array('thong-cao'),
		),
	);
}

/**
 * Resolve a group key (?group=) to its definition, or null when unknown.
 *
 * @return array{key:string, title:string, slugs:array<int,string>}|null
 */
function qp_holdings_news_group_by_key(string $key): ?array
{
	foreach (qp_holdings_news_category_groups() as $group) {
		if ($group['key'] === $key) {
			return $group;
		}
	}
	return null;
}

/**
 * Short hero description for category-group list pages.
 */
function qp_holdings_news_group_description(string $key): string
{
	$default = function_exists('qp_holdings_t')
		? qp_holdings_t('news_hero_desc')
		: 'Cập nhật tin tức, hoạt động doanh nghiệp, thị trường và quan hệ nhà đầu tư từ QP Holdings.';

	if ('tin-tap-doan' === $key && function_exists('qp_holdings_t')) {
		return qp_holdings_t('news_group_corporate_desc');
	}

	return $default;
}

/**
 * Render the next category-group teaser below a full group listing.
 *
 * @param array<int,WP_Post> $all_posts
 * @param array<string,array<int,string>> $source_category_map
 */
function qp_holdings_render_news_followup_group_section(string $active_group_key, array $all_posts, array $source_category_map, int $count = 6): void
{
	$groups = qp_holdings_news_category_groups();
	$target_group = null;

	foreach ($groups as $index => $group) {
		if ($group['key'] === $active_group_key && isset($groups[$index + 1])) {
			$target_group = $groups[$index + 1];
			break;
		}
	}

	if (!$target_group) {
		return;
	}

	$posts = array_values(
		array_filter(
			$all_posts,
			static function ($post) use ($target_group, $source_category_map): bool {
				if (!$post instanceof WP_Post) {
					return false;
				}

				$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($post) : $post->post_name;
				$source_terms = $source_category_map[$source_slug] ?? array();

				return (bool) array_intersect($target_group['slugs'], $source_terms);
			}
		)
	);
	$posts = array_slice($posts, 0, $count);

	if (!$posts) {
		return;
	}

	$news_base = function_exists('qp_holdings_page_url')
		? qp_holdings_page_url('news')
		: home_url('/tintuc/');
	$view_all_url = add_query_arg('group', $target_group['key'], $news_base);
	$view_all = function_exists('qp_holdings_t') ? qp_holdings_t('view_all') : 'Xem tất cả';

	echo '<div class="news-category-related news-cat-section reveal reveal-delay-1">';
	echo '<div class="news-cat-header">';
	echo '<div class="news-cat-title-wrap"><h2 class="news-cat-title">' . esc_html($target_group['title']) . '</h2></div>';
	echo '<a href="' . esc_url($view_all_url) . '" class="btn-gold-pill"><span>' . esc_html($view_all) . '</span><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.83331 14.1667L14.1666 5.83333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.83331 5.83333H14.1666V14.1667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
	echo '</div>';

	echo '<div class="news-cat-desktop-rows"><div class="news-cat-2col-row"><div class="swiper-wrapper">';
	foreach ($posts as $post) {
		if ($post instanceof WP_Post) {
			echo '<div class="swiper-slide">' . qp_holdings_figma_horizontal_card($post) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo '</div></div></div>';

	echo '<div class="news-cat-carousel-row swiper news-swiper" data-news-swiper><div class="swiper-wrapper">';
	foreach ($posts as $post) {
		if ($post instanceof WP_Post) {
			echo '<div class="swiper-slide">' . qp_holdings_figma_vertical_card($post) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
	echo '</div></div>';
	echo '<div class="news-swiper-pagination"></div>';
	echo '<a href="' . esc_url($view_all_url) . '" class="btn-gold-pill btn-gold-pill-mobile"><span>' . esc_html($view_all) . '</span><svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.83331 14.1667L14.1666 5.83333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.83331 5.83333H14.1666V14.1667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></a>';
	echo '</div>';
}

/**
 * Render news posts grouped by category with the Figma-matching layout:
 * Each section: Header (title + "Xem tất cả") → Featured row → Grid row.
 */
function qp_holdings_render_news_by_category(): void
{
	static $rendered = false;

	if ($rendered) {
		return;
	}

	$rendered = true;

	$categories = qp_holdings_news_category_groups();

	$source_category_map = array();
	$source_posts = get_posts(
		array(
			'post_type' => 'qp_news',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
			'suppress_filters' => false,
			/* All languages; exclude EN/ZH duplicate copies via meta so the map
			 * keys come from canonical VI originals even when a source post was
			 * never assigned a Polylang language. */
			'lang' => '',
			'meta_query' => array(
				array(
					'key' => '_qp_translation_lang',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);
	foreach ($source_posts as $source_post) {
		$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($source_post) : $source_post->post_name;
		$terms = wp_get_post_terms($source_post->ID, 'qp_news_category', array('fields' => 'slugs'));
		if (!is_wp_error($terms) && $terms) {
			$source_category_map[$source_slug] = array_map('strval', $terms);
		}
	}

	$section_index = 0;
	foreach ($categories as $cat_group) {
		$posts_per_page = ($section_index === 0) ? 7 : 9;

		$q_args = array(
			'post_type' => 'qp_news',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'no_found_rows' => true,
			'orderby' => 'date',
			'order' => 'DESC',
		);

		$q = function_exists('qp_holdings_wp_query_with_language_fallback') ? qp_holdings_wp_query_with_language_fallback($q_args) : new WP_Query($q_args);

		if (!$q->have_posts()) {
			wp_reset_postdata();
			continue;
		}

		$posts = array_values(
			array_filter(
				$q->posts,
				static function ($post) use ($cat_group, $source_category_map): bool {
					if (!$post instanceof WP_Post) {
						return false;
					}

					$source_slug = function_exists('qp_holdings_translation_source_slug') ? qp_holdings_translation_source_slug($post) : $post->post_name;
					$source_terms = $source_category_map[$source_slug] ?? array();

					return (bool) array_intersect($cat_group['slugs'], $source_terms);
				}
			)
		);
		wp_reset_postdata();

		$posts = array_slice($posts, 0, $posts_per_page);
		if (!$posts) {
			continue;
		}

		if ($section_index === 0) {
			$featured = array_shift($posts);
			$side_cards = array_splice($posts, 0, min(3, count($posts)));
		} else {
			$featured = null;
			$side_cards = array_splice($posts, 0, min(6, count($posts)));
		}

		$delay = $section_index > 0 ? ' reveal-delay-1' : '';
		$section_classes = 'news-sections';
		if ($section_index === 1) {
			$section_classes .= ' news-sections--grey-bg';
		}

		// "Xem tất cả" goes to the full news listing filtered to this group's
		// categories, in the current language (e.g. /tintuc/?group=tin-du-an,
		// /en/news/?group=tin-du-an). The archive renderer resolves the group
		// key back to its VI category slugs.
		$news_base = function_exists('qp_holdings_page_url')
			? qp_holdings_page_url('news')
			: home_url('/tintuc/');
		$view_all_url = add_query_arg('group', $cat_group['key'], $news_base);
		?>
		<section class="<?php echo esc_attr($section_classes); ?>"
			aria-label="<?php echo esc_attr($cat_group['title']); ?>">
			<div class="container">
				<div class="news-cat-section reveal<?php echo esc_attr($delay); ?>">
					<!-- Section Header -->
					<div class="news-cat-header">
						<div class="news-cat-title-wrap">
							<h2 class="news-cat-title"><?php echo esc_html($cat_group['title']); ?></h2>
						</div>
						<a href="<?php echo esc_url($view_all_url); ?>" class="btn-gold-pill">
							<span><?php echo esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('view_all') : 'Xem tất cả'); ?></span>
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
								<path d="M5.83331 14.1667L14.1666 5.83333" stroke="currentColor" stroke-width="1.5"
									stroke-linecap="round" stroke-linejoin="round" />
								<path d="M5.83331 5.83333H14.1666V14.1667" stroke="currentColor" stroke-width="1.5"
									stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					</div>

					<?php if ($section_index === 0): ?>
						<!-- Featured Row: Big card left + side cards right -->
						<div class="news-cat-featured-row<?php echo empty($side_cards) ? ' news-cat-featured-row--full' : ''; ?>">
							<?php if ($featured instanceof WP_Post): ?>
								<?php echo qp_holdings_figma_featured_card($featured, true); // phpcs:ignore -- LCP candidate, eager + fetchpriority=high ?>
							<?php endif; ?>
							<?php
							// Side cards: 3 horizontal cards on desktop; mobile Swiper.
							$all_cards = $side_cards;
							if ($all_cards):
								?>
								<div class="news-cat-side-stack swiper news-swiper" data-news-swiper>
									<div class="swiper-wrapper">
										<?php foreach ($all_cards as $p): ?>
											<div class="swiper-slide"><?php echo qp_holdings_figma_horizontal_card($p); // phpcs:ignore ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php else: ?>
						<?php
						// Desktop (>1024px): a 2-column grid of horizontal cards.
						// Tablet/mobile (<=1024px): a single combined carousel (handled below).
						?>
						<!-- Desktop two-row layout -->
						<div class="news-cat-desktop-rows">
							<?php if ($side_cards): ?>
								<!-- 6 horizontal cards in a 2-column grid (per Figma) -->
								<div class="news-cat-2col-row">
									<div class="swiper-wrapper">
										<?php foreach ($side_cards as $p): ?>
											<div class="swiper-slide"><?php echo qp_holdings_figma_horizontal_card($p); // phpcs:ignore ?>
											</div>
										<?php endforeach; ?>
									</div>
								</div>
							<?php endif; ?>
						</div>

						<?php
						// Tablet/mobile single-row carousel: all cards as vertical cards.
						$carousel_cards = $side_cards;
						if ($carousel_cards):
							?>
							<div class="news-cat-carousel-row swiper news-swiper" data-news-swiper>
								<div class="swiper-wrapper">
									<?php foreach ($carousel_cards as $p): ?>
										<div class="swiper-slide"><?php echo qp_holdings_figma_vertical_card($p); // phpcs:ignore ?></div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>

					<!-- Pagination dots — sibling of cards row, per Figma layout (mobile/tablet only) -->
					<div class="news-swiper-pagination"></div>

					<!-- Mobile-only "Xem tất cả" CTA below scroll area -->
					<a href="<?php echo esc_url($view_all_url); ?>" class="btn-gold-pill btn-gold-pill-mobile">
						<span><?php echo esc_html(function_exists('qp_holdings_t') ? qp_holdings_t('view_all') : 'Xem tất cả'); ?></span>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true">
							<path d="M5.83331 14.1667L14.1666 5.83333" stroke="currentColor" stroke-width="1.5"
								stroke-linecap="round" stroke-linejoin="round" />
							<path d="M5.83331 5.83333H14.1666V14.1667" stroke="currentColor" stroke-width="1.5"
								stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</a>
				</div>
			</div>
		</section>
		<?php
		$section_index++;
	}
}

/**
 * Featured card: large image with gradient overlay + text at bottom-left.
 *
 * The first featured card on the news listing is the LCP element on mobile,
 * so it gets `fetchpriority=high` + eager loading; subsequent cards (or
 * featured cards used inside category sections that aren't above the fold)
 * stay lazy.
 */
function qp_holdings_figma_featured_card(WP_Post $post, bool $is_lcp_candidate = false): string
{
	$id = $post->ID;
	$external = qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
	$external = is_string($external) ? trim($external) : '';
	$url = $external !== '' ? $external : get_permalink($id);
	$target = $external !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
	$title = get_the_title($id);
	$excerpt = qp_holdings_news_excerpt($id, 24);
	$date_str = get_the_date('d - m - Y', $id);

	$img_attrs = array(
		'class' => 'figma-featured-card__img',
		'alt' => $title,
		'decoding' => 'async',
	);
	if ($is_lcp_candidate) {
		$img_attrs['loading'] = 'eager';
		$img_attrs['fetchpriority'] = 'high';
	} else {
		$img_attrs['loading'] = 'lazy';
	}
	// Featured card is near full-width up to 888px on desktop.
	$img = qp_holdings_news_card_image($id, 'qp_news_featured', '(max-width: 1200px) 100vw, 888px', $img_attrs);

	$out = '<a href="' . esc_url($url) . '" class="figma-featured-card"' . $target . '>';
	$out .= $img;
	$out .= '<div class="figma-featured-card__overlay"></div>';
	$out .= '<div class="figma-featured-card__content">';
	$out .= '<h3 class="figma-featured-card__title">' . esc_html($title) . '</h3>';
	$out .= '<div class="figma-featured-card__meta">';
	$out .= '<p class="figma-featured-card__excerpt">' . esc_html(wp_trim_words($excerpt, 24)) . '</p>';
	$out .= '<div class="figma-card-sep" aria-hidden="true"></div>';
	$out .= '<span class="figma-card-date">' . esc_html($date_str) . '</span>';
	$out .= '</div>'; // __meta
	$out .= '</div>'; // __content
	$out .= '</a>';
	return $out;
}

/**
 * Horizontal card: image left (152×152px) + text right.
 */
function qp_holdings_figma_horizontal_card(WP_Post $post): string
{
	$id = $post->ID;
	$external = qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
	$external = is_string($external) ? trim($external) : '';
	$url = $external !== '' ? $external : get_permalink($id);
	$target = $external !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
	$title = get_the_title($id);
	$excerpt = qp_holdings_news_excerpt($id, 20);
	$date_str = get_the_date('d - m - Y', $id);

	// Square-ish thumb: ~152px desktop, ~242px in the mobile carousel.
	$img = qp_holdings_news_card_image(
		$id,
		'qp_news_card',
		'(max-width: 1024px) 242px, 152px',
		array('alt' => $title, 'loading' => 'lazy', 'decoding' => 'async')
	);

	$out = '<a href="' . esc_url($url) . '" class="figma-h-card"' . $target . '>';
	$out .= '<div class="figma-h-card__img">' . $img . '</div>';
	$out .= '<div class="figma-h-card__body">';
	$out .= '<h3 class="figma-h-card__title">' . esc_html($title) . '</h3>';
	$out .= '<div class="figma-h-card__meta">';
	$out .= '<p class="figma-h-card__excerpt">' . esc_html(wp_trim_words($excerpt, 18)) . '</p>';
	$out .= '<div class="figma-card-sep" aria-hidden="true"></div>';
	$out .= '<span class="figma-card-date">' . esc_html($date_str) . '</span>';
	$out .= '</div>'; // __meta
	$out .= '</div>'; // __body
	$out .= '</a>';
	return $out;
}

/**
 * Vertical card: image top (260px) + text body below.
 */
function qp_holdings_figma_vertical_card(WP_Post $post): string
{
	$id = $post->ID;
	$external = qp_holdings_acf_or_meta($id, 'external_url', 'external_url');
	$external = is_string($external) ? trim($external) : '';
	$url = $external !== '' ? $external : get_permalink($id);
	$target = $external !== '' ? ' target="_blank" rel="noopener noreferrer"' : '';
	$title = get_the_title($id);
	$excerpt = qp_holdings_news_excerpt($id, 20);
	$date_str = get_the_date('d - m - Y', $id);

	// Image top of the vertical card: ~242px wide in the mobile carousel,
	// up to ~416px in wider grids.
	$img = qp_holdings_news_card_image(
		$id,
		'qp_news_card',
		'(max-width: 640px) 242px, (max-width: 1024px) 320px, 416px',
		array('alt' => $title, 'loading' => 'lazy', 'decoding' => 'async')
	);

	$out = '<a href="' . esc_url($url) . '" class="figma-v-card"' . $target . '>';
	$out .= '<div class="figma-v-card__img">';
	$out .= $img;
	$out .= '</div>';
	$out .= '<div class="figma-v-card__body">';
	$out .= '<h3 class="figma-v-card__title">' . esc_html($title) . '</h3>';
	$out .= '<div class="figma-v-card__meta">';
	$out .= '<p class="figma-v-card__excerpt">' . esc_html(wp_trim_words($excerpt, 18)) . '</p>';
	$out .= '<div class="figma-card-sep" aria-hidden="true"></div>';
	$out .= '<span class="figma-card-date">' . esc_html($date_str) . '</span>';
	$out .= '</div>'; // __meta
	$out .= '</div>'; // __body
	$out .= '</a>';
	return $out;
}

/* ===================================================================
 * Seed: 8 rich news items spread across categories.
 * =================================================================== */

function qp_holdings_seed_news(bool $force = false): void
{
	$assets = get_template_directory_uri() . '/assets/tintuc_files/';
	$assets_i = get_template_directory_uri() . '/assets/index_files/';
	$assets_c = get_template_directory_uri() . '/assets/crawled-news/';

	$items = array(
		array(
			'slug' => 'hau-tai-co-cau-qp-holdings-hkt-len-ke-hoach-lai-tang-578',
			'title' => 'Hậu tái cơ cấu, QP Holdings (HKT) lên kế hoạch lãi tăng 578%, tham vọng làm dự án bất động sản nghìn tỷ ở TP.HCM',
			'date' => '2026-05-27 21:44:00',
			'image' => $assets_c . 'qp-holdings-hkt-dhcd-cafef.jpg',
			'category' => 'Nhà đầu tư',
			'tax' => array('qp_news_category' => array('nha-dau-tu')),
			'excerpt' => 'QP Holdings thông qua phương án đổi tên, đặt mục tiêu lợi nhuận năm 2026 tăng 578% và chuẩn bị danh mục dự án bất động sản quy mô lớn.',
			'content' => '<p>Tại Đại hội đồng cổ đông thường niên năm 2026 tổ chức ở TP.HCM, Công ty Cổ phần Đầu tư QP Xanh (Mã CK: HKT) đã thông qua phương án đổi tên thành Công ty Cổ phần QP Holdings, mục tiêu lợi nhuận năm nay tăng trưởng 578% cùng danh mục dự án bất động sản quy mô lớn.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-holdings-hkt-dhcd-cafef.jpg', 'Đại hội đồng cổ đông thường niên năm 2026 của QP Xanh thông qua phương án đổi tên thành QP Holdings.')
				. qp_holdings_news_figure_html($assets_c . 'hkt-cafef-3.jpg', 'Đại hội đồng cổ đông thường niên năm 2026 của QP Holdings (HKT) tổ chức tại TP.HCM.')
				. '<p>Năm 2025, doanh nghiệp ghi nhận doanh thu thuần hợp nhất đạt 42 tỷ đồng, giảm 64% so với mức 115 tỷ đồng của năm 2024. Lợi nhuận sau thuế đạt 5,4 tỷ đồng, gấp 21 lần so với mức 260 triệu đồng của năm trước.</p>'
				. '<p>Trong quý 1 năm 2026, lợi nhuận sau thuế đạt 3 tỷ đồng, gấp 11 lần so với mức 261 triệu đồng của quý 1/2025, nhưng đều đến từ doanh thu tài chính và khoản thu nhập khác từ hoạt động bồi thường, đạt hơn 2,7 tỷ đồng.</p>'
				. qp_holdings_news_figure_html($assets_c . 'hkt-cafef-6.png', 'Kế hoạch kinh doanh và chỉ tiêu lợi nhuận năm 2026 của QP Holdings (HKT).')
				. '<h2>Kế hoạch kinh doanh và thay đổi mô hình hoạt động năm 2026</h2>'
				. '<p>Đại hội đã thông qua chỉ tiêu tài chính năm 2026 với kế hoạch doanh thu thuần hợp nhất là 116 tỷ đồng, tăng 279% so với thực hiện của năm 2025. Chỉ tiêu lợi nhuận sau thuế kế hoạch đạt 31 tỷ đồng, tăng 578% so với năm trước.</p>'
				. '<p>Để phù hợp với mô hình mới, doanh nghiệp đổi tên thành Công ty Cổ phần QP Holdings và thay đổi địa điểm trụ sở chính. Doanh nghiệp cũng điều chỉnh ngành nghề khi lược bỏ 19 ngành cũ và bổ sung 10 ngành mới liên quan đến thương mại, vận tải và môi giới bất động sản.</p>'
				. '<p>Về phương án phân phối lợi nhuận, công ty chia cổ tức năm 2025 bằng cổ phiếu với tỷ lệ 4%, tương ứng phát hành thêm 1.333.401 cổ phiếu với tổng giá trị tính theo mệnh giá đạt hơn 13,33 tỷ đồng, đồng thời đặt mục tiêu cổ tức năm 2026 tối thiểu là 5% tính trên mệnh giá.</p>'
				. '<h2>Các dự án và phương án nhân sự</h2>'
				. qp_holdings_news_figure_html($assets_c . 'hkt-cafef-4.jpg', 'Ban lãnh đạo QP Holdings trình bày định hướng chiến lược và danh mục dự án tại đại hội.')
				. '<p>Về dòng vốn, tổng số tiền huy động từ đợt phát hành riêng lẻ của công ty đạt 272 tỷ đồng. Trong đó, doanh nghiệp đã chi gần 271 tỷ đồng để sở hữu 99,63% vốn tại Công ty TNHH Phát triển Nhà Quang Phúc.</p>'
				. '<p>Trong năm 2026, công ty dự kiến phân bổ khoảng 500 tỷ đồng cho bất động sản công nghiệp và khoảng 1.000 tỷ đồng cho bất động sản nhà ở. Nguồn thu năm nay dự kiến đến từ dự án QP Green Park, hoạt động khai thác nhà xưởng cho thuê và đầu tư tài chính ngắn hạn.</p>'
				. '<p>Đối với các dự án cụ thể, đại hội đã phê duyệt chủ trương triển khai một số hợp đồng lớn. Dự án QP Green Park có diện tích 137.821 m2 với tổng mức đầu tư 1.137 tỷ đồng, trong đó công ty con của doanh nghiệp sẽ ký hợp đồng thi công xây dựng với Công ty Cổ phần Đầu tư Xây dựng QP trị giá tối đa 881 tỷ đồng trong giai đoạn 2026 - 2027.</p>'
				. '<p>Tại dự án Khu chung cư Lô D17 ở Thủ Dầu Một, doanh nghiệp thực hiện hợp đồng tiếp thị, phân phối độc quyền và phát triển dự án cho 737 căn hộ với diện tích sàn 71.725 m2, tổng giá trị hợp đồng tối đa 2.500 tỷ đồng và dự kiến vay vốn hạn mức 800 tỷ đồng.</p>'
				. '<p>Ngoài ra, phương án M&amp;A một dự án nhà ở tại Thủ Dầu Một có diện tích hơn 6.000 m2 cũng được thông qua với tổng mức đầu tư tối đa 500 tỷ đồng, sử dụng hạn mức vay ngân hàng tối đa 400 tỷ đồng.</p>'
				. '<p>Về nhân sự, đại hội đã bầu Hội đồng quản trị nhiệm kỳ 2026 - 2030 gồm 4 thành viên, với ông Phạm Tự Trọng giữ chức Chủ tịch và ông Nguyễn Mạnh Tuấn làm Tổng giám đốc.</p>'
				. '<p>Trong năm 2026, công ty duy trì chính sách thù lao 0 đồng cho Hội đồng quản trị và Ủy ban kiểm toán. Ban điều hành được áp dụng cơ chế thưởng tối đa 10% trên phần lợi nhuận sau thuế vượt kế hoạch. Định hướng dài hạn của doanh nghiệp là ứng dụng công nghệ vào quản lý và thực hiện các tiêu chuẩn ESG nhằm tiếp cận các nguồn vốn quốc tế.</p>'
				. '<p><em>Theo <a href="https://cafef.vn/hau-tai-co-cau-qp-holdings-hkt-len-ke-hoach-lai-tang-578-tham-vong-lam-du-an-bat-dong-san-nghin-ty-o-tphcm-188260527214153661.chn" target="_blank" rel="noopener noreferrer">CafeF</a>.</em></p>',
		),
		array(
			'slug' => 'dhcd-qp-xanh-hkt-doi-ten-cong-ty-du-chi-500-ty-dong-ma-du-an-bat-dong-san',
			'title' => 'ĐHCĐ QP Xanh (HKT): Đổi tên Công ty, dự chi 500 tỷ đồng để M&A dự án bất động sản',
			'date' => '2026-05-28 11:09:00',
			'image' => $assets_c . 'qp-holdings-hkt-dhcd-tnck.jpg',
			'category' => 'Nhà đầu tư',
			'tax' => array('qp_news_category' => array('nha-dau-tu')),
			'excerpt' => 'ĐHĐCĐ QP Xanh thông qua đổi tên thành QP Holdings, kế hoạch huy động vốn, M&A dự án và mở rộng quỹ đất giai đoạn 2026-2030.',
			'content' => '<p>ĐHĐCĐ CTCP Đầu tư QP Xanh (mã chứng khoán HKT) đã thông qua nhiều tờ trình quan trọng liên quan đến lĩnh vực đầu tư bất động sản, M&amp;A dự án và kế hoạch huy động nguồn vốn để phục vụ mở rộng quỹ đất, phát triển dự án; đổi tên Công ty.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-holdings-hkt-dhcd-tnck.jpg', 'QP Xanh thông qua nhiều tờ trình về đổi tên, M&A dự án và kế hoạch mở rộng quỹ đất.')
				. '<p>Theo đó, QP Xanh sẽ được đổi tên thành QP Holdings, đồng thời đổi trụ sở về Tầng L16, Toà nhà Vietcombank, Công trường Mê Linh, phường Sài Gòn, TP.HCM.</p>'
				. '<p>Theo Ban lãnh đạo Công ty cho biết, đổi tên thành QP Holdings nhằm phản ánh định hướng tái cấu trúc và mở rộng mô hình hoạt động trong giai đoạn phát triển mới, từ doanh nghiệp có trọng tâm kinh doanh bất động sản sang mô hình công ty đầu tư theo cấu trúc holdings. Công ty đang triển khai nhiều kế hoạch liên quan đến M&amp;A dự án, huy động vốn và phát triển danh mục đầu tư bất động sản giai đoạn 2026-2030.</p>'
				. '<p>Trong giai đoạn tới, QP Holdings định hướng phát triển trên ba trụ cột gồm: bất động sản nhà ở, bất động sản khu công nghiệp và đầu tư tài chính.</p>'
				. '<p>Bên cạnh đó, QP Holdings cũng có nhiều tờ trình liên quan đến kế hoạch M&amp;A và phương án huy động vốn, chuyển từ trạng thái tích lũy nguồn lực sang giai đoạn tìm kiếm động lực tăng trưởng mới thông qua mở rộng quỹ đất.</p>'
				. '<h2>QP Green Park và kế hoạch đầu tư dự án</h2>'
				. '<p>Cụ thể, QP Holdings trình cổ đông thông qua việc công ty con là Công ty TNHH Phát triển Nhà Quang Phúc ký hợp đồng xây dựng với CTCP Đầu tư Xây dựng QP để triển khai dự án Khu nhà ở Quang Phúc 3 (QP Green Park), với tổng giá trị không vượt quá 881,25 tỷ đồng. Thời gian thực hiện 2026 - 2027.</p>'
				. '<p>Công ty TNHH Phát triển Nhà Quang Phúc hiện là công ty con do QP Holdings sở hữu 99,63% vốn, đồng thời là chủ đầu tư dự án QP Green Park. Dự án có quy mô 137.821 m2 diện tích đất, tổng mức đầu tư khoảng 1.137,6 tỷ đồng, trong đó riêng chi phí xây dựng chiếm khoảng 881,25 tỷ đồng, tương ứng quy mô giá trị hợp đồng dự kiến ký kết. Phát triển Nhà Quang Phúc có vốn chủ sở hữu 270 tỷ đồng, tổng tài sản 390,6 tỷ đồng.</p>'
				. '<p>Bên cạnh đó, QP Holdings còn có tờ trình thông qua chủ trương đầu tư M&amp;A một dự án bất động sản nhà ở tại khu vực Thủ Dầu Một (TP.HCM, trước đây thuộc Bình Dương), với tổng giá trị đầu tư tối đa 500 tỷ đồng, đồng thời triển khai kế hoạch vay vốn tối đa 400 tỷ đồng nhằm phục vụ thương vụ và phát triển dự án.</p>'
				. '<p>Theo tờ trình, dự án mục tiêu có quy mô hơn 6.000 m2, định hướng phát triển dòng sản phẩm căn hộ chung cư, với thời gian triển khai dự kiến từ quý II/2026 đến năm 2027. QP Holdings cho biết, mục tiêu của thương vụ là mở rộng quỹ đất phục vụ chiến lược tăng trưởng giai đoạn 2026-2030, trong bối cảnh doanh nghiệp đẩy mạnh hiện diện ở phân khúc bất động sản nhà ở dân dụng.</p>'
				. '<p>Để phục vụ kế hoạch đầu tư, QP Holdings đã trình cổ đông phương án vay vốn tối đa 400 tỷ đồng từ ngân hàng, tổ chức tín dụng, tổ chức kinh tế hoặc cá nhân đủ điều kiện cho vay theo quy định pháp luật. Khoản vay dự kiến được sử dụng không chỉ cho hoạt động M&amp;A mà còn để đáp ứng các nghĩa vụ tài chính liên quan đến dự án như tiền sử dụng đất, chi phí xây dựng, phát triển dự án, tư vấn và pháp lý.</p>'
				. '<h2>Hợp đồng phân phối dự án Lô D17</h2>'
				. '<p>Tại Đại hội, QP Holdings cũng đã thông qua chủ trương ký kết hợp đồng Dịch vụ Tiếp thị Phân phối Độc quyền và Phát triển dự án đối với Khu chung cư tại lô D17, với tổng giá trị hợp đồng ký kết tối đa 2.500 tỷ đồng. Đồng thời, Công ty cũng trình kế hoạch vay vốn tối đa 800 tỷ đồng để thực hiện hợp đồng này.</p>'
				. '<p>Dự án Khu chung cư tại lô D17 nằm tại phường Bình Dương, TP.HCM, do CTCP Phú Thịnh City làm chủ đầu tư. Dự án có diện tích sàn xây dựng 71.725,3 m2, tổng diện tích thương phẩm 45.677 m2 và quy mô 737 căn hộ.</p>'
				. '<p>Về pháp lý, dự án đã có quyết định cho phép chuyển nhượng một phần dự án Khu đô thị mới thuộc Khu liên hợp Công nghiệp - Dịch vụ - Đô thị Bình Dương; có văn bản góp ý liên quan đến quy hoạch tổng mặt bằng lô D17; đồng thời Giấy chứng nhận quyền sử dụng đất đã được cấp ngày 04/06/2025 cho CTCP Phú Thịnh City. Dự án hiện đã nộp hồ sơ lập báo cáo nghiên cứu khả thi, đã có kết quả thẩm định trong quý II/2026 và được miễn giấy phép xây dựng, dự kiến triển khai khởi công xây dựng trong năm 2026.</p>'
				. '<p>Song song với đó, QP Holdings trình cổ đông kế hoạch vay vốn với hạn mức tối đa 800 tỷ đồng từ ngân hàng, tổ chức tín dụng, tổ chức kinh tế hoặc cá nhân đủ điều kiện cho vay theo quy định pháp luật. Khoản vay dự kiến dùng để thanh toán cho chủ đầu tư dự án Khu chung cư tại lô D17 theo giá trị hợp đồng đã ký.</p>'
				. '<h2>Kế hoạch kinh doanh và tái cấu trúc nguồn vốn</h2>'
				. '<p>Năm 2026, HKT đặt kế hoạch doanh thu 116 tỷ đồng, tăng trưởng hơn 179% so với thực hiện 2025; lợi nhuận sau thuế cổ đông công ty mẹ 31 tỷ đồng, gấp 5,7 lần năm 2025. Dự kiến trong năm 2026, Công ty sẽ phát hành hơn 1,33 triệu cổ phiếu để trả cổ tức tỷ lệ 4% trên số lượng cổ phiếu lưu hành.</p>'
				. '<p>Trong năm 2025, Công ty ghi nhận doanh thu 41,5 tỷ đồng, giảm 63,48% so với cùng kỳ, do quỹ đất hiện tại đang được sử dụng để xây dựng dự án QP Green Park, chưa đủ điều kiện ghi nhận doanh thu.</p>'
				. '<p>Để hiện thực hóa các mục tiêu chiến lược trong giai đoạn 2025-2026, QP Holdings đặt trọng tâm là tái cấu trúc nguồn vốn theo hướng đa dạng hóa và lựa chọn các kênh có chi phí vốn thấp, giảm dần sự phụ thuộc vào tín dụng ngân hàng truyền thống ngắn hạn.</p>'
				. '<p>ĐHĐCĐ cũng tiến hành miễn nhiệm và bầu bổ sung HĐQT nhiệm kỳ 2026-2030, kết quả trúng cử nhiệm kỳ mới gồm ông Phạm Tự Trọng, ông Nguyễn Hồng Anh Khá, ông Phạm Duy và bà Nguyễn Thị Thuỳ Ngân.</p>'
				. '<p><em>Theo <a href="https://www.tinnhanhchungkhoan.vn/dhcd-qp-xanh-hkt-doi-ten-cong-ty-du-chi-500-ty-dong-de-ma-du-an-bat-dong-san-post391236.html" target="_blank" rel="noopener noreferrer">Đầu tư Chứng khoán</a>.</em></p>',
		),
		array(
			'slug' => 'vn-index-bung-no-thanh-khoan-ky-luc',
			'title' => 'VN-Index bùng nổ với thanh khoản kỷ lục, thị trường bước vào giai đoạn mới',
			'date' => '2026-04-22 08:00:00',
			'image' => $assets . 'chung-khoan-1-1024x683.webp',
			'category' => 'Truyền thông',
			'tax' => array('qp_news_category' => array('thi-truong')),
			'excerpt' => 'Thị trường đang ở nhịp tăng kéo dài hơn 1 tháng, sự điều chỉnh là cần thiết để tạo nền cho giai đoạn phát triển tiếp theo.',
			'content' => '<p>VN-Index ghi nhận một phiên giao dịch đặc biệt khi dòng tiền nhập cuộc mạnh, thanh khoản khớp lệnh trên HoSE vươn lên vùng kỷ lục và nhóm cổ phiếu vốn hóa lớn tiếp tục đóng vai trò dẫn dắt. Sau nhịp tăng nhanh trong phiên, áp lực chốt lời khiến chỉ số rung lắc, nhưng lực cầu cuối ngày vẫn giúp thị trường giữ được sắc xanh.</p>'
				. qp_holdings_news_figure_html($assets_c . 'vn-index-chart.png', 'Diễn biến VN-Index trong phiên bùng nổ thanh khoản được tổng hợp từ dữ liệu thị trường.')
				. '<h2>Dòng tiền mạnh nhưng phân hóa rõ</h2><p>Điểm đáng chú ý không chỉ nằm ở mức tăng của chỉ số chung mà còn ở cấu trúc dòng tiền. VN30 diễn biến tích cực hơn VN-Index, cho thấy tiền lớn ưu tiên nhóm cổ phiếu trụ như ngân hàng, thép và một số mã bất động sản. Trong khi đó, nhiều cổ phiếu đã tăng nóng bắt đầu xuất hiện trạng thái giằng co, phản ánh tâm lý thận trọng hơn của nhà đầu tư sau giai đoạn tăng kéo dài.</p>'
				. '<p>Theo dữ liệu được tổng hợp từ các bản tin thị trường, VN-Index từng có thời điểm tăng gần 57 điểm so với tham chiếu trong phiên, sau đó thu hẹp đà tăng khi áp lực chốt lời dâng cao. Dù vậy, việc dòng tiền quay lại nhanh cho thấy thanh khoản vẫn là điểm tựa quan trọng của xu hướng hiện tại.</p>'
				. '<h2>Rủi ro FOMO và vùng hỗ trợ cần quan sát</h2><p>Các chuyên gia phân tích cho rằng thị trường đã có nhịp đi lên kéo dài hơn một tháng nên một nhịp điều chỉnh kỹ thuật là cần thiết để tạo nền tích lũy mới. Vùng hỗ trợ gần được nhắc tới quanh 1.484-1.500 điểm, cũng là khu vực giúp nhà đầu tư đánh giá sức khỏe của dòng tiền sau các phiên biến động mạnh.</p>'
				. qp_holdings_news_figure_html($assets_c . 'vn-index-market.webp', 'Thanh khoản cao tạo cơ hội nhưng cũng làm gia tăng biến động ngắn hạn.')
				. '<h2>Góc nhìn từ QP Holdings</h2><p>Với định hướng đầu tư dài hạn, QP Holdings ưu tiên quan sát chất lượng dòng tiền thay vì chỉ bám theo mốc điểm số. Các nhóm ngành gắn với nền tảng vĩ mô như hạ tầng, bất động sản đô thị, ngân hàng và năng lượng vẫn được đánh giá có dư địa nếu kết quả kinh doanh cải thiện rõ ràng. Nhà đầu tư nên tránh mua đuổi trong các phiên hưng phấn, đồng thời quản trị tỷ trọng và đặt mốc bảo vệ lợi nhuận cho các vị thế đã tăng mạnh.</p>'
				. '<blockquote>Thanh khoản kỷ lục là tín hiệu tích cực, nhưng chiến lược phù hợp hơn trong giai đoạn này là giải ngân theo nhịp điều chỉnh và ưu tiên doanh nghiệp có nền tảng tăng trưởng bền vững.</blockquote>'
				. qp_holdings_news_source_html('QP Xanh / Nhịp sống thị trường', 'https://qpxanh.com.vn/vn-index-bung-no-voi-thanh-khoan-ky-luc-thi-truong-buoc-vao-song-tang-moi-hay-chi-la-nhip-hung-phan/'),
		),
		array(
			'slug' => 'bon-ong-lon-cong-nghe-thai-carbon-vi-ai',
			'title' => 'Bốn ông lớn công nghệ thải carbon tăng gấp 2,5 lần vì AI',
			'date' => '2026-03-28 08:00:00',
			'image' => $assets . 'x2.webp',
			'category' => 'Truyền thông',
			'tax' => array('qp_news_category' => array('truyen-thong')),
			'excerpt' => 'Khí thải carbon của các tập đoàn công nghệ lớn tăng mạnh khi mở rộng hạ tầng phục vụ trí tuệ nhân tạo.',
			'content' => '<p>Báo cáo của Liên minh Viễn thông Quốc tế (ITU), cơ quan thuộc Liên Hợp Quốc, cho thấy lượng phát thải carbon trực tiếp và gián tiếp của bốn tập đoàn công nghệ lớn gồm Amazon, Microsoft, Meta và Alphabet tăng mạnh trong giai đoạn 2020-2023. Nguyên nhân chính đến từ nhu cầu mở rộng trí tuệ nhân tạo, trung tâm dữ liệu và hạ tầng tính toán hiệu năng cao.</p>'
				. qp_holdings_news_figure_html($assets . 'x2.webp', 'AI tạo ra nhu cầu lớn về trung tâm dữ liệu, điện năng và hạ tầng làm mát.')
				. '<h2>Áp lực năng lượng phía sau làn sóng AI</h2><p>Theo dữ liệu được công bố, mức phát thải trong phạm vi 1 và 2 của các doanh nghiệp công nghệ tăng trung bình khoảng 150%. Amazon ghi nhận mức tăng cao nhất, tiếp theo là Microsoft, Meta và Alphabet. Riêng Microsoft, phát thải từ nguồn điện sử dụng cho vận hành được cho là tăng từ 4,3 triệu tấn năm 2020 lên gần 10 triệu tấn vào năm 2024.</p>'
				. '<p>Với các mô hình AI ngày càng lớn, nhu cầu điện, nước làm mát và thiết bị bán dẫn gia tăng nhanh. Đây là vấn đề không chỉ của ngành công nghệ mà còn tác động tới quy hoạch năng lượng, hạ tầng số và chiến lược phát triển bền vững của nhiều nền kinh tế.</p>'
				. '<h2>Các phản ứng từ doanh nghiệp công nghệ</h2><p>Một số tập đoàn đã tăng mua năng lượng tái tạo, tín chỉ carbon, nghiên cứu điện hạt nhân hoặc chuyển sang thiết kế làm mát bằng chất lỏng ở cấp độ chip để giảm tiêu thụ điện trong trung tâm dữ liệu. Tuy vậy, ITU nhấn mạnh rằng nhiều mục tiêu phát thải ròng bằng 0 vẫn cần được chuyển hóa thành hành động thực tế, có số liệu đo lường rõ ràng và kiểm chứng độc lập.</p>'
				. '<h2>Hàm ý cho chiến lược ESG</h2><p>Từ góc nhìn của QP Holdings, câu chuyện AI và phát thải cho thấy tăng trưởng công nghệ phải song hành với tiêu chuẩn môi trường, quản trị dữ liệu và hiệu quả sử dụng tài nguyên. Khi hạ tầng số trở thành một phần của bất động sản thương mại, khu công nghiệp và đô thị thông minh, các chủ đầu tư cần tính toán sớm nguồn điện xanh, hệ thống làm mát, quản lý nước và khả năng đo lường phát thải trong suốt vòng đời vận hành.</p>'
				. '<blockquote>AI mở ra năng suất mới, nhưng lợi thế cạnh tranh dài hạn sẽ thuộc về doanh nghiệp biết cân bằng giữa tốc độ mở rộng, chi phí năng lượng và cam kết phát triển bền vững.</blockquote>'
				. qp_holdings_news_source_html('VnExpress / Reuters / ITU', 'https://vnexpress.net/bon-ong-lon-cong-nghe-thai-carbon-tang-gap-2-5-lan-vi-ai-4895321.html'),
		),
		array(
			'slug' => 'lng-my-muon-viet-nam-thanh-trung-tam-asean',
			'title' => 'Tập đoàn khí LNG hàng đầu Mỹ muốn Việt Nam thành trung tâm phân phối của ASEAN',
			'date' => '2026-03-15 08:00:00',
			'image' => $assets . 'img4389-1748499461135670993135-5888-4063-1748517041.webp',
			'category' => 'Truyền thông',
			'tax' => array('qp_news_category' => array('thi-truong')),
			'excerpt' => 'Mở ra cơ hội đầu tư công nghệ và phát triển dự án LNG quy mô lớn tại Việt Nam.',
			'content' => '<p>Excelerate Energy, tập đoàn năng lượng của Hoa Kỳ hoạt động trong lĩnh vực khí thiên nhiên hóa lỏng (LNG), bày tỏ mong muốn hợp tác để Việt Nam trở thành trung tâm phân phối LNG của khu vực ASEAN. Đề xuất này được đặt trong bối cảnh Việt Nam đang tìm kiếm các nguồn cung dài hạn, ổn định và có khả năng hỗ trợ quá trình chuyển dịch năng lượng.</p>'
				. qp_holdings_news_figure_html($assets_c . 'lng-vietnam.jpg', 'Buổi làm việc giữa đại diện Excelerate Energy và Chính phủ Việt Nam về hợp tác LNG. Ảnh dữ liệu crawl từ VietnamBiz/VGP.')
				. '<h2>Cơ hội từ chuỗi cung ứng LNG</h2><p>Theo thông tin được công bố, Excelerate Energy đã ký thỏa thuận với PVGas về thu xếp nguồn LNG dài hạn từ Mỹ cho thị trường Việt Nam. Doanh nghiệp này đề xuất phát triển hạ tầng LNG, bảo đảm nguồn cung ổn định và thành lập liên doanh với đối tác Việt Nam để mở rộng thị trường khu vực.</p>'
				. '<p>Việt Nam có vị trí thuận lợi trong mạng lưới thương mại ASEAN, nhu cầu điện tăng và định hướng xây dựng trung tâm kho chứa, phân phối LNG phục vụ cả thị trường trong nước lẫn khu vực. Nếu được triển khai đồng bộ, các dự án kho cảng, đường ống, logistics lạnh và nhà máy điện khí có thể tạo ra chuỗi giá trị mới cho năng lượng sạch hơn so với than.</p>'
				. '<h2>Yêu cầu về công nghệ và giảm phát thải</h2><p>Thủ tướng Phạm Minh Chính nhấn mạnh Việt Nam chủ trương phát triển nhanh nhưng bền vững, không đánh đổi môi trường để chạy theo tăng trưởng đơn thuần. Do đó, hợp tác LNG không chỉ dừng ở nhập khẩu nhiên liệu mà còn cần chuyển giao công nghệ, kinh nghiệm quản lý kho chứa, vận hành an toàn và giải pháp giảm phát thải trong vận chuyển, sử dụng khí.</p>'
				. qp_holdings_news_figure_html($assets . 'img4389-1748499461135670993135-5888-4063-1748517041.webp', 'Năng lượng và hạ tầng logistics là nhóm lĩnh vực QP Holdings theo dõi trong chiến lược đầu tư dài hạn.')
				. '<h2>Góc nhìn đầu tư hạ tầng</h2><p>Với QP Holdings, câu chuyện LNG mở ra nhiều lớp cơ hội liên quan đến khu công nghiệp, logistics, cảng, kho bãi, năng lượng phụ trợ và đô thị công nghiệp. Các dự án lớn trong lĩnh vực này đòi hỏi vốn dài hạn, pháp lý rõ ràng và năng lực quản trị rủi ro cao; tuy nhiên khi được chuẩn bị tốt, chúng có thể tạo ra dòng tiền ổn định và nâng sức cạnh tranh cho nền kinh tế.</p>'
				. qp_holdings_news_source_html('VietnamBiz / VGP', 'https://vietnambiz.vn/tap-doan-nang-luong-my-muon-dua-viet-nam-tro-thanh-trung-tam-phan-phoi-lng-cua-khu-vuc-202552914520303.htm'),
		),
		array(
			'slug' => 'qp-holdings-cong-bo-ket-qua-quy-1-2026',
			'title' => 'QP Holdings công bố kết quả kinh doanh quý 1/2026, doanh thu tăng 28%',
			'date' => '2026-04-15 09:00:00',
			'image' => $assets_i . 'about-banner.jpg',
			'category' => 'Nhà đầu tư',
			'tax' => array('qp_news_category' => array('nha-dau-tu')),
			'excerpt' => 'Doanh thu hợp nhất Q1/2026 đạt 4.180 tỷ đồng, lợi nhuận sau thuế tăng 31% so với cùng kỳ.',
			'content' => '<p>QP Holdings công bố kết quả kinh doanh quý 1/2026 với doanh thu hợp nhất đạt 4.180 tỷ đồng, tăng 28% so với cùng kỳ; lợi nhuận sau thuế đạt 612 tỷ đồng, tăng 31%. Kết quả này phản ánh sự phục hồi của thị trường bất động sản, đóng góp ổn định từ logistics công nghiệp và hiệu quả kiểm soát chi phí trong toàn hệ sinh thái.</p>'
				. qp_holdings_news_figure_html($assets_i . 'about-banner.jpg', 'QP Holdings duy trì định hướng phát triển hệ sinh thái bất động sản, xây dựng, thương mại dịch vụ và đầu tư công nghiệp.')
				. '<h2>Các mảng kinh doanh đóng góp chính</h2><p>Mảng bất động sản dân cư tiếp tục là động lực lớn nhất, đóng góp khoảng 52% doanh thu nhờ tiến độ bán hàng và bàn giao tại các dự án đô thị. Mảng logistics và công nghiệp đóng góp 22%, hưởng lợi từ nhu cầu nhà xưởng, kho vận và dịch vụ phụ trợ tại khu vực Đông Nam Bộ. Năng lượng tái tạo và hạ tầng phụ trợ chiếm 18%, còn lại đến từ thương mại dịch vụ, vật liệu xây dựng và các hoạt động đầu tư khác.</p>'
				. '<p>Ban lãnh đạo cho biết chiến lược quý 1 tập trung vào ba ưu tiên: bảo toàn dòng tiền, hoàn thiện pháp lý dự án trọng điểm và tăng tốc các khoản đầu tư có khả năng tạo dòng tiền định kỳ. Nhờ đó, biên lợi nhuận gộp được cải thiện, chi phí tài chính được kiểm soát và vòng quay vốn lưu động tích cực hơn so với cùng kỳ.</p>'
				. '<h2>Nền tảng tài chính và quản trị rủi ro</h2><p>Trong bối cảnh lãi suất và sức mua thị trường vẫn biến động, QP Holdings duy trì tỷ lệ nợ vay ở mức thận trọng, ưu tiên cơ cấu kỳ hạn dài và nguồn vốn phù hợp với chu kỳ phát triển dự án. Tập đoàn cũng tăng cường quản trị rủi ro pháp lý, kiểm soát tiến độ thi công và chuẩn hóa quy trình phê duyệt đầu tư để bảo đảm tính minh bạch.</p>'
				. qp_holdings_news_figure_html($assets_i . 'vietcombanktower.jpg', 'Hệ thống quản trị tài chính tập trung giúp QP Holdings theo dõi sát hiệu quả từng mảng kinh doanh.')
				. '<h2>Triển vọng năm 2026</h2><p>Tập đoàn duy trì kế hoạch doanh thu 18.500 tỷ đồng và lợi nhuận sau thuế 2.500 tỷ đồng cho năm tài chính 2026. Các động lực chính gồm bàn giao dự án hiện hữu, mở bán sản phẩm mới, khai thác bất động sản công nghiệp và hợp tác với đối tác trong các lĩnh vực đô thị thông minh, năng lượng sạch và logistics.</p>'
				. '<blockquote>Kết quả quý 1 cho thấy QP Holdings đang đi đúng hướng: tăng trưởng doanh thu đi kèm kiểm soát đòn bẩy, chất lượng tài sản và khả năng tạo dòng tiền dài hạn.</blockquote>',
		),
		array(
			'slug' => 'qp-green-park-mo-ban-giai-doan-2',
			'title' => 'QP Green Park chính thức mở bán giai đoạn 2 với hơn 320 sản phẩm',
			'date' => '2026-03-08 10:00:00',
			'image' => $assets_i . 'project-2-BMb1e8Jo.jpg',
			'category' => 'Sự kiện',
			'tax' => array('qp_news_category' => array('su-kien')),
			'excerpt' => 'Giai đoạn 2 dự án QP Green Park giới thiệu dòng sản phẩm shop-house và biệt thự song lập, đáp ứng nhu cầu an cư cao cấp tại Long An.',
			'content' => '<p>QP Green Park chính thức giới thiệu giai đoạn 2 với hơn 320 sản phẩm nhà phố thương mại, biệt thự song lập và biệt thự đơn lập. Giai đoạn mới kế thừa nền tảng quy hoạch xanh, hạ tầng đồng bộ và hệ tiện ích hướng tới cộng đồng cư dân trẻ, chuyên gia và nhà đầu tư dài hạn.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-green-park-khoicong-1.jpg', 'QP Green Park được phát triển theo định hướng khu đô thị xanh, cân bằng giữa tiện nghi đô thị và không gian sinh thái.')
				. '<h2>Quy hoạch xanh và tiện ích cộng đồng</h2><p>Dữ liệu dự án QP Green Park cho thấy khu đô thị được phát triển trên quỹ đất quy mô lớn, chú trọng không gian cây xanh, công viên trung tâm, hồ cảnh quan và các khu sinh hoạt ngoài trời. Những tiện ích này giúp tạo môi trường sống thoáng đãng, đồng thời nâng giá trị khai thác dài hạn cho sản phẩm nhà ở và shophouse.</p>'
				. '<p>Các sản phẩm trong giai đoạn 2 được định vị cho hai nhóm nhu cầu: an cư thực và đầu tư khai thác dịch vụ. Nhà phố thương mại nằm trên các trục giao thông chính phù hợp kinh doanh, trong khi biệt thự song lập và đơn lập hướng tới nhóm khách hàng ưu tiên không gian sống riêng tư, tiện ích nội khu và cộng đồng cư dân có chuẩn sống cao.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-green-park-khoicong-2.jpg', 'Sự kiện dự án có sự tham gia của ban lãnh đạo, đối tác phát triển, tư vấn giám sát và đơn vị thi công.')
				. '<h2>Lợi thế vị trí và nhu cầu ở thực</h2><p>QP Green Park sở hữu lợi thế khi kết nối với các vùng công nghiệp đang phát triển mạnh tại Đông Nam Bộ. Nhu cầu nhà ở cho chuyên gia, kỹ sư, lao động tay nghề cao và các hộ gia đình trẻ tạo nền tảng tiêu thụ bền vững hơn so với các dự án chỉ dựa vào đầu cơ ngắn hạn. Vị trí gần các khu công nghiệp cũng mở ra khả năng khai thác cho thuê, thương mại dịch vụ và tăng giá trị tài sản theo thời gian.</p>'
				. '<h2>Bảo chứng từ hệ sinh thái đối tác</h2><p>Để đảm bảo chất lượng và tiến độ, dự án được triển khai với sự tham gia của các đối tác trong phát triển dự án, thiết kế, tư vấn giám sát và thi công. Cách tiếp cận này giúp QP Holdings kiểm soát chất lượng, tối ưu vận hành và gia tăng tính minh bạch trong quá trình phát triển sản phẩm.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-green-park-khoicong-3.jpg', 'QP Green Park hướng tới cộng đồng cư dân xanh, bền vững và có khả năng khai thác thương mại dài hạn.')
				. qp_holdings_news_source_html('QP Xanh', 'https://qpxanh.com.vn/le-khoi-cong-qp-green-park-hanh-trinh-khoi-tao-cuoc-song-xanh/'),
		),
		array(
			'slug' => 'qp-holdings-ky-mou-doi-tac-nhat-ban',
			'title' => 'QP Holdings ký kết MoU với đối tác Nhật Bản phát triển đô thị thông minh',
			'date' => '2026-02-20 14:00:00',
			'image' => $assets_i . 'vietcombanktower.jpg',
			'category' => 'Thông cáo báo chí',
			'tax' => array('qp_news_category' => array('thong-cao')),
			'excerpt' => 'Hợp tác chiến lược 5 năm với Nikko Urban Group về quy hoạch và quản lý vận hành đô thị thông minh tại Đông Nam Bộ.',
			'content' => '<p>QP Holdings ký kết biên bản ghi nhớ hợp tác với Nikko Urban Group nhằm phát triển các mô hình đô thị thông minh, ứng dụng công nghệ số trong quản lý vận hành và nâng chuẩn trải nghiệm cư dân tại các dự án mới. Thỏa thuận có thời hạn 5 năm, tập trung vào quy hoạch, vận hành, năng lượng sạch và dữ liệu đô thị.</p>'
				. qp_holdings_news_figure_html($assets_i . 'vietcombanktower.jpg', 'Lễ ký kết hợp tác chiến lược được tổ chức tại TP. Hồ Chí Minh với sự tham dự của đại diện hai bên.')
				. '<h2>Trọng tâm hợp tác</h2><p>Theo định hướng của thỏa thuận, Nikko Urban Group sẽ cùng QP Holdings nghiên cứu khung tiêu chuẩn cho khu đô thị thông minh, bao gồm hệ thống quản lý năng lượng, an ninh, dữ liệu cư dân, vận hành tiện ích và quản trị tòa nhà. Mục tiêu là giảm chi phí vận hành, nâng chất lượng dịch vụ và tăng khả năng kết nối giữa cư dân, chủ đầu tư và đơn vị quản lý.</p>'
				. '<p>Đối với các dự án đô thị mới, công nghệ không chỉ là lớp bổ sung sau khi bàn giao mà cần được tích hợp ngay từ giai đoạn quy hoạch. Điều này giúp tối ưu hạ tầng kỹ thuật, bố trí cảm biến, hệ thống điều khiển chiếu sáng, quản lý nước, thu gom rác và các dịch vụ số cho cư dân.</p>'
				. qp_holdings_news_figure_html($assets_i . 'quang-phuc4.jpg', 'Các dự án đô thị kiểu mẫu là nền tảng để QP Holdings thử nghiệm giải pháp vận hành thông minh.')
				. '<h2>Từ bất động sản sang nền tảng dịch vụ</h2><p>QP Holdings xác định đô thị thông minh là bước chuyển từ tư duy bán sản phẩm sang vận hành hệ sinh thái dịch vụ dài hạn. Khi cư dân có thể tương tác với tiện ích, thanh toán, phản ánh sự cố và nhận thông báo qua nền tảng số, chủ đầu tư sẽ có thêm dữ liệu để cải thiện chất lượng vận hành và duy trì giá trị tài sản.</p>'
				. '<h2>Định hướng 2025-2030</h2><p>Giai đoạn 2025-2030, QP Holdings đặt mục tiêu chuẩn hóa mô hình phát triển dự án theo ba trụ cột: xanh, thông minh và bền vững tài chính. Hợp tác với đối tác Nhật Bản là bước đi nhằm tiếp cận kinh nghiệm quản lý vận hành, tiêu chuẩn dịch vụ và tư duy cải tiến liên tục vốn đã được kiểm chứng tại nhiều thị trường phát triển.</p>'
				. qp_holdings_news_source_html('QP Group', 'https://qpgroup.vn/project/khu-do-thi-quang-phuc-4/'),
		),
		array(
			'slug' => 'esg-trach-nhiem-doanh-nghiep',
			'title' => 'ESG: Trách nhiệm doanh nghiệp không còn là lựa chọn mà là tiêu chuẩn',
			'date' => '2026-01-30 08:00:00',
			'image' => $assets_i . 'BG-2.jpg',
			'category' => 'Truyền thông',
			'tax' => array('qp_news_category' => array('truyen-thong')),
			'excerpt' => 'Báo cáo phát triển bền vững 2025 của QP Holdings: 100% dự án mới áp dụng tiêu chuẩn xanh, giảm 22% phát thải vận hành.',
			'content' => '<p>ESG không còn là lựa chọn mang tính hình ảnh mà đang trở thành tiêu chuẩn vận hành đối với doanh nghiệp phát triển bất động sản, hạ tầng và công nghiệp. QP Holdings công bố định hướng phát triển bền vững năm 2025 với trọng tâm là công trình xanh, quản trị minh bạch, cộng đồng cư dân và giảm phát thải trong vận hành.</p>'
				. qp_holdings_news_figure_html($assets_i . 'BG-2.jpg', 'Phát triển bền vững được tích hợp vào quy hoạch, thiết kế và vận hành dự án của QP Holdings.')
				. '<h2>Những chỉ tiêu chính</h2><p>Báo cáo nội bộ của QP Holdings đặt ra ba nhóm chỉ tiêu: 100% dự án mới áp dụng tối thiểu một tiêu chuẩn công trình xanh hoặc bộ tiêu chí tương đương; giảm 22% cường độ phát thải vận hành so với năm 2023; và đầu tư 18 tỷ đồng cho giáo dục, đào tạo nghề, hoạt động cộng đồng tại địa phương có dự án.</p>'
				. '<ul><li>Ưu tiên vật liệu tiết kiệm năng lượng, chiếu sáng hiệu suất cao và giải pháp thông gió tự nhiên.</li><li>Tăng diện tích cây xanh, mặt nước và không gian sinh hoạt cộng đồng trong các dự án đô thị.</li><li>Chuẩn hóa quy trình công bố thông tin, quản trị rủi ro và kiểm soát nhà thầu.</li></ul>'
				. '<h2>ESG tạo giá trị kinh tế như thế nào?</h2><p>Trong bất động sản, tiêu chuẩn xanh giúp giảm chi phí vận hành, tăng khả năng cho thuê, cải thiện giá trị tài sản và tạo lợi thế khi tiếp cận nguồn vốn. Với khách hàng, yếu tố môi trường và chất lượng sống ngày càng ảnh hưởng trực tiếp đến quyết định mua nhà, thuê văn phòng hoặc lựa chọn địa điểm sản xuất.</p>'
				. qp_holdings_news_figure_html($assets_c . 'qp-green-park-khoicong-1.jpg', 'Các dự án đô thị xanh giúp ESG chuyển từ cam kết trên giấy thành trải nghiệm thực tế của cư dân.')
				. '<h2>Cam kết quản trị và cộng đồng</h2><p>QP Holdings xác định ESG phải bắt đầu từ quản trị: lựa chọn dự án phù hợp năng lực tài chính, tuân thủ pháp lý, minh bạch tiến độ và kiểm soát chất lượng thi công. Ở khía cạnh xã hội, tập đoàn ưu tiên tạo việc làm tại địa phương, nâng chuẩn an toàn lao động và tổ chức các chương trình hỗ trợ cộng đồng gắn với vòng đời dự án.</p>'
				. '<blockquote>Phát triển bền vững chỉ có ý nghĩa khi tạo ra giá trị đồng thời cho khách hàng, nhà đầu tư, người lao động, địa phương và môi trường.</blockquote>'
				. qp_holdings_news_source_html('QP Group', 'https://qpgroup.vn/en/about-us/'),
		),
		array(
			'slug' => 'qp-holdings-top-50-doanh-nghiep-niem-yet',
			'title' => 'QP Holdings vào Top 50 doanh nghiệp niêm yết tốt nhất Việt Nam 2025',
			'date' => '2025-12-12 10:00:00',
			'image' => $assets_i . 'quang-phuc-plaza.jpg',
			'category' => 'Thông cáo báo chí',
			'tax' => array('qp_news_category' => array('thong-cao')),
			'excerpt' => 'Lần thứ 3 liên tiếp QP Holdings được Forbes Việt Nam vinh danh trong danh sách Top 50 công ty niêm yết tốt nhất.',
			'content' => '<p>QP Holdings được vinh danh trong nhóm Top 50 doanh nghiệp niêm yết tốt nhất Việt Nam năm 2025, ghi nhận nỗ lực tăng trưởng, quản trị tài chính và chiến lược phát triển hệ sinh thái trong nhiều năm liên tiếp. Đây là lần thứ 3 liên tiếp tập đoàn góp mặt trong danh sách, củng cố vị thế trên thị trường vốn.</p>'
				. qp_holdings_news_figure_html($assets_i . 'quang-phuc-plaza.jpg', 'Danh hiệu Top 50 phản ánh năng lực phát triển dự án, quản trị tài chính và uy tín thương hiệu của QP Holdings.')
				. '<h2>Các tiêu chí nổi bật</h2><p>Danh sách Top 50 thường dựa trên nhiều tiêu chí như doanh thu, lợi nhuận, tỷ suất sinh lời, tăng trưởng vốn hóa, minh bạch thông tin và năng lực duy trì hiệu quả kinh doanh qua các chu kỳ. Đối với QP Holdings, điểm mạnh nằm ở chiến lược đa ngành có trọng tâm: bất động sản, xây dựng, thương mại dịch vụ và đầu tư công nghiệp.</p>'
				. '<p>Trong bối cảnh thị trường bất động sản và chứng khoán biến động, việc duy trì tốc độ tăng trưởng ổn định cho thấy tập đoàn đã kiểm soát tốt danh mục dự án, nợ vay và dòng tiền. Các dự án đô thị, văn phòng, khu công nghiệp và dịch vụ phụ trợ tiếp tục tạo nền tảng cho doanh thu trong các năm tới.</p>'
				. qp_holdings_news_figure_html($assets_i . 'about-Bvbwf05l.jpg', 'QP Holdings xây dựng hệ sinh thái dựa trên bất động sản, xây dựng, thương mại dịch vụ và đầu tư công nghiệp.')
				. '<h2>Ý nghĩa với nhà đầu tư</h2><p>Việc xuất hiện trong nhóm doanh nghiệp niêm yết tốt nhất không chỉ là yếu tố thương hiệu mà còn giúp QP Holdings tăng mức độ nhận diện với nhà đầu tư tổ chức, đối tác tài chính và khách hàng. Danh hiệu này tạo thêm động lực để tập đoàn tiếp tục nâng chuẩn công bố thông tin, quản trị rủi ro và hiệu quả sử dụng vốn.</p>'
				. '<h2>Chiến lược tiếp theo</h2><p>Giai đoạn 2026, QP Holdings đặt mục tiêu mở rộng các dự án có khả năng tạo dòng tiền đều, phát triển đô thị xanh, tăng hợp tác với đối tác công nghệ và chuẩn hóa mô hình vận hành sau bàn giao. Tập đoàn cũng hướng tới việc tích hợp ESG vào từng khâu phát triển dự án để duy trì lợi thế cạnh tranh dài hạn.</p>'
				. '<blockquote>Thành quả trên thị trường vốn chỉ bền vững khi đi cùng kỷ luật tài chính, minh bạch thông tin và năng lực tạo giá trị thật cho khách hàng.</blockquote>'
				. qp_holdings_news_source_html('QP Group', 'https://qpgroup.vn/en/about-us/'),
		),
	);

	// Merge original QP Xanh articles instead of generic encyclopedic content.
	$qpxanh_file = __DIR__ . '/dynamic-news-seed-qpxanh.php';
	if (file_exists($qpxanh_file)) {
		require_once $qpxanh_file;
		if (function_exists('qp_holdings_seed_news_qpxanh')) {
			$items = array_merge($items, qp_holdings_seed_news_qpxanh());
		}
	}

	foreach ($items as $item) {
		$existing = get_page_by_path($item['slug'], OBJECT, 'qp_news');
		$legacy_seeded = $existing instanceof WP_Post && qp_holdings_news_is_legacy_seeded_post($existing);
		if ($existing instanceof WP_Post && !$force && !$legacy_seeded) {
			continue;
		}

		$args = array(
			'post_type' => 'qp_news',
			'post_status' => $existing instanceof WP_Post ? $existing->post_status : 'publish',
			'post_title' => $item['title'],
			'post_name' => $item['slug'],
			'post_date' => $item['date'],
			'post_excerpt' => $item['excerpt'],
			'post_content' => $item['content'],
		);
		if ($existing instanceof WP_Post) {
			$args['ID'] = (int) $existing->ID;
		}

		$post_id = wp_insert_post($args, true);
		if (is_wp_error($post_id) || !$post_id) {
			continue;
		}

		update_post_meta((int) $post_id, 'category_info', $item['category']);
		update_post_meta((int) $post_id, 'card_image', $item['image']);
		if (!empty($item['tax'])) {
			qp_holdings_seed_assign_terms((int) $post_id, $item['tax']);
		}
		update_post_meta((int) $post_id, '_qp_seed_content_version', 'rich-news-v2');
	}
}

/**
 * Remove old Wikipedia-derived seed posts from installations that already
 * received them. Only seeded posts are touched; admin-created posts are kept.
 */
function qp_holdings_remove_wikipedia_seeded_news_once(): void
{
	$flag = 'qp_holdings_remove_wikipedia_seeded_news_v1';
	if (get_option($flag)) {
		return;
	}

	if (!post_type_exists('qp_news')) {
		return;
	}

	$is_cli = defined('WP_CLI') && WP_CLI;
	if (!$is_cli && !is_admin()) {
		return;
	}

	if (!$is_cli && !current_user_can('delete_posts')) {
		return;
	}

	$wiki_slugs = array(
		'thuong-mai-ien-tu',
		'tri-tue-nhan-tao',
		'nang-luong-tai-tao',
		'bien-oi-khi-hau',
		'phat-trien-ben-vung',
		'bat-ong-san',
		'nha-o-xa-hoi',
		'kinh-te-viet-nam',
		'khoi-nghiep',
		'chung-khoan',
		'an-ninh-mang',
		'blockchain',
		'hau-can',
		'nang-luong-mat-troi',
		'nang-luong-gio',
		'doanh-nghiep-nha-nuoc',
		'o-thi-hoa',
		'quy-hoach-o-thi',
		'kinh-te-chia-se',
	);

	$posts = get_posts(
		array(
			'post_type' => 'qp_news',
			'post_status' => array('publish', 'draft', 'private', 'pending', 'future'),
			'posts_per_page' => -1,
			'no_found_rows' => true,
			'meta_key' => '_qp_seed_content_version',
			'meta_value' => 'rich-news-v2',
		)
	);

	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}

		$content = (string) $post->post_content;
		$is_wiki = in_array((string) $post->post_name, $wiki_slugs, true)
			|| false !== stripos($content, 'Wikipedia (CC BY-SA 3.0)')
			|| false !== stripos($content, 'vi.wikipedia.org/wiki');

		if ($is_wiki) {
			wp_trash_post((int) $post->ID);
		}
	}

	update_option($flag, 1);
}
add_action('init', 'qp_holdings_remove_wikipedia_seeded_news_once', 34);

/**
 * Refresh existing seeded news once so installations that already have the
 * short placeholder posts receive the richer crawled/curated content.
 */
function qp_holdings_refresh_rich_news_content_once(): void
{
	$flag = 'qp_holdings_rich_news_content_v4';
	if (get_option($flag)) {
		return;
	}

	if (!post_type_exists('qp_news')) {
		return;
	}

	$is_cli = defined('WP_CLI') && WP_CLI;
	if (!$is_cli && !is_admin()) {
		return;
	}

	if (!$is_cli && !current_user_can('edit_posts')) {
		return;
	}

	qp_holdings_seed_news(false);
	update_option($flag, 1);
}
add_action('init', 'qp_holdings_refresh_rich_news_content_once', 35);

/**
 * Backfill the additional original-article photos onto the already-published
 * HKT restructuring news post (and any translations) once.
 */
function qp_holdings_backfill_hkt_news_images_once(): void
{
	$flag = 'qp_holdings_hkt_news_images_v1';
	if (get_option($flag)) {
		return;
	}

	if (!post_type_exists('qp_news')) {
		return;
	}

	$is_cli = defined('WP_CLI') && WP_CLI;
	if (!$is_cli && !is_admin()) {
		return;
	}
	if (!$is_cli && !current_user_can('edit_posts')) {
		return;
	}

	$assets_c = get_template_directory_uri() . '/assets/crawled-news/';
	$source = 'hau-tai-co-cau-qp-holdings-hkt-len-ke-hoach-lai-tang-578';

	$captions = array(
		'vi' => array(
			'event' => 'Đại hội đồng cổ đông thường niên năm 2026 của QP Holdings (HKT) tổ chức tại TP.HCM.',
			'leaders' => 'Ban lãnh đạo QP Holdings trình bày định hướng chiến lược và danh mục dự án tại đại hội.',
			'chart' => 'Kế hoạch kinh doanh và chỉ tiêu lợi nhuận năm 2026 của QP Holdings (HKT).',
		),
		'en' => array(
			'event' => 'QP Holdings (HKT) 2026 Annual General Meeting of Shareholders held in Ho Chi Minh City.',
			'leaders' => 'QP Holdings leadership presents the strategic direction and project portfolio at the meeting.',
			'chart' => 'QP Holdings (HKT) 2026 business plan and profit targets.',
		),
		'zh' => array(
			'event' => 'QP Holdings（HKT）2026 年股东大会在胡志明市举行。',
			'leaders' => 'QP Holdings 管理层在大会上介绍战略方向与项目组合。',
			'chart' => 'QP Holdings（HKT）2026 年经营计划与利润目标。',
		),
	);

	$posts = get_posts(
		array(
			'post_type' => 'qp_news',
			'post_status' => array('publish', 'draft', 'private', 'pending', 'future'),
			'posts_per_page' => -1,
			'no_found_rows' => true,
			'suppress_filters' => true,
			'lang' => '',
			'post_name__in' => array($source, $source . '-en', $source . '-zh'),
		)
	);

	foreach ($posts as $post) {
		if (!$post instanceof WP_Post) {
			continue;
		}

		$content = (string) $post->post_content;
		if (false !== strpos($content, 'hkt-cafef-3')) {
			continue;
		}

		$lang = (string) get_post_meta($post->ID, '_qp_translation_lang', true);
		$cap = $captions[$lang] ?? $captions['vi'];

		$event = qp_holdings_news_figure_html($assets_c . 'hkt-cafef-3.jpg', $cap['event']);
		$leaders = qp_holdings_news_figure_html($assets_c . 'hkt-cafef-4.jpg', $cap['leaders']);
		$chart = qp_holdings_news_figure_html($assets_c . 'hkt-cafef-6.png', $cap['chart']);

		// Insert the event photo + chart right after the existing cover figure,
		// and the leadership photo before the closing source note when present.
		$pos = strpos($content, '</figure>');
		if (false !== $pos) {
			$insert_at = $pos + strlen('</figure>');
			$content = substr($content, 0, $insert_at) . $event . $chart . substr($content, $insert_at);
		} else {
			$content = $event . $chart . $content;
		}

		$note_pos = strpos($content, '<p><em>Theo');
		if (false !== $note_pos) {
			$content = substr($content, 0, $note_pos) . $leaders . substr($content, $note_pos);
		} else {
			$content .= $leaders;
		}

		wp_update_post(
			array(
				'ID' => (int) $post->ID,
				'post_content' => $content,
			)
		);
	}

	update_option($flag, 1);
}
add_action('init', 'qp_holdings_backfill_hkt_news_images_once', 36);

/**
 * Automatically format YouTube Shorts embeds into portrait 9:16 aspect ratio.
 */
function qp_holdings_format_youtube_shorts_embeds(string $html): string
{
	if (false === stripos($html, 'shorts')) {
		return $html;
	}

	// Transform oEmbed or Gutenberg embed blocks containing shorts
	$html = preg_replace_callback(
		'#<figure\s+class="([^"]*wp-block-embed[^"]*)"\s*>(.*?)</figure>#is',
		static function ($matches) {
			$classes = $matches[1];
			$inner = $matches[2];
			if (false !== stripos($inner, 'shorts/') || false !== stripos($inner, '/shorts')) {
				$classes = str_replace(array('wp-embed-aspect-16-9', 'wp-has-aspect-ratio'), '', $classes);
				$classes .= ' is-shorts qp-video-shorts-wrapper';
				return '<figure class="' . trim($classes) . '">' . $inner . '</figure>';
			}
			return $matches[0];
		},
		$html
	);

	// Also catch any standalone iframes with /shorts/ in src
	$html = preg_replace_callback(
		'#<iframe\s+([^>]*src="[^"]*(?:youtube\.com/shorts/|youtube\.com/embed/)[^"]*"[^>]*)></iframe>#is',
		static function ($matches) {
			$iframe_tag = $matches[0];
			if (false !== stripos($iframe_tag, 'shorts')) {
				return '<div class="qp-video-shorts-wrapper">' . $iframe_tag . '</div>';
			}
			return $iframe_tag;
		},
		$html
	);

	return $html;
}
add_filter('the_content', 'qp_holdings_format_youtube_shorts_embeds', 25);
add_filter('embed_oembed_html', 'qp_holdings_format_youtube_shorts_embeds', 25);

