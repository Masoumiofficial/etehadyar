<?php
/**
 * پاک‌سازی تنظیمات هنگام حذف پلاگین اتحادیار.
 *
 * @package Etehadyar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'etehadyar_options' );
