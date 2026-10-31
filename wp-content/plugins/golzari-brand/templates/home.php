<?php
/**
 * صفحه‌ی اصلیِ اختصاصیِ مجموعه‌ی گلزاری.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/parts/vars.php';
$facts   = array( 'f1_d', 'f2_d', 'f3_d', 'f4_d', 'f5_d', 'f6_d' );
$serv    = array(
	array( 's1_t', 's1_d', 'remo-brow-2' ),
	array( 's2_t', 's2_d', 'remo-lip' ),
	array( 's3_t', 's3_d', 'remo-eyeliner' ),
);
$works   = array(
	array( 'remo-brow-1', 0 ),
	array( 'remo-brow-2', 0 ),
	array( 'remo-brow-3', 0 ),
	array( 'remo-brow-4', 0 ),
	array( 'remo-lip', 1 ),
	array( 'remo-eyeliner', 2 ),
	array( 'work-1', 3 ),
	array( 'work-2', 3 ),
);
$tabs    = array( 'g0', 'g1', 'g2', 'g3' );
require __DIR__ . '/parts/head.php';
require __DIR__ . '/parts/header.php';
?>

<main id="top">

	<section class="gz-hero">
		<div class="gz-wrap gz-hero__in">
			<div class="gz-hero__text">
				<span class="gz-kicker"><?php echo esc_html( $l( 'h_kicker' ) ); ?></span>
				<h1><?php echo esc_html( $l( 'h_title' ) ); ?></h1>
				<p class="gz-lead"><?php echo esc_html( $l( 'intro' ) ); ?></p>
				<p class="gz-sub"><?php echo esc_html( $l( 'teacher' ) ); ?></p>
				<div class="gz-actions">
					<a class="gz-btn gz-btn--gold" href="<?php echo $booking; ?>"><?php echo $icon['cal']; ?><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
					<a class="gz-btn gz-btn--ghost" href="#works"><?php echo esc_html( $l( 'h_cta2' ) ); ?></a>
				</div>
				<ul class="gz-stats">
					<?php foreach ( array( 1, 2, 3 ) as $i ) : ?>
						<li><b><?php echo esc_html( $l( "stat{$i}_n" ) ); ?></b><span><?php echo esc_html( $l( "stat{$i}_t" ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="gz-hero__media">
				<div class="gz-arch"></div>
				<img src="<?php echo $h::img( 'hero-cut' ); ?>" width="647" height="900" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>" fetchpriority="high">
			</div>
		</div>
	</section>

	<section class="gz-section gz-about" id="about">
		<div class="gz-wrap gz-about__in">
			<div class="gz-about__media">
				<div class="gz-frame"></div>
				<img src="<?php echo $h::img( 'about-cut' ); ?>" width="661" height="1000" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>" loading="lazy">
			</div>
			<div class="gz-about__text">
				<span class="gz-kicker"><?php echo esc_html( $l( 'about_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'info_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'about' ) ); ?></p>
				<ul class="gz-checks">
					<?php foreach ( $facts as $k ) : ?>
						<li><?php echo $icon['check']; ?><?php echo esc_html( $l( $k ) ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</section>

	<section class="gz-section gz-services" id="services">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'serv_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'serv_title' ) ); ?></h2>
			</div>
			<div class="gz-cards">
				<?php foreach ( $serv as $s ) : ?>
					<article class="gz-card">
						<div class="gz-card__img"><img src="<?php echo $h::img( $s[2] ); ?>" alt="<?php echo esc_attr( $l( $s[0] ) ); ?>" loading="lazy"></div>
						<div class="gz-card__body">
							<h3><?php echo esc_html( $l( $s[0] ) ); ?></h3>
							<p><?php echo esc_html( $l( $s[1] ) ); ?></p>
							<a href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?> ←</a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gz-section gz-works" id="works">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'works_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'works_title' ) ); ?></h2>
			</div>
			<div class="gz-tabs" role="tablist">
				<button type="button" class="is-on" data-tab="all"><?php echo esc_html( $l( 'g_all' ) ); ?></button>
				<?php foreach ( array( 0, 1, 2 ) as $i ) : ?>
					<button type="button" data-tab="<?php echo $i; ?>"><?php echo esc_html( $l( $tabs[ $i ] ) ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="gz-grid">
				<?php foreach ( $works as $w ) : ?>
					<?php if ( 3 === $w[1] ) { continue; } ?>
					<a class="gz-grid__item" data-cat="<?php echo (int) $w[1]; ?>" href="<?php echo $h::img( $w[0] ); ?>"><img src="<?php echo $h::img( $w[0] ); ?>" alt="<?php echo esc_attr( $l( $tabs[ $w[1] ] ) ); ?>" loading="lazy"></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gz-section gz-academy" id="academy">
		<div class="gz-wrap gz-academy__in">
			<div class="gz-academy__text">
				<span class="gz-kicker gz-kicker--light"><?php echo esc_html( $l( 'acad_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'acad_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'acad_text' ) ); ?></p>
				<a class="gz-btn gz-btn--gold" href="<?php echo esc_url( $h::tel( $phone ) ); ?>"><?php echo $icon['phone']; ?><?php echo esc_html( $l( 'acad_cta' ) ); ?></a>
			</div>
			<div class="gz-collage">
				<?php foreach ( array( 'cert-1', 'class-1', 'work-2', 'cert-2' ) as $p ) : ?>
					<a class="gz-grid__item" data-cat="3" href="<?php echo $h::img( $p ); ?>"><img src="<?php echo $h::img( $p ); ?>" alt="<?php echo esc_attr( $l( 'g3' ) ); ?>" loading="lazy"></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gz-cta">
		<div class="gz-wrap gz-cta__in">
			<div>
				<h2><?php echo esc_html( $l( 'cta_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'cta_text' ) ); ?></p>
			</div>
			<div class="gz-actions">
				<a class="gz-btn gz-btn--gold" href="<?php echo $booking; ?>"><?php echo $icon['cal']; ?><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
				<a class="gz-btn gz-btn--light" href="<?php echo esc_url( $h::tel( $phone ) ); ?>"><?php echo $icon['phone']; ?><span dir="ltr"><?php echo esc_html( $phone_f ); ?></span></a>
			</div>
		</div>
	</section>

</main>

<?php require __DIR__ . '/parts/footer.php';
