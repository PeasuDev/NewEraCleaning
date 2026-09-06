<?php
/**
 * Fallback template.
 *
 * WordPress requires this file. It is reached by blog archives, categories, and
 * search results — none of which the marketing site uses today, but the theme
 * should not break if a post is ever published.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

get_header();

// newera_page_banner() takes HTML, so anything from the database is encoded here.
if ( is_search() ) {
	$newera_title    = 'Search results';
	/* translators: %s: search term. */
	$newera_subtitle = sprintf( 'Showing results for &ldquo;%s&rdquo;', esc_html( get_search_query() ) );
} elseif ( is_archive() ) {
	$newera_title    = esc_html( wp_strip_all_tags( get_the_archive_title() ) );
	$newera_subtitle = esc_html( wp_strip_all_tags( get_the_archive_description() ) );
} else {
	$newera_title    = get_the_title( (int) get_option( 'page_for_posts' ) );
	$newera_title    = esc_html( $newera_title ? $newera_title : 'News' );
	$newera_subtitle = '';
}

newera_page_banner(
	array(
		'title'    => $newera_title,
		'subtitle' => $newera_subtitle,
	)
);
?>

<section class="section">
	<div class="section-inner">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'post-card' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="post-meta"><?php echo esc_html( get_the_date() ); ?></p>
						<p class="service-text"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<a class="service-link" href="<?php the_permalink(); ?>">
							Read more <?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'class'     => 'pagination',
					'mid_size'  => 2,
					'prev_text' => 'Previous',
					'next_text' => 'Next',
				)
			);
			?>
		<?php else : ?>
			<p class="section-sub" style="text-align:center;">Nothing found here yet.</p>
		<?php endif; ?>
	</div>
</section>

<?php
get_footer();
