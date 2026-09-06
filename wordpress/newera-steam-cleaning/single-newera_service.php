<?php
/**
 * A single service, served at {services base}/{slug}/.
 *
 * Ported from Pages/Public/ServiceDetail.razor.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$newera_service = newera_get_service_by_slug( get_post_field( 'post_name' ) );

	// A service can exist as a post without meta if it was created by hand,
	// so every field below has to tolerate being empty.
	$newera_tagline    = $newera_service ? $newera_service['tagline'] : '';
	$newera_highlights = $newera_service ? $newera_service['highlights'] : array();
	$newera_name       = get_the_title();

	// The copy sits beside a photo only when the owner has actually set a
	// featured image. Without one it runs full width — an empty framed box
	// next to the text reads as a missing photo, not as a design element.
	$newera_has_photo  = has_post_thumbnail();
	$newera_grid_class = $newera_has_photo ? 'section-inner split' : 'section-inner section-narrow';
	$newera_copy_class = $newera_has_photo ? 'split-copy prose entry-content' : 'prose entry-content';

	// The banner treats its arguments as HTML, so a database title — which is
	// plain text and may contain a bare "&" — has to be encoded first.
	newera_page_banner(
		array(
			'title'        => esc_html( $newera_name ),
			'subtitle'     => $newera_tagline,
			'parent_label' => 'Services',
			'parent_href'  => get_post_type_archive_link( 'newera_service' ),
		)
	);
	?>

	<section class="section">
		<div class="<?php echo esc_attr( $newera_grid_class ); ?>">
			<div class="<?php echo esc_attr( $newera_copy_class ); ?>">
				<?php if ( $newera_tagline ) : ?>
					<h2><?php echo wp_kses_post( $newera_tagline ); ?></h2>
				<?php endif; ?>

				<?php
				// Prefer the editor content so the owner can rewrite the copy;
				// fall back to the ported description for services that only
				// exist in inc/services.php.
				if ( trim( get_the_content() ) ) {
					the_content();
				} elseif ( $newera_service ) {
					echo '<p>' . wp_kses_post( $newera_service['description'] ) . '</p>';
				}
				?>

				<?php if ( $newera_highlights ) : ?>
					<h3>What's included</h3>
					<ul class="check-list">
						<?php foreach ( $newera_highlights as $newera_highlight ) : ?>
							<li>
								<?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<?php echo wp_kses_post( $newera_highlight ); ?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php
				/*
				 * Where the service is offered, and what is specific about how it
				 * is done here. One H3 per service, each saying something
				 * different — a city name swapped into the same sentence eight
				 * times is the pattern this is written to avoid.
				 */
				$newera_local_body = $newera_service ? newera_service_local_body( $newera_service ) : '';
				$newera_local_head = $newera_service && ! empty( $newera_service['local_title'] )
					? $newera_service['local_title']
					: '';
				?>

				<?php if ( $newera_local_body ) : ?>
					<?php if ( $newera_local_head ) : ?>
						<h3><?php echo wp_kses_post( $newera_local_head ); ?></h3>
					<?php endif; ?>
					<p><?php echo wp_kses_post( $newera_local_body ); ?></p>
				<?php endif; ?>

				<p class="price-note" style="text-align:left;margin-top:1.5rem;">
					<?php echo newera_icon( 'bi-patch-check-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					Our technicians are insured &amp; bonded, and every job is backed by our
					10-day, no-hassle guarantee. Organic, all-natural green options are available
					on request. Check out our <a href="<?php echo esc_url( newera_pricing_url() ); ?>">price list</a>
					or request a free, personalized quote.
				</p>

				<?php
				/*
				 * Carry the service name over to the quote page so its picker can
				 * preselect it. The picker matches against newera_service_names()
				 * and ignores anything it does not recognise, so a stale or
				 * hand-edited link degrades to the plain form rather than breaking.
				 */
				$newera_quote_link = add_query_arg(
					'service',
					rawurlencode( html_entity_decode( $newera_name, ENT_QUOTES, 'UTF-8' ) ),
					newera_quote_url()
				);
				?>
				<div class="hero-actions" style="margin-top:1.5rem;">
					<a class="btn-primary-lg" href="<?php echo esc_url( $newera_quote_link ); ?>">
						Get a Free Quote
						<?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
					<a class="btn-outline" href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>">
						<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo esc_html( newera_phone() ); ?>
					</a>
				</div>
			</div>
			<?php if ( $newera_has_photo ) : ?>
				<div class="split-media">
					<?php
					/*
					 * This is the first and largest image on a service page, so
					 * the loading hints are stated rather than left to core's
					 * heuristic, which counts images differently between
					 * versions and cannot see the raw <img> in header.php.
					 *
					 * Alt text falls back to the service name: an image with no
					 * alt at all is worse than a plain description, and the
					 * media library entry is empty more often than not.
					 */
					$newera_thumb_id  = get_post_thumbnail_id();
					$newera_thumb_alt = trim( (string) get_post_meta( $newera_thumb_id, '_wp_attachment_image_alt', true ) );

					$newera_thumb_attr = array(
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
					);

					if ( '' === $newera_thumb_alt ) {
						$newera_thumb_attr['alt'] = newera_plain_text( $newera_name ) . ' by ' . newera_business_name();
					}

					the_post_thumbnail( 'large', $newera_thumb_attr );
					?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php
	// Three other services, so visitors have somewhere to go next.
	$newera_others = array_slice(
		array_values(
			array_filter(
				newera_get_services(),
				static function ( $service ) {
					return $service['slug'] !== get_post_field( 'post_name' );
				}
			)
		),
		0,
		3
	);
	?>

	<?php if ( $newera_others ) : ?>
		<section class="section section-alt">
			<div class="section-inner">
				<div class="section-head">
					<span class="section-eyebrow">More Services</span>
					<h2 class="section-title">Explore What Else We Do</h2>
				</div>
				<div class="services-grid">
					<?php foreach ( $newera_others as $newera_other ) : ?>
						<?php newera_service_card( $newera_other ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	newera_quote_section(
		array(
			'id'        => 'service-quote',
			'form_name' => 'service-quote',
			'preselect' => html_entity_decode( $newera_name, ENT_QUOTES, 'UTF-8' ),
		)
	);
	?>

<?php
endwhile;

get_footer();
