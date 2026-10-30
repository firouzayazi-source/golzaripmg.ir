<?php
/**
 * Plugin Name: گلزاری — به‌روزرسان
 * Description: افزونه‌های سفارشیِ سایت را از انتشارِ گیت‌هاب می‌خواند، در فهرستِ به‌روزرسانی نشان می‌دهد و هر ۱۵ دقیقه خودکار نصب می‌کند. نشانیِ سلامت و نشانی‌های فقط‌خواندنیِ مدیر را هم می‌سازد.
 * Version: 1.1.0
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: گلزاری
 * Text Domain: golzari-updater
 * Golzari Purge: no
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GOLZARI_UPD_VER', '1.1.0' );
define( 'GOLZARI_UPD_FILE', __FILE__ );
define( 'GOLZARI_UPD_DIR', plugin_dir_path( __FILE__ ) );

if ( ! defined( 'GOLZARI_MANIFEST_URL' ) ) {
	define( 'GOLZARI_MANIFEST_URL', 'https://github.com/firouzayazi-source/golzaripmg.ir/releases/download/latest/manifest.json' );
}

require_once GOLZARI_UPD_DIR . 'includes/class-updater.php';
require_once GOLZARI_UPD_DIR . 'includes/class-rest.php';

Golzari_Updater::init();
Golzari_Rest::init();

register_activation_hook( __FILE__, array( 'Golzari_Updater', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Golzari_Updater', 'deactivate' ) );
