<?php
/**
 * صفحه‌ی رزروِ نوبتِ مجموعه‌ی گلزاری، با سربرگ و پانویسِ مجموعه.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/parts/vars.php';
$gz_body = 'gz gz-booking';
$steps   = array( 'b_step1', 'b_step2', 'b_step3', 'b_step4' );
require __DIR__ . '/parts/head.php';
require __DIR__ . '/parts/header.php';
?>

<main id="top">

	<section class="gz-pagehead">
		<div class="gz-wrap">
			<span class="gz-kicker"><?php echo esc_html( $l( 'brand_name' ) ); ?></span>
			<h1><?php echo esc_html( $l( 'b_title' ) ); ?></h1>
			<p><?php echo esc_html( $l( 'b_lead' ) ); ?></p>
		</div>
	</section>

	<section class="gz-section gz-book">
		<div class="gz-wrap gz-book__in">

			<form class="gz-bookform" id="gz-bookform" novalidate>
				<ol class="gz-steps" aria-hidden="true">
					<?php foreach ( $steps as $i => $s ) : ?>
						<li data-step="<?php echo (int) $i + 1; ?>"<?php echo 0 === $i ? ' class="is-on"' : ''; ?>><b><?php echo esc_html( Golzari_Brand::fa_digits( $i + 1 ) ); ?></b><span><?php echo esc_html( $l( $s ) ); ?></span></li>
					<?php endforeach; ?>
				</ol>

				<fieldset class="gz-field">
					<legend><?php echo esc_html( Golzari_Brand::fa_digits( 1 ) . '. ' . $l( 'b_step1' ) ); ?></legend>
					<div class="gz-choices" id="gz-services">
						<?php foreach ( Golzari_Brand_Booking::services() as $key => $name ) : ?>
							<label class="gz-choice"><input type="radio" name="service" value="<?php echo esc_attr( $key ); ?>"><span><?php echo esc_html( $name ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</fieldset>

				<fieldset class="gz-field">
					<legend><?php echo esc_html( Golzari_Brand::fa_digits( 2 ) . '. ' . $l( 'b_step2' ) ); ?></legend>
					<div class="gz-days" id="gz-days"><p class="gz-muted"><?php echo esc_html( $l( 'b_loading' ) ); ?></p></div>
				</fieldset>

				<fieldset class="gz-field">
					<legend><?php echo esc_html( Golzari_Brand::fa_digits( 3 ) . '. ' . $l( 'b_step3' ) ); ?></legend>
					<div class="gz-times" id="gz-times"><p class="gz-muted">—</p></div>
				</fieldset>

				<fieldset class="gz-field">
					<legend><?php echo esc_html( Golzari_Brand::fa_digits( 4 ) . '. ' . $l( 'b_step4' ) ); ?></legend>
					<div class="gz-inputs">
						<label><span><?php echo esc_html( $l( 'b_name' ) ); ?></span><input type="text" name="name" autocomplete="name" required></label>
						<label><span><?php echo esc_html( $l( 'b_phone' ) ); ?></span><input type="tel" name="phone" inputmode="tel" autocomplete="tel" dir="ltr" placeholder="۰۹۱۲۱۲۳۴۵۶۷" required></label>
						<label class="gz-inputs__wide"><span><?php echo esc_html( $l( 'b_note' ) ); ?></span><textarea name="note" rows="3"></textarea></label>
						<label class="gz-hp" aria-hidden="true">website<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					</div>
				</fieldset>

				<p class="gz-formerr" id="gz-formerr" role="alert" hidden></p>
				<button class="gz-btn gz-btn--gold gz-bookform__submit" type="submit"><?php echo $icon['cal']; ?><span><?php echo esc_html( $l( 'b_submit' ) ); ?></span></button>
				<noscript><p class="gz-formerr"><?php echo esc_html( $l( 'cta_text' ) ); ?> <a href="<?php echo esc_url( $h::tel( $phone ) ); ?>" dir="ltr"><?php echo esc_html( $phone_f ); ?></a></p></noscript>
			</form>

			<div class="gz-done" id="gz-done" hidden>
				<div class="gz-done__icon"><?php echo $icon['check']; ?></div>
				<h2><?php echo esc_html( $l( 'b_done_title' ) ); ?></h2>
				<dl id="gz-done-list"></dl>
				<p><?php echo esc_html( $l( 'b_done_text' ) ); ?></p>
				<a class="gz-btn gz-btn--ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $l( 'nav_home' ) ); ?></a>
			</div>

			<aside class="gz-bookside">
				<img src="<?php echo $h::img( 'logo' ); ?>" width="512" height="512" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>" loading="lazy">
				<h3><?php echo esc_html( $l( 'b_side_title' ) ); ?></h3>
				<ul class="gz-checks">
					<?php foreach ( array( 'b_side_1', 'b_side_2', 'b_side_3' ) as $k ) : ?>
						<li><?php echo $icon['check']; ?><?php echo esc_html( $l( $k ) ); ?></li>
					<?php endforeach; ?>
				</ul>
				<ul class="gz-contact">
					<li><?php echo $icon['phone']; ?><a href="<?php echo esc_url( $h::tel( $phone ) ); ?>" dir="ltr"><?php echo esc_html( $phone_f ); ?></a></li>
					<li><?php echo $icon['phone']; ?><a href="<?php echo esc_url( $h::tel( $l( 'phone2' ) ) ); ?>" dir="ltr"><?php echo esc_html( $l( 'phone2' ) ); ?></a></li>
					<li><?php echo $icon['pin']; ?><span><?php echo esc_html( $l( 'address' ) ); ?></span></li>
					<li><?php echo $icon['insta']; ?><a href="<?php echo $insta; ?>" target="_blank" rel="noopener" dir="ltr">@<?php echo esc_html( $l( 'instagram' ) ); ?></a></li>
				</ul>
			</aside>

		</div>
	</section>

</main>

<?php require __DIR__ . '/parts/footer.php';
