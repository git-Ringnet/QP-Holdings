<?php
/*
Template Name: Landing Page Profile Chiness
*/

get_header();
// Lấy mô tả: Ưu tiên lấy từ Tóm tắt (Excerpt) của trang trong wp-admin, nếu trống lấy mặc định
$page_desc = get_the_excerpt();
if ( empty( $page_desc ) ) {
$page_desc = function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_hero_desc' ) : 'Tổng quan về năng lực, tầm nhìn chiến lược, cơ cấu tổ chức và hành trình phát triển của Tập đoàn QP Holdings.';
}
?>

<main id="primary" class="projects-page">

    <!-- ═══ KHỐI BANNER ĐẦU TRANG ĐỘC LẬP (HERO KEY: profile) ═══ -->
    <section class="page-hero profile-hero" aria-label="<?php echo esc_attr( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_page_title' ) : 'Hồ sơ doanh nghiệp' ); ?>">
        <div class="page-hero-bg" aria-hidden="true">
            <?php echo qp_holdings_hero_picture( 'profile', 'assets/site/projects-hero-bg-79abd3.png', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ), '100vw' ); ?>
            <div class="page-hero-overlay"></div>
        </div>
        <div class="container page-hero-breadcrumb-container">
            <nav class="page-breadcrumb reveal" aria-label="Breadcrumb">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="page-breadcrumb-link"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'home' ) : 'Trang chủ' ); ?></a>
                <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/site/breadcrumb-arrow.svg' ); ?>" alt="" width="20" height="20" aria-hidden="true">
                <span class="page-breadcrumb-current"><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_page_title' ) : 'Hồ sơ doanh nghiệp' ); ?></span>
            </nav>
        </div>

        <div class="container page-hero-content page-hero-content--centered">
            <h1 class="page-hero-title page-hero-title--gold reveal reveal-delay-1"<?php qp_holdings_te_attrs( 'profile_page_title' ); ?>><?php echo esc_html( function_exists( 'qp_holdings_t' ) ? qp_holdings_t( 'profile_page_title' ) : 'Hồ sơ doanh nghiệp' ); ?></h1>
            <p class="page-hero-desc reveal reveal-delay-2"<?php qp_holdings_te_attrs( 'profile_hero_desc', array( 'multiline' => true ) ); ?>>
            <?php echo esc_html( $page_desc ); ?>
            </p>
        </div>
    </section>

	<!-- ═══ PRÒILE ARCHIVE ═══ -->
	<section aria-label="<?php echo ('Corporate Profile' ); ?>">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>

        <div class="flipbook-wrapper">
            <!-- 2 VÙNG CLICK ẨN -->
            <div class="click-zone click-zone-left" id="prevBtn" aria-label="Trang trước"></div>
            <div class="click-zone click-zone-right" id="nextBtn" aria-label="Trang sau"></div>

            <div class="book-container" id="bookContainer">
                <!-- NÚT ĐIỀU HƯỚNG HIỂN THỊ -->
                <button type="button" class="flipbook-nav flipbook-nav-prev" id="visiblePrevBtn" aria-label="Trang trước" title="Trang trước">
                    <span aria-hidden="true">&#10094;</span>
                </button>

                <button type="button" class="flipbook-nav flipbook-nav-next" id="visibleNextBtn" aria-label="Trang sau" title="Trang sau">
                    <span aria-hidden="true">&#10095;</span>
                </button>

                <div class="book">

                    <!-- TRANG 1 -->
                    <div class="page p1" id="page1">
                        <div class="front">
                            <!-- Ảnh Desktop -->
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-01.webp" width="1087" height="1512" alt="Bìa Trước Desktop" decoding="async">
                            <!-- Ảnh Mobile -->
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-01.webp" width="1087" height="1512" alt="Bìa Trước Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-02.webp" width="1087" height="1512" alt="Trang 1 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-02.webp" width="1087" height="1512" alt="Trang 1 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 2 -->
                    <div class="page p2" id="page2">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-03.webp" width="1087" height="1512" alt="Trang 2 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-03.webp" width="1087" height="1512" alt="Trang 2 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-04.webp" width="1087" height="1512" alt="Trang 3 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-04.webp" width="1087" height="1512" alt="Trang 3 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 3 -->
                    <div class="page p3" id="page3">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-05.webp" width="1087" height="1512" alt="Trang 4 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-05.webp" width="1087" height="1512" alt="Trang 4 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-06.webp" width="1087" height="1512" alt="Trang 5 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-06.webp" width="1087" height="1512" alt="Trang 5 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 4 -->
                    <div class="page p4" id="page4">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-07.webp" width="1087" height="1512" alt="Trang 6 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-07.webp" width="1087" height="1512" alt="Trang 6 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-08.webp" width="1087" height="1512" alt="Trang 7 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-08.webp" width="1087" height="1512" alt="Trang 7 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 5 -->
                    <div class="page p5" id="page5">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-09.webp" width="1087" height="1512" alt="Trang 8 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-09.webp" width="1087" height="1512" alt="Trang 8 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-10.webp" width="1087" height="1512" alt="Trang 9 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-10.webp" width="1087" height="1512" alt="Trang 9 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 6 -->
                    <div class="page p6" id="page6">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-11.webp" width="1087" height="1512" alt="Trang 10 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-11.webp" width="1087" height="1512" alt="Trang 11 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-12.webp" width="1087" height="1512" alt="Trang 12 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-12.webp" width="1087" height="1512" alt="12 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 7 -->
                    <div class="page p7" id="page7">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-13.webp" width="1087" height="1512" alt="Trang 13 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-13.webp" width="1087" height="1512" alt="Trang 13 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-14.webp" width="1087" height="1512" alt="Trang 14 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-14.webp" width="1087" height="1512" alt="Trang 14 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 8 -->
                    <div class="page p8" id="page8">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-15.webp" width="1087" height="1512" alt="Trang 15 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-15.webp" width="1087" height="1512" alt="Trang 15 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-16.webp" width="1087" height="1512" alt="Trang 16 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-16.webp" width="1087" height="1512" alt="Trang 16 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 9 -->
                    <div class="page p9" id="page9">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-17.webp" width="1087" height="1512" alt="Trang 17 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-17.webp" width="1087" height="1512" alt="Trang 7 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-18.webp" width="1087" height="1512" alt="Trang 18 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-18.webp" width="1087" height="1512" alt="Trang 18 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 10 -->
                    <div class="page p10" id="page10">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-19.webp" width="1087" height="1512" alt="Trang 19 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-19.webp" width="1087" height="1512" alt="Trang 19 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-20.webp" width="1087" height="1512" alt="Trang 20 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-20.webp" width="1087" height="1512" alt="Trang 20 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 11 -->
                    <div class="page p11" id="page11">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-21.webp" width="1087" height="1512" alt="Trang 21 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-21.webp" width="1087" height="1512" alt="Trang 21 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-22.webp" width="1087" height="1512" alt="Trang 22 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-22.webp" width="1087" height="1512" alt="Trang 22 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 12 -->
                    <div class="page p12" id="page12">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-23.webp" width="1087" height="1512" alt="Trang 23 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-23.webp" width="1087" height="1512" alt="Trang 23 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-24.webp" width="1087" height="1512" alt="Trang 24 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-24.webp" width="1087" height="1512" alt="Trang 24 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 13 -->
                    <div class="page p13" id="page13">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-25.webp" width="1087" height="1512" alt="Trang 25 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-25.webp" width="1087" height="1512" alt="Trang 25 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-26.webp" width="1087" height="1512" alt="Trang 26 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-26.webp" width="1087" height="1512" alt="Trang 26 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 14 -->
                    <div class="page p14" id="page14">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-27.webp" width="1087" height="1512" alt="Trang 27 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-27.webp" width="1087" height="1512" alt="Trang 27 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-28.webp" width="1087" height="1512" alt="Trang 28 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-28.webp" width="1087" height="1512" alt="Trang 28 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 15 -->
                    <div class="page p15" id="page15">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-29.webp" width="1087" height="1512" alt="Trang 29 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-29.webp" width="1087" height="1512" alt="Trang 29 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-30.webp" width="1087" height="1512" alt="Trang 30 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-30.webp" width="1087" height="1512" alt="Trang 30 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 16 -->
                    <div class="page p16" id="page16">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-31.webp" width="1087" height="1512" alt="Trang 31 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-31.webp" width="1087" height="1512" alt="Trang 31 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-32.webp" width="1087" height="1512" alt="Trang 32 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-32.webp" width="1087" height="1512" alt="Trang 32 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 17 -->
                    <div class="page p17" id="page17">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-33.webp" width="1087" height="1512" alt="Trang 33 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-33.webp" width="1087" height="1512" alt="Trang 33 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-34.webp" width="1087" height="1512" alt="Trang 34 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-34.webp" width="1087" height="1512" alt="Trang 34 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 18 -->
                    <div class="page p18" id="page18">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-35.webp" width="1087" height="1512" alt="Trang 35 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-35.webp" width="1087" height="1512" alt="Trang 35 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-36.webp" width="1087" height="1512" alt="Trang 36 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-36.webp" width="1087" height="1512" alt="Trang 36 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 19 -->
                    <div class="page p19" id="page19">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-37.webp" width="1087" height="1512" alt="Trang 37 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-37.webp" width="1087" height="1512" alt="Trang 37 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-38.webp" width="1087" height="1512" alt="Trang 38 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-38.webp" width="1087" height="1512" alt="Trang 38 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 20 -->
                    <div class="page p20" id="page20">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-39.webp" width="1087" height="1512" alt="Trang 39 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-39.webp" width="1087" height="1512" alt="Trang 39 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-40.webp" width="1087" height="1512" alt="Trang 40 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-40.webp" width="1087" height="1512" alt="Trang 40 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 21 -->
                    <div class="page p21" id="page21">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-41.webp" width="1087" height="1512" alt="Trang 41 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-41.webp" width="1087" height="1512" alt="Trang 41 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-42.webp" width="1087" height="1512" alt="Trang 42 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-42.webp" width="1087" height="1512" alt="Trang 42 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 22 -->
                    <div class="page p22" id="page22">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-43.webp" width="1087" height="1512" alt="Trang 43 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-43.webp" width="1087" height="1512" alt="Trang 43 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-44.webp" width="1087" height="1512" alt="Trang 44 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-44.webp" width="1087" height="1512" alt="Trang 44 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 23 -->
                    <div class="page p23" id="page23">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-45.webp" width="1087" height="1512" alt="Trang 45 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-45.webp" width="1087" height="1512" alt="Trang 45 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-46.webp" width="1087" height="1512" alt="Trang 46 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-46.webp" width="1087" height="1512" alt="Trang 46 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 24 -->
                    <div class="page p24" id="page24">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-47.webp" width="1087" height="1512" alt="Trang 47 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-47.webp" width="1087" height="1512" alt="Trang 47 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-48.webp" width="1087" height="1512" alt="Trang 48 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-48.webp" width="1087" height="1512" alt="Trang 48 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 25 -->
                    <div class="page p25" id="page25">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-49.webp" width="1087" height="1512" alt="Trang 49 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-49.webp" width="1087" height="1512" alt="Trang 49 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-50.webp" width="1087" height="1512" alt="Trang 50 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-50.webp" width="1087" height="1512" alt="Trang 50 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 26 -->
                    <div class="page p26" id="page26">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-51.webp" width="1087" height="1512" alt="Trang 51 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-51.webp" width="1087" height="1512" alt="Trang 51 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-52.webp" width="1087" height="1512" alt="Trang 52 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-52.webp" width="1087" height="1512" alt="Trang 52 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 27 -->
                    <div class="page p27" id="page27">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-53.webp" width="1087" height="1512" alt="Trang 53 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-53.webp" width="1087" height="1512" alt="Trang 53 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-54.webp" width="1087" height="1512" alt="Trang 54 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-54.webp" width="1087" height="1512" alt="Trang 54 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 28 -->
                    <div class="page p28" id="page28">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-55.webp" width="1087" height="1512" alt="Trang 55 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-55.webp" width="1087" height="1512" alt="Trang 55 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-56.webp" width="1087" height="1512" alt="Trang 56 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-56.webp" width="1087" height="1512" alt="Trang 56 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 29 -->
                    <div class="page p29" id="page29">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-57.webp" width="1087" height="1512" alt="Trang 57 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-57.webp" width="1087" height="1512" alt="Trang 57 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-58.webp" width="1087" height="1512" alt="Trang 58 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-58.webp" width="1087" height="1512" alt="Trang 58 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 30 -->
                    <div class="page p30" id="page30">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-59.webp" width="1087" height="1512" alt="Trang 59 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-59.webp" width="1087" height="1512" alt="Trang 59 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-60.webp" width="1087" height="1512" alt="Trang 60 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-60.webp" width="1087" height="1512" alt="Trang 60 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 31 -->
                    <div class="page p31" id="page31">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-61.webp" width="1087" height="1512" alt="Trang 61 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-61.webp" width="1087" height="1512" alt="Trang 61 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-62.webp" width="1087" height="1512" alt="Trang 62 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-62.webp" width="1087" height="1512" alt="Trang 62 Mobile" decoding="async">
                        </div>
                    </div>

                    <!-- TRANG 31 -->
                    <div class="page p32" id="page32">
                        <div class="front">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-63.webp" width="1087" height="1512" alt="Trang 63 Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-63.webp" width="1087" height="1512" alt="Trang 63 Mobile" decoding="async">
                        </div>
                        <div class="back">
                            <img class="img-desktop" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_Trang-64.webp" width="1087" height="1512" alt="Bìa Sau Desktop" decoding="async">
                            <img class="img-mobile" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/mobile/QP_Profile_Trang-64.webp" width="1087" height="1512" alt="Bìa Sau Mobile" decoding="async">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- QR CODE DOWNLOAD PROFILE -->
        <div class="footer-qr-wrapper" style="text-align: center; background: #eef1f6;">
            <div class="footer-qr-card" style="display: inline-block; padding: 6px; background: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/qr_profile_zh.png"
            alt="QR Code QP Holdings"
            width="96"
            height="96"
            style="display: block; width: 105px; height: 105px; border-radius: 4px;"
            loading="lazy">
            </div>
            <p class="footer-qr-label" style="margin: 0 0 0 0px; font-size: 14px; color: var(--gold-start); letter-spacing: 0.2px;     font-weight: 900;">公司简介</p>
        </div>

        <!-- NÚT DOWNLOAD PROFILE -->
        <div class="flipbook-download-wrap">
            <a
                class="flipbook-download-btn"
                id="downloadProfileBtn"
                href="<?php echo esc_url( get_template_directory_uri() ); ?>/hinh/zh/QP_Profile_CN_trang-doi.pdf"
                download="QP_Profile_Company.pdf"
                aria-label="下载资料"
            >
                <span class="download-icon" aria-hidden="true">&#8681;</span>
                <span>下载资料</span>
            </a>
        </div>

        <?php endwhile; endif; ?>

        <style>
            /* ==============================
            RESPONSIVE LAYOUT - KHUNG BOOK
            ============================== */
            .flipbook-wrapper {
                position: relative;
                width: 100%;
                min-height: 0;
                background-color: #eef1f6;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                overflow: hidden;
                padding: 40px 0 15px;
                box-sizing: border-box;
                clear: both;
            }

            /* MẶC ĐỊNH (DESKTOP): Hiện ảnh Desktop, Ẩn ảnh Mobile */
            .flipbook-wrapper .img-desktop {
                display: block !important;
            }
            .flipbook-wrapper .img-mobile {
                display: none !important;
            }

            /* 2 VÙNG CẢM ỨNG CLICK ĐỂ LẬT TRANG */
            .flipbook-wrapper .click-zone {
                position: absolute;
                top: 0;
                width: 50%;
                height: 100%;
                z-index: 100;
                cursor: pointer;
                user-select: none;
            }
            .flipbook-wrapper .click-zone-left { left: 0; }
            .flipbook-wrapper .click-zone-right { right: 0; }

            /* Vùng chứa tạo không gian 3D */
            .flipbook-wrapper .book-container {
                width: 450px;
                height: 570px;
                position: relative;
                perspective: 1500px;
                transform: translateX(0%);
                transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1);
                flex-shrink: 0;
                box-sizing: border-box;
            }

            /* TRÊN MOBILE: Hiện ảnh Mobile, Ẩn ảnh Desktop */
            @media (max-width: 600px), (orientation: landscape) and (max-height: 600px) {
                .flipbook-wrapper .img-desktop {
                    display: none !important;
                }
                .flipbook-wrapper .img-mobile {
                    display: block !important;
                }

                /*
                * MOBILE / iPHONE:
                * Không dùng min-height theo viewport vì sẽ đẩy QR xuống quá xa.
                * Wrapper ôm sát chiều cao của book để khoảng cách Header -> Book
                * và Book -> QR ổn định trên iPhone 12, 15 Pro Max và các màn hình khác.
                */
                .flipbook-wrapper {
                    min-height: 0;
                    height: auto;
                    /* Header mobile hiện khoảng 112px; thêm 14px khoảng thở.
                    Không dùng vh để tránh iPhone đẩy QR xuống quá xa. */
                    padding: 126px 12px 12px;
                    align-items: flex-start;
                    justify-content: center;
                }

                .flipbook-wrapper .book-container {
                    width: min(100%, 450px);
                    height: auto;
                    aspect-ratio: 3 / 4;
                    max-width: 450px;
                    max-height: none;
                    flex-shrink: 0;
                    box-sizing: border-box;
                    transform: translateX(0%) !important;
                }

                .flipbook-wrapper .book,
                .flipbook-wrapper .page,
                .flipbook-wrapper .front,
                .flipbook-wrapper .back {
                    width: 100%;
                    height: 100%;
                    box-sizing: border-box;
                }

                .flipbook-wrapper .page.mobile-hidden { display: none !important; }
                .flipbook-wrapper .page.mobile-current {
                    display: block !important;
                    transform: none !important;
                    transform-style: flat !important;
                }

                .flipbook-wrapper .page.mobile-current .front,
                .flipbook-wrapper .page.mobile-current .back {
                    transform: none !important;
                    backface-visibility: visible !important;
                }

                .flipbook-wrapper .page.mobile-current .front.mobile-face-hidden,
                .flipbook-wrapper .page.mobile-current .back.mobile-face-hidden { display: none !important; }

                .flipbook-wrapper .page.mobile-current .front.mobile-face-visible,
                .flipbook-wrapper .page.mobile-current .back.mobile-face-visible {
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }

                .flipbook-wrapper .front img,
                .flipbook-wrapper .back img {
                    width: 100%;
                    height: 100%;
                    max-width: 100%;
                    max-height: 100%;
                    object-fit: cover;
                }

                /* Lật 1 trang: tờ phủ xoay quanh mép trái như lật sách */
                .flipbook-wrapper .book-container { perspective: 1400px; }
                .flipbook-wrapper .qp-turn {
                    position: absolute;
                    inset: 0;
                    z-index: 60;
                    transform-origin: left center;
                    backface-visibility: hidden;
                    -webkit-backface-visibility: hidden;
                    transition: transform 0.6s cubic-bezier(0.3, 0.1, 0.3, 1);
                    will-change: transform;
                    pointer-events: none;
                    overflow: hidden;
                    border-radius: 0 12px 12px 0;
                    box-shadow: 0 6px 22px rgba(0, 0, 0, 0.28);
                }
                .flipbook-wrapper .qp-turn img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                .flipbook-wrapper .qp-turn::after {
                    content: '';
                    position: absolute;
                    inset: 0;
                    background: linear-gradient(to left, rgba(0, 0, 0, 0.32), rgba(0, 0, 0, 0) 55%);
                    opacity: 0;
                    transition: opacity 0.65s ease;
                }
                .flipbook-wrapper .qp-turn.is-out::after { opacity: 1; }
            }

            .flipbook-wrapper .book {
                width: 100%;
                height: 100%;
                position: absolute;
                transform-style: preserve-3d;
            }

            .flipbook-wrapper .page {
                width: 100%;
                height: 100%;
                position: absolute;
                top: 0;
                left: 0;
                transform-style: preserve-3d;
                transform-origin: left center;
                transition: transform 0.8s cubic-bezier(0.25, 1, 0.5, 1); /* không chuyển dần z-index: gây chớp trang + vẽ lại cả chồng giấy */
            }

            .flipbook-wrapper .front, 
            .flipbook-wrapper .back {
                position: absolute;
                width: 100%;
                height: 100%;
                padding: 0;
                margin: 0;
                box-sizing: border-box;
                backface-visibility: hidden;
                overflow: hidden;
                transition: opacity 0.4s ease, visibility 0.4s ease; 
            }

            .flipbook-wrapper .front img, 
            .flipbook-wrapper .back img {
                width: 100%;
                height: 100%;
                max-width: 100%;
                object-fit: cover;
            }

            .flipbook-wrapper .front { border-radius: 0 12px 12px 0; }
            .flipbook-wrapper .back {
                transform: rotateY(180deg);
                z-index: 1;
                border-radius: 12px 0 0 12px;
            }
            .flipbook-wrapper .p3 .back { border-radius: 12px; }
            .flipbook-wrapper .page.flipped { transform: rotateY(-180deg); }
            .flipbook-wrapper .book-container.hide-left-content .p2 .back {
                opacity: 0;
                visibility: hidden;
            }

            /* ==============================
            NÚT MŨI TÊN ĐIỀU HƯỚNG
            ============================== */
            .flipbook-wrapper .flipbook-nav {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                width: 48px;
                height: 72px;
                border: 0;
                border-radius: 10px;
                background: rgba(0, 0, 0, 0.42);
                color: #fff;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 110;
                cursor: pointer;
                font-size: 32px;
                line-height: 1;
                padding: 0;
                transition: background-color .2s ease, opacity .2s ease, transform .2s ease, left .8s cubic-bezier(0.25, 1, 0.5, 1), right .8s cubic-bezier(0.25, 1, 0.5, 1);
                -webkit-tap-highlight-color: transparent;
            }

            .flipbook-wrapper .flipbook-nav:hover {
                background: rgba(0, 0, 0, 0.68);
            }

            .flipbook-wrapper .flipbook-nav:active {
                transform: translateY(-50%) scale(.94);
            }

            /* DESKTOP: Định vị nút theo từng trạng thái trang */
            @media (min-width: 601px) {
                /* Mặc định cho Trang bìa đầu (1 trang bên phải) */
                .flipbook-wrapper .flipbook-nav-prev {
                    left: -24px;
                }
                .flipbook-wrapper .flipbook-nav-next {
                    right: -24px;
                }

                /* Khi mở 2 trang ở giữa */
                .flipbook-wrapper .book-container.is-open .flipbook-nav-prev {
                    left: -474px; /* Dính mép trái của trang đôi (-450px - 24px) */
                }
                .flipbook-wrapper .book-container.is-open .flipbook-nav-next {
                    right: -24px;  /* Dính mép phải của trang đôi */
                }

                /* Khi đến Trang bìa cuối (1 trang bên trái) */
                .flipbook-wrapper .book-container.is-last-page .flipbook-nav-prev {
                    left: -474px; /* Dính mép trái của trang bìa sau */
                }
                .flipbook-wrapper .book-container.is-last-page .flipbook-nav-next {
                    right: 426px; /* Dính mép phải của trang bìa sau (450px - 24px) */
                }
            }

            /* MOBILE: Căn chỉnh nút điều hướng hiển thị sát cạnh màn hình */
            @media (max-width: 600px), (orientation: landscape) and (max-height: 600px) {
                .flipbook-wrapper .flipbook-nav {
                    width: 38px;
                    height: 58px;
                    font-size: 25px;
                    border-radius: 8px;
                    background: rgba(0, 0, 0, 0.48);
                }

                .flipbook-wrapper .flipbook-nav-prev {
                    left: 6px !important;
                }

                .flipbook-wrapper .flipbook-nav-next {
                    right: 6px !important;
                }
            }

            /* ==============================
            NÚT DOWNLOAD PROFILE
            ============================== */
            .flipbook-download-wrap {
                width: 100%;
                display: flex;
                justify-content: center;
                padding: 5px 5px 5px;
                box-sizing: border-box;
                background: #eef1f6;
            }

            .flipbook-download-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 9px;
                min-width: 190px;
                padding: 11px 22px;
                border-radius: 7px;
                background: #1f2937;
                color: #fff !important;
                text-decoration: none !important;
                font-size: 15px;
                font-weight: 600;
                line-height: 1.2;
                box-shadow: 0 4px 12px rgba(0,0,0,.16);
                transition: background-color .2s ease, transform .2s ease;
            }

            .flipbook-download-btn:hover {
                background: #111827;
                color: #fff !important;
                transform: translateY(-1px);
            }

            .flipbook-download-btn:active {
                transform: translateY(0);
            }

            .flipbook-download-btn .download-icon {
                font-size: 21px;
                line-height: 1;
            }

            /* TABLET / MÀN HÌNH NHỎ: giảm book theo chiều rộng nhưng giữ tỷ lệ 3:4 */
            @media (min-width: 601px) and (max-width: 1100px) {
                .flipbook-wrapper {
                    padding: 110px 20px 14px;
                }

                .flipbook-wrapper .book-container {
                    width: min(450px, calc(100vw - 40px));
                    height: auto;
                    aspect-ratio: 3 / 4;
                }
            }

            /* ĐIỆN THOẠI XOAY NGANG (đặt sau khối máy tính bảng để không bị đè): trang vừa chiều cao màn hình */
            @media (orientation: landscape) and (max-height: 600px) {
                .flipbook-wrapper {
                    padding-top: 84px;
                }

                .flipbook-wrapper .book-container {
                    width: min(450px, calc((100vh - 110px) * 0.75));
                }
            }

            /* PHONE: khoảng cách được tính theo nhóm kích thước màn hình */
            @media (max-width: 600px) {
                .footer-qr-wrapper {
                    margin-top: 0 !important;
                    padding: 4px 12px 6px !important;
                }

                .footer-qr-card {
                    padding: 5px !important;
                }

                .footer-qr-card img {
                    width: 96px !important;
                    height: 96px !important;
                }

                .flipbook-download-wrap {
                    padding: 8px 12px 16px;
                }

                .flipbook-download-btn {
                    width: min(230px, 85vw);
                    min-width: 0;
                    padding: 10px 18px;
                    font-size: 14px;
                }
            }

            /* Điện thoại rất thấp / landscape: không để header + book chiếm quá nhiều chiều cao */
            @media (max-width: 600px) and (max-height: 700px) {
                .flipbook-wrapper {
                    padding-top: 40px;
                }

                .flipbook-wrapper .book-container {
                    width: min(100%, 420px);
                }
            }

            /* Điện thoại cao: giữ khoảng cách Header -> Book ổn định, không dùng vh */
            @media (max-width: 600px) and (min-height: 800px) {
                .flipbook-wrapper {
                    padding-top: 40px;
                }
            }

            /* iPhone / thiết bị rất hẹp */
            @media (max-width: 390px) {
                .flipbook-wrapper {
                    padding-left: 8px;
                    padding-right: 8px;
                }

                .flipbook-wrapper .book-container {
                    width: 100%;
                }

                .flipbook-wrapper .flipbook-nav {
                    width: 36px;
                    height: 54px;
                    font-size: 23px;
                }
            }
        </style>

        <script>
            const container = document.getElementById('bookContainer');
            const pages = [
                document.getElementById('page1'),
                document.getElementById('page2'),
                document.getElementById('page3'),
                document.getElementById('page4'),
                document.getElementById('page5'),
                document.getElementById('page6'),
                document.getElementById('page7'),
                document.getElementById('page8'),
                document.getElementById('page9'),
                document.getElementById('page10'),
                document.getElementById('page11'),
                document.getElementById('page12'),
                document.getElementById('page13'),
                document.getElementById('page14'),
                document.getElementById('page15'),
                document.getElementById('page16'),
                document.getElementById('page17'),
                document.getElementById('page18'),
                document.getElementById('page19'),
                document.getElementById('page20'),
                document.getElementById('page21'),
                document.getElementById('page22'),
                document.getElementById('page23'),
                document.getElementById('page24'),
                document.getElementById('page25'),
                document.getElementById('page26'),
                document.getElementById('page27'),
                document.getElementById('page28'),
                document.getElementById('page29'),
                document.getElementById('page30'),
                document.getElementById('page31'),
                document.getElementById('page32')
            ];

            let currentPageIndex = 0;
            const totalPages = pages.length;

            let mobilePageIndex = 0;
            const mobileTotalPages = totalPages * 2;

            const isMobile = () =>
                window.matchMedia('(max-width: 600px), (orientation: landscape) and (max-height: 600px)').matches;

            function initZIndex() {
                for (let i = 0; i < totalPages; i++) {
                    pages[i].style.zIndex = totalPages - i;
                }
            }
            initZIndex();

            function updateBookPosition() {
                if (currentPageIndex === 0) {
                    container.style.transform = "translateX(0%)";
                    container.classList.remove('hide-left-content', 'is-open', 'is-last-page');
                } else if (currentPageIndex === totalPages) {
                    container.style.transform = "translateX(100%)";
                    container.classList.add('hide-left-content', 'is-last-page');
                    container.classList.remove('is-open');
                } else {
                    container.style.transform = "translateX(50%)";
                    container.classList.remove('hide-left-content', 'is-last-page');
                    container.classList.add('is-open');
                }
            }

            function updateMobilePage() {
                pages.forEach((page, pageIndex) => {
                    const imageIndex = mobilePageIndex;
                    const targetPage = Math.floor(imageIndex / 2);
                    const showBack = imageIndex % 2 === 1;

                    page.classList.remove('mobile-current', 'mobile-hidden');

                    if (pageIndex !== targetPage) {
                        page.classList.add('mobile-hidden');
                        page.querySelector('.front').classList.add('mobile-face-hidden');
                        page.querySelector('.front').classList.remove('mobile-face-visible');
                        page.querySelector('.back').classList.add('mobile-face-hidden');
                        page.querySelector('.back').classList.remove('mobile-face-visible');
                    } else {
                        page.classList.add('mobile-current');
                        page.classList.remove('flipped');

                        const front = page.querySelector('.front');
                        const back = page.querySelector('.back');

                        if (showBack) {
                            front.classList.add('mobile-face-hidden');
                            front.classList.remove('mobile-face-visible');
                            back.classList.remove('mobile-face-hidden');
                            back.classList.add('mobile-face-visible');
                        } else {
                            back.classList.add('mobile-face-hidden');
                            back.classList.remove('mobile-face-visible');
                            front.classList.remove('mobile-face-hidden');
                            front.classList.add('mobile-face-visible');
                        }

                        page.style.zIndex = '10';
                    }
                });

                container.style.transform = 'translateX(0%)';
                container.classList.remove('hide-left-content', 'is-open', 'is-last-page');
            }

            if (isMobile()) {
                updateMobilePage();
            } else {
                updateBookPosition();
            }

            // Nút mũi tên hiển thị dùng chung logic với 2 vùng click hiện tại.
            document.getElementById('visibleNextBtn').addEventListener('click', (event) => {
                event.stopPropagation();
                document.getElementById('nextBtn').click();
            });

            document.getElementById('visiblePrevBtn').addEventListener('click', (event) => {
                event.stopPropagation();
                document.getElementById('prevBtn').click();
            });

            function updateNavigationButtons() {
                const prev = document.getElementById('visiblePrevBtn');
                const next = document.getElementById('visibleNextBtn');

                if (isMobile()) {
                    prev.style.opacity = mobilePageIndex > 0 ? '1' : '0.28';
                    next.style.opacity = mobilePageIndex < mobileTotalPages - 1 ? '1' : '0.28';
                    prev.style.pointerEvents = mobilePageIndex > 0 ? 'auto' : 'none';
                    next.style.pointerEvents = mobilePageIndex < mobileTotalPages - 1 ? 'auto' : 'none';
                } else {
                    prev.style.opacity = currentPageIndex > 0 ? '1' : '0.28';
                    next.style.opacity = currentPageIndex < totalPages ? '1' : '0.28';
                    prev.style.pointerEvents = currentPageIndex > 0 ? 'auto' : 'none';
                    next.style.pointerEvents = currentPageIndex < totalPages ? 'auto' : 'none';
                }
            }

            // Đặt tờ đang lật lên trên cùng; sau khi lật xong (0,8s) trả về z-index đích.
            function setTurning(sheet, finalZ) {
                clearTimeout(sheet._qpTurn);
                sheet.style.visibility = '';
                sheet.style.zIndex = totalPages + 2;
                sheet._qpTurn = setTimeout(() => {
                    sheet.style.zIndex = finalZ;
                    sheet._qpTurn = null;
                    cullPages();
                }, 820);
            }

            // Máy tính: chỉ dựng 2 tờ mỗi bên trang đang xem (các tờ khác nằm khuất bên dưới) → lật mượt hơn.
            function cullPages() {
                if (isMobile()) {
                    pages.forEach((pg) => { pg.style.visibility = ''; });
                    return;
                }
                pages.forEach((pg, i) => {
                    const near = i >= currentPageIndex - 2 && i <= currentPageIndex + 1;
                    pg.style.visibility = near || pg._qpTurn ? '' : 'hidden';
                });
            }

            // Chỉ tải ảnh của tờ đang xem và các tờ kế bên (ảnh khác chờ trong data-src).
            function loadSheetImages(index) {
                const sheet = pages[index];
                if (!sheet) return;
                sheet.querySelectorAll(isMobile() ? 'img.img-mobile[data-src]' : 'img.img-desktop[data-src]').forEach((img) => {
                    const webp = img.dataset.src;
                    // Trình duyệt cũ không hiển thị được WebP (iOS 13 trở xuống, IE) → dùng file JPG gốc cùng tên.
                    img.onerror = () => { img.onerror = null; img.src = webp.replace(/\.webp$/, '.jpg'); };
                    img.src = webp;
                    img.removeAttribute('data-src');
                });
            }

            function loadNearbyPages() {
                const base = isMobile() ? Math.floor(mobilePageIndex / 2) : currentPageIndex;
                for (let i = base - 1; i <= base + 2; i++) {
                    loadSheetImages(i);
                }
            }

            // Cập nhật trạng thái nút khi trang thay đổi.
            const originalUpdateMobilePage = updateMobilePage;
            updateMobilePage = function() {
                originalUpdateMobilePage();
                updateNavigationButtons();
                loadNearbyPages();
                cullPages();
            };

            const originalUpdateBookPosition = updateBookPosition;
            updateBookPosition = function() {
                originalUpdateBookPosition();
                updateNavigationButtons();
                loadNearbyPages();
                cullPages();
            };

            // ĐIỆN THOẠI: lật từng trang. Tờ phủ mang ảnh trang cũ xoay sang trái (trang sau) hoặc ảnh trang mới xoay về (trang trước).
            let mobileTurning = false;
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            function mobileImage(index) {
                const sheet = pages[Math.floor(index / 2)];
                const img = sheet && sheet.querySelector((index % 2 ? '.back' : '.front') + ' img.img-mobile');
                if (!img) return '';
                return img.getAttribute('data-src') || img.currentSrc || img.src;
            }

            function turnMobile(delta) {
                const target = mobilePageIndex + delta;
                if (target < 0 || target > mobileTotalPages - 1) return;
                if (mobileTurning || reduceMotion) {
                    mobilePageIndex = target;
                    updateMobilePage();
                    return;
                }
                mobileTurning = true;
                const forward = delta > 0;
                const leaf = document.createElement('div');
                leaf.className = 'qp-turn';
                leaf.innerHTML = '<img alt="" decoding="async">';
                leaf.firstChild.src = mobileImage(forward ? mobilePageIndex : target);
                leaf.style.transform = forward ? 'rotateY(0deg)' : 'rotateY(-180deg)';
                container.appendChild(leaf);
                if (forward) {
                    mobilePageIndex = target; // trang mới nằm bên dưới tờ đang lật
                    updateMobilePage();
                }
                void leaf.offsetWidth; // áp trạng thái đầu trước khi chuyển động
                leaf.classList.toggle('is-out', forward);
                leaf.style.transform = forward ? 'rotateY(-180deg)' : 'rotateY(0deg)';
                // Xong khi tờ lật tới nơi (transitionend); 1,2s là chốt an toàn nếu trình duyệt không báo.
                let done = false;
                const finish = () => {
                    if (done) return;
                    done = true;
                    if (!forward) {
                        mobilePageIndex = target;
                        updateMobilePage();
                    }
                    leaf.remove();
                    mobileTurning = false;
                };
                leaf.addEventListener('transitionend', (e) => { if (e.propertyName === 'transform') finish(); });
                setTimeout(finish, 1200);
            }

            document.getElementById('nextBtn').addEventListener('click', () => {
                if (isMobile()) {
                    turnMobile(1);
                    return;
                }

                if (currentPageIndex < totalPages) {
                    const pageToFlip = pages[currentPageIndex];
                    if (pageToFlip) {
                        // Tờ đang lật nằm trên cùng suốt lúc lật, lật xong mới về đúng vị trí trong chồng bên trái.
                        setTurning(pageToFlip, currentPageIndex + 1);
                        pageToFlip.classList.add('flipped');
                    }
                    currentPageIndex++;
                    updateBookPosition();
                }
            });

            document.getElementById('prevBtn').addEventListener('click', () => {
                if (isMobile()) {
                    turnMobile(-1);
                    return;
                }

                if (currentPageIndex > 0) {
                    currentPageIndex--;
                    const pageToUnflip = pages[currentPageIndex];
                    if (pageToUnflip) {
                        setTurning(pageToUnflip, totalPages - currentPageIndex);
                        pageToUnflip.classList.remove('flipped');
                    }
                    updateBookPosition();
                }
            });

            updateNavigationButtons();
            loadNearbyPages();
            cullPages();

            window.addEventListener('resize', () => {
                if (isMobile()) {
                    updateMobilePage();
                } else {
                    currentPageIndex = 0;
                    pages.forEach(page => {
                        page.classList.remove('mobile-current', 'mobile-hidden', 'flipped');
                    });
                    initZIndex();
                    updateBookPosition();
                }
            });
        </script>
	</section>

</main>

<?php
get_footer();