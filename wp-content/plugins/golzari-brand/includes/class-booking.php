<?php
/**
 * سیستمِ نوبت‌دهیِ مجموعه: ساعت‌های آزاد، ثبتِ نوبت، جلوگیری از رزروِ تکراری،
 * فهرست و تنظیماتِ روز و ساعتِ کاری در پیشخوان.
 *
 * نوبت‌ها نوشته‌ی سفارشیِ golzari_booking هستند (بی نمایش در سایت). ساعت‌های آزاد همیشه زنده از
 * نشانیِ عمومی خوانده می‌شوند و حافظه‌ی موقت ندارند، پس هر نوبتِ تازه بی‌درنگ دیده می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Booking {

	const CPT    = 'golzari_booking';
	const OPTS   = 'golzari_booking_opts';
	const NS     = 'golzari/v1';
	const STATUS = array( 'pending', 'confirmed', 'done', 'cancelled' );

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_golzari_booking_status', array( __CLASS__, 'admin_status' ) );
		add_action( 'admin_post_golzari_booking_opts', array( __CLASS__, 'admin_opts' ) );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'public'          => false,
				'show_ui'         => false,
				'rewrite'         => false,
				'query_var'       => false,
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
			)
		);
	}

	/* ---------- برچسب‌ها ---------- */

	public static function defaults() {
		return array(
			'b_title'      => 'رزرو نوبت',
			'b_lead'       => 'خدمت، روز و ساعتِ دلخواه را انتخاب کنید؛ پس از ثبت، برای هماهنگیِ نهایی با شما تماس می‌گیریم.',
			'b_step1'      => 'خدمت',
			'b_step_staff' => 'پیگمنتر',
			'b_step2'      => 'روز',
			'b_step3'      => 'ساعت',
			'b_step4'      => 'مشخصات',
			'b_name'       => 'نام و نام خانوادگی',
			'b_phone'      => 'شماره‌ی همراه',
			'b_note'       => 'توضیح (اختیاری)',
			'b_submit'     => 'ثبتِ نوبت',
			'b_loading'    => 'در حالِ خواندنِ ساعت‌های آزاد…',
			'b_nofree'     => 'برای این روز ساعتِ آزادی نمانده؛ روزِ دیگری را انتخاب کنید.',
			'b_closed'     => 'تعطیل',
			'b_done_title' => 'نوبتِ شما ثبت شد',
			'b_done_text'  => 'برای هماهنگیِ نهایی به‌زودی با شما تماس می‌گیریم.',
			'b_err_phone'  => 'شماره‌ی همراه را درست وارد کنید (مثلِ ۰۹۱۲۱۲۳۴۵۶۷).',
			'b_err_name'   => 'نام را وارد کنید.',
			'b_err_pick'   => 'خدمت، پیگمنتر، روز و ساعت را انتخاب کنید.',
			'b_err_taken'  => 'این ساعت همین حالا رزرو شد؛ ساعتِ دیگری انتخاب کنید.',
			'b_err_limit'  => 'تعدادِ درخواست‌ها زیاد است؛ کمی بعد دوباره امتحان کنید یا تماس بگیرید.',
			'b_err_net'    => 'ارتباط برقرار نشد؛ دوباره امتحان کنید یا تماس بگیرید.',
			'b_side_title' => 'پیش از مراجعه',
			'b_side_1'     => 'لطفاً ده دقیقه زودتر تشریف بیاورید.',
			'b_side_2'     => 'برای لغو یا جابه‌جاییِ نوبت تماس بگیرید.',
			'b_side_3'     => 'همه‌ی کارها با مواد اصلی و اورجینال فی انجام می‌شود.',
		);
	}

	public static function titles() {
		$t = array();
		foreach ( array_keys( self::defaults() ) as $k ) {
			$t[ $k ] = 'رزرو: ' . self::defaults()[ $k ];
		}
		return $t;
	}

	/** پیام‌هایی که اسکریپتِ صفحه‌ی رزرو لازم دارد. */
	public static function js_labels() {
		$out = array();
		foreach ( array( 'b_loading', 'b_nofree', 'b_closed', 'b_done_title', 'b_done_text', 'b_err_phone', 'b_err_name', 'b_err_pick', 'b_err_taken', 'b_err_limit', 'b_err_net', 'b_submit', 'b_step1', 'b_step_staff', 'b_step2', 'b_step3', 'b_name' ) as $k ) {
			$out[ $k ] = golzari_label( $k );
		}
		return $out;
	}

	/** خدمت‌های قابلِ رزرو: کلید => نام (از تنظیماتِ نوبت). */
	public static function services() {
		return (array) self::opts()['services'];
	}

	/** پیگمنترها: کلید => نام (از تنظیماتِ نوبت). نخستین، پیش‌فرض است. */
	public static function staff() {
		return (array) self::opts()['staff'];
	}

	/** پیگمنترِ یک نوبت؛ نوبت‌های پیش از افزوده شدنِ پیگمنتر به نخستین پیگمنتر تعلق دارند. */
	private static function staff_of( $id ) {
		$k = (string) get_post_meta( $id, '_gz_staff', true );
		if ( '' === $k ) {
			$keys = array_keys( self::staff() );
			$k    = $keys ? (string) $keys[0] : '';
		}
		return $k;
	}

	/* ---------- تنظیمات ---------- */

	public static function opts() {
		$o = get_option( self::OPTS, array() );
		return wp_parse_args(
			is_array( $o ) ? $o : array(),
			array(
				'days'     => array( 6, 0, 1, 2, 3, 4 ), // شنبه تا پنجشنبه؛ جمعه تعطیل.
				'start'    => '10:00',
				'end'      => '19:00',
				'slot'     => 60,
				'ahead'    => 14,
				'lead'     => 2,
				'capacity' => 1,
				'notify'   => '1',
				'services' => array(
					'remo-brow'  => 'ریمو ابرو',
					'remo-lip'   => 'ریمو لب',
					'remo-liner' => 'ریمو خط چشم',
					'fibrosis'   => 'فیبروز ابرو',
					'general'    => 'خدمات عمومی',
				),
				'staff'    => array(
					'golzari' => 'سمیه گلزاری',
				),
				'closed'   => array(),
			)
		);
	}

	/** نوشتنِ یک تنظیم (از ربات)؛ بقیه دست نمی‌خورند. */
	public static function set_opt( $key, $value ) {
		$o         = self::opts();
		$o[ $key ] = $value;
		if ( 'closed' === $key ) {
			// روزهای گذشته از فهرستِ بسته‌ها پاک می‌شوند.
			$today     = ( new DateTimeImmutable( 'today', wp_timezone() ) )->format( 'Y-m-d' );
			$o[ $key ] = array_values( array_filter( (array) $value, function ( $d ) use ( $today ) { return $d >= $today; } ) );
		}
		update_option( self::OPTS, $o, false );
	}

	/* ---------- تاریخ ---------- */

	public static function g2j( $gy, $gm, $gd ) {
		$g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );
		$gy2   = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 ) + intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];
		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;
		if ( $days > 365 ) {
			$jy  += intdiv( $days - 1, 365 );
			$days = ( $days - 1 ) % 365;
		}
		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}
		return array( $jy, $jm, $jd );
	}

	const MONTHS   = array( 1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	const WEEKDAYS = array( 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه' );

	/** «شنبه ۱۰ آبان ۱۴۰۵» برای تاریخِ Y-m-d. */
	public static function jdate( $ymd, $with_year = true ) {
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
		if ( ! $d ) {
			return $ymd;
		}
		list( $jy, $jm, $jd ) = self::g2j( (int) $d->format( 'Y' ), (int) $d->format( 'n' ), (int) $d->format( 'j' ) );
		$s = self::WEEKDAYS[ (int) $d->format( 'w' ) ] . ' ' . $jd . ' ' . self::MONTHS[ $jm ] . ( $with_year ? ' ' . $jy : '' );
		return Golzari_Brand::fa_digits( $s );
	}

	/* ---------- ساعت‌های آزاد ---------- */

	/** شمارِ نوبت‌های فعالِ یک پیگمنتر در هر «تاریخ ساعت» میانِ دو تاریخ. */
	private static function taken( $from, $to, $staff ) {
		$q   = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'private',
				'posts_per_page' => 500,
				'no_found_rows'  => true,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array(
						'key'     => '_gz_date',
						'value'   => array( $from, $to ),
						'compare' => 'BETWEEN',
						'type'    => 'CHAR',
					),
					array(
						'key'     => '_gz_status',
						'value'   => 'cancelled',
						'compare' => '!=',
					),
				),
			)
		);
		$out = array();
		foreach ( $q->posts as $id ) {
			if ( self::staff_of( $id ) !== $staff ) {
				continue;
			}
			$k         = get_post_meta( $id, '_gz_date', true ) . ' ' . get_post_meta( $id, '_gz_time', true );
			$out[ $k ] = ( isset( $out[ $k ] ) ? $out[ $k ] : 0 ) + 1;
		}
		return $out;
	}

	/** ساعت‌های هر روزِ کاری، با آزاد یا پر بودن. */
	public static function availability( $staff ) {
		$o     = self::opts();
		$tz    = wp_timezone();
		$now   = new DateTimeImmutable( 'now', $tz );
		$min   = $now->modify( '+' . (int) $o['lead'] . ' hours' );
		$today = $now->setTime( 0, 0 );
		$last  = $today->modify( '+' . ( (int) $o['ahead'] - 1 ) . ' days' );
		$taken = self::taken( $today->format( 'Y-m-d' ), $last->format( 'Y-m-d' ), $staff );
		list( $sh, $sm ) = array_map( 'intval', explode( ':', $o['start'] ) );
		list( $eh, $em ) = array_map( 'intval', explode( ':', $o['end'] ) );
		$days = array();
		for ( $i = 0; $i < (int) $o['ahead']; $i++ ) {
			$day  = $today->modify( "+{$i} days" );
			$ymd  = $day->format( 'Y-m-d' );
			$open = in_array( (int) $day->format( 'w' ), array_map( 'intval', (array) $o['days'] ), true ) && ! in_array( $ymd, (array) $o['closed'], true );
			$slots = array();
			if ( $open ) {
				$t   = $day->setTime( $sh, $sm );
				$end = $day->setTime( $eh, $em );
				while ( $t->modify( '+' . (int) $o['slot'] . ' minutes' ) <= $end ) {
					$hm      = $t->format( 'H:i' );
					$used    = isset( $taken[ "$ymd $hm" ] ) ? $taken[ "$ymd $hm" ] : 0;
					$slots[] = array(
						't'    => $hm,
						'l'    => Golzari_Brand::fa_digits( $hm ),
						'free' => $t >= $min && $used < (int) $o['capacity'],
					);
					$t = $t->modify( '+' . (int) $o['slot'] . ' minutes' );
				}
			}
			$days[] = array(
				'd'     => $ymd,
				'w'     => self::WEEKDAYS[ (int) $day->format( 'w' ) ],
				'l'     => self::jdate( $ymd, false ),
				'open'  => $open,
				'slots' => $slots,
			);
		}
		return $days;
	}

	/* ---------- نشانی‌های عمومی ---------- */

	public static function routes() {
		register_rest_route(
			self::NS,
			'/booking',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'rest_get' ),
					'permission_callback' => '__return_true',
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'rest_post' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/** پاسخِ بی حافظه‌ی موقت (هم وردپرس، هم لایت‌اسپید). */
	private static function fresh( $data, $status = 200 ) {
		do_action( 'litespeed_control_set_nocache', 'golzari booking' );
		$r = new WP_REST_Response( $data, $status );
		$r->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$r->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
		return $r;
	}

	public static function rest_get( WP_REST_Request $req ) {
		$staff = self::staff();
		$pick  = sanitize_key( (string) $req->get_param( 'staff' ) );
		if ( ! isset( $staff[ $pick ] ) ) {
			$keys = array_keys( $staff );
			$pick = $keys ? (string) $keys[0] : '';
		}
		return self::fresh(
			array(
				'services' => self::services(),
				'staff'    => $staff,
				'current'  => $pick,
				'days'     => '' === $pick ? array() : self::availability( $pick ),
			)
		);
	}

	public static function normalize_phone( $s ) {
		$s = strtr( (string) $s, array( '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9' ) );
		$s = preg_replace( '/\D+/', '', $s );
		if ( 0 === strpos( $s, '98' ) && 12 === strlen( $s ) ) {
			$s = '0' . substr( $s, 2 );
		} elseif ( 10 === strlen( $s ) && '9' === $s[0] ) {
			$s = '0' . $s;
		}
		return preg_match( '/^09\d{9}$/', $s ) ? $s : '';
	}

	public static function rest_post( WP_REST_Request $req ) {
		$err = function ( $key, $code = 400 ) {
			return self::fresh( array( 'ok' => false, 'error' => $key, 'message' => golzari_label( $key ) ), $code );
		};

		// تله‌ی ربات: خانه‌ی پنهان باید خالی بماند و فرم دست‌کم سه ثانیه باز بوده باشد.
		if ( '' !== (string) $req->get_param( 'website' ) || (int) $req->get_param( 'elapsed' ) < 3 ) {
			return $err( 'b_err_net' );
		}

		$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key = 'gz_bk_' . md5( $ip );
		$n   = (int) get_transient( $key );
		if ( $n >= 4 ) {
			return $err( 'b_err_limit', 429 );
		}

		$name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
		$phone   = self::normalize_phone( $req->get_param( 'phone' ) );
		$note    = sanitize_textarea_field( (string) $req->get_param( 'note' ) );
		$service = sanitize_key( (string) $req->get_param( 'service' ) );
		$staff   = sanitize_key( (string) $req->get_param( 'staff' ) );
		$people  = self::staff();
		$date    = (string) $req->get_param( 'date' );
		$time    = (string) $req->get_param( 'time' );
		$servs   = self::services();

		if ( mb_strlen( $name ) < 2 ) {
			return $err( 'b_err_name' );
		}
		if ( '' === $phone ) {
			return $err( 'b_err_phone' );
		}
		if ( ! isset( $servs[ $service ] ) || ! isset( $people[ $staff ] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
			return $err( 'b_err_pick' );
		}

		// ساعت باید همین حالا در فهرستِ آزادها باشد.
		$free = false;
		foreach ( self::availability( $staff ) as $d ) {
			if ( $d['d'] !== $date ) {
				continue;
			}
			foreach ( $d['slots'] as $s ) {
				if ( $s['t'] === $time && $s['free'] ) {
					$free = true;
				}
			}
		}
		if ( ! $free ) {
			return $err( 'b_err_taken', 409 );
		}

		$id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'private',
				'post_title'  => $name . ' — ' . $date . ' ' . $time . ' — ' . ( isset( $people[ $staff ] ) ? $people[ $staff ] : '' ),
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			return $err( 'b_err_net', 500 );
		}
		update_post_meta( $id, '_gz_date', $date );
		update_post_meta( $id, '_gz_time', $time );
		update_post_meta( $id, '_gz_name', $name );
		update_post_meta( $id, '_gz_phone', $phone );
		update_post_meta( $id, '_gz_service', $service );
		update_post_meta( $id, '_gz_service_name', $servs[ $service ] );
		update_post_meta( $id, '_gz_staff', $staff );
		update_post_meta( $id, '_gz_note', $note );
		update_post_meta( $id, '_gz_status', 'pending' );

		// دو درخواستِ هم‌زمان برای یک ساعت: اگر از ظرفیت گذشت، این یکی پس گرفته می‌شود.
		$taken = self::taken( $date, $date, $staff );
		if ( ( isset( $taken[ "$date $time" ] ) ? $taken[ "$date $time" ] : 0 ) > (int) self::opts()['capacity'] ) {
			wp_delete_post( $id, true );
			return $err( 'b_err_taken', 409 );
		}

		set_transient( $key, $n + 1, HOUR_IN_SECONDS );
		self::notify( $id );
		do_action( 'golzari_booking_created', $id );

		return self::fresh(
			array(
				'ok'      => true,
				'summary' => array(
					'service' => $servs[ $service ],
					'staff'   => $people[ $staff ],
					'date'    => self::jdate( $date ),
					'time'    => Golzari_Brand::fa_digits( $time ),
					'name'    => $name,
				),
			)
		);
	}

	private static function notify( $id ) {
		if ( '1' !== self::opts()['notify'] ) {
			return;
		}
		$body  = implode(
			"\n",
			array(
				'نوبتِ تازه:',
				'نام: ' . get_post_meta( $id, '_gz_name', true ),
				'همراه: ' . get_post_meta( $id, '_gz_phone', true ),
				'خدمت: ' . self::service_name( $id ),
				'پیگمنتر: ' . self::staff_name( $id ),
				'زمان: ' . self::jdate( get_post_meta( $id, '_gz_date', true ) ) . ' ساعتِ ' . Golzari_Brand::fa_digits( get_post_meta( $id, '_gz_time', true ) ),
				'توضیح: ' . get_post_meta( $id, '_gz_note', true ),
				'',
				admin_url( 'admin.php?page=golzari-bookings' ),
			)
		);
		wp_mail( get_option( 'admin_email' ), 'نوبتِ تازه — ' . get_post_meta( $id, '_gz_name', true ), $body );
	}

	/** نامِ خدمت؛ اگر خدمت بعداً از فهرست برداشته شد، نامِ ذخیره‌شده. */
	public static function service_name( $id ) {
		$servs = self::services();
		$k     = (string) get_post_meta( $id, '_gz_service', true );
		$saved = (string) get_post_meta( $id, '_gz_service_name', true );
		return isset( $servs[ $k ] ) ? $servs[ $k ] : ( '' !== $saved ? $saved : $k );
	}

	public static function staff_name( $id ) {
		$staff = self::staff();
		$k     = self::staff_of( $id );
		return isset( $staff[ $k ] ) ? $staff[ $k ] : $k;
	}

	/* ---------- پیشخوان ---------- */

	public static function menu() {
		add_menu_page( 'نوبت‌ها', 'نوبت‌ها', 'edit_posts', 'golzari-bookings', array( __CLASS__, 'page_list' ), 'dashicons-calendar-alt', 3 );
		add_submenu_page( 'golzari-bookings', 'نوبت‌ها', 'همه‌ی نوبت‌ها', 'edit_posts', 'golzari-bookings', array( __CLASS__, 'page_list' ) );
		add_submenu_page( 'golzari-bookings', 'تنظیماتِ نوبت', 'تنظیماتِ نوبت', 'manage_options', 'golzari-booking-opts', array( __CLASS__, 'page_opts' ) );
	}

	public static function status_label( $s ) {
		$l = array(
			'pending'   => 'در انتظارِ تأیید',
			'confirmed' => 'تأییدشده',
			'done'      => 'انجام‌شده',
			'cancelled' => 'لغوشده',
		);
		return isset( $l[ $s ] ) ? $l[ $s ] : $s;
	}

	public static function page_list() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		$view  = isset( $_GET['view'] ) && 'past' === $_GET['view'] ? 'past' : 'upcoming'; // phpcs:ignore WordPress.Security.NonceVerification
		$today = ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d' );
		$q     = new WP_Query(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'private',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
				'meta_key'       => '_gz_date',
				'orderby'        => array( 'meta_value' => 'past' === $view ? 'DESC' : 'ASC' ),
				'meta_query'     => array(
					array(
						'key'     => '_gz_date',
						'value'   => $today,
						'compare' => 'past' === $view ? '<' : '>=',
					),
				),
			)
		);
		$rows = $q->posts;
		usort(
			$rows,
			function ( $a, $b ) use ( $view ) {
				$ka = get_post_meta( $a->ID, '_gz_date', true ) . get_post_meta( $a->ID, '_gz_time', true );
				$kb = get_post_meta( $b->ID, '_gz_date', true ) . get_post_meta( $b->ID, '_gz_time', true );
				return 'past' === $view ? strcmp( $kb, $ka ) : strcmp( $ka, $kb );
			}
		);
		$servs = self::services();
		$base  = admin_url( 'admin.php?page=golzari-bookings' );
		echo '<div class="wrap"><h1>نوبت‌ها</h1>';
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>';
		}
		printf(
			'<ul class="subsubsub"><li><a href="%s"%s>پیشِ رو</a> | </li><li><a href="%s"%s>گذشته</a></li></ul>',
			esc_url( $base ),
			'upcoming' === $view ? ' class="current"' : '',
			esc_url( add_query_arg( 'view', 'past', $base ) ),
			'past' === $view ? ' class="current"' : ''
		);
		echo '<div style="clear:both;overflow-x:auto;max-width:100%"><table class="widefat striped" style="min-width:760px"><thead><tr><th>زمان</th><th>نام</th><th>همراه</th><th>خدمت</th><th>پیگمنتر</th><th>توضیح</th><th>وضعیت</th><th>کار</th></tr></thead><tbody>';
		if ( ! $rows ) {
			echo '<tr><td colspan="8">نوبتی نیست.</td></tr>';
		}
		foreach ( $rows as $p ) {
			$m      = function ( $k ) use ( $p ) {
				return (string) get_post_meta( $p->ID, '_gz_' . $k, true );
			};
			$status = $m( 'status' );
			$svc    = $m( 'service' );
			echo '<tr>';
			echo '<td>' . esc_html( self::jdate( $m( 'date' ) ) . ' — ' . Golzari_Brand::fa_digits( $m( 'time' ) ) ) . '</td>';
			echo '<td>' . esc_html( $m( 'name' ) ) . '</td>';
			echo '<td><a href="' . esc_url( 'tel:' . $m( 'phone' ) ) . '" dir="ltr">' . esc_html( Golzari_Brand::fa_digits( $m( 'phone' ) ) ) . '</a></td>';
			echo '<td>' . esc_html( self::service_name( $p->ID ) ) . '</td>';
			echo '<td>' . esc_html( self::staff_name( $p->ID ) ) . '</td>';
			echo '<td>' . esc_html( $m( 'note' ) ) . '</td>';
			echo '<td>' . esc_html( self::status_label( $status ) ) . '</td>';
			echo '<td>';
			foreach ( self::STATUS as $s ) {
				if ( $s === $status ) {
					continue;
				}
				$url = wp_nonce_url( admin_url( 'admin-post.php?action=golzari_booking_status&id=' . $p->ID . '&s=' . $s ), 'gz_bk_' . $p->ID );
				printf( '<a class="button button-small" href="%s">%s</a> ', esc_url( $url ), esc_html( self::status_label( $s ) ) );
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	public static function admin_status() {
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		$s  = isset( $_GET['s'] ) ? sanitize_key( wp_unslash( $_GET['s'] ) ) : '';
		if ( ! current_user_can( 'edit_posts' ) || ! wp_verify_nonce( isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '', 'gz_bk_' . $id ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		if ( self::CPT === get_post_type( $id ) && in_array( $s, self::STATUS, true ) ) {
			update_post_meta( $id, '_gz_status', $s );
		}
		wp_safe_redirect( add_query_arg( 'updated', 1, wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=golzari-bookings' ) ) );
		exit;
	}

	/** گزینه‌های ساعت برای فهرستِ کشویی، هر نیم ساعت. */
	private static function hours() {
		$out = array();
		for ( $m = 6 * 60; $m <= 23 * 60; $m += 30 ) {
			$out[] = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
		}
		return $out;
	}

	private static function select( $name, $value, $options ) {
		$h = '<select name="' . esc_attr( $name ) . '">';
		foreach ( $options as $k => $label ) {
			$h .= '<option value="' . esc_attr( $k ) . '"' . selected( (string) $value, (string) $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		return $h . '</select>';
	}

	public static function page_opts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o     = self::opts();
		$hours = array();
		foreach ( self::hours() as $h ) {
			$hours[ $h ] = Golzari_Brand::fa_digits( $h );
		}
		$fa = function ( $list ) {
			$out = array();
			foreach ( $list as $v ) {
				$out[ $v ] = Golzari_Brand::fa_digits( $v );
			}
			return $out;
		};
		echo '<div class="wrap"><h1>تنظیماتِ نوبت</h1>';
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="golzari_booking_opts">';
		wp_nonce_field( 'golzari_booking_opts' );
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th>روزهای کاری</th><td>';
		foreach ( array( 6, 0, 1, 2, 3, 4, 5 ) as $w ) {
			printf( '<label style="margin-inline-end:14px"><input type="checkbox" name="days[]" value="%d"%s> %s</label>', $w, checked( in_array( $w, array_map( 'intval', (array) $o['days'] ), true ), true, false ), esc_html( self::WEEKDAYS[ $w ] ) );
		}
		echo '</td></tr>';
		echo '<tr><th>شروعِ کار</th><td>' . self::select( 'start', $o['start'], $hours ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<tr><th>پایانِ کار</th><td>' . self::select( 'end', $o['end'], $hours ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<tr><th>مدتِ هر نوبت (دقیقه)</th><td>' . self::select( 'slot', $o['slot'], $fa( array( 30, 45, 60, 90, 120, 180 ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<tr><th>نوبت‌های هم‌زمانِ هر پیگمنتر در هر ساعت</th><td>' . self::select( 'capacity', $o['capacity'], $fa( array( 1, 2, 3, 4 ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<tr><th>چند روزِ آینده قابلِ رزرو</th><td>' . self::select( 'ahead', $o['ahead'], $fa( array( 7, 14, 21, 30, 45, 60 ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<tr><th>کم‌ترین فاصله تا نوبت (ساعت)</th><td>' . self::select( 'lead', $o['lead'], $fa( array( 0, 1, 2, 3, 6, 12, 24 ) ) ) . '</td></tr>'; // phpcs:ignore WordPress.Security.EscapeOutput
		printf( '<tr><th>رایانامه‌ی نوبتِ تازه</th><td><input type="hidden" name="notify" value="0"><label><input type="checkbox" name="notify" value="1"%s> به رایانامه‌ی مدیرِ سایت فرستاده شود</label></td></tr>', checked( $o['notify'], '1', false ) );
		echo self::list_rows( 'services', 'خدمت‌ها', $o['services'], 'خدمتِ تازه' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo self::list_rows( 'staff', 'پیگمنترها', $o['staff'], 'پیگمنترِ تازه' ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '</table>';
		submit_button( 'ذخیره' );
		echo '</form></div>';
	}

	/** فهرستِ نام‌ها: هر سطر قابلِ ویرایش، با تیکِ برداشتن؛ یک سطرِ خالی برای افزودن. */
	private static function list_rows( $name, $title, $items, $placeholder ) {
		$h = '<tr><th>' . esc_html( $title ) . '</th><td>';
		foreach ( (array) $items as $k => $v ) {
			$h .= sprintf(
				'<p><input type="text" class="regular-text" name="%1$s[%2$s]" value="%3$s"> <label><input type="checkbox" name="%1$s_remove[]" value="%2$s"> برداشتن</label></p>',
				esc_attr( $name ),
				esc_attr( $k ),
				esc_attr( $v )
			);
		}
		$h .= sprintf( '<p><input type="text" class="regular-text" name="%s_new" placeholder="%s"></p>', esc_attr( $name ), esc_attr( $placeholder ) );
		return $h . '</td></tr>';
	}

	/** فهرستِ نام‌ها از فرم؛ دست‌کم یک مورد می‌ماند. */
	private static function list_save( $in, $name, $old ) {
		$remove = isset( $in[ $name . '_remove' ] ) ? array_map( 'sanitize_key', (array) $in[ $name . '_remove' ] ) : array();
		$out    = array();
		foreach ( isset( $in[ $name ] ) ? (array) $in[ $name ] : array() as $k => $v ) {
			$k = sanitize_key( $k );
			$v = sanitize_text_field( $v );
			if ( '' !== $k && '' !== $v && ! in_array( $k, $remove, true ) ) {
				$out[ $k ] = $v;
			}
		}
		$new = isset( $in[ $name . '_new' ] ) ? sanitize_text_field( $in[ $name . '_new' ] ) : '';
		if ( '' !== $new ) {
			$k = substr( $name, 0, 2 ) . '-' . substr( md5( $new . microtime() ), 0, 6 );
			$out[ $k ] = $new;
		}
		return $out ? $out : $old;
	}

	public static function admin_opts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'golzari_booking_opts' );
		$in    = wp_unslash( $_POST );
		$hours = self::hours();
		$pick  = function ( $v, $allowed, $def ) {
			return in_array( (string) $v, array_map( 'strval', $allowed ), true ) ? $v : $def;
		};
		$days  = array_values( array_intersect( array_map( 'intval', isset( $in['days'] ) ? (array) $in['days'] : array() ), array( 0, 1, 2, 3, 4, 5, 6 ) ) );
		$start = $pick( isset( $in['start'] ) ? $in['start'] : '', $hours, '10:00' );
		$end   = $pick( isset( $in['end'] ) ? $in['end'] : '', $hours, '19:00' );
		if ( $end <= $start ) {
			$end = '19:00' > $start ? '19:00' : '23:00';
		}
		update_option(
			self::OPTS,
			array(
				'days'     => $days,
				'start'    => $start,
				'end'      => $end,
				'slot'     => (int) $pick( isset( $in['slot'] ) ? $in['slot'] : '', array( 30, 45, 60, 90, 120, 180 ), 60 ),
				'capacity' => (int) $pick( isset( $in['capacity'] ) ? $in['capacity'] : '', array( 1, 2, 3, 4 ), 1 ),
				'ahead'    => (int) $pick( isset( $in['ahead'] ) ? $in['ahead'] : '', array( 7, 14, 21, 30, 45, 60 ), 14 ),
				'lead'     => (int) $pick( isset( $in['lead'] ) ? $in['lead'] : '', array( 0, 1, 2, 3, 6, 12, 24 ), 2 ),
				'notify'   => isset( $in['notify'] ) && '1' === $in['notify'] ? '1' : '0',
				'services' => self::list_save( $in, 'services', self::opts()['services'] ),
				'staff'    => self::list_save( $in, 'staff', self::opts()['staff'] ),
				'closed'   => self::opts()['closed'],
			),
			false
		);
		wp_safe_redirect( admin_url( 'admin.php?page=golzari-booking-opts&updated=1' ) );
		exit;
	}
}
