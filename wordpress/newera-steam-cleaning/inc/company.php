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
	return (string) apply_filters( 'newera_google_business_profile', '' );
}

/**
 * Social profile URLs, keyed by network.
 *
 * @return array<string,string>
 */
function newera_social_links() {
	$links = array(
		'facebook' => 'https://www.facebook.com/newerasteamclean',
		'yelp'     => 'https://www.yelp.com/biz/new-era-steam-cleaning-and-restoration-sugar-land',
		'angi'     => 'https://www.angi.com/companylist/us/tx/austin/new-era-steam-cleaning-and-restoration-reviews-8435486.htm',
	);

	$google = trim( newera_google_business_profile() );

	if ( '' !== $google ) {
		$links['google'] = $google;
	}

	return (array) apply_filters( 'newera_social_links', $links );
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
