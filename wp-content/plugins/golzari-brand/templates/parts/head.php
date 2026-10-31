<?php
/**
 * آغازِ سند تا پیش از سربرگ.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#4f7e6e">
<?php wp_head(); ?>
</head>
<body <?php body_class( isset( $gz_body ) ? $gz_body : 'gz' ); ?>>
<?php wp_body_open(); ?>

