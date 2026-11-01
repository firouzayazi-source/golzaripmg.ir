<?php
/**
 * سربرگِ مجموعه.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<header class="gz-header" id="gz-header">
	<div class="gz-wrap gz-header__in">
		<a class="gz-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo $h::img( 'logo-160' ); ?>" width="160" height="160" decoding="async" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>">
			<span><strong><?php echo esc_html( $l( 'brand_name' ) ); ?></strong><small><?php echo esc_html( $l( 'collection' ) ); ?></small></span>
		</a>
		<nav class="gz-nav" id="gz-nav" aria-label="<?php echo esc_attr( $l( 'nav_home' ) ); ?>">
			<?php foreach ( $nav as $id => $key ) : ?>
				<a href="<?php echo esc_url( $h::anchor( $id ) ); ?>"><?php echo esc_html( $l( $key ) ); ?></a>
			<?php endforeach; ?>
			<a class="gz-btn gz-btn--gold gz-nav__cta" href="<?php echo $booking; ?>"><?php echo $icon['cal']; ?><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
		</nav>
		<a class="gz-btn gz-btn--gold gz-header__cta" href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
		<button class="gz-burger" id="gz-burger" type="button" aria-controls="gz-nav" aria-expanded="false" aria-label="<?php echo esc_attr( $l( 'nav_home' ) ); ?>"><span></span><span></span><span></span></button>
	</div>
</header>
