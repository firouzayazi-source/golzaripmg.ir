<?php
/**
 * بقیه‌ی صفحه‌ها (نوشته، برگه، بایگانی، جست‌وجو، ۴۰۴) با سربرگ و پانویسِ مجموعه؛ از قالبِ «گلزاری» صدا زده می‌شود.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/parts/vars.php';
$gz_body = 'gz gz-page';
require __DIR__ . '/parts/head.php';
require __DIR__ . '/parts/header.php';

if ( is_404() ) {
	$gz_title = $l( 'p404_title' );
} elseif ( is_search() ) {
	$gz_title = $l( 'search_title' ) . ': ' . get_search_query();
} elseif ( is_archive() ) {
	$gz_title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_singular() ) {
	$gz_title = get_the_title();
} else {
	$gz_title = $l( 'brand_name' );
}
?>

<main id="top">
	<section class="gz-pagehead">
		<div class="gz-wrap">
			<span class="gz-kicker"><?php echo esc_html( $l( 'brand_name' ) ); ?></span>
			<h1><?php echo esc_html( $gz_title ); ?></h1>
		</div>
	</section>

	<section class="gz-section gz-content">
		<div class="gz-wrap gz-content__in">
			<?php if ( is_404() || ( ! is_singular() && ! have_posts() ) ) : ?>
				<div class="gz-empty">
					<p><?php echo esc_html( $l( is_404() ? 'p404_text' : 'search_none' ) ); ?></p>
					<div class="gz-actions">
						<a class="gz-btn gz-btn--gold" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $l( 'nav_home' ) ); ?></a>
						<a class="gz-btn gz-btn--ghost" href="<?php echo $booking; ?>"><?php echo esc_html( $l( 'h_cta' ) ); ?></a>
					</div>
				</div>
			<?php elseif ( is_singular() ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="gz-article"><?php the_content(); ?></article>
				<?php endwhile; ?>
			<?php else : ?>
				<div class="gz-list">
					<?php while ( have_posts() ) : the_post(); ?>
						<a class="gz-list__item" href="<?php the_permalink(); ?>">
							<h2><?php the_title(); ?></h2>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
						</a>
					<?php endwhile; ?>
				</div>
				<div class="gz-pager"><?php the_posts_pagination(); ?></div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php require __DIR__ . '/parts/footer.php';
