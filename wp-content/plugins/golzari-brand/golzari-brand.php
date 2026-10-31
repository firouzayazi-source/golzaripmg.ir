<?php
/**
 * Plugin Name: گلزاری — شخصی‌سازی
 * Description: صفحه‌ی اصلی و صفحه‌ی رزروِ اختصاصیِ مجموعه‌ی گلزاری با سربرگ و پانویسِ خودمان، سیستمِ نوبت‌دهی، و جایگزینیِ نام‌ها، متن‌ها، عکس‌ها، لوگو و رنگِ قالب در بقیه‌ی صفحه‌ها؛ فقط در نمایش، بی دست زدن به قالب یا پایگاهِ داده.
 * Version: 2.2.0
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: گلزاری
 * Text Domain: golzari-brand
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOLZARI_BRAND_VER', '2.2.0' );
define( 'GOLZARI_BRAND_DIR', plugin_dir_path( __FILE__ ) );

require_once GOLZARI_BRAND_DIR . 'includes/class-content.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-media.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-home.php';
require_once GOLZARI_BRAND_DIR . 'includes/class-booking.php';
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
Golzari_Brand_Home::init();
Golzari_Brand_Booking::init();
Golzari_Brand_Settings::init();
