<?php
/**
 * Search and social metadata: titles, descriptions, canonicals, robots.
 *
 * Ported from Components/SeoHead.razor and the <HeadContent> block on
 * Pages/Public/Home.razor. Structured data lives in inc/schema.php, and the
 * wp-admin checklist that reports what is still missing in inc/seo-audit.php.
 *
 * The meta tags stand down completely when an SEO plugin is active. The live
 * site runs Yoast, so in practice the plugin owns titles, canonicals and Open
 * Graph, and this file only matters on an install without one. Emitting both
 * would produce duplicate canonicals, which is worse than emitting neither.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether an SEO plugin is managing head output.
 *
 * @return bool
 */
function newera_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )            // Yoast SEO.
		|| defined( 'RANK_MATH_VERSION' )         // Rank Math.
		|| defined( 'AIOSEO_VERSION' )            // All in One SEO.
		|| defined( 'SEOPRESS_VERSION' )          // SEOPress.
		|| class_exists( 'The_SEO_Framework\\Load' );
}

/**
 * Use a pipe in <title>, matching the Blazor original.
 *
 * @return string
 */
function newera_title_separator() {
	return '|';
}
add_filter( 'document_title_separator', 'newera_title_separator' );

/**
 * Which of the theme's pages is being rendered.
 *
 * Descriptions and structured data both need to know this, and neither can get
 * it from the template hierarchy alone. Two of the landing pages sit on slugs
 * the live site has always used — `about-us` and `get-quote` — which do not
 * match page-about.php and page-quote.php by the hierarchy's naming rule, so
 * those pages are resolved by their assigned template instead. The slug list is
 * the fallback for an install where the template was never assigned.
 *
 * @return string One of: front, service, services, about, pricing, quote,
 *                guarantee, photos, page, search, 404, other.
 */
function newera_page_kind() {
	if ( is_front_page() ) {
		return 'front';
	}

	if ( is_singular( 'newera_service' ) ) {
		return 'service';
	}

	if ( is_post_type_archive( 'newera_service' ) ) {
		return 'services';
	}

	if ( is_404() ) {
		return '404';
	}

	if ( is_search() ) {
		return 'search';
	}

	if ( is_page() ) {
		$by_template = array(
			'page-about.php'      => 'about',
			'page-pricing.php'    => 'pricing',
			'page-quote.php'      => 'quote',
			'page-guarantee.php'  => 'guarantee',
			'page-job-photos.php' => 'photos',
		);

		$template = (string) get_page_template_slug();

		if ( isset( $by_template[ $template ] ) ) {
			return $by_template[ $template ];
		}

		$by_slug = array(
			'about'      => 'about',
			'about-us'   => 'about',
			'pricing'    => 'pricing',
			'quote'      => 'quote',
			'get-quote'  => 'quote',
			'guarantee'  => 'guarantee',
			'job-photos' => 'photos',
		);

		$slug = (string) get_post_field( 'post_name' );

		return isset( $by_slug[ $slug ] ) ? $by_slug[ $slug ] : 'page';
	}

	return 'other';
}

/**
 * A description for the page being rendered.
 *
 * Google asks for a short, unique summary of one or two sentences, so each of
 * these is written to land under about 155 characters rather than reusing one
 * catch-all sentence that lists every city on every page.
 *
 * Every claim below already appears in the rendered copy of the page it
 * describes — the 10-day guarantee, insured and bonded, six rooms plus halls
 * and closets, the founding year, the service areas. Nothing here is a new
 * claim about the business.
 *
 * @return string
 */
function newera_page_description() {
	$name    = newera_business_name();
	$phone   = newera_phone();
	$areas   = newera_service_areas();
	$primary = wp_sprintf_l( '%l', array_slice( $areas, 0, 3 ) );

	/*
	 * No business name in here. Prefixing it costs 36 characters and pushes the
	 * sentence past the width the snippet has room for, to say something the
	 * title and og:site_name beside it already say twice.
	 */
	$default = 'Carpet, tile, grout, stone and upholstery cleaning for homes and businesses in ' . $primary . '. Free quotes — call ' . $phone . '.';

	switch ( newera_page_kind() ) {
		case 'service':
			$service     = newera_get_service_by_slug( get_post_field( 'post_name' ) );
			$description = $service && '' !== trim( (string) $service['summary'] ) ? $service['summary'] : $default;
			break;

		case 'services':
			$description = 'Carpet cleaning and repair, tile and grout, natural stone restoration, upholstery, pet odor and water damage — all from one family-owned company.';
			break;

		case 'about':
			$description = $name . ' has cleaned carpet, tile and stone for Houston-area homes and businesses since ' . newera_founded_year() . '. Meet the family behind it.';
			break;

		case 'pricing':
			$description = 'Current coupon prices for carpet, tile, grout and upholstery cleaning. House coupons cover up to 6 rooms, halls and closets. Every quote is free.';
			break;

		case 'guarantee':
			$description = 'Every job is backed by our 10-day, no-hassle guarantee, and our technicians are insured and bonded. If something is not right, we come back.';
			break;

		case 'photos':
			$description = 'Before-and-after photos from real carpet, tile, grout and natural stone jobs across the Houston area.';
			break;

		case 'quote':
			$description = 'Tell us about your space and get a free, no-obligation quote for carpet, tile, stone or upholstery cleaning. Prefer to talk? Call ' . $phone . '.';
			break;

		default:
			/*
			 * Anything with an editor behind it — a privacy policy, a blog post
			 * — describes itself better than the site-wide sentence can, so an
			 * excerpt wins where one has been written.
			 */
			$excerpt     = is_singular() && has_excerpt() ? get_the_excerpt() : '';
			$description = $excerpt ? $excerpt : $default;
			break;
	}

	return trim( wp_strip_all_tags( html_entity_decode( (string) apply_filters( 'newera_page_description', $description ), ENT_QUOTES, 'UTF-8' ) ) );
}

/**
 * Give the front page a title that describes the business.
 *
 * Without this the front page title is the Site Title and tagline, or — when a
 * static page is assigned in Settings > Reading — the word "Home". Google's
 * title-link guidance asks for something descriptive, with the location
 * included where it is relevant and the brand kept concise at one end.
 *
 * Only the front page is touched. Interior and service titles are already
 * unique, and templating "in Sugar Land, TX" into all seven service titles
 * would be exactly the repeated boilerplate that same guidance warns against —
 * and the doorway pattern this theme was built to avoid.
 *
 * @param array $parts Title parts: title, page, tagline, site.
 * @return array
 */
function newera_document_title_parts( $parts ) {
	if ( newera_seo_plugin_active() || ! is_front_page() || ! is_array( $parts ) ) {
		return $parts;
	}

	$address = newera_address();
	$region  = '' !== trim( (string) $address['region'] ) ? ', ' . $address['region'] : '';
	$brand   = newera_business_name_parts();

	$parts['title'] = (string) apply_filters(
		'newera_front_page_title',
		'Carpet, Tile & Stone Cleaning in ' . $address['locality'] . $region
	);

	$parts['site'] = $brand[0];
	unset( $parts['tagline'] );

	return $parts;
}
add_filter( 'document_title_parts', 'newera_document_title_parts' );

/**
 * Emit description, canonical, Open Graph, and Twitter card tags.
 */
function newera_meta_tags() {
	if ( newera_seo_plugin_active() ) {
		return;
	}

	$description = newera_page_description();
	/*
	 * newera_current_url() rather than home_url( add_query_arg( array() ) ):
	 * the latter appends REQUEST_URI to a home_url() that already carries the
	 * subdirectory prefix, and it keeps every query argument, so the quote
	 * confirmation would canonicalise to itself instead of the clean page.
	 */
	$canonical = newera_current_url();
	$image     = newera_logo_url();

	/*
	 * Decoded, not used raw. wp_get_document_title() runs its result through
	 * esc_html() before returning it, so the business name arrives here as
	 * "New Era Steam Cleaning &amp; Restoration". Passing that to esc_attr()
	 * escapes it a second time and the social card renders a literal "&amp;".
	 */
	$title = newera_plain_text( wp_get_document_title() );

	/*
	 * No robots tag is emitted here on purpose. WordPress owns that one through
	 * the `wp_robots` filter, which is what Settings > Reading > "Discourage
	 * search engines" drives. Printing a hard-coded "index, follow" beside it
	 * put two contradictory robots tags on every page of a staging site, and
	 * made per-page noindex impossible on any install without an SEO plugin.
	 */
	echo "\n";
	echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";

	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) . '" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $canonical ) . '" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";

	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
}
add_action( 'wp_head', 'newera_meta_tags', 1 );

/**
 * Whether this request is a post-submission quote confirmation.
 *
 * @return bool
 */
function newera_is_quote_state() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display state.
	return isset( $_GET['newera_quote'] );
}

/**
 * Keep the quote-confirmation URLs out of the index.
 *
 * A successful submission redirects back to the same page carrying
 * `newera_quote`, `newera_form` and `newera_token`, which produces a crawlable
 * variant of the home page, the quote page and all seven service pages. The
 * canonical already points at the clean URL; saying noindex outright is a
 * cheaper guarantee than relying on canonicalisation. `follow` is kept so the
 * links on the confirmation page still carry weight.
 *
 * @param array $robots Directives keyed by name.
 * @return array
 */
function newera_robots_quote_state( $robots ) {
	if ( newera_is_quote_state() && is_array( $robots ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'newera_robots_quote_state' );

/**
 * Keep thin, auto-generated archives out of the index.
 *
 * A marketing site with no blog still exposes date and author archives, and
 * they list the same handful of posts as each other under URLs nothing links
 * to. Core already noindexes search results through wp_robots_noindex_search(),
 * so that case is not repeated here.
 *
 * Skipped entirely when an SEO plugin is active: Yoast and its peers have their
 * own settings for exactly these archives, and a theme quietly overriding a
 * choice made in the plugin's UI is worse than leaving it alone.
 *
 * @param array $robots Directives keyed by name.
 * @return array
 */
function newera_robots_thin_archives( $robots ) {
	if ( newera_seo_plugin_active() || ! is_array( $robots ) ) {
		return $robots;
	}

	if ( is_date() || is_author() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['index'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'newera_robots_thin_archives' );

/**
 * The same, for Yoast, which emits its own robots tag rather than core's.
 *
 * @param array $robots Yoast's directives.
 * @return array
 */
function newera_yoast_robots_quote_state( $robots ) {
	if ( newera_is_quote_state() && is_array( $robots ) ) {
		$robots['index'] = 'noindex';
	}

	return $robots;
}
add_filter( 'wpseo_robots_array', 'newera_yoast_robots_quote_state' );

/**
 * Whether Yoast specifically is running.
 *
 * Distinct from newera_seo_plugin_active(): Yoast is the one plugin this theme
 * can hand its business details to, by extending the graph it already emits.
 * Under any other SEO plugin the theme keeps emitting its own nodes, because
 * there is nothing to merge into. See inc/schema.php.
 *
 * @return bool
 */
function newera_yoast_active() {
	return defined( 'WPSEO_VERSION' );
}
