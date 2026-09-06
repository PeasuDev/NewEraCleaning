<?php
/**
 * Template Name: Guarantee
 *
 * Ported from Pages/Public/Guarantee.razor.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	newera_page_banner(
		array(
			'title'    => esc_html( get_the_title() ),
			'subtitle' => "We stand behind every job. If something isn't right, we'll come back and make it right.",
		)
	);
	?>

	<section class="section">
		<div class="section-inner">
			<div class="guarantee-hero">
				<span class="guarantee-seal">
					<?php echo newera_icon( 'bi-patch-check-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span>10 Day</span>
				</span>
				<div>
					<h2>Our Promise to You</h2>
					<?php if ( trim( get_the_content() ) ) : ?>
						<div class="entry-content"><?php the_content(); ?></div>
					<?php else : ?>
						<p>
							Quality work is the foundation of our business &mdash; that's our promise as a
							local, family-owned company. Our technicians are insured &amp; bonded, and every
							job is backed by our <strong>10-day, no-hassle guarantee</strong>.
						</p>
					<?php endif; ?>
				</div>
			</div>

			<div class="services-grid" style="margin-top:3rem;">
				<?php
				$newera_promises = array(
					array( 'bi-hand-thumbs-up-fill', '10-Day No-Hassle Guarantee', "If something isn't up to your standards, let us know within 10 days and we'll re-clean the area at no extra cost." ),
					array( 'bi-shield-lock-fill', 'Bonded &amp; Insured', 'Full coverage means total peace of mind whenever our team is in your home or business.' ),
					array( 'bi-cash-coin', 'No Hidden Fees', 'The price we quote is the price you pay. No surprises, no upsells, no pressure.' ),
					array( 'bi-flower1', 'Safe &amp; Green', 'Family- and pet-friendly methods, with organic, all-natural green options available on request.' ),
				);

				foreach ( $newera_promises as $newera_promise ) :
					?>
					<article class="service-card">
						<span class="service-icon"><?php echo newera_icon( $newera_promise[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h3 class="service-name"><?php echo wp_kses_post( $newera_promise[1] ); ?></h3>
						<p class="service-text"><?php echo wp_kses_post( $newera_promise[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	newera_cta_band(
		array(
			'heading'    => 'Book with confidence',
			'subheading' => 'Backed by our guarantee. Get your free quote today.',
		)
	);

endwhile;

get_footer();
