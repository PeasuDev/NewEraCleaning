<?php
/**
 * Company-wide marketing facts.
 *
 * Single source of truth for the details that appear on more than one page:
 * phone number, hours, service area, and the advertised years of experience.
 * Ported from Business Tools Suite.Client/Services/CompanyFacts.cs.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * Years of industry experience to advertise.
 *
 * Starts at 25+ in 2026 and increases by one each calendar year, never
 * reporting less than the anchored value.
 *
 * **This is not the age of the company, and the two must not be reconciled.**
 * The business was founded in 2014 — see newera_founded_year() — so this figure
 * is larger than the company is old, on purpose: it describes the trade
 * experience the technicians bring, which predates the business. The About copy
 * words it as "hands-on experience" and the home page as "technicians with N+
 * years in the trade" for exactly that reason.
 *
 * Confirmed with the owner on 7 Sep 2026, when the site was publishing three
 * different business ages at once. If the two figures ever need to agree,
 * change the copy rather than this number.
 *
 * @return int
 */
function newera_years_of_experience() {
	$anchor_year = 2026;
	$at_anchor   = 25;
	$now         = (int) current_time( 'Y' );

	return (int) apply_filters(
		'newera_years_of_experience',
		$at_anchor + max( 0, $now - $anchor_year )
	);
}

/**
 * The business phone number.
 *
 * @param string $format 'display' for (713) 906-0918, 'tel' for a tel: href,
 *                       'schema' for the E.164 form used in JSON-LD.
 * @return string
 */
function newera_phone( $format = 'display' ) {
	$formats = array(
		'display' => '(713) 906-0918',
		'tel'     => 'tel:+17139060918',
		'schema'  => '+1-713-906-0918',
	);

	$formats = apply_filters( 'newera_phone_formats', $formats );

	return isset( $formats[ $format ] ) ? $formats[ $format ] : $formats['display'];
}

/**
 * Cities the business serves, in display order.
 *
 * @return string[]
 */
function newera_service_areas() {
	return (array) apply_filters(
		'newera_service_areas',
		array( 'Sugar Land', 'Houston', 'Katy', 'Richmond', 'Missouri City', 'Fulshear' )
	);
}

/**
 * The business's postal address, for structured data.
 *
 * New Era is a service-area business: technicians travel to the customer and
 * there is no shopfront to visit. Google asks that a published street address
 * be one customers can actually turn up at, so `street` and `postal` are empty
 * by default and the corresponding schema properties are simply omitted.
 *
 * Fill them in — here or through the filter — only if the owner wants a real,
 * visitable address published. Locality and region are always emitted, because
 * a local business with no place attached to it at all is a weaker signal than
 * one anchored to a city.
 *
 * @return array{street:string,locality:string,region:string,postal:string,country:string}
 */
function newera_address() {
	return (array) apply_filters(
		'newera_address',
		array(
			'street'   => '',
			'locality' => 'Sugar Land',
			'region'   => 'TX',
			'postal'   => '',
			'country'  => 'US',
		)
	);
}

/**
 * Advertised price range, in schema.org's $ to $$$$ notation.
 *
 * This is a public claim about what the business charges, so it should match
 * reality rather than be left at whatever the port happened to ship.
 *
 * @return string
 */
function newera_price_range() {
	return (string) apply_filters( 'newera_price_range', '$$' );
}

/**
 * Human-readable opening hours.
 *
 * @return string
 */
function newera_hours() {
	return (string) apply_filters( 'newera_hours', 'Mon&ndash;Fri: 9:00 AM &ndash; 6:00 PM' );
}

/**
 * The year the business was founded.
 *
 * @return string
 */
function newera_founded_year() {
	return (string) apply_filters( 'newera_founded_year', '2014' );
}

/**
 * The public URL of the Google Business Profile listing.
 *
 * Empty by default because nobody has supplied it yet, and a wrong URL here is
 * worse than none — `sameAs` is a claim that two profiles are the same entity.
 *
 * This is the single most valuable identifier missing from the site's markup:
 * for a service-area business, the Business Profile is what actually ranks in
 * the local pack, and `sameAs` is how the site and that listing are tied
 * together. newera_seo_notice() reports it while it is unset.
 *
 * Accepts either the maps.google.com/?cid=… share link or a g.page/ short link.
 *
 * @return string
 */
function newera_google_business_profile() {
	/*
	 * Saved in wp-admin (Settings > General) so the owner can set it without
	 * touching code. The filter still wins where one is registered, which keeps
	 * any existing child theme or snippet working.
	 */
	$saved = ( defined( 'NEWERA_GBP_OPTION' ) && function_exists( 'get_option' ) )
		? (string) get_option( NEWERA_GBP_OPTION, '' )
		: '';

	return (string) apply_filters( 'newera_google_business_profile', $saved );
}

/**
 * Social profile URLs, keyed by network.
 *
 * @return array<string,string>
 */
function newera_social_links() {
	/*
	 * No Angi listing here, deliberately. It is filed under Austin and carries
	 * that city in its URL, so linking it from a Houston-area site sends people
	 * to a page asserting a location the business does not serve from. Removed
	 * at the owner's request on 2026-09-10.
	 */
	$links = array(
		'facebook' => 'https://www.facebook.com/newerasteamclean',
		'yelp'     => 'https://www.yelp.com/biz/new-era-steam-cleaning-and-restoration-sugar-land',
	);

	$google = trim( newera_google_business_profile() );

	if ( '' !== $google ) {
		$links['google'] = $google;
	}

	return (array) apply_filters( 'newera_social_links', $links );
}

/**
 * Social profile URLs suitable for the `sameAs` array in structured data.
 *
 * Now the same list as newera_social_links(). It used to strip the Angi listing
 * out of schema while the footer still linked it; the listing has since been
 * removed outright, so there is nothing left to subtract. The function stays as
 * the seam between "profiles we link" and "profiles we claim as our identity",
 * because those are different questions and the next one to diverge should not
 * have to reintroduce the distinction.
 *
 * Note this is not the only path into sameAs. Yoast builds its own array from
 * its own settings, which the theme does not control; newera_yoast_organization()
 * filters that separately.
 *
 * @return array<string,string>
 */
function newera_schema_profiles() {
	return (array) apply_filters( 'newera_schema_profiles', newera_social_links() );
}

/**
 * The business name, used in titles, schema, and the footer.
 *
 * Plain text, not an HTML fragment: escape it at the point of output.
 *
 * @return string
 */
function newera_business_name() {
	return (string) apply_filters( 'newera_business_name', 'New Era Steam Cleaning & Restoration' );
}

/**
 * The business name split for the two-line header lockup.
 *
 * The header used to hard-code "New Era Steam Cleaning" above "& Restoration",
 * which meant renaming the business changed the schema and the footer but left
 * the logo saying something else. Splitting on the ampersand keeps the design
 * and derives both halves from the one name.
 *
 * @return array{0:string,1:string} Primary line, then the remainder (may be empty).
 */
function newera_business_name_parts() {
	$name  = newera_business_name();
	$parts = explode( ' & ', $name, 2 );

	return array(
		trim( $parts[0] ),
		isset( $parts[1] ) ? '& ' . trim( $parts[1] ) : '',
	);
}
