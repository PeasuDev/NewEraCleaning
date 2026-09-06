<?php
/**
 * Services index, served at the catalogue base — see newera_services_base().
 *
 * Ported from Pages/Public/Services.razor.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

newera_page_banner(
	array(
		'title'    => 'Our Cleaning &amp; Restoration Services',
		'subtitle' => 'Professional, family-owned steam cleaning for homes and businesses. No job is too large or too small.',
	)
);
?>

<section class="section">
	<div class="section-inner">
		<div class="section-head">
			<span class="section-eyebrow">What We Do</span>
			<h2 class="section-title">Our Most Requested Services</h2>
			<p class="section-sub">
				We're excited to offer a wide range of services to meet your residential and
				commercial cleaning needs. Our most commonly requested services are listed below
				&mdash; select any service for more information.
			</p>
		</div>

		<div class="services-grid">
			<?php foreach ( newera_get_services() as $newera_service ) : ?>
				<?php newera_service_card( $newera_service ); ?>
			<?php endforeach; ?>
		</div>

		<p class="price-note">
			<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			Need a service that's not listed? Just give us a call &mdash; we'll be happy to discuss
			your specific needs. Organic, all-natural green options are available on request.
		</p>
	</div>
</section>

<?php
newera_cta_band(
	array(
		'heading'    => 'Not sure which service you need?',
		'subheading' => "Tell us about your space and we'll recommend the right solution &mdash; free of charge.",
	)
);

get_footer();
