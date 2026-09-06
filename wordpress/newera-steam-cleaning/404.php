<?php
/**
 * Not found.
 *
 * Ported in spirit from Pages/NotFound.razor: say what happened and give the
 * visitor the two things they most likely wanted — the services list or a phone
 * number.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

newera_page_banner(
	array(
		'title'    => 'Page not found',
		'subtitle' => "The page you're looking for doesn't exist, or it may have moved.",
	)
);
?>

<section class="section">
	<div class="section-inner section-narrow" style="text-align:center;">
		<div class="hero-actions" style="justify-content:center;">
			<a class="btn-primary-lg" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				Back to home
				<?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>
			<a class="btn-outline" href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>">
				<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php echo esc_html( newera_phone() ); ?>
			</a>
		</div>
	</div>
</section>

<section class="section section-alt">
	<div class="section-inner">
		<div class="section-head">
			<span class="section-eyebrow">What We Do</span>
			<h2 class="section-title">Browse Our Services</h2>
		</div>
		<div class="services-grid">
			<?php foreach ( array_slice( newera_get_services(), 0, 3 ) as $newera_service ) : ?>
				<?php newera_service_card( $newera_service ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
get_footer();
