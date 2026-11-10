<?php defined( 'ABSPATH' ) || exit; ?>
<?php get_header(); ?>
<?php $qp_assets = get_template_directory_uri() . '/assets/site'; ?>

<main>

	<!-- ═══ HERO ═══ -->
	<section class="hero" id="hero" aria-label="<?php echo esc_attr( qp_holdings_t( 'Trang chủ' ) ); ?>">
		<div class="hero-bg" aria-hidden="true">
			<?php echo qp_holdings_hero_picture( 'home', 'assets/site/hero-bg-1b89f1.png', array( 'alt' => '', 'fetchpriority' => 'high', 'decoding' => 'sync' ), '100vw' ); ?>
			<div class="hero-overlay"></div>
		</div>

		<div class="container hero-container">
			<div class="hero-content">
				<div class="hero-left reveal">
					<div class="hero-text-block">
						<h1 class="hero-title"<?php qp_holdings_te_attrs( 'home_hero_title_html', array( 'html' => true ) ); ?>><?php echo qp_holdings_t( 'home_hero_title_html' ); ?></h1>
						<p class="hero-body"><?php qp_holdings_te( 'home_hero_desc' ); ?></p>
					</div>
					<div class="hero-buttons">
						<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'projects' ) : home_url( '/du-an/' ) ); ?>" class="btn btn-primary-gold">
							<span><?php echo esc_html( qp_holdings_t( 'home_cta_projects' ) ); ?></span>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
						<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'about' ) : home_url( '/gioi-thieu/' ) ); ?>" class="btn btn-primary-white">
							<span><?php echo esc_html( qp_holdings_t( 'home_cta_about' ) ); ?></span>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					</div>
				</div>

				<div class="hero-right reveal reveal-delay-1">
					<div class="stats-card-wrap">
						<div class="stats-card">
							<div class="stat-item">
								<div class="stat-label"<?php qp_holdings_te_attrs( 'home_stat_equity' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_equity' ) ); ?></div>
								<div class="stat-value gold-gradient-text"<?php qp_holdings_te_attrs( 'home_stat_equity_value' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_equity_value' ) ); ?></div>
								<div class="stat-unit"<?php qp_holdings_te_attrs( 'home_stat_equity_sub' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_equity_sub' ) ); ?></div>
							</div>
							<div class="stat-divider" aria-hidden="true"></div>
							<div class="stat-item">
								<div class="stat-label"<?php qp_holdings_te_attrs( 'home_stat_projects' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_projects' ) ); ?></div>
								<div class="stat-value gold-gradient-text"<?php qp_holdings_te_attrs( 'home_stat_projects_value' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_projects_value' ) ); ?></div>
								<div class="stat-unit"<?php qp_holdings_te_attrs( 'home_stat_projects_sub' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_projects_sub' ) ); ?></div>
							</div>
							<div class="stat-divider" aria-hidden="true"></div>
							<div class="stat-item">
								<div class="stat-label"<?php qp_holdings_te_attrs( 'home_stat_partners' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_partners' ) ); ?></div>
								<div class="stat-value gold-gradient-text"<?php qp_holdings_te_attrs( 'home_stat_partners_value' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_partners_value' ) ); ?></div>
								<div class="stat-unit"<?php qp_holdings_te_attrs( 'home_stat_partners_sub' ); ?>><?php echo esc_html( qp_holdings_t( 'home_stat_partners_sub' ) ); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Hero key bar -->
			<div class="hero-bar reveal reveal-delay-2" role="list" aria-label="<?php echo esc_attr( qp_holdings_t( 'Lĩnh vực trọng tâm' ) ); ?>">
				<div class="hero-bar-item" role="listitem">
					<div class="hero-bar-icon" aria-hidden="true">
						<svg width="70" height="70" viewBox="0 0 70 70" fill="none" xmlns="http://www.w3.org/2000/svg">
							<rect x="0.25" y="0.25" width="69.5" height="69.5" rx="9.75" fill="#193660" fill-opacity="0.4" />
							<rect x="0.25" y="0.25" width="69.5" height="69.5" rx="9.75" stroke="url(#paint0_linear_2110_8751)" stroke-width="0.5" />
							<path
								d="M46.7876 17H50.292C51.2597 17 52.0442 17.7845 52.0442 18.7522V22.2566M50.292 18.7522C36.3768 29.9512 29.1641 32.9678 17 34.5221M45.0354 31.0177V48.5398C45.0354 50.4752 46.6044 52.0442 48.5398 52.0442C50.4752 52.0442 52.0442 50.4752 52.0442 48.5398V31.0177C52.0442 29.0822 50.4752 27.5133 48.5398 27.5133C46.6044 27.5133 45.0354 29.0822 45.0354 31.0177ZM17 45.0354L17 48.5398C17 50.4752 18.569 52.0442 20.5044 52.0442C22.4399 52.0442 24.0088 50.4752 24.0088 48.5398L24.0088 45.0354C24.0088 43.0999 22.4399 41.531 20.5044 41.531C18.569 41.531 17 43.0999 17 45.0354ZM31.0177 38.0265V48.5398C31.0177 50.4752 32.5867 52.0442 34.5221 52.0442C36.4575 52.0442 38.0265 50.4752 38.0265 48.5398V38.0265C38.0265 36.0911 36.4575 34.5221 34.5221 34.5221C32.5867 34.5221 31.0177 36.0911 31.0177 38.0265Z"
								stroke="url(#paint1_linear_2110_8751)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							<defs>
								<linearGradient id="paint0_linear_2110_8751" x1="-2.46672" y1="32.6155" x2="70.1644" y2="32.3414" gradientUnits="userSpaceOnUse">
									<stop stop-color="#D3A049" />
									<stop offset="1" stop-color="#FFDDA4" />
								</linearGradient>
								<linearGradient id="paint1_linear_2110_8751" x1="15.7651" y1="33.3284" x2="52.1265" y2="33.1911" gradientUnits="userSpaceOnUse">
									<stop stop-color="#D3A049" />
									<stop offset="1" stop-color="#FFDDA4" />
								</linearGradient>
							</defs>
						</svg>
					</div>
				<div class="hero-bar-text">
					<h2<?php qp_holdings_te_attrs( 'Bất động sản Dân Dụng' ); ?>><?php echo esc_html( qp_holdings_t( 'Bất động sản Dân Dụng' ) ); ?></h2>
					<p><?php qp_holdings_te( 'Đầu tư và phát triển các khu dân cư, căn hộ cao tầng, văn phòng và trung tâm thương mại hiện đại, kiến tạo không gian sống và làm việc chất lượng, bền vững.' ); ?></p>
				</div>
				</div>

				<div class="hero-bar-divider" aria-hidden="true"></div>

				<div class="hero-bar-item" role="listitem">
					<div class="hero-bar-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="52" height="46" viewBox="0 0 52 46" fill="none">
							<path
								d="M14.529 20.4356H7.39551C6.01789 20.4356 5.32908 20.4356 4.80289 20.7003C4.34005 20.9333 3.96375 21.3049 3.72792 21.7621C3.45981 22.2817 3.45981 22.9621 3.45981 24.3227V44.73M36.6673 20.4356H43.8007C45.1784 20.4356 45.8672 20.4356 46.3934 20.7003C46.8562 20.9333 47.2325 21.3049 47.4683 21.7621C47.7364 22.2817 47.7364 22.9621 47.7364 24.3227V44.73M36.6673 44.73V8.77422C36.6673 6.05299 36.6673 4.69237 36.1311 3.653C35.6594 2.73874 34.9068 1.99543 33.9811 1.52959C32.9288 1 31.5511 1 28.7959 1H22.4004C19.6451 1 18.2675 1 17.2151 1.52959C16.2894 1.99543 15.5368 2.73874 15.0652 3.653C14.529 4.69237 14.529 6.05299 14.529 8.77422V44.73M50.1962 44.73H1M23.1383 10.7178H28.0579M23.1383 20.4356H28.0579M23.1383 30.1533H28.0579"
								stroke="url(#paint0_linear_2110_8759)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							<defs>
								<linearGradient id="paint0_linear_2110_8759" x1="-0.733619" y1="21.3754" x2="50.3116" y2="21.1587" gradientUnits="userSpaceOnUse">
									<stop stop-color="#D3A049" />
									<stop offset="1" stop-color="#FFDDA4" />
								</linearGradient>
							</defs>
						</svg>
					</div>
				<div class="hero-bar-text">
					<h2<?php qp_holdings_te_attrs( 'Bất động sản Công Nghiệp' ); ?>><?php echo esc_html( qp_holdings_t( 'Bất động sản Công Nghiệp' ) ); ?></h2>
					<p><?php qp_holdings_te( 'Đầu tư và phát triển khu công nghiệp, cụm công nghiệp và hạ tầng logistics đồng bộ, đáp ứng nhu cầu sản xuất, kinh doanh và thu hút đầu tư.' ); ?></p>
				</div>
				</div>

				<div class="hero-bar-divider" aria-hidden="true"></div>

				<div class="hero-bar-item" role="listitem">
					<div class="hero-bar-icon" aria-hidden="true">
						<svg xmlns="http://www.w3.org/2000/svg" width="56" height="46" viewBox="0 0 56 46" fill="none">
							<mask id="path-1-inside-1_2110_8766" fill="white">
								<path
									d="M41.7888 0L37.0634 4.73636C36.8264 4.70311 36.5895 4.67935 36.3525 4.6651C34.6605 3.97626 31.6461 3.4917 28.1151 6.21855L28.0345 6.28981L26.8734 7.45847C22.5082 4.48933 19.0388 6.31832 18.0672 6.9644L13.7163 2.60334L0 16.3469L5.19931 21.5583C5.24196 23.8908 6.06665 26.2852 7.6781 28.708C8.34164 29.7056 9.11419 30.6225 9.95309 31.4728C11.0242 32.5607 12.1096 33.6343 13.1902 34.7175C12.0195 36.4277 12.5219 37.8386 13.74 39.1118C15.0055 40.4325 16.1714 40.5845 17.8871 40.0904C17.9014 40.0904 17.9203 40.0904 17.9298 40.1047C19.1573 41.9527 20.2948 42.8458 21.5176 42.9456C22.0248 43.4396 22.5272 43.9384 23.039 44.4325C24.551 45.8957 26.8591 46.2235 28.6839 45.1736C29.4706 44.7223 29.6081 44.347 30.0109 43.7057C33.0964 46.2995 35.1581 46.347 37.58 42.708C37.5895 42.6938 37.6037 42.689 37.6227 42.6938C39.3384 43.1926 40.5043 43.0358 41.7698 41.7151C43.059 40.3707 43.5471 38.8695 42.1063 37.0263C42.0968 37.012 41.7224 36.5275 41.7414 36.5275C42.7699 36.5607 43.5803 35.9051 44.0306 35.3256C45.225 33.7769 45.1491 32.1522 43.8694 30.556C44.4287 29.9906 44.9975 29.4348 45.552 28.8695C46.3909 28.0191 47.1634 27.0975 47.827 26.1046C49.4384 23.6866 50.2631 21.2923 50.3058 18.955L55.5003 13.7435L41.7888 0ZM10.7825 29.4776C9.20424 27.8101 8.02409 25.9621 7.50748 23.8718C7.39373 23.4063 7.30841 22.9265 7.26576 22.4372C7.22784 22.0381 7.21362 21.6248 7.22784 21.2067L1.95744 15.9241L13.285 4.56534L16.9155 8.20431L17.1525 8.44659L17.1383 8.4846L18.8919 8.63187L19.0057 8.34683C19.6976 7.87177 22.5698 6.27556 26.4089 9.20669L31.485 14.3184C31.7409 14.5749 31.8783 14.9169 31.8783 15.2827C31.8783 15.6485 31.7361 15.9906 31.4802 16.2471L31.1437 16.5844C30.6129 17.1165 29.7503 17.1117 29.2194 16.5844L24.0296 11.3587L22.7689 12.2423L24.1718 13.8908L24.7785 14.5036C24.1813 15.1022 23.5841 15.7008 22.9869 16.2994C22.84 16.4466 22.7547 16.6082 22.712 16.7744C22.6836 16.8029 22.6551 16.8362 22.622 16.8647C19.1336 20.3659 15.6311 23.8576 12.1523 27.3683C11.4555 28.0714 10.9958 28.7792 10.773 29.4871L10.7825 29.4776ZM27.6127 43.6486C27.2099 44.0287 26.7975 44.2662 26.1956 44.2187C25.3662 44.1522 24.7927 43.5204 24.3898 43.1213C24.1434 42.879 23.9016 42.632 23.6552 42.3897C24.2287 42.0809 24.8353 41.6486 25.4941 41.0976C25.897 41.7389 26.0297 42.1142 26.8212 42.5655C27.1767 42.7698 27.5464 42.917 27.9303 43.0216C27.8639 43.2781 27.7596 43.5061 27.6127 43.6439V43.6486ZM35.0918 42.9456C34.3145 43.8624 33.4234 43.9432 32.2859 42.8838C32.1106 42.7175 31.9352 42.5513 31.7598 42.385C32.011 42.2235 32.248 42.0382 32.466 41.8244C33.1201 41.1926 33.7599 40.556 34.4093 39.9242C34.4756 39.9954 34.5467 40.0619 34.6178 40.1332C35.6889 41.2068 35.8311 42.0762 35.0918 42.9456ZM40.5138 40.1047C39.7271 41.1831 38.5232 41.2068 37.4473 40.1284C36.9118 39.5964 36.3809 39.0548 35.8454 38.5227C36.7269 37.6581 37.6132 36.7983 38.49 35.9337C38.9877 36.4325 39.4853 36.9265 39.983 37.4253C40.9404 38.3897 41.1015 39.2971 40.5138 40.1047ZM42.7082 33.7341C42.3101 34.7365 41.1205 35.2116 40.1773 34.689C40.0825 34.6367 39.9972 34.5797 39.9119 34.518C40.7603 33.6724 41.6039 32.822 42.4476 31.9764C42.8125 32.4609 43.0021 33.0025 42.7082 33.7341ZM48.2725 18.6034C48.2867 19.0215 48.2725 19.43 48.2346 19.8338C48.1872 20.3231 48.1066 20.8029 47.9929 21.2685C47.4715 23.3683 46.2819 25.221 44.6941 26.8932C42.5424 29.164 40.2863 31.3351 38.073 33.5488C35.7553 35.8719 33.4424 38.2092 31.1058 40.5132C30.7029 40.9075 30.1294 41.5441 29.3 41.6106C28.6981 41.6581 28.2857 41.4254 27.8876 41.0406C27.5274 40.6985 27.4089 39.8339 27.5511 39.2971C27.7028 38.7128 28.2194 38.1854 28.6696 37.7246C29.7266 36.6415 30.8025 35.5726 31.8736 34.499C32.395 33.9764 32.9116 33.4491 33.4234 32.9218C33.9685 32.3564 35.561 31.164 34.6699 30.3279C33.85 29.5583 32.6746 31.0405 32.1817 31.5393C31.6603 32.0619 31.139 32.5845 30.6129 33.1023C28.1578 35.5061 25.7359 37.9432 23.2239 40.2805C22.0864 41.3398 21.1953 41.2591 20.4181 40.3422C19.6787 39.4729 19.8209 38.6035 20.892 37.5299C23.6741 34.7365 26.4563 31.9479 29.2384 29.1593C29.627 28.7697 29.9683 28.5132 30.0394 27.9146C30.0915 27.4728 29.9304 27.0833 29.4754 27.012C28.7076 26.898 28.3758 27.1878 27.8829 27.6818C24.6078 30.9645 21.3375 34.2519 18.0578 37.5251C16.9819 38.5987 15.778 38.575 14.9913 37.5014C14.4035 36.6985 14.5647 35.7864 15.5221 34.822C18.8445 31.4776 22.1717 28.1426 25.5084 24.8125C26.0534 24.2709 26.48 23.6628 25.8117 23.0357C25.1766 22.4372 24.6268 22.9692 24.1386 23.4538C21.5793 26.0286 19.0151 28.6034 16.4416 31.164C16.1003 31.5061 15.7401 31.8529 15.323 32.0857C14.3799 32.6035 13.1902 32.1332 12.7921 31.1308C12.3798 30.1046 12.9106 29.4538 13.5599 28.803C17.816 24.5512 22.0722 20.2994 26.2951 16.0096L28.0393 17.7673C28.6317 18.3659 29.4138 18.6652 30.1958 18.6652C30.9731 18.6652 31.7504 18.3706 32.3428 17.7768L32.6793 17.4443C33.2528 16.8694 33.5704 16.1046 33.5751 15.2922C33.5751 14.5274 33.2907 13.81 32.7836 13.2495L28.9161 9.33496L28.1578 8.57011L29.191 7.52972C30.7882 6.30882 32.2148 5.88126 33.3903 5.82425C35.542 5.62948 37.4473 6.75537 37.4473 6.75537L38.5896 5.61048L42.2201 1.96675L53.5524 13.3255L48.282 18.6082L48.2725 18.6034Z" />
							</mask>
							<path
								d="M41.7888 0L37.0634 4.73636C36.8264 4.70311 36.5895 4.67935 36.3525 4.6651C34.6605 3.97626 31.6461 3.4917 28.1151 6.21855L28.0345 6.28981L26.8734 7.45847C22.5082 4.48933 19.0388 6.31832 18.0672 6.9644L13.7163 2.60334L0 16.3469L5.19931 21.5583C5.24196 23.8908 6.06665 26.2852 7.6781 28.708C8.34164 29.7056 9.11419 30.6225 9.95309 31.4728C11.0242 32.5607 12.1096 33.6343 13.1902 34.7175C12.0195 36.4277 12.5219 37.8386 13.74 39.1118C15.0055 40.4325 16.1714 40.5845 17.8871 40.0904C17.9014 40.0904 17.9203 40.0904 17.9298 40.1047C19.1573 41.9527 20.2948 42.8458 21.5176 42.9456C22.0248 43.4396 22.5272 43.9384 23.039 44.4325C24.551 45.8957 26.8591 46.2235 28.6839 45.1736C29.4706 44.7223 29.6081 44.347 30.0109 43.7057C33.0964 46.2995 35.1581 46.347 37.58 42.708C37.5895 42.6938 37.6037 42.689 37.6227 42.6938C39.3384 43.1926 40.5043 43.0358 41.7698 41.7151C43.059 40.3707 43.5471 38.8695 42.1063 37.0263C42.0968 37.012 41.7224 36.5275 41.7414 36.5275C42.7699 36.5607 43.5803 35.9051 44.0306 35.3256C45.225 33.7769 45.1491 32.1522 43.8694 30.556C44.4287 29.9906 44.9975 29.4348 45.552 28.8695C46.3909 28.0191 47.1634 27.0975 47.827 26.1046C49.4384 23.6866 50.2631 21.2923 50.3058 18.955L55.5003 13.7435L41.7888 0ZM10.7825 29.4776C9.20424 27.8101 8.02409 25.9621 7.50748 23.8718C7.39373 23.4063 7.30841 22.9265 7.26576 22.4372C7.22784 22.0381 7.21362 21.6248 7.22784 21.2067L1.95744 15.9241L13.285 4.56534L16.9155 8.20431L17.1525 8.44659L17.1383 8.4846L18.8919 8.63187L19.0057 8.34683C19.6976 7.87177 22.5698 6.27556 26.4089 9.20669L31.485 14.3184C31.7409 14.5749 31.8783 14.9169 31.8783 15.2827C31.8783 15.6485 31.7361 15.9906 31.4802 16.2471L31.1437 16.5844C30.6129 17.1165 29.7503 17.1117 29.2194 16.5844L24.0296 11.3587L22.7689 12.2423L24.1718 13.8908L24.7785 14.5036C24.1813 15.1022 23.5841 15.7008 22.9869 16.2994C22.84 16.4466 22.7547 16.6082 22.712 16.7744C22.6836 16.8029 22.6551 16.8362 22.622 16.8647C19.1336 20.3659 15.6311 23.8576 12.1523 27.3683C11.4555 28.0714 10.9958 28.7792 10.773 29.4871L10.7825 29.4776ZM27.6127 43.6486C27.2099 44.0287 26.7975 44.2662 26.1956 44.2187C25.3662 44.1522 24.7927 43.5204 24.3898 43.1213C24.1434 42.879 23.9016 42.632 23.6552 42.3897C24.2287 42.0809 24.8353 41.6486 25.4941 41.0976C25.897 41.7389 26.0297 42.1142 26.8212 42.5655C27.1767 42.7698 27.5464 42.917 27.9303 43.0216C27.8639 43.2781 27.7596 43.5061 27.6127 43.6439V43.6486ZM35.0918 42.9456C34.3145 43.8624 33.4234 43.9432 32.2859 42.8838C32.1106 42.7175 31.9352 42.5513 31.7598 42.385C32.011 42.2235 32.248 42.0382 32.466 41.8244C33.1201 41.1926 33.7599 40.556 34.4093 39.9242C34.4756 39.9954 34.5467 40.0619 34.6178 40.1332C35.6889 41.2068 35.8311 42.0762 35.0918 42.9456ZM40.5138 40.1047C39.7271 41.1831 38.5232 41.2068 37.4473 40.1284C36.9118 39.5964 36.3809 39.0548 35.8454 38.5227C36.7269 37.6581 37.6132 36.7983 38.49 35.9337C38.9877 36.4325 39.4853 36.9265 39.983 37.4253C40.9404 38.3897 41.1015 39.2971 40.5138 40.1047ZM42.7082 33.7341C42.3101 34.7365 41.1205 35.2116 40.1773 34.689C40.0825 34.6367 39.9972 34.5797 39.9119 34.518C40.7603 33.6724 41.6039 32.822 42.4476 31.9764C42.8125 32.4609 43.0021 33.0025 42.7082 33.7341ZM48.2725 18.6034C48.2867 19.0215 48.2725 19.43 48.2346 19.8338C48.1872 20.3231 48.1066 20.8029 47.9929 21.2685C47.4715 23.3683 46.2819 25.221 44.6941 26.8932C42.5424 29.164 40.2863 31.3351 38.073 33.5488C35.7553 35.8719 33.4424 38.2092 31.1058 40.5132C30.7029 40.9075 30.1294 41.5441 29.3 41.6106C28.6981 41.6581 28.2857 41.4254 27.8876 41.0406C27.5274 40.6985 27.4089 39.8339 27.5511 39.2971C27.7028 38.7128 28.2194 38.1854 28.6696 37.7246C29.7266 36.6415 30.8025 35.5726 31.8736 34.499C32.395 33.9764 32.9116 33.4491 33.4234 32.9218C33.9685 32.3564 35.561 31.164 34.6699 30.3279C33.85 29.5583 32.6746 31.0405 32.1817 31.5393C31.6603 32.0619 31.139 32.5845 30.6129 33.1023C28.1578 35.5061 25.7359 37.9432 23.2239 40.2805C22.0864 41.3398 21.1953 41.2591 20.4181 40.3422C19.6787 39.4729 19.8209 38.6035 20.892 37.5299C23.6741 34.7365 26.4563 31.9479 29.2384 29.1593C29.627 28.7697 29.9683 28.5132 30.0394 27.9146C30.0915 27.4728 29.9304 27.0833 29.4754 27.012C28.7076 26.898 28.3758 27.1878 27.8829 27.6818C24.6078 30.9645 21.3375 34.2519 18.0578 37.5251C16.9819 38.5987 15.778 38.575 14.9913 37.5014C14.4035 36.6985 14.5647 35.7864 15.5221 34.822C18.8445 31.4776 22.1717 28.1426 25.5084 24.8125C26.0534 24.2709 26.48 23.6628 25.8117 23.0357C25.1766 22.4372 24.6268 22.9692 24.1386 23.4538C21.5793 26.0286 19.0151 28.6034 16.4416 31.164C16.1003 31.5061 15.7401 31.8529 15.323 32.0857C14.3799 32.6035 13.1902 32.1332 12.7921 31.1308C12.3798 30.1046 12.9106 29.4538 13.5599 28.803C17.816 24.5512 22.0722 20.2994 26.2951 16.0096L28.0393 17.7673C28.6317 18.3659 29.4138 18.6652 30.1958 18.6652C30.9731 18.6652 31.7504 18.3706 32.3428 17.7768L32.6793 17.4443C33.2528 16.8694 33.5704 16.1046 33.5751 15.2922C33.5751 14.5274 33.2907 13.81 32.7836 13.2495L28.9161 9.33496L28.1578 8.57011L29.191 7.52972C30.7882 6.30882 32.2148 5.88126 33.3903 5.82425C35.542 5.62948 37.4473 6.75537 37.4473 6.75537L38.5896 5.61048L42.2201 1.96675L53.5524 13.3255L48.282 18.6082L48.2725 18.6034Z"
								stroke="url(#paint0_linear_2110_8766)" stroke-width="2" mask="url(#path-1-inside-1_2110_8766)" />
							<defs>
								<linearGradient id="paint0_linear_2110_8766" x1="-1.95577" y1="21.3342" x2="55.6303" y2="21.0708" gradientUnits="userSpaceOnUse">
									<stop stop-color="#D3A049" />
									<stop offset="1" stop-color="#FFDDA4" />
								</linearGradient>
							</defs>
						</svg>
					</div>
				<div class="hero-bar-text">
					<h2<?php qp_holdings_te_attrs( 'Đầu tư Tài chính' ); ?>><?php echo esc_html( qp_holdings_t( 'Đầu tư Tài chính' ) ); ?></h2>
					<p><?php qp_holdings_te( 'Tối ưu nguồn vốn, quản trị danh mục đầu tư hiệu quả và khai thác các cơ hội tăng trưởng bền vững, gia tăng giá trị dài hạn.' ); ?></p>
				</div>
				</div>
			</div>
		</div>
	</section>

	<!-- ═══ ABOUT ═══ -->
	<section class="about" id="about" aria-label="<?php echo esc_attr( qp_holdings_t( 'Giới thiệu QP Holdings' ) ); ?>">
		<div class="container">
			<div class="about-grid">

				<div class="about-text">
					<div class="about-eyebrow reveal">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M16 10L18.1494 10.6448C19.5226 11.0568 20.2092 11.2628 20.6046 11.7942C21 12.3256 21 13.0425 21 14.4761V22" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
							<path d="M8 9L11 9M8 13L11 13" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
							<path d="M12 22V19C12 18.0572 12 17.5858 11.7071 17.2929C11.4142 17 10.9428 17 10 17H9C8.05719 17 7.58579 17 7.29289 17.2929C7 17.5858 7 18.0572 7 19V22" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
							<path d="M2 22L22 22" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
							<path d="M3 22V6.71724C3 4.20649 3 2.95111 3.79118 2.32824C4.58237 1.70537 5.74742 2.04355 8.07752 2.7199L13.0775 4.17122C14.4836 4.57937 15.1867 4.78344 15.5933 5.33965C16 5.89587 16 6.65344 16 8.16857V22" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
						<span<?php qp_holdings_te_attrs( 'CHÚNG TÔI LÀ QP HOLDINGS' ); ?>><?php echo esc_html( qp_holdings_t( 'CHÚNG TÔI LÀ QP HOLDINGS' ) ); ?></span>
					</div>
					<h2 class="section-title reveal reveal-delay-1">
						<span class="about-gold"<?php qp_holdings_te_attrs( 'QP Holdings' ); ?>><?php echo esc_html( qp_holdings_t( 'QP Holdings' ) ); ?></span> <span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'là đơn vị' ); ?>><?php echo esc_html( qp_holdings_t( 'là đơn vị' ) ); ?></span> <span class="about-gold"<?php qp_holdings_te_attrs( 'đầu tư và phát triển BĐS' ); ?>><?php echo esc_html( qp_holdings_t( 'đầu tư và phát triển BĐS' ) ); ?></span> <span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'hàng đầu tại Việt Nam' ); ?>><?php echo esc_html( qp_holdings_t( 'hàng đầu tại Việt Nam' ) ); ?></span>
					</h2>
					<p class="about-para reveal reveal-delay-2"><?php echo esc_html( qp_holdings_t( 'QP Holdings hoạt động trong các lĩnh vực trọng tâm gồm:' ) ); ?> <strong class="about-strong"<?php qp_holdings_te_attrs( 'Bất động sản Dân Dụng, Bất động sản Công Nghiệp, Đầu tư Tài chính.' ); ?>><?php echo esc_html( qp_holdings_t( 'Bất động sản Dân Dụng, Bất động sản Công Nghiệp, Đầu tư Tài chính.' ) ); ?></strong></p>
					<p class="about-para reveal reveal-delay-3"><?php qp_holdings_te( 'Với gần 20 năm kinh nghiệm, QP Holdings định hướng phát triển bền vững, kiến tạo giá trị lâu dài cho đối tác, khách hàng và cộng đồng thông qua các hoạt động đầu tư hiệu quả, minh bạch và trách nhiệm.' ); ?></p>

					<ul class="checklist reveal reveal-delay-4" aria-label="<?php echo esc_attr( qp_holdings_t( 'Cam kết của QP Holdings' ) ); ?>">
						<?php
						$qp_about_items = array(
							'Minh bạch trong quản trị và vận hành',
							'Nâng cao năng lực đầu tư và hiệu quả hoạt động',
							'Đồng hành phát triển cùng đối tác và khách hàng',
							'Hướng đến tăng trưởng dài hạn và bền vững',
							'Không ngừng đổi mới và nâng cao năng lực cạnh tranh',
						);
						foreach ( $qp_about_items as $item ) :
							?>
							<li>
								<span class="check-icon" aria-hidden="true">
									<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
										<circle cx="12" cy="12" r="10" fill="#293644" />
										<path d="M8 12.5L11 15.5L16 9.5" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									</svg>
								</span>
								<?php qp_holdings_te( $item ); ?>
							</li>
						<?php endforeach; ?>
					</ul>

				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'about' ) : home_url( '/gioi-thieu/' ) ); ?>" class="btn btn-outline-gold btn-lg reveal reveal-delay-5" aria-label="<?php echo esc_attr( qp_holdings_t( 'learn_more_about' ) ); ?>">
					<?php echo esc_html( qp_holdings_t( 'learn_more' ) ); ?>
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</a>
				</div>

				<div class="about-images reveal reveal-delay-1" role="img" aria-label="<?php echo esc_attr( qp_holdings_t( 'Hình ảnh QP Holdings' ) ); ?>">
					<div class="about-collage">
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-1-3ba564.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Khu cong nghiep va nha may' ), 'class' => 'about-img about-img-1', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 36vw, (max-width: 900px) 28vw, 16vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-2-62d568.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Nha xuong QP' ), 'class' => 'about-img about-img-2', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 28vw, (max-width: 900px) 22vw, 12vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-3-3f8b28.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Du an An Binh 7' ), 'class' => 'about-img about-img-3', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 24vw, (max-width: 900px) 18vw, 11vw' ); ?>
						<?php echo qp_holdings_theme_picture( 'assets/site/about-collage-4-afbd86.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Cong khu cong nghiep An Binh 7' ), 'class' => 'about-img about-img-4', 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 24vw, (max-width: 900px) 18vw, 11vw' ); ?>
						<div class="about-accent" aria-hidden="true"></div>
					</div>
					<svg style="position: absolute; top: 0; left: 0; transform: translate(-25%, -25%); pointer-events: none;" xmlns="http://www.w3.org/2000/svg" width="1304" height="892" viewBox="0 0 1304 892" fill="none">
						<g filter="url(#about_glow)">
							<path d="M1114 446.625C1114 750.395 912.714 609.256 675.19 609.256C437.666 609.256 250 737.12 250 433.35C250 129.581 457.751 294.91 695.276 294.91C932.8 294.91 1114 142.855 1114 446.625Z" fill="#F6D191" fill-opacity="0.5" />
						</g>
						<defs>
							<filter id="about_glow" x="0" y="0" width="1364" height="892" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
								<feFlood flood-opacity="0" result="BackgroundImageFix" />
								<feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
								<feGaussianBlur stdDeviation="125" result="effect1_foregroundBlur" />
							</filter>
						</defs>
					</svg>
				</div>

			</div>
		</div>
	</section>

	<!-- ═══ ACTIVITY / FIELDS ═══ -->
	<section class="activity" id="projects" aria-label="<?php echo esc_attr( qp_holdings_t( 'Lĩnh vực hoạt động' ) ); ?>">
		<div class="container">
			<div class="section-header center">
				<div class="activity-eyebrow">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M16 10L18.1494 10.6448C19.5226 11.0568 20.2092 11.2628 20.6046 11.7942C21 12.3256 21 13.0425 21 14.4761V22" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
						<path d="M8 9L11 9M8 13L11 13" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						<path d="M12 22V19C12 18.0572 12 17.5858 11.7071 17.2929C11.4142 17 10.9428 17 10 17H9C8.05719 17 7.58579 17 7.29289 17.2929C7 17.5858 7 18.0572 7 19V22" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
						<path d="M2 22L22 22" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
						<path d="M3 22V6.71724C3 4.20649 3 2.95111 3.79118 2.32824C4.58237 1.70537 5.74742 2.04355 8.07752 2.7199L13.0775 4.17122C14.4836 4.57937 15.1867 4.78344 15.5933 5.33965C16 5.89587 16 6.65344 16 8.16857V22" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<span<?php qp_holdings_te_attrs( 'GIỚI THIỆU CÔNG TY CỔ PHẦN QP HOLDINGS' ); ?>><?php echo esc_html( qp_holdings_t( 'GIỚI THIỆU CÔNG TY CỔ PHẦN QP HOLDINGS' ) ); ?></span>
				</div>
				<h2 class="section-title navy-gradient-text"<?php qp_holdings_te_attrs( 'Lĩnh vực hoạt động' ); ?>><?php echo esc_html( qp_holdings_t( 'Lĩnh vực hoạt động' ) ); ?></h2>
			</div>

			<div class="activity-swiper swiper">
				<div class="swiper-wrapper activity-grid">

					<article class="activity-card reveal swiper-slide">
						<div class="activity-card-img">
							<?php echo qp_holdings_theme_picture( 'assets/site/field-real-estate-2718-1908.jpg', array( 'alt' => qp_holdings_t( 'Dau tu - Phat trien Bat dong san' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
							<div class="activity-card-glow" aria-hidden="true"></div>
						</div>
						<div class="activity-card-body">
							<h3 class="activity-card-title"<?php qp_holdings_te_attrs( 'Bất động sản Dân dụng' ); ?>><?php echo esc_html( qp_holdings_t( 'Bất động sản Dân dụng' ) ); ?></h3>
							<p class="activity-card-desc"><?php qp_holdings_te( 'Đầu tư và phát triển các khu dân cư, căn hộ cao tầng, văn phòng và trung tâm thương mại hiện đại, kiến tạo không gian sống và làm việc chất lượng, bền vững.' ); ?></p>
						</div>
						<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'projects' ) : home_url( '/du-an/' ) ); ?>" class="activity-card-link" aria-label="<?php echo esc_attr( qp_holdings_t( 'Khám phá ngay lĩnh vực Bất động sản' ) ); ?>">
							<?php echo esc_html( qp_holdings_t( 'Khám phá ngay' ) ); ?>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					</article>

					<article class="activity-card reveal reveal-delay-1 swiper-slide">
						<div class="activity-card-img">
							<?php echo qp_holdings_theme_picture( 'assets/site/field-industrial-none.png', array( 'alt' => qp_holdings_t( 'Phat trien Khu Cong nghiep' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
						</div>
						<div class="activity-card-body">
							<h3 class="activity-card-title"<?php qp_holdings_te_attrs( 'Bất động sản Công nghiệp' ); ?>><?php echo esc_html( qp_holdings_t( 'Bất động sản Công nghiệp' ) ); ?></h3>
							<p class="activity-card-desc"><?php qp_holdings_te( 'Đầu tư và phát triển khu công nghiệp, cụm công nghiệp và hạ tầng logistics đồng bộ, đáp ứng nhu cầu sản xuất, kinh doanh và thu hút đầu tư.' ); ?></p>
						</div>
						<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'projects' ) : home_url( '/du-an/' ) ); ?>" class="activity-card-link" aria-label="<?php echo esc_attr( qp_holdings_t( 'Khám phá ngay lĩnh vực Khu Công nghiệp' ) ); ?>">
							<?php echo esc_html( qp_holdings_t( 'Khám phá ngay' ) ); ?>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					</article>

					<article class="activity-card reveal reveal-delay-2 swiper-slide">
						<div class="activity-card-img">
							<?php echo qp_holdings_theme_picture( 'assets/site/field-finance-none.png', array( 'alt' => qp_holdings_t( 'Dau tu Tai chinh' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
						</div>
						<div class="activity-card-body">
							<h3 class="activity-card-title"<?php qp_holdings_te_attrs( 'Đầu tư Tài chính' ); ?>><?php echo esc_html( qp_holdings_t( 'Đầu tư Tài chính' ) ); ?></h3>
							<p class="activity-card-desc"><?php qp_holdings_te( 'Tối ưu nguồn vốn, quản trị danh mục đầu tư hiệu quả và khai thác các cơ hội tăng trưởng bền vững, gia tăng giá trị dài hạn.' ); ?></p>
						</div>
						<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'projects' ) : home_url( '/du-an/' ) ); ?>" class="activity-card-link" aria-label="<?php echo esc_attr( qp_holdings_t( 'Khám phá ngay lĩnh vực Tài chính' ) ); ?>">
							<?php echo esc_html( qp_holdings_t( 'Khám phá ngay' ) ); ?>
							<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
								<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</a>
					</article>

				</div>
			</div>
		</div>
	</section>

	<!-- ═══ COMMITMENT ═══ -->
	<section class="commitment" aria-label="<?php echo esc_attr( qp_holdings_t( 'Cam kết của QP Holdings' ) ); ?>">
		<div class="container">
			<div class="commitment-grid">

				<div class="commitment-image reveal">
					<div class="commitment-img-wrap">
						<?php echo qp_holdings_theme_picture( 'assets/site/commitment-image-2481-7671.png', array( 'alt' => qp_holdings_t( 'QP Holdings - Cam kết đồng hành và minh bạch' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 900px) 88vw, 32vw' ); ?>
					</div>
				</div>

				<div class="commitment-text reveal reveal-delay-1">
					<div class="badge commitment-eyebrow">
						<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
							<path d="M6 22C7.86748 22 9.43606 20.7202 9.87657 18.9899C10.0128 18.4547 10.4477 18 11 18H18M6 22C3.79086 22 2 20.2091 2 18V5C2 3.34315 3.34315 2 5 2H15C16.6569 2 18 3.34315 18 5V18M6 22H18C19.8675 22 21.4361 20.7202 21.8766 18.9899C22.0128 18.4547 21.5523 18 21 18H18M7 13L8.5 11.5M13 9.5C13 10.8807 11.8807 12 10.5 12C9.11929 12 8 10.8807 8 9.5C8 8.11929 9.11929 7 10.5 7C11.8807 7 13 8.11929 13 9.5Z" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
						</svg>
						<span<?php qp_holdings_te_attrs( 'TRUY XUẤT NGUỒN GỐC' ); ?>><?php echo esc_html( qp_holdings_t( 'TRUY XUẤT NGUỒN GỐC' ) ); ?></span>
					</div>
					<h2 class="section-title">
						<span class="commitment-title-gradient"<?php qp_holdings_te_attrs( 'QP Holdings cam kết' ); ?>><?php echo esc_html( qp_holdings_t( 'QP Holdings cam kết' ) ); ?></span>
						<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'đồng hành' ); ?>><?php echo esc_html( qp_holdings_t( 'đồng hành' ) ); ?></span>
						<span class="commitment-title-dark">&amp;</span>
						<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'minh bạch' ); ?>><?php echo esc_html( qp_holdings_t( 'minh bạch' ) ); ?></span>
					</h2>

					<div class="commitment-features">
						<div class="commitment-feature">
							<div class="commitment-feature-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path d="M18.0003 18.5L18.2777 17.1138C18.4244 16.3806 18.8164 15.7048 18.9562 14.9703C18.9852 14.818 19.0003 14.6608 19.0003 14.5C19.0003 13.1193 17.881 12 16.5003 12C15.1196 12 14.0003 13.1193 14.0003 14.5C14.0003 14.6608 14.0155 14.818 14.0445 14.9703C14.1843 15.7048 14.5763 16.3806 14.723 17.1138L15.0003 18.5M18.0003 18.5H15.0003M18.0003 18.5L20.4966 19.1659C21.375 19.3611 22 20.1402 22 21.0401C22 21.5702 21.5702 22 21.0401 22H20.4966H12.5H11.9599C11.4298 22 11 21.5702 11 21.0401C11 20.1402 11.625 19.3611 12.5034 19.1659L15.0003 18.5" stroke="#D3A049" stroke-width="1.5" />
									<path d="M17 9V8C17 5.17157 17 3.75736 16.1213 2.87868C15.2426 2 13.8284 2 11 2H8C5.17157 2 3.75736 2 2.87868 2.87868C2 3.75736 2 5.17157 2 8V16C2 18.8284 2 20.2426 2.87868 21.1213C3.75736 22 5.17157 22 8 22" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
									<path d="M7 8.66667C7 8.66667 7.625 8.66667 8.25 10C8.25 10 10.2353 6.66667 12 6" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M6 14H10" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
									<path d="M6 17H10" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
								</svg>
							</div>
							<div>
								<h3<?php qp_holdings_te_attrs( 'Đồng hành dài hạn cùng đối tác và khách hàng' ); ?>><?php echo esc_html( qp_holdings_t( 'Đồng hành dài hạn cùng đối tác và khách hàng' ) ); ?></h3>
								<p><?php qp_holdings_te( 'Lấy uy tín và hiệu quả làm nền tảng, hướng đến xây dựng các mối quan hệ hợp tác bền vững và gia tăng giá trị lâu dài cho các bên.' ); ?></p>
							</div>
						</div>

						<div class="commitment-feature">
							<div class="commitment-feature-icon" aria-hidden="true">
								<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
									<path d="M15 7C15 7 15.5 7.5 16 8.5C16 8.5 17.5882 6 19 5.5" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M10.0144 2.00578C7.51591 1.9 5.58565 2.18782 5.58565 2.18782C4.3668 2.27496 2.03099 2.95829 2.03101 6.94898C2.03103 10.9058 2.00517 15.7837 2.03101 17.7284C2.03101 18.9164 2.76663 21.6877 5.31279 21.8363C8.40763 22.0168 13.9822 22.0552 16.54 21.8363C17.2247 21.7976 19.5042 21.2602 19.7927 18.7801C20.0915 16.2107 20.032 14.4251 20.032 14.0001" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M22.0193 7C22.0193 9.76142 19.7785 12 17.0145 12C14.2504 12 12.0096 9.76142 12.0096 7C12.0096 4.23858 14.2504 2 17.0145 2C19.7785 2 22.0193 4.23858 22.0193 7Z" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
									<path d="M7 13H11" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
									<path d="M7 17H15" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
								</svg>
							</div>
							<div>
								<h3<?php qp_holdings_te_attrs( 'Minh bạch trong quản trị và hợp tác đầu tư' ); ?>><?php echo esc_html( qp_holdings_t( 'Minh bạch trong quản trị và hợp tác đầu tư' ) ); ?></h3>
								<p><?php qp_holdings_te( 'Đảm bảo tính rõ ràng, chuyên nghiệp và trách nhiệm trong mọi hoạt động vận hành, tài chính và hợp tác kinh doanh.' ); ?></p>
							</div>
						</div>
					</div>

				<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'about' ) : home_url( '/gioi-thieu/' ) ); ?>" class="btn btn-outline-gold commitment-more" aria-label="<?php echo esc_attr( qp_holdings_t( 'learn_more_commitment' ) ); ?>">
					<?php echo esc_html( qp_holdings_t( 'learn_more' ) ); ?>
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M7.5 16.5L16.5 7.5M16.5 7.5H8.5M16.5 7.5V15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</a>
				</div>

			</div>
		</div>
	</section>

	<!-- ═══ GALLERY ═══ -->
	<section class="gallery" aria-label="<?php echo esc_attr( qp_holdings_t( 'Thư viện hình ảnh và video' ) ); ?>">
		<div class="gallery-inner">
			<div class="container">
				<div class="section-header gallery-header">
					<div>
						<div class="news-eyebrow">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
								<path d="M14.4531 12.8948C14.3016 13.5215 13.5857 13.9644 12.1539 14.8502C10.7697 15.7064 10.0777 16.1346 9.51993 15.9625C9.28934 15.8913 9.07925 15.7562 8.90982 15.57C8.5 15.1198 8.5 14.2465 8.5 12.5C8.5 10.7535 8.5 9.88018 8.90982 9.42995C9.07925 9.24381 9.28934 9.10868 9.51993 9.03753C10.0777 8.86544 10.7697 9.29357 12.1539 10.1498C13.5857 11.0356 14.3016 11.4785 14.4531 12.1052C14.5156 12.3639 14.5156 12.6361 14.4531 12.8948Z" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
								<path d="M20.9977 11C21 11.4701 21 11.9693 21 12.5C21 16.9783 21 19.2175 19.6088 20.6088C18.2175 22 15.9783 22 11.5 22C7.02166 22 4.78249 22 3.39124 20.6088C2 19.2175 2 16.9783 2 12.5C2 8.02166 2 5.78249 3.39124 4.39124C4.78249 3 7.02166 3 11.5 3C12.0307 3 12.5299 3 13 3.00231" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" />
								<path d="M18.5 2L18.7579 2.69703C19.0961 3.61102 19.2652 4.06802 19.5986 4.40139C19.932 4.73477 20.389 4.90387 21.303 5.24208L22 5.5L21.303 5.75792C20.389 6.09613 19.932 6.26524 19.5986 6.59861C19.2652 6.93198 19.0961 7.38898 18.7579 8.30297L18.5 9L18.2421 8.30297C17.9039 7.38898 17.7348 6.93198 17.4014 6.59861C17.068 6.26524 16.611 6.09613 15.697 5.75792L15 5.5L15.697 5.24208C16.611 4.90387 17.068 4.73477 17.4014 4.40139C17.7348 4.06802 17.9039 3.61102 18.2421 2.69703L18.5 2Z" stroke="#D3A049" stroke-width="1.5" stroke-linejoin="round" />
							</svg>
							<span<?php qp_holdings_te_attrs( 'KHOẢNH KHẮC QP HOLDINGS' ); ?>><?php echo esc_html( qp_holdings_t( 'KHOẢNH KHẮC QP HOLDINGS' ) ); ?></span>
						</div>
						<h2 class="section-title">
							<span class="navy-gradient-text"<?php qp_holdings_te_attrs( 'THƯ VIỆN' ); ?>><?php echo esc_html( qp_holdings_t( 'THƯ VIỆN' ) ); ?></span>
							<span class="gold-gradient-text"<?php qp_holdings_te_attrs( 'HÌNH ẢNH & VIDEO' ); ?>><?php echo esc_html( qp_holdings_t( 'HÌNH ẢNH & VIDEO' ) ); ?></span>
						</h2>
					</div>
					<a href="#" data-gallery-view-all class="btn btn-primary-gold btn-sm gallery-view-all">
						<span><?php echo esc_html( qp_holdings_t( 'Xem tất cả' ) ); ?></span>
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</a>
				</div>

				<?php
				$qp_gallery = function_exists( 'qp_holdings_render_gallery_cards' )
					? qp_holdings_render_gallery_cards( 4 )
					: array( 'cards' => '', 'payload' => '[]', 'count' => 0 );
				?>
				<div class="gallery-swiper swiper" data-home-gallery data-gallery-items="<?php echo esc_attr( $qp_gallery['payload'] ); ?>">
					<div class="swiper-wrapper gallery-grid">
						<?php
						if ( ! empty( $qp_gallery['cards'] ) ) {
							echo $qp_gallery['cards']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>

				<a href="#" class="gallery-more-mobile" data-gallery-view-all aria-label="<?php echo esc_attr( qp_holdings_t( 'Xem tất cả khoảnh khắc' ) ); ?>">
					<span><?php echo esc_html( qp_holdings_t( 'Xem tất cả' ) ); ?></span>
					<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
				</a>
			</div>
		</div>
	</section>

	<!-- ═══ NEWS ═══ -->
	<section class="news" id="news" aria-label="<?php echo esc_attr( qp_holdings_t( 'Tin tức QP Holdings' ) ); ?>">
		<div class="container news-container">

			<div class="news-header">
				<div class="news-eyebrow">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
						<path d="M19 21H5C3.89543 21 3 20.1046 3 19V8C3 6.89543 3.89543 6 5 6H15C16.1046 6 17 6.89543 17 8V21M17 21H19C20.1046 21 21 20.1046 21 19V11C21 9.89543 20.1046 9 19 9H17M17 21V9M7 11H13M7 14H13M7 17H11" stroke="#D3A049" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
					</svg>
					<span<?php qp_holdings_te_attrs( 'TIN TỨC QP HOLDINGS' ); ?>><?php echo esc_html( qp_holdings_t( 'TIN TỨC QP HOLDINGS' ) ); ?></span>
				</div>

				<div class="news-heading-row">
					<h2 class="news-title-main"<?php qp_holdings_te_attrs( 'Truyền thông & tin tức' ); ?>><?php echo esc_html( qp_holdings_t( 'Truyền thông & tin tức' ) ); ?></h2>
					<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="btn-gold-pill">
						<span><?php echo esc_html( qp_holdings_t( 'Xem tất cả' ) ); ?></span>
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M9 6.65032C9 6.65032 15.9383 6.10759 16.9154 7.08463C17.8924 8.06167 17.3496 15 17.3496 15M16.5 7.5L6.5 17.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
						</svg>
					</a>
				</div>
			</div>

			<div class="news-swiper swiper">
				<div class="swiper-wrapper news-grid">
					<?php
					$rendered = function_exists( 'qp_holdings_render_site_news_cards' )
						? qp_holdings_render_site_news_cards( 3 )
						: false;
					if ( ! $rendered ) :
						?>
						<article class="news-card featured reveal swiper-slide" aria-label="<?php echo esc_attr( qp_holdings_t( 'Bài viết nổi bật' ) ); ?>">
							<div class="news-card-img">
								<?php echo qp_holdings_theme_picture( 'assets/site/news-1-2714aa.png', array( 'alt' => qp_holdings_t( 'Bai viet ve phan bo danh muc dau tu' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
							</div>
							<div class="news-card-body">
								<time class="news-date featured" datetime="2025-10-17">17/10/2025</time>
								<h3 class="news-title"<?php qp_holdings_te_attrs( 'Đừng "bỏ trứng vào một giỏ": Bật mí cách phân bổ danh mục đầu tư chứng' ); ?>><?php echo esc_html( qp_holdings_t( 'Đừng "bỏ trứng vào một giỏ": Bật mí cách phân bổ danh mục đầu tư chứng' ) ); ?></h3>
							</div>
							<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="news-link news-link-featured" aria-label="<?php echo esc_attr( qp_holdings_t( 'Đọc chi tiết' ) ); ?>">
								<span><?php echo esc_html( qp_holdings_t( 'Chi tiết' ) ); ?></span>
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						</article>
						<article class="news-card reveal reveal-delay-1 swiper-slide">
							<div class="news-card-img">
								<?php echo qp_holdings_theme_picture( 'assets/site/news-2-389d63.png', array( 'alt' => qp_holdings_t( 'Bai viet ve ASEANSC dinh gia' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
							</div>
							<div class="news-card-body">
								<time class="news-date" datetime="2025-10-14">14/10/2025</time>
								<h3 class="news-title"<?php qp_holdings_te_attrs( 'ASEANSC: Định giá vẫn hấp dẫn so với các chu kỳ bơm tiền trong quá khứ,' ); ?>><?php echo esc_html( qp_holdings_t( 'ASEANSC: Định giá vẫn hấp dẫn so với các chu kỳ bơm tiền trong quá khứ,' ) ); ?></h3>
							</div>
							<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="news-link" aria-label="<?php echo esc_attr( qp_holdings_t( 'Đọc chi tiết' ) ); ?>">
								<span><?php echo esc_html( qp_holdings_t( 'Chi tiết' ) ); ?></span>
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						</article>
						<article class="news-card reveal reveal-delay-2 swiper-slide">
							<div class="news-card-img">
								<?php echo qp_holdings_theme_picture( 'assets/site/news-3-834c34.png', array( 'alt' => qp_holdings_t( 'Bai viet le khoi cong QP Green Park' ), 'loading' => 'lazy', 'decoding' => 'async' ), '(max-width: 640px) 80vw, 25vw' ); ?>
							</div>
							<div class="news-card-body">
								<time class="news-date" datetime="2025-10-02">02/10/2025</time>
								<h3 class="news-title"<?php qp_holdings_te_attrs( 'Lễ khởi công QP Green Park: Hành trình khởi tạo cuộc sống xanh' ); ?>><?php echo esc_html( qp_holdings_t( 'Lễ khởi công QP Green Park: Hành trình khởi tạo cuộc sống xanh' ) ); ?></h3>
							</div>
							<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="news-link" aria-label="<?php echo esc_attr( qp_holdings_t( 'Đọc chi tiết' ) ); ?>">
								<span><?php echo esc_html( qp_holdings_t( 'Chi tiết' ) ); ?></span>
								<svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
									<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
								</svg>
							</a>
						</article>
					<?php endif; ?>
				</div>
			</div>

			<a href="<?php echo esc_url( function_exists( 'qp_holdings_page_url' ) ? qp_holdings_page_url( 'news' ) : home_url( '/tintuc/' ) ); ?>" class="btn btn-outline-pill news-more-mobile">
				<span><?php echo esc_html( qp_holdings_t( 'Xem tất cả' ) ); ?></span>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M7 17L17 7M17 7H9M17 7V15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
				</svg>
			</a>
		</div>
	</section>

	<!-- ═══ PARTNERS ═══ -->
	<section class="partners" aria-label="<?php echo esc_attr( qp_holdings_t( 'Đối tác của QP Holdings' ) ); ?>">
		<div class="container">
			<div class="section-header center">
				<h2 class="section-title navy-gradient-text"<?php qp_holdings_te_attrs( 'Đối tác của chúng tôi' ); ?>><?php echo esc_html( qp_holdings_t( 'Đối tác của chúng tôi' ) ); ?></h2>
			</div>

			<div class="partners-swiper swiper" aria-label="<?php echo esc_attr( qp_holdings_t( 'Danh sách đối tác' ) ); ?>">
				<div class="swiper-wrapper partners-row">
					<?php
					echo function_exists( 'qp_holdings_render_partners_slides' )
						? qp_holdings_render_partners_slides() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						: '';
					?>
				</div>
			</div>
		</div>
	</section>

</main>

<?php get_footer(); ?>
