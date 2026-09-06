<?php
/**
 * Single blog post.
 *
 * Not used by the marketing pages, but present so a published post renders in
 * the site's styling rather than falling through to index.php.
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
			'subtitle' => esc_html( get_the_date() ),
		)
	);
	?>

	<section class="section">
		<div class="section-inner section-narrow prose entry-content">
			<?php
			if ( has_post_thumbnail() ) {
				the_post_thumbnail( 'large' );
			}

			the_content();
			?>
		</div>
	</section>

	<?php newera_cta_band(); ?>

<?php
endwhile;

get_footer();
