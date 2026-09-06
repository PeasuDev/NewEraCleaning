<?php
/**
 * Template Name: Pricing
 *
 * Ported from Pages/Public/Pricing.razor.
 *
 * The coupon tables come from the page's own content, imported as core table
 * blocks, so the owner can change prices in the block editor. When the page is
 * empty the seeded tables from inc/pricing.php are rendered instead.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	newera_page_banner(
		array(
			'title'    => 'Simple, Transparent Pricing',
			'subtitle' => 'Honest rates with no hidden fees. Every quote is free and tailored to your space.',
		)
	);
	?>

	<section class="section" id="coupon-prices">
		<div class="section-inner">
			<div class="section-head">
				<span class="section-eyebrow">Current Coupon Prices</span>
				<h2 class="section-title">Cleaning Services &amp; Current Coupon Prices</h2>
				<p class="section-sub">
					This page gives you the opportunity to see our current coupon offers. These prices are
					subject to change, unless you have already booked. We take great pride in offering
					high-quality services at competitive prices. To give you an idea of how we will quote
					your job, we've compiled a list of prices for each service. Prices can fluctuate with
					larger homes and oversized rooms, while house coupons include up to 6 rooms, halls and
					closets. Questions about our pricing? Call us at
					<a href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>"><?php echo esc_html( newera_phone() ); ?></a>.
				</p>
			</div>

			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="entry-content"><?php the_content(); ?></div>
			<?php else : ?>
				<?php foreach ( newera_default_price_tables() as $newera_table ) : ?>
					<h3 class="price-table-title"><?php echo wp_kses_post( $newera_table['title'] ); ?></h3>
					<div class="price-table-wrap">
						<table class="price-table">
							<thead>
								<tr>
									<th scope="col">Service</th>
									<th scope="col">Description</th>
									<th scope="col">Pricing or incident</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $newera_table['rows'] as $newera_row ) : ?>
									<tr>
										<th scope="row"><?php echo wp_kses_post( $newera_row['service'] ); ?></th>
										<td><?php echo wp_kses_post( $newera_row['description'] ); ?></td>
										<td class="price-table-amount"><?php echo wp_kses_post( $newera_row['price'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<p class="price-note">
				<?php echo newera_icon( 'bi-info-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				*Whole house coupon based on total square footage of your entire home.
			</p>
		</div>
	</section>

	<?php
	newera_cta_band(
		array(
			'heading'    => 'Get your exact price &mdash; free',
			'subheading' => "Send us a few details and we'll follow up with a no-obligation quote.",
		)
	);

endwhile;

get_footer();
