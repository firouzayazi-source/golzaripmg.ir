<?php
/**
 * صفحه‌ی ورود با لوگو، رنگ و فونتِ مجموعه، و برداشتنِ منوی نوبت‌پلاسِ قالب از پیشخوان
 * (نوبت‌ها حالا با سیستمِ خودمان است؛ پرونده‌های افزونه‌ی قالب دست نمی‌خورند، فقط منو پنهان می‌شود).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Admin {

	public static function init() {
		// پس از شیوه‌نامه‌ی خودِ وردپرس چاپ می‌شود تا بر آن بنشیند.
		add_action( 'login_head', array( __CLASS__, 'login_css' ), 99 );
		add_filter( 'login_headerurl', array( __CLASS__, 'login_url' ) );
		add_filter( 'login_headertext', array( __CLASS__, 'login_text' ) );
		add_action( 'admin_menu', array( __CLASS__, 'hide_nobat' ), 9999 );
	}

	public static function login_url() {
		return home_url( '/' );
	}

	public static function login_text() {
		return golzari_label( 'brand_name' );
	}

	public static function login_css() {
		$assets = plugins_url( 'assets/', GOLZARI_BRAND_DIR . 'golzari-brand.php' );
		$logo   = esc_url( Golzari_Brand_Media::url( 'logo-192' ) );
		$css    = '
@font-face{font-family:"GZ";src:url(' . esc_url( $assets . 'fonts/Vazirmatn-FD-Regular.woff2' ) . ') format("woff2");font-weight:400;font-display:swap}
@font-face{font-family:"GZ";src:url(' . esc_url( $assets . 'fonts/Vazirmatn-FD-Bold.woff2' ) . ') format("woff2");font-weight:700;font-display:swap}
body.login{font-family:"GZ",Tahoma,sans-serif;min-height:100dvh;background:radial-gradient(900px 500px at 85% -10%,rgba(201,162,74,.22),transparent 60%),linear-gradient(160deg,#2e5246,#1c3530);display:flex;flex-direction:column;justify-content:center}
body.login *{font-family:inherit}
#login{width:min(92vw,380px);padding:24px 0}
.login h1 a{background-image:url(' . $logo . ')!important;background-size:contain!important;background-position:center!important;width:96px!important;height:96px!important;margin-bottom:8px}
.login h1::after{content:"' . esc_attr( golzari_label( 'brand_name' ) ) . '";display:block;text-align:center;color:#fff;font-size:20px;font-weight:700;margin-top:4px}
.login form{border:0;border-radius:22px;padding:28px 24px;box-shadow:0 30px 70px -30px rgba(0,0,0,.6);background:#fff}
.login label{color:#1f2b28;font-size:14px}
.login input[type=text],.login input[type=password],.login input[type=email]{border:1.5px solid #dbe5e1;border-radius:12px;padding:8px 12px;font-size:16px;background:#fff;box-shadow:none}
.login input[type=text]:focus,.login input[type=password]:focus{border-color:#4f7e6e;box-shadow:0 0 0 3px #e8f0ed;outline:0}
input:-webkit-autofill{-webkit-box-shadow:0 0 0 40px #f4f8f6 inset!important}
.login .button-primary{background:linear-gradient(135deg,#d9b763,#c9a24a 55%,#a8822f)!important;border:0!important;border-radius:999px!important;padding:4px 26px!important;font-weight:700;box-shadow:0 10px 24px -10px rgba(168,130,47,.8)!important;text-shadow:none!important;min-height:42px}
.login .button.wp-hide-pw .dashicons{color:#4f7e6e}
.login input[type=checkbox]:checked::before{filter:hue-rotate(80deg)}
.login #nav,.login #backtoblog{text-align:center}
.login #nav a,.login #backtoblog a{color:#dfeae6!important}
.login #nav a:hover,.login #backtoblog a:hover{color:#e9cf8c!important}
.login .message,.login .notice,.login #login_error{border-radius:14px;border-inline-start-color:#c9a24a}
.language-switcher{display:none}
.login .privacy-policy-page-link a{color:#dfeae6}
';
		echo '<style id="golzari-login">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/** منوهای نوبت‌پلاسِ قالب در پیشخوان پنهان می‌شوند. */
	public static function hide_nobat() {
		global $menu, $submenu;
		$is_nobat = function ( $title, $slug ) {
			if ( 0 === strpos( (string) $slug, 'golzari-' ) ) {
				return false;
			}
			return (bool) preg_match( '#nobat|sab[-_]|appointment#i', (string) $slug ) || false !== strpos( wp_strip_all_tags( (string) $title ), 'نوبت پلاس' );
		};
		foreach ( (array) $menu as $item ) {
			if ( isset( $item[0], $item[2] ) && $is_nobat( $item[0], $item[2] ) ) {
				remove_menu_page( $item[2] );
			}
		}
		foreach ( (array) $submenu as $parent => $items ) {
			foreach ( (array) $items as $item ) {
				if ( isset( $item[0], $item[2] ) && $is_nobat( $item[0], $item[2] ) ) {
					remove_submenu_page( $parent, $item[2] );
				}
			}
		}
	}
}
