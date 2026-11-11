<?php
/**
 * Remove plugin settings on uninstall. Usage history and translated posts are kept.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'qpat_settings' );
