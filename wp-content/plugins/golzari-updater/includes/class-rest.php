<?php
/**
 * نشانی‌های /wp-json/golzari/v1/…
 * health عمومی است؛ بقیه فقط‌خواندنی و فقط برای مدیرِ کل.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Golzari_Rest {

	const NS        = 'golzari/v1';
	const CHUNK_MAX = 262144;

	const TEXT_EXT = array( 'php', 'css', 'js', 'json', 'html', 'htm', 'txt', 'xml', 'svg', 'md', 'po', 'pot', 'scss', 'less', 'twig' );

	const MASK_RE = '/(pass|pwd|secret|token|api_?key|apikey|private|auth|licen[cs]e|merchant|terminal|gateway|zarinpal|idpay|payir|zibal|nextpay|sms|kavenegar|melipayamak|farazsms|ippanel|otp|salt|_key$|^key$|درگاه|پیامک|رمز)/iu';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/health',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'health' ),
				'permission_callback' => '__return_true',
			)
		);

		$admin = array( __CLASS__, 'is_admin' );
		register_rest_route( self::NS, '/status', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'status' ), 'permission_callback' => $admin ) );
		register_rest_route( self::NS, '/theme-files', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'theme_files' ), 'permission_callback' => $admin ) );
		register_rest_route( self::NS, '/theme-file', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'theme_file' ), 'permission_callback' => $admin ) );
		register_rest_route( self::NS, '/theme-options', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'theme_options' ), 'permission_callback' => $admin ) );
	}

	public static function is_admin() {
		return current_user_can( 'manage_options' );
	}

	private static function nocache( $data ) {
		do_action( 'litespeed_control_set_nocache', 'golzari rest' );
		$res = rest_ensure_response( $data );
		$res->header( 'Cache-Control', 'no-store, max-age=0' );
		return $res;
	}

	/* ---------- سلامت ---------- */

	public static function health( WP_REST_Request $req ) {
		$poked = false;
		if ( $req->get_param( 'refresh' ) ) {
			$poked = Golzari_Updater::poke();
		}
		$state   = Golzari_Updater::state();
		$plugins = array();
		foreach ( Golzari_Updater::ours( get_option( Golzari_Updater::MANIFEST_LAST ) ?: null ) as $file => $p ) {
			$plugins[ $p['slug'] ] = array(
				'version' => $p['installed'],
				'active'  => is_plugin_active( $file ),
			);
		}
		$plugins['golzari-updater']['version'] = GOLZARI_UPD_VER;
		$plugins['golzari-updater']['active']  = true;

		return self::nocache(
			array(
				'ok'              => true,
				'commit'          => $state['deployed_commit'],
				'manifest_commit' => $state['manifest_commit'],
				'last_check'      => $state['last_check'] ? gmdate( 'c', $state['last_check'] ) : null,
				'last_error'      => $state['last_error'],
				'plugins'         => $plugins,
				'wp'              => get_bloginfo( 'version' ),
				'php'             => PHP_VERSION,
				'poked'           => $poked,
				'time'            => gmdate( 'c' ),
			)
		);
	}

	/* ---------- وضعیت ---------- */

	public static function status() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$theme   = wp_get_theme();
		$parent  = $theme->parent();
		$plugins = array();
		foreach ( get_plugins() as $file => $p ) {
			$plugins[] = array(
				'file'    => $file,
				'name'    => $p['Name'],
				'version' => $p['Version'],
				'active'  => is_plugin_active( $file ),
			);
		}

		return self::nocache(
			array(
				'theme'      => array(
					'name'       => $theme->get( 'Name' ),
					'version'    => $theme->get( 'Version' ),
					'stylesheet' => $theme->get_stylesheet(),
					'template'   => $theme->get_template(),
					'parent'     => $parent ? array( 'name' => $parent->get( 'Name' ), 'version' => $parent->get( 'Version' ) ) : null,
				),
				'plugins'    => $plugins,
				'wp'         => get_bloginfo( 'version' ),
				'php'        => PHP_VERSION,
				'locale'     => get_locale(),
				'timezone'   => wp_timezone_string(),
				'permalinks' => get_option( 'permalink_structure' ),
				'memory'     => ini_get( 'memory_limit' ),
				'max_exec'   => ini_get( 'max_execution_time' ),
				'fs_method'  => get_filesystem_method(),
				'cron_off'   => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
				'next_check' => wp_next_scheduled( Golzari_Updater::HOOK ) ? gmdate( 'c', wp_next_scheduled( Golzari_Updater::HOOK ) ) : null,
				'updater'    => Golzari_Updater::state(),
			)
		);
	}

	/* ---------- فایل‌های قالب ---------- */

	private static function theme_root( WP_REST_Request $req ) {
		return 'child' === $req->get_param( 'theme' ) ? get_stylesheet_directory() : get_template_directory();
	}

	public static function theme_files( WP_REST_Request $req ) {
		$root  = realpath( self::theme_root( $req ) );
		$files = array();
		if ( $root ) {
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( count( $files ) >= 5000 ) {
					break;
				}
				$rel = ltrim( str_replace( '\\', '/', substr( $f->getPathname(), strlen( $root ) ) ), '/' );
				if ( false !== strpos( $rel, 'node_modules/' ) ) {
					continue;
				}
				$files[] = array( 'path' => $rel, 'size' => $f->getSize() );
			}
		}
		return self::nocache( array( 'root' => basename( (string) $root ), 'count' => count( $files ), 'files' => $files ) );
	}

	/** فایلِ بزرگ تکه‌تکه؛ محتوا base64 است تا حروفِ چندبایتی وسطِ تکه نشکنند. */
	public static function theme_file( WP_REST_Request $req ) {
		$root = realpath( self::theme_root( $req ) );
		$path = realpath( $root . '/' . ltrim( (string) $req->get_param( 'path' ), '/' ) );

		if ( ! $root || ! $path || 0 !== strpos( $path, $root . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
			return new WP_Error( 'golzari_not_found', 'فایل پیدا نشد.', array( 'status' => 404 ) );
		}
		if ( ! in_array( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ), self::TEXT_EXT, true ) ) {
			return new WP_Error( 'golzari_type', 'فقط فایلِ متنی.', array( 'status' => 400 ) );
		}

		$size   = filesize( $path );
		$offset = max( 0, (int) $req->get_param( 'offset' ) );
		$length = (int) $req->get_param( 'length' );
		$length = $length > 0 ? min( $length, self::CHUNK_MAX ) : self::CHUNK_MAX;
		$chunk  = (string) file_get_contents( $path, false, null, $offset, $length );

		return self::nocache(
			array(
				'path'     => substr( $path, strlen( $root ) + 1 ),
				'size'     => $size,
				'offset'   => $offset,
				'length'   => strlen( $chunk ),
				'eof'      => $offset + strlen( $chunk ) >= $size,
				'encoding' => 'base64',
				'content'  => base64_encode( $chunk ),
			)
		);
	}

	/* ---------- تنظیماتِ قالب ---------- */

	public static function theme_options( WP_REST_Request $req ) {
		global $wpdb;

		$name = (string) $req->get_param( 'name' );
		if ( '' !== $name ) {
			$value = get_option( $name, null );
			return self::nocache(
				array(
					'name'  => $name,
					'type'  => gettype( $value ),
					'value' => self::mask( $value ),
				)
			);
		}

		$theme  = wp_get_theme();
		$slugs  = array_unique( array( $theme->get_stylesheet(), $theme->get_template() ) );
		$out    = array();
		foreach ( $slugs as $slug ) {
			$out[ 'theme_mods_' . $slug ] = self::mask( get_option( 'theme_mods_' . $slug, array() ) );
		}

		// گزینه‌هایی که نامشان نامِ قالب را دارد (بی حافظه‌ی موقت‌ها).
		$names = array();
		foreach ( $slugs as $slug ) {
			$like  = '%' . $wpdb->esc_like( $slug ) . '%';
			$names = array_merge(
				$names,
				(array) $wpdb->get_col(
					$wpdb->prepare(
						"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_name NOT LIKE %s AND option_name NOT LIKE %s LIMIT 50",
						$like,
						'\_transient%',
						'\_site\_transient%'
					)
				)
			);
		}
		$others = array();
		foreach ( array_unique( $names ) as $n ) {
			if ( 0 !== strpos( $n, 'theme_mods_' ) ) {
				$others[ $n ] = gettype( get_option( $n ) );
			}
		}

		return self::nocache( array( 'mods' => $out, 'options' => $others ) );
	}

	/** کلیدهای رمز، توکن، درگاه و پیامک با *** پوشانده می‌شوند. */
	public static function mask( $value, $key = '' ) {
		if ( '' !== $key && preg_match( self::MASK_RE, (string) $key ) && ! is_array( $value ) && ! is_object( $value ) ) {
			return ( '' === $value || null === $value ) ? $value : '***';
		}
		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}
		if ( is_array( $value ) ) {
			$out = array();
			foreach ( $value as $k => $v ) {
				$child_key = ( '' !== $key && preg_match( self::MASK_RE, (string) $key ) ) ? $key : (string) $k;
				$out[ $k ] = self::mask( $v, $child_key );
			}
			return $out;
		}
		return $value;
	}
}
