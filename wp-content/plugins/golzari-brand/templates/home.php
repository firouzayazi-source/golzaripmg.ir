<?php
/**
 * صفحه‌ی اصلیِ اختصاصیِ مجموعه‌ی گلزاری.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$l       = 'golzari_label';
$h       = 'Golzari_Brand_Home';
$booking = esc_url( home_url( golzari_label( 'booking_path' ) ) );
$insta   = esc_url( Golzari_Brand::instagram_url() );
$phone   = golzari_label( 'phone' );
$phone_f = Golzari_Brand::fa_digits( $phone );
$nav     = array(
	'top'      => 'nav_home',
	'services' => 'nav_services',
	'works'    => 'nav_works',
	'academy'  => 'nav_academy',
	'about'    => 'nav_about',
	'contact'  => 'nav_contact',
);
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
$icon    = array(
	'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25 11.4 11.4 0 0 0 3.6.57 1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.6a1 1 0 0 1-.25 1z"/></svg>',
	'insta' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9zm0 2a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM17.5 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
	'pin'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a7 7 0 0 1 7 7c0 5.2-7 13-7 13S5 14.2 5 9a7 7 0 0 1 7-7zm0 4.5A2.5 2.5 0 1 0 12 11.5 2.5 2.5 0 0 0 12 6.5z"/></svg>',
	'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
	'cal'   => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h2v2h6V2h2v2h3a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h3V2zm12 8H5v9h14v-9z"/></svg>',
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#4f7e6e">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'gz' ); ?>>
<?php wp_body_open(); ?>

<header class="gz-header" id="gz-header">
	<div class="gz-wrap gz-header__in">
		<a class="gz-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<img src="<?php echo $h::img( 'logo' ); ?>" width="512" height="512" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>">
			<span><strong><?php echo esc_html( $l( 'brand_name' ) ); ?></strong><small><?php echo esc_html( $l( 'collection' ) ); ?></small></span>
		</a>
		<nav class="gz-nav" id="gz-nav" aria-label="<?php echo esc_attr( $l( 'nav_home' ) ); ?>">
			<?php foreach ( $nav as $id => $key ) : ?>
				<a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $l( $key ) ); ?></a>
			<?php endforeach; ?>
			<a class="gz-btn gz-btn--gold gz-nav__cta" href="<?php echo $booking; ?>"><?php echo $icon['cal']; ?><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
		</nav>
		<a class="gz-btn gz-btn--gold gz-header__cta" href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
		<button class="gz-burger" id="gz-burger" type="button" aria-controls="gz-nav" aria-expanded="false" aria-label="<?php echo esc_attr( $l( 'nav_home' ) ); ?>"><span></span><span></span><span></span></button>
	</div>
</header>

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

<footer class="gz-footer" id="contact">
	<div class="gz-wrap gz-footer__in">
		<div class="gz-footer__brand">
			<img src="<?php echo $h::img( 'logo' ); ?>" width="512" height="512" alt="<?php echo esc_attr( $l( 'name_fa' ) ); ?>" loading="lazy">
			<strong><?php echo esc_html( $l( 'brand_name' ) ); ?></strong>
			<p><?php echo esc_html( $l( 'foot_about' ) ); ?></p>
			<a class="gz-social" href="<?php echo $insta; ?>" target="_blank" rel="noopener"><?php echo $icon['insta']; ?><span dir="ltr">@<?php echo esc_html( $l( 'instagram' ) ); ?></span></a>
		</div>
		<div>
			<h3><?php echo esc_html( $l( 'foot_links' ) ); ?></h3>
			<ul class="gz-links">
				<?php foreach ( $nav as $id => $key ) : ?>
					<li><a href="#<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $l( $key ) ); ?></a></li>
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
