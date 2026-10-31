<?php
/**
 * قالبِ «گلزاری»: پشتیبانی‌های پایه و شیوه‌نامه‌ی مجموعه از افزونه.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( defined( 'GOLZARI_BRAND_DIR' ) ) {
			$url = plugins_url( 'assets/', GOLZARI_BRAND_DIR . 'golzari-brand.php' );
			wp_enqueue_style( 'golzari-home', $url . 'home.css', array(), defined( 'GOLZARI_BRAND_VER' ) ? GOLZARI_BRAND_VER : null );
			wp_enqueue_script( 'golzari-home', $url . 'home.js', array(), defined( 'GOLZARI_BRAND_VER' ) ? GOLZARI_BRAND_VER : null, true );
		}
	}
);
