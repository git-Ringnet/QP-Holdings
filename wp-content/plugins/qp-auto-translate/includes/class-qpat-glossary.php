<?php
/**
 * Fixed company wording (footer box labels, address, departments) that
 * translation engines sometimes leave half-translated. Applied after every
 * engine so EN/ZH output is consistent.
 *
 * Patterns copied unchanged from QP LLM Translate (proven on production).
 */

defined( 'ABSPATH' ) || exit;

final class QPAT_Glossary {
	/**
	 * @param array<string, string> $out
	 * @return array<string, string>
	 */
	public static function normalize( array $out, string $target ): array {
		if ( ! in_array( $target, array( 'en', 'zh' ), true ) ) {
			return $out;
		}

		$replacements_en = array(
			// Taglines & Titles
			'/QP\s+HOLDINGS\s*[-–—]\s*NHÀ\s+PHÁT\s+TRIỂN\s+BẤT\s+ĐỘNG\s+SẢN/ui' => 'QP HOLDINGS – REAL ESTATE DEVELOPER',

			// Address terms & Address string
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+(?:Công\s+Trường|Cong\s+Truong)\s*(?:Mê|Me)\s*Linh,\s*(?:P\.|Phường)\s*(?:Sài\s+Gòn|Sai\s+Gon),\s*(?:TP\.\s*Hồ\s+Chí\s+Minh|Thành\s+phố\s+Hồ\s+Chí\s+Minh|Ho\s+Chi\s+Minh\s+City)(?:,\s*(?:Việt\s+Nam|Vietnam))?/ui' => 'Level L16, Vietcombank Tower, 5 Cong Truong Me Linh, Sai Gon Ward, Ho Chi Minh City',
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank/ui' => 'Level L16, Vietcombank Tower',
			'/\b(?:Địa\s+chỉ|Address)\s*:/ui' => 'Address:',
			'/\bTrang\s+web\s*:/ui' => 'Website:',
			'/\bĐường\s+dây\s+nóng\s*:/ui' => 'Hotline:',
			'/\bThư\s+điện\s+tử\s*:/ui' => 'Email:',

			// Stock ticker
			'/\bMã\s+chứng\s+khoán\s*:\s*HKT/ui' => 'Stock ticker: HKT',
			'/\bMã\s+chứng\s+khoán\s*:/ui' => 'Stock ticker:',

			// Reference source
			'/\bNguồn\s+(?:số\s+liệu\s+)?tham\s+khảo\s*:/ui' => 'Reference sources:',

			// Editorial credits
			'/\bTổng\s+hợp\s+và\s+biên\s+soạn\s+(?:nội\s+dung\s*)?:/ui' => 'Compiled and edited by:',
			'/Ban\s+Truyền\s+Thông\s*[-–—]\s*QP\s+HOLDINGS/ui' => 'Communications Department — QP HOLDINGS',
			'/Ban\s+Truyền\s+thông\s*[-–—]\s*QP\s+Holdings/ui' => 'Communications Department — QP Holdings',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư\s*[-–—]\s*QP\s+HOLDINGS/ui' => 'Investor Relations Department — QP HOLDINGS',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư/ui' => 'Investor Relations Department',
			'/Ban\s+Truyền\s+[tT]hông/ui' => 'Communications Department',
		);

		$replacements_zh = array(
			// Taglines & Titles
			'/QP\s+HOLDINGS\s*[-–—]\s*(?:NHÀ\s+PHÁT\s+TRIỂN\s+BẤT\s+ĐỘNG\s+SẢN|REAL\s+ESTATE\s+DEVELOPER)/ui' => 'QP HOLDINGS – 房地产开发商',

			// Address terms & Address string
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank,\s*Số\s+5\s+(?:Công\s+Trường|Cong\s+Truong)\s*(?:Mê|Me)\s*Linh,\s*(?:P\.|Phường)\s*(?:Sài\s+Gòn|Sai\s+Gon),\s*(?:TP\.\s*Hồ\s+Chí\s+Minh|Thành\s+phố\s+Hồ\s+Chí\s+Minh|Ho\s+Chi\s+Minh\s+City)(?:,\s*(?:Việt\s+Nam|Vietnam))?/ui' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市',
			'/Level\s+L?16,\s*Vietcombank\s+Tower,\s*5\s+Cong\s+Truong\s+Me\s+Linh,\s*Sai\s+Gon\s+Ward,\s*Ho\s+Chi\s+Minh\s+City(?:,\s*Vietnam)?/ui' => 'L16层，Vietcombank Tower，Công Trường Mê Linh 5号，Sài Gòn坊，胡志明市',
			'/Tầng\s+L?16,\s*Tòa\s+nhà\s+Vietcombank/ui' => 'L16层，Vietcombank Tower',

			// Contact Labels in Chinese Characters (Hanzi)
			'/\b(?:Địa\s+chỉ|Address)\s*:/ui' => '地址：',
			'/\b(?:Website|Trang\s+web)\s*:/ui' => '官方网站：',
			'/\b(?:Hotline|Đường\s+dây\s+nóng)\s*:/ui' => '服务热线：',
			'/\b(?:Email|Thư\s+điện\s+tử)\s*:/ui' => '电子邮箱：',
			'/\b(?:Fanpage\s*FB|Fanpage\s*Facebook|Fanpage|Facebook)\s*:/ui' => 'Facebook 官方专页：',

			// Stock ticker
			'/\b(?:Mã\s+chứng\s+khoán|Stock\s+ticker|Stock\s+code)\s*:\s*HKT/ui' => '证券代码：HKT',
			'/\b(?:Mã\s+chứng\s+khoán|Stock\s+ticker|Stock\s+code)\s*:/ui' => '证券代码：',

			// Reference source
			'/\b(?:Nguồn\s+(?:số\s+liệu\s+)?tham\s+khảo|Reference\s+sources?)\s*:/ui' => '参考数据来源：',

			// Editorial credits
			'/\b(?:Tổng\s+hợp\s+và\s+biên\s+soạn\s+(?:nội\s+dung\s*)?|Compiled\s+(?:and|&)\s+edited\s+by)\s*:/ui' => '内容编辑与整理：',
			'/(?:Ban\s+Truyền\s+[tT]hông|Communications\s+Department)\s*[-–—]\s*QP\s+HOLDINGS/ui' => '传媒部 — QP HOLDINGS',
			'/(?:Ban\s+Truyền\s+[tT]hông|Communications\s+Department)\s*[-–—]\s*QP\s+Holdings/ui' => '传媒部 — QP Holdings',
			'/(?:Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư|Investor\s+Relations\s+Department)\s*[-–—]\s*QP\s+HOLDINGS/ui' => '投资者关系部 — QP HOLDINGS',
			'/Ban\s+Quan\s+hệ\s+Nhà\s+đầu\s+tư/ui' => '投资者关系部',
			'/Ban\s+Truyền\s+[tT]hông/ui' => '传媒部',
		);

		$map = ( 'zh' === $target ) ? $replacements_zh : $replacements_en;

		foreach ( $out as $k => $val ) {
			if ( ! is_string( $val ) || '' === trim( $val ) ) {
				continue;
			}
			foreach ( $map as $pattern => $replacement ) {
				$val = preg_replace( $pattern, $replacement, $val ) ?? $val;
			}
			$out[ $k ] = $val;
		}

		return $out;
	}
}
