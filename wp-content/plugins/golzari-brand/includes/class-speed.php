<?php
/**
 * سرعت: حذفِ بارهای اضافه‌ی وردپرس، پیش‌بارگذاریِ قلم و عکسِ اول،
 * و حافظه‌ی موقتِ صفحه‌ی لایت‌اسپید فقط برای بازدیدکننده‌ی مهمان.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Speed {

	const PENDING = 'golzari_lscache_purge';
	const TTL     = 14400;

	public static function init() {
		// اسکریپت و شیوه‌نامه‌ی شکلک از سرورِ خارجی؛ مرورگرها خودشان شکلک دارند.
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_enqueue_scripts', 'wp_enqueue_emoji_styles' );
		add_filter( 'emoji_svg_url', '__return_false' );

		// پیوندهای بی‌کاربرد در سرِ صفحه.
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'wp_generator' );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'dequeue' ), 100 );
		add_action( 'wp_head', array( __CLASS__, 'preload' ), 2 );

		// حافظه‌ی موقتِ صفحه.
		add_action( 'template_redirect', array( __CLASS__, 'cache_headers' ), 999 );
		add_action( 'init', array( __CLASS__, 'flush_pending' ), 1 );
		add_action( 'golzari_purged', array( __CLASS__, 'purge' ) );
		add_action( 'update_option_golzari_booking_opts', array( __CLASS__, 'purge' ) );
		add_action( 'save_post', array( __CLASS__, 'purge_post' ), 10, 2 );
		add_action( 'set_logged_in_cookie', array( __CLASS__, 'vary_on' ) );
		add_action( 'wp_logout', array( __CLASS__, 'vary_off' ) );
	}

	private static function ours() {
		return '' !== Golzari_Brand_Home::page() || 'golzari-site' === get_stylesheet();
	}

	public static function dequeue() {
		if ( ! self::ours() ) {
			return;
		}
		foreach ( array( 'classic-theme-styles', 'wp-block-library', 'wp-block-library-theme', 'global-styles' ) as $h ) {
			wp_dequeue_style( $h );
		}
	}

	public static function preload() {
		$page = Golzari_Brand_Home::page();
		if ( '' === $page && 'golzari-site' !== get_stylesheet() ) {
			return;
		}
		$fonts = plugins_url( 'assets/fonts/', GOLZARI_BRAND_DIR . 'golzari-brand.php' );
		foreach ( array( 'Regular', 'Black' ) as $w ) {
			echo '<link rel="preload" href="' . esc_url( $fonts . 'Vazirmatn-FD-' . $w . '.woff2' ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
		}
		if ( 'home' === $page ) {
			$a = Golzari_Brand_Media::url( 'hero-cut-420' );
			$b = Golzari_Brand_Media::url( 'hero-cut' );
			echo '<link rel="preload" as="image" href="' . esc_url( $a ) . '" imagesrcset="' . esc_attr( $a . ' 420w, ' . $b . ' 647w' ) . '" imagesizes="(max-width: 900px) 78vw, 480px" fetchpriority="high">' . "\n";
		}
	}

	/** فقط صفحه‌های عمومیِ خودمان، فقط برای مهمان، فقط درخواستِ خواندنی. */
	public static function cache_headers() {
		if ( headers_sent() ) {
			return;
		}
		if ( is_user_logged_in() ) {
			header( 'X-LiteSpeed-Cache-Control: no-cache' );
			return;
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
		if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) || is_admin() || is_preview() || is_404() || is_search() || is_feed() || post_password_required() ) {
			return;
		}
		if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		foreach ( array_keys( $_COOKIE ) as $c ) {
			if ( 0 === strpos( $c, 'comment_author' ) || 0 === strpos( $c, 'wp-postpass' ) ) {
				return;
			}
		}
		if ( ! self::ours() || ! ( is_front_page() || is_singular() ) ) {
			return;
		}
		header( 'X-LiteSpeed-Cache-Control: public,max-age=' . self::TTL );
		header( 'X-LiteSpeed-Tag: golzari' );
	}

	/** پاک کردنِ حافظه‌ی موقتِ صفحه‌های همین سایت. */
	public static function purge() {
		if ( ! headers_sent() ) {
			header( 'X-LiteSpeed-Purge: tag=golzari' );
		}
		// برای اطمینان، درخواستِ بعدیِ بی‌حافظه هم یک بار پاک می‌کند.
		update_option( self::PENDING, 1, false );
	}

	public static function flush_pending() {
		if ( get_option( self::PENDING ) && ! headers_sent() ) {
			header( 'X-LiteSpeed-Purge: tag=golzari' );
			delete_option( self::PENDING );
		}
	}

	public static function purge_post( $id, $post ) {
		if ( wp_is_post_revision( $id ) || 'golzari_booking' === $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}
		self::purge();
	}

	/** مدیرِ واردشده نسخه‌ی مهمان را نمی‌گیرد. */
	public static function vary_on() {
		if ( ! headers_sent() ) {
			setcookie( '_lscache_vary', 'golzari', 0, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	public static function vary_off() {
		if ( ! headers_sent() ) {
			setcookie( '_lscache_vary', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
		}
	}
}
