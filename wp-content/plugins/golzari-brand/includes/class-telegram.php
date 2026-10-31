<?php
/**
 * ربات تلگرامِ نوبت‌ها: خبرِ نوبتِ تازه، فهرستِ نوبت‌ها، تأیید و لغو، و تنظیماتِ روز و ساعتِ کاری.
 *
 * تلگرام پیام‌ها را مستقیم به نشانیِ «قلاب» در سایت می‌فرستد و سایت در همان پاسخ جواب می‌دهد؛
 * پس مدیریت از ربات به دسترسیِ هاست به تلگرام نیازی ندارد.
 * خبرِ نوبتِ تازه اول مستقیم فرستاده می‌شود؛ اگر هاست به تلگرام نرسید، در صف می‌ماند تا
 * کارِ زمان‌بندی‌شده‌ی گیت‌هاب (telegram.yml) با یک درخواست برش دارد و بفرستد.
 *
 * رمزِ ربات فقط در پیشخوان (و رمزهای مخزنِ گیت‌هاب) است؛ نشانیِ قلاب و کلیدِ صف از خودِ رمز
 * ساخته می‌شوند تا رمزِ دومی لازم نباشد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Telegram {

	const TOKEN  = 'golzari_tg_token';
	const CHATS  = 'golzari_tg_chats';
	const CODE   = 'golzari_tg_code';
	const OUTBOX = 'golzari_tg_outbox';
	const DIRECT = 'golzari_tg_direct';
	const API    = 'https://api.telegram.org/bot';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'golzari_booking_created', array( __CLASS__, 'on_booking' ) );
		add_action( 'golzari_tg_flush', array( __CLASS__, 'flush' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_golzari_tg', array( __CLASS__, 'admin_save' ) );
		add_filter( 'golzari_health_extra', array( __CLASS__, 'health' ) );
	}

	/* ---------- رمز و کلیدهای ساخته‌شده از آن ---------- */

	public static function token() {
		return (string) get_option( self::TOKEN, '' );
	}

	private static function derive( $what, $len ) {
		$t = self::token();
		return '' === $t ? '' : substr( hash_hmac( 'sha256', $what, $t ), 0, $len );
	}

	public static function hook_url() {
		$k = self::derive( 'hook', 24 );
		return '' === $k ? '' : rest_url( 'golzari/v1/telegram/hook/' . $k );
	}

	/** وضعیتِ بی‌خطر برای نشانیِ سلامت. */
	public static function health( $extra ) {
		$d                 = get_option( self::DIRECT, array() );
		$extra['telegram'] = array(
			'configured' => '' !== self::token(),
			'linked'     => count( (array) get_option( self::CHATS, array() ) ),
			'queued'     => count( (array) get_option( self::OUTBOX, array() ) ),
			'direct'     => isset( $d['ok'] ) ? (bool) $d['ok'] : null,
			'direct_at'  => isset( $d['at'] ) ? gmdate( 'c', (int) $d['at'] ) : null,
		);
		return $extra;
	}

	/* ---------- نشانی‌ها ---------- */

	public static function routes() {
		register_rest_route(
			'golzari/v1',
			'/telegram/hook/(?P<k>[a-f0-9]{24})',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'hook' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'golzari/v1',
			'/telegram/outbox',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'outbox' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	private static function nocache( $data, $status = 200 ) {
		do_action( 'litespeed_control_set_nocache', 'golzari telegram' );
		$r = new WP_REST_Response( $data, $status );
		$r->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
		$r->header( 'X-LiteSpeed-Cache-Control', 'no-cache' );
		return $r;
	}

	/** صف برای کارِ زمان‌بندی‌شده‌ی گیت‌هاب؛ با برداشتن خالی می‌شود. */
	public static function outbox( WP_REST_Request $req ) {
		$key = self::derive( 'outbox', 32 );
		if ( '' === $key || ! hash_equals( $key, (string) $req->get_param( 'key' ) ) ) {
			return self::nocache( array( 'ok' => false ), 403 );
		}
		$box = (array) get_option( self::OUTBOX, array() );
		update_option( self::OUTBOX, array(), false );
		return self::nocache(
			array(
				'ok'       => true,
				'webhook'  => array(
					'url'    => self::hook_url(),
					'secret' => self::derive( 'secret', 32 ),
				),
				'messages' => array_values( $box ),
			)
		);
	}

	/* ---------- فرستادن ---------- */

	/** فرستادنِ مستقیم؛ نتیجه برای یک ساعت به خاطر می‌ماند تا هر بار شش ثانیه منتظر نمانیم. */
	private static function direct( $method, $payload ) {
		$d = get_option( self::DIRECT, array() );
		if ( isset( $d['ok'], $d['at'] ) && ! $d['ok'] && time() - (int) $d['at'] < HOUR_IN_SECONDS ) {
			return false;
		}
		$r  = wp_remote_post(
			self::API . self::token() . '/' . $method,
			array(
				'timeout' => 6,
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $payload ),
			)
		);
		$ok = ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r );
		update_option( self::DIRECT, array( 'ok' => $ok, 'at' => time() ), false );
		return $ok;
	}

	/** پیام به همه‌ی گفت‌وگوهای وصل‌شده: در صف، و فرستادن در پس‌زمینه. */
	public static function broadcast( $text, $markup = null ) {
		if ( '' === self::token() ) {
			return;
		}
		$box = (array) get_option( self::OUTBOX, array() );
		foreach ( array_keys( (array) get_option( self::CHATS, array() ) ) as $chat ) {
			$m = array(
				'chat_id'    => $chat,
				'text'       => $text,
				'parse_mode' => 'HTML',
			);
			if ( $markup ) {
				$m['reply_markup'] = $markup;
			}
			$box[] = $m;
		}
		update_option( self::OUTBOX, array_slice( $box, -100 ), false );
		if ( ! wp_next_scheduled( 'golzari_tg_flush' ) ) {
			wp_schedule_single_event( time(), 'golzari_tg_flush' );
		}
	}

	/** خالی کردنِ صف از راهِ مستقیم؛ هر چه نرسید برای گیت‌هاب می‌ماند. */
	public static function flush() {
		$box  = (array) get_option( self::OUTBOX, array() );
		$left = array();
		foreach ( $box as $m ) {
			if ( ! self::direct( 'sendMessage', $m ) ) {
				$left[] = $m;
			}
		}
		update_option( self::OUTBOX, $left, false );
	}

	/* ---------- متن‌ها ---------- */

	private static function fa( $s ) {
		return Golzari_Brand::fa_digits( $s );
	}

	private static function e( $s ) {
		return htmlspecialchars( (string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	public static function booking_text( $id ) {
		$m = function ( $k ) use ( $id ) {
			return (string) get_post_meta( $id, '_gz_' . $k, true );
		};
		$lines = array(
			'🗓 <b>' . self::e( Golzari_Brand_Booking::jdate( $m( 'date' ) ) ) . '</b> — ساعتِ <b>' . self::fa( $m( 'time' ) ) . '</b>',
			'👤 ' . self::e( $m( 'name' ) ) . '  📞 ' . self::fa( $m( 'phone' ) ),
			'💄 ' . self::e( Golzari_Brand_Booking::service_name( $id ) ) . ' — ' . self::e( Golzari_Brand_Booking::staff_name( $id ) ),
		);
		if ( '' !== $m( 'note' ) ) {
			$lines[] = '📝 ' . self::e( $m( 'note' ) );
		}
		$lines[] = 'وضعیت: ' . Golzari_Brand_Booking::status_label( $m( 'status' ) );
		return implode( "\n", $lines );
	}

	private static function booking_buttons( $id ) {
		return array(
			'inline_keyboard' => array(
				array(
					array( 'text' => '✅ تأیید', 'callback_data' => "b:$id:c" ),
					array( 'text' => '✔️ انجام شد', 'callback_data' => "b:$id:d" ),
					array( 'text' => '❌ لغو', 'callback_data' => "b:$id:x" ),
				),
				array( array( 'text' => '🏠 منو', 'callback_data' => 'm' ) ),
			),
		);
	}

	public static function on_booking( $id ) {
		self::broadcast( "🔔 <b>نوبتِ تازه</b>\n" . self::booking_text( $id ), self::booking_buttons( $id ) );
	}

	/* ---------- قلاب ---------- */

	public static function hook( WP_REST_Request $req ) {
		$k = self::derive( 'hook', 24 );
		if ( '' === $k || ! hash_equals( $k, (string) $req['k'] ) || ! hash_equals( self::derive( 'secret', 32 ), (string) $req->get_header( 'x_telegram_bot_api_secret_token' ) ) ) {
			return self::nocache( array( 'ok' => false ), 403 );
		}
		$u     = $req->get_json_params();
		$reply = null;
		if ( ! empty( $u['callback_query'] ) ) {
			$reply = self::on_callback( $u['callback_query'] );
		} elseif ( ! empty( $u['message']['text'] ) ) {
			$reply = self::on_message( $u['message'] );
		}
		// پاسخ در همان درخواست: تلگرام آن را مثلِ فراخوانیِ روشِ ربات اجرا می‌کند.
		return self::nocache( $reply ? $reply : array( 'ok' => true ) );
	}

	private static function linked( $chat ) {
		return array_key_exists( (string) $chat, (array) get_option( self::CHATS, array() ) );
	}

	private static function send( $chat, $text, $markup = null ) {
		$r = array(
			'method'     => 'sendMessage',
			'chat_id'    => $chat,
			'text'       => $text,
			'parse_mode' => 'HTML',
		);
		if ( $markup ) {
			$r['reply_markup'] = $markup;
		}
		return $r;
	}

	private static function edit( $chat, $msg, $text, $markup ) {
		return array(
			'method'       => 'editMessageText',
			'chat_id'      => $chat,
			'message_id'   => $msg,
			'text'         => $text,
			'parse_mode'   => 'HTML',
			'reply_markup' => $markup,
		);
	}

	private static function on_message( $msg ) {
		$chat = (string) $msg['chat']['id'];
		$text = trim( (string) $msg['text'] );
		if ( preg_match( '#^/start\s+(\d{6})$#', $text, $m ) ) {
			$c = get_option( self::CODE, array() );
			if ( isset( $c['code'], $c['exp'] ) && hash_equals( (string) $c['code'], $m[1] ) && time() < (int) $c['exp'] ) {
				$chats          = (array) get_option( self::CHATS, array() );
				$name           = trim( ( isset( $msg['from']['first_name'] ) ? $msg['from']['first_name'] : '' ) . ' ' . ( isset( $msg['from']['last_name'] ) ? $msg['from']['last_name'] : '' ) );
				$chats[ $chat ] = '' !== $name ? $name : $chat;
				update_option( self::CHATS, $chats, false );
				delete_option( self::CODE );
				return self::send( $chat, "✅ این گفت‌وگو به سیستمِ نوبتِ <b>" . self::e( golzari_label( 'brand_name' ) ) . "</b> وصل شد.\n\n" . self::menu_text(), self::menu_kb() );
			}
			return self::send( $chat, 'کدِ اتصال درست نیست یا گذشته است. از پیشخوانِ سایت کدِ تازه بگیرید.' );
		}
		if ( ! self::linked( $chat ) ) {
			return self::send( $chat, 'این ربات خصوصیِ مجموعه است. برای وصل شدن، از پیشخوانِ سایت ← نوبت‌ها ← ربات تلگرام کدِ اتصال بگیرید.' );
		}
		return self::send( $chat, self::menu_text(), self::menu_kb() );
	}

	/* ---------- منوها ---------- */

	private static function menu_text() {
		return "🏠 <b>منوی نوبت‌ها</b>\nیکی را انتخاب کنید:";
	}

	private static function menu_kb() {
		return array(
			'inline_keyboard' => array(
				array(
					array( 'text' => '📅 امروز', 'callback_data' => 'l:0' ),
					array( 'text' => '📅 فردا', 'callback_data' => 'l:1' ),
				),
				array( array( 'text' => '🗓 هفت روزِ آینده', 'callback_data' => 'l:7' ) ),
				array( array( 'text' => '⛔️ بستن یا باز کردنِ یک روز', 'callback_data' => 'x' ) ),
				array( array( 'text' => '⚙️ تنظیماتِ نوبت', 'callback_data' => 's' ) ),
			),
		);
	}

	private static function back( $to = 'm' ) {
		return array( array( 'text' => '↩️ بازگشت', 'callback_data' => $to ) );
	}

	/** فهرستِ نوبت‌های فعال: ۰ امروز، ۱ فردا، ۷ هفت روز. */
	private static function list_text( $span ) {
		$tz    = wp_timezone();
		$today = new DateTimeImmutable( 'today', $tz );
		$from  = 1 === $span ? $today->modify( '+1 day' ) : $today;
		$to    = 7 === $span ? $today->modify( '+6 days' ) : $from;
		$q     = new WP_Query(
			array(
				'post_type'      => Golzari_Brand_Booking::CPT,
				'post_status'    => 'private',
				'posts_per_page' => 100,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array(
						'key'     => '_gz_date',
						'value'   => array( $from->format( 'Y-m-d' ), $to->format( 'Y-m-d' ) ),
						'compare' => 'BETWEEN',
					),
					array(
						'key'     => '_gz_status',
						'value'   => array( 'pending', 'confirmed' ),
						'compare' => 'IN',
					),
				),
			)
		);
		$rows = $q->posts;
		usort(
			$rows,
			function ( $a, $b ) {
				return strcmp( get_post_meta( $a->ID, '_gz_date', true ) . get_post_meta( $a->ID, '_gz_time', true ), get_post_meta( $b->ID, '_gz_date', true ) . get_post_meta( $b->ID, '_gz_time', true ) );
			}
		);
		$title = 0 === $span ? 'امروز' : ( 1 === $span ? 'فردا' : 'هفت روزِ آینده' );
		if ( ! $rows ) {
			return array( "📭 برای {$title} نوبتی نیست.", array() );
		}
		$lines = array( "📋 <b>نوبت‌های {$title}</b> (" . self::fa( count( $rows ) ) . ')' );
		$kb    = array();
		foreach ( $rows as $p ) {
			$d       = get_post_meta( $p->ID, '_gz_date', true );
			$t       = get_post_meta( $p->ID, '_gz_time', true );
			$s       = get_post_meta( $p->ID, '_gz_status', true );
			$mark    = 'confirmed' === $s ? '✅' : '⏳';
			$lines[] = $mark . ' ' . ( 7 === $span ? self::e( Golzari_Brand_Booking::jdate( $d, false ) ) . ' ' : '' ) . self::fa( $t ) . ' — ' . self::e( get_post_meta( $p->ID, '_gz_name', true ) ) . ' — ' . self::e( Golzari_Brand_Booking::service_name( $p->ID ) );
			$kb[]    = array( array( 'text' => $mark . ' ' . self::fa( $t ) . ' ' . get_post_meta( $p->ID, '_gz_name', true ), 'callback_data' => 'v:' . $p->ID ) );
		}
		return array( implode( "\n", $lines ), array_slice( $kb, 0, 30 ) );
	}

	private static function settings_text() {
		$o    = Golzari_Brand_Booking::opts();
		$days = array();
		foreach ( array( 6, 0, 1, 2, 3, 4, 5 ) as $w ) {
			if ( in_array( $w, array_map( 'intval', (array) $o['days'] ), true ) ) {
				$days[] = Golzari_Brand_Booking::WEEKDAYS[ $w ];
			}
		}
		return implode(
			"\n",
			array(
				'⚙️ <b>تنظیماتِ نوبت</b>',
				'روزهای کاری: ' . ( $days ? implode( '، ', $days ) : '—' ),
				'ساعتِ کار: ' . self::fa( $o['start'] ) . ' تا ' . self::fa( $o['end'] ),
				'مدتِ هر نوبت: ' . self::fa( $o['slot'] ) . ' دقیقه',
				'نوبتِ هم‌زمان: ' . self::fa( $o['capacity'] ),
				'رزرو تا ' . self::fa( $o['ahead'] ) . ' روزِ آینده',
				'کم‌ترین فاصله تا نوبت: ' . self::fa( $o['lead'] ) . ' ساعت',
			)
		);
	}

	private static function settings_kb() {
		return array(
			'inline_keyboard' => array(
				array( array( 'text' => '📆 روزهای کاری', 'callback_data' => 'sd' ) ),
				array(
					array( 'text' => '🕘 شروعِ کار', 'callback_data' => 'ss' ),
					array( 'text' => '🕖 پایانِ کار', 'callback_data' => 'se' ),
				),
				array(
					array( 'text' => '⏱ مدتِ نوبت', 'callback_data' => 'sl' ),
					array( 'text' => '👥 هم‌زمان', 'callback_data' => 'sc' ),
				),
				array(
					array( 'text' => '📅 چند روزِ آینده', 'callback_data' => 'sa' ),
					array( 'text' => '⌛️ کم‌ترین فاصله', 'callback_data' => 'sh' ),
				),
				self::back(),
			),
		);
	}

	/** ردیف‌هایی از دکمه، هر ردیف n تا. */
	private static function grid( $buttons, $n ) {
		return array_chunk( $buttons, $n );
	}

	/** گزینه‌های هر تنظیم: کد => [کلید، فهرست، برچسب]. */
	private static function choices() {
		$hours = array();
		for ( $h = 6; $h <= 23; $h++ ) {
			$hours[] = sprintf( '%02d:00', $h );
		}
		return array(
			'ss' => array( 'start', $hours, 'ساعتِ شروعِ کار' ),
			'se' => array( 'end', $hours, 'ساعتِ پایانِ کار' ),
			'sl' => array( 'slot', array( 30, 45, 60, 90, 120, 180 ), 'مدتِ هر نوبت (دقیقه)' ),
			'sc' => array( 'capacity', array( 1, 2, 3, 4 ), 'نوبت‌های هم‌زمان' ),
			'sa' => array( 'ahead', array( 7, 14, 21, 30, 45, 60 ), 'چند روزِ آینده قابلِ رزرو' ),
			'sh' => array( 'lead', array( 0, 1, 2, 3, 6, 12, 24 ), 'کم‌ترین فاصله تا نوبت (ساعت)' ),
		);
	}

	private static function on_callback( $cb ) {
		$chat = (string) $cb['message']['chat']['id'];
		$msg  = (int) $cb['message']['message_id'];
		$data = (string) $cb['data'];
		if ( ! self::linked( $chat ) ) {
			return array( 'method' => 'answerCallbackQuery', 'callback_query_id' => $cb['id'], 'text' => 'دسترسی ندارید.' );
		}

		if ( 'm' === $data ) {
			return self::edit( $chat, $msg, self::menu_text(), self::menu_kb() );
		}

		if ( preg_match( '#^l:(0|1|7)$#', $data, $m ) ) {
			list( $text, $kb ) = self::list_text( (int) $m[1] );
			$kb[]              = self::back();
			return self::edit( $chat, $msg, $text, array( 'inline_keyboard' => $kb ) );
		}

		if ( preg_match( '#^v:(\d+)$#', $data, $m ) && Golzari_Brand_Booking::CPT === get_post_type( (int) $m[1] ) ) {
			return self::edit( $chat, $msg, self::booking_text( (int) $m[1] ), self::booking_buttons( (int) $m[1] ) );
		}

		if ( preg_match( '#^b:(\d+):(c|d|x)$#', $data, $m ) && Golzari_Brand_Booking::CPT === get_post_type( (int) $m[1] ) ) {
			$map = array( 'c' => 'confirmed', 'd' => 'done', 'x' => 'cancelled' );
			update_post_meta( (int) $m[1], '_gz_status', $map[ $m[2] ] );
			return self::edit( $chat, $msg, self::booking_text( (int) $m[1] ), self::booking_buttons( (int) $m[1] ) );
		}

		if ( 's' === $data ) {
			return self::edit( $chat, $msg, self::settings_text(), self::settings_kb() );
		}

		// روزهای کاری: هر دکمه روشن یا خاموش می‌کند.
		if ( preg_match( '#^sd(?::([0-6]))?$#', $data, $m ) ) {
			$o    = Golzari_Brand_Booking::opts();
			$days = array_map( 'intval', (array) $o['days'] );
			if ( isset( $m[1] ) ) {
				$w    = (int) $m[1];
				$days = in_array( $w, $days, true ) ? array_values( array_diff( $days, array( $w ) ) ) : array_merge( $days, array( $w ) );
				Golzari_Brand_Booking::set_opt( 'days', $days );
			}
			$btn = array();
			foreach ( array( 6, 0, 1, 2, 3, 4, 5 ) as $w ) {
				$btn[] = array( 'text' => ( in_array( $w, $days, true ) ? '✅ ' : '⬜️ ' ) . Golzari_Brand_Booking::WEEKDAYS[ $w ], 'callback_data' => "sd:$w" );
			}
			$kb   = self::grid( $btn, 2 );
			$kb[] = self::back( 's' );
			return self::edit( $chat, $msg, "📆 <b>روزهای کاری</b>\nبا هر دکمه روز روشن یا خاموش می‌شود.", array( 'inline_keyboard' => $kb ) );
		}

		// بقیه‌ی تنظیم‌ها: انتخاب از فهرست.
		$choices = self::choices();
		if ( preg_match( '#^(s[selcah])(?::(.+))?$#', $data, $m ) && isset( $choices[ $m[1] ] ) ) {
			list( $key, $list, $label ) = $choices[ $m[1] ];
			if ( isset( $m[2] ) && in_array( $m[2], array_map( 'strval', $list ), true ) ) {
				$o = Golzari_Brand_Booking::opts();
				$v = is_int( $list[0] ) ? (int) $m[2] : $m[2];
				if ( ( 'start' === $key && $v >= $o['end'] ) || ( 'end' === $key && $v <= $o['start'] ) ) {
					return array( 'method' => 'answerCallbackQuery', 'callback_query_id' => $cb['id'], 'text' => 'ساعتِ پایان باید بعد از ساعتِ شروع باشد.', 'show_alert' => true );
				}
				Golzari_Brand_Booking::set_opt( $key, $v );
				return self::edit( $chat, $msg, "✅ ذخیره شد.\n\n" . self::settings_text(), self::settings_kb() );
			}
			$cur = (string) Golzari_Brand_Booking::opts()[ $key ];
			$btn = array();
			foreach ( $list as $v ) {
				$btn[] = array( 'text' => ( (string) $v === $cur ? '● ' : '' ) . self::fa( $v ), 'callback_data' => $m[1] . ':' . $v );
			}
			$kb   = self::grid( $btn, is_int( $list[0] ) ? 3 : 4 );
			$kb[] = self::back( 's' );
			return self::edit( $chat, $msg, "<b>{$label}</b>\nیکی را انتخاب کنید:", array( 'inline_keyboard' => $kb ) );
		}

		// بستن یا باز کردنِ یک روزِ خاص (مثلاً مرخصی).
		if ( preg_match( '#^x(?::(\d{8}))?$#', $data, $m ) ) {
			$o      = Golzari_Brand_Booking::opts();
			$closed = array_values( (array) $o['closed'] );
			if ( isset( $m[1] ) ) {
				$d      = substr( $m[1], 0, 4 ) . '-' . substr( $m[1], 4, 2 ) . '-' . substr( $m[1], 6, 2 );
				$closed = in_array( $d, $closed, true ) ? array_values( array_diff( $closed, array( $d ) ) ) : array_merge( $closed, array( $d ) );
				Golzari_Brand_Booking::set_opt( 'closed', $closed );
			}
			$today = new DateTimeImmutable( 'today', wp_timezone() );
			$btn   = array();
			for ( $i = 0; $i < 21; $i++ ) {
				$day   = $today->modify( "+{$i} days" );
				$ymd   = $day->format( 'Y-m-d' );
				$btn[] = array( 'text' => ( in_array( $ymd, $closed, true ) ? '⛔️ ' : '' ) . Golzari_Brand_Booking::jdate( $ymd, false ), 'callback_data' => 'x:' . $day->format( 'Ymd' ) );
			}
			$kb   = self::grid( $btn, 2 );
			$kb[] = self::back();
			return self::edit( $chat, $msg, "⛔️ <b>بستن یا باز کردنِ یک روز</b>\nروزهای ⛔️ بسته‌اند و نوبت نمی‌گیرند. با زدن، روز بسته یا باز می‌شود.", array( 'inline_keyboard' => $kb ) );
		}

		return array( 'method' => 'answerCallbackQuery', 'callback_query_id' => $cb['id'] );
	}

	/* ---------- پیشخوان ---------- */

	public static function menu() {
		add_submenu_page( 'golzari-bookings', 'ربات تلگرام', 'ربات تلگرام', 'manage_options', 'golzari-telegram', array( __CLASS__, 'page' ) );
	}

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$token = self::token();
		$chats = (array) get_option( self::CHATS, array() );
		$code  = get_option( self::CODE, array() );
		$d     = get_option( self::DIRECT, array() );
		echo '<div class="wrap"><h1>ربات تلگرام</h1>';
		if ( isset( $_GET['updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>ذخیره شد.</p></div>';
		}
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="golzari_tg">';
		wp_nonce_field( 'golzari_tg' );
		echo '<table class="form-table" role="presentation">';
		echo '<tr><th>رمزِ ربات</th><td>';
		if ( '' !== $token ) {
			echo '<p>ثبت شده (<code>' . esc_html( substr( $token, 0, 6 ) ) . '…</code>). برای عوض کردن، رمزِ تازه را بنویسید.</p>';
		}
		echo '<input type="password" class="regular-text" name="token" autocomplete="off" dir="ltr" placeholder="123456:ABC…">';
		echo '<p class="description">از ‎@BotFather‎ در تلگرام بگیرید. این رمز را در هیچ پیام یا گفت‌وگویی نفرستید.</p></td></tr>';
		if ( '' !== $token ) {
			$direct = isset( $d['ok'] ) ? ( $d['ok'] ? 'برقرار است' : 'برقرار نیست؛ خبرها از راهِ گیت‌هاب فرستاده می‌شوند' ) : 'هنوز سنجیده نشده';
			echo '<tr><th>دسترسیِ مستقیمِ هاست به تلگرام</th><td>' . esc_html( $direct ) . '</td></tr>';
			echo '<tr><th>گفت‌وگوهای وصل‌شده</th><td>';
			if ( ! $chats ) {
				echo '<p>هنوز هیچ.</p>';
			}
			foreach ( $chats as $id => $name ) {
				printf( '<p>%s <label><input type="checkbox" name="unlink[]" value="%s"> جدا کردن</label></p>', esc_html( $name ), esc_attr( $id ) );
			}
			echo '</td></tr>';
			echo '<tr><th>کدِ اتصال</th><td>';
			if ( isset( $code['code'], $code['exp'] ) && time() < (int) $code['exp'] ) {
				echo '<p style="font-size:18px">در ربات بفرستید: <code dir="ltr">/start ' . esc_html( $code['code'] ) . '</code></p><p class="description">تا ۳۰ دقیقه معتبر است.</p>';
			}
			echo '<label><input type="checkbox" name="newcode" value="1"> ساختنِ کدِ اتصالِ تازه</label></td></tr>';
		}
		echo '</table>';
		submit_button( 'ذخیره' );
		echo '</form></div>';
	}

	public static function admin_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'golzari_tg' );
		$token = isset( $_POST['token'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['token'] ) ) ) : '';
		if ( '' !== $token && preg_match( '/^\d{5,}:[A-Za-z0-9_-]{30,}$/', $token ) ) {
			update_option( self::TOKEN, $token, false );
			delete_option( self::DIRECT );
			update_option( self::CHATS, array(), false );
			self::direct( 'setWebhook', array( 'url' => self::hook_url(), 'secret_token' => self::derive( 'secret', 32 ), 'allowed_updates' => array( 'message', 'callback_query' ) ) );
		}
		if ( ! empty( $_POST['unlink'] ) ) {
			$chats = (array) get_option( self::CHATS, array() );
			foreach ( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['unlink'] ) ) as $id ) {
				unset( $chats[ $id ] );
			}
			update_option( self::CHATS, $chats, false );
		}
		if ( ! empty( $_POST['newcode'] ) || ( '' !== $token && preg_match( '/^\d{5,}:/', $token ) ) ) {
			update_option( self::CODE, array( 'code' => (string) wp_rand( 100000, 999999 ), 'exp' => time() + 30 * MINUTE_IN_SECONDS ), false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=golzari-telegram&updated=1' ) );
		exit;
	}
}
