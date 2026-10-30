<?php
/**
 * جایگزینیِ نام‌ها و لینک‌های قالب در خروجیِ صفحه.
 * فقط متنِ نمایشی و چند ویژگیِ مشخص عوض می‌شود؛ نشانیِ فایل‌ها، اسکریپت‌ها و شیوه‌نامه‌ها دست نمی‌خورد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand {

	const OPTION = 'golzari_labels';

	/** عکسِ بزرگِ بالای صفحه‌ی اصلیِ قالب (کلاسِ پیوستِ آن در کتابخانه‌ی رسانه، فایلِ 2023/08/003.webp). */
	const HERO_IMG = 'wp-image-2094';

	/** ویژگی‌هایی که متنِ نمایشی دارند. */
	const TEXT_ATTRS = array( 'alt', 'title', 'placeholder', 'aria-label', 'data-title' );

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'start' ), 0 );
		add_action( 'update_option_' . self::OPTION, array( __CLASS__, 'purge' ) );
	}

	public static function defaults() {
		return array_merge( self::base_defaults(), Golzari_Brand_Content::defaults() );
	}

	private static function base_defaults() {
		return array(
			'name_fa'    => 'گلزاری',
			'name_en'    => 'golzari',
			'collection' => 'golzari pmg',
			'instagram'  => 'golzari_pmg',
			'phone'      => '09129178237',
			'footer'     => 'مجموعه golzari pmg',
			'hero'       => '1',
		);
	}

	public static function label( $key ) {
		$saved = get_option( self::OPTION, array() );
		$all   = array_merge( self::defaults(), is_array( $saved ) ? array_filter( $saved, 'strlen' ) : array() );
		return isset( $all[ $key ] ) ? (string) $all[ $key ] : '';
	}

	public static function fa_digits( $s ) {
		return strtr( (string) $s, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}

	public static function instagram_url() {
		return 'https://instagram.com/' . rawurlencode( ltrim( self::label( 'instagram' ), '@' ) ) . '/';
	}

	public static function start() {
		if ( is_admin() || wp_doing_ajax() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		Golzari_Brand_Content::$front = is_front_page();
		ob_start( array( __CLASS__, 'filter' ) );
	}

	/** خروجیِ کاملِ صفحه. هر خطایی پیش بیاید، صفحه‌ی دست‌نخورده برمی‌گردد. */
	public static function filter( $html ) {
		if ( ! is_string( $html ) || '' === $html || false === stripos( $html, '<html' ) ) {
			return $html;
		}
		$parts = preg_split( '#(<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>|<!--.*?-->|<[^>]+>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
		if ( false === $parts ) {
			return $html;
		}
		Golzari_Brand_Content::reset();
		foreach ( $parts as $i => $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( '<' !== $part[0] ) {
				$parts[ $i ] = self::text( Golzari_Brand_Content::text( $part ) );
			} elseif ( preg_match( '#^<(script|style|textarea|!--)#i', $part ) ) {
				continue;
			} else {
				$parts[ $i ] = self::tag( $part );
			}
		}
		return implode( '', $parts );
	}

	/** متنِ نمایشی. */
	public static function text( $s ) {
		if ( false !== strpos( $s, 'راست چین' ) ) {
			$footer = self::label( 'footer' );
			$s      = preg_replace_callback(
				'/(حق چاپ\s*©\s*)(\d{4})(\s*-\s*)قالب وردپرس توسط راست چین/u',
				function ( $m ) use ( $footer ) {
					return $m[1] . self::fa_digits( $m[2] ) . $m[3] . $footer;
				},
				$s
			);
			$s = str_replace( 'قالب وردپرس توسط راست چین', $footer, $s );
		}
		$s = str_replace( 'زیبانو', self::label( 'name_fa' ), $s );
		return preg_replace( '/\bzibanoo?\b/i', self::label( 'name_en' ), $s );
	}

	/** یک برچسبِ باز. */
	public static function tag( $tag ) {
		if ( 0 === strpos( $tag, '</' ) ) {
			return $tag;
		}

		// متنِ ویژگی‌های نمایشی.
		$tag = preg_replace_callback(
			'#(\s)(' . implode( '|', self::TEXT_ATTRS ) . ')=("[^"]*"|\'[^\']*\')#i',
			function ( $m ) {
				$q = $m[3][0];
				return $m[1] . $m[2] . '=' . $q . self::text( substr( $m[3], 1, -1 ) ) . $q;
			},
			$tag
		);

		if ( preg_match( '#^<img\s#i', $tag ) ) {
			return self::img( $tag );
		}
		if ( ! preg_match( '#^<a\s#i', $tag ) ) {
			return $tag;
		}

		// لینک‌های اینستاگرام به صفحه‌ی مجموعه.
		$insta = esc_url( self::instagram_url() );
		$tag   = preg_replace( '#href=(["\'])(?:https?:)?//(?:www\.)?instagram\.com/[^"\']*\1#i', 'href="' . $insta . '"', $tag );
		if ( preg_match( '#data-network=["\']instagram["\']#i', $tag ) && preg_match( '#href=["\']\#?["\']#', $tag ) ) {
			$tag = preg_replace( '#href=["\']\#?["\']#', 'href="' . $insta . '" target="_blank" rel="noopener"', $tag, 1 );
		}

		// لینک‌های سایتِ نمونه‌ی قالب به همین سایت.
		$tag = preg_replace_callback(
			'#href=(["\'])https?://(?:www\.)?smdesign\.ir/zibanoo/?([^"\']*)\1#i',
			function ( $m ) {
				return 'href="' . esc_url( home_url( '/' . trim( $m[2], '/' ) . ( '' === trim( $m[2], '/' ) ? '' : '/' ) ) ) . '"';
			},
			$tag
		);

		// لینکِ تماس.
		$tag = preg_replace( '#href=(["\'])tel:[^"\']*\1#i', 'href="tel:' . esc_attr( self::label( 'phone' ) ) . '"', $tag );

		return $tag;
	}

	/** عکسِ بالای صفحه به عکسِ خودِ مجموعه، با زمینه‌ی شفاف. */
	public static function img( $tag ) {
		if ( '1' !== self::label( 'hero' ) || ! preg_match( '#\b' . self::HERO_IMG . '\b#', $tag ) ) {
			return $tag;
		}
		$base   = plugins_url( 'assets/', GOLZARI_BRAND_DIR . 'golzari-brand.php' );
		$ver    = '?ver=' . GOLZARI_BRAND_VER;
		$src    = esc_url( $base . 'hero-1200.webp' . $ver );
		$srcset = esc_attr( $base . 'hero-600.webp' . $ver . ' 600w, ' . $base . 'hero-1200.webp' . $ver . ' 1200w' );
		$tag    = preg_replace( '#\ssrc=(["\'])[^"\']*\1#i', ' src="' . $src . '"', $tag, 1 );
		$tag    = preg_replace( '#\ssrcset=(["\'])[^"\']*\1#i', ' srcset="' . $srcset . '"', $tag, 1 );
		$tag    = preg_replace( '#\salt=(["\'])[^"\']*\1#i', ' alt="' . esc_attr( self::label( 'name_fa' ) ) . '"', $tag, 1 );
		return $tag;
	}

	/** پاک کردنِ حافظه‌ی موقتِ همین وردپرس بعد از عوض شدنِ تنظیمات. */
	public static function purge() {
		if ( class_exists( 'Golzari_Updater' ) ) {
			Golzari_Updater::purge();
			return;
		}
		do_action( 'litespeed_purge_all' );
		wp_cache_flush();
	}
}
