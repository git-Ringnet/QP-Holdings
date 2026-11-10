<?php
/**
 * Legal pages (Điều khoản sử dụng / Chính sách bảo mật).
 *
 * Migrates the previously hard-coded page bodies into editable WordPress
 * post_content so admins can manage them through the normal page editor (with
 * Polylang per-language pages). Seeding is non-destructive: it only fills a
 * page whose body is still empty, and never overwrites edited content.
 *
 * @package QP_Holdings
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canonical (cleaned) default body HTML for a legal page.
 *
 * @param string $key 'terms' | 'privacy'.
 * @return string
 */
function qp_holdings_legal_default_body( string $key ): string {
	if ( 'terms' === $key ) {
		return <<<'HTML'
<p>Xin chân thành cám ơn vì đã tin tưởng vào <a href="/">Sàn GD Bất Động Sản QP Holdings</a></p>
<p>Trước khi vào đọc tham khảo thông tin cũng như sử dụng dịch vụ của <strong>QP Holdings,</strong> xin vui lòng đọc và ghi nhớ Điều khoản sử dụng tại <strong>qpholdings.vn</strong>. Việc sử dụng hoặc truy cập vào website <strong>qpholdings.vn</strong> sẽ được hiểu là sự chấp nhận và đồng ý ràng buộc vào Điều khoản sử dụng.</p>
<p><strong>qpholdings.vn</strong> là website cho phép người dùng tìm kiếm thông tin về bất động sản, bao gồm:</p>
<p>Cung cấp thông tin dự án bất động sản, chủ đầu tư trong các bài đăng trên Website với mục đích hỗ trợ thêm thông tin đánh giá cho quý khách hàng tham khảo.</p>
<p>Thông tin bán/cho thuê bất động sản cùng với hình ảnh, nội dung được hỗ trợ và đăng tải từ đội ngũ kinh doanh.</p>
<h3>Các điều khoản sử dụng</h3>
<p>Truy cập tới website <strong>qpholdings.vn</strong>, quý khách hàng đồng ý:</p>
<h3>Thông tin, nội dung trên website:</h3>
<p>Không sử dụng bất kỳ thiết bị, phần mềm, quy trình, phương tiện để can thiệp hay cố gắng can thiệp vào hoạt động đúng đắn trên web.</p>
<p>Quý khách hàng có thể tải các tài liệu trên website <strong>qpholdings.vn</strong> về làm thông tin tham khảo, nhưng tuyệt đối không điều chỉnh các thông tin đó thành thông tin sai, hoặc vi phạm pháp luật và sau đó truyền tải đến những người dùng khác.</p>
<p><strong>qpholdings.vn</strong> không uỷ quyền hay cho phép bất cứ một tổ chức nào sử dụng hay chỉnh sửa với các tài liệu, bài viết trên website của <strong>qpholdings.vn</strong> trừ khi có văn bản đồng ý chính thức từ <strong>QP Holdings.</strong></p>
<p><strong>qpholdings.vn</strong> chịu trách nhiệm cho sự hợp pháp, tính đúng đắn của các dữ liệu, thông tin bất động sản được đăng trên website.</p>
<h3>Sử dụng Dịch vụ</h3>
<p>Liên quan đến việc quý khách hàng sử dụng Dịch vụ, chúng tôi có thể gửi cho quý khách hàng các thông báo dịch vụ, thông báo quản trị và thông tin bất động sản khác. Bạn có thể chọn không nhận một số thông báo này bằng cách từ chối trong email.</p>
<p><strong>qpholdings.vn</strong> có thể thêm hoặc xóa các tính năng, nội dung và chúng tôi cũng có thể ngừng một Dịch vụ mà không phải xin phép quý khách hàng. Chúng tôi sẽ gửi thông báo đến cho quý khách hàng để quý khách hàng nắm bắt các thông tin này một cách nhanh nhất.</p>
<h3>Gửi thông tin hỗ trợ</h3>
<p>Quý khách hàng chỉ gửi yêu cầu hỗ trợ về các thông tin hỗ trợ trên website của <strong>QP Holdings</strong> qua các kênh như điện thoại, email, chat. Nếu quý khách hàng nhận được bất kỳ thông tin hỗ trợ nào nhưng không phải từ các kênh này, vui lòng bỏ qua thông tin đó. <strong>QP Holdings</strong> sẽ không chịu trách nhiệm cho độ xác thực của những thông tin từ nguồn đó.</p>
<p>Chỉ nhập các thông tin được yêu cầu trong phần liên hệ. Không sử dụng các từ ngữ mang tính chất xấu như các từ ngữ phản động hoặc sử dụng các từ ngữ khiếm nhã…</p>
<p>Cung cấp chính xác các thông tin BĐS, thông tin liên hệ, các thông tin sẽ được gởi đến quý khách hàng sau khi chuyên viên chúng tôi xác thực lại, và sẽ bị bỏ qua nếu thông tin không chính xác.</p>
<h3>Chia sẻ thông tin trên các trang mạng xã hội</h3>
<p>Các thông tin, bài viết, chia sẻ hữu ích trên website <strong>qpholdings.vn</strong>, quý khách hàng có thể chia sẻ trên các trang mạng xã hội cá nhân như: Facebook, Twitter, Messenger, Email.</p>
<p>Khi quý khách hàng muốn chia sẻ các thông tin của chúng tôi trên các trang cá nhân này của mình, đồng nghĩa với việc quý khách hàng đã đồng ý thực hiện các thao tác chia sẻ thông tin các tài khoản này với <strong>QP Holdings</strong>, như vậy chúng tôi mới có thể hỗ trợ quý khách hàng về việc chia sẻ thông tin lên mạng xã hội này.</p>
HTML;
	}

	if ( 'privacy' === $key ) {
		return <<<'HTML'
<p>Chính sách Bảo mật Thông tin ("Chính sách") này cung cấp thông tin chi tiết về các hoạt động thu thập, xử lý, sử dụng, bảo vệ dữ liệu cá nhân của Người dùng, được tiến hành bởi <strong>CÔNG TY CỔ PHẦN QP HOLDINGS ("QP Holdings"</strong> hoặc <strong>"Chúng tôi")</strong> khi Người dùng sử dụng Website có tên miền <a href="https://www.qpholdings.vn" target="_blank" rel="noopener noreferrer">https://www.qpholdings.vn</a>. Chính sách này chỉ áp dụng đối với Dữ liệu cá nhân của Người dùng cung cấp trên Website. Mọi Dữ liệu cá nhân của Người dùng cung cấp bên ngoài Website đều không thuộc phạm vi điều chỉnh của Chính sách này.</p>
<p>Bằng việc sử dụng và cung cấp thông tin trên Website, Người dùng xác nhận đã đọc, hiểu, đồng ý với Chính sách và toàn bộ các thông tin được cập nhật tùy từng thời điểm trên Website.</p>
<h3>I. ĐỊNH NGHĨA</h3>
<p>1. <strong>"Dữ liệu cá nhân"</strong> hoặc <strong>"DLCN"</strong> là dữ liệu số hoặc thông tin dưới dạng khác xác định hoặc giúp xác định một con người cụ thể, bao gồm: dữ liệu cá nhân cơ bản và dữ liệu cá nhân nhạy cảm. DLCN sau khi khử nhận dạng không còn là DLCN.</p>
<p>2. <strong>"Chủ thể dữ liệu"</strong> là người được DLCN phản ánh.</p>
<p>3. <strong>"Người dùng"</strong> là các Chủ thể dữ liệu tương tác với Website thông qua các hoạt động như truy cập, tìm kiếm thông tin, gửi phản hồi, liên hệ hỗ trợ, hoặc bất kỳ hình thức tương tác nào khác, bao gồm nhưng không giới hạn ở khách hàng, ứng viên, đại diện của các đối tác, nhà cung cấp dịch vụ và các bên khác có DLCN được thu thập, xử lý thông qua Website.</p>
<p>4. <strong>"Mục Đích"</strong> là các Mục đích xử lý Dữ liệu cá nhân nêu tại Mục II của Chính sách.</p>
<p>5. <strong>"Xử lý DLCN"</strong> hoặc "Xử lý" là hoạt động tác động đến DLCN bao gồm một hoặc nhiều hoạt động như sau: thu thập, phân tích, tổng hợp, mã hóa, giải mã, chỉnh sửa, xóa, hủy, khử nhận dạng, cung cấp, công khai, chuyển giao DLCN và các hoạt động khác tác động đến DLCN.</p>
<h3>II. MỤC ĐÍCH XỬ LÝ DỮ LIỆU CÁ NHÂN</h3>
<p><strong>1. QP Holdings sẽ xử lý DLCN của Người dùng vào các mục đích sau đây:</strong></p>
<p>a) Liên lạc, hỗ trợ và giải quyết các câu hỏi, yêu cầu hoặc khiếu nại của Người dùng.</p>
<p>b) Lưu trữ, sao lưu DLCN để tuân thủ các yêu cầu pháp lý, quy định của Cơ quan Nhà nước có thẩm quyền, các yêu cầu về tuân thủ quy định nội bộ, cho mục đích khôi phục sau thảm họa hoặc mục đích khác.</p>
<p>c) Nghiên cứu thị trường, tổng hợp, phân tích thông tin chung của Người dùng (độ tuổi, khu vực lưu trú …) để phục vụ việc thống kê và xây dựng các chính sách bán hàng phù hợp.</p>
<p><strong>2. Xử lý DLCN trong trường hợp không cần sự đồng ý của Người dùng:</strong></p>
<p>Theo quy định pháp luật, Chúng tôi có thể xử lý DLCN mà không cần sự đồng ý của Người dùng trong các trường hợp sau:</p>
<p>a) Để bảo vệ tính mạng, sức khỏe, danh dự, nhân phẩm, quyền, lợi ích hợp pháp của Người dùng hoặc cá nhân khác trong trường hợp cấp bách; bảo vệ quyền hoặc lợi ích chính đáng của Chúng tôi, của bên thứ ba hoặc lợi ích của Nhà nước một cách cần thiết trước hành vi xâm phạm lợi ích nói trên.</p>
<p>b) Để giải quyết tình trạng khẩn cấp; nguy cơ đe dọa an ninh quốc gia nhưng chưa đến mức ban bố tình trạng khẩn cấp; phòng, chống bạo loạn, khủng bố, phòng, chống tội phạm và vi phạm pháp luật.</p>
<p>c) Để thực hiện thỏa thuận của Chủ thể dữ liệu với cơ quan, tổ chức, cá nhân có liên quan theo quy định pháp luật.</p>
<p>d) Thực hiện theo yêu cầu của Cơ quan Nhà nước có thẩm quyền và các trường hợp khác theo quy định pháp luật.</p>
<p><strong>3. Tại từng thời điểm, phù hợp với những sản phẩm và dịch vụ mà Chúng tôi cung cấp cho Người dùng, Chúng tôi có thể đề nghị Người dùng cung cấp thêm một số thông tin, DLCN khác. Người dùng hiểu và đồng ý rằng, việc cung cấp thông tin, DLCN này là hoàn toàn tự nguyện và phù hợp với ý chí của Người dùng.</strong></p>
<h3>III. CÁC LOẠI DỮ LIỆU CÁ NHÂN CỦA NGƯỜI DÙNG ĐƯỢC XỬ LÝ</h3>
<p>Đối với các Mục Đích nêu tại Mục II của Chính sách, Chúng tôi thu thập và xử lý những DLCN của Người dùng như sau:</p>
<p>1. Thông tin liên lạc như: Họ tên; số điện thoại, email.</p>
<p>2. Nội dung câu hỏi, yêu cầu hoặc khiếu nại mà Người dùng gửi cho chúng tôi thông qua Website.</p>
<p>3. Bất kỳ thông tin, DLCN nào khác mà Người dùng cung cấp khi truy cập và sử dụng các tính năng trên Website của Chúng tôi.</p>
<h3>IV. THỜI GIAN LƯU TRỮ DỮ LIỆU CÁ NHÂN</h3>
<p>1. DLCN của Người dùng sẽ được lưu trữ và bảo mật trên máy chủ của QP Holdings trong thời gian lưu trữ cần thiết để có thể hoàn thành các Mục Đích được nêu tại Mục II của Chính sách này, hoặc cho đến khi có yêu cầu xóa, hủy DLCN từ Người dùng, hoặc theo các quy định pháp luật hiện hành.</p>
<p>2. Chúng tôi bảo lưu quyền từ chối xóa, hủy DLCN trong trường hợp (i) thuộc những trường hợp mà DLCN không được xóa, hủy theo quy định pháp luật hiện hành, hoặc (ii) Người dùng không thể cung cấp đầy đủ các tài liệu để chứng minh tư cách Chủ thể dữ liệu của mình khi đề nghị Chúng tôi xóa dữ liệu.</p>
<h3>V. TỔ CHỨC VÀ CÁ NHÂN LIÊN QUAN CÓ THỂ TIẾP CẬN VÀ XỬ LÝ DỮ LIỆU CÁ NHÂN CỦA NGƯỜI DÙNG</h3>
<p>1. Chúng tôi có thể chia sẻ, tiết lộ và chuyển giao DLCN của Người dùng cho các Bên xử lý DLCN, bên thứ ba và/hoặc các công ty, tổ chức liên kết của Chúng tôi cho một hoặc nhiều mục đích nêu tại Chính sách này, bao gồm nhưng không giới hạn:</p>
<p>a) Công ty con, công ty liên kết, chi nhánh, các đơn vị có liên quan khác của QP Holdings.</p>
<p>b) Các đối tác, nhà cung cấp dịch vụ hoặc hỗ trợ hoạt động kinh doanh của QP Holdings.</p>
<p>c) Cơ quan Nhà nước, tổ chức, cá nhân có thẩm quyền theo quy định của pháp luật hoặc theo sự đồng ý của Người dùng.</p>
<p>2. Chúng tôi không tiết lộ hoặc chuyển DLCN của Người dùng cho bất kỳ bên thứ ba nào để sử dụng cho các hoạt động tiếp thị, quảng cáo trực tiếp của họ.</p>
<p>3. Website có thể chứa các liên kết đến các website của bên thứ ba nhằm mục đích cung cấp thêm thông tin hoặc chức năng. Việc nhấp vào các liên kết này có thể dẫn Người dùng đến các website do các bên độc lập và không do Chúng tôi vận hành và/ hoặc kiểm soát. Chúng tôi không chịu trách nhiệm về các hành vi của các website này và khuyến khích Người dùng thận trọng và xem xét kỹ Chính sách thu thập, bảo mật thông tin của họ trước khi cung cấp bất kỳ thông tin cá nhân nào.</p>
<h3>VI. BẢO VỆ DỮ LIỆU CÁ NHÂN CỦA NGƯỜI DÙNG</h3>
<p>1. Chúng tôi sẽ thực hiện các biện pháp bảo mật hợp lý để bảo vệ DLCN của Người dùng mà Chúng tôi đang quản lý, tiếp nhận hoặc kiểm soát kể từ thời điểm Chúng tôi tiếp nhận DLCN và trong suốt quá trình xử lý DLCN. Chúng tôi sử dụng nhiều biện pháp quản trị, vật lý và kỹ thuật phù hợp nhằm bảo vệ DLCN của Người dùng khỏi việc truy cập, thu thập, sử dụng, tiết lộ, xử lý, sao chép, sửa đổi, hủy bỏ, mất mát, sử dụng sai mục đích, chỉnh sửa bất hợp pháp hoặc các rủi ro tương tự trong quá trình Người dùng sử dụng Website của Chúng tôi.</p>
<p>2. Mặc dù, Chúng tôi nỗ lực tối đa để bảo vệ an toàn DLCN của Người dùng, kiểm tra định kỳ và nâng cấp các biện pháp bảo mật cần thiết, Chúng tôi không thể ngăn chặn hoàn toàn các rủi ro như việc bị bất kỳ bên nào truy cập trái phép vào DLCN của Người dùng. Chúng tôi không chịu trách nhiệm đối với Người dùng cho bất kỳ tổn thất nào trong trường hợp Website, hệ thống xử lý dữ liệu, phần mềm, hệ thống truyền tín hiệu gặp sự cố, bị lỗi, bị tấn công hoặc vì bất kỳ lý do khách quan nào khác nằm ngoài khả năng kiểm soát của Chúng tôi. Trường hợp phát hiện có vi phạm, Chúng tôi sẽ tuân thủ việc báo cáo, đồng thời sẽ nỗ lực khắc phục, ngăn chặn thiệt hại trong khả năng của Chúng tôi và theo quy định pháp luật hiện hành.</p>
<h3>VII. QUYỀN VÀ NGHĨA VỤ CỦA NGƯỜI DÙNG ĐỐI VỚI DỮ LIỆU CÁ NHÂN</h3>
<p>1. Trừ trường hợp pháp luật có các quy định khác, liên quan đến hoạt động Xử lý DLCN của Chúng tôi, Người dùng có quyền:</p>
<p>a) Yêu cầu xem, chỉnh sửa DLCN của mình, hoặc yêu cầu Chúng tôi cung cấp DLCN của Người dùng.</p>
<p>b) Rút lại sự đồng ý cho phép xử lý DLCN của Người dùng, yêu cầu hạn chế, xóa và phản đối việc xử lý DLCN của Người dùng.</p>
<p>c) Khiếu nại, tố cáo, khởi kiện, yêu cầu bồi thường thiệt hại theo quy định của pháp luật.</p>
<p>d) Yêu cầu thực hiện các biện pháp, giải pháp bảo vệ DLCN của mình theo quy định pháp luật.</p>
<p>2. Nếu Người dùng có bất kỳ câu hỏi hoặc thắc mắc nào về việc Chúng tôi xử lý DLCN, hoặc nếu Người dùng muốn thực hiện các quyền của mình, vui lòng liên hệ Chúng tôi theo thông tin tại Mục VIII của Chính sách. Nếu có bất kỳ sự chậm trễ nào trong việc phản hồi của Chúng tôi, Chúng tôi sẽ kịp thời thông báo đến Người dùng việc này và về thời gian phản hồi chính thức của Chúng tôi.</p>
<p>3. Chúng tôi có quyền từ chối xử lý yêu cầu của Người dùng nếu Người dùng không cung cấp đủ thông tin để xác minh tính hợp lệ của yêu cầu.</p>
<p>4. Người dùng có trách nhiệm:</p>
<p>a) Cung cấp cho Chúng tôi DLCN chính xác để thực hiện các Mục Đích theo Chính sách này;</p>
<p>b) Có nghĩa vụ bảo vệ DLCN của mình và DLCN của người khác, cũng như tuân thủ các nghĩa vụ bảo vệ DLCN khác theo quy định pháp luật hiện hành;</p>
<p>c) Chỉ cung cấp DLCN của người khác khi đã nhận được sự đồng ý của họ để Chúng tôi xử lý theo Chính sách này;</p>
<p>d) Đảm bảo tính đầy đủ, tính chính xác và tính hợp pháp của DLCN của Người dùng cung cấp cho Chúng tôi. Chúng tôi không chịu trách nhiệm về nội dung của DLCN mà Người dùng cung cấp;</p>
<p>e) Chịu trách nhiệm đối với các thiệt hại, hạn chế hoặc bất cập khi thực hiện các quyền của Người dùng;</p>
<p>f) Không gây khó khăn, cản trở việc thực hiện quyền, nghĩa vụ pháp lý của Chúng tôi; và</p>
<p>g) Tuân thủ quy định của pháp luật về bảo vệ Dữ liệu cá nhân.</p>
<h3>VIII. THÔNG TIN LIÊN HỆ</h3>
<p>Nếu Người dùng có bất kỳ câu hỏi hoặc thắc mắc nào liên quan đến Chính sách của Chúng tôi, hoặc cần cập nhật, chỉnh sửa, xóa, hoặc phản đối việc xử lý DLCN, vui lòng liên hệ với Chúng tôi theo thông tin sau:</p>
<p><strong>CÔNG TY CỔ PHẦN QP HOLDINGS</strong><br>
Địa chỉ: Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Thành phố Hồ Chí Minh<br>
Email: info@qpholdings.vn<br>
Số điện thoại: 19000066<br>
Giờ làm việc: Buổi sáng: 08h00 – 12h00; Buổi chiều: 13h00 – 17h00 từ Thứ 2 đến Thứ 6 hàng tuần (trừ ngày nghỉ Lễ, Tết theo quy định)</p>
<h3>IX. PHIÊN BẢN VÀ CẬP NHẬT</h3>
<p>Chúng tôi có thể cập nhật Chính sách này khi cần thiết. Chính sách cập nhật sẽ được đăng trên Website <a href="https://www.qpholdings.vn" target="_blank" rel="noopener noreferrer">https://www.qpholdings.vn</a>. Việc Người dùng tiếp tục sử dụng Website của Chúng tôi sau ngày Chính sách được cập nhật đồng nghĩa với việc Người dùng chấp nhận Chính sách cập nhật.</p>
HTML;
	}

	return '';
}

/**
 * Seed legal page bodies into post_content (non-destructive). Fills only pages
 * whose body is still empty, across all configured language slugs.
 *
 * @return void
 */
function qp_holdings_seed_legal_pages(): void {
	if ( ! function_exists( 'qp_holdings_page_definitions' ) ) {
		return;
	}

	$defs = qp_holdings_page_definitions();

	foreach ( array( 'terms', 'privacy' ) as $key ) {
		if ( empty( $defs[ $key ] ) ) {
			continue;
		}
		$body = qp_holdings_legal_default_body( $key );
		if ( '' === $body ) {
			continue;
		}

		foreach ( array( 'vi', 'en', 'zh' ) as $lang ) {
			$slug = function_exists( 'qp_holdings_page_storage_slug' )
				? qp_holdings_page_storage_slug( $key, $lang )
				: '';
			if ( '' === $slug ) {
				continue;
			}

			$page = get_page_by_path( trim( $slug, '/' ), OBJECT, 'page' );
			if ( ! ( $page instanceof WP_Post ) ) {
				continue;
			}

			// Only seed when the body is still empty (never clobber edits).
			if ( '' !== trim( (string) $page->post_content ) ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_content' => $body,
				)
			);
		}
	}
}

/**
 * Run the one-time legal-content seed (separate flag from starter pages so it
 * applies to existing installs whose starter-page flag is already set).
 *
 * @return void
 */
function qp_holdings_maybe_seed_legal_pages(): void {
	$flag = 'qp_holdings_legal_pages_content_v1';
	if ( get_option( $flag ) ) {
		return;
	}
	qp_holdings_seed_legal_pages();
	update_option( $flag, 1 );
}
add_action( 'init', 'qp_holdings_maybe_seed_legal_pages', 31 );

/**
 * Render the editable body for a legal page (post_content), falling back to the
 * canonical default HTML if the page body is somehow empty.
 *
 * @param string $key 'terms' | 'privacy'.
 * @return void
 */
function qp_holdings_render_legal_body( string $key ): void {
	$content = get_the_content( null, false, get_queried_object_id() );

	if ( '' === trim( (string) $content ) ) {
		$content = qp_holdings_legal_default_body( $key );
	}

	echo apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content filters sanitize.
}
