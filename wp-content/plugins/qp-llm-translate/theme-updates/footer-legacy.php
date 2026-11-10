<?php defined( 'ABSPATH' ) || exit; ?>
<footer id="footer" class="footer-wrapper">
			<style>
				/* === Custom Footer Styles === */
				.footer-custom {
					background-color: #0B1F3A;
					padding: 30px 0 0;
					font-family: 'Open Sans', sans-serif;
				}

				.footer-custom .container {
					max-width: 1250px;
					margin: 0 auto;
					padding: 0 15px;
				}

				.footer-top {
					display: grid;
					grid-template-columns: auto 1fr auto;
					gap: 30px;
					padding-bottom: 24px;
					align-items: start;
				}

				.footer-logo-col {
					/* logo column */
				}

				.footer-logo-col img,
				.footer-logo-col svg {
					max-width: 180px;
					height: auto;
					display: block;
				}

				.footer-company-info {
					display: none;
				}

				.footer-links-col {
					/* middle content column */
					min-width: 0;
				}

				.footer-links-col h4 {
					color: #fff;
					font-size: 14px;
					font-weight: 700;
					line-height: 22px;
					margin: 0 0 12px 0;
					text-transform: uppercase;
				}

				.footer-offices {
					display: grid;
					grid-template-columns: 1fr 1fr 1fr;
					gap: 8px 24px;
				}

				.footer-links-col p {
					color: #d0d5d4;
					font-size: 13px;
					font-weight: 400;
					line-height: 22px;
					margin: 0 0 4px 0;
				}

				.footer-meta p {
					white-space: nowrap;
				}

				.footer-social-col {
					display: flex;
					align-items: flex-start;
					justify-content: flex-end;
					gap: 24px;
				}

				.footer-social-col a {
					display: inline-flex;
					align-items: center;
					justify-content: center;
					text-decoration: none;
					transition: opacity 0.2s;
				}

				.footer-social-col a:hover {
					opacity: 0.8;
				}

				.footer-divider {
					border: none;
					border-top: 1px solid rgba(255, 255, 255, 0.3);
					margin: 0 0 16px 0;
				}

				.footer-bottom {
					display: flex;
					flex-wrap: wrap;
					justify-content: space-between;
					align-items: center;
					padding-bottom: 16px;
				}

				.footer-copyright {
					color: #ADB2B1;
					font-size: 13px;
					font-weight: 400;
					line-height: 20px;
					margin: 0;
				}

				.footer-bottom-links {
					display: flex;
					gap: 32px;
				}

				.footer-bottom-links a {
					color: #ADB2B1;
					font-size: 13px;
					font-weight: 400;
					line-height: 20px;
					text-decoration: none;
					transition: color 0.2s;
				}

				.footer-bottom-links a:hover {
					color: #C9A96E;
				}

				@media (max-width: 849px) {
					.footer-top {
						grid-template-columns: 1fr;
						text-align: center;
						gap: 24px;
					}

					.footer-logo-col img,
					.footer-logo-col svg {
						margin: 0 auto;
					}

					.footer-offices {
						grid-template-columns: 1fr;
					}

					.footer-social-col {
						justify-content: center;
					}

					.footer-bottom {
						flex-direction: column;
						text-align: center;
						gap: 16px;
					}

					.footer-bottom-links {
						justify-content: center;
					}
				}
			</style>

			<section class="footer-custom">
				<div class="container">
					<div class="footer-top">
						<div class="footer-logo-col">
							
<svg class="footer-logo" style="width: 180px; min-height: 70px; display: block; margin-bottom: 20px;" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 290.27 112.09"><defs><style>.ft-cls-1{fill:url(#ft-linear-gradient);}.ft-cls-2{fill:url(#ft-linear-gradient-2);}.ft-cls-3{fill:url(#ft-linear-gradient-3);}.ft-cls-4{fill:url(#ft-linear-gradient-4);}.ft-cls-5{fill:url(#ft-linear-gradient-5);}.ft-cls-6{fill:url(#ft-linear-gradient-6);}.ft-cls-7{fill:url(#ft-linear-gradient-7);}.ft-cls-8{fill:url(#ft-linear-gradient-8);}.ft-cls-9{fill:url(#ft-linear-gradient-9);}.ft-cls-10{fill:url(#ft-linear-gradient-10);}.ft-cls-11{fill:url(#ft-linear-gradient-11);}.ft-cls-12{fill:url(#ft-linear-gradient-12);}.ft-cls-13{fill:url(#ft-linear-gradient-13);}.ft-cls-14{fill:url(#ft-linear-gradient-14);}.ft-cls-15{fill:url(#ft-linear-gradient-15);}.ft-cls-16{fill:url(#ft-linear-gradient-16);}.ft-cls-17{fill:url(#ft-linear-gradient-17);}</style><linearGradient id="ft-linear-gradient" x1="4.06" y1="-16.22" x2="109.39" y2="130.45" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#994b05"/><stop offset="0.19" stop-color="#f6eb9f"/><stop offset="0.3" stop-color="#e5c360"/><stop offset="0.42" stop-color="#aa7020"/><stop offset="0.74" stop-color="#f6eb9f"/><stop offset="1" stop-color="#a1651a"/></linearGradient><linearGradient id="ft-linear-gradient-2" x1="6.21" y1="-17.76" x2="111.55" y2="128.9" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#d3a049"/><stop offset="0.14" stop-color="#f6eb9f"/><stop offset="0.3" stop-color="#e5c360"/><stop offset="0.45" stop-color="#d3a049"/><stop offset="0.67" stop-color="#f6eb9f"/><stop offset="1" stop-color="#d3a049"/></linearGradient><linearGradient id="ft-linear-gradient-3" x1="86.19" y1="-55.27" x2="243.53" y2="106.07" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#e5c360"/><stop offset="0.14" stop-color="#f6eb9f"/><stop offset="0.42" stop-color="#d3a049"/><stop offset="0.67" stop-color="#f6eb9f"/><stop offset="1" stop-color="#e5c360"/></linearGradient><linearGradient id="ft-linear-gradient-4" x1="212.38" y1="-37.98" x2="268.38" y2="60.02" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#d3a049"/><stop offset="0.14" stop-color="#f6eb9f"/><stop offset="0.42" stop-color="#d3a049"/><stop offset="0.67" stop-color="#f6eb9f"/><stop offset="1" stop-color="#d3a049"/></linearGradient><linearGradient id="ft-linear-gradient-5" x1="147.39" y1="62.24" x2="154.73" y2="121.91" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#e5c360"/><stop offset="0.14" stop-color="#f6eb9f"/><stop offset="0.42" stop-color="#e5c360"/><stop offset="0.67" stop-color="#f6eb9f"/><stop offset="1" stop-color="#e5c360"/></linearGradient><linearGradient id="ft-linear-gradient-6" x1="169.37" y1="59.54" x2="176.7" y2="119.21" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-7" x1="188.77" y1="57.16" x2="196.1" y2="116.82" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-8" x1="207.7" y1="54.83" x2="215.03" y2="114.5" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-9" x1="222.86" y1="52.97" x2="230.19" y2="112.63" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-10" x1="237.89" y1="51.12" x2="245.22" y2="110.79" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-11" x1="259" y1="48.52" x2="266.34" y2="108.19" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-12" x1="277.21" y1="46.29" x2="284.55" y2="105.95" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-13" x1="196.16" y1="56.25" x2="203.49" y2="115.91" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-14" x1="211.4" y1="54.38" x2="218.73" y2="114.04" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-15" x1="223.85" y1="52.84" x2="231.18" y2="112.51" xlink:href="#ft-linear-gradient-5"/><linearGradient id="ft-linear-gradient-16" x1="190.47" y1="99.88" x2="194.27" y2="111.39" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#f6eb9f"/><stop offset="1" stop-color="#e5c360"/></linearGradient><linearGradient id="ft-linear-gradient-17" x1="239.65" y1="99.85" x2="239.65" y2="111.02" xlink:href="#ft-linear-gradient-16"/></defs><g id="ft-Layer_2" data-name="Layer 2"><g id="ft-Layer_1-2" data-name="Layer 1"><rect class="ft-cls-1" x="44.59" y="44.59" width="22.3" height="22.3"/><path class="ft-cls-2" d="M111.49,32v76.46a3,3,0,0,1-3,3H47.62a3,3,0,0,1-3-3V89.19H89.18V36a1,1,0,0,0-.3-.72L76.24,22.59a1,1,0,0,0-.71-.29H23.31a1,1,0,0,0-1,1V93.65l-8.93-8.92L8.93,80.27h0L4.47,75.81h0L.89,72.24A3,3,0,0,1,0,70.1V2A2,2,0,0,1,2,0H79.5a2,2,0,0,1,1.44.6l30,30A2,2,0,0,1,111.49,32Z"/><path class="ft-cls-3" d="M216.58,64.58l-2.83-2.23-5.42-4.29V20A11.16,11.16,0,0,0,197.17,8.84H152.6A11.27,11.27,0,0,0,141.37,20v2.24h53.57V47.47l-6.72-5.3-1.64-1.3H165l10.36,8.19H154.76V33.35H141.37V51.29a11.16,11.16,0,0,0,11.16,11.16h39.74l2.7,2.13Z"/><path class="ft-cls-4" d="M279.07,8.82,268,8.88l-36-.06h-8.66V22.27h53.56V33.32H223.32V62.45h13.39V46.71h42.4a11.16,11.16,0,0,0,11.16-11.15V20A11.21,11.21,0,0,0,279.07,8.82Z"/><polygon class="ft-cls-5" points="154.5 82.88 145.8 82.88 145.8 76.14 141.37 76.14 141.37 93.23 145.8 93.23 145.8 86.23 154.5 86.23 154.5 93.23 158.93 93.23 158.93 76.14 154.5 76.14 154.5 82.88"/><path class="ft-cls-6" d="M179.94,78.37a10.19,10.19,0,0,0-3.31-1.86,13.47,13.47,0,0,0-8.33,0A10.39,10.39,0,0,0,165,78.37a8.36,8.36,0,0,0-2.2,2.8,8.31,8.31,0,0,0,0,7A8.4,8.4,0,0,0,165,91a10.08,10.08,0,0,0,3.33,1.86,12.89,12.89,0,0,0,4.2.66,12.57,12.57,0,0,0,4.14-.66A10.36,10.36,0,0,0,179.94,91a8.42,8.42,0,0,0,2.2-2.8,7.94,7.94,0,0,0,.79-3.53,7.82,7.82,0,0,0-.79-3.53A8.69,8.69,0,0,0,179.94,78.37ZM178,86.91a5.08,5.08,0,0,1-1.27,1.73,5.78,5.78,0,0,1-1.9,1.13,6.93,6.93,0,0,1-2.37.39,7,7,0,0,1-2.38-.39,5.58,5.58,0,0,1-1.9-1.13,5.22,5.22,0,0,1-1.26-1.74,5.68,5.68,0,0,1,0-4.43,5.24,5.24,0,0,1,1.28-1.74,5.82,5.82,0,0,1,1.9-1.12,6.87,6.87,0,0,1,2.36-.39,7,7,0,0,1,2.38.39,5.72,5.72,0,0,1,1.9,1.12A5.18,5.18,0,0,1,178,82.47a5.7,5.7,0,0,1,0,4.44Z"/><polygon class="ft-cls-7" points="190.42 76.14 185.99 76.14 185.99 93.23 200.02 93.23 200.02 90.01 190.42 90.01 190.42 76.14"/><path class="ft-cls-8" d="M216.42,77.21a13.28,13.28,0,0,0-5.49-1.07h-8.7V93.23h8.7a13.28,13.28,0,0,0,5.49-1.06,8.74,8.74,0,0,0,3.72-3,8.26,8.26,0,0,0,0-9A8.7,8.7,0,0,0,216.42,77.21Zm-.2,10.29A5.1,5.1,0,0,1,214,89.34a7.9,7.9,0,0,1-3.32.65h-4.05V79.39h4.05A7.9,7.9,0,0,1,214,80a5.08,5.08,0,0,1,2.19,1.83,5.47,5.47,0,0,1,0,5.63Z"/><rect class="ft-cls-9" x="224.54" y="76.14" width="4.43" height="17.09"/><polygon class="ft-cls-10" points="246.42 86.51 236.9 76.14 233.24 76.14 233.24 93.23 237.61 93.23 237.61 82.86 247.16 93.23 250.79 93.23 250.79 76.14 246.42 76.14 246.42 86.51"/><path class="ft-cls-11" d="M260.07,80.73a6,6,0,0,1,2-1.12,7.83,7.83,0,0,1,2.54-.39,7.33,7.33,0,0,1,2.68.48,7,7,0,0,1,2.27,1.45l2.84-2.35A9.15,9.15,0,0,0,269,76.61a14.07,14.07,0,0,0-8.79-.11,10.11,10.11,0,0,0-3.34,1.84,8.53,8.53,0,0,0-2.2,2.81,8.42,8.42,0,0,0,0,7.08A8.6,8.6,0,0,0,256.82,91a10.15,10.15,0,0,0,3.31,1.84,12.84,12.84,0,0,0,4.17.65,15.77,15.77,0,0,0,4.11-.56A12.12,12.12,0,0,0,272,91.35V84.42h-4v5a6.56,6.56,0,0,1-.89.35,8.82,8.82,0,0,1-2.56.38,7.67,7.67,0,0,1-2.5-.39,5.76,5.76,0,0,1-2-1.14,5.2,5.2,0,0,1-1.28-1.74,5.31,5.31,0,0,1-.45-2.2,5.37,5.37,0,0,1,.45-2.22A5.11,5.11,0,0,1,260.07,80.73Z"/><path class="ft-cls-12" d="M289.66,86a4.67,4.67,0,0,0-1.6-1.42,9.6,9.6,0,0,0-2.18-.85c-.79-.21-1.58-.4-2.38-.57s-1.51-.32-2.17-.48a5.25,5.25,0,0,1-1.59-.65,1.23,1.23,0,0,1-.6-1.06,1.47,1.47,0,0,1,.36-1,2.51,2.51,0,0,1,1.17-.7,6.89,6.89,0,0,1,2.11-.27,10.87,10.87,0,0,1,2.62.33,12.07,12.07,0,0,1,2.68,1l1.37-3a11,11,0,0,0-3.09-1.13,16.92,16.92,0,0,0-3.55-.38,12.12,12.12,0,0,0-4.5.72,6,6,0,0,0-2.68,1.93,4.44,4.44,0,0,0-.89,2.67,3.86,3.86,0,0,0,.62,2.25,4.68,4.68,0,0,0,1.6,1.42,10.57,10.57,0,0,0,2.17.87c.79.23,1.59.42,2.38.58s1.52.32,2.17.49a5.14,5.14,0,0,1,1.59.66,1.23,1.23,0,0,1,.6,1.08,1.36,1.36,0,0,1-.38,1,2.54,2.54,0,0,1-1.19.65,7.73,7.73,0,0,1-2.12.23,11.73,11.73,0,0,1-3.41-.5,11.46,11.46,0,0,1-2.91-1.28l-1.5,3A10.94,10.94,0,0,0,277.75,93a17.62,17.62,0,0,0,4.4.55,12.27,12.27,0,0,0,4.53-.73,6.3,6.3,0,0,0,2.69-1.93,4.3,4.3,0,0,0,.9-2.64A3.76,3.76,0,0,0,289.66,86Z"/><polygon class="ft-cls-13" points="204.93 104.17 199.48 104.17 199.48 99.44 196.7 99.44 196.7 111.44 199.48 111.44 199.48 106.52 204.93 106.52 204.93 111.44 207.71 111.44 207.71 99.44 204.93 99.44 204.93 104.17"/><polygon class="ft-cls-14" points="223.25 99.44 220.17 99.44 214.85 105.04 214.85 99.44 212.09 99.44 212.09 111.44 214.85 111.44 214.85 108.41 216.46 106.73 220.31 111.44 223.54 111.44 218.29 104.78 223.25 99.44"/><polygon class="ft-cls-15" points="224.49 99.44 224.49 101.7 228.33 101.7 228.33 111.44 231.1 111.44 231.1 101.7 234.94 101.7 234.94 99.44 224.49 99.44"/><path class="ft-cls-16" d="M189.38,105.44a12.34,12.34,0,0,1,1.7-6.64h2.77a14.26,14.26,0,0,0,0,13.28h-2.77A12.34,12.34,0,0,1,189.38,105.44Z"/><path class="ft-cls-17" d="M237.41,112.09a14.3,14.3,0,0,0,0-13.3h2.77a13.79,13.79,0,0,1,0,13.3Z"/></g></g></svg>

							<div class="footer-company-info">
								<p class="footer-company-name"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_company_name' ) : 'CÔNG TY CỔ PHẦN QP HOLDINGS' ); ?></p>
								<p class="footer-company-detail"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_head_office_label' ) : 'Trụ sở chính:' ); ?> <?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_head_office_address' ) : 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Thành phố Hồ Chí Minh, Việt Nam' ); ?></p>
								<p class="footer-company-detail">Tel: (028) 3528 2828</p>
								<p class="footer-company-detail"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_binh_duong_label' ) : 'Văn phòng Bình Dương:' ); ?> <?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_binh_duong_address' ) : 'Nhà phố F1, Đường Huỳnh Thúc Kháng, Phường Bình Dương, Thành phố Hồ Chí Minh, Việt Nam' ); ?></p>
								<p class="footer-company-detail">Tel: (027) 4380 3586</p>
								<p class="footer-company-detail">Tax Code: 5400257584</p>
								<p class="footer-company-detail">Email: info@qpholdings.vn</p>
								<p class="footer-company-detail">Website: qpholdings.vn</p>
								<p class="footer-company-detail">MÃ CK: HKT</p>
							</div>
						</div>

						<div class="footer-links-col">
							<h4><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_company_name' ) : 'CÔNG TY CỔ PHẦN QP HOLDINGS' ); ?></h4>
							<div class="footer-offices">
								<div>
									<p><strong><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_head_office_label' ) : 'Trụ sở chính:' ); ?></strong> <?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_head_office_address' ) : 'Tầng L16, Tòa nhà Vietcombank, Số 5 Công Trường Mê Linh, Phường Sài Gòn, Thành phố Hồ Chí Minh, Việt Nam' ); ?></p>
									<p>Tel: (028) 3528 2828</p>
								</div>
								<div>
									<p><strong><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_binh_duong_label' ) : 'Văn phòng Bình Dương:' ); ?></strong> <?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'footer_binh_duong_address' ) : 'Nhà phố F1, Đường Huỳnh Thúc Kháng, Phường Bình Dương, Thành phố Hồ Chí Minh, Việt Nam' ); ?></p>
									<p>Tel: (027) 4380 3586</p>
								</div>
								<div class="footer-meta">
									<p>Tax Code: 5400257584</p>
									<p>Email: info@qpholdings.vn</p>
									<p>Website: qpholdings.vn</p>
									<p>
										<?php
										$stock_code_text = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'stock_code' ) : 'MÃ CK: HKT';
										$parts           = preg_split( '/(:|：)/u', $stock_code_text, 2 );
										if ( count( $parts ) === 2 ) {
											echo '<strong>' . esc_html( trim( $parts[0] ) ) . ':</strong> <span class="footer-stock-ticker">' . esc_html( trim( $parts[1] ) ) . '</span>';
										} else {
											echo esc_html( $stock_code_text );
										}
										?>
									</p>
								</div>
							</div>
						</div>

						<div class="footer-social-col">
							<a href="#" target="_blank" aria-label="Facebook">
								<svg width="10" height="20" viewBox="0 0 10 20" fill="none"
									xmlns="http://www.w3.org/2000/svg">
									<path
										d="M8.11163 3.29509H9.92331V0.139742C9.61075 0.0967442 8.53581 0 7.28393 0C4.67183 0 2.88248 1.643 2.88248 4.66274V7.44186H0V10.9693H2.88248V19.845H6.41654V10.9701H9.18243L9.6215 7.44269H6.41571V5.01251C6.41654 3.99297 6.69106 3.29509 8.11163 3.29509Z"
										fill="#C9A96E" />
								</svg>
							</a>
							<a href="#" target="_blank" aria-label="Twitter">
								<svg width="22" height="18" viewBox="0 0 22 18" fill="none"
									xmlns="http://www.w3.org/2000/svg">
									<path
										d="M22 2.11613C21.1819 2.475 20.3101 2.71288 19.4013 2.82838C20.3363 2.27013 21.0499 1.39287 21.3854 0.3355C20.5136 0.85525 19.5511 1.22238 18.5254 1.42725C17.6976 0.545875 16.5179 0 15.2309 0C12.7339 0 10.7236 2.02675 10.7236 4.51137C10.7236 4.86888 10.7539 5.21263 10.8281 5.53988C7.0785 5.357 3.76062 3.55988 1.53175 0.82225C1.14262 1.49738 0.914375 2.27012 0.914375 3.102C0.914375 4.664 1.71875 6.04862 2.91775 6.85025C2.19312 6.8365 1.48225 6.62613 0.88 6.29475C0.88 6.3085 0.88 6.32638 0.88 6.34425C0.88 8.536 2.44338 10.3565 4.4935 10.7759C4.12638 10.8763 3.72625 10.9244 3.311 10.9244C3.02225 10.9244 2.73075 10.9079 2.45712 10.8474C3.0415 12.6335 4.69975 13.9466 6.6715 13.9893C5.137 15.1896 3.18863 15.9129 1.07938 15.9129C0.7095 15.9129 0.35475 15.8964 0 15.851C1.99787 17.1394 4.36563 17.875 6.919 17.875C15.2185 17.875 19.756 11 19.756 5.04075C19.756 4.84137 19.7491 4.64887 19.7395 4.45775C20.6346 3.8225 21.3867 3.02913 22 2.11613Z"
										fill="#C9A96E" />
								</svg>
							</a>
							<a href="#" target="_blank" aria-label="Instagram">
								<svg width="22" height="22" viewBox="0 0 22 22" fill="none"
									xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd"
										d="M6.875 0H15.125C18.9214 0 22 3.07862 22 6.875V15.125C22 18.9214 18.9214 22 15.125 22H6.875C3.07862 22 0 18.9214 0 15.125V6.875C0 3.07862 3.07862 0 6.875 0ZM15.125 19.9375C17.7787 19.9375 19.9375 17.7787 19.9375 15.125V6.875C19.9375 4.22125 17.7787 2.0625 15.125 2.0625H6.875C4.22125 2.0625 2.0625 4.22125 2.0625 6.875V15.125C2.0625 17.7787 4.22125 19.9375 6.875 19.9375H15.125Z"
										fill="#C9A96E" />
									<path fill-rule="evenodd" clip-rule="evenodd"
										d="M5.5 11C5.5 7.96263 7.96263 5.5 11 5.5C14.0374 5.5 16.5 7.96263 16.5 11C16.5 14.0374 14.0374 16.5 11 16.5C7.96263 16.5 5.5 14.0374 5.5 11ZM7.5625 11C7.5625 12.8948 9.10525 14.4375 11 14.4375C12.8948 14.4375 14.4375 12.8948 14.4375 11C14.4375 9.10388 12.8948 7.5625 11 7.5625C9.10525 7.5625 7.5625 9.10388 7.5625 11Z"
										fill="#C9A96E" />
									<circle cx="16.9124" cy="5.08737" r="0.732875" fill="#C9A96E" />
								</svg>
							</a>
						</div>
					</div>

					<hr class="footer-divider" />

					<div class="footer-bottom">
						<p class="footer-copyright">Copyright @ 2026 QP GROUP. All Rights Reserved</p>
						<div class="footer-bottom-links">
							<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'terms' ) : home_url( '/dieu-khoan-su-dung/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'terms' ) : 'Điều khoản' ); ?></a>
							<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'privacy' ) : home_url( '/chinh-sach-bao-mat/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'privacy' ) : 'Chính sách bảo mật' ); ?></a>
						</div>
					</div>
				</div>
			</section>
		</footer>
<div id="main-menu" class="mobile-sidebar no-scrollbar mfp-hide">


		<div class="sidebar-menu no-scrollbar ">


			<ul class="nav nav-sidebar nav-vertical nav-uppercase" data-tab="1">
				<li class="header-search-form search-form html relative has-icon">
					<div class="header-search-form-wrapper">
						<div class="searchform-wrapper ux-search-box relative form-flat is-normal">
							<form method="get" class="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>"
								role="search">
								<div class="flex-row relative">
									<div class="flex-col flex-grow">
									<input type="search" class="search-field mb-0" name="s" value="" id="s"
										placeholder="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'search_placeholder' ) : 'Tìm kiếm…' ); ?>" autocomplete="off">
									</div>
									<div class="flex-col">
										<button type="submit"
									class="ux-search-submit submit-button secondary button icon mb-0"
										aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'search_submit' ) : 'Gửi' ); ?>">
											<i class="icon-search" aria-hidden="true"></i> </button>
									</div>
								</div>
								<div class="live-search-results text-left z-top">
									<div class="autocomplete-suggestions"
										style="position: absolute; display: none; max-height: 300px; z-index: 9999;">
									</div>
								</div>
							</form>
						</div>
					</div>
				</li>
			<li
				class="menu-item menu-item-type-post_type menu-item-object-page menu-item-home current-menu-item page_item page-item-1810 current_page_item menu-item-1942">
				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'home' ) : home_url( '/' ) ); ?>" aria-current="page"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang Chủ' ); ?></a>
			</li>
			<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-2072"><a
					href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'about' ) : home_url( '/gioi-thieu/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'about' ) : 'Về QP Holdings' ); ?></a></li>
			<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-2073"><a
					href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'projects' ) : home_url( '/du-an/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'projects' ) : 'Dự Án' ); ?></a></li>
			<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-2074"><a
					href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'investor' ) : home_url( '/nha-dau-tu/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'investor' ) : 'Quan Hệ Nhà Đầu Tư' ); ?></a></li>
			<li class="menu-item menu-item-type-custom menu-item-object-custom menu-item-2075"><a
					href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'news' ) : 'Tin tức' ); ?></a></li>
			</ul>


		</div>


	</div>
	<script type="speculationrules">
{"prefetch":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/wp-*.php","/wp-admin/*","/wp-content/uploads/*","/wp-content/*","/wp-content/plugins/*","/wp-content/themes/phongnv-child/*","/wp-content/themes/flatsome/*","/*\\?(.+)"]}},{"not":{"selector_matches":"a[rel~=\"nofollow\"]"}},{"not":{"selector_matches":".no-prefetch, .no-prefetch a"}}]},"eagerness":"conservative"}]}
</script>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {

			// 1. Hàm lấy tham số URL (?code=...)
			function getUrlParameter(name) {
				name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
				var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
				var results = regex.exec(location.search);
				return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
			}

			var codeParam = getUrlParameter('code');

			console.log('=====> cenly_insert_ajax_script: ', codeParam)

			// 2. Nếu có param 'code', thực hiện AJAX
			if (codeParam && codeParam !== '') {

				// console.log('Cenly: Detected code ' + codeParam + ', calling API...');

				$.ajax({
					url: '<?php echo esc_url( home_url( '/' ) ); ?>wp-admin/admin-ajax.php',
					type: 'POST',
					data: {
						action: 'cenly_check_api', // Phải khớp với tên action ở Phần 1
						code: codeParam
					},
					success: function (response) {
						if (response.success) {
							var data = response.data;

							// Giữ nguyên delay 3s theo yêu cầu cũ (nếu muốn hiển thị ngay thì xóa setTimeout)
							setTimeout(function () {

								// Cập nhật Serial
								var elsSerial = $('.data_serial_number');
								if (elsSerial.length > 0) {
									elsSerial.html('<span>' + data.serial + '</span>');
								}

								// Cập nhật Code
								var elsCode = $('.data_code');
								if (elsCode.length > 0) {
									elsCode.html('<span>' + data.code + '</span>');
								}

								// console.log('Cenly: DOM Updated via AJAX');

							}, 3000);
						} else {
							console.log('Cenly Error:', response.data.message);
						}
					},
					error: function (xhr, status, error) {
						console.log('Cenly AJAX Error:', error);
					}
				});
			}
		});
	</script>
	<div id="fb-root" class=" fb_reset">
		<div style="position: absolute; top: -10000px; width: 0px; height: 0px;">
			<div></div>
		</div>
	</div>
<script type="speculationrules">
{"prefetch":[{"source":"document","where":{"and":[{"href_matches":"/*"},{"not":{"href_matches":["/wp-*.php","/wp-admin/*","/wp-content/uploads/*","/wp-content/*","/wp-content/plugins/*","/wp-content/themes/phongnv-child/*","/wp-content/themes/flatsome/*","/*\\?(.+)"]}},{"not":{"selector_matches":"a[rel~=\"nofollow\"]"}},{"not":{"selector_matches":".no-prefetch, .no-prefetch a"}}]},"eagerness":"conservative"}]}
</script>
</div>
<?php
/** Custom footer-area injection slot (see inc/custom-code.php). */
do_action( 'qp_holdings_custom_footer' );
?>
<?php wp_footer(); ?>
</body>
</html>
