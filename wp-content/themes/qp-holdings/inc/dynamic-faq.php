<?php
/**
 * FAQ page content.
 *
 * Questions are managed through the `qp_faq` CPT. The post title is the
 * question, the editor content is the answer, and Page Attributes > Order
 * controls display order.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Default FAQ rows, used both for seeding and as a front-end fallback.
 *
 * @return array<int,array{slug:string,question:string,answer:string}>
 */
function qp_holdings_default_faqs(): array {
	return array(
		array(
			'slug'     => 'qp-holding-hoat-dong-trong-nhung-linh-vuc-nao',
			'question' => 'QP Holding hoạt động trong những lĩnh vực nào?',
			'answer'   => 'QP Holding là tập đoàn hoạt động đa ngành, tập trung vào các lĩnh vực như đầu tư bất động sản, phát triển dự án, tư vấn đầu tư, tài chính và các dịch vụ hỗ trợ doanh nghiệp. Chúng tôi luôn hướng đến việc tạo ra giá trị bền vững cho khách hàng, đối tác và cộng đồng.',
		),
		array(
			'slug'     => 'qp-holding-co-tuyen-dung-thuong-xuyen-khong',
			'question' => 'QP Holding có tuyển dụng thường xuyên không?',
			'answer'   => 'QP Holdings thường xuyên cập nhật các vị trí tuyển dụng phù hợp với nhu cầu phát triển của từng khối chuyên môn. Ứng viên có thể theo dõi trang Tuyển dụng hoặc gửi hồ sơ để được đội ngũ nhân sự liên hệ khi có vị trí phù hợp.',
		),
		array(
			'slug'     => 'lam-the-nao-de-dang-ky-tu-van-voi-qp-holding',
			'question' => 'Làm thế nào để đăng ký tư vấn với QP Holding?',
			'answer'   => 'Quý khách có thể gửi thông tin qua trang Liên hệ hoặc gọi trực tiếp đến hotline của QP Holdings. Đội ngũ phụ trách sẽ tiếp nhận nhu cầu, tư vấn hướng phù hợp và phản hồi trong thời gian sớm nhất.',
		),
		array(
			'slug'     => 'qp-holding-cam-ket-gi-voi-khach-hang',
			'question' => 'QP Holding cam kết gì với khách hàng?',
			'answer'   => 'QP Holdings cam kết cung cấp thông tin minh bạch, giải pháp phù hợp và trải nghiệm đồng hành chuyên nghiệp. Mỗi hoạt động đều hướng đến giá trị dài hạn, sự tin cậy và lợi ích bền vững cho khách hàng.',
		),
		array(
			'slug'     => 'quy-trinh-hop-tac-voi-qp-holding-dien-ra-nhu-the-nao',
			'question' => 'Quy trình hợp tác với QP Holding diễn ra như thế nào?',
			'answer'   => 'Quy trình hợp tác bắt đầu bằng việc tiếp nhận nhu cầu, trao đổi thông tin, đánh giá phương án phù hợp và thống nhất kế hoạch triển khai. Trong suốt quá trình, QP Holdings duy trì trao đổi rõ ràng để đảm bảo tiến độ và chất lượng cam kết.',
		),
		array(
			'slug'     => 'gia-tri-cot-loi-ma-qp-holding-theo-duoi-la-gi',
			'question' => 'Giá trị cốt lõi mà QP Holding theo đuổi là gì?',
			'answer'   => 'QP Holdings theo đuổi các giá trị cốt lõi gồm uy tín, minh bạch, hiệu quả và phát triển bền vững. Đây là nền tảng trong mọi quyết định đầu tư, vận hành và hợp tác của doanh nghiệp.',
		),
	);
}

/**
 * Seed default FAQ posts. Non-destructive unless $force is true.
 */
function qp_holdings_seed_faqs( bool $force = false ): void {
	foreach ( qp_holdings_default_faqs() as $index => $item ) {
		qp_holdings_seed_upsert_post(
			array(
				'post_type'    => 'qp_faq',
				'post_status'  => 'publish',
				'post_title'   => $item['question'],
				'post_name'    => $item['slug'],
				'post_content' => $item['answer'],
				'menu_order'   => $index + 1,
			),
			$force
		);
	}
}

/**
 * Return published FAQ items in display order.
 *
 * @return array<int,array{question:string,answer:string}>
 */
function qp_holdings_get_faq_items(): array {
	$posts = get_posts(
		array(
			'post_type'        => 'qp_faq',
			'post_status'      => 'publish',
			'posts_per_page'   => -1,
			'orderby'          => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'order'            => 'ASC',
			'lang'             => '',
			'suppress_filters' => false,
		)
	);

	if ( ! $posts ) {
		return array_map(
			static function ( array $item ): array {
				return array(
					'question' => $item['question'],
					'answer'   => $item['answer'],
				);
			},
			qp_holdings_default_faqs()
		);
	}

	$items = array();
	foreach ( $posts as $post ) {
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$items[] = array(
			'question' => get_the_title( $post ),
			'answer'   => (string) $post->post_content,
		);
	}

	return $items;
}

/**
 * Render the FAQ accordion list.
 */
function qp_holdings_render_faq_accordion(): void {
	$items = qp_holdings_get_faq_items();
	?>
	<div class="faq-list">
		<?php foreach ( $items as $index => $item ) : ?>
			<details class="faq-item"<?php echo 0 === $index ? ' open' : ''; ?>>
				<summary class="faq-item__summary">
					<span class="faq-item__question"><?php echo esc_html( $item['question'] ); ?></span>
					<span class="faq-item__icon" aria-hidden="true"></span>
				</summary>
				<div class="faq-item__answer">
					<?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?>
				</div>
			</details>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Return the editable FAQ illustration attachment ID.
 */
function qp_holdings_get_faq_image_id(): int {
	return absint( get_option( 'qp_holdings_faq_image_id', 0 ) );
}

/**
 * Render the FAQ page image, falling back to the bundled design asset.
 */
function qp_holdings_render_faq_image(): void {
	$image_id = qp_holdings_get_faq_image_id();
	$alt      = qp_holdings_t( 'faq_illustration_alt' );

	if ( $image_id ) {
		echo wp_get_attachment_image(
			$image_id,
			'full',
			false,
			array(
				'alt'      => $alt,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);
		return;
	}

	printf(
		'<img src="%1$s" alt="%2$s" width="2200" height="2160" loading="lazy" decoding="async">',
		esc_url( get_template_directory_uri() . '/assets/site/faq-illustration-3257-17281-fdc866.png' ),
		esc_attr( $alt )
	);
}

function qp_holdings_faq_admin_columns( array $columns ): array {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['qp_faq_answer'] = 'Câu trả lời';
			$new['qp_faq_order']  = 'Thứ tự';
		}
	}
	return $new;
}
add_filter( 'manage_qp_faq_posts_columns', 'qp_holdings_faq_admin_columns' );

function qp_holdings_faq_admin_column_content( string $column, int $post_id ): void {
	if ( 'qp_faq_answer' === $column ) {
		echo esc_html( wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 22, '...' ) );
	} elseif ( 'qp_faq_order' === $column ) {
		echo '<span style="color:#8c8f94;">' . (int) get_post_field( 'menu_order', $post_id ) . '</span>';
	}
}
add_action( 'manage_qp_faq_posts_custom_column', 'qp_holdings_faq_admin_column_content', 10, 2 );

function qp_holdings_faq_admin_default_order( $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'qp_faq' !== $query->get( 'post_type' ) ) {
		return;
	}
	if ( ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', 'menu_order' );
		$query->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'qp_holdings_faq_admin_default_order' );

function qp_holdings_faq_is_admin_list_screen(): bool {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	return $screen && 'edit-qp_faq' === $screen->id;
}

function qp_holdings_faq_admin_enqueue_media( string $hook_suffix ): void {
	if ( 'edit.php' !== $hook_suffix || ! qp_holdings_faq_is_admin_list_screen() ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'qp_holdings_faq_admin_enqueue_media' );

function qp_holdings_faq_admin_image_settings(): void {
	if ( ! qp_holdings_faq_is_admin_list_screen() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$image_id  = qp_holdings_get_faq_image_id();
	$default_url = get_template_directory_uri() . '/assets/site/faq-illustration-3257-17281-fdc866.png';
	$image_url   = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : $default_url;
	?>
	<?php if ( isset( $_GET['qp_faq_image_updated'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Đã cập nhật ảnh minh họa trang FAQ.</p></div>
	<?php endif; ?>
	<div class="notice notice-info qp-faq-image-settings" style="padding:16px 18px;">
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-default-url="<?php echo esc_url( $default_url ); ?>" style="display:grid;grid-template-columns:160px minmax(260px,1fr);gap:16px;align-items:start;max-width:900px;">
			<input type="hidden" name="action" value="qp_holdings_save_faq_image">
			<?php wp_nonce_field( 'qp_holdings_save_faq_image', 'qp_holdings_faq_image_nonce' ); ?>
			<div style="width:160px;height:120px;background:#f6f7f7;border:1px solid #dcdcde;overflow:hidden;display:flex;align-items:center;justify-content:center;">
				<img class="qp-faq-image-preview" src="<?php echo esc_url( $image_url ); ?>" alt="" style="width:100%;height:100%;object-fit:cover;<?php echo $image_url ? '' : 'display:none;'; ?>">
			</div>
			<div>
				<h2 style="margin:0 0 8px;font-size:16px;">Ảnh minh họa trang FAQ</h2>
				<p style="margin:0 0 12px;color:#646970;">Ảnh này hiển thị bên phải danh sách câu hỏi tại trang Câu hỏi thường gặp.</p>
				<input type="hidden" class="qp-faq-image-id" name="faq_image_id" value="<?php echo esc_attr( (string) $image_id ); ?>">
				<button type="button" class="button qp-faq-image-select">Chọn ảnh</button>
				<button type="button" class="button qp-faq-image-remove"<?php echo $image_id ? '' : ' style="display:none;"'; ?>>Dùng ảnh mặc định</button>
				<?php submit_button( 'Lưu ảnh FAQ', 'primary', 'submit', false, array( 'style' => 'margin-left:8px;' ) ); ?>
			</div>
		</form>
	</div>
	<?php
}
add_action( 'all_admin_notices', 'qp_holdings_faq_admin_image_settings' );

function qp_holdings_faq_admin_image_script(): void {
	if ( ! qp_holdings_faq_is_admin_list_screen() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	?>
	<script>
	(function() {
		const box = document.querySelector('.qp-faq-image-settings');
		if (!box || !window.wp || !wp.media) {
			return;
		}

		const idInput = box.querySelector('.qp-faq-image-id');
		const preview = box.querySelector('.qp-faq-image-preview');
		const removeButton = box.querySelector('.qp-faq-image-remove');
		let frame;

		box.querySelector('.qp-faq-image-select').addEventListener('click', function() {
			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Chọn ảnh minh họa FAQ',
				button: { text: 'Dùng ảnh này' },
				multiple: false,
				library: { type: 'image' }
			});

			frame.on('select', function() {
				const attachment = frame.state().get('selection').first().toJSON();
				idInput.value = attachment.id || '';
				preview.src = (attachment.sizes && attachment.sizes.medium_large ? attachment.sizes.medium_large.url : attachment.url);
				preview.style.display = '';
				removeButton.style.display = '';
			});

			frame.open();
		});

		removeButton.addEventListener('click', function() {
			idInput.value = '0';
			preview.src = box.querySelector('form').dataset.defaultUrl || preview.src;
			removeButton.style.display = 'none';
		});
	})();
	</script>
	<?php
}
add_action( 'admin_footer-edit.php', 'qp_holdings_faq_admin_image_script' );

function qp_holdings_save_faq_image(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền thực hiện thao tác này.', 'qp-holdings' ) );
	}

	$nonce = isset( $_POST['qp_holdings_faq_image_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['qp_holdings_faq_image_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'qp_holdings_save_faq_image' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'qp-holdings' ) );
	}

	$image_id = isset( $_POST['faq_image_id'] ) ? absint( $_POST['faq_image_id'] ) : 0;
	if ( $image_id ) {
		update_option( 'qp_holdings_faq_image_id', $image_id, false );
	} else {
		delete_option( 'qp_holdings_faq_image_id' );
	}

	wp_safe_redirect( add_query_arg( 'qp_faq_image_updated', '1', admin_url( 'edit.php?post_type=qp_faq' ) ) );
	exit;
}
add_action( 'admin_post_qp_holdings_save_faq_image', 'qp_holdings_save_faq_image' );
