<?php
/**
 * Template Name: Get a Quote
 *
 * Ported from Pages/Public/Quote.razor.
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
			'subtitle' => "You're seconds away from a cleaner space. Tell us what you need and we'll be in touch.",
		)
	);

	/*
	 * Service pages link here with ?service=<name> so the picker can open on the
	 * service the visitor was reading about. Read-only display state, so no
	 * nonce: an unrecognised value simply fails to match any option and the
	 * picker falls back to "Select a service...".
	 */
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display state.
	$newera_preselect = isset( $_GET['service'] )
		? sanitize_text_field( wp_unslash( $_GET['service'] ) )
		: '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	newera_quote_section(
		array(
			'form_name' => 'quote-page',
			'preselect' => $newera_preselect,
		)
	);

endwhile;

get_footer();
