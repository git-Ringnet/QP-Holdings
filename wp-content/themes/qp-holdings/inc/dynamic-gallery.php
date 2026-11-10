<?php
/**
 * Home "Thư viện hình ảnh & video" gallery section.
 *
 * Items are managed through the `qp_gallery_item` CPT (registered in
 * dynamic-content.php). Each item is either an image or a video:
 *   - gallery_type   : 'image' | 'video'
 *   - gallery_image  : display/thumbnail image (URL via ACF or meta)
 *   - gallery_video  : video URL (YouTube watch/short link or direct .mp4)
 *   - menu_order     : drag-drop order in admin
 *
 * The front page renders the first few cards and passes the FULL item list to
 * the lightbox so "Xem tất cả" opens the lightbox showing everything (images
 * inline, videos embedded).
 */

defined( 'ABSPATH' ) || exit;

/**
 * ACF fields for each gallery item (type + image + video URL).
 */
function qp_holdings_register_gallery_acf(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_gallery_item',
			'title'    => 'Nội dung ảnh / video',
			'fields'   => array(
				array(
					'key'           => 'field_qp_gallery_type',
					'label'         => 'Loại nội dung',
					'name'          => 'gallery_type',
					'type'          => 'select',
					'choices'       => array(
						'image' => 'Hình ảnh',
						'video' => 'Video',
					),
					'default_value' => 'image',
					'return_format' => 'value',
					'instructions'  => 'Chọn "Video" nếu mục này là video (YouTube hoặc tệp MP4).',
				),
				array(
					'key'           => 'field_qp_gallery_image',
					'label'         => 'Ảnh hiển thị',
					'name'          => 'gallery_image',
					'type'          => 'image',
					'return_format' => 'url',
					'instructions'  => 'Ảnh hiển thị trên thẻ. Với video, đây là ảnh thumbnail. Có thể dùng "Ảnh đại diện" (featured image) thay thế.',
				),
				array(
					'key'           => 'field_qp_gallery_video',
					'label'         => 'Liên kết video',
					'name'          => 'gallery_video',
					'type'          => 'url',
					'instructions'  => 'Dán link YouTube (https://youtu.be/... hoặc https://www.youtube.com/watch?v=...) hoặc link tệp .mp4. Chỉ dùng khi Loại nội dung = Video.',
					'conditional_logic' => array(
						array(
							array(
								'field'    => 'field_qp_gallery_type',
								'operator' => '==',
								'value'    => 'video',
							),
						),
					),
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_gallery_item' ),
				),
			),
		)
	);
}
add_action( 'acf/init', 'qp_holdings_register_gallery_acf' );

/**
 * Admin list columns: thumbnail + type + order.
 */
function qp_holdings_gallery_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['qp_gallery_thumb'] = 'Ảnh';
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['qp_gallery_type']  = 'Loại';
			$new['qp_gallery_order'] = 'Thứ tự';
		}
	}
	return $new;
}
add_filter( 'manage_qp_gallery_item_posts_columns', 'qp_holdings_gallery_admin_columns' );

function qp_holdings_gallery_admin_column_content( string $column, int $post_id ): void {
	if ( 'qp_gallery_thumb' === $column ) {
		$url = qp_holdings_gallery_item_image_url( $post_id );
		echo $url
			? '<img src="' . esc_url( $url ) . '" alt="" style="width:64px;height:42px;object-fit:cover;border-radius:4px;">'
			: '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( 'qp_gallery_type' === $column ) {
		$type = (string) qp_holdings_acf_or_meta( $post_id, 'gallery_type', 'gallery_type' );
		echo 'video' === $type
			? '<span class="dashicons dashicons-video-alt3" style="color:#D3A049;"></span> Video'
			: '<span class="dashicons dashicons-format-image" style="color:#8c8f94;"></span> Hình ảnh'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} elseif ( 'qp_gallery_order' === $column ) {
		echo '<span class="dashicons dashicons-menu" style="color:#8c8f94;cursor:move;" title="Kéo để sắp xếp"></span> '
			. '<span style="color:#8c8f94;">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
	}
}
add_action( 'manage_qp_gallery_item_posts_custom_column', 'qp_holdings_gallery_admin_column_content', 10, 2 );

/**
 * Default the gallery list to ascending menu_order in admin.
 */
function qp_holdings_gallery_admin_default_order( $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'qp_gallery_item' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'qp_holdings_gallery_admin_default_order' );

/**
 * Drag-and-drop ordering on the gallery list table (mirrors board members).
 */
function qp_holdings_gallery_admin_sortable_assets( string $hook ): void {
	if ( 'edit.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'qp_gallery_item' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_script( 'jquery-ui-sortable' );

	$nonce  = wp_create_nonce( 'qp_gallery_reorder' );
	$inline = <<<JS
jQuery(function ($) {
	var \$tbody = $('#the-list');
	if (!\$tbody.length) { return; }
	\$tbody.find('tr').css('cursor', 'move');

	var \$status = $('<div style="display:none;position:fixed;top:46px;right:24px;z-index:9999;padding:10px 16px;border-radius:6px;background:#0B1B2A;color:#fff;font-size:13px;box-shadow:0 4px 16px rgba(0,0,0,0.25);"></div>').appendTo('body');
	function flash(msg, ok) {
		\$status.text(msg).css('background', ok ? '#1e7e34' : '#b32d2e').fadeIn(120);
		clearTimeout(\$status.data('t'));
		\$status.data('t', setTimeout(function () { \$status.fadeOut(300); }, 1800));
	}

	\$tbody.sortable({
		items: 'tr', axis: 'y', cursor: 'move', opacity: 0.7,
		placeholder: 'qp-gallery-sort-placeholder',
		helper: function (e, tr) {
			var \$orig = tr.children();
			var \$helper = tr.clone();
			\$helper.children().each(function (i) { $(this).width(\$orig.eq(i).width()); });
			return \$helper;
		},
		update: function () {
			var ids = [];
			\$tbody.find('tr').each(function () {
				var id = ($(this).attr('id') || '').replace('post-', '');
				if (id) { ids.push(id); }
			});
			if (!ids.length) { return; }
			\$tbody.sortable('disable');
			$.post(ajaxurl, { action: 'qp_gallery_reorder', nonce: '{$nonce}', order: ids })
				.done(function (res) { flash((res && res.data && res.data.message) ? res.data.message : 'Đã lưu thứ tự', !!(res && res.success)); })
				.fail(function () { flash('Lưu thứ tự thất bại', false); })
				.always(function () { \$tbody.sortable('enable'); });
		}
	});
});
JS;
	wp_add_inline_script( 'jquery-ui-sortable', $inline );
	wp_add_inline_style( 'wp-admin', '.qp-gallery-sort-placeholder{outline:2px dashed #D3A049;background:#fff8e9;}.qp-gallery-sort-placeholder td{visibility:hidden;}' );
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_gallery_admin_sortable_assets' );

/**
 * Persist drag-and-drop order from the gallery list table.
 */
function qp_holdings_gallery_reorder_ajax(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	check_ajax_referer( 'qp_gallery_reorder', 'nonce' );

	$order = isset( $_POST['order'] ) ? (array) wp_unslash( $_POST['order'] ) : array();
	$order = array_values( array_filter( array_map( 'absint', $order ) ) );
	if ( empty( $order ) ) {
		wp_send_json_error( array( 'message' => 'Danh sách trống.' ) );
	}

	$position = 0;
	foreach ( $order as $post_id ) {
		if ( 'qp_gallery_item' !== get_post_type( $post_id ) ) {
			continue;
		}
		$position += 10;
		wp_update_post( array( 'ID' => $post_id, 'menu_order' => $position ) );
	}

	wp_send_json_success( array( 'message' => 'Đã lưu thứ tự mới.' ) );
}
add_action( 'wp_ajax_qp_gallery_reorder', 'qp_holdings_gallery_reorder_ajax' );

/**
 * Resolve a gallery item's display image URL (ACF image → meta → featured).
 */
function qp_holdings_gallery_item_image_url( int $post_id ): string {
	$image = qp_holdings_acf_or_meta( $post_id, 'gallery_image', 'gallery_image' );
	$url   = qp_holdings_image_url( $image, $post_id, 'large' );
	return $url;
}

/**
 * Normalize a video URL into an embeddable form for the lightbox.
 *
 * @return array{kind:string,embed:string,src:string} kind = youtube|youtube_shorts|mp4|external|''
 */
function qp_holdings_gallery_video_info( string $url ): array {
	$url = trim( $url );
	if ( '' === $url ) {
		return array( 'kind' => '', 'embed' => '', 'src' => '' );
	}

	// YouTube Shorts (youtube.com/shorts/{id})
	if ( preg_match( '#(?:youtube\.com/shorts/)([A-Za-z0-9_-]{11})#i', $url, $m ) ) {
		$id = $m[1];
		return array(
			'kind'  => 'youtube_shorts',
			'embed' => 'https://www.youtube.com/embed/' . $id . '?rel=0',
			'src'   => $url,
		);
	}

	// YouTube (watch?v=, youtu.be/, /embed/).
	if ( preg_match( '#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $url, $m ) ) {
		$id = $m[1];
		return array(
			'kind'  => 'youtube',
			'embed' => 'https://www.youtube.com/embed/' . $id . '?rel=0',
			'src'   => $url,
		);
	}

	// Direct video file.
	if ( preg_match( '#\.(mp4|webm|ogg|mov)(\?.*)?$#i', $url ) ) {
		return array( 'kind' => 'mp4', 'embed' => $url, 'src' => $url );
	}

	// TikTok links (handled safely via external preview card, never raw crashing iframes).
	if ( preg_match( '#(?:tiktok\.com)#i', $url ) ) {
		return array( 'kind' => 'tiktok', 'embed' => '', 'src' => $url );
	}

	// Any other external link (Facebook, Vimeo, web link): NEVER embed as iframe to prevent browser freeze.
	return array( 'kind' => 'external', 'embed' => '', 'src' => $url );
}

/**
 * Fetch published gallery items ordered by menu_order.
 *
 * @return WP_Post[]
 */
function qp_holdings_get_gallery_items(): array {
	$q_args = array(
		'post_type'      => 'qp_gallery_item',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
	);
	$q = function_exists( 'qp_holdings_wp_query_with_language_fallback' )
		? qp_holdings_wp_query_with_language_fallback( $q_args )
		: new WP_Query( $q_args );
	$items = $q->have_posts() ? $q->posts : array();
	wp_reset_postdata();
	return is_array( $items ) ? $items : array();
}

/**
 * Build a normalized array of gallery entries for rendering + the lightbox.
 *
 * Each entry: [ 'type'=>image|video, 'image'=>url, 'video_kind'=>'', 'video'=>url, 'embed'=>url, 'title'=>str ]
 * Falls back to the original 4 static images when no CPT items exist yet.
 *
 * @return array<int,array<string,string>>
 */
function qp_holdings_gallery_entries(): array {
	$entries      = array();
	$current_lang = function_exists( 'qp_holdings_current_language' ) ? qp_holdings_current_language() : 'vi';

	foreach ( qp_holdings_get_gallery_items() as $item ) {
		$target_id = (int) $item->ID;
		if ( 'vi' !== $current_lang && function_exists( 'pll_get_post' ) ) {
			$trans_id = (int) pll_get_post( $target_id, $current_lang );
			if ( $trans_id && 'publish' === get_post_status( $trans_id ) ) {
				$target_id = $trans_id;
			}
		}

		$image = qp_holdings_gallery_item_image_url( $target_id );
		if ( '' === $image && $target_id !== (int) $item->ID ) {
			$image = qp_holdings_gallery_item_image_url( (int) $item->ID );
		}

		$type = (string) qp_holdings_acf_or_meta( $target_id, 'gallery_type', 'gallery_type' );
		if ( '' === $type && $target_id !== (int) $item->ID ) {
			$type = (string) qp_holdings_acf_or_meta( (int) $item->ID, 'gallery_type', 'gallery_type' );
		}
		$type = ( 'video' === $type ) ? 'video' : 'image';

		$title = get_the_title( $target_id );

		$entry = array(
			'type'       => $type,
			'image'      => $image,
			'title'      => $title,
			'video'      => '',
			'video_kind' => '',
			'embed'      => '',
		);
		if ( 'video' === $type ) {
			$vurl = (string) qp_holdings_acf_or_meta( $target_id, 'gallery_video', 'gallery_video' );
			if ( '' === $vurl && $target_id !== (int) $item->ID ) {
				$vurl = (string) qp_holdings_acf_or_meta( (int) $item->ID, 'gallery_video', 'gallery_video' );
			}
			$info = qp_holdings_gallery_video_info( $vurl );
			$entry['video']      = $info['src'];
			$entry['video_kind'] = $info['kind'];
			$entry['embed']      = $info['embed'];
		}
		if ( '' !== $entry['image'] || '' !== $entry['embed'] || '' !== $entry['video'] ) {
			$entries[] = $entry;
		}
	}

	if ( ! empty( $entries ) ) {
		return $entries;
	}

	// Fallback to the bundled static set so the section is never empty.
	$fallback = array(
		'assets/site/commitment-6e3b08.png',
		'assets/site/gallery-2-43cfe8.png',
		'assets/site/gallery-3-373dfa.png',
		'assets/site/gallery-4-3ee6f1.png',
	);
	foreach ( $fallback as $i => $rel ) {
		$entries[] = array(
			'type'       => 'image',
			'image'      => function_exists( 'qp_holdings_theme_image_largest_src' ) ? qp_holdings_theme_image_largest_src( $rel ) : ( get_template_directory_uri() . '/' . $rel ),
			'title'      => 'QP Holdings - Khoảnh khắc ' . ( $i + 1 ),
			'video'      => '',
			'video_kind' => '',
			'embed'      => '',
		);
	}
	return $entries;
}

/**
 * Seed gallery items from the original 4 home-page images (idempotent).
 */
function qp_holdings_seed_gallery_items( bool $force = false ): void {
	if ( ! $force ) {
		$existing = get_posts(
			array(
				'post_type'        => 'qp_gallery_item',
				'post_status'      => array( 'publish', 'draft', 'private', 'pending', 'trash' ),
				'posts_per_page'   => 1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		if ( ! empty( $existing ) ) {
			return;
		}
	}

	$items = array(
		array( 'slug' => 'khoanh-khac-1', 'title' => 'Khoảnh khắc QP Holdings 1', 'img' => 'assets/site/commitment-6e3b08.png' ),
		array( 'slug' => 'khoanh-khac-2', 'title' => 'Khoảnh khắc QP Holdings 2', 'img' => 'assets/site/gallery-2-43cfe8.png' ),
		array( 'slug' => 'khoanh-khac-3', 'title' => 'Khoảnh khắc QP Holdings 3', 'img' => 'assets/site/gallery-3-373dfa.png' ),
		array( 'slug' => 'khoanh-khac-4', 'title' => 'Khoảnh khắc QP Holdings 4', 'img' => 'assets/site/gallery-4-3ee6f1.png' ),
	);

	$order = 0;
	foreach ( $items as $it ) {
		$order  += 10;
		$post_id = qp_holdings_seed_upsert_post(
			array(
				'post_type'   => 'qp_gallery_item',
				'post_name'   => 'tv-anh-' . $it['slug'],
				'post_title'  => $it['title'],
				'post_status' => 'publish',
				'menu_order'  => $order,
			),
			$force
		);
		if ( ! $post_id ) {
			continue;
		}
		update_post_meta( $post_id, 'gallery_type', 'image' );
		update_post_meta( $post_id, 'gallery_image', $it['img'] );
	}
}

/**
 * Render the home gallery cards (first N) + a hidden full list for the
 * lightbox. Returns the inner HTML for the .gallery-swiper wrapper plus a
 * JSON payload the front-page script reads.
 *
 * @param int $preview How many cards to show in the strip.
 * @return array{cards:string,payload:string,count:int}
 */
function qp_holdings_render_gallery_cards( int $preview = 4 ): array {
	$entries = qp_holdings_gallery_entries();
	$count   = count( $entries );

	$cards = '';
	$delay = 0;
	foreach ( array_slice( $entries, 0, $preview ) as $i => $entry ) {
		$delay_class = $i > 0 ? ' reveal-delay-' . min( $i, 3 ) : '';
		$featured    = 0 === $i ? ' featured' : '';
		$is_video    = 'video' === $entry['type'];
		$img         = '';
		if ( '' !== $entry['image'] ) {
			$img = sprintf(
				'<img src="%s" alt="%s" loading="lazy" decoding="async">',
				esc_url( $entry['image'] ),
				esc_attr( $entry['title'] )
			);
		}
		$badge = $is_video
			? '<span class="gallery-card__play" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="11" fill="rgba(25,54,96,0.55)"/><path d="M10 8.5l6 3.5-6 3.5z" fill="#fff"/></svg></span>'
			: '';
		$glow = 0 === $i ? '<div class="gallery-glow" aria-hidden="true"></div>' : '';

		if ( $is_video && 'external' === $entry['video_kind'] ) {
			$cards .= sprintf(
				'<a class="gallery-card%s reveal%s swiper-slide" href="%s" target="_blank" rel="noopener noreferrer" data-index="%d">%s%s%s</a>',
				esc_attr( $featured ),
				esc_attr( $delay_class ),
				esc_url( $entry['video'] ?: $entry['image'] ),
				$i,
				$img,
				$badge,
				$glow
			);
		} else {
			$cards .= sprintf(
				'<a class="gallery-card%s reveal%s swiper-slide" href="%s" data-gallery-lightbox data-index="%d">%s%s%s</a>',
				esc_attr( $featured ),
				esc_attr( $delay_class ),
				esc_url( $is_video ? ( $entry['video'] ?: $entry['image'] ) : $entry['image'] ),
				$i,
				$img,
				$badge,
				$glow
			);
		}
	}

	// Payload for the lightbox: every entry, with type + embed for videos.
	$payload_items = array();
	foreach ( $entries as $entry ) {
		$payload_items[] = array(
			'type'  => $entry['type'],
			'image' => $entry['image'],
			'embed' => $entry['embed'],
			'kind'  => $entry['video_kind'],
			'video' => $entry['video'],
			'title' => $entry['title'],
		);
	}
	$payload = wp_json_encode( $payload_items );

	return array(
		'cards'   => $cards,
		'payload' => $payload ? $payload : '[]',
		'count'   => $count,
	);
}

/**
 * Safety fallback script to guarantee lightbox dismiss and scroll recovery across all devices/browsers.
 */
function qp_holdings_gallery_lightbox_safety_script(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<script id="qp-gallery-lightbox-safety">
	(function() {
		function dismissAllLightboxes() {
			var activeBoxes = document.querySelectorAll('.qp-lightbox.is-open, .qp-lightbox');
			activeBoxes.forEach(function(box) {
				box.classList.remove('is-open');
				var iframes = box.querySelectorAll('iframe');
				iframes.forEach(function(f) { try { f.src = 'about:blank'; } catch(e){} });
				var videos = box.querySelectorAll('video');
				videos.forEach(function(v) { try { v.pause(); v.src = ''; } catch(e){} });
				var videoWrap = box.querySelector('.qp-lightbox__video-wrap');
				if (videoWrap) { videoWrap.innerHTML = ''; videoWrap.style.display = 'none'; }
			});
			document.body.classList.remove('qp-lightbox-open');
			document.body.style.overflow = '';
			document.documentElement.style.overflow = '';
		}

		document.addEventListener('click', function(e) {
			var openBox = document.querySelector('.qp-lightbox.is-open');
			if (!openBox) return;

			if (e.target.closest('.qp-lightbox__prev, .qp-lightbox__next, .qp-lightbox__video, .qp-lightbox__iframe, .qp-lightbox__ext-btn')) {
				return;
			}
			e.preventDefault();
			dismissAllLightboxes();
		}, true);

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				dismissAllLightboxes();
			}
		}, true);

		window.addEventListener('popstate', function() {
			dismissAllLightboxes();
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'qp_holdings_gallery_lightbox_safety_script', 99 );

