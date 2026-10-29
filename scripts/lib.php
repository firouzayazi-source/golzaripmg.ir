<?php
/**
 * توابعِ مشترکِ اسکریپت‌ها.
 */

const GOLZARI_PLUGINS_DIR = 'wp-content/plugins';

/** @return string[] نامکِ افزونه‌هایی که فایلِ اصلیِ <slug>/<slug>.php دارند. */
function golzari_slugs( $root ) {
	$out = array();
	foreach ( glob( $root . '/' . GOLZARI_PLUGINS_DIR . '/*', GLOB_ONLYDIR ) as $dir ) {
		$slug = basename( $dir );
		if ( is_file( "$dir/$slug.php" ) ) {
			$out[] = $slug;
		}
	}
	sort( $out );
	return $out;
}

/** سرآیندِ افزونه، به همان شیوه‌ی وردپرس. */
function golzari_header( $content, $name ) {
	if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $name, '/' ) . ':(.*)$/mi', $content, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

/** مقدارِ ثابتِ نسخه، مثلِ define( 'XXX_VER', '1.2.3' ). */
function golzari_ver_const( $content ) {
	if ( preg_match( "/define\(\s*'([A-Z0-9_]+_VER)'\s*,\s*'([^']+)'\s*\)/", $content, $m ) ) {
		return array( $m[1], $m[2] );
	}
	return null;
}

function golzari_fail( $msg ) {
	fwrite( STDERR, "✗ $msg\n" );
}
