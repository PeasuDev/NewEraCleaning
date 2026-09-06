<?php
/**
 * Generic page.
 *
 * Used for pages that do not have a dedicated template, such as a privacy
 * policy. Gives them the site banner and the same prose styling as the ported
 * pages.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	newera_page_banner( array( 'title' => esc_html( get_the_title() ) ) );
	?>

	<section class="section">
		<div class="section-inner section-narrow prose entry-content">
			<?php
			the_content();

			wp_link_pages(
				array(
					'before' => '<nav class="pagination">',
					'after'  => '</nav>',
				)
			);
			?>
		</div>
	</section>

<?php
endwhile;

get_footer();
