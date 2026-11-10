<?php
/**
 * Custom project meta boxes (gallery + hero highlights).
 *
 * These replace ACF PRO-only field types (`gallery`, `repeater`) which do not
 * render under the bundled ACF free plugin. Data is stored as post meta the
 * front-end already reads:
 *  - `gallery`                          => array of attachment IDs
 *  - `hero_highlights`                  => row count (int)
 *  - `hero_highlights_{i}_highlight_title|highlight_short_description|highlight_body`
 *
 * @package QP_Holdings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register both project meta boxes.
 */
function qp_holdings_register_project_meta_boxes(): void {
	add_meta_box(
		'qp_project_gallery_box',
		'Thư viện ảnh dự án',
		'qp_holdings_render_project_gallery_metabox',
		'qp_project',
		'normal',
		'high'
	);
	add_meta_box(
		'qp_project_highlights_box',
		'Điểm nổi bật (hero)',
		'qp_holdings_render_project_highlights_metabox',
		'qp_project',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'qp_holdings_register_project_meta_boxes' );

/**
 * Enqueue the media library + sortable on the project editor screen.
 */
function qp_holdings_project_meta_admin_assets( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'qp_project' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'jquery-ui-sortable' );
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_project_meta_admin_assets' );

/**
 * Default meta-box order for the project editor so the admin layout mirrors the
 * public detail page: Thông tin dự án → Điểm nổi bật (hero) → Thư viện ảnh.
 *
 * Only applied when the editor has not saved a personal arrangement, so manual
 * drag-and-drop keeps winning.
 *
 * @param mixed $value Stored per-user order (false when none saved).
 * @return mixed
 */
function qp_holdings_project_default_metabox_order( $value ) {
	if ( ! empty( $value ) ) {
		return $value;
	}
	return array(
		'normal'   => implode(
			',',
			array(
				'acf-group_qp_project',
				'qp_project_highlights_box',
				'qp_project_gallery_box',
				'postexcerpt',
				'slugdiv',
			)
		),
		'side'     => '',
		'advanced' => '',
	);
}
add_filter( 'get_user_option_meta-box-order_qp_project', 'qp_holdings_project_default_metabox_order' );


/* ---------------------------------------------------------------------------
 * Gallery meta box
 * ------------------------------------------------------------------------- */

/**
 * Normalise the stored gallery meta into an array of valid attachment IDs.
 *
 * @return int[]
 */
function qp_holdings_project_gallery_ids( int $post_id ): array {
	$raw = get_post_meta( $post_id, 'gallery', true );
	if ( is_string( $raw ) ) {
		$raw = '' === trim( $raw ) ? array() : explode( ',', $raw );
	}
	if ( ! is_array( $raw ) ) {
		return array();
	}
	$ids = array();
	foreach ( $raw as $value ) {
		$id = is_array( $value ) ? (int) ( $value['id'] ?? 0 ) : (int) $value;
		if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}
	return $ids;
}

/**
 * Render the gallery picker meta box.
 */
function qp_holdings_render_project_gallery_metabox( WP_Post $post ): void {
	wp_nonce_field( 'qp_project_gallery_save', 'qp_project_gallery_nonce' );
	$ids = qp_holdings_project_gallery_ids( $post->ID );
	?>
	<style>
		.qp-pgx-help{margin:0 0 12px;color:#555}
		.qp-pgx-grid{display:flex;flex-wrap:wrap;gap:12px;margin-bottom:12px}
		.qp-pgx-item{position:relative;width:120px;height:120px;border:1px solid #dcdcde;border-radius:8px;overflow:hidden;background:#f6f7f7;cursor:move}
		.qp-pgx-item img{width:100%;height:100%;object-fit:cover;display:block;pointer-events:none}
		.qp-pgx-remove{position:absolute;top:4px;right:4px;width:24px;height:24px;line-height:1;border:none;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;font-size:16px;cursor:pointer}
		.qp-pgx-remove:hover{background:#b32d2e}
		.qp-pgx-empty{color:#646970;font-style:italic;margin:0 0 12px}
		.qp-pgx-item.ui-sortable-helper{box-shadow:0 4px 12px rgba(0,0,0,.25)}
	</style>
	<p class="qp-pgx-help">Chọn nhiều ảnh từ Thư viện media. Kéo-thả để sắp xếp thứ tự — ảnh đầu tiên sẽ là ảnh trung tâm trên trang chi tiết.</p>
	<input type="hidden" id="qp-pgx-ids" name="qp_project_gallery" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<div id="qp-pgx-grid" class="qp-pgx-grid">
		<?php
		if ( empty( $ids ) ) {
			echo '<p class="qp-pgx-empty" data-empty>Chưa có ảnh nào. Bấm “Thêm ảnh” để chọn.</p>';
		} else {
			foreach ( $ids as $id ) {
				$thumb = wp_get_attachment_image_url( $id, 'thumbnail' );
				if ( ! $thumb ) {
					continue;
				}
				printf(
					'<div class="qp-pgx-item" data-id="%1$d"><img src="%2$s" alt=""><button type="button" class="qp-pgx-remove" aria-label="Xoá ảnh">&times;</button></div>',
					(int) $id,
					esc_url( $thumb )
				);
			}
		}
		?>
	</div>
	<button type="button" class="button button-secondary" id="qp-pgx-add">＋ Thêm ảnh</button>
	<?php
	qp_holdings_project_gallery_metabox_script();
}

/**
 * Inline JS for the gallery picker: media frame, remove, sortable reorder.
 */
function qp_holdings_project_gallery_metabox_script(): void {
	?>
	<script>
	(function($){
		var grid = document.getElementById('qp-pgx-grid');
		var input = document.getElementById('qp-pgx-ids');
		var addBtn = document.getElementById('qp-pgx-add');
		var frame = null;

		function syncIds(){
			var ids = [];
			grid.querySelectorAll('.qp-pgx-item').forEach(function(el){
				var id = parseInt(el.getAttribute('data-id'), 10);
				if(id){ ids.push(id); }
			});
			input.value = ids.join(',');
		}

		function removeEmpty(){
			var empty = grid.querySelector('[data-empty]');
			if(empty){ empty.remove(); }
		}

		function addItem(att){
			if(input.value.split(',').indexOf(String(att.id)) !== -1){ return; }
			var url = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
			var item = document.createElement('div');
			item.className = 'qp-pgx-item';
			item.setAttribute('data-id', att.id);
			item.innerHTML = '<img src="' + url + '" alt=""><button type="button" class="qp-pgx-remove" aria-label="Xoá ảnh">&times;</button>';
			grid.appendChild(item);
		}

		addBtn.addEventListener('click', function(e){
			e.preventDefault();
			if(frame){ frame.open(); return; }
			frame = wp.media({
				title: 'Chọn ảnh cho thư viện dự án',
				button: { text: 'Dùng ảnh đã chọn' },
				library: { type: 'image' },
				multiple: 'add'
			});
			frame.on('select', function(){
				removeEmpty();
				frame.state().get('selection').toJSON().forEach(addItem);
				syncIds();
			});
			frame.open();
		});

		grid.addEventListener('click', function(e){
			if(e.target.classList.contains('qp-pgx-remove')){
				e.preventDefault();
				var item = e.target.closest('.qp-pgx-item');
				if(item){ item.remove(); }
				syncIds();
				if(!grid.querySelector('.qp-pgx-item')){
					var p = document.createElement('p');
					p.className = 'qp-pgx-empty';
					p.setAttribute('data-empty','');
					p.textContent = 'Chưa có ảnh nào. Bấm “Thêm ảnh” để chọn.';
					grid.appendChild(p);
				}
			}
		});

		if($.fn.sortable){
			$(grid).sortable({ items: '.qp-pgx-item', tolerance: 'pointer', update: syncIds });
		}
	})(jQuery);
	</script>
	<?php
}

/**
 * Persist the gallery attachment IDs.
 */
function qp_holdings_save_project_gallery_metabox( int $post_id ): void {
	if ( ! isset( $_POST['qp_project_gallery_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qp_project_gallery_nonce'] ) ), 'qp_project_gallery_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw = isset( $_POST['qp_project_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['qp_project_gallery'] ) ) : '';
	$ids = array();
	foreach ( array_filter( explode( ',', $raw ) ) as $value ) {
		$id = absint( $value );
		if ( $id > 0 && 'attachment' === get_post_type( $id ) && ! in_array( $id, $ids, true ) ) {
			$ids[] = $id;
		}
	}

	if ( $ids ) {
		update_post_meta( $post_id, 'gallery', $ids );
	} else {
		delete_post_meta( $post_id, 'gallery' );
	}
	// Drop the orphaned ACF reference left by earlier seeding.
	delete_post_meta( $post_id, '_gallery' );
}
add_action( 'save_post_qp_project', 'qp_holdings_save_project_gallery_metabox' );

/* ---------------------------------------------------------------------------
 * Hero highlights meta box
 * ------------------------------------------------------------------------- */

/**
 * Read stored highlight rows.
 *
 * @return array<int,array{title:string,short:string,body:string}>
 */
function qp_holdings_project_highlight_rows( int $post_id ): array {
	$count = (int) get_post_meta( $post_id, 'hero_highlights', true );
	$rows  = array();
	for ( $i = 0; $i < $count; $i++ ) {
		$prefix = 'hero_highlights_' . $i . '_';
		$rows[] = array(
			'title' => (string) get_post_meta( $post_id, $prefix . 'highlight_title', true ),
			'short' => (string) get_post_meta( $post_id, $prefix . 'highlight_short_description', true ),
			'body'  => (string) get_post_meta( $post_id, $prefix . 'highlight_body', true ),
		);
	}
	return $rows;
}

/**
 * Render one highlight row. $index may be "__i__" for the JS template.
 *
 * @param array{title:string,short:string,body:string} $row
 */
function qp_holdings_render_project_highlight_row( $index, array $row ): void {
	$name = 'qp_project_highlights[' . $index . ']';
	?>
	<div class="qp-phx-row">
		<button type="button" class="qp-phx-remove" aria-label="Xoá điểm nổi bật">&times;</button>
		<label class="qp-phx-field">
			<span>Tiêu đề</span>
			<input type="text" name="<?php echo esc_attr( $name ); ?>[title]" value="<?php echo esc_attr( $row['title'] ); ?>">
		</label>
		<label class="qp-phx-field">
			<span>Mô tả ngắn</span>
			<textarea name="<?php echo esc_attr( $name ); ?>[short]" rows="2"><?php echo esc_textarea( $row['short'] ); ?></textarea>
		</label>
		<label class="qp-phx-field">
			<span>Mô tả đầy đủ (tuỳ chọn)</span>
			<textarea name="<?php echo esc_attr( $name ); ?>[body]" rows="3"><?php echo esc_textarea( $row['body'] ); ?></textarea>
		</label>
	</div>
	<?php
}

/**
 * Render the highlights meta box.
 */
function qp_holdings_render_project_highlights_metabox( WP_Post $post ): void {
	wp_nonce_field( 'qp_project_highlights_save', 'qp_project_highlights_nonce' );
	$rows = qp_holdings_project_highlight_rows( $post->ID );
	?>
	<style>
		.qp-phx-help{margin:0 0 12px;color:#555}
		.qp-phx-row{position:relative;border:1px solid #dcdcde;border-radius:8px;padding:16px 40px 16px 16px;margin-bottom:12px;background:#fff}
		.qp-phx-field{display:flex;flex-direction:column;gap:4px;margin-bottom:10px}
		.qp-phx-field:last-child{margin-bottom:0}
		.qp-phx-field>span{font-weight:600;font-size:12px;color:#1d2327}
		.qp-phx-field input,.qp-phx-field textarea{width:100%}
		.qp-phx-remove{position:absolute;top:8px;right:8px;width:26px;height:26px;line-height:1;border:1px solid #dcdcde;border-radius:50%;background:#fff;color:#b32d2e;font-size:18px;cursor:pointer}
		.qp-phx-remove:hover{background:#b32d2e;color:#fff;border-color:#b32d2e}
		.qp-phx-empty{color:#646970;font-style:italic;margin:0 0 12px}
	</style>
	<p class="qp-phx-help">Mỗi mục gồm tiêu đề + mô tả. Số thứ tự (01, 02…) tự sinh theo vị trí. Tối đa khuyến nghị 4 mục. Để trống toàn bộ sẽ dùng phần “Điểm nổi bật (cũ)” làm dự phòng.</p>
	<div id="qp-phx-list">
		<?php
		if ( empty( $rows ) ) {
			echo '<p class="qp-phx-empty" data-empty>Chưa có điểm nổi bật nào. Bấm “Thêm điểm nổi bật” để bắt đầu.</p>';
		} else {
			foreach ( $rows as $i => $row ) {
				qp_holdings_render_project_highlight_row( $i, $row );
			}
		}
		?>
	</div>
	<button type="button" class="button button-secondary" id="qp-phx-add">＋ Thêm điểm nổi bật</button>
	<script type="text/html" id="qp-phx-tmpl">
		<?php qp_holdings_render_project_highlight_row( '__i__', array( 'title' => '', 'short' => '', 'body' => '' ) ); ?>
	</script>
	<script>
	(function(){
		var list = document.getElementById('qp-phx-list');
		var addBtn = document.getElementById('qp-phx-add');
		var tmpl = document.getElementById('qp-phx-tmpl').innerHTML;
		var nextIndex = <?php echo (int) count( $rows ); ?>;

		function reindex(){
			list.querySelectorAll('.qp-phx-row').forEach(function(row, idx){
				row.querySelectorAll('input,textarea').forEach(function(el){
					if(!el.name){ return; }
					el.name = el.name.replace(/qp_project_highlights\[[^\]]*\]/, 'qp_project_highlights['+idx+']');
				});
			});
		}

		addBtn.addEventListener('click', function(){
			var empty = list.querySelector('[data-empty]');
			if(empty){ empty.remove(); }
			var wrap = document.createElement('div');
			wrap.innerHTML = tmpl.replace(/__i__/g, nextIndex);
			list.appendChild(wrap.firstElementChild);
			nextIndex++;
			reindex();
		});

		list.addEventListener('click', function(e){
			if(e.target.classList.contains('qp-phx-remove')){
				e.preventDefault();
				var row = e.target.closest('.qp-phx-row');
				if(row){ row.remove(); reindex(); }
				if(!list.querySelector('.qp-phx-row')){
					var p = document.createElement('p');
					p.className = 'qp-phx-empty';
					p.setAttribute('data-empty','');
					p.textContent = 'Chưa có điểm nổi bật nào. Bấm “Thêm điểm nổi bật” để bắt đầu.';
					list.appendChild(p);
				}
			}
		});
	})();
	</script>
	<?php
}

/**
 * Persist the highlight rows as indexed meta.
 */
function qp_holdings_save_project_highlights_metabox( int $post_id ): void {
	if ( ! isset( $_POST['qp_project_highlights_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['qp_project_highlights_nonce'] ) ), 'qp_project_highlights_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Remove previous rows (and any ACF reference keys).
	$old = (int) get_post_meta( $post_id, 'hero_highlights', true );
	for ( $i = 0; $i < $old; $i++ ) {
		$prefix = 'hero_highlights_' . $i . '_';
		foreach ( array( 'highlight_title', 'highlight_short_description', 'highlight_body' ) as $sub ) {
			delete_post_meta( $post_id, $prefix . $sub );
			delete_post_meta( $post_id, '_' . $prefix . $sub );
		}
	}
	delete_post_meta( $post_id, '_hero_highlights' );

	$submitted = isset( $_POST['qp_project_highlights'] ) && is_array( $_POST['qp_project_highlights'] )
		? wp_unslash( $_POST['qp_project_highlights'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		: array();

	$saved = 0;
	foreach ( $submitted as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$title = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
		$short = isset( $row['short'] ) ? sanitize_textarea_field( (string) $row['short'] ) : '';
		$body  = isset( $row['body'] ) ? sanitize_textarea_field( (string) $row['body'] ) : '';

		if ( '' === $title && '' === $short && '' === $body ) {
			continue;
		}

		$prefix = 'hero_highlights_' . $saved . '_';
		update_post_meta( $post_id, $prefix . 'highlight_title', $title );
		update_post_meta( $post_id, $prefix . 'highlight_short_description', $short );
		update_post_meta( $post_id, $prefix . 'highlight_body', $body );
		$saved++;
	}

	if ( $saved > 0 ) {
		update_post_meta( $post_id, 'hero_highlights', $saved );
	} else {
		delete_post_meta( $post_id, 'hero_highlights' );
	}
}
add_action( 'save_post_qp_project', 'qp_holdings_save_project_highlights_metabox' );






