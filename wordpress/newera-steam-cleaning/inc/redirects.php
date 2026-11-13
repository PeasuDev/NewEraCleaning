<?php
/**
 * 301 redirects for the URLs the previous site published that this one does not.
 *
 * Of the twenty-two URLs the live site serves, sixteen keep their exact address
 * — the theme was built around them deliberately — so only six need a redirect.
 * They are documented, with the Search Console figures behind each decision, in
 * REDIRECTS.md alongside this theme.
 *
 * Five more were added after launch, to catch the suffixed slugs the site briefly
 * shipped on. See the note against them below.
 *
 * Matching is on the request path, not on what WordPress resolves the request
 * to. That matters: /pricing/specials/ is still a published page today, and a
 * rule that only fired on a 404 would never reach it. Matching the path means
 * the redirect behaves the same before and after the old pages are trashed.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * Old path (no trailing slash, lower case) mapped to its destination.
 *
 * Destinations are relative to the site root and resolved through home_url()
 * at redirect time, so this works unchanged on staging and on live.
 *
 * @return array<string,string>
 */
function newera_redirect_map() {
	return (array) apply_filters(
		'newera_redirect_map',
		array(
			// Coupon prices are now tables on the pricing page.
			'/pricing/specials' => '/pricing/#coupon-prices',

			// No contact page: the quote form answers the same need, and the
			// old one earned 1 click in sixteen months.
			'/contact-us'       => '/get-quote/',

			/*
			 * The four testimonial URLs. Their traffic was entirely queries for
			 * the literal string the reviewers' surnames were anonymised to, so
			 * nothing real is lost by retiring them. Reviews now live in a
			 * section of the home page.
			 */
			'/testimonials/j-e-xxxx-texas'   => '/#reviews',
			'/testimonials/j-e-xxxx-texas-2' => '/#reviews',
			'/testimonials/l-p-xxxx-texas'   => '/#reviews',
			'/testimonials/m-f-xxxx-texas'   => '/#reviews',

			/*
			 * Suffixed slugs the launch shipped on, 2026-09-03 to 2026-09-11.
			 *
			 * These five pages were created while the old versions still held the
			 * original slugs, so WordPress appended a suffix and the site launched
			 * on addresses nobody chose. Trashing the old pages released the
			 * originals and the live pages have been renamed back, which is why
			 * every destination below is now a real page rather than a redirect.
			 *
			 * Core's own wp_old_slug_redirect() does not cover these: it keys off
			 * the `name` query var, and a page request that misses sets `pagename`,
			 * so the suffixed URLs 404 instead of redirecting. They were indexable
			 * for eight days, which is long enough to be worth catching here.
			 */
			'/about-us-2'   => '/about-us/',
			'/get-quote-2'  => '/get-quote/',
			'/pricing-3'    => '/pricing/',
			'/guarantee-2'  => '/guarantee/',
			'/job-photos-2' => '/job-photos/',
		)
	);
}

/**
 * Resolve a request path to its redirect destination.
 *
 * Split out from the hook so it can be exercised directly.
 *
 * @param string $path Request path, with or without a trailing slash.
 * @return string Destination path, or '' when the path is not redirected.
 */
function newera_resolve_redirect( $path ) {
	$path = (string) $path;

	if ( '' === $path || '/' === $path ) {
		return '';
	}

	$trimmed = rtrim( $path, '/' );

	if ( '' === $trimmed ) {
		return '';
	}

	$map = newera_redirect_map();

	foreach ( $map as $from => $to ) {
		if ( 0 === strcasecmp( $trimmed, rtrim( $from, '/' ) ) ) {
			return $to;
		}
	}

	return '';
}

/**
 * Serve the redirect.
 *
 * Runs on template_redirect, which fires for resolved requests and for 404s
 * alike, and early enough that nothing has been sent to the browser.
 *
 * @return void
 */
function newera_do_redirects() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$requested = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path      = wp_parse_url( $requested, PHP_URL_PATH );

	if ( ! $path ) {
		return;
	}

	$target = newera_resolve_redirect( $path );

	if ( '' === $target ) {
		return;
	}

	// Preserve any query string; campaign tags on an old link should survive.
	$query = wp_parse_url( $requested, PHP_URL_QUERY );

	if ( $query ) {
		/*
		 * The query goes before the fragment, never after it. Five of the six
		 * destinations carry a '#', and everything past that character is
		 * fragment: appending there would bury the query inside it, so the
		 * server would never see the campaign tag and the anchor would read
		 * 'reviews?utm_source=x', matching no element on the page. A tagged old
		 * link would lose its attribution *and* land at the top of the home page
		 * rather than the section it names.
		 */
		$fragment = '';
		$hash     = strpos( $target, '#' );

		if ( false !== $hash ) {
			$fragment = substr( $target, $hash );
			$target   = substr( $target, 0, $hash );
		}

		$target .= ( false === strpos( $target, '?' ) ? '?' : '&' ) . $query . $fragment;
	}

	wp_safe_redirect( home_url( $target ), 301 );
	exit;
}
add_action( 'template_redirect', 'newera_do_redirects', 1 );
