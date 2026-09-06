<?php
/**
 * Template Name: Job Photos
 *
 * Ported from Pages/Public/JobPhotos.razor. The photos ship inside the theme
 * under assets/img and are listed by newera_gallery_photos(), which is
 * filterable if they should later come from the Media Library instead.
 *
 * Tapping a tile opens the photo full size. The lightbox is CSS only — one
 * fixed overlay per photo, revealed by :target — because the rest of the theme
 * (mobile nav, home carousel) ships no JavaScript either. The styles and the
 * reasoning live under "Gallery lightbox" in style.css.
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
			'subtitle' => 'A look at the kind of results we deliver for homes and businesses across Houston.',
		)
	);
	?>

	<section class="section">
		<div class="section-inner">
			<div class="section-head">
				<span class="section-eyebrow">Our Work</span>
				<h2 class="section-title">Real Results, Real Care</h2>
				<p class="section-sub">
					From tired carpets brought back to life to spotless tile and revived upholstery,
					here's a snapshot of what we do every day.
				</p>
			</div>

			<?php if ( trim( get_the_content() ) ) : ?>
				<div class="entry-content prose"><?php the_content(); ?></div>
			<?php endif; ?>

			<div class="gallery-grid">
				<?php
				$newera_tiles = newera_gallery_photos();
				$newera_count = count( $newera_tiles );

				foreach ( $newera_tiles as $newera_index => $newera_tile ) :
					// Previous and next wrap around the ends of the gallery.
					$newera_prev = ( $newera_index - 1 + $newera_count ) % $newera_count;
					$newera_next = ( $newera_index + 1 ) % $newera_count;
					?>
					<figure class="gallery-tile" id="tile-<?php echo esc_attr( $newera_index ); ?>">
						<?php
						/*
						 * The tiles carry no visible caption, so the photo has to
						 * describe itself: the alt text is the only description a
						 * screen reader gets, and the link takes its own label from
						 * the same string.
						 */
						?>
						<a class="gallery-tile-link" href="#photo-<?php echo esc_attr( $newera_index ); ?>"
							aria-label="<?php echo newera_attr_text( 'View larger: ' . $newera_tile['alt'] ); ?>">
							<img src="<?php echo esc_url( $newera_tile['image'] ); ?>"
								alt="<?php echo newera_attr_text( $newera_tile['alt'] ); ?>" width="<?php echo esc_attr( $newera_tile['width'] ); ?>" height="<?php echo esc_attr( $newera_tile['height'] ); ?>" decoding="async" <?php echo $newera_index < 3 ? '' : 'loading="lazy"'; // First row is on screen at load. ?> />
						</a>
					</figure>

					<?php
					/*
					 * The overlay sits inside the grid, immediately after the tile
					 * that opens it. display:none keeps it out of the grid flow, out
					 * of the tab order and un-fetched until :target reveals it, and
					 * placing it here means the close control is the next thing a
					 * keyboard user reaches after the tile they activated.
					 *
					 * Every close control links back to #tile-N rather than to the top
					 * of the grid, so dismissing photo twelve returns the reader to
					 * photo twelve. The src is the same file the tile already loaded,
					 * so opening is a cache hit, not a second request.
					 */
					?>
					<div class="lightbox" id="photo-<?php echo esc_attr( $newera_index ); ?>" role="group"
						aria-label="<?php echo newera_attr_text( 'Enlarged photo: ' . $newera_tile['alt'] ); ?>">
						<a class="lightbox-backdrop" href="#tile-<?php echo esc_attr( $newera_index ); ?>"
							tabindex="-1" aria-hidden="true"></a>

						<figure class="lightbox-figure">
							<img src="<?php echo esc_url( $newera_tile['image'] ); ?>"
								alt="<?php echo newera_attr_text( $newera_tile['alt'] ); ?>" width="<?php echo esc_attr( $newera_tile['width'] ); ?>" height="<?php echo esc_attr( $newera_tile['height'] ); ?>" decoding="async" loading="lazy" />
						</figure>

						<a class="lightbox-close" href="#tile-<?php echo esc_attr( $newera_index ); ?>"
							aria-label="Close enlarged photo">
							<?php echo newera_icon( 'bi-x-lg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<a class="lightbox-nav lightbox-prev" href="#photo-<?php echo esc_attr( $newera_prev ); ?>"
							aria-label="Previous photo">
							<?php echo newera_icon( 'bi-chevron-left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
						<a class="lightbox-nav lightbox-next" href="#photo-<?php echo esc_attr( $newera_next ); ?>"
							aria-label="Next photo">
							<?php echo newera_icon( 'bi-chevron-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	newera_cta_band(
		array(
			'heading'    => 'Want results like these?',
			'subheading' => "Let's make your space the next success story. Get your free quote today.",
		)
	);

endwhile;

get_footer();
