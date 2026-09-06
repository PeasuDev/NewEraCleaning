<?php
/**
 * Theme bootstrap.
 *
 * New Era Steam Cleaning & Restoration — ported from the Blazor rebuild of
 * neweracleaning.com. See README.md in the parent folder for install steps.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

define( 'NEWERA_VERSION', '1.2.0' );

require_once get_theme_file_path( 'inc/company.php' );
require_once get_theme_file_path( 'inc/services.php' );
require_once get_theme_file_path( 'inc/testimonials.php' );
require_once get_theme_file_path( 'inc/pricing.php' );
require_once get_theme_file_path( 'inc/post-types.php' );
require_once get_theme_file_path( 'inc/template-tags.php' );
require_once get_theme_file_path( 'inc/quote-handler.php' );
require_once get_theme_file_path( 'inc/mail.php' );
require_once get_theme_file_path( 'inc/seo.php' );
require_once get_theme_file_path( 'inc/schema.php' );
require_once get_theme_file_path( 'inc/seo-audit.php' );

/**
 * Theme supports and menu locations.
 */
function newera_setup() {
	load_theme_textdomain( 'newera', get_theme_file_path( 'languages' ) );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 120,
			'width'       => 400,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary' => 'Primary Navigation',
			'footer'  => 'Footer Links',
		)
	);
}
add_action( 'after_setup_theme', 'newera_setup' );

/**
 * Whether Bootstrap Icons has been vendored into the theme.
 *
 * @return bool
 */
function newera_has_local_icons() {
	return file_exists( get_theme_file_path( 'assets/vendor/bootstrap-icons/bootstrap-icons.css' ) );
}

/**
 * Preload the icon font.
 *
 * The font is only discovered once the browser has fetched and parsed the icon
 * stylesheet, which puts it two round trips deep. It declares
 * `font-display: block`, so until it arrives every icon on the page is blank
 * space — including the phone icon in the header. Preloading collapses that to
 * one round trip.
 */
function newera_preload_icon_font() {
	if ( ! newera_has_local_icons() ) {
		/*
		 * Without a vendored copy the icons come from jsDelivr, and the font is
		 * two round trips deep: stylesheet, then font. The connection can at
		 * least be opened while the stylesheet request is still in flight.
		 * crossorigin is required — a font is fetched in CORS mode, and a
		 * preconnect without it warms a connection the font cannot reuse.
		 */
		echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin="anonymous" />' . "\n";

		return;
	}

	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin="anonymous" />' . "\n",
		esc_url( newera_asset( 'assets/vendor/bootstrap-icons/fonts/bootstrap-icons.woff2' ) )
	);
}
add_action( 'wp_head', 'newera_preload_icon_font', 2 );

/**
 * Front-end styles.
 *
 * Bootstrap Icons is loaded from the theme when a copy has been vendored into
 * assets/vendor/bootstrap-icons/, and from jsDelivr otherwise. Self-hosting is
 * therefore a drop-in change that needs no edits here. See README.md.
 */
function newera_enqueue_assets() {
	$local_icons = 'assets/vendor/bootstrap-icons/bootstrap-icons.css';

	if ( newera_has_local_icons() ) {
		wp_enqueue_style( 'bootstrap-icons', newera_asset( $local_icons ), array(), '1.11.3' );
	} else {
		wp_enqueue_style(
			'bootstrap-icons',
			'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css',
			array(),
			'1.11.3'
		);
	}

	wp_enqueue_style(
		'newera-style',
		get_stylesheet_uri(),
		array( 'bootstrap-icons' ),
		NEWERA_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'newera_enqueue_assets' );

/**
 * Remove the viewport tag WordPress block themes add, and emit one that permits
 * pinch-zoom.
 *
 * The live site currently ships `maximum-scale=1, user-scalable=no`, which is a
 * WCAG 1.4.4 failure flagged in docs/neweracleaning-website-analysis.md. The
 * tag is written out in header.php; this exists so nothing re-adds a locked one.
 */
function newera_remove_block_viewport() {
	remove_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
}
add_action( 'init', 'newera_remove_block_viewport' );

/**
 * The navigation shown when no menu has been assigned to the primary location.
 *
 * This mirrors the nav in Layout/PublicLayout.razor so the site is correct the
 * moment the theme is activated, before anyone visits Appearance > Menus.
 */
function newera_default_nav() {
	$items = array(
		home_url( '/' )          => 'Home',
		newera_about_url()       => 'About',
		newera_services_url()    => 'Services',
		newera_pricing_url()     => 'Pricing',
		newera_photos_url()      => 'Job Photos',
		newera_guarantee_url()   => 'Guarantee',
		newera_quote_url()       => 'Get a Quote',
	);

	echo '<ul>';

	foreach ( $items as $url => $label ) {
		printf(
			'<li><a href="%s">%s</a></li>',
			esc_url( $url ),
			esc_html( $label )
		);
	}

	echo '</ul>';
}

/**
 * Flush rewrite rules once on activation so the service URLs resolve.
 */
function newera_activate() {
	newera_register_post_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'newera_activate' );

/**
 * Warn about pages sitting on the service catalogue's URLs.
 *
 * The theme deliberately reuses the paths the live site has always served, so
 * on an existing install the old service pages are still there — and a real
 * page beats a post type archive in the rewrite table. Left in place they hide
 * the whole catalogue, and because the old page still renders, the failure
 * looks like "nothing happened" rather than an error.
 *
 * The old pages need trashing, not redirecting: the new services take the
 * identical URLs, which is the entire point of keeping them.
 */
function newera_services_slug_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	/*
	 * Checking costs one lookup per service, so limit it to the screens where
	 * someone is actually installing or arranging content rather than running it
	 * on every admin page view.
	 */
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$where  = $screen ? $screen->id : '';

	if ( ! in_array( $where, array( 'dashboard', 'themes', 'edit-page', 'edit-newera_service', 'options-permalink', 'import' ), true ) ) {
		return;
	}

	$base = newera_services_base();
	$conflicts = array();

	$archive_page = get_page_by_path( $base );

	if ( $archive_page ) {
		$conflicts[] = $archive_page;
	}

	foreach ( newera_get_services() as $service ) {
		$child = get_page_by_path( $base . '/' . $service['slug'] );

		if ( $child ) {
			$conflicts[] = $child;
		}
	}

	if ( ! $conflicts ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>New Era theme:</strong>
			<?php echo count( $conflicts ) === 1 ? 'a page is' : esc_html( count( $conflicts ) ) . ' pages are'; ?>
			sitting on the service catalogue's URLs. WordPress serves a page in preference to the
			service of the same address, so
			<?php echo count( $conflicts ) === 1 ? 'that page hides its service' : 'these hide the services behind them'; ?>.
			Move
			<?php echo count( $conflicts ) === 1 ? 'it' : 'them'; ?>
			to the Trash — the new services take the same URLs, so nothing needs redirecting.
		</p>
		<ul style="list-style:disc;margin-left:2em;">
			<?php foreach ( $conflicts as $conflict ) : ?>
				<li>
					<code>/<?php echo esc_html( get_page_uri( $conflict ) ); ?>/</code> &mdash;
					<a href="<?php echo esc_url( get_edit_post_link( $conflict->ID ) ); ?>">edit</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
add_action( 'admin_notices', 'newera_services_slug_notice' );

/**
 * Warn when the WordPress Site Title disagrees with the business name.
 *
 * These are two different sources feeding the same fact. The theme renders
 * newera_business_name() in the header, footer and copy, while Yoast builds
 * titles and its Organization schema from the Site Title. When they differ the
 * site tells Google one name and shows visitors another, which is precisely the
 * inconsistency that weakens a local listing — and nothing anywhere reports it.
 */
function newera_business_name_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$expected = newera_business_name();
	$actual   = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	if ( $expected === $actual ) {
		return;
	}
	?>
	<div class="notice notice-warning">
		<p>
			<strong>New Era theme:</strong> the Site Title and the business name do not match.
		</p>
		<p>
			Site Title: <code><?php echo esc_html( $actual ); ?></code><br />
			Business name: <code><?php echo esc_html( $expected ); ?></code>
		</p>
		<p>
			Yoast builds page titles and its Organization schema from the Site Title, so search
			results will show one name while the page shows the other. Set them to the same value
			in <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Settings &rarr; General</a>,
			and make sure it matches the Google Business Profile exactly.
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'newera_business_name_notice' );

/**
 * Point the "Posts page" excerpt length at something readable.
 *
 * @param int $length Default word count.
 * @return int
 */
function newera_excerpt_length( $length ) {
	return 28;
}
add_filter( 'excerpt_length', 'newera_excerpt_length' );

/**
 * Trim the excerpt with an ellipsis rather than [...].
 *
 * @param string $more Default suffix.
 * @return string
 */
function newera_excerpt_more( $more ) {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'newera_excerpt_more' );
