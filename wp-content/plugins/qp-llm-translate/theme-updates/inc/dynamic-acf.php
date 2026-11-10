<?php
/**
 * ACF field group definitions for the 4 dynamic CPTs.
 * No-op when ACF is not active (post meta still works through
 * qp_holdings_acf_or_meta in dynamic-helpers.php).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Fixed shareholder-composition fields for the stock-information page.
 *
 * ACF free does not provide repeater fields, so this uses three explicit rows
 * matching the current chart design: shareholder 1, shareholder 2, and other.
 *
 * @param string $lang   Language suffix: vi, en, zh.
 * @param array  $labels Localized labels/defaults.
 * @return array<int,array<string,mixed>>
 */
function qp_holdings_stock_shareholder_acf_fields( string $lang, array $labels ): array {
	$fields = array(
		array(
			'key'     => 'field_qp_stock_shareholders_heading_' . $lang,
			'label'   => $labels['section'],
			'type'    => 'message',
			'message' => $labels['instructions'],
		),
	);

	foreach ( $labels['rows'] as $index => $row ) {
		$number = $index + 1;
		$prefix = 'shareholder_' . $number . '_';
		$fields[] = array(
			'key'           => 'field_qp_stock_' . $prefix . 'name_' . $lang,
			'label'         => $row['label'] . ' - ' . $labels['name'],
			'name'          => $prefix . 'name_' . $lang,
			'type'          => 'text',
			'default_value' => $row['name'],
			'wrapper'       => array( 'width' => '40' ),
		);
		$fields[] = array(
			'key'           => 'field_qp_stock_' . $prefix . 'ratio_' . $lang,
			'label'         => $row['label'] . ' - ' . $labels['ratio'],
			'name'          => $prefix . 'ratio_' . $lang,
			'type'          => 'number',
			'default_value' => $row['ratio'],
			'append'        => '%',
			'min'           => 0,
			'max'           => 100,
			'step'          => 0.01,
			'wrapper'       => array( 'width' => '30' ),
		);
		$fields[] = array(
			'key'           => 'field_qp_stock_' . $prefix . 'shares_' . $lang,
			'label'         => $row['label'] . ' - ' . $labels['shares'],
			'name'          => $prefix . 'shares_' . $lang,
			'type'          => 'text',
			'default_value' => $row['shares'],
			'wrapper'       => array( 'width' => '30' ),
		);
	}

	return $fields;
}

function qp_holdings_register_acf_groups(): void {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$phone_country_choices = array(
		'VN' => 'Việt Nam (+84)',
		'US' => 'Mỹ (+1)',
		'CN' => 'Trung Quốc (+86)',
	);

	/* ---------------- qp_news ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_news',
			'title'    => 'Thông tin tin tức',
			'fields'   => array(
				array(
					'key'           => 'field_qp_news_card_image',
					'label'         => 'Ảnh đại diện (card)',
					'name'          => 'card_image',
					'type'          => 'image',
					'return_format' => 'url',
					'instructions'  => 'Ảnh hiển thị ở card và phần hero của bài viết.',
				),
				array(
					'key'           => 'field_qp_news_category_info',
					'label'         => 'Nhãn danh mục (hiển thị)',
					'name'          => 'category_info',
					'type'          => 'text',
					'instructions'  => 'Văn bản hiển thị làm badge — ví dụ: Truyền thông.',
				),
				array(
					'key'           => 'field_qp_news_external_url',
					'label'         => 'External URL (tuỳ chọn)',
					'name'          => 'external_url',
					'type'          => 'url',
					'instructions'  => 'Nếu điền, card sẽ trỏ tới URL này thay vì single post.',
				),
				array(
					'key'           => 'field_qp_news_source',
					'label'         => 'Nguồn số liệu tham khảo',
					'name'          => 'source',
					'type'          => 'text',
					'instructions'  => 'Nguồn số liệu hoặc tài liệu tham khảo hiển thị trong khung thông tin ở cuối bài viết (ví dụ: VnExpress / Reuters / ITU; Báo Đầu tư; Tổng hợp...). Nếu để trống, mục này sẽ tự động ẩn ở giao diện bài viết.',
					'placeholder'   => 'Ví dụ: VnExpress / Reuters / ITU',
				),
				array(
					'key'           => 'field_qp_news_reading_time',
					'label'         => 'Thời gian đọc (phút)',
					'name'          => 'reading_time',
					'type'          => 'number',
					'min'           => 1,
					'max'           => 60,
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_news' ),
				),
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ),
				),
			),
		)
	);

	/* ---------------- qp_project ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_project',
			'title'    => 'Thông tin dự án',
			'fields'   => array(
				array(
					'key'           => 'field_qp_project_card_image',
					'label'         => 'Ảnh đại diện',
					'name'          => 'card_image',
					'type'          => 'image',
					'return_format' => 'url',
				),
				array(
					'key'           => 'field_qp_project_banner_image',
					'label'         => 'Ảnh banner (trang chi tiết)',
					'name'          => 'banner_image',
					'type'          => 'image',
					'return_format' => 'url',
					'instructions'  => 'Ảnh nền lớn ở đầu trang chi tiết dự án (phía sau tiêu đề). Để trống sẽ dùng ảnh banner mặc định.',
				),
				array(
					'key'           => 'field_qp_project_subtitle',
					'label'         => 'Tagline / Phụ đề',
					'name'          => 'subtitle',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_project_display_title',
					'label'         => 'Tiêu đề hiển thị (trang chi tiết)',
					'name'          => 'display_title',
					'type'          => 'textarea',
					'rows'          => 3,
					'new_lines'     => '',
					'instructions'  => 'Hiển thị làm tiêu đề lớn (gradient navy) trong trang chi tiết dự án. Mỗi dòng = 1 dòng hiển thị. Để trống sẽ tự ghép từ Tên dự án + Phụ đề (viết hoa).',
				),
				array(
					'key'           => 'field_qp_project_address',
					'label'         => 'Địa chỉ',
					'name'          => 'address',
					'type'          => 'text',
					'instructions'  => 'Hiển thị ở ô "Địa chỉ" trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_scale',
					'label'         => 'Quy mô (mô tả dài, dùng cho card)',
					'name'          => 'scale',
					'type'          => 'text',
					'instructions'  => 'Ví dụ: 45,000 m² sàn — 30 tầng — 280 căn hộ. Dùng cho card hiển thị, không hiển thị trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_area',
					'label'         => 'Quy mô (diện tích)',
					'name'          => 'area_total',
					'type'          => 'text',
					'instructions'  => 'Ví dụ: 1,94 ha. Hiển thị ở ô "Quy mô" trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_investor',
					'label'         => 'Chủ đầu tư',
					'name'          => 'investor_name',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_project_developer',
					'label'         => 'Đơn vị phát triển',
					'name'          => 'developer',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_project_year',
					'label'         => 'Năm hoàn thành',
					'name'          => 'completion_year',
					'type'          => 'number',
					'min'           => 1990,
					'max'           => 2050,
				),
				array(
					'key'           => 'field_qp_project_handover',
					'label'         => 'Tiến độ / Bàn giao dự kiến',
					'name'          => 'handover',
					'type'          => 'text',
					'instructions'  => 'Hiển thị ở ô "Tiến độ" trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_investment_total',
					'label'         => 'Tổng vốn đầu tư dự kiến',
					'name'          => 'investment_total',
					'type'          => 'text',
					'instructions'  => 'Ví dụ: 1.800 tỷ đồng. Hiển thị ở ô "Tổng vốn đầu tư dự kiến" trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_highlights',
					'label'         => 'Điểm nổi bật (cũ, dạng danh sách)',
					'name'          => 'highlights',
					'type'          => 'textarea',
					'instructions'  => 'Mỗi dòng = 1 điểm. Chỉ dùng làm dự phòng khi chưa nhập "Điểm nổi bật (hero)" bên dưới.',
				),
				array(
					'key'           => 'field_qp_project_hero_heading',
					'label'         => 'Tiêu đề khối điểm nổi bật (hero)',
					'name'          => 'hero_heading',
					'type'          => 'textarea',
					'rows'          => 2,
					'new_lines'     => '',
					'instructions'  => 'Tiêu đề lớn của khối "Tâm điểm thịnh vượng…". Để trống sẽ dùng Tiêu đề hiển thị / Phụ đề.',
				),
				array(
					'key'           => 'field_qp_project_hero_image',
					'label'         => 'Ảnh khối điểm nổi bật (hero)',
					'name'          => 'hero_image',
					'type'          => 'image',
					'return_format' => 'url',
					'instructions'  => 'Ảnh lớn hiển thị bên cạnh các điểm nổi bật. Để trống sẽ dùng Ảnh đại diện / ảnh tiêu biểu.',
				),
				array(
					'key'           => 'field_qp_project_hero_cta_label',
					'label'         => 'Nhãn nút CTA (hero)',
					'name'          => 'hero_cta_label',
					'type'          => 'text',
					'instructions'  => 'Ví dụ: Xem chi tiết dự án. Để trống sẽ không hiển thị nút (trừ khi có URL).',
				),
				array(
					'key'           => 'field_qp_project_hero_cta_url',
					'label'         => 'Liên kết nút CTA (hero)',
					'name'          => 'hero_cta_url',
					'type'          => 'url',
				),
				array(
					'key'           => 'field_qp_project_external_url',
					'label'         => 'External URL (tuỳ chọn)',
					'name'          => 'external_url',
					'type'          => 'url',
				),
				array(
					'key'           => 'field_qp_project_category_info',
					'label'         => 'Loại hình (nhãn category)',
					'name'          => 'category_info',
					'type'          => 'text',
					'instructions'  => 'Ví dụ: Khu phức hợp cao tầng. Hiển thị làm badge ở card và ô "Loại hình" trong khối thông tin dự án.',
				),
				array(
					'key'           => 'field_qp_project_archive_section',
					'label'         => 'Nhóm hiển thị trang Dự án',
					'name'          => 'archive_section',
					'type'          => 'select',
					'instructions'  => 'Chọn section hiển thị trên trang Dự án theo thiết kế Figma.',
					'choices'       => array(
						'in_progress'            => 'Dự án đang triển khai',
						'investment_development' => 'Dự án hợp tác đầu tư & phát triển',
					),
					'default_value' => 'investment_development',
					'return_format' => 'value',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_project' ),
				),
			),
		)
	);

	/* ---------------- qp_job ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_job',
			'title'    => 'Thông tin vị trí tuyển dụng',
			'fields'   => array(
				array( 'key' => 'field_qp_job_display_title', 'label' => 'Tiêu đề hiển thị', 'name' => 'job_display_title', 'type' => 'text', 'instructions' => 'Dùng cho hero/trang chi tiết nếu cần khác tiêu đề quản trị.' ),
				array( 'key' => 'field_qp_job_summary', 'label' => 'Mô tả ngắn', 'name' => 'job_summary', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_hero_image', 'label' => 'Ảnh hero', 'name' => 'job_hero_image', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'medium' ),
				array( 'key' => 'field_qp_job_quantity', 'label' => 'Số lượng', 'name' => 'quantity', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_department', 'label' => 'Phòng ban', 'name' => 'department', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_location', 'label' => 'Địa điểm', 'name' => 'location', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_workplace', 'label' => 'Địa điểm làm việc chi tiết', 'name' => 'workplace', 'type' => 'textarea', 'rows' => 2, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_level', 'label' => 'Cấp bậc', 'name' => 'level', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_reports_to', 'label' => 'Quản lý trực tiếp', 'name' => 'reports_to', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_employment_type', 'label' => 'Loại hợp đồng', 'name' => 'employment_type', 'type' => 'select', 'choices' => array( 'full-time' => 'Toàn thời gian', 'part-time' => 'Bán thời gian', 'internship' => 'Thực tập', 'contract' => 'Hợp đồng' ), 'default_value' => 'full-time' ),
				array( 'key' => 'field_qp_job_deadline', 'label' => 'Hạn nộp hồ sơ', 'name' => 'deadline', 'type' => 'date_picker', 'return_format' => 'Y-m-d', 'display_format' => 'd/m/Y' ),
				array( 'key' => 'field_qp_job_salary', 'label' => 'Mức lương', 'name' => 'salary', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_experience', 'label' => 'Kinh nghiệm yêu cầu', 'name' => 'experience', 'type' => 'text' ),
				array( 'key' => 'field_qp_job_general_info', 'label' => '1. Thông tin chung', 'name' => 'job_general_info', 'type' => 'textarea', 'rows' => 5, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_goal', 'label' => '2. Mục tiêu công việc', 'name' => 'job_goal', 'type' => 'textarea', 'rows' => 4, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_main_responsibilities', 'label' => '3. Nhiệm vụ và Trách nhiệm chính', 'name' => 'job_main_responsibilities', 'type' => 'textarea', 'rows' => 12, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_requirements_detail', 'label' => '4. Yêu cầu công việc', 'name' => 'job_requirements_detail', 'type' => 'textarea', 'rows' => 8, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_benefits_detail', 'label' => '5. Quyền lợi được hưởng', 'name' => 'job_benefits_detail', 'type' => 'textarea', 'rows' => 6, 'new_lines' => '' ),
				array( 'key' => 'field_qp_job_responsibilities', 'label' => 'Trách nhiệm chính', 'name' => 'responsibilities', 'type' => 'textarea', 'instructions' => 'Mỗi dòng = 1 trách nhiệm.' ),
				array( 'key' => 'field_qp_job_requirements', 'label' => 'Yêu cầu', 'name' => 'requirements', 'type' => 'textarea', 'instructions' => 'Mỗi dòng = 1 yêu cầu.' ),
				array( 'key' => 'field_qp_job_benefits', 'label' => 'Quyền lợi', 'name' => 'benefits', 'type' => 'textarea', 'instructions' => 'Mỗi dòng = 1 quyền lợi.' ),
				array( 'key' => 'field_qp_job_related_jobs', 'label' => 'Vị trí liên quan', 'name' => 'related_jobs', 'type' => 'relationship', 'post_type' => array( 'qp_job' ), 'filters' => array( 'search' ), 'return_format' => 'object', 'min' => 0, 'max' => 2 ),
				array( 'key' => 'field_qp_job_apply_url', 'label' => 'URL ứng tuyển', 'name' => 'apply_url', 'type' => 'url' ),
				array( 'key' => 'field_qp_job_apply_email', 'label' => 'Email ứng tuyển', 'name' => 'apply_email', 'type' => 'email' ),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_job' ),
				),
			),
		)
	);

	/* ---------------- qp_investor_doc ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_investor_doc',
			'title'    => 'Thông tin tài liệu nhà đầu tư',
			'fields'   => array(
				array( 'key' => 'field_qp_doc_date', 'label' => 'Ngày phát hành', 'name' => 'doc_date', 'type' => 'date_picker', 'return_format' => 'Y-m-d', 'display_format' => 'd/m/Y', 'instructions' => 'Ngày công bố tài liệu — hiển thị trên danh sách và trang chi tiết.' ),
				array( 'key' => 'field_qp_doc_year', 'label' => 'Năm tài chính', 'name' => 'doc_year', 'type' => 'number', 'min' => 1990, 'max' => 2099, 'instructions' => 'Năm dùng để lọc theo "Năm tài liệu". Để trống sẽ lấy theo Ngày phát hành.' ),
				array( 'key' => 'field_qp_doc_language', 'label' => 'Ngôn ngữ', 'name' => 'doc_language', 'type' => 'select', 'choices' => array( 'vi' => 'Tiếng Việt', 'en' => 'Tiếng Anh', 'bilingual' => 'Song ngữ' ), 'default_value' => 'vi', 'instructions' => 'Nhãn ngôn ngữ hiển thị trên thẻ tài liệu ở danh sách.' ),
				array( 'key' => 'field_qp_doc_files_hint', 'label' => '', 'name' => '', 'type' => 'message', 'message' => 'Tải lên các tệp PDF (tiếng Việt, tiếng Anh, phụ lục…) ở hộp <strong>“Tệp tin đính kèm”</strong> bên dưới. Mỗi dòng là một tệp và sẽ hiển thị trên trang chi tiết tài liệu.', 'esc_html' => 0 ),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_investor_doc' ),
				),
			),
		)
	);

	/* ---------------- qp_contact_msg ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_contact_msg',
			'title'    => 'Chi tiết liên hệ',
			'fields'   => array(
				array(
					'key'   => 'field_qp_contact_name',
					'label' => 'Họ tên',
					'name'  => '_qp_contact_name',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_contact_email',
					'label' => 'Email',
					'name'  => '_qp_contact_email',
					'type'  => 'email',
				),
				array(
					'key'   => 'field_qp_contact_phone_country',
					'label' => 'Mã quốc gia',
					'name'  => '_qp_contact_phone_country',
					'type'  => 'select',
					'choices' => $phone_country_choices,
					'default_value' => 'VN',
				),
				array(
					'key'   => 'field_qp_contact_phone',
					'label' => 'Số điện thoại',
					'name'  => '_qp_contact_phone',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_contact_question_label',
					'label' => 'Câu hỏi',
					'name'  => '_qp_contact_label',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_contact_message',
					'label' => 'Nội dung',
					'name'  => '_qp_contact_message',
					'type'  => 'textarea',
				),
				array(
					'key'   => 'field_qp_contact_email_sent',
					'label' => 'Email đã gửi',
					'name'  => '_qp_email_sent',
					'type'  => 'true_false',
				),
				array(
					'key'   => 'field_qp_contact_submitted_at',
					'label' => 'Thời gian gửi',
					'name'  => '_qp_submitted_at',
					'type'  => 'text',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_contact_msg' ),
				),
			),
		)
	);

	/* ---------------- qp_job_app ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_job_app',
			'title'    => 'Chi tiết hồ sơ ứng tuyển',
			'fields'   => array(
				array(
					'key'   => 'field_qp_applicant_name',
					'label' => 'Họ tên',
					'name'  => '_qp_applicant_name',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_applicant_email',
					'label' => 'Email',
					'name'  => '_qp_applicant_email',
					'type'  => 'email',
				),
				array(
					'key'   => 'field_qp_applicant_phone_country',
					'label' => 'Mã quốc gia',
					'name'  => '_qp_applicant_phone_country',
					'type'  => 'select',
					'choices' => $phone_country_choices,
					'default_value' => 'VN',
				),
				array(
					'key'   => 'field_qp_applicant_phone',
					'label' => 'Số điện thoại',
					'name'  => '_qp_applicant_phone',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_applicant_message',
					'label' => 'Mô tả bản thân',
					'name'  => '_qp_applicant_message',
					'type'  => 'textarea',
				),
				array(
					'key'           => 'field_qp_applicant_attachment_ids',
					'label'         => 'CV / tài liệu upload',
					'name'          => '_qp_attachment_ids',
					'type'          => 'relationship',
					'post_type'     => array( 'attachment' ),
					'filters'       => array( 'search' ),
					'return_format' => 'id',
				),
				array(
					'key'   => 'field_qp_applicant_job_title',
					'label' => 'Vị trí',
					'name'  => '_qp_job_title',
					'type'  => 'text',
				),
				array(
					'key'   => 'field_qp_applicant_email_sent',
					'label' => 'Email đã gửi',
					'name'  => '_qp_email_sent',
					'type'  => 'true_false',
				),
				array(
					'key'   => 'field_qp_applicant_submitted_at',
					'label' => 'Thời gian gửi',
					'name'  => '_qp_submitted_at',
					'type'  => 'text',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_job_app' ),
				),
			),
		)
	);

	/* ---------------- qp_consult_msg ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_consult_msg',
			'title'    => 'Chi tiết đăng ký tư vấn',
			'fields'   => array(
				array(
					'key'           => 'field_qp_consult_name',
					'label'         => 'Họ tên',
					'name'          => '_qp_consult_name',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_consult_phone_country',
					'label'         => 'Mã quốc gia',
					'name'          => '_qp_consult_phone_country',
					'type'          => 'select',
					'choices'       => $phone_country_choices,
					'default_value' => 'VN',
				),
				array(
					'key'           => 'field_qp_consult_phone',
					'label'         => 'Số điện thoại',
					'name'          => '_qp_consult_phone',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_consult_project_interest',
					'label'         => 'Dự án quan tâm',
					'name'          => '_qp_consult_project_interest',
					'type'          => 'text',
				),
				array(
					'key'           => 'field_qp_consult_project_id',
					'label'         => 'ID dự án',
					'name'          => '_qp_consult_project_id',
					'type'          => 'number',
				),
				array(
					'key'           => 'field_qp_consult_email_sent',
					'label'         => 'Email đã gửi',
					'name'          => '_qp_email_sent',
					'type'          => 'true_false',
				),
				array(
					'key'           => 'field_qp_consult_submitted_at',
					'label'         => 'Thời gian gửi',
					'name'          => '_qp_submitted_at',
					'type'          => 'text',
				),
			),
			'location' => array(
				array(
					array( 'param' => 'post_type', 'operator' => '==', 'value' => 'qp_consult_msg' ),
				),
			),
		)
	);

	/* ---------------- qp_investor_stock ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_investor_stock',
			'title'    => 'Số liệu cổ phiếu (Stock Metrics)',
			'fields'   => array(
				// TAB VIETNAMESE
				array(
					'key'   => 'field_qp_stock_tab_vi',
					'label' => 'Tiếng Việt',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_qp_stock_par_value_vi',
					'label'         => 'Mệnh giá cổ phiếu',
					'name'          => 'par_value_vi',
					'type'          => 'text',
					'default_value' => '10.000 đồng/cổ phiếu',
				),
				array(
					'key'           => 'field_qp_stock_outstanding_vi',
					'label'         => 'Số lượng cổ phiếu đang lưu hành',
					'name'          => 'outstanding_shares_vi',
					'type'          => 'text',
					'default_value' => '33.335.029',
				),
				array(
					'key'           => 'field_qp_stock_charter_capital_vi',
					'label'         => 'Vốn điều lệ',
					'name'          => 'charter_capital_vi',
					'type'          => 'text',
					'default_value' => '333.350.290.000 đồng',
				),
				array(
					'key'           => 'field_qp_stock_treasury_vi',
					'label'         => 'Số lượng cổ phiếu quỹ',
					'name'          => 'treasury_shares_vi',
					'type'          => 'text',
					'default_value' => '0 cổ phiếu',
				),
				array(
					'key'           => 'field_qp_stock_foreign_limit_vi',
					'label'         => 'Giới hạn tỷ lệ sở hữu nước ngoài',
					'name'          => 'foreign_limit_vi',
					'type'          => 'text',
					'default_value' => '0%',
				),
				array(
					'key'           => 'field_qp_stock_foreign_investor_ratio_vi',
					'label'         => 'Tỷ lệ vốn nhà đầu tư nước ngoài',
					'name'          => 'foreign_investor_ratio_vi',
					'type'          => 'number',
					'instructions'  => 'Dùng cho biểu đồ tỷ lệ nhà đầu tư trong nước / nước ngoài. Nhập số, không cần ký hiệu %.',
					'default_value' => 0,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				array(
					'key'           => 'field_qp_stock_domestic_investor_ratio_vi',
					'label'         => 'Tỷ lệ vốn nhà đầu tư trong nước',
					'name'          => 'domestic_investor_ratio_vi',
					'type'          => 'number',
					'instructions'  => 'Dùng cho biểu đồ tỷ lệ nhà đầu tư trong nước / nước ngoài. Nhập số, không cần ký hiệu %.',
					'default_value' => 100,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				... qp_holdings_stock_shareholder_acf_fields(
					'vi',
					array(
						'section'      => 'Cơ cấu cổ đông lớn',
						'instructions' => 'Dùng cho biểu đồ donut "Danh sách các Cổ đông của chúng tôi". Màu biểu đồ được giữ cố định theo thiết kế. Để trống Tên của một cổ đông nếu không muốn hiển thị cổ đông đó.',
						'name'         => 'Tên',
						'ratio'        => 'Tỷ lệ sở hữu',
						'shares'       => 'Số cổ phiếu',
						'rows'         => array(
							array( 'label' => 'Cổ đông 1', 'name' => 'Vi Thị Dung', 'ratio' => 60.30, 'shares' => '20,100,000' ),
							array( 'label' => 'Cổ đông 2', 'name' => 'Dương Thị Chín', 'ratio' => 21.00, 'shares' => '7,000,000' ),
							array( 'label' => 'Cổ đông 3', 'name' => 'Khác', 'ratio' => 18.70, 'shares' => '6,233,630' ),
							array( 'label' => 'Cổ đông 4', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Cổ đông 5', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Cổ đông 6', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Cổ đông 7', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Cổ đông 8', 'name' => '', 'ratio' => 0, 'shares' => '' ),
						),
					)
				),

				// TAB ENGLISH
				array(
					'key'   => 'field_qp_stock_tab_en',
					'label' => 'English',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_qp_stock_par_value_en',
					'label'         => 'Par value',
					'name'          => 'par_value_en',
					'type'          => 'text',
					'default_value' => 'VND 10,000/share',
				),
				array(
					'key'           => 'field_qp_stock_outstanding_en',
					'label'         => 'Outstanding shares',
					'name'          => 'outstanding_shares_en',
					'type'          => 'text',
					'default_value' => '33,335,029',
				),
				array(
					'key'           => 'field_qp_stock_charter_capital_en',
					'label'         => 'Charter capital',
					'name'          => 'charter_capital_en',
					'type'          => 'text',
					'default_value' => 'VND 333,350,290,000',
				),
				array(
					'key'           => 'field_qp_stock_treasury_en',
					'label'         => 'Treasury shares',
					'name'          => 'treasury_shares_en',
					'type'          => 'text',
					'default_value' => '0 shares',
				),
				array(
					'key'           => 'field_qp_stock_foreign_limit_en',
					'label'         => 'Foreign ownership limit',
					'name'          => 'foreign_limit_en',
					'type'          => 'text',
					'default_value' => '0%',
				),
				array(
					'key'           => 'field_qp_stock_foreign_investor_ratio_en',
					'label'         => 'Foreign investor capital ratio',
					'name'          => 'foreign_investor_ratio_en',
					'type'          => 'number',
					'instructions'  => 'Used for the domestic / foreign investor ratio chart. Enter a number without the % symbol.',
					'default_value' => 0,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				array(
					'key'           => 'field_qp_stock_domestic_investor_ratio_en',
					'label'         => 'Domestic investor capital ratio',
					'name'          => 'domestic_investor_ratio_en',
					'type'          => 'number',
					'instructions'  => 'Used for the domestic / foreign investor ratio chart. Enter a number without the % symbol.',
					'default_value' => 100,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				... qp_holdings_stock_shareholder_acf_fields(
					'en',
					array(
						'section'      => 'Major shareholder structure',
						'instructions' => 'Used for the "Our Shareholders" donut chart. Chart colours are fixed by the design. Leave a shareholder Name empty to hide that shareholder.',
						'name'         => 'Name',
						'ratio'        => 'Ownership ratio',
						'shares'       => 'Number of shares',
						'rows'         => array(
							array( 'label' => 'Shareholder 1', 'name' => 'Vi Thị Dung', 'ratio' => 60.30, 'shares' => '20,100,000' ),
							array( 'label' => 'Shareholder 2', 'name' => 'Dương Thị Chín', 'ratio' => 21.00, 'shares' => '7,000,000' ),
							array( 'label' => 'Shareholder 3', 'name' => 'Other', 'ratio' => 18.70, 'shares' => '6,233,630' ),
							array( 'label' => 'Shareholder 4', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Shareholder 5', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Shareholder 6', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Shareholder 7', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => 'Shareholder 8', 'name' => '', 'ratio' => 0, 'shares' => '' ),
						),
					)
				),

				// TAB CHINESE
				array(
					'key'   => 'field_qp_stock_tab_zh',
					'label' => '中文',
					'type'  => 'tab',
				),
				array(
					'key'           => 'field_qp_stock_par_value_zh',
					'label'         => '股票面值',
					'name'          => 'par_value_zh',
					'type'          => 'text',
					'default_value' => '10,000 越盾/股',
				),
				array(
					'key'           => 'field_qp_stock_outstanding_zh',
					'label'         => '流通股数量',
					'name'          => 'outstanding_shares_zh',
					'type'          => 'text',
					'default_value' => '33,335,029',
				),
				array(
					'key'           => 'field_qp_stock_charter_capital_zh',
					'label'         => '注册资本',
					'name'          => 'charter_capital_zh',
					'type'          => 'text',
					'default_value' => '333,350,290,000 越盾',
				),
				array(
					'key'           => 'field_qp_stock_treasury_zh',
					'label'         => '库存股数量',
					'name'          => 'treasury_shares_zh',
					'type'          => 'text',
					'default_value' => '0 股',
				),
				array(
					'key'           => 'field_qp_stock_foreign_limit_zh',
					'label'         => '外资持股比例上限',
					'name'          => 'foreign_limit_zh',
					'type'          => 'text',
					'default_value' => '0%',
				),
				array(
					'key'           => 'field_qp_stock_foreign_investor_ratio_zh',
					'label'         => '外国投资者持资本比例',
					'name'          => 'foreign_investor_ratio_zh',
					'type'          => 'number',
					'instructions'  => '用于国内/外国投资者比例图表。请输入数字，不需要 % 符号。',
					'default_value' => 0,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				array(
					'key'           => 'field_qp_stock_domestic_investor_ratio_zh',
					'label'         => '国内投资者持资本比例',
					'name'          => 'domestic_investor_ratio_zh',
					'type'          => 'number',
					'instructions'  => '用于国内/外国投资者比例图表。请输入数字，不需要 % 符号。',
					'default_value' => 100,
					'append'        => '%',
					'min'           => 0,
					'max'           => 100,
					'step'          => 0.01,
				),
				... qp_holdings_stock_shareholder_acf_fields(
					'zh',
					array(
						'section'      => '主要股东结构',
						'instructions' => '用于“我们的股东名单”环形图。图表颜色按设计固定。 如不想显示某位股东，请将其姓名留空。',
						'name'         => '名称',
						'ratio'        => '持股比例',
						'shares'       => '持股数量',
						'rows'         => array(
							array( 'label' => '股东 1', 'name' => 'Vi Thị Dung', 'ratio' => 60.30, 'shares' => '20,100,000' ),
							array( 'label' => '股东 2', 'name' => 'Dương Thị Chín', 'ratio' => 21.00, 'shares' => '7,000,000' ),
							array( 'label' => '股东 3', 'name' => '其他', 'ratio' => 18.70, 'shares' => '6,233,630' ),
							array( 'label' => '股东 4', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => '股东 5', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => '股东 6', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => '股东 7', 'name' => '', 'ratio' => 0, 'shares' => '' ),
							array( 'label' => '股东 8', 'name' => '', 'ratio' => 0, 'shares' => '' ),
						),
					)
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'page_template',
						'operator' => '==',
						'value'    => 'page-thong-tin-co-phieu.php',
					),
				),
			),
		)
	);

	/* ---------------- Hồ sơ năng lực (trang Giới thiệu) ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_about_profile',
			'title'    => 'Hồ sơ năng lực',
			'fields'   => array(
				array(
					'key'           => 'field_qp_about_profile_file',
					'label'         => 'Tệp hồ sơ năng lực (PDF)',
					'name'          => 'capability_profile_file',
					'type'          => 'file',
					'return_format' => 'url',
					'mime_types'    => 'pdf',
					'instructions'  => 'Tải lên tệp PDF hồ sơ năng lực. Nút "Xem hồ sơ năng lực" trên trang Giới thiệu sẽ mở tệp này. Nếu để trống, có thể dùng "Link hồ sơ năng lực" bên dưới.',
				),
				array(
					'key'           => 'field_qp_about_profile_url',
					'label'         => 'Link hồ sơ năng lực (tuỳ chọn)',
					'name'          => 'capability_profile_url',
					'type'          => 'url',
					'instructions'  => 'Dùng khi muốn trỏ tới một liên kết PDF có sẵn thay vì tải tệp lên. Bị bỏ qua nếu đã chọn tệp PDF ở trên.',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'page_template',
						'operator' => '==',
						'value'    => 'page-gioi-thieu.php',
					),
				),
			),
		)
	);

	/* ---------------- Slot menu Header động (mọi page) ---------------- */
	acf_add_local_field_group(
		array(
			'key'      => 'group_qp_header_menu_slot',
			'title'    => 'Menu Header (slot động)',
			'fields'   => array(
				array(
					'key'          => 'field_qp_header_menu_slot',
					'label'        => 'Hiển thị trên menu Header',
					'name'         => 'header_menu_slot',
					'type'         => 'true_false',
					'ui'           => 1,
					'instructions' => 'Bật để trang này xuất hiện ở mục CUỐI cùng của menu Header (sau "Liên hệ"). Hệ thống sẽ tự động dùng tiêu đề và đường dẫn của trang.',
				),
				array(
					'key'           => 'field_qp_header_menu_target_en',
					'label'         => 'Trang Tiếng Anh (English) tương ứng',
					'name'          => 'header_menu_target_en',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'multiple'      => 0,
					'ui'            => 1,
					'instructions'  => 'Chọn trang bài viết tiếng Anh tương ứng (như /en/about/ đối với /gioi-thieu/). Menu tiếng Anh và nút chuyển cờ EN sẽ trỏ đến trang này. Để trống = dùng trang gốc.',
				),
				array(
					'key'           => 'field_qp_header_menu_target_zh',
					'label'         => 'Trang Tiếng Trung (中文) tương ứng',
					'name'          => 'header_menu_target_zh',
					'type'          => 'post_object',
					'post_type'     => array( 'page' ),
					'return_format' => 'id',
					'allow_null'    => 1,
					'multiple'      => 0,
					'ui'            => 1,
					'instructions'  => 'Chọn trang bài viết tiếng Trung tương ứng (như /zh/about/ đối với /gioi-thieu/). Menu tiếng Trung và nút chuyển cờ ZH sẽ trỏ đến trang này. Để trống = dùng trang gốc.',
				),
				array(
					'key'          => 'field_qp_header_menu_label',
					'label'        => 'Nhãn menu hiển thị (tuỳ chọn)',
					'name'         => 'header_menu_label',
					'type'         => 'text',
					'instructions' => 'Để trống = tự động dùng tiêu đề của chính trang này (hoặc tiêu đề trang tương ứng khi xem ở bản EN/ZH). Chỉ nhập khi cần rút gọn nhãn trên menu.',
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'qp_holdings_register_acf_groups' );
