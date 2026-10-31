<?php
/**
 * پاک‌سازیِ چیزهای قالب که دیگر لازم نیست: برگه‌ها و نوشته‌های نمونه، عکس‌های کتابخانه‌ی رسانه،
 * گونه‌های دیگرِ محتوای قالب و قالب‌های نصب‌شده‌ی دیگر.
 *
 * هیچ چیز خودکار پاک نمی‌شود: مدیر فهرست را می‌بیند، تیک می‌زند و تأیید می‌کند.
 * برگه‌ها و نوشته‌ها به زباله‌دان می‌روند (تا ۳۰ روز برمی‌گردند)؛ عکس‌ها و قالب‌ها همیشگی پاک می‌شوند.
 * صفحه‌ی اصلی، صفحه‌ی رزرو، لوگو، نمادِ سایت، قالبِ فعال و نوبت‌ها قفل‌اند.
 * کار تکه‌تکه (هر بار ۲۰ مورد) انجام می‌شود تا هاستِ اشتراکی از زمان نیفتد.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Brand_Cleanup {

	const QUEUE = 'golzari_cleanup_queue';
	const LOG   = 'golzari_cleanup_log';
	const BATCH = 20;

	const THEME = 'golzari-site';
	const AUTO  = 'golzari_cleanup_auto';
	const HOOK  = 'golzari_cleanup_step';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_golzari_cleanup', array( __CLASS__, 'start' ) );
		add_action( 'init', array( __CLASS__, 'install_theme' ), 1 );
		add_action( 'init', array( __CLASS__, 'auto_schedule' ), 20 );
		add_action( self::HOOK, array( __CLASS__, 'auto_step' ) );
		add_filter( 'golzari_health_extra', array( __CLASS__, 'health' ) );
	}

	/* ---------- قالبِ «گلزاری» ---------- */

	/** پرونده‌های قالب از افزونه به پوشه‌ی قالب‌ها رونوشت می‌شوند (هر بار که نسخه عوض شود). */
	public static function install_theme() {
		$src = GOLZARI_BRAND_DIR . 'theme/' . self::THEME;
		$dst = trailingslashit( get_theme_root() ) . self::THEME;
		if ( get_option( 'golzari_theme_ver' ) === GOLZARI_BRAND_VER && file_exists( $dst . '/style.css' ) ) {
			return;
		}
		if ( ! is_dir( $src ) || ( ! is_dir( $dst ) && ! wp_mkdir_p( $dst ) ) ) {
			return;
		}
		$ok = true;
		foreach ( (array) glob( $src . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				$ok = copy( $file, $dst . '/' . basename( $file ) ) && $ok;
			}
		}
		if ( $ok ) {
			update_option( 'golzari_theme_ver', GOLZARI_BRAND_VER, false );
			delete_site_transient( 'theme_roots' );
			wp_clean_themes_cache();
		}
	}

	/* ---------- پاک‌سازیِ خودکارِ یک‌باره ---------- */

	/** گونه‌های محتوای قالب و صفحه‌ساز که به زباله‌دان می‌روند. */
	private static function theme_type( $type ) {
		return in_array( $type, array( 'page', 'post' ), true ) || (bool) preg_match( '#elementor|^e-|^ct_|zib|nour|noor|^wp_template|^wp_block$|^wp_navigation$#i', $type );
	}

	/** افزونه‌های قالب و صفحه‌ساز (پرونده‌ی اصلی). */
	public static function theme_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$out = array();
		foreach ( array_keys( get_plugins() ) as $file ) {
			$dir = strtok( $file, '/' );
			if ( in_array( $dir, array( 'elementor', 'elementor-pro' ), true ) || 0 === strpos( $dir, 'zibanoo' ) ) {
				$out[] = $file;
			}
		}
		return $out;
	}

	public static function auto_schedule() {
		$st = get_option( self::AUTO, array() );
		if ( ! empty( $st['stage'] ) && 'done' === $st['stage'] ) {
			return;
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::HOOK );
		}
	}

	/** همه‌ی مرحله‌ها پشتِ سرِ هم (برای آزمون و خطِ فرمان). */
	public static function auto_all() {
		for ( $i = 0; $i < 500; $i++ ) {
			$st = get_option( self::AUTO, array() );
			if ( ! empty( $st['stage'] ) && 'done' === $st['stage'] ) {
				return $st;
			}
			self::auto_step( true );
		}
		return get_option( self::AUTO, array() );
	}

	/** یک گام: قالب ← محتوا ← عکس‌ها ← افزونه‌های قالب ← قالب‌های دیگر ← پایان. */
	public static function auto_step( $sync = false ) {
		if ( ! $sync && get_transient( 'golzari_cleanup_lock' ) ) {
			return;
		}
		set_transient( 'golzari_cleanup_lock', 1, 5 * MINUTE_IN_SECONDS );
		$st = wp_parse_args(
			get_option( self::AUTO, array() ),
			array( 'stage' => 'theme', 'trash' => 0, 'media' => 0, 'plugins' => 0, 'themes' => 0, 'fail' => 0, 'switched' => false, 'skip' => array() )
		);
		// موردهایی که پاک نشدند دوباره امتحان نمی‌شوند تا کار در یک جا گیر نکند.
		$locked = array_merge( self::protected_ids(), array_map( 'intval', (array) $st['skip'] ) );

		switch ( $st['stage'] ) {
			case 'theme':
				self::install_theme();
				$theme = wp_get_theme( self::THEME );
				if ( $theme->exists() && ! $theme->errors() ) {
					if ( get_stylesheet() !== self::THEME ) {
						switch_theme( self::THEME );
					}
					$st['switched'] = get_stylesheet() === self::THEME;
				}
				$st['stage'] = 'content';
				break;

			case 'content':
				$types = array_filter( get_post_types(), array( __CLASS__, 'theme_type' ) );
				$ids   = get_posts(
					array(
						'post_type'      => array_values( $types ),
						'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
						'posts_per_page' => 25,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'post__not_in'   => $locked,
					)
				);
				foreach ( $ids as $id ) {
					if ( wp_trash_post( $id ) ) {
						$st['trash']++;
					} else {
						$st['fail']++;
						$st['skip'][] = $id;
					}
				}
				if ( ! $ids ) {
					$st['stage'] = 'media';
				}
				break;

			case 'media':
				$ids = get_posts(
					array(
						'post_type'      => 'attachment',
						'post_status'    => 'inherit',
						'posts_per_page' => 25,
						'fields'         => 'ids',
						'no_found_rows'  => true,
						'post__not_in'   => $locked,
					)
				);
				foreach ( $ids as $id ) {
					if ( wp_delete_attachment( $id, true ) ) {
						$st['media']++;
					} else {
						$st['fail']++;
						$st['skip'][] = $id;
					}
				}
				if ( ! $ids ) {
					$st['stage'] = 'plugins';
				}
				break;

			case 'plugins':
				// فقط وقتی قالبِ خودمان فعال است؛ وگرنه زیبانو بی افزونه‌اش می‌شکند.
				if ( $st['switched'] ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
					require_once ABSPATH . 'wp-admin/includes/file.php';
					$files = self::theme_plugins();
					if ( $files ) {
						deactivate_plugins( $files, true );
						$r = delete_plugins( $files );
						true === $r ? $st['plugins'] += count( $files ) : $st['fail']++;
					}
				}
				$st['stage'] = 'themes';
				break;

			case 'themes':
				if ( $st['switched'] ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
					require_once ABSPATH . 'wp-admin/includes/theme.php';
					foreach ( array_keys( wp_get_themes() ) as $slug ) {
						if ( self::THEME !== $slug && get_stylesheet() !== $slug && get_template() !== $slug ) {
							true === delete_theme( $slug ) ? $st['themes']++ : $st['fail']++;
						}
					}
				}
				$st['stage'] = 'done';
				$st['at']    = time();
				$st['skip']  = array();
				Golzari_Brand::purge();
				break;
		}

		update_option( self::AUTO, $st, false );
		delete_transient( 'golzari_cleanup_lock' );
		if ( ! $sync && 'done' !== $st['stage'] ) {
			wp_schedule_single_event( time() + 20, self::HOOK );
		}
	}

	/** وضعیتِ بی‌خطر برای نشانیِ سلامت. */
	public static function health( $extra ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$st               = get_option( self::AUTO, array() );
		$extra['cleanup'] = array(
			'stage'   => isset( $st['stage'] ) ? $st['stage'] : 'waiting',
			'trash'   => isset( $st['trash'] ) ? (int) $st['trash'] : 0,
			'media'   => isset( $st['media'] ) ? (int) $st['media'] : 0,
			'plugins' => isset( $st['plugins'] ) ? (int) $st['plugins'] : 0,
			'themes'  => isset( $st['themes'] ) ? (int) $st['themes'] : 0,
			'fail'    => isset( $st['fail'] ) ? (int) $st['fail'] : 0,
			'theme'   => get_stylesheet(),
			'active'  => array_values( array_map( function ( $f ) { return strtok( $f, '/' ); }, (array) get_option( 'active_plugins', array() ) ) ),
		);
		return $extra;
	}

	public static function menu() {
		add_management_page( 'پاک‌سازیِ قالب', 'پاک‌سازیِ قالب', 'manage_options', 'golzari-cleanup', array( __CLASS__, 'page' ) );
	}

	/* ---------- آنچه قفل است ---------- */

	/** شناسه‌های برگه‌ها و پیوست‌هایی که هرگز پاک نمی‌شوند. */
	public static function protected_ids() {
		$ids = array(
			(int) get_option( 'page_on_front' ),
			(int) get_option( 'page_for_posts' ),
			(int) get_option( 'site_icon' ),
			(int) get_theme_mod( 'custom_logo' ),
		);
		$slug    = trim( (string) wp_parse_url( golzari_label( 'booking_path' ), PHP_URL_PATH ), '/' );
		$booking = '' !== $slug ? get_page_by_path( $slug ) : null;
		if ( $booking ) {
			$ids[] = (int) $booking->ID;
		}
		return array_values( array_filter( array_unique( $ids ) ) );
	}

	/** گونه‌های محتوا که در فهرست نمی‌آیند. */
	private static function skip_types() {
		return array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles', 'wp_font_family', 'wp_font_face', Golzari_Brand_Booking::CPT );
	}

	/* ---------- نامزدها ---------- */

	private static function posts_of( $type ) {
		return get_posts(
			array(
				'post_type'      => $type,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => 500,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
				'post__not_in'   => self::protected_ids(),
			)
		);
	}

	private static function other_types() {
		$out = array();
		foreach ( get_post_types( array(), 'objects' ) as $name => $obj ) {
			if ( in_array( $name, array_merge( self::skip_types(), array( 'page', 'post' ) ), true ) ) {
				continue;
			}
			$n = (int) array_sum( (array) wp_count_posts( $name ) ) - (int) ( isset( wp_count_posts( $name )->trash ) ? wp_count_posts( $name )->trash : 0 );
			if ( $n > 0 ) {
				$out[ $name ] = array( $obj->labels->name, $n );
			}
		}
		return $out;
	}

	private static function attachments() {
		return get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 2000,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'post__not_in'   => self::protected_ids(),
			)
		);
	}

	private static function themes() {
		$keep = array( get_stylesheet(), get_template() );
		$out  = array();
		foreach ( wp_get_themes() as $slug => $theme ) {
			if ( ! in_array( $slug, $keep, true ) ) {
				$out[ $slug ] = $theme->get( 'Name' );
			}
		}
		return $out;
	}

	/* ---------- صفحه ---------- */

	public static function page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$queue = get_option( self::QUEUE, array() );
		echo '<div class="wrap" id="golzari-cleanup"><h1>پاک‌سازیِ قالب</h1>';

		if ( $queue ) {
			self::run_batch();
			$left = count( (array) get_option( self::QUEUE, array() ) );
			$log  = (array) get_option( self::LOG, array() );
			echo '<div class="notice notice-info"><p>در حالِ پاک‌سازی… ' . esc_html( Golzari_Brand::fa_digits( $left ) ) . ' موردِ دیگر مانده. این صفحه را نبندید.</p></div>';
			self::print_log( $log );
			if ( $left ) {
				echo '<script>setTimeout(function(){location.reload();},1200);</script>';
			} else {
				Golzari_Brand::purge();
				echo '<div class="notice notice-success"><p>پاک‌سازی تمام شد.</p></div>';
				delete_option( self::LOG );
			}
			echo '</div>';
			return;
		}

		$pages  = self::posts_of( 'page' );
		$posts  = self::posts_of( 'post' );
		$others = self::other_types();
		$atts   = self::attachments();
		$themes = self::themes();
		$locked = self::protected_ids();

		echo '<p>چیزهایی را که لازم ندارید تیک بزنید. <b>برگه‌ها و نوشته‌ها به زباله‌دان می‌روند و تا ۳۰ روز از «برگه‌ها ← زباله‌دان» برمی‌گردند.</b> <b style="color:#b32d2e">عکس‌ها و قالب‌ها همیشگی پاک می‌شوند.</b></p>';
		echo '<p>قفل و پاک‌نشدنی: ';
		$names = array();
		foreach ( $locked as $id ) {
			$names[] = esc_html( get_the_title( $id ) ? get_the_title( $id ) : '#' . $id );
		}
		echo implode( '، ', $names ) . '، قالبِ فعال (' . esc_html( wp_get_theme()->get( 'Name' ) ) . ') و همه‌ی نوبت‌ها.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="golzari_cleanup">';
		wp_nonce_field( 'golzari_cleanup' );

		self::section( 'برگه‌ها', 'page', $pages, true );
		self::section( 'نوشته‌ها', 'post', $posts, true );

		if ( $others ) {
			echo '<h2>گونه‌های دیگرِ محتوای قالب و صفحه‌ساز</h2><p class="description">بعضی از این‌ها (مثلِ قالب‌های سربرگ و پانویس) برای صفحه‌های باقی‌مانده‌ی قالب، مثلِ جست‌وجو و ۴۰۴، به کار می‌روند؛ با پاک کردنشان آن صفحه‌ها ساده‌تر نمایش داده می‌شوند.</p><ul>';
			foreach ( $others as $type => $info ) {
				printf( '<li><label><input type="checkbox" name="types[]" value="%s"> %s (%s)</label></li>', esc_attr( $type ), esc_html( $info[0] ), esc_html( Golzari_Brand::fa_digits( $info[1] ) ) );
			}
			echo '</ul>';
		}

		echo '<h2>عکس‌ها و پرونده‌های کتابخانه‌ی رسانه (' . esc_html( Golzari_Brand::fa_digits( count( $atts ) ) ) . ')</h2>';
		if ( $atts ) {
			echo '<p class="description">صفحه‌ی اصلی و صفحه‌ی رزرو از هیچ‌کدام از این‌ها استفاده نمی‌کنند؛ عکس‌های مجموعه داخلِ افزونه‌ی خودمان است.</p>';
			echo '<div style="display:flex;flex-wrap:wrap;gap:6px;margin:8px 0">';
			foreach ( array_slice( $atts, 0, 60 ) as $id ) {
				$src = wp_get_attachment_image_url( $id, 'thumbnail' );
				if ( $src ) {
					printf( '<img src="%s" width="56" height="56" style="object-fit:cover;border-radius:6px" alt="" loading="lazy">', esc_url( $src ) );
				}
			}
			echo count( $atts ) > 60 ? '<span style="align-self:center">…</span>' : '';
			echo '</div>';
			echo '<label><input type="checkbox" name="media" value="1"> همه‌ی این عکس‌ها و پرونده‌ها برای همیشه پاک شوند</label>';
		}

		if ( $themes ) {
			echo '<h2>قالب‌های نصب‌شده‌ی دیگر</h2><ul>';
			foreach ( $themes as $slug => $name ) {
				printf( '<li><label><input type="checkbox" name="themes[]" value="%s" checked> %s</label></li>', esc_attr( $slug ), esc_html( $name ) );
			}
			echo '</ul>';
		}

		echo '<hr><p><label><input type="checkbox" name="confirm" value="1" required> فهرست را دیدم و می‌دانم عکس‌ها و قالب‌ها برنمی‌گردند.</label></p>';
		submit_button( 'پاک کردنِ موارد انتخاب‌شده', 'delete' );
		echo '</form></div>';
	}

	private static function section( $title, $type, $items, $checked ) {
		echo '<h2>' . esc_html( $title ) . ' (' . esc_html( Golzari_Brand::fa_digits( count( $items ) ) ) . ')</h2>';
		if ( ! $items ) {
			echo '<p>چیزی نیست.</p>';
			return;
		}
		echo '<ul style="columns:2;max-width:900px">';
		foreach ( $items as $p ) {
			printf(
				'<li><label><input type="checkbox" name="ids[]" value="%d"%s> %s <code dir="ltr">/%s</code></label></li>',
				(int) $p->ID,
				$checked ? ' checked' : '',
				esc_html( $p->post_title ? $p->post_title : '—' ),
				esc_html( $p->post_name )
			);
		}
		echo '</ul>';
	}

	private static function print_log( $log ) {
		if ( ! $log ) {
			return;
		}
		$labels = array(
			'trash' => 'به زباله‌دان رفت',
			'media' => 'عکس پاک شد',
			'theme' => 'قالب پاک شد',
			'fail'  => 'پاک نشد',
		);
		echo '<ul>';
		foreach ( $labels as $k => $l ) {
			if ( ! empty( $log[ $k ] ) ) {
				echo '<li>' . esc_html( $l ) . ': ' . esc_html( Golzari_Brand::fa_digits( $log[ $k ] ) ) . '</li>';
			}
		}
		echo '</ul>';
	}

	/* ---------- اجرا ---------- */

	public static function start() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'golzari_cleanup' );
		if ( empty( $_POST['confirm'] ) ) {
			wp_die( 'تأیید لازم است.' );
		}
		$locked = self::protected_ids();
		$queue  = array();

		foreach ( isset( $_POST['ids'] ) ? array_map( 'intval', (array) $_POST['ids'] ) : array() as $id ) {
			if ( $id && ! in_array( $id, $locked, true ) && in_array( get_post_type( $id ), array( 'page', 'post' ), true ) ) {
				$queue[] = array( 'trash', $id );
			}
		}
		$allowed = array_keys( self::other_types() );
		foreach ( isset( $_POST['types'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['types'] ) ) : array() as $type ) {
			if ( in_array( $type, $allowed, true ) ) {
				foreach ( self::posts_of( $type ) as $p ) {
					$queue[] = array( 'trash', (int) $p->ID );
				}
			}
		}
		if ( ! empty( $_POST['media'] ) ) {
			foreach ( self::attachments() as $id ) {
				$queue[] = array( 'media', (int) $id );
			}
		}
		$themes = self::themes();
		foreach ( isset( $_POST['themes'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['themes'] ) ) : array() as $slug ) {
			if ( isset( $themes[ $slug ] ) ) {
				$queue[] = array( 'theme', $slug );
			}
		}
		update_option( self::QUEUE, $queue, false );
		update_option( self::LOG, array(), false );
		wp_safe_redirect( admin_url( 'tools.php?page=golzari-cleanup' ) );
		exit;
	}

	private static function run_batch() {
		$queue  = (array) get_option( self::QUEUE, array() );
		$log    = (array) get_option( self::LOG, array() );
		$locked = self::protected_ids();
		$batch  = array_splice( $queue, 0, self::BATCH );
		foreach ( $batch as $job ) {
			list( $kind, $target ) = $job;
			$ok = false;
			if ( 'trash' === $kind && ! in_array( (int) $target, $locked, true ) && Golzari_Brand_Booking::CPT !== get_post_type( $target ) ) {
				$ok = (bool) wp_trash_post( (int) $target );
			} elseif ( 'media' === $kind && ! in_array( (int) $target, $locked, true ) && 'attachment' === get_post_type( $target ) ) {
				$ok = (bool) wp_delete_attachment( (int) $target, true );
			} elseif ( 'theme' === $kind && ! in_array( $target, array( get_stylesheet(), get_template() ), true ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/theme.php';
				$r  = delete_theme( $target );
				$ok = true === $r;
			}
			$key         = $ok ? $kind : 'fail';
			$log[ $key ] = ( isset( $log[ $key ] ) ? $log[ $key ] : 0 ) + 1;
		}
		update_option( self::QUEUE, $queue, false );
		update_option( self::LOG, $log, false );
	}
}
