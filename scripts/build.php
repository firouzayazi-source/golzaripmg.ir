<?php
/**
 * ساختِ dist/: zipِ هر افزونه و manifest.json.
 *
 * php scripts/build.php <commit> <download-base-url>
 */

require __DIR__ . '/lib.php';

$root   = dirname( __DIR__ );
$commit = isset( $argv[1] ) ? $argv[1] : trim( (string) shell_exec( 'git -C ' . escapeshellarg( $root ) . ' rev-parse HEAD' ) );
$base   = isset( $argv[2] ) ? rtrim( $argv[2], '/' ) : 'https://github.com/firouzayazi-source/golzaripmg.ir/releases/download/latest';
$dist   = "$root/dist";

if ( is_dir( $dist ) ) {
	exec( 'rm -rf ' . escapeshellarg( $dist ) );
}
mkdir( $dist );

$manifest = array(
	'commit'  => $commit,
	'built'   => gmdate( 'c' ),
	'plugins' => array(),
);

foreach ( golzari_slugs( $root ) as $slug ) {
	$src     = file_get_contents( "$root/" . GOLZARI_PLUGINS_DIR . "/$slug/$slug.php" );
	$version = golzari_header( $src, 'Version' );
	$zip     = "$slug-$version.zip";

	$cmd = 'cd ' . escapeshellarg( "$root/" . GOLZARI_PLUGINS_DIR ) . ' && zip -qr -X ' . escapeshellarg( "$dist/$zip" ) . ' ' . escapeshellarg( $slug ) . " -x '*.DS_Store' -x '*/node_modules/*'";
	exec( $cmd, $out, $code );
	if ( 0 !== $code ) {
		golzari_fail( "ساختِ zipِ $slug نشد" );
		exit( 1 );
	}

	$manifest['plugins'][ $slug ] = array(
		'name'         => golzari_header( $src, 'Plugin Name' ),
		'version'      => $version,
		'package'      => "$base/$zip",
		'requires_php' => golzari_header( $src, 'Requires PHP' ),
		'purge'        => 'no' !== strtolower( golzari_header( $src, 'Golzari Purge' ) ),
	);
	echo "✓ $zip\n";
}

file_put_contents( "$dist/manifest.json", json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" );
echo "✓ manifest.json ($commit)\n";
