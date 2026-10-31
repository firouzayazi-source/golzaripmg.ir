<?php
/**
 * قالبِ «گلزاری»: همه‌ی صفحه‌ها از قالبِ نمایشِ افزونه می‌آیند؛ اگر افزونه نبود، صفحه‌ای ساده.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'GOLZARI_BRAND_DIR' ) && function_exists( 'golzari_label' ) && file_exists( GOLZARI_BRAND_DIR . 'templates/page.php' ) ) {
	require GOLZARI_BRAND_DIR . 'templates/page.php';
	return;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width, initial-scale=1"><?php wp_head(); ?></head>
<body <?php body_class(); ?> style="font-family:Tahoma,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;direction:rtl">
<?php wp_body_open(); ?>
<h1><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
<?php
while ( have_posts() ) {
	the_post();
	the_title( '<h2>', '</h2>' );
	the_content();
}
wp_footer();
?>
</body>
</html>
