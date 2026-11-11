<?php
/**
 * wp-config.php DÀNH CHO MÁY DEV (được đưa vào Git).
 *
 * ⚠️ KHÔNG BAO GIỜ upload file này lên hosting — production có wp-config.php riêng.
 *    (tools/make-deploy-zip.ps1 chỉ đóng gói code trong wp-content nên không đụng tới file này.)
 *
 * Mặc định: database "qpholdings", user "root", mật khẩu rỗng (XAMPP),
 * website tại http://localhost/qpholdings.
 *
 * Máy bạn cấu hình khác? Tạo file wp-config-local.php (không đưa lên Git) và chỉ khai báo phần khác, ví dụ:
 *
 *   <?php
 *   define( 'DB_NAME', 'ten_database_khac' );
 *   define( 'DB_PASSWORD', 'mat-khau' );
 *   define( 'WP_HOME', 'http://localhost/ten-thu-muc-khac' );
 *   define( 'WP_SITEURL', 'http://localhost/ten-thu-muc-khac' );
 *
 * (wp-config-local.php cũng có thể là một wp-config.php đầy đủ — khi đó nó được dùng toàn bộ.)
 */

if ( file_exists( __DIR__ . '/wp-config-local.php' ) ) {
	require_once __DIR__ . '/wp-config-local.php';
}

// ** Database ** //
defined( 'DB_NAME' )     || define( 'DB_NAME', getenv( 'QP_DB_NAME' ) ?: 'qpholdings' );
defined( 'DB_USER' )     || define( 'DB_USER', getenv( 'QP_DB_USER' ) ?: 'root' );
defined( 'DB_PASSWORD' ) || define( 'DB_PASSWORD', getenv( 'QP_DB_PASSWORD' ) ?: '' );
defined( 'DB_HOST' )     || define( 'DB_HOST', getenv( 'QP_DB_HOST' ) ?: 'localhost' );
defined( 'DB_CHARSET' )  || define( 'DB_CHARSET', 'utf8mb4' );
defined( 'DB_COLLATE' )  || define( 'DB_COLLATE', '' );

// ** Khoá bảo mật — chỉ dùng cho máy dev, khác hoàn toàn với production ** //
defined( 'AUTH_KEY' )         || define( 'AUTH_KEY',         'A-ee3]E5Ln1^%w.F7aXy#Tu^C9.4pJY&#D%H_U:sFmib,W]-EBq8Y/{)IwTC.@Ir' );
defined( 'SECURE_AUTH_KEY' )  || define( 'SECURE_AUTH_KEY',  '+D7FCSb57I.Un9ACU6PIBy##y>eL!dd#TLzILdJ7L,_R,cal5U/lVD=lYb;^3P8a' );
defined( 'LOGGED_IN_KEY' )    || define( 'LOGGED_IN_KEY',    'PU!)@[iGjjGj.>Z@.#ZbG>QiFb+mbC@Idm}gEB~!:tEwgKR[:_@cDcX/7i2Ic(L0' );
defined( 'NONCE_KEY' )        || define( 'NONCE_KEY',        ':R2z)Ge]a6YV:Px*wPYLC?r(akE0m09Z{QSo]poSiwNKY2fy|WIfTEyQi8RCm&PM' );
defined( 'AUTH_SALT' )        || define( 'AUTH_SALT',        'S>3!Dz)+W3L6<v+eiaPA%XJ^]%yJ)vG>sc+iN!R7K,(Eo~EAeJr%jW]=Z0_[|?#K' );
defined( 'SECURE_AUTH_SALT' ) || define( 'SECURE_AUTH_SALT', '<gzJ%#02Z}#nHL@.!nOJ,1vVX+azZsq6!qa:cOJbx4(#Rj14=Ef[vIZ[&[j*Cxve' );
defined( 'LOGGED_IN_SALT' )   || define( 'LOGGED_IN_SALT',   'N9)5E3SM@hr7^6/6XMDgcxda5E?P(Srf9[v;4H1jRd%spQ.?Th.{Nq@8UP{b;iMQ' );
defined( 'NONCE_SALT' )       || define( 'NONCE_SALT',       'lKM*2{ceJ]27LaM5O}Kp)q2&7mrQB@n{g79cx5Lf99RX],5JYpc.sx2eA:*GMEgx' );

if ( ! isset( $table_prefix ) ) {
	$table_prefix = 'wp_';
}

// ** Địa chỉ website local ** //
defined( 'WP_HOME' )    || define( 'WP_HOME', 'http://localhost/qpholdings' );
defined( 'WP_SITEURL' ) || define( 'WP_SITEURL', 'http://localhost/qpholdings' );

// ** Môi trường dev ** //
defined( 'WP_ENVIRONMENT_TYPE' ) || define( 'WP_ENVIRONMENT_TYPE', 'local' );
defined( 'WP_DEBUG' )            || define( 'WP_DEBUG', true );
defined( 'WP_DEBUG_LOG' )        || define( 'WP_DEBUG_LOG', true );
defined( 'WP_DEBUG_DISPLAY' )    || define( 'WP_DEBUG_DISPLAY', false );
defined( 'WP_CACHE' )            || define( 'WP_CACHE', false );
defined( 'AUTOMATIC_UPDATER_DISABLED' ) || define( 'AUTOMATIC_UPDATER_DISABLED', true );

/* That's all, stop editing! Happy publishing. */

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

require_once ABSPATH . 'wp-settings.php';
