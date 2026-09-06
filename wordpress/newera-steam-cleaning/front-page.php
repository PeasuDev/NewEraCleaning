<?php
/**
 * Home page.
 *
 * Ported from Pages/Public/Home.razor. WordPress uses this template for the
 * front page whether or not a static page has been assigned in
 * Settings > Reading, so the marketing homepage works on a fresh install.
 *
 * This page owns the site's single <h1>; every other heading here is an <h2>
 * or lower. The live site has five <h1> tags on its homepage, which is one of
 * the ranking problems documented in docs/neweracleaning-website-analysis.md.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

$newera_reviews  = newera_get_testimonials();
$newera_services = newera_get_services();
$newera_slides   = newera_carousel_slides();

// The hero quotes the most recent five-star review; the grid shows the best three.
$newera_featured = null;

foreach ( $newera_reviews as $newera_review ) {
	if ( 5 === (int) $newera_review['rating'] ) {
		$newera_featured = $newera_review;
		break;
	}
}

if ( ! $newera_featured && $newera_reviews ) {
	$newera_featured = $newera_reviews[0];
}

$newera_testimonials = array_slice(
	array_values(
		array_filter(
			$newera_reviews,
			static function ( $review ) {
				return (int) $review['rating'] >= 4;
			}
		)
	),
	0,
	3
);
?>

<!-- ===================== HERO ===================== -->
<section class="hero">
	<div class="hero-inner">
		<div class="hero-copy">
			<span class="hero-eyebrow">
				<?php echo newera_icon( 'bi-patch-check-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				Sugar Land &middot; Houston &middot; Katy &amp; surrounding areas
			</span>
			<h1 class="hero-title">
				Houston's Trusted <span class="hero-accent">Steam&nbsp;Cleaning</span> &amp; Restoration Experts
			</h1>
			<p class="hero-lead">
				Recapture your carpet's youth. <?php echo esc_html( newera_business_name() ); ?> is a local,
				family-owned company delivering deep, hospital-grade steam cleaning for
				carpets, tile, stone, and upholstery &mdash; backed by
				<?php echo esc_html( newera_years_of_experience() ); ?>+ years of experience.
			</p>
			<div class="hero-actions">
				<a class="btn-primary-lg" href="#contact">
					Get a Free Quote
					<?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="btn-ghost-lg" href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>">
					<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( newera_phone() ); ?>
				</a>
			</div>
			<ul class="hero-trust">
				<li><?php echo newera_icon( 'bi-shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Guaranteed, Bonded &amp; Insured</li>
				<li><?php echo newera_icon( 'bi-flower1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Organic Green Options</li>
				<li><?php echo newera_icon( 'bi-people-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Family-Owned Since <?php echo esc_html( newera_founded_year() ); ?></li>
			</ul>
		</div>

		<?php if ( $newera_featured ) : ?>
			<div class="hero-card">
				<div class="hero-card-rating">
					<div class="hero-stars"><?php echo newera_stars( 5 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<span class="hero-card-rating-label">from 240+ reviews</span>
					<?php newera_social_icon_links( 'hero-card-social' ); ?>
				</div>
				<p class="hero-card-quote">&ldquo;<?php echo wp_kses_post( $newera_featured['text'] ); ?>&rdquo;</p>
				<span class="hero-card-author">
					&mdash; <?php echo esc_html( $newera_featured['author'] ); ?>,
					<?php echo esc_html( $newera_featured['source'] ? $newera_featured['source'] : 'Google' ); ?>
				</span>
				<a class="hero-card-cta" href="#contact">Schedule your cleaning &rarr;</a>
			</div>
		<?php endif; ?>
	</div>
</section>

<!-- ===================== CAROUSEL ===================== -->
<?php if ( $newera_slides ) : ?>
	<section class="section carousel-section" aria-label="Our work in action">
		<div class="section-inner">
			<div class="carousel" role="group" aria-roledescription="carousel">
				<?php
				/*
				 * Pure-CSS carousel: one radio per slide, and the checked radio
				 * translates the track. The radios must precede the track, the
				 * arrows and the dots, because the CSS uses sibling combinators.
				 */
				foreach ( $newera_slides as $i => $slide ) :
					?>
					<input type="radio" name="home-carousel" id="carousel-<?php echo esc_attr( $i ); ?>"
						class="carousel-radio" <?php checked( 0, $i ); ?> />
				<?php endforeach; ?>

				<div class="carousel-track">
					<?php foreach ( $newera_slides as $newera_slide_index => $slide ) : ?>
						<figure class="carousel-slide">
							<img src="<?php echo esc_url( $slide['image'] ); ?>"
								alt="<?php echo newera_attr_text( $slide['caption'] ); ?>" width="<?php echo esc_attr( $slide['width'] ); ?>" height="<?php echo esc_attr( $slide['height'] ); ?>" decoding="async" <?php
									/*
									 * Slide one is visible on load and sits high on the page, so
									 * it is the likely Largest Contentful Paint element. The other
									 * two are translated off-screen and can wait.
									 */
									echo 0 === $newera_slide_index ? 'fetchpriority="high"' : 'loading="lazy"';
									?> />
							<figcaption class="carousel-caption"><?php echo wp_kses_post( $slide['caption'] ); ?></figcaption>
						</figure>
					<?php endforeach; ?>
				</div>

				<div class="carousel-arrows" aria-hidden="true">
					<?php
					$newera_slide_count = count( $newera_slides );
					foreach ( $newera_slides as $i => $slide ) :
						$prev = ( $i - 1 + $newera_slide_count ) % $newera_slide_count;
						$next = ( $i + 1 ) % $newera_slide_count;
						?>
						<div class="carousel-arrow-pair" id="carousel-arrows-<?php echo esc_attr( $i ); ?>">
							<label class="carousel-arrow carousel-arrow-prev" for="carousel-<?php echo esc_attr( $prev ); ?>">
								<?php echo newera_icon( 'bi-chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
							<label class="carousel-arrow carousel-arrow-next" for="carousel-<?php echo esc_attr( $next ); ?>">
								<?php echo newera_icon( 'bi-chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>

				<div class="carousel-dots">
					<?php foreach ( $newera_slides as $i => $slide ) : ?>
						<label class="carousel-dot" id="carousel-dot-<?php echo esc_attr( $i ); ?>"
							for="carousel-<?php echo esc_attr( $i ); ?>"
							aria-label="Show slide <?php echo esc_attr( $i + 1 ); ?>"></label>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- ===================== BRAND LOGO ===================== -->
<section class="section brand-logo-feature" aria-label="<?php echo newera_attr_text( newera_business_name() ); ?>">
	<?php
	// The band's CSS is height:auto, so these attributes are what stop it
	// shifting as the banner loads. See newera_banner_dimensions().
	list( $newera_banner_w, $newera_banner_h ) = newera_banner_dimensions();
	?>
	<img src="<?php echo esc_url( newera_banner_url() ); ?>"
		alt="<?php echo newera_attr_text( newera_business_name() ); ?> logo"
		width="<?php echo esc_attr( $newera_banner_w ); ?>" height="<?php echo esc_attr( $newera_banner_h ); ?>"
		loading="lazy" decoding="async" />
</section>

<!-- ===================== TRUST BAR ===================== -->
<section class="trust-bar">
	<div class="trust-bar-inner">
		<div class="trust-item"><?php echo newera_icon( 'bi-clock-history' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Mon&ndash;Fri &middot; 9:00 AM&ndash;6:00 PM</div>
		<div class="trust-item"><?php echo newera_icon( 'bi-droplet-half' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Truck-Mounted Steam Cleaning</div>
		<div class="trust-item"><?php echo newera_icon( 'bi-geo-alt-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Harris &amp; Fort Bend County</div>
	</div>
</section>

<!-- ===================== SERVICES ===================== -->
<section class="section" id="services">
	<div class="section-inner">
		<div class="section-head">
			<span class="section-eyebrow">What We Do</span>
			<h2 class="section-title">Professional Cleaning Services</h2>
			<p class="section-sub">
				From everyday refreshes to post-construction deep cleans, no job is too large or
				too small. Our skilled technicians bring the best cleaning technology to every visit.
			</p>
		</div>

		<div class="services-grid">
			<?php foreach ( $newera_services as $newera_service ) : ?>
				<?php newera_service_card( $newera_service ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ===================== ABOUT / WHY US ===================== -->
<section class="section section-alt" id="about">
	<div class="section-inner about-grid">
		<div class="about-copy">
			<span class="section-eyebrow">About New Era</span>
			<h2 class="section-title">Family-Owned &amp; Houston Proud</h2>
			<p>
				<?php echo esc_html( newera_business_name() ); ?> was founded in <?php echo esc_html( newera_founded_year() ); ?>
				by Charles Hegler in Austin. A born-and-raised Houstonian with a beautiful young family to
				support and a deep love for the craft, Charles set out to fulfill a lifelong dream of
				starting his own business. He has since brought that business home &mdash; breathing new
				life into the Sugar Land, Houston, Katy, Richmond, Missouri City, and Fulshear steam
				cleaning scene.
			</p>
			<p>
				We combine over <?php echo esc_html( newera_years_of_experience() ); ?> years of hands-on
				experience with the best cleaning technologies to deliver an outstanding floor-care
				experience. As Houston natives, we're dedicated to growing our business the right way
				&mdash; through superior service and exceptional customer care. Your recommendation is
				our greatest compliment.
			</p>
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
				<strong><?php echo esc_html( count( $newera_services ) ); ?></strong>
				<span>Specialized Services</span>
			</div>
		</div>
	</div>
</section>

<!-- ===================== TESTIMONIALS ===================== -->
<?php if ( $newera_testimonials ) : ?>
	<section class="section" id="reviews">
		<div class="section-inner">
			<div class="section-head">
				<span class="section-eyebrow">Customer Reviews</span>
				<h2 class="section-title">What Houston Says About Us</h2>
				<p class="section-sub">Real feedback from homeowners and businesses across the area.</p>
			</div>
			<div class="testimonial-grid">
				<?php foreach ( $newera_testimonials as $newera_review ) : ?>
					<figure class="testimonial-card">
						<div class="testimonial-stars"><?php echo newera_stars( $newera_review['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
						<blockquote>&ldquo;<?php echo wp_kses_post( $newera_review['text'] ); ?>&rdquo;</blockquote>
						<figcaption>
							<span class="testimonial-author"><?php echo esc_html( $newera_review['author'] ); ?></span>
							<span class="testimonial-source">via <?php echo esc_html( $newera_review['source'] ); ?></span>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<!-- ===================== SERVICE AREA ===================== -->
<section class="section section-alt" id="service-area">
	<div class="section-inner">
		<div class="section-head">
			<span class="section-eyebrow">Where We Work</span>
			<h2 class="section-title">Proudly Serving the Greater Houston Area</h2>
			<p class="section-sub">
				New Era proudly serves homes and businesses in Harris &amp; Fort Bend County and
				surrounding areas.
			</p>
		</div>
		<div class="area-chips">
			<?php foreach ( newera_service_areas() as $newera_area ) : ?>
				<span class="area-chip">
					<?php echo newera_icon( 'bi-geo-alt-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( $newera_area ); ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<!-- ===================== QUOTE FORM / CONTACT ===================== -->
<?php newera_quote_section( array( 'id' => 'contact', 'form_name' => 'home-quote' ) ); ?>

<?php
get_footer();
