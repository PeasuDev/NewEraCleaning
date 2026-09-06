<?php
/**
 * Structured data.
 *
 * One set of node builders with two consumers: newera_json_ld() emits a
 * connected @graph on an install with no SEO plugin, and the wpseo_* filters
 * add the same business facts and service nodes to the graph Yoast already
 * publishes. Nothing is described twice.
 *
 * What is deliberately absent, so it does not get "fixed" back in later:
 *
 * - FAQPage and HowTo. Both were withdrawn from Google's rich results gallery;
 *   the markup has no consumer left and only invites mismatch warnings.
 * - SearchAction / sitelinks search box. Deprecated, and this site has no
 *   search UI to describe.
 * - aggregateRating on the business. The testimonials are republished from Yelp
 *   and Angi; averaging them into a rating for ourselves is self-serving review
 *   markup, unsupported on LocalBusiness and a manual-action risk. The stars
 *   stay visible page content — the hero card in front-page.php shows the star
 *   row and review count, and links out to the three profiles they come from.
 *
 * Google's own guidance on generative AI features (May 2026) is that structured
 * data is not required for them and no special markup exists. What is here is
 * for entity understanding and the two rich results this site can actually
 * earn: local business and breadcrumbs.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/* -----------------------------------------------------------------
 * Identifiers
 * ----------------------------------------------------------------- */

/**
 * The @id of the node describing the business.
 *
 * Under Yoast the business *is* Yoast's Organization node, promoted to a
 * LocalBusiness by newera_yoast_organization(). Pointing `provider` at a
 * second, theme-invented @id would describe the same company twice under two
 * names — the drift this theme has consistently avoided.
 *
 * @return string
 */
function newera_business_id() {
	$id = newera_yoast_active()
		? trailingslashit( home_url() ) . '#organization'
		: home_url( '/#business' );

	return (string) apply_filters( 'newera_business_id', $id );
}

/**
 * The @id of the WebSite node.
 *
 * @return string
 */
function newera_website_id() {
	return home_url( '/#website' );
}

/* -----------------------------------------------------------------
 * Shared property builders
 * ----------------------------------------------------------------- */

/**
 * The cities served, as Place nodes rather than bare strings.
 *
 * `areaServed` accepts text, but three of the six names are ambiguous without a
 * state attached — Richmond above all. Naming the containing state costs a few
 * bytes and removes the guess.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_area_served_schema() {
	$address = newera_address();
	$region  = trim( (string) $address['region'] );

	$state = '' !== $region
		? array(
			'@type' => 'State',
			'name'  => $region,
		)
		: null;

	$areas = array();

	foreach ( newera_service_areas() as $city ) {
		$place = array(
			'@type' => 'City',
			'name'  => newera_plain_text( $city ),
		);

		if ( $state ) {
			$place['containedInPlace'] = $state;
		}

		$areas[] = $place;
	}

	return $areas;
}

/**
 * The service catalogue as an OfferCatalog hanging off the business.
 *
 * This is the one place the business node says what it actually sells. Each
 * entry carries the @id of the Service node the corresponding page emits, so
 * the catalogue and the service pages resolve to the same eight entities
 * rather than sixteen.
 *
 * @return array<string,mixed>
 */
function newera_offer_catalog() {
	$offers = array();

	foreach ( newera_get_services() as $service ) {
		$offers[] = array(
			'@type'       => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'@id'   => $service['permalink'] . '#service',
				'name'  => newera_plain_text( $service['name'] ),
				'url'   => $service['permalink'],
			),
		);
	}

	return array(
		'@type'           => 'OfferCatalog',
		'name'            => 'Cleaning and Restoration Services',
		'itemListElement' => $offers,
	);
}

/**
 * The business's identity as schema.org properties, without the wrapper.
 *
 * Shared by the two consumers below so the same facts are published whether the
 * theme emits its own node or extends Yoast's.
 *
 * @return array<string,mixed>
 */
function newera_business_schema() {
	$address = newera_address();

	$postal = array(
		'@type'           => 'PostalAddress',
		'addressLocality' => $address['locality'],
		'addressRegion'   => $address['region'],
		'addressCountry'  => $address['country'],
	);

	// Omitted rather than left blank: an empty streetAddress is worse than none.
	if ( '' !== trim( (string) $address['street'] ) ) {
		$postal['streetAddress'] = $address['street'];
	}

	if ( '' !== trim( (string) $address['postal'] ) ) {
		$postal['postalCode'] = $address['postal'];
	}

	return (array) apply_filters(
		'newera_business_schema',
		array(
			'telephone'                 => newera_phone( 'schema' ),
			'foundingDate'              => newera_founded_year(),
			'priceRange'                => newera_price_range(),
			'areaServed'                => newera_area_served_schema(),
			'address'                   => $postal,
			'hasOfferCatalog'           => newera_offer_catalog(),
			'openingHoursSpecification' => array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
				'opens'     => '09:00',
				'closes'    => '18:00',
			),
		)
	);
}

/* -----------------------------------------------------------------
 * Nodes
 * ----------------------------------------------------------------- */

/**
 * The LocalBusiness node.
 *
 * The Blazor source declared this as `HousePainter`, which is simply the wrong
 * schema type for a cleaning and restoration company; it is corrected here.
 *
 * @return array<string,mixed>
 */
function newera_business_node() {
	return array_merge(
		array(
			'@type'       => array( 'LocalBusiness', 'HomeAndConstructionBusiness' ),
			'@id'         => newera_business_id(),
			'name'        => newera_business_name(),
			'image'       => newera_logo_url(),
			'logo'        => newera_logo_url(),
			'description' => 'Local, family-owned steam cleaning company serving residential and commercial customers across the greater Houston area since ' . newera_founded_year() . '.',
			'url'         => home_url( '/' ),
			'sameAs'      => array_values( newera_social_links() ),
		),
		newera_business_schema()
	);
}

/**
 * The WebSite node.
 *
 * @return array<string,mixed>
 */
function newera_website_node() {
	return array(
		'@type'      => 'WebSite',
		'@id'        => newera_website_id(),
		'url'        => home_url( '/' ),
		'name'       => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'publisher'  => array( '@id' => newera_business_id() ),
		'inLanguage' => get_bloginfo( 'language' ),
	);
}

/**
 * The WebPage node for the request being rendered.
 *
 * @return array<string,mixed>
 */
function newera_webpage_node() {
	$url = newera_current_url();

	$types = array(
		'about'    => 'AboutPage',
		'quote'    => 'ContactPage',
		'services' => 'CollectionPage',
		'photos'   => 'CollectionPage',
	);

	$kind = newera_page_kind();

	$node = array(
		'@type'       => isset( $types[ $kind ] ) ? $types[ $kind ] : 'WebPage',
		'@id'         => $url . '#webpage',
		'url'         => $url,
		'name'        => newera_plain_text( wp_get_document_title() ),
		'description' => newera_page_description(),
		'isPartOf'    => array( '@id' => newera_website_id() ),
		'inLanguage'  => get_bloginfo( 'language' ),
	);

	if ( is_front_page() ) {
		$node['about'] = array( '@id' => newera_business_id() );
	} else {
		/*
		 * Every template except front-page.php renders newera_page_banner(),
		 * which is what produces the BreadcrumbList this points at. The node
		 * itself is emitted in the footer — see newera_breadcrumb_json_ld().
		 */
		$node['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
	}

	if ( is_singular() && has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'large' );

		if ( $image ) {
			$node['primaryImageOfPage'] = array(
				'@type' => 'ImageObject',
				'url'   => $image,
			);
		}
	}

	if ( is_singular() ) {
		$node['datePublished'] = get_the_date( DATE_W3C );
		$node['dateModified']  = get_the_modified_date( DATE_W3C );
	}

	return $node;
}

/**
 * The Service node for a single service page.
 *
 * There is no rich result for Service — this is here so the pages resolve to
 * the same entities the business's OfferCatalog lists, rather than as eight
 * unrelated documents.
 *
 * @return array<string,mixed>|null Null when the post has no catalogue entry.
 */
function newera_service_node() {
	$service = newera_get_service_by_slug( (string) get_post_field( 'post_name' ) );

	if ( ! $service ) {
		return null;
	}

	$url     = get_permalink();
	$summary = newera_plain_text( $service['summary'] );

	/*
	 * The summary is a sentence written for exactly this job. Where a service
	 * has none the full body copy is the only thing left, and that runs to a
	 * thousand characters — trimmed rather than published whole.
	 */
	$description = '' !== $summary
		? $summary
		: wp_trim_words( newera_plain_text( $service['description'] ), 40, '…' );

	$node = array(
		'@type'       => 'Service',
		'@id'         => $url . '#service',
		'name'        => newera_plain_text( $service['name'] ),
		'serviceType' => newera_plain_text( $service['name'] ),
		'url'         => $url,
		'description' => $description,
		'provider'    => array( '@id' => newera_business_id() ),
		'areaServed'  => newera_area_served_schema(),
	);

	if ( has_post_thumbnail() ) {
		$image = get_the_post_thumbnail_url( null, 'large' );

		if ( $image ) {
			$node['image'] = $image;
		}
	}

	return $node;
}

/**
 * The ItemList node for the services archive.
 *
 * @return array<string,mixed>
 */
function newera_service_list_node() {
	$items    = array();
	$position = 1;

	foreach ( newera_get_services() as $service ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => newera_plain_text( $service['name'] ),
			'url'      => $service['permalink'],
		);

		$position++;
	}

	return array(
		'@type'           => 'ItemList',
		'@id'             => newera_services_url() . '#services',
		'name'            => 'Cleaning and Restoration Services',
		'itemListElement' => $items,
	);
}

/**
 * The nodes that describe what this particular page is about.
 *
 * Shared by both consumers: these are additions to whichever graph is being
 * built, and never duplicate anything an SEO plugin already emits.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_page_nodes() {
	$nodes = array();

	if ( is_singular( 'newera_service' ) ) {
		$service = newera_service_node();

		if ( $service ) {
			$nodes[] = $service;
		}
	}

	if ( is_post_type_archive( 'newera_service' ) ) {
		$nodes[] = newera_service_list_node();
	}

	return $nodes;
}

/* -----------------------------------------------------------------
 * Breadcrumbs
 * ----------------------------------------------------------------- */

/**
 * Record, or read back, the breadcrumb trail this request rendered.
 *
 * Google requires breadcrumb markup to match the trail the visitor can see.
 * Deriving the trail twice — once in the banner, once from the query in
 * wp_head — is how those two drift apart, and four templates already pass a
 * banner title that differs from the post title (page-pricing.php renders
 * "Simple, Transparent Pricing" for a page called "Pricing").
 *
 * So the banner hands its trail to this function as it renders, and the markup
 * is emitted afterwards in the footer from that exact record. JSON-LD is valid
 * anywhere in the document, and this makes the two structurally incapable of
 * disagreeing.
 *
 * @param array|null $trail Ordered items, each 'label' and 'url'. Null to read.
 * @return array<int,array{label:string,url:string}>
 */
function newera_breadcrumb_trail( $trail = null ) {
	static $stored = array();

	if ( is_array( $trail ) ) {
		$stored = $trail;
	}

	return $stored;
}

/**
 * Emit the BreadcrumbList for the trail the page rendered.
 *
 * Runs on wp_footer, by which time newera_page_banner() has been called. Stands
 * down under any SEO plugin: Yoast, Rank Math and the rest all publish their
 * own BreadcrumbList, and two of them on one page is a conflict, not a bonus.
 */
function newera_breadcrumb_json_ld() {
	if ( newera_seo_plugin_active() ) {
		return;
	}

	$trail = newera_breadcrumb_trail();

	// A single "Home" crumb describes nothing worth marking up.
	if ( count( $trail ) < 2 ) {
		return;
	}

	$items    = array();
	$position = 1;

	foreach ( $trail as $crumb ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $crumb['label'],
		);

		/*
		 * The last crumb is the current page and carries no link, which is also
		 * how Google documents it: the final ListItem omits `item`.
		 */
		if ( '' !== $crumb['url'] ) {
			$item['item'] = $crumb['url'];
		}

		$items[]  = $item;
		$position++;
	}

	newera_print_json_ld(
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'@id'             => newera_current_url() . '#breadcrumb',
			'itemListElement' => $items,
		)
	);
}
add_action( 'wp_footer', 'newera_breadcrumb_json_ld' );

/* -----------------------------------------------------------------
 * Output
 * ----------------------------------------------------------------- */

/**
 * Print one JSON-LD script block.
 *
 * @param array $data Structured data to encode.
 */
function newera_print_json_ld( $data ) {
	echo "\n" . '<script type="application/ld+json">' .
		wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) .
		'</script>' . "\n";
}

/**
 * The theme's own structured data graph.
 *
 * Two different gates, because "an SEO plugin is installed" and "Yoast is
 * installed" call for different things:
 *
 * - The business and page nodes are emitted whenever Yoast is absent. Under any
 *   other plugin there is no Organization node this theme knows how to extend,
 *   so it publishes its own — which is what the original port did.
 * - WebSite and WebPage are emitted only when there is no SEO plugin at all.
 *   Every one of them already publishes both, and a second copy under a
 *   different @id is a rival description of the same page.
 *
 * Note for anyone hooking `newera_json_ld`: the value passed to that filter is
 * now `{ '@context': …, '@graph': [ … ] }`, where it used to be a single flat
 * LocalBusiness node. A filter written against the old shape will still
 * receive an array and will still return one, so nothing errors — it just
 * silently edits a key that no longer exists.
 *
 * The business node is also emitted on every page now, not only the front page.
 * That is what lets the Service nodes point `provider` at something that
 * resolves.
 */
function newera_json_ld() {
	if ( newera_yoast_active() ) {
		return;
	}

	$graph = array( newera_business_node() );

	if ( ! newera_seo_plugin_active() ) {
		$graph[] = newera_website_node();
		$graph[] = newera_webpage_node();
	}

	foreach ( newera_page_nodes() as $node ) {
		$graph[] = $node;
	}

	$data = apply_filters(
		'newera_json_ld',
		array(
			'@context' => 'https://schema.org',
			'@graph'   => array_values( $graph ),
		)
	);

	newera_print_json_ld( $data );
}
add_action( 'wp_head', 'newera_json_ld', 5 );

/* -----------------------------------------------------------------
 * Yoast integration
 * ----------------------------------------------------------------- */

/**
 * Turn Yoast's Organization node into a LocalBusiness.
 *
 * Yoast already publishes Organization, WebSite, WebPage and BreadcrumbList as
 * one connected graph. Rather than add a rival business entity, this promotes
 * the type it already emits and fills in the local-business properties Yoast
 * has no settings field for.
 *
 * Name, url, logo, image and sameAs are left alone — those come from Yoast's
 * own configuration, and overwriting them is how the two sources drift apart.
 *
 * @param array $data The Organization piece Yoast is about to output.
 * @return array
 */
function newera_yoast_organization( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	$types = isset( $data['@type'] ) ? (array) $data['@type'] : array( 'Organization' );

	foreach ( array( 'LocalBusiness', 'HomeAndConstructionBusiness' ) as $type ) {
		if ( ! in_array( $type, $types, true ) ) {
			$types[] = $type;
		}
	}

	$data['@type'] = array_values( $types );

	foreach ( newera_business_schema() as $key => $value ) {
		if ( ! isset( $data[ $key ] ) || '' === $data[ $key ] || array() === $data[ $key ] ) {
			$data[ $key ] = $value;
		}
	}

	return $data;
}
add_filter( 'wpseo_schema_organization', 'newera_yoast_organization' );

/**
 * Add the service nodes to Yoast's graph.
 *
 * Yoast describes the page and the company; it has no idea the company sells
 * eight specific things. These are additions only — nothing Yoast emits is
 * touched — and `provider` is resolved from the Organization node actually
 * present in the graph rather than assumed, so a site that has customised its
 * @id still links up.
 *
 * @param array $graph The pieces Yoast is about to output.
 * @return array
 */
function newera_yoast_schema_graph( $graph ) {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}

	$nodes = newera_page_nodes();

	if ( ! $nodes ) {
		return $graph;
	}

	$organization = '';

	foreach ( $graph as $piece ) {
		if ( ! is_array( $piece ) || empty( $piece['@id'] ) ) {
			continue;
		}

		$types = isset( $piece['@type'] ) ? (array) $piece['@type'] : array();

		if ( in_array( 'Organization', $types, true ) ) {
			$organization = (string) $piece['@id'];
			break;
		}
	}

	foreach ( $nodes as $node ) {
		if ( $organization && isset( $node['provider'] ) ) {
			$node['provider'] = array( '@id' => $organization );
		}

		$graph[] = $node;
	}

	return $graph;
}
add_filter( 'wpseo_schema_graph', 'newera_yoast_schema_graph' );
