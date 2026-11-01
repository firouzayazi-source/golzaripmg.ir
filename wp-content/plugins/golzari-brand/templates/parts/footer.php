<?php
/**
 * پانویسِ مجموعه، دکمه‌ی شناورِ رزرو و پایانِ سند.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<footer class="gz-footer" id="contact">
	<div class="gz-wrap gz-footer__in">
		<div class="gz-footer__brand">
			<img src="<?php echo $h::img( 'logo-160' ); ?>" width="160" height="160" decoding="async" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>" loading="lazy">
			<strong><?php echo esc_html( $l( 'brand_name' ) ); ?></strong>
			<p><?php echo esc_html( $l( 'foot_about' ) ); ?></p>
			<a class="gz-social" href="<?php echo $insta; ?>" target="_blank" rel="noopener"><?php echo $icon['insta']; ?><span dir="ltr">@<?php echo esc_html( $l( 'instagram' ) ); ?></span></a>
		</div>
		<div>
			<h3><?php echo esc_html( $l( 'foot_links' ) ); ?></h3>
			<ul class="gz-links">
				<?php foreach ( $nav as $id => $key ) : ?>
					<li><a href="<?php echo esc_url( $h::anchor( $id ) ); ?>"><?php echo esc_html( $l( $key ) ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?></a></li>
			</ul>
		</div>
		<div>
			<h3><?php echo esc_html( $l( 'foot_contact' ) ); ?></h3>
			<ul class="gz-contact">
				<li><?php echo $icon['pin']; ?><span><?php echo esc_html( $l( 'address' ) ); ?></span></li>
				<li><?php echo $icon['phone']; ?><a href="<?php echo esc_url( $h::tel( $phone ) ); ?>" dir="ltr"><?php echo esc_html( $phone_f ); ?></a></li>
				<li><?php echo $icon['phone']; ?><a href="<?php echo esc_url( $h::tel( $l( 'phone2' ) ) ); ?>" dir="ltr"><?php echo esc_html( $l( 'phone2' ) ); ?></a></li>
				<li><?php echo $icon['insta']; ?><a href="<?php echo $insta; ?>" target="_blank" rel="noopener" dir="ltr">@<?php echo esc_html( $l( 'instagram' ) ); ?></a></li>
			</ul>
		</div>
	</div>
	<div class="gz-footer__bar">
		<div class="gz-wrap">© <?php echo esc_html( $h::jalali_year() ); ?> — <?php echo esc_html( $l( 'footer' ) ); ?></div>
	</div>
</footer>

<a class="gz-float" href="<?php echo $booking; ?>" aria-label="<?php echo esc_attr( $l( 'h_cta' ) ); ?>"><?php echo $icon['cal']; ?><span><?php echo esc_html( $l( 'h_cta' ) ); ?></span></a>

<dialog class="gz-lightbox" id="gz-lightbox"><button type="button" class="gz-lightbox__close" aria-label="×">×</button><img alt=""></dialog>

<?php wp_footer(); ?>
</body>
</html>
