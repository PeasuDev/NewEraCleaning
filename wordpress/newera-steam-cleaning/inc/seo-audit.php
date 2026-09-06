<?php
/**
 * The wp-admin SEO checklist.
 *
 * Everything else in this theme's SEO work takes effect the moment the theme is
 * active. What is left is the handful of things code cannot supply for itself —
 * a Business Profile URL nobody has pasted in, a service with no photo, a
 * summary that was left blank so the page falls back to the generic site
 * description.
 *
 * Each of those fails silently. The page still renders, the markup is still
 * valid, and nothing anywhere says the site is weaker than it looks. This file
 * is the thing that says so.
 *
 * One notice rather than one per problem, on the same restricted set of screens
 * as newera_services_slug_notice(): a checklist is read once, whereas four
 * stacked warnings are dismissed as noise.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The longest a meta description should be before Google truncates it.
 *
 * Not a rule from Google — there is no documented limit, only "short, unique,
 * one or two sentences". This is the width the desktop snippet has held for
 * long enough to plan around.
 */
define( 'NEWERA_DESCRIPTION_MAX', 160 );

/**
 * Outstanding SEO items, as ready-to-render HTML fragments.
 *
 * @return array<int,string> Empty when nothing needs attention.
 */
function newera_seo_findings() {
	$findings = array();

	/*
	 * The launch-day mistake. Settings > Reading has a checkbox that adds
	 * noindex to every page of the site, it is on by default on a great many
	 * staging installs, and it is routinely carried into production. Nothing in
	 * the front end looks any different.
	 */
	if ( ! get_option( 'blog_public' ) ) {
		$findings[] = sprintf(
			'<strong>The whole site is set to noindex.</strong> &ldquo;Discourage search engines from indexing this site&rdquo; is switched on in <a href="%s">Settings &rarr; Reading</a>. Nothing here will be indexed until it is switched off.',
			esc_url( admin_url( 'options-reading.php' ) )
		);
	}

	if ( '' === trim( newera_google_business_profile() ) ) {
		$findings[] = 'No <strong>Google Business Profile</strong> URL is set. For a service-area business the Business Profile is what ranks in the local map pack, and <code>sameAs</code> is how this site and that listing are tied to the same company. Add the listing&rsquo;s share link with the <code>newera_google_business_profile</code> filter.';
	}

	$no_image = array();
	$no_summary = array();
	$long_summary = array();

	foreach ( newera_get_services() as $service ) {
		// The seeded catalogue has no posts behind it, so nothing to check yet.
		if ( empty( $service['id'] ) ) {
			continue;
		}

		$name = newera_plain_text( $service['name'] );

		if ( ! has_post_thumbnail( $service['id'] ) ) {
			$no_image[] = $name;
		}

		$summary = newera_plain_text( $service['summary'] );

		if ( '' === $summary ) {
			$no_summary[] = $name;
		} elseif ( mb_strlen( $summary ) > NEWERA_DESCRIPTION_MAX ) {
			$long_summary[] = $name . ' (' . mb_strlen( $summary ) . ')';
		}
	}

	if ( $no_summary ) {
		$findings[] = sprintf(
			'%s %s no Summary, so %s meta description falls back to the generic site-wide one. Google asks for a short, unique description per page.',
			'<strong>' . esc_html( implode( ', ', $no_summary ) ) . '</strong>',
			count( $no_summary ) === 1 ? 'has' : 'have',
			count( $no_summary ) === 1 ? 'its' : 'their'
		);
	}

	if ( $long_summary ) {
		$findings[] = sprintf(
			'%s over %d characters, so the search snippet will be cut off mid-sentence.',
			'<strong>' . esc_html( implode( ', ', $long_summary ) ) . '</strong> ' . ( count( $long_summary ) === 1 ? 'is' : 'are' ),
			NEWERA_DESCRIPTION_MAX
		);
	}

	if ( $no_image ) {
		$findings[] = sprintf(
			'%s %s no featured image. The photo is the page&rsquo;s largest element, its Open Graph card and its <code>image</code> in structured data.',
			'<strong>' . esc_html( implode( ', ', $no_image ) ) . '</strong>',
			count( $no_image ) === 1 ? 'has' : 'have'
		);
	}

	/*
	 * Only meaningful once the business has a street address to publish. A
	 * service-area business is not required to have one, and geo coordinates
	 * for an address that is not public would describe a place customers cannot
	 * visit — so this is checked only when an address is actually set.
	 */
	$address = newera_address();

	if ( '' !== trim( (string) $address['street'] ) && '' === trim( (string) $address['postal'] ) ) {
		$findings[] = 'A street address is published without a postal code. Google asks for as much of the address as possible; a partial one is a weaker match against the Business Profile.';
	}

	return $findings;
}

/**
 * Render the checklist.
 */
function newera_seo_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$where  = $screen ? $screen->id : '';

	if ( ! in_array( $where, array( 'dashboard', 'themes', 'edit-page', 'edit-newera_service', 'options-reading', 'import' ), true ) ) {
		return;
	}

	$findings = newera_seo_findings();

	if ( ! $findings ) {
		return;
	}
	?>
	<div class="notice notice-warning is-dismissible">
		<p><strong>New Era theme &mdash; SEO checklist:</strong></p>
		<ul style="list-style:disc;margin-left:2em;">
			<?php foreach ( $findings as $finding ) : ?>
				<li><?php echo wp_kses_post( $finding ); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
add_action( 'admin_notices', 'newera_seo_notice' );
