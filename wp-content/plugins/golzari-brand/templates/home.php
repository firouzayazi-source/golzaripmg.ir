<?php
/**
 * صفحه‌ی اصلیِ اختصاصیِ مرکز و آکادمیِ سمیه گلزاری.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/parts/vars.php';

/** متنِ چندبندی: هر بند (جداشده با سطرِ خالی) یک <p>. */
$paras = function ( $key ) use ( $l ) {
	$out = '';
	foreach ( preg_split( '/\n\s*\n/u', trim( (string) $l( $key ) ) ) as $p ) {
		if ( '' !== trim( $p ) ) {
			$out .= '<p>' . esc_html( trim( $p ) ) . '</p>';
		}
	}
	return $out;
};
/** فهرست: هر سطر یک مورد. */
$lines = function ( $key ) use ( $l ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\n/u', (string) $l( $key ) ) ), 'strlen' ) );
};
$fa    = array( 'Golzari_Brand', 'fa_digits' );

$serv  = array(
	array( 'sv1', 'brow' ),
	array( 'sv2', 'eye' ),
	array( 'sv3', 'lip' ),
	array( 'sv4', 'lash' ),
	array( 'sv5', 'erase' ),
);
$why   = array(
	array( 'w1', 'award' ),
	array( 'w2', 'gem' ),
	array( 'w3', 'chat' ),
	array( 'w4', 'shield' ),
	array( 'w5', 'leaf' ),
);
$works = array(
	array( 'remo-brow-1', 0 ),
	array( 'remo-brow-2', 0 ),
	array( 'remo-brow-3', 0 ),
	array( 'remo-brow-4', 0 ),
	array( 'remo-lip', 1 ),
	array( 'remo-eyeliner', 2 ),
);
$tabs  = array( 'g0', 'g1', 'g2', 'g3' );

require __DIR__ . '/parts/head.php';
require __DIR__ . '/parts/header.php';
?>

<main id="top">

	<section class="gz-hero">
		<div class="gz-wrap gz-hero__in">
			<div class="gz-hero__text">
				<span class="gz-kicker"><?php echo esc_html( $l( 'h_kicker' ) ); ?></span>
				<h1><?php echo esc_html( $l( 'h_title' ) ); ?></h1>
				<p class="gz-role"><?php echo esc_html( $l( 'h_role' ) ); ?></p>
				<p class="gz-lead"><?php echo esc_html( $l( 'h_lead' ) ); ?></p>
				<div class="gz-actions">
					<a class="gz-btn gz-btn--gold" href="<?php echo $booking; ?>"><?php echo $icon['cal']; ?><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
					<a class="gz-btn gz-btn--ghost" href="#services"><?php echo esc_html( $l( 'h_cta2' ) ); ?></a>
				</div>
				<ul class="gz-stats">
					<?php foreach ( array( 1, 2, 3 ) as $i ) : ?>
						<li><b><?php echo esc_html( $l( "stat{$i}_n" ) ); ?></b><span><?php echo esc_html( $l( "stat{$i}_t" ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="gz-hero__media">
				<div class="gz-arch"></div>
				<img src="<?php echo $h::img( 'hero-cut' ); ?>" width="647" height="900" alt="<?php echo esc_attr( $l( 'h_title' ) ); ?>" fetchpriority="high">
			</div>
		</div>
	</section>

	<section class="gz-section gz-about" id="about">
		<div class="gz-wrap gz-about__in">
			<div class="gz-about__media">
				<div class="gz-frame"></div>
				<img src="<?php echo $h::img( 'about-cut' ); ?>" width="661" height="1000" alt="<?php echo esc_attr( $l( 'h_title' ) ); ?>" loading="lazy">
			</div>
			<div class="gz-about__text">
				<span class="gz-kicker"><?php echo esc_html( $l( 'about_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'about_title' ) ); ?></h2>
				<p class="gz-role"><?php echo esc_html( $l( 'about_sub' ) ); ?></p>
				<div class="gz-prose"><?php echo $paras( 'about_text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<blockquote class="gz-quote"><?php echo esc_html( $l( 'about_quote' ) ); ?><cite>— <?php echo esc_html( $l( 'h_title' ) ); ?></cite></blockquote>
			</div>
		</div>
	</section>

	<section class="gz-team">
		<div class="gz-wrap gz-team__in">
			<img src="<?php echo $h::img( 'work-1-sq' ); ?>" width="700" height="700" alt="<?php echo esc_attr( $l( 'team_title' ) ); ?>" loading="lazy">
			<div>
				<h2><?php echo esc_html( $l( 'team_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'team_text' ) ); ?></p>
			</div>
		</div>
	</section>

	<section class="gz-section gz-services" id="services">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'serv_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'serv_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'serv_text' ) ); ?></p>
			</div>
			<div class="gz-cards gz-cards--serv">
				<?php foreach ( $serv as $i => $s ) : ?>
					<?php $items = $lines( $s[0] . '_l' ); ?>
					<article class="gz-card gz-card--serv">
						<div class="gz-card__icon"><?php echo $icon[ $s[1] ]; ?></div>
						<span class="gz-card__num"><?php echo esc_html( call_user_func( $fa, sprintf( '%02d', $i + 1 ) ) ); ?></span>
						<h3><?php echo esc_html( $l( $s[0] . '_t' ) ); ?></h3>
						<?php if ( count( $items ) > 1 ) : ?>
							<ul class="gz-ticks">
								<?php foreach ( $items as $it ) : ?>
									<li><?php echo esc_html( $it ); ?></li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p><?php echo esc_html( implode( ' ', $items ) ); ?></p>
						<?php endif; ?>
						<a href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?> ←</a>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gz-section gz-why">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'why_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'why_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'why_text' ) ); ?></p>
			</div>
			<div class="gz-features">
				<?php foreach ( $why as $w ) : ?>
					<div class="gz-feature">
						<div class="gz-feature__icon"><?php echo $icon[ $w[1] ]; ?></div>
						<h3><?php echo esc_html( $l( $w[0] . '_t' ) ); ?></h3>
						<p><?php echo esc_html( $l( $w[0] . '_d' ) ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
			<p class="gz-tagline"><?php echo esc_html( $l( 'why_tag' ) ); ?></p>
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
					<button type="button" data-tab="<?php echo (int) $i; ?>"><?php echo esc_html( $l( $tabs[ $i ] ) ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="gz-grid">
				<?php foreach ( $works as $w ) : ?>
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
				<div class="gz-prose"><?php echo $paras( 'acad_text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<p class="gz-tagline gz-tagline--light"><?php echo esc_html( $l( 'acad_tag' ) ); ?></p>
				<a class="gz-btn gz-btn--gold" href="<?php echo esc_url( $h::tel( $phone ) ); ?>"><?php echo $icon['phone']; ?><?php echo esc_html( $l( 'acad_cta' ) ); ?></a>
			</div>
			<div class="gz-collage">
				<?php foreach ( array( 'cert-1', 'class-1', 'work-2', 'cert-2' ) as $p ) : ?>
					<a class="gz-grid__item" data-cat="3" href="<?php echo $h::img( $p ); ?>"><img src="<?php echo $h::img( $p ); ?>" alt="<?php echo esc_attr( $l( 'g3' ) ); ?>" loading="lazy"></a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="gz-section gz-perks">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'perk_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'perk_title' ) ); ?></h2>
				<p><?php echo esc_html( $l( 'perk_text' ) ); ?></p>
			</div>
			<ol class="gz-numbered">
				<?php for ( $i = 1; $i <= 9; $i++ ) : ?>
					<li>
						<b><?php echo esc_html( call_user_func( $fa, $i ) ); ?></b>
						<div>
							<h3><?php echo esc_html( $l( "p{$i}_t" ) ); ?></h3>
							<p><?php echo esc_html( $l( "p{$i}_d" ) ); ?></p>
						</div>
					</li>
				<?php endfor; ?>
			</ol>
		</div>
	</section>

	<section class="gz-section gz-workshops" id="workshops">
		<div class="gz-wrap">
			<div class="gz-head">
				<span class="gz-kicker"><?php echo esc_html( $l( 'ws_kicker' ) ); ?></span>
				<h2><?php echo esc_html( $l( 'ws_title' ) ); ?></h2>
				<div class="gz-prose"><?php echo $paras( 'ws_text' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			</div>
			<div class="gz-ws">
				<?php for ( $i = 1; $i <= 8; $i++ ) : ?>
					<article class="gz-ws__item">
						<span class="gz-ws__num"><?php echo esc_html( call_user_func( $fa, sprintf( '%02d', $i ) ) ); ?></span>
						<h3><?php echo esc_html( $l( "ws{$i}_t" ) ); ?></h3>
						<p><?php echo esc_html( $l( "ws{$i}_d" ) ); ?></p>
					</article>
				<?php endfor; ?>
			</div>
			<p class="gz-tagline"><?php echo esc_html( $l( 'ws_tag' ) ); ?></p>
			<div class="gz-actions gz-actions--center">
				<a class="gz-btn gz-btn--gold" href="<?php echo esc_url( $h::tel( $phone ) ); ?>"><?php echo $icon['phone']; ?><?php echo esc_html( $l( 'acad_cta' ) ); ?></a>
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
