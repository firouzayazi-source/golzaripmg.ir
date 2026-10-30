<?php
/**
 * عکس‌ها و رنگِ صفحه‌ی اصلی: عکس‌های نمونه‌ی قالب به عکس‌های مجموعه، گالریِ نمونه‌کارها،
 * پنهان کردنِ بخش‌ها و عکس‌های تزئینیِ بی‌کاربرد، و رنگِ سبزِ نوارِ بالای سایت به جای بنفش.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Media {

	/** سبزِ نوارِ بالای سایت. */
	const GREEN      = '#4f7e6e';
	const GREEN_DARK = '#365951';
	const GREEN_SOFT = '#e6f0ee';

	/** بخش‌هایی از صفحه‌ی اصلی که پنهان می‌شوند (شناسه‌ی صفحه‌ساز). */
	const HIDE = array(
		'4757072', // شش کارتِ ویژگی‌ها
		'74f8fcb', // شکلِ سفیدِ داخلِ دایره‌ها
		'ecbb25e', // نقطه‌های تزئینی
	);

	/** نامِ فایلِ عکسِ نمونه => عکس‌های مجموعه به ترتیبِ تکرار در صفحه. */
	private static function swaps() {
		return array(
			'makeup-artist-applies-eye-shadow' => array( 'portrait-sq', 'work-1-sq' ),
			'consectetur-adipiscing'           => array( 'remo-brow-4-sq' ),
			'slider5-1-2'                      => array( 'class-1-wide' ),
			'jarahi-chaqi-1'                   => array( 'icon-brow' ),
			'jarahi-zibaei-1'                  => array( 'icon-lip' ),
			'laser-1'                          => array( 'icon-remo' ),
		);
	}

	/** گالری: [ نامِ فایل, شماره‌ی دسته ]؛ دسته‌ها: ۰ ریمو ابرو، ۱ ریمو لب، ۲ ریمو خط چشم، ۳ آموزش. */
	private static function gallery() {
		return array(
			array( 'remo-brow-1', 0 ),
			array( 'remo-brow-2', 0 ),
			array( 'remo-brow-3', 0 ),
			array( 'remo-brow-4', 0 ),
			array( 'remo-lip', 1 ),
			array( 'remo-eyeliner', 2 ),
			array( 'work-1', 3 ),
			array( 'work-2', 3 ),
			array( 'course', 3 ),
			array( 'class-1', 3 ),
			array( 'class-2', 3 ),
			array( 'cert-1', 3 ),
			array( 'cert-2', 3 ),
		);
	}

	private static $seen = array();

	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'css' ), 100 );
	}

	public static function reset() {
		self::$seen = array();
	}

	public static function url( $name ) {
		return plugins_url( 'assets/' . $name . '.webp', GOLZARI_BRAND_DIR . 'golzari-brand.php' ) . '?ver=' . GOLZARI_BRAND_VER;
	}

	/** اندازه‌ی عکس از خودِ فایل؛ نتیجه برای هر درخواست نگه داشته می‌شود. */
	private static function size( $name ) {
		static $cache = array();
		if ( ! isset( $cache[ $name ] ) ) {
			$s              = @getimagesize( GOLZARI_BRAND_DIR . 'assets/' . $name . '.webp' );
			$cache[ $name ] = $s ? array( (int) $s[0], (int) $s[1] ) : array( 0, 0 );
		}
		return $cache[ $name ];
	}

	public static function enabled() {
		return '1' === golzari_label( 'hero' ) && Golzari_Brand_Content::$front;
	}

	/** یک برچسبِ <img> از صفحه‌ی اصلی. */
	public static function img( $tag ) {
		if ( ! self::enabled() || ! preg_match( '#\ssrc=(["\'])([^"\']*)\1#i', $tag, $m ) ) {
			return $tag;
		}
		foreach ( self::swaps() as $needle => $list ) {
			if ( false === strpos( $m[2], '/' . $needle ) ) {
				continue;
			}
			$i                     = isset( self::$seen[ $needle ] ) ? self::$seen[ $needle ] : 0;
			self::$seen[ $needle ] = $i + 1;
			$name                  = $list[ min( $i, count( $list ) - 1 ) ];
			list( $w, $h )         = self::size( $name );
			$tag                   = preg_replace( '#\ssrc=(["\'])[^"\']*\1#i', ' src="' . esc_url( self::url( $name ) ) . '"', $tag, 1 );
			$tag                   = preg_replace( '#\s(srcset|sizes)=(["\'])[^"\']*\2#i', '', $tag );
			if ( $w && $h ) {
				$tag = preg_replace( '#\swidth=(["\'])[^"\']*\1#i', ' width="' . $w . '"', $tag, 1 );
				$tag = preg_replace( '#\sheight=(["\'])[^"\']*\1#i', ' height="' . $h . '"', $tag, 1 );
			}
			return preg_replace( '#\salt=(["\'])[^"\']*\1#i', ' alt="' . esc_attr( golzari_label( 'name_fa' ) ) . '"', $tag, 1 );
		}
		return $tag;
	}

	/** گالریِ نمونه‌کارها روی کلِ صفحه، پیش از تکه‌تکه شدن. */
	public static function html( $html ) {
		if ( ! self::enabled() || false === strpos( $html, 'elementor-gallery__container' ) ) {
			return $html;
		}
		$titles = array( golzari_label( 'g0' ), golzari_label( 'g1' ), golzari_label( 'g2' ), golzari_label( 'g3' ) );
		$bar    = '<div class="elementor-gallery__titles-container e--pointer-underline e--animation-fade" aria-label="فیلتر گالری">';
		$bar   .= '<a class="elementor-item elementor-gallery-title" role="button" tabindex="0" data-gallery-index="all">' . esc_html( golzari_label( 'g_all' ) ) . '</a>';
		foreach ( $titles as $i => $t ) {
			$bar .= '<a class="elementor-item elementor-gallery-title" role="button" tabindex="0" data-gallery-index="' . $i . '">' . esc_html( $t ) . '</a>';
		}
		$bar .= '</div>';

		$items = '';
		foreach ( self::gallery() as $g ) {
			list( $w, $h ) = self::size( $g[0] );
			$url           = esc_url( self::url( $g[0] ) );
			$title         = esc_attr( $titles[ $g[1] ] );
			$items        .= '<a class="e-gallery-item elementor-gallery-item elementor-animated-content" data-e-gallery-tags="' . $g[1] . '" href="' . $url . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="golzari-works" data-elementor-lightbox-title="' . $title . '">'
				. '<div class="e-gallery-image elementor-gallery-item__image" data-thumbnail="' . $url . '" data-width="' . $w . '" data-height="' . $h . '" aria-label="' . $title . '" role="img"></div>'
				. '<div class="elementor-gallery-item__overlay"></div><div class="elementor-gallery-item__content"></div></a>';
		}

		// بارگذاریِ تأخیری خاموش تا عکس‌ها بی اسکرول بیایند.
		$out = preg_replace( '#(data-id="be17f13"[^>]*?)&quot;lazyload&quot;:&quot;yes&quot;#', '$1&quot;lazyload&quot;:&quot;no&quot;', $html, 1 );
		$out = preg_replace( '#<div class="elementor-gallery__titles-container[^"]*"[^>]*>.*?</div>#s', $bar, (string) $out, 1 );
		$out = preg_replace( '#<a class="e-gallery-item[^"]*"[^>]*>.*?</a>#s', '', (string) $out );
		$out = preg_replace( '#(<div class="elementor-gallery__container">)#', '$1' . str_replace( '$', '\\$', $items ), (string) $out, 1 );
		return is_string( $out ) && '' !== $out ? $out : $html;
	}

	/** رنگ‌ها و پنهان‌کردن‌ها؛ فقط در صفحه‌ی اصلی، جز رنگ که همه‌جا یکی است. */
	public static function css() {
		if ( is_admin() || '1' !== golzari_label( 'hero' ) ) {
			return;
		}
		$g   = self::GREEN;
		$css = ":root,.elementor-kit-5{--e-global-color-primary:{$g};--e-global-color-secondary:" . self::GREEN_DARK . ";--theme-palette-color-1:{$g};--theme-palette-color-2:" . self::GREEN_DARK . ';}';
		if ( is_front_page() ) {
			foreach ( self::HIDE as $id ) {
				$css .= ".elementor-element-{$id}{display:none!important}";
			}
			// نقطه‌های روی عکس به جایی از عکسِ تازه اشاره نمی‌کنند.
			$css .= '.elementor-element-22f7a87 .nour-hotspot-item{display:none!important}';
			$css .= '.elementor-element-22f7a87 img,.elementor-element-2ec482d img,.elementor-element-0bf1c58 img{object-fit:cover}';
			// پس‌زمینه‌ی ستاره‌ای به سبزِ ملایم.
			$css .= '.elementor-element-ed0c954:not(.elementor-motion-effects-element-type-background){background-image:linear-gradient(180deg,' . self::GREEN_SOFT . ',#f7faf9)!important}';
			// عکسِ خدمات گرد.
			$css .= '.elementor-element-aa8dada .elementor-image-box-img,.elementor-element-f4d6715 .elementor-image-box-img,.elementor-element-5c4e16c .elementor-image-box-img{width:120px!important;max-width:none!important;flex:0 0 120px}';
			$css .= '.elementor-element-aa8dada img,.elementor-element-f4d6715 img,.elementor-element-5c4e16c img{width:120px!important;height:120px!important;max-width:none!important;aspect-ratio:1/1;object-fit:cover;border-radius:50%}';
		}
		echo '<style id="golzari-brand-css">' . $css . "</style>\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
