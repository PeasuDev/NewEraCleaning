<?php
/**
 * Template Name: About
 *
 * Ported from Pages/Public/About.razor. Used automatically for the page with
 * the slug `about`, and selectable from the page editor for any other page.
 *
 * The story column prints the page's own content when it has some, so the copy
 * can be rewritten in the block editor without touching the layout.
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
			'subtitle' => 'A local, family-owned steam cleaning company proudly serving the greater Houston area since ' . newera_founded_year() . '.',
		)
	);
	?>

	<section class="section brand-logo-feature" aria-label="<?php echo newera_attr_text( newera_business_name() ); ?>">
		<?php list( $newera_banner_w, $newera_banner_h ) = newera_banner_dimensions(); ?>
		<img src="<?php echo esc_url( newera_banner_url() ); ?>"
			alt="<?php echo newera_attr_text( newera_business_name() ); ?> logo"
			width="<?php echo esc_attr( $newera_banner_w ); ?>" height="<?php echo esc_attr( $newera_banner_h ); ?>"
			loading="lazy" decoding="async" />
	</section>

	<section class="section">
		<div class="section-inner about-grid">
			<div class="about-copy">
				<span class="section-eyebrow">Our Story</span>
				<h2 class="section-title">Family-Owned &amp; Houston Proud</h2>

				<?php if ( trim( get_the_content() ) ) : ?>
					<div class="entry-content"><?php the_content(); ?></div>
				<?php else : ?>
					<p>
						<?php echo esc_html( newera_business_name() ); ?> was founded in
						<?php echo esc_html( newera_founded_year() ); ?> by Charles Hegler in Austin.
						A born-and-raised Houstonian with a beautiful young family to support and a deep
						love for the craft, Charles set out to fulfill a lifelong dream of starting his
						own business. He has since brought that business home &mdash; breathing new life
						into the Sugar Land, Houston, Katy, Richmond, Missouri City, and Fulshear steam
						cleaning scene.
					</p>
					<p>
						We combine over <?php echo esc_html( newera_years_of_experience() ); ?> years of
						hands-on experience with the best cleaning technologies to deliver an outstanding
						floor-care experience. As Houston natives, we're dedicated to growing our business
						the right way &mdash; through superior service and exceptional customer care.
						Your recommendation is our greatest compliment.
					</p>
					<p>
						Whether it's a single room, an entire home, or a busy commercial space, no job is
						too large or too small. We treat every property as if it were our own.
					</p>
				<?php endif; ?>

				<div class="about-points">
					<div class="about-point"><?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Residential &amp; commercial cleaning</div>
					<div class="about-point"><?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> All work guaranteed, bonded &amp; insured</div>
					<div class="about-point"><?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Zero-contact service options available</div>
					<div class="about-point"><?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Natural, organic green cleaning on request</div>
				</div>
			</div>
			<div class="about-stats">
				<div class="stat-card">
					<strong><?php echo esc_html( newera_years_of_experience() ); ?>+</strong>
					<span>Years of Experience</span>
				</div>
				<div class="stat-card">
					<strong><?php echo esc_html( newera_founded_year() ); ?></strong>
					<span>Founded in Austin</span>
				</div>
				<div class="stat-card">
					<strong><?php echo esc_html( count( newera_get_services() ) ); ?></strong>
					<span>Specialized Services</span>
				</div>
			</div>
		</div>
	</section>

	<section class="section section-alt">
		<div class="section-inner">
			<div class="section-head">
				<span class="section-eyebrow">What Drives Us</span>
				<h2 class="section-title">Our Commitment to You</h2>
				<p class="section-sub">The principles behind every job we take on.</p>
			</div>
			<div class="services-grid">
				<?php
				$newera_values = array(
					array( 'bi-gem', 'Quality First', 'We use professional, truck-mounted equipment and proven techniques to deliver a deep, lasting clean every visit.' ),
					array( 'bi-flower1', 'Green Options', 'Prefer natural? Ask about our organic, all-natural green cleaning solutions that are safe for kids and pets.' ),
					array( 'bi-shield-check', 'Trust &amp; Safety', 'Fully guaranteed, bonded &amp; insured. You can feel confident inviting our team into your home or business.' ),
					array( 'bi-people-fill', 'Local &amp; Personal', "We're your neighbors. Every customer is treated with the care and respect we'd give our own family." ),
				);

				foreach ( $newera_values as $newera_value ) :
					?>
					<article class="service-card">
						<span class="service-icon"><?php echo newera_icon( $newera_value[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<h3 class="service-name"><?php echo wp_kses_post( $newera_value[1] ); ?></h3>
						<p class="service-text"><?php echo wp_kses_post( $newera_value[2] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	newera_cta_band(
		array(
			'heading'    => 'Experience the New Era difference',
			'subheading' => 'See why Houston families and businesses trust us with their floors and furniture.',
		)
	);

endwhile;

get_footer();
