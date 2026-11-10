<?php
/**
 * QP Xanh / QP Holdings original news seed data.
 */

defined( 'ABSPATH' ) || exit;

function qp_holdings_seed_news_qpxanh_content( array $item ): string {
	$content = '';
	$lead    = (string) ( $item['lead'] ?? '' );

	if ( '' !== $lead ) {
		$content .= '<p>' . esc_html( $lead ) . '</p>';
	}

	if ( ! empty( $item['image'] ) ) {
		$content .= qp_holdings_news_figure_html( (string) $item['image'], (string) ( $item['caption'] ?? $item['title'] ?? '' ) );
	}

	foreach ( $item['blocks'] ?? array() as $block ) {
		if ( is_array( $block ) ) {
			if ( ! empty( $block['heading'] ) ) {
				$content .= '<h2>' . esc_html( (string) $block['heading'] ) . '</h2>';
			}

			foreach ( $block['paragraphs'] ?? array() as $paragraph ) {
				$content .= '<p>' . esc_html( (string) $paragraph ) . '</p>';
			}
			continue;
		}

		$content .= '<p>' . esc_html( (string) $block ) . '</p>';
	}

	$content .= '<p><em>Theo <a href="' . esc_url( (string) $item['url'] ) . '" target="_blank" rel="noopener noreferrer">QP Xanh</a>.</em></p>';

	return $content;
}

function qp_holdings_seed_news_qpxanh_item( array $item ): array {
	return array(
		'slug'     => (string) $item['slug'],
		'title'    => (string) $item['title'],
		'date'     => (string) $item['date'],
		'image'    => (string) $item['image'],
		'category' => (string) $item['category'],
		'tax'      => $item['tax'],
		'excerpt'  => (string) $item['excerpt'],
		'content'  => qp_holdings_seed_news_qpxanh_content( $item ),
	);
}

function qp_holdings_seed_news_qpxanh(): array {
	$items = array(
		array(
			'slug'     => 'dung-bo-trung-vao-mot-gio-bat-mi-cach-phan-bo-danh-muc-dau-tu-chung-khoan-an-toan-va-hieu-qua',
			'title'    => 'Đừng "bỏ trứng vào một giỏ": Bật mí cách phân bổ danh mục đầu tư chứng khoán an toàn và hiệu quả',
			'date'     => '2025-10-17 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/10/avatar1760627505376-1760627506019993684971.png',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'Phân bổ danh mục hợp lý giúp nhà đầu tư mới giảm rủi ro, giữ kỷ luật và tận dụng cơ hội dài hạn trên thị trường chứng khoán.',
			'lead'     => 'Một trong những nguyên tắc quan trọng nhất mà nhà đầu tư mới cần nắm vững là phân bổ danh mục hợp lý. Không phải chỉ chọn cổ phiếu hot hay theo đám đông, mà là biết chia vốn vào các loại tài sản khác nhau để giảm rủi ro và tối ưu hóa lợi nhuận.',
			'caption'  => 'Phân bổ danh mục là nền tảng quản trị rủi ro cho nhà đầu tư mới.',
			'url'      => 'https://qpxanh.com.vn/dung-bo-trung-vao-mot-gio-bat-mi-cach-phan-bo-danh-muc-dau-tu-chung-khoan-an-toan-va-hieu-qua/',
			'blocks'   => array(
				array(
					'heading'    => 'Xác định tỷ trọng phù hợp theo loại tài sản',
					'paragraphs' => array(
						'Để xây dựng danh mục hợp lý, nhà đầu tư mới nên bắt đầu bằng việc xác định tỷ trọng vốn giữa các loại tài sản. Cổ phiếu có thể chiếm tỷ trọng lớn nhưng không nên dồn toàn bộ tiền vào một mã hoặc một nhóm ngành.',
						'Nhà đầu tư có thể phân bổ 60-70% vào cổ phiếu bluechip và midcap, 20-30% giữ tiền mặt hoặc tài sản an toàn, phần còn lại dành cho cổ phiếu tăng trưởng hoặc ngành triển vọng. Tỷ trọng cần phù hợp với khả năng chịu rủi ro của từng người.',
					),
				),
				array(
					'heading'    => 'Đa dạng hóa ngành và chiến lược',
					'paragraphs' => array(
						'Không nên tập trung toàn bộ vốn vào một ngành đang nóng, bởi nếu ngành đó điều chỉnh mạnh, toàn bộ danh mục sẽ chịu thiệt hại lớn. Nhà đầu tư nên chia vốn vào các ngành khác nhau như ngân hàng, bất động sản, chứng khoán, tiêu dùng và công nghệ.',
						'Một phần danh mục nên dành cho dài hạn, chọn doanh nghiệp có nền tảng vững chắc; phần còn lại có thể dùng cho cơ hội ngắn hạn. Việc chia danh mục theo thời gian giúp bảo vệ vốn và hạn chế bị cuốn theo cảm xúc.',
					),
				),
				array(
					'heading'    => 'Giữ tiền mặt và điều chỉnh định kỳ',
					'paragraphs' => array(
						'Khi thị trường tăng mạnh hoặc ở vùng giá cao, giữ một phần tiền mặt giúp nhà đầu tư linh hoạt hơn và giảm áp lực tâm lý. Kết hợp với stop-loss hoặc chốt lời từng phần, danh mục sẽ cân bằng và an toàn hơn.',
						'Danh mục đầu tư không phải mua một lần là xong. Nhà đầu tư nên kiểm tra tỷ trọng cổ phiếu và ngành sau mỗi quý hoặc khi thị trường biến động mạnh để duy trì cấu trúc phù hợp với mục tiêu đầu tư.',
					),
				),
			),
		),
		array(
			'slug'     => 'aseansc-dinh-gia-van-hap-dan-so-voi-cac-chu-ky-bom-tien-trong-qua-khu-vn-index-co-the-len-1-800-diem',
			'title'    => 'ASEANSC: Định giá vẫn hấp dẫn so với các chu kỳ bơm tiền trong quá khứ, VN-Index có thể lên 1.800 điểm',
			'date'     => '2025-10-14 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/10/avatar1760370762237-17603707627961494171168.png',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'ASEANSC dự báo lợi nhuận toàn thị trường quý 3/2025 tăng trên 20%, trong khi định giá VN-Index vẫn hấp dẫn so với các chu kỳ bơm tiền trước đây.',
			'lead'     => 'Trong báo cáo chiến lược tháng 10, Chứng khoán Asean dự báo lợi nhuận sau thuế quý 3/2025 trên toàn thị trường tăng trên 20% so với cùng kỳ, với nhóm chứng khoán, ngân hàng và bất động sản tiếp tục là các động lực quan trọng.',
			'caption'  => 'Định giá thị trường được đánh giá vẫn còn dư địa nếu so với các chu kỳ bơm tiền trước đây.',
			'url'      => 'https://qpxanh.com.vn/aseansc-dinh-gia-van-hap-dan-so-voi-cac-chu-ky-bom-tien-trong-qua-khu-vn-index-co-the-len-1-800-diem/',
			'blocks'   => array(
				array(
					'heading'    => 'Lợi nhuận thị trường có thể tăng trên 20%',
					'paragraphs' => array(
						'Nhóm chứng khoán được dự báo thuộc nhóm tăng trưởng lợi nhuận cao nhất, khoảng 50-60%, nhờ thanh khoản thị trường lập kỷ lục và hoạt động tự doanh hưởng lợi từ xu hướng tăng tại nhiều cổ phiếu vốn hóa lớn.',
						'Ngân hàng được dự báo tăng trưởng lợi nhuận trước thuế 15-20% trong quý 3/2025, nhờ tín dụng duy trì ở mức cao, thu nhập ngoài lãi mở rộng và tỷ lệ chi phí hoạt động có xu hướng thu hẹp nhờ chuyển đổi số.',
						'Bất động sản được kỳ vọng tăng trưởng lợi nhuận sau thuế 25-30%, nhờ hoạt động bàn giao dự án sôi động hơn, tình hình tài chính cải thiện và xu hướng giảm lãi suất cho vay.',
					),
				),
				array(
					'heading'    => 'Định giá và chiến lược tháng 10',
					'paragraphs' => array(
						'VN-Index đang giao dịch quanh forward P/E 2025F từ 12,3-12,8 lần. Mức này cao hơn trung bình 5 năm nhưng vẫn thấp hơn đáng kể nếu so với các chu kỳ bơm tiền mạnh trong quá khứ.',
						'ASEANSC Research đánh giá khả quan về xu hướng trung hạn, nhận định VN-Index có thể vận động trong kênh 1.680 đến 1.800 điểm trong tháng 10. Nhà đầu tư nên ưu tiên các cổ phiếu có câu chuyện hỗ trợ về kết quả kinh doanh, nâng hạng thị trường và ngành kinh tế mới.',
					),
				),
			),
		),
		array(
			'slug'     => 'kich-ban-tich-cuc-vn-index-co-the-len-1-800-diem-cong-ty-chung-khoan-diem-ten-3-nhom-co-phieu-hot-du-bao-huong-loi-trong-nua-cuoi-nam-2025',
			'title'    => 'Kịch bản tích cực VN-Index có thể lên 1.800 điểm, công ty chứng khoán điểm tên 3 nhóm cổ phiếu hot dự báo hưởng lợi trong nửa cuối năm 2025',
			'date'     => '2025-08-14 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/08/avatar1755105119475-17551051203621054068127.jpg',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'Maybank Investment Bank nâng dự báo tăng trưởng lợi nhuận toàn thị trường năm 2025 lên 18,5% và giữ quan điểm tích cực với VN-Index.',
			'lead'     => 'Maybank Investment Bank kỳ vọng tăng trưởng lợi nhuận toàn thị trường chậm lại nhưng chất lượng cải thiện trong nửa cuối năm 2025, đồng thời nâng dự báo tăng trưởng cả năm lên 18,5% so với cùng kỳ.',
			'caption'  => 'Dòng tiền thị trường được hỗ trợ bởi kỳ vọng nâng hạng và thanh khoản kỷ lục.',
			'url'      => 'https://qpxanh.com.vn/kich-ban-tich-cuc-vn-index-co-the-len-1-800-diem-cong-ty-chung-khoan-diem-ten-3-nhom-co-phieu-hot-du-bao-huong-loi-trong-nua-cuoi-nam-2025/',
			'blocks'   => array(
				array(
					'heading'    => 'Lợi nhuận cải thiện nhờ đầu tư và hạ tầng',
					'paragraphs' => array(
						'Lợi nhuận quý 2 toàn thị trường tăng mạnh nhờ nhiều nhóm ngành phục hồi. Các ngành hưởng lợi từ đầu tư hạ tầng như thép và bất động sản được thúc đẩy bởi chính sách tài khóa chủ động của Chính phủ.',
						'Tăng trưởng tín dụng mạnh tiếp tục hỗ trợ ngân hàng, trong khi logistics hàng không hưởng lợi từ du lịch và một số ngành tiêu dùng duy trì kết quả tích cực nhờ quản lý chi phí.',
					),
				),
				array(
					'heading'    => 'Ba nhóm cổ phiếu được ưu tiên',
					'paragraphs' => array(
						'Maybank Investment Bank giữ quan điểm tích cực cho nửa cuối năm, nâng mục tiêu VN-Index cuối năm lên 1.800 điểm dựa trên P/E mục tiêu 14,5 lần và dự báo tăng trưởng EPS năm 2025 đạt 18,5%.',
						'Ba nhóm ngành ưu tiên gồm nhóm hưởng lợi từ đầu tư hạ tầng như thép và bất động sản, nhóm hưởng lợi từ tăng trưởng tín dụng mạnh như ngân hàng, và nhóm có nhu cầu mang tính cấu trúc như công nghệ thông tin, hàng không.',
					),
				),
			),
		),
		array(
			'slug'     => 'chung-khoan-dang-o-vung-gia-cao-nhat-hon-3-nam-qua',
			'title'    => 'Chứng khoán đang ở vùng giá cao nhất hơn 3 năm qua',
			'date'     => '2025-07-25 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/07/screen-shot-2025-07-24-at-162430-1753351019843-17533510203072127053168.png',
			'category' => 'Thông báo',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'VN-Index vượt 1.520 điểm, tiếp tục leo dốc ở vùng giá cao nhất trong hơn 3 năm và tiến sát đỉnh lịch sử.',
			'lead'     => 'VN-Index vượt mốc 1.520 điểm, tiếp tục leo dốc ở vùng giá cao nhất trong hơn 3 năm trở lại đây. Với đà tăng này, chỉ số chính đã tiến sát vùng đỉnh lịch sử 1.530 điểm từng thiết lập trước đó.',
			'caption'  => 'Nhóm VN30 bao phủ trong sắc xanh khi thị trường tiến sát vùng đỉnh lịch sử.',
			'url'      => 'https://qpxanh.com.vn/chung-khoan-dang-o-vung-gia-cao-nhat-hon-3-nam-qua/',
			'blocks'   => array(
				'Thị trường chứng khoán tiếp tục khởi sắc khi loạt cổ phiếu lớn trong rổ VN30 đồng loạt bứt phá. 22 trong 30 mã tăng giá, nhóm ngân hàng nới rộng sức ảnh hưởng và MBB trở thành mã đóng góp tích cực nhất cho VN-Index.',
				'VIC của Vingroup lấy lại đà tăng và lọt nhóm dẫn dắt thị trường. Dầu khí cũng giao dịch tích cực với BSR, PLX, GAS, PVD, PVC, PET, PVS và OIL cùng ngập trong sắc xanh.',
				'Thanh khoản tiếp tục ở mức cao, với giá trị giao dịch HoSE hơn 36.635 tỷ đồng. Bên cạnh nhóm vốn hóa lớn, các mã nhỏ và vừa cũng dậy sóng, nhiều cổ phiếu tăng trần như FIT, TSC và VPG.',
			),
		),
		array(
			'slug'     => 'dinh-gia-chung-khoan-viet-nam-se-bung-no-neu-kinh-te-tang-truong-2-con-so',
			'title'    => 'Định giá chứng khoán Việt Nam sẽ bùng nổ, nếu kinh tế tăng trưởng 2 con số',
			'date'     => '2025-07-18 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/07/photo1752658385869-17526583860491323695830-1752736140393794150225.jpg',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'Thị trường chứng khoán Việt Nam đứng trước giai đoạn chuyển mình nhờ nền tảng vĩ mô, kỳ vọng nâng hạng và mục tiêu tăng trưởng cao.',
			'lead'     => 'Thị trường chứng khoán Việt Nam đang đứng trước một giai đoạn chuyển mình lịch sử, từ một kênh đầu tư dần trở thành trụ cột chiến lược cho phát triển quốc gia, được thúc đẩy bởi câu chuyện nâng hạng và nền tảng vĩ mô vững chắc.',
			'caption'  => 'Nền tảng vĩ mô và nâng hạng thị trường là hai động lực lớn cho chu kỳ mới.',
			'url'      => 'https://qpxanh.com.vn/dinh-gia-chung-khoan-viet-nam-se-bung-no-neu-kinh-te-tang-truong-2-con-so/',
			'blocks'   => array(
				array(
					'heading'    => 'Động lực từ vĩ mô và nâng hạng',
					'paragraphs' => array(
						'Chính phủ đặt mục tiêu nâng quy mô vốn hóa thị trường cổ phiếu lên 120% GDP và thị trường trái phiếu doanh nghiệp lên 25% GDP vào năm 2030, nhấn mạnh vai trò của thị trường chứng khoán trong cấu trúc vốn của nền kinh tế.',
						'Câu chuyện nâng hạng từ thị trường cận biên lên mới nổi đang gần đến hồi kết. Nếu thành công, đây sẽ là chất xúc tác lớn, khơi thông dòng vốn ngoại trị giá hàng tỷ USD và hỗ trợ định giá lại thị trường.',
					),
				),
				array(
					'heading'    => 'Chiến lược đầu tư dài hạn',
					'paragraphs' => array(
						'Với mức P/E dự phóng năm 2025 còn quanh 11,6 lần và tăng trưởng EPS 12-13%, thị trường vẫn còn dư địa nếu kịch bản tăng trưởng GDP cao thành hiện thực.',
						'Trong bối cảnh biến động, chiến lược đầu tư dựa trên nền tảng doanh nghiệp, quản trị rủi ro và điều chỉnh danh mục linh hoạt được xem là chìa khóa để tối ưu lợi nhuận dài hạn.',
					),
				),
			),
		),
		array(
			'slug'     => 'gia-dau-tang-vot-co-phieu-dau-khi-dong-loat-noi-song-dieu-gi-dang-dien-ra',
			'title'    => 'Giá dầu tăng vọt, cổ phiếu dầu khí đồng loạt nổi sóng, điều gì đang diễn ra?',
			'date'     => '2025-06-15 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/06/screenshot-2025-06-13-at-093638-1749782477665-1749782477838609155765.png',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'Căng thẳng địa chính trị và giá dầu Brent tăng mạnh giúp nhóm cổ phiếu dầu khí đồng loạt bứt phá trong phiên giao dịch.',
			'lead'     => 'Thị trường chứng khoán mở phiên 13/6 chìm trong sắc đỏ trước lo ngại căng thẳng địa chính trị leo thang tại Trung Đông, nhưng nhóm dầu khí bất ngờ ngược dòng bứt phá với GAS, PVS, PVD, BSR, PLX và PVT cùng tăng mạnh.',
			'caption'  => 'Nhóm dầu khí nổi sóng khi giá dầu Brent tăng lên vùng cao nhất trong hai tháng.',
			'url'      => 'https://qpxanh.com.vn/gia-dau-tang-vot-co-phieu-dau-khi-dong-loat-noi-song-dieu-gi-dang-dien-ra/',
			'blocks'   => array(
				'Đà tăng của nhóm dầu khí đến từ diễn biến giá dầu. Giá dầu Brent Biển Bắc tăng vọt 9% lên 75 USD một thùng sau động thái mới nhất của Israel nhắm vào Iran, ghi nhận tuần tăng tốt nhất trong hơn 3 năm.',
				'Dữ liệu của EIA cho thấy lượng dầu thô dự trữ của Hoa Kỳ giảm mạnh hơn dự kiến, báo hiệu nhu cầu tích cực. Lạm phát Mỹ yếu hơn cũng củng cố kỳ vọng Fed cắt giảm lãi suất, hỗ trợ triển vọng tăng trưởng và tiêu thụ dầu.',
				'SSI Research kỳ vọng giá dầu trung bình dao động 70-75 USD một thùng trong năm 2025. Triển vọng lợi nhuận nhóm dầu khí có thể cải thiện nhờ hoạt động E&P sôi nổi, dự án Lô B và các dự án LNG, lọc dầu lớn trong chuỗi giá trị ngành.',
			),
		),
		array(
			'slug'     => 'dai-hoi-dong-co-dong-thuong-nien-2025-cua-qp-xanh-thanh-cong-ruc-ro-va-dinh-huong-phat-trien-ben-vung',
			'title'    => 'Đại hội đồng cổ đông thường niên 2025 của QP Xanh: Thành công rực rỡ và định hướng phát triển bền vững',
			'date'     => '2025-05-29 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/05/Anh-2-scaled.webp',
			'category' => 'Tin tức sự kiện',
			'tax'      => array( 'qp_news_category' => array( 'thong-cao' ) ),
			'excerpt'  => 'QP Xanh tổ chức thành công Đại hội đồng cổ đông thường niên 2025, thông qua nhiều báo cáo và định hướng phát triển bền vững.',
			'lead'     => 'Công ty Cổ phần Đầu tư QP Xanh đã tổ chức thành công Đại hội đồng cổ đông thường niên năm 2025 tại TP.HCM trong không khí trang trọng, dân chủ và minh bạch với sự tham dự đông đủ của quý vị cổ đông.',
			'caption'  => 'ĐHĐCĐ thường niên 2025 là diễn đàn quan trọng để QP Xanh nhìn lại kết quả và thống nhất định hướng phát triển.',
			'url'      => 'https://qpxanh.com.vn/dai-hoi-dong-co-dong-thuong-nien-2025-cua-qp-xanh-thanh-cong-ruc-ro-va-dinh-huong-phat-trien-ben-vung/',
			'blocks'   => array(
				array(
					'heading'    => 'Điểm nhấn quan trọng tại Đại hội',
					'paragraphs' => array(
						'Đại hội là dịp để QP Xanh nhìn lại chặng đường hoạt động năm 2024, đồng thời định ra những chiến lược quan trọng cho năm 2025 và các năm tiếp theo.',
						'Các báo cáo quan trọng đã được trình bày và thông qua, bao gồm báo cáo tài chính đã kiểm toán năm 2024, phương án phân phối lợi nhuận, kế hoạch kinh doanh năm 2025, lựa chọn đơn vị kiểm toán độc lập và báo cáo của Ban Kiểm soát.',
						'Đại hội cũng thông qua kế hoạch chi trả thù lao năm 2025 cho HĐQT, UBKT, phương án phát hành riêng lẻ, sửa đổi điều lệ công ty và ban hành cơ cấu tổ chức, quy chế hoạt động của Hội đồng Quản trị.',
					),
				),
				array(
					'heading'    => 'Nâng cao giá trị cho cổ đông và cộng đồng',
					'paragraphs' => array(
						'ĐHĐCĐ thường niên 2025 không chỉ là sự kiện quan trọng mà còn là diễn đàn để cổ đông đóng góp ý kiến vào sự phát triển chung của công ty. Ban lãnh đạo đã lắng nghe và tiếp thu các ý kiến với tinh thần cầu thị, minh bạch.',
						'Với sự đồng thuận cao từ cổ đông, QP Xanh cam kết tiếp tục nỗ lực để đạt mục tiêu đã đề ra, nâng cao hiệu quả hoạt động và mang lại giá trị bền vững cho cổ đông, đối tác và cộng đồng.',
					),
				),
			),
		),
		array(
			'slug'     => 'chien-luoc-bom-tien-gom-bat-dong-san-cua-chanel',
			'title'    => 'Chiến lược bơm tiền gom bất động sản của Chanel',
			'date'     => '2025-05-28 08:00:00',
			'image'    => 'https://i1-kinhdoanh.vnecdn.net/2025/05/28/2025-05-23T171907Z-1668855921-3300-2083-1748367746.jpg?w=680&h=0&q=100&dpr=1&fit=crop&s=Yj6qq_j777DvMTf3E9pABQ',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'thi-truong' ) ),
			'excerpt'  => 'Dù doanh thu giảm trong năm 2024, Chanel vẫn kiên trì chiến lược đầu tư bất động sản và mở rộng mạng lưới cửa hàng toàn cầu.',
			'lead'     => 'Đối diện doanh thu giảm 4,3% vào năm 2024, Chanel vẫn kiên trì chiến lược thâu tóm bất động sản và dự kiến mở thêm 48 cửa hàng mới trong năm nay.',
			'caption'  => 'Bất động sản vị trí đắc địa trở thành một phần trong chiến lược dài hạn của các thương hiệu xa xỉ.',
			'url'      => 'https://qpxanh.com.vn/chien-luoc-bom-tien-gom-bat-dong-san-cua-chanel/',
			'blocks'   => array(
				'Doanh thu năm 2024 của Chanel giảm 4,3% so với năm 2023, còn 17,9 tỷ euro. Diễn biến này trái ngược với giai đoạn tăng trưởng mạnh trước đó và phản ánh môi trường vĩ mô nhiều thách thức tại các thị trường lớn như Trung Quốc và Mỹ.',
				'Dù lợi nhuận hoạt động giảm, Chanel cho rằng sự sụt giảm chủ yếu đến từ tăng cường đầu tư. Tổng đầu tư năm 2024 tăng 43% lên 1,7 tỷ euro, trong đó khoảng 600 triệu USD được dành cho các mặt bằng đẹp và các thương vụ bất động sản chiến lược.',
				'Sở hữu bất động sản giúp các thương hiệu xa xỉ kiểm soát vị trí bán lẻ, tránh biến động giá thuê, bảo vệ hình ảnh thương hiệu và duy trì trải nghiệm khách hàng. Sang năm 2025, Chanel tiếp tục lên kế hoạch mở rộng đầu tư và khai trương 48 cửa hàng mới.',
			),
		),
		array(
			'slug'     => 'de-heus-xay-chuoi-cung-ung-ben-vung-cho-nganh-chan-nuoi',
			'title'    => 'De Heus xây chuỗi cung ứng bền vững cho ngành chăn nuôi',
			'date'     => '2025-04-26 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/04/ong-johan-van-den-ban-ceo-de-h-8695-4499-1745584100-scaled.webp',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'truyen-thong' ) ),
			'excerpt'  => 'De Heus sử dụng năng lượng tái tạo, giảm bao bì nhựa, hỗ trợ nông dân và áp dụng an toàn sinh học để xây dựng chuỗi cung ứng bền vững.',
			'lead'     => 'De Heus sử dụng năng lượng tái tạo, giảm bao bì nhựa, hỗ trợ nông dân và áp dụng an toàn sinh học nhằm xây dựng chuỗi cung ứng bền vững cho ngành chăn nuôi.',
			'caption'  => 'De Heus chia sẻ định hướng phát triển chuỗi cung ứng bền vững tại Supplier Summit 2025.',
			'url'      => 'https://qpxanh.com.vn/de-heus-xay-chuoi-cung-ung-ben-vung-cho-nganh-chan-nuoi/',
			'blocks'   => array(
				'Hội nghị Nhà cung cấp 2025 do De Heus Việt Nam tổ chức tại TP.HCM quy tụ hơn 100 đối tác chiến lược và nhà cung cấp trong chuỗi giá trị nông nghiệp - thực phẩm.',
				'Doanh nghiệp hiện có khoảng 10.000 nhân viên, vận hành 90 nhà máy trên toàn cầu, trong đó có 14 nhà máy tại Việt Nam. Năm 2024, De Heus sản xuất khoảng 3 triệu tấn thức ăn chăn nuôi tại Việt Nam và đặt mục tiêu đạt 5 triệu tấn tại khu vực châu Á trong năm 2025.',
				'Các sáng kiến nổi bật gồm hệ thống năng lượng mặt trời áp mái tại toàn bộ cơ sở sản xuất, nâng cao năng lực trồng bắp tại Tây Nguyên, hỗ trợ trang trại sử dụng kháng sinh có trách nhiệm và giảm bao bì nhựa bằng hệ thống vận chuyển hàng thay cho bao bì truyền thống.',
			),
		),
		array(
			'slug'     => 'binh-duong-thanh-lap-trung-tam-nghien-cuu-vi-dien-tu',
			'title'    => 'Bình Dương thành lập Trung tâm nghiên cứu vi điện tử',
			'date'     => '2025-04-19 08:00:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/04/z6518354512952-1f331d26695eb27d3b3ea1a14260aa3d.webp',
			'category' => 'Truyền thông',
			'tax'      => array( 'qp_news_category' => array( 'su-kien' ) ),
			'excerpt'  => 'Becamex IDC và Viện Fraunhofer ENAS ký thỏa thuận hợp tác thành lập Trung tâm Nghiên cứu vi điện tử tại Bình Dương.',
			'lead'     => 'Tại Bình Dương, Tổng công ty Becamex IDC và Viện Hệ thống Nano Điện tử Fraunhofer ENAS của Đức ký kết thỏa thuận hợp tác thành lập Trung tâm Nghiên cứu vi điện tử.',
			'caption'  => 'Trung tâm nghiên cứu vi điện tử được kỳ vọng đưa Bình Dương tham gia sâu hơn vào chuỗi giá trị công nghệ cao.',
			'url'      => 'https://qpxanh.com.vn/binh-duong-thanh-lap-trung-tam-nghien-cuu-vi-dien-tu/',
			'blocks'   => array(
				'Trung tâm được kỳ vọng trở thành đầu mối nghiên cứu, phát triển trong lĩnh vực vi điện tử và bán dẫn, góp phần đưa Bình Dương tham gia sâu vào chuỗi giá trị công nghệ cao toàn cầu.',
				'Dự án mở ra mô hình hợp tác giữa Việt Nam và Đức trong lĩnh vực công nghệ cao, tạo nền tảng cho đổi mới sáng tạo và phát triển bền vững ngành công nghiệp bán dẫn trong nước.',
				'Bình Dương đang xem phát triển ngành vi mạch, bán dẫn là lựa chọn chiến lược và ưu tiên hàng đầu, đồng thời khởi động chương trình đào tạo nguồn nhân lực chất lượng cao để đáp ứng yêu cầu của doanh nghiệp công nghệ cao.',
			),
		),
		array(
			'slug'     => 'binh-duong-trinh-thu-tuong-phuong-an-lam-tuyen-metro-56-000-ti-noi-metro-so-1-tp-hcm',
			'title'    => 'Bình Dương trình Thủ tướng phương án làm tuyến metro 56.000 tỉ nối metro số 1 TP.HCM',
			'date'     => '2025-04-19 07:30:00',
			'image'    => 'https://qpxanh.com.vn/wp-content/uploads/2025/04/1737360650-metro-so-1-tphcm-1024x639.jpg',
			'category' => 'Tin tức sự kiện',
			'tax'      => array( 'qp_news_category' => array( 'su-kien' ) ),
			'excerpt'  => 'Bình Dương trình phương án metro số 1 TP mới - Suối Tiên, tổng mức đầu tư hơn 56.000 tỷ đồng và dự kiến khai thác cuối năm 2031.',
			'lead'     => 'UBND tỉnh Bình Dương trình Thủ tướng, Bộ Tài chính báo cáo nghiên cứu tiền khả thi dự án metro số 1 TP mới - Suối Tiên, kết nối với metro số 1 TP.HCM.',
			'caption'  => 'Tuyến metro Bình Dương - Suối Tiên được kỳ vọng tạo không gian phát triển đô thị mới cho vùng.',
			'url'      => 'https://qpxanh.com.vn/binh-duong-trinh-thu-tuong-phuong-an-lam-tuyen-metro-56-000-ti-noi-metro-so-1-tp-hcm/',
			'blocks'   => array(
				'Dự án có điểm đầu tại ga S1 thuộc trung tâm TP mới Bình Dương và điểm cuối tại ga bến xe Suối Tiên, kết nối tuyến metro số 1 TP.HCM. Tuyến dự kiến đi trên cao theo trục Hùng Vương, ĐX.01, Mỹ Phước - Tân Vạn và kết nối về Suối Tiên.',
				'Sơ bộ tổng mức đầu tư dự án khoảng 56.301 tỷ đồng, gồm ngân sách tỉnh, vốn phát triển đô thị theo hình thức TOD và ngân sách trung ương hỗ trợ.',
				'Tuyến metro số 1 Bình Dương cùng metro số 1 TP.HCM sẽ tạo thành tuyến đường sắt vùng TP mới - Suối Tiên - Bến Thành, góp phần thuận tiện cho người dân đi lại, thúc đẩy phát triển đô thị và phân bổ dân cư dọc hành lang tuyến.',
				'Tỉnh Bình Dương kiến nghị chấp thuận chủ trương đầu tư trong quý 2/2025, khởi công quý 2/2027 và đưa dự án vào khai thác thương mại cuối năm 2031.',
			),
		),
	);

	return array_map( 'qp_holdings_seed_news_qpxanh_item', $items );
}
