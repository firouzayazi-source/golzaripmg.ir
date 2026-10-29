<?php
/**
 * خواندنِ manifest از انتشارِ گیت‌هاب، نشان دادنِ آپدیت در فهرستِ افزونه‌ها و نصبِ خودکار.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Updater {

	const HOOK          = 'golzari_upd_run';
	const SCHEDULE      = 'golzari_15min';
	const STATE         = 'golzari_upd_state';
	const MANIFEST_T    = 'golzari_manifest';
	const MANIFEST_LAST = 'golzari_manifest_last';
	const FAIL_T        = 'golzari_manifest_fail';
	const LOCK_T        = 'golzari_upd_lock';
	const POKE_T        = 'golzari_upd_poke';

	public static function init() {
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) );
		add_action( self::HOOK, array( __CLASS__, 'run' ), 10, 1 );
		add_action( 'init', array( __CLASS__, 'ensure_scheduled' ) );

		// تزریق روی «خواندن»؛ نوشتنِ این مقدار فقط وقتی رخ می‌دهد که api.wordpress.org از ایران جواب بدهد.
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'inject' ) );
		add_filter( 'auto_update_plugin', array( __CLASS__, 'auto_update' ), 10, 2 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 10, 3 );

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_post_golzari_upd_now', array( __CLASS__, 'admin_run_now' ) );
	}

	public static function activate() {
		self::ensure_scheduled();
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	public static function schedules( $schedules ) {
		$schedules[ self::SCHEDULE ] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => 'هر ۱۵ دقیقه',
		);
		return $schedules;
	}

	public static function ensure_scheduled() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 60, self::SCHEDULE, self::HOOK );
		}
	}

	/* ---------- manifest ---------- */

	/**
	 * @param bool $refresh اگر true باشد حافظه‌ی موقتِ manifest نادیده گرفته می‌شود.
	 * @return array|null
	 */
	public static function manifest( $refresh = false ) {
		if ( ! $refresh ) {
			$cached = get_transient( self::MANIFEST_T );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		// بعد از شکست، چند دقیقه دوباره نمی‌زنیم.
		if ( get_transient( self::FAIL_T ) ) {
			return self::last_manifest();
		}

		$res = wp_remote_get(
			add_query_arg( 't', time(), GOLZARI_MANIFEST_URL ),
			array(
				'timeout'     => 15,
				'redirection' => 5,
				'headers'     => array( 'Accept' => 'application/json' ),
			)
		);

		$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
		$data = $code === 200 ? json_decode( wp_remote_retrieve_body( $res ), true ) : null;

		if ( ! is_array( $data ) || empty( $data['plugins'] ) || ! is_array( $data['plugins'] ) ) {
			$msg = is_wp_error( $res ) ? $res->get_error_message() : 'manifest: HTTP ' . $code;
			self::set_state( array( 'last_error' => $msg ) );
			set_transient( self::FAIL_T, 1, 5 * MINUTE_IN_SECONDS );
			return self::last_manifest();
		}

		set_transient( self::MANIFEST_T, $data, 10 * MINUTE_IN_SECONDS );
		update_option( self::MANIFEST_LAST, $data, false );
		self::set_state( array( 'manifest_commit' => isset( $data['commit'] ) ? (string) $data['commit'] : '' ) );
		return $data;
	}

	private static function last_manifest() {
		$last = get_option( self::MANIFEST_LAST );
		return is_array( $last ) ? $last : null;
	}

	/**
	 * افزونه‌هایی از manifest که روی سایت نصب‌اند.
	 *
	 * @return array file => array( slug, installed, info )
	 */
	public static function ours( $manifest = null ) {
		if ( null === $manifest ) {
			$manifest = self::manifest( false );
		}
		if ( ! $manifest ) {
			return array();
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed = get_plugins();
		$out       = array();
		foreach ( $manifest['plugins'] as $slug => $info ) {
			$file = $slug . '/' . $slug . '.php';
			if ( isset( $installed[ $file ] ) && ! empty( $info['version'] ) && ! empty( $info['package'] ) ) {
				$out[ $file ] = array(
					'slug'      => $slug,
					'installed' => $installed[ $file ]['Version'],
					'info'      => $info,
				);
			}
		}
		return $out;
	}

	public static function pending( $manifest = null ) {
		$out = array();
		foreach ( self::ours( $manifest ) as $file => $p ) {
			if ( version_compare( $p['info']['version'], $p['installed'], '>' ) ) {
				$out[ $file ] = $p;
			}
		}
		return $out;
	}

	/* ---------- فهرستِ به‌روزرسانیِ وردپرس ---------- */

	public static function inject( $value ) {
		// بیرون از پیشخوان و زمان‌بندی، از گیت‌هاب نمی‌خوانیم؛ فقط حافظه‌ی موقت.
		$manifest = ( is_admin() || wp_doing_cron() ) ? self::manifest( false ) : get_transient( self::MANIFEST_T );
		if ( ! is_array( $manifest ) ) {
			return $value;
		}
		if ( ! is_object( $value ) ) {
			$value = new stdClass();
		}
		if ( ! isset( $value->response ) || ! is_array( $value->response ) ) {
			$value->response = array();
		}
		if ( ! isset( $value->no_update ) || ! is_array( $value->no_update ) ) {
			$value->no_update = array();
		}

		foreach ( self::ours( $manifest ) as $file => $p ) {
			$item = (object) array(
				'id'           => 'golzari/' . $p['slug'],
				'slug'         => $p['slug'],
				'plugin'       => $file,
				'new_version'  => $p['info']['version'],
				'url'          => 'https://github.com/firouzayazi-source/golzaripmg.ir',
				'package'      => $p['info']['package'],
				'requires_php' => isset( $p['info']['requires_php'] ) ? $p['info']['requires_php'] : '',
				'icons'        => array(),
				'banners'      => array(),
			);
			if ( version_compare( $p['info']['version'], $p['installed'], '>' ) ) {
				$value->response[ $file ] = $item;
				unset( $value->no_update[ $file ] );
			} else {
				unset( $value->response[ $file ] );
				$value->no_update[ $file ] = $item;
			}
		}
		return $value;
	}

	public static function auto_update( $update, $item ) {
		if ( is_object( $item ) && ! empty( $item->id ) && 0 === strpos( (string) $item->id, 'golzari/' ) ) {
			return true;
		}
		return $update;
	}

	/** پنجره‌ی «جزئیات» در فهرستِ افزونه‌ها برای افزونه‌های ما خالی نماند. */
	public static function plugins_api( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $result;
		}
		$manifest = self::manifest( false );
		if ( ! $manifest || empty( $manifest['plugins'][ $args->slug ] ) ) {
			return $result;
		}
		$info = $manifest['plugins'][ $args->slug ];
		return (object) array(
			'name'          => isset( $info['name'] ) ? $info['name'] : $args->slug,
			'slug'          => $args->slug,
			'version'       => $info['version'],
			'download_link' => $info['package'],
			'sections'      => array( 'description' => 'افزونه‌ی سفارشیِ سایت.' ),
		);
	}

	/* ---------- نصبِ خودکار ---------- */

	/**
	 * @param mixed $args برای زمان‌بندی‌های تک‌باره، فقط برای یکتا شدنِ رویداد.
	 * @return array نتیجه
	 */
	public static function run( $args = null ) {
		if ( get_transient( self::LOCK_T ) ) {
			return array( 'skipped' => 'locked' );
		}
		set_transient( self::LOCK_T, 1, 10 * MINUTE_IN_SECONDS );

		$result = array( 'upgraded' => array(), 'failed' => array() );
		try {
			$manifest = self::manifest( true );
			if ( ! $manifest ) {
				return $result;
			}
			$pending = self::pending( $manifest );
			$purge   = false;

			if ( $pending ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/misc.php';
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

				foreach ( $pending as $file => $p ) {
					$was_active = is_plugin_active( $file );
					$upgrader   = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
					$ok         = $upgrader->upgrade( $file );
					wp_clean_plugins_cache( false );

					if ( true === $ok ) {
						if ( $was_active && ! is_plugin_active( $file ) ) {
							activate_plugin( $file, '', false, true );
						}
						$result['upgraded'][ $p['slug'] ] = $p['info']['version'];
						if ( ! isset( $p['info']['purge'] ) || $p['info']['purge'] ) {
							$purge = true;
						}
					} else {
						$err = is_wp_error( $ok ) ? $ok : $upgrader->skin->result;
						$result['failed'][ $p['slug'] ] = is_wp_error( $err ) ? $err->get_error_message() : 'نامعلوم';
					}
				}
			}

			if ( $purge ) {
				self::purge();
			}

			$left  = self::pending( $manifest );
			$state = array(
				'last_check'  => time(),
				'last_result' => $result,
			);
			if ( ! $left ) {
				$state['deployed_commit'] = isset( $manifest['commit'] ) ? (string) $manifest['commit'] : '';
				$state['last_error']      = '';
			} elseif ( $result['failed'] ) {
				$state['last_error'] = wp_json_encode( $result['failed'], JSON_UNESCAPED_UNICODE );
			}
			self::set_state( $state );
		} finally {
			delete_transient( self::LOCK_T );
		}
		return $result;
	}

	/**
	 * درخواستِ وارسیِ فوری از راهِ نشانیِ سلامت؛ هر ۶۰ ثانیه حداکثر یک بار.
	 */
	public static function poke() {
		if ( get_transient( self::POKE_T ) ) {
			return false;
		}
		set_transient( self::POKE_T, 1, MINUTE_IN_SECONDS );
		delete_transient( self::MANIFEST_T );
		wp_schedule_single_event( time(), self::HOOK, array( array( 'poke' => time() ) ) );
		spawn_cron();
		return true;
	}

	/** پاک کردنِ حافظه‌ی موقتِ همین وردپرس (نه کلِ سرور). */
	public static function purge() {
		do_action( 'litespeed_purge_all' );
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		wp_cache_flush();
		do_action( 'golzari_purged' );
	}

	/* ---------- وضعیت ---------- */

	public static function state() {
		$s = get_option( self::STATE );
		return wp_parse_args(
			is_array( $s ) ? $s : array(),
			array(
				'last_check'      => 0,
				'last_error'      => '',
				'last_result'     => array(),
				'deployed_commit' => '',
				'manifest_commit' => '',
			)
		);
	}

	private static function set_state( $changes ) {
		update_option( self::STATE, array_merge( self::state(), $changes ), false );
	}

	/* ---------- صفحه‌ی مدیر ---------- */

	public static function admin_menu() {
		add_management_page( 'به‌روزرسانِ گلزاری', 'به‌روزرسانِ گلزاری', 'manage_options', 'golzari-updater', array( __CLASS__, 'admin_page' ) );
	}

	public static function admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s    = self::state();
		$ours = self::ours();
		echo '<div class="wrap" dir="rtl"><h1>به‌روزرسانِ گلزاری</h1>';
		if ( isset( $_GET['done'] ) ) {
			echo '<div class="notice notice-success"><p>وارسی انجام شد.</p></div>';
		}
		echo '<table class="widefat striped" style="max-width:40rem"><tbody>';
		printf( '<tr><th>کامیتِ منتشرشده</th><td><code>%s</code></td></tr>', esc_html( substr( $s['deployed_commit'], 0, 7 ) ) );
		printf( '<tr><th>کامیتِ آخرین انتشار</th><td><code>%s</code></td></tr>', esc_html( substr( $s['manifest_commit'], 0, 7 ) ) );
		printf( '<tr><th>آخرین وارسی</th><td>%s</td></tr>', $s['last_check'] ? esc_html( date_i18n( 'Y/m/d H:i', $s['last_check'] ) ) : '—' );
		printf( '<tr><th>آخرین خطا</th><td>%s</td></tr>', $s['last_error'] ? '<code>' . esc_html( $s['last_error'] ) . '</code>' : '—' );
		echo '</tbody></table><h2>افزونه‌ها</h2><table class="widefat striped" style="max-width:40rem"><tbody>';
		foreach ( $ours as $p ) {
			printf( '<tr><td>%s</td><td><code>%s</code> ← <code>%s</code></td></tr>', esc_html( $p['slug'] ), esc_html( $p['installed'] ), esc_html( $p['info']['version'] ) );
		}
		echo '</tbody></table><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:1rem">';
		wp_nonce_field( 'golzari_upd_now' );
		echo '<input type="hidden" name="action" value="golzari_upd_now">';
		submit_button( 'وارسی و نصبِ همین الان', 'primary', 'submit', false );
		echo '</form></div>';
	}

	public static function admin_run_now() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی ندارید.' );
		}
		check_admin_referer( 'golzari_upd_now' );
		delete_transient( self::FAIL_T );
		self::run();
		wp_safe_redirect( admin_url( 'tools.php?page=golzari-updater&done=1' ) );
		exit;
	}
}
