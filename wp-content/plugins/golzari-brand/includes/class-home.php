<?php
/**
 * صفحه‌ی اصلیِ اختصاصیِ مجموعه: سربرگ، بخش‌ها و پانویسِ خودمان به جای صفحه‌ی قالب.
 * قالب دست نمی‌خورد؛ فقط برای صفحه‌ی اصلی قالبِ نمایشِ دیگری از همین افزونه انتخاب می‌شود.
 * با برداشتنِ تیکِ «صفحه‌ی اصلیِ اختصاصی» در تنظیمات، صفحه‌ی قبلی برمی‌گردد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Home {

	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 999 );
		// پرونده‌هایی که دیرتر صف می‌شوند (مثلِ آیکون‌های صفحه‌ساز) پیش از چاپ هم برداشته می‌شوند.
		add_action( 'wp_print_styles', array( __CLASS__, 'assets' ), 999 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'assets' ), 0 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ), 999 );
	}

	/** کلاس‌های قالب و صفحه‌ساز روی بدنه‌ی صفحه نمی‌آیند. */
	public static function body_class( $classes ) {
		if ( ! self::active() ) {
			return $classes;
		}
		return array_values( array_intersect( $classes, array( 'rtl', 'home', 'logged-in', 'admin-bar', 'gz' ) ) );
	}

	/** ردهایی از قالب که با صف برداشته نمی‌شوند: پیوندِ داخلِ noscript، برچسبِ سازنده و شیوه‌نامه‌های درون‌خطیِ رنگِ قالب. */
	public static function clean( $html ) {
		if ( ! self::active() ) {
			return $html;
		}
		$html = preg_replace( '#<noscript>\s*<link[^>]*(?:/themes/|zibanoo)[^>]*>\s*</noscript>#i', '', $html );
		$html = preg_replace( '#<meta name="generator" content="Elementor[^>]*>#i', '', $html );
		$html = preg_replace( '#<link[^>]*(?:/plugins/elementor|/themes/|zibanoo)[^>]*>#i', '', $html );
		$html = preg_replace_callback(
			'#<style\b[^>]*>.*?</style>#is',
			function ( $m ) {
				return preg_match( '#noorgfx|--e-global|elementor|zibanoo#i', $m[0] ) ? '' : $m[0];
			},
			$html
		);
		// اسکریپت‌های درون‌خطی که بی صفحه‌ساز کاری ندارند.
		$html = preg_replace_callback(
			'#<script\b(?![^>]*\bsrc=)(?![^>]*speculationrules)[^>]*>.*?</script>#is',
			function ( $m ) {
				return preg_match( '#elementor#i', $m[0] ) ? '' : $m[0];
			},
			$html
		);
		return $html;
	}

	public static function active() {
		return ! is_admin() && is_front_page() && '1' === golzari_label( 'home' );
	}

	public static function defaults() {
		return array(
			'home'           => '1',
			'brand_name'     => 'مجموعه گلزاری',
			'h_kicker'       => 'golzari pmg',
			'h_title'        => 'مجموعه آرایش دائم گلزاری',
			'h_cta'          => 'رزرو نوبت',
			'h_cta2'         => 'نمونه‌کارها',
			'stat1_n'        => '+۱۵',
			'stat1_t'        => 'سال سابقه',
			'stat2_n'        => '+۵۰۰',
			'stat2_t'        => 'هنرجوی فعال',
			'stat3_n'        => 'فی',
			'stat3_t'        => 'مدرک آکادمی فی',
			'nav_home'       => 'خانه',
			'nav_services'   => 'خدمات',
			'nav_works'      => 'نمونه‌کارها',
			'nav_academy'    => 'آموزش',
			'nav_about'      => 'درباره ما',
			'nav_contact'    => 'تماس',
			'about_kicker'   => 'درباره ما',
			'serv_kicker'    => 'خدمات',
			'works_kicker'   => 'نمونه‌کارها',
			'works_title'    => 'قبل و بعد از کار',
			'acad_kicker'    => 'آموزش',
			'acad_title'     => 'آموزش حرفه‌ای ریمو و آرایش دائم',
			'acad_text'      => 'آموزش بیش از ۵۰۰ نفر هنرجوی فعال؛ مدرس ریمو و آرایش دائم با مواد اصلی و اورجینال فی.',
			'acad_cta'       => 'مشاوره‌ی دوره',
			'cta_title'      => 'همین حالا نوبت بگیرید',
			'cta_text'       => 'برای مشاوره و رزرو نوبت تماس بگیرید یا فرمِ رزرو را پر کنید.',
			'address'        => 'قم، میثم بالا، کوچه ۵، پلاک ۸',
			'phone2'         => '۰۲۵-۳۲۹۲۶۶۷۲',
			'foot_about'     => 'تخصص ریمو و آرایش دائم لب و ابرو با بیش از ۱۵ سال سابقه و مواد اصلی و اورجینال فی.',
			'foot_links'     => 'دسترسی سریع',
			'foot_contact'   => 'تماس با ما',
			'booking_path'   => '/booking/',
		);
	}

	public static function titles() {
		return array(
			'home'         => 'صفحه‌ی اصلیِ اختصاصی',
			'brand_name'   => 'نامِ مجموعه در سربرگ و پانویس',
			'h_kicker'     => 'صفحه‌ی اصلی: نوشته‌ی کوچکِ بالای عنوان',
			'h_title'      => 'صفحه‌ی اصلی: عنوان',
			'h_cta'        => 'دکمه‌ی رزرو',
			'h_cta2'       => 'دکمه‌ی نمونه‌کارها',
			'stat1_n'      => 'آمارِ ۱: عدد',
			'stat1_t'      => 'آمارِ ۱: عنوان',
			'stat2_n'      => 'آمارِ ۲: عدد',
			'stat2_t'      => 'آمارِ ۲: عنوان',
			'stat3_n'      => 'آمارِ ۳: عدد',
			'stat3_t'      => 'آمارِ ۳: عنوان',
			'nav_home'     => 'منو: خانه',
			'nav_services' => 'منو: خدمات',
			'nav_works'    => 'منو: نمونه‌کارها',
			'nav_academy'  => 'منو: آموزش',
			'nav_about'    => 'منو: درباره ما',
			'nav_contact'  => 'منو: تماس',
			'about_kicker' => 'بخشِ درباره: نوشته‌ی کوچک',
			'serv_kicker'  => 'بخشِ خدمات: نوشته‌ی کوچک',
			'works_kicker' => 'بخشِ نمونه‌کارها: نوشته‌ی کوچک',
			'works_title'  => 'بخشِ نمونه‌کارها: عنوان',
			'acad_kicker'  => 'بخشِ آموزش: نوشته‌ی کوچک',
			'acad_title'   => 'بخشِ آموزش: عنوان',
			'acad_text'    => 'بخشِ آموزش: متن',
			'acad_cta'     => 'بخشِ آموزش: دکمه',
			'cta_title'    => 'بخشِ رزرو: عنوان',
			'cta_text'     => 'بخشِ رزرو: متن',
			'address'      => 'نشانی',
			'phone2'       => 'تلفنِ ثابت',
			'foot_about'   => 'پانویس: معرفیِ کوتاه',
			'foot_links'   => 'پانویس: عنوانِ پیوندها',
			'foot_contact' => 'پانویس: عنوانِ تماس',
			'booking_path' => 'نشانیِ صفحه‌ی رزرو',
		);
	}

	public static function template( $template ) {
		return self::active() ? GOLZARI_BRAND_DIR . 'templates/home.php' : $template;
	}

	/** روی این صفحه فقط شیوه‌نامه و اسکریپتِ خودمان؛ هیچ پرونده‌ای از قالب و صفحه‌سازِ قالب. */
	public static function assets() {
		if ( ! self::active() ) {
			return;
		}
		global $wp_styles, $wp_scripts;
		foreach ( array( $wp_styles, $wp_scripts ) as $deps ) {
			if ( ! $deps ) {
				continue;
			}
			foreach ( (array) $deps->queue as $handle ) {
				$src = isset( $deps->registered[ $handle ] ) ? (string) $deps->registered[ $handle ]->src : '';
				if ( preg_match( '#/themes/|zibanoo|elementor|nour-|noorgfx#i', $src . ' ' . $handle ) ) {
					$deps === $wp_styles ? wp_dequeue_style( $handle ) : wp_dequeue_script( $handle );
				}
			}
		}
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'wp-block-library' );
		$url = plugins_url( 'assets/', GOLZARI_BRAND_DIR . 'golzari-brand.php' );
		wp_enqueue_style( 'golzari-home', $url . 'home.css', array(), GOLZARI_BRAND_VER );
		wp_enqueue_script( 'golzari-home', $url . 'home.js', array(), GOLZARI_BRAND_VER, true );
	}

	public static function img( $name ) {
		return esc_url( Golzari_Brand_Media::url( $name ) );
	}

	/** سالِ شمسیِ امروز (برای پانویس). */
	public static function jalali_year() {
		$y = (int) gmdate( 'Y' );
		$md = (int) gmdate( 'nd' );
		return Golzari_Brand::fa_digits( $md >= 321 ? $y - 621 : $y - 622 );
	}

	public static function tel( $s ) {
		return 'tel:' . preg_replace( '/[^0-9+]/', '', strtr( $s, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' ) ) );
	}
}
