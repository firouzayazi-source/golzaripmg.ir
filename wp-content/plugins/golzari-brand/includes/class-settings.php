<?php
/**
 * تنظیماتِ شخصی‌سازی: پیشخوان ← تنظیمات ← شخصی‌سازیِ گلزاری.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Settings {

	const PAGE = 'golzari-brand';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	public static function fields() {
		return array_merge( self::base_fields(), Golzari_Brand_Home::titles(), Golzari_Brand_Booking::titles(), Golzari_Brand_Content::titles() );
	}

	private static function base_fields() {
		return array(
			'name_fa'    => 'نامِ فارسی (به جای «زیبانو»)',
			'name_en'    => 'نامِ لاتین',
			'collection' => 'نامِ مجموعه',
			'instagram'  => 'نامِ کاربریِ اینستاگرام',
			'phone'      => 'شماره‌ی تماس',
			'footer'     => 'متنِ پایینِ صفحه (به جای «قالب وردپرس توسط راست چین»)',
			'hero'       => 'عکسِ بالای صفحه‌ی اصلی',
		);
	}

	public static function menu() {
		add_options_page( 'شخصی‌سازیِ گلزاری', 'شخصی‌سازیِ گلزاری', 'manage_options', self::PAGE, array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting(
			self::PAGE,
			Golzari_Brand::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize( $in ) {
		$out = array();
		foreach ( array_keys( self::fields() ) as $key ) {
			$v = isset( $in[ $key ] ) ? sanitize_text_field( wp_unslash( $in[ $key ] ) ) : '';
			if ( in_array( $key, array( 'hero', 'home' ), true ) ) {
				$v = '1' === $v ? '1' : '0';
			} elseif ( 'instagram' === $key ) {
				$v = preg_replace( '/[^A-Za-z0-9._]/', '', ltrim( $v, '@' ) );
			} elseif ( 'phone' === $key ) {
				$v = preg_replace( '/[^0-9+]/', '', strtr( $v, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9' ) ) );
			}
			$out[ $key ] = $v;
		}
		return $out;
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$defaults = Golzari_Brand::defaults();
		echo '<div class="wrap"><h1>شخصی‌سازیِ گلزاری</h1>';
		echo '<p>این مقدارها فقط در نمایشِ صفحه جایگزین می‌شوند؛ قالب و محتوای ذخیره‌شده دست نمی‌خورد. خانه‌ی خالی یعنی مقدارِ پیش‌فرض.</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( self::PAGE );
		echo '<table class="form-table" role="presentation">';
		foreach ( self::fields() as $key => $title ) {
			$checks = array(
				'hero' => 'عکس‌ها، لوگو و رنگِ مجموعه به جای قالب',
				'home' => 'صفحه‌ی اصلیِ اختصاصی با سربرگ و پانویسِ مجموعه',
			);
			if ( isset( $checks[ $key ] ) ) {
				printf(
					'<tr><th scope="row">%1$s</th><td><input type="hidden" name="%2$s[%5$s]" value="0"><label><input type="checkbox" name="%2$s[%5$s]" value="1"%3$s> %4$s</label></td></tr>',
					esc_html( $title ),
					esc_attr( Golzari_Brand::OPTION ),
					checked( Golzari_Brand::label( $key ), '1', false ),
					esc_html( $checks[ $key ] ),
					esc_attr( $key )
				);
				continue;
			}
			printf(
				'<tr><th scope="row"><label for="golzari-%1$s">%2$s</label></th><td><input type="text" class="regular-text" id="golzari-%1$s" name="%3$s[%1$s]" value="%4$s" placeholder="%5$s" dir="auto"></td></tr>',
				esc_attr( $key ),
				esc_html( $title ),
				esc_attr( Golzari_Brand::OPTION ),
				esc_attr( Golzari_Brand::label( $key ) ),
				esc_attr( $defaults[ $key ] )
			);
		}
		echo '</table>';
		submit_button( 'ذخیره' );
		echo '</form></div>';
	}
}
