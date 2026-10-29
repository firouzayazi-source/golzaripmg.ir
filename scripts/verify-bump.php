<?php
/**
 * اگر فایل‌های یک افزونه نسبت به BASE عوض شده ولی Version بالا نرفته، قرمز می‌کند.
 * این گام هیچ‌وقت از ورک‌فلو حذف نمی‌شود.
 *
 * php scripts/verify-bump.php <base-ref>
 */

require __DIR__ . '/lib.php';

$root = dirname( __DIR__ );
$base = isset( $argv[1] ) ? $argv[1] : '';
chdir( $root );

if ( '' === $base || preg_match( '/^0+$/', $base ) ) {
	echo "پایه‌ای برای مقایسه نیست (اولین پوش)؛ وارسیِ نسخه رد شد.\n";
	exit( 0 );
}

exec( 'git rev-parse --verify --quiet ' . escapeshellarg( $base . '^{commit}' ), $o, $code );
if ( 0 !== $code ) {
	golzari_fail( "کامیتِ پایه پیدا نشد: $base (fetch-depth: 0 لازم است)" );
	exit( 1 );
}

$bad = 0;
foreach ( golzari_slugs( $root ) as $slug ) {
	$dir     = GOLZARI_PLUGINS_DIR . "/$slug";
	$changed = array();
	exec( 'git diff --name-only ' . escapeshellarg( $base ) . ' HEAD -- ' . escapeshellarg( $dir ), $changed );
	if ( ! $changed ) {
		continue;
	}

	$main = "$dir/$slug.php";
	$new  = golzari_header( file_get_contents( $main ), 'Version' );
	$old_src = array();
	exec( 'git show ' . escapeshellarg( "$base:$main" ) . ' 2>/dev/null', $old_src, $c );
	if ( 0 !== $c ) {
		echo "• $slug: افزونه‌ی تازه، نسخه‌ی $new\n";
		continue;
	}
	$old = golzari_header( implode( "\n", $old_src ), 'Version' );

	if ( version_compare( $new, $old, '>' ) ) {
		echo "✓ $slug: $old ← $new\n";
	} else {
		golzari_fail( "$slug: " . count( $changed ) . " فایل عوض شده ولی نسخه بالا نرفته ($old ← $new). Version و ثابتِ نسخه را با هم بالا ببرید." );
		$bad++;
	}
}

exit( $bad ? 1 : 0 );
