<?php
/**
 * Plugin Name: گلزاری — شخصی‌سازی
 * Description: نام‌ها، متن‌های نمونه، عکس‌ها، رنگ، اینستاگرام و شماره‌ی تماسِ قالب را با مشخصاتِ مجموعه‌ی گلزاری عوض می‌کند؛ فقط در خروجیِ صفحه، بی دست زدن به قالب یا پایگاهِ داده.
 * Version: 1.4.2
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: گلزاری
 * Text Domain: golzari-brand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOLZARI_BRAND_VER', '1.4.2' );
define( 'GOLZARI_BRAND_DIR', plugin_dir_path( __FILE__ ) );

require_once GOLZARI_BRAND_DIR . 'includes/class-content.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-media.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-brand.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-settings.php';

if ( ! function_exists( 'golzari_label' ) ) {
	/** هر متنی که صفحه خودش می‌سازد از این‌جا می‌گذرد و در تنظیمات قابلِ عوض کردن است. */
	function golzari_label( $key ) {
		return Golzari_Brand::label( $key );
	}
}

Golzari_Brand::init();
Golzari_Brand_Media::init();
Golzari_Brand_Settings::init();
