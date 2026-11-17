<?php
/**
 * به‌روز نگه داشتنِ خودِ وردپرس: نسخه‌های کوچکِ هسته، همه‌ی افزونه‌ها و ترجمه‌ها
 * با به‌روزرسانِ خودکارِ خودِ وردپرس (که اگر افزونه‌ای خطای مرگبار بدهد برش می‌گرداند).
 * نسخه‌ی بزرگِ هسته خودکار نصب نمی‌شود؛ فقط در نشانیِ سلامت گزارش می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Site_Updates {

	const HOOK   = 'golzari_site_updates_now';
	const STATE  = 'golzari_site_updates';
	const ROUND  = 1; // برای اجرای فوریِ دوباره، این عدد بالا می‌رود.

	public static function init() {
		add_filter( 'golzari_health_extra', array( __CLASS__, 'health' ) );
		// وردپرسِ آزمون (local) چیزی از بیرون به‌روز نمی‌کند.
		if ( function_exists( 'wp_get_environment_type' ) && in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			return;
		}
		add_filter( 'auto_update_plugin', array( __CLASS__, 'plugins' ), 20, 2 );
		add_filter( 'auto_update_translation', '__return_true' );
		add_filter( 'allow_minor_auto_core_updates', '__return_true' );
		add_filter( 'allow_major_auto_core_updates', '__return_false' );
		add_filter( 'auto_core_update_send_email', '__return_false' );
		add_filter( 'auto_plugin_update_send_email', '__return_false' );

		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		add_action( 'init', array( __CLASS__, 'schedule_once' ) );
		add_action( 'automatic_updates_complete', array( __CLASS__, 'record' ) );
	}

	public static function plugins( $update, $item ) {
		return true;
	}

	private static function state() {
		$s = get_option( self::STATE );
		return is_array( $s ) ? $s : array();
	}

	private static function save( $changes ) {
		update_option( self::STATE, array_merge( self::state(), $changes ), false );
	}

	/** یک بار برای هر دور، یک دقیقه بعد. */
	public static function schedule_once() {
		$s = self::state();
		if ( ( isset( $s['round'] ) ? (int) $s['round'] : 0 ) >= self::ROUND || wp_next_scheduled( self::HOOK ) ) {
			return;
		}
		self::save( array( 'round' => self::ROUND, 'scheduled' => time() ) );
		wp_schedule_single_event( time() + 60, self::HOOK );
	}

	public static function run() {
		require_once ABSPATH . 'wp-admin/includes/update.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		self::save( array( 'started' => time(), 'error' => '' ) );
		try {
			if ( function_exists( 'wp_clean_update_cache' ) ) {
				wp_clean_update_cache();
			}
			wp_version_check( array(), true );
			wp_update_plugins();
			wp_update_themes();

			$updater = new WP_Automatic_Updater();
			if ( $updater->is_disabled() ) {
				self::save( array( 'error' => 'disabled' ) );
				return;
			}
			wp_maybe_auto_update();
		} catch ( Throwable $e ) {
			self::save( array( 'error' => $e->getMessage() ) );
		}
		self::save( array( 'finished' => time() ) );
	}

	/** نتیجه‌ی هر دورِ به‌روزرسانیِ خودکارِ وردپرس. */
	public static function record( $results ) {
		$out = array();
		foreach ( (array) $results as $type => $items ) {
			foreach ( (array) $items as $r ) {
				$name  = isset( $r->name ) ? wp_strip_all_tags( (string) $r->name ) : '';
				$ver   = isset( $r->item->new_version ) ? (string) $r->item->new_version : ( isset( $r->item->version ) ? (string) $r->item->version : '' );
				$ok    = ! is_wp_error( $r->result ) && ! empty( $r->result );
				$out[] = array(
					'type' => $type,
					'name' => $name,
					'to'   => $ver,
					'ok'   => $ok,
					'err'  => is_wp_error( $r->result ) ? $r->result->get_error_code() : '',
				);
			}
		}
		self::save( array( 'last' => time(), 'results' => $out ) );
		if ( $out && class_exists( 'Golzari_Updater' ) ) {
			Golzari_Updater::purge();
		}
	}

	public static function health( $extra ) {
		$core    = get_site_transient( 'update_core' );
		$latest  = '';
		$major   = '';
		if ( is_object( $core ) && ! empty( $core->updates ) ) {
			foreach ( $core->updates as $o ) {
				if ( isset( $o->response ) && 'upgrade' === $o->response && ! empty( $o->current ) ) {
					$latest = $latest ? $latest : (string) $o->current;
				}
				if ( isset( $o->current ) && version_compare( implode( '.', array_slice( explode( '.', (string) $o->current ), 0, 2 ) ), implode( '.', array_slice( explode( '.', get_bloginfo( 'version' ) ), 0, 2 ) ), '>' ) ) {
					$major = (string) $o->current;
				}
			}
		}
		$plugins = array();
		$tr      = get_site_transient( 'update_plugins' );
		if ( is_object( $tr ) && ! empty( $tr->response ) ) {
			foreach ( $tr->response as $file => $u ) {
				if ( 0 === strpos( $file, 'golzari-' ) ) {
					continue;
				}
				$plugins[ dirname( $file ) ] = isset( $u->new_version ) ? (string) $u->new_version : '';
			}
		}
		$s = self::state();
		$extra['updates'] = array(
			'wp'       => get_bloginfo( 'version' ),
			'core_new' => $latest,
			'major'    => $major,
			'plugins'  => $plugins,
			'started'  => isset( $s['started'] ) ? gmdate( 'c', (int) $s['started'] ) : '',
			'last'     => isset( $s['last'] ) ? gmdate( 'c', (int) $s['last'] ) : '',
			'results'  => isset( $s['results'] ) ? $s['results'] : array(),
			'error'    => isset( $s['error'] ) ? $s['error'] : '',
		);
		return $extra;
	}
}
