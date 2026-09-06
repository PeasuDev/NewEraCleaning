<?php
/**
 * Site footer.
 *
 * Ported from the footer half of Layout/PublicLayout.razor. The original
 * carried a "Staff Admin" link to the Blazor dashboard; that application is not
 * part of the WordPress site, so the link is gone.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/*
 * On the quote page itself the green CTA would otherwise reload the page the
 * visitor is already reading. Point it at the form instead, which the theme
 * gives the id of its form_name -- 'quote-page' on page-quote.php. Deciding this
 * here keeps the theme JavaScript-free; the Blazor preview needs a scroll helper
 * for the same behaviour only because its router does not reload the document.
 */
$newera_on_quote = is_page()
	&& untrailingslashit( get_permalink( get_queried_object_id() ) )
		=== untrailingslashit( newera_quote_url() );
$newera_cta_href = $newera_on_quote ? '#quote-page' : newera_quote_url();
?>
	</main>

	<footer class="public-footer">
		<div class="public-footer-inner">
			<div class="public-footer-brand">
				<img src="<?php echo esc_url( newera_logo_url() ); ?>"
					alt="<?php echo newera_attr_text( newera_business_name() ); ?> logo"
					class="public-footer-logo" loading="lazy" width="180" height="54" />
				<p class="public-footer-tagline">
					A local, family-owned steam cleaning company proudly serving the
					greater Houston area since <?php echo esc_html( newera_founded_year() ); ?>.
					Guaranteed, bonded &amp; insured.
				</p>
				<p class="public-footer-green">
					<?php echo newera_icon( 'bi-flower1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					Organic, all-natural green options available on request.
				</p>
				<div class="public-footer-follow">
					<h3 class="public-footer-social-heading">Follow us</h3>
					<?php newera_social_icon_links(); ?>
				</div>
			</div>

			<div class="public-footer-col">
				<h3>Services</h3>
				<ul>
					<?php foreach ( newera_get_services() as $newera_service ) : ?>
						<li>
							<a href="<?php echo esc_url( $newera_service['permalink'] ); ?>">
								<?php echo esc_html( $newera_service['name'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="public-footer-col">
				<h3>Service Area</h3>
				<ul>
					<?php foreach ( newera_service_areas() as $newera_area ) : ?>
						<li><?php echo esc_html( $newera_area ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div class="public-footer-col">
				<h3>Get in Touch</h3>
				<ul class="public-footer-contact">
					<li>
						<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<a href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>"><?php echo esc_html( newera_phone() ); ?></a>
					</li>
					<li>
						<?php echo newera_icon( 'bi-clock-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo wp_kses_post( newera_hours() ); ?>
					</li>
					<li>
						<?php echo newera_icon( 'bi-geo-alt-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						Harris &amp; Fort Bend County, TX
					</li>
				</ul>
				<a class="public-footer-cta" href="<?php echo esc_url( $newera_cta_href ); ?>">Request a Free Quote</a>
			</div>
		</div>

		<div class="public-footer-bottom">
			<span>
				&copy; <?php echo esc_html( current_time( 'Y' ) ); ?>
				<?php echo esc_html( newera_business_name() ); ?>. All Rights Reserved.
			</span>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'container'      => 'nav',
						'container_class' => 'public-footer-links',
						'container_aria_label' => 'Footer',
						'menu_class'     => '',
						'items_wrap'     => '%3$s',
						'depth'          => 1,
					)
				);
			} else {
				?>
				<nav class="public-footer-links" aria-label="Footer">
					<a href="<?php echo esc_url( newera_about_url() ); ?>">About</a>
					<a href="<?php echo esc_url( newera_pricing_url() ); ?>">Pricing</a>
					<a href="<?php echo esc_url( newera_photos_url() ); ?>">Job Photos</a>
					<a href="<?php echo esc_url( newera_guarantee_url() ); ?>">Guarantee</a>
				</nav>
				<?php
			}

			/*
			 * Rendered outside the menu branch on purpose. The privacy policy is
			 * the one link that has to exist whether or not anyone has built a
			 * footer menu — the page was already published on the live site and
			 * nothing linked to it.
			 */
			$newera_privacy = get_privacy_policy_url();

			if ( $newera_privacy ) :
				?>
				<a class="public-footer-privacy" href="<?php echo esc_url( $newera_privacy ); ?>">Privacy Policy</a>
				<?php
			endif;
			?>
		</div>
	</footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
