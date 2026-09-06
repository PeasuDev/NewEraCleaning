<?php
/**
 * Template helpers.
 *
 * Two kinds of function live here: data accessors that read edited WordPress
 * content and fall back to the values ported from C#, and the reusable markup
 * blocks that were Razor components (PageBanner, CtaBand, QuoteForm, and the
 * service card).
 *
 * Escaping convention: short labels — service names, review authors, the
 * business name — are plain text and go through esc_html(). Long-form copy
 * carries typographic entities such as &mdash; and is emitted with
 * wp_kses_post(). Mixing the two double-escapes one or under-escapes the other.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * URL for a file shipped inside the theme.
 *
 * @param string $path Path relative to the theme root, e.g. 'assets/img/x.jpg'.
 * @return string
 */
function newera_asset( $path ) {
	return get_theme_file_uri( ltrim( $path, '/' ) );
}

/**
 * A Bootstrap Icons glyph.
 *
 * Decorative by default: the icons repeat information already present in the
 * adjacent text, so they are hidden from assistive technology.
 *
 * @param string $name  Icon name, with or without the `bi-` prefix.
 * @param string $extra Additional class names.
 * @return string
 */
function newera_icon( $name, $extra = '' ) {
	$name  = 0 === strpos( $name, 'bi-' ) ? $name : 'bi-' . $name;
	$class = trim( 'bi ' . $name . ' ' . $extra );

	return '<span class="' . esc_attr( $class ) . '" aria-hidden="true"></span>';
}

/**
 * Flatten a copy string to plain text.
 *
 * The ported copy contains entities such as &mdash; and occasional markup.
 * Structured data wants neither: wp_json_encode() escapes the string itself, so
 * an entity that arrives here undecoded is published literally as "&mdash;".
 *
 * @param string $text Text possibly containing entities and markup.
 * @return string
 */
function newera_plain_text( $text ) {
	return trim(
		wp_strip_all_tags(
			html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' )
		)
	);
}

/**
 * Flatten a copy string into something safe for an HTML attribute.
 *
 * Entities must be decoded before re-encoding or they end up double-escaped.
 *
 * @param string $text Text possibly containing entities and markup.
 * @return string
 */
function newera_attr_text( $text ) {
	return esc_attr( newera_plain_text( $text ) );
}

/* -----------------------------------------------------------------
 * Internal links
 * ----------------------------------------------------------------- */

/**
 * The URL of one of the theme's landing pages.
 *
 * Looked up by page template first, then by slug. Each of these templates
 * carries a `Template Name:` header, so the About design can be attached to a
 * page living at /about-us/ — which is exactly what happens when an existing
 * page is kept in preference to the imported one. Hard-coding
 * home_url( '/about/' ) in the navigation turned that choice into a site full
 * of 404s.
 *
 * @param string $template Template filename, e.g. 'page-about.php'.
 * @param string $slug     Slug to fall back to when no page uses that template.
 * @return string
 */
function newera_page_url( $template, $slug ) {
	static $cache = array();

	if ( isset( $cache[ $template ] ) ) {
		return $cache[ $template ];
	}

	$pages = get_pages(
		array(
			'meta_key'    => '_wp_page_template',
			'meta_value'  => $template,
			'number'      => 1,
			'post_status' => 'publish',
		)
	);

	if ( ! empty( $pages ) ) {
		$cache[ $template ] = get_permalink( $pages[0] );

		return $cache[ $template ];
	}

	$page = get_page_by_path( $slug );

	$cache[ $template ] = $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );

	return $cache[ $template ];
}

/**
 * URL of the quote page.
 *
 * @return string
 */
function newera_quote_url() {
	return newera_page_url( 'page-quote.php', 'get-quote' );
}

/**
 * URL of the pricing page.
 *
 * @return string
 */
function newera_pricing_url() {
	return newera_page_url( 'page-pricing.php', 'pricing' );
}

/**
 * URL of the about page.
 *
 * @return string
 */
function newera_about_url() {
	return newera_page_url( 'page-about.php', 'about-us' );
}

/**
 * URL of the guarantee page.
 *
 * @return string
 */
function newera_guarantee_url() {
	return newera_page_url( 'page-guarantee.php', 'guarantee' );
}

/**
 * URL of the job photos page.
 *
 * @return string
 */
function newera_photos_url() {
	return newera_page_url( 'page-job-photos.php', 'job-photos' );
}

/**
 * URL of the services listing.
 *
 * Read from the post type rather than assembled by hand, so changing the
 * rewrite slug moves every link on the site with it.
 *
 * @return string
 */
function newera_services_url() {
	$link = get_post_type_archive_link( 'newera_service' );

	return $link ? $link : home_url( '/' . newera_services_base() . '/' );
}

/* -----------------------------------------------------------------
 * Services
 * ----------------------------------------------------------------- */

/**
 * The service catalog.
 *
 * Returns the `newera_service` posts when any exist, so edits made in wp-admin
 * appear everywhere the catalog is used. Falls back to the seeded catalog so
 * the theme renders correctly before the content import has been run.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_get_services() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$posts = get_posts(
		array(
			'post_type'        => 'newera_service',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'suppress_filters' => false,
		)
	);

	if ( empty( $posts ) ) {
		$cache = array_map(
			static function ( $service ) {
				$service['permalink'] = trailingslashit( newera_services_url() ) . $service['slug'] . '/';
				$service['id']        = 0;
				return $service;
			},
			newera_default_services()
		);

		return $cache;
	}

	$cache = array_map(
		static function ( $post ) {
			$highlights = get_post_meta( $post->ID, '_newera_highlights', true );

			if ( is_string( $highlights ) && '' !== $highlights ) {
				$highlights = array_values( array_filter( array_map( 'trim', explode( "\n", $highlights ) ) ) );
			}

			return array(
				'id'          => $post->ID,
				'slug'        => $post->post_name,
				'name'        => $post->post_title,
				'icon'        => get_post_meta( $post->ID, '_newera_icon', true ) ?: 'bi-stars',
				'tagline'     => (string) get_post_meta( $post->ID, '_newera_tagline', true ),
				'summary'     => (string) get_post_meta( $post->ID, '_newera_summary', true ),
				'local_title' => (string) get_post_meta( $post->ID, '_newera_local_title', true ),
				'local_body'  => (string) get_post_meta( $post->ID, '_newera_local_body', true ),
				'highlights'  => is_array( $highlights ) ? $highlights : array(),
				'description' => $post->post_content,
				'permalink'   => get_permalink( $post ),
			);
		},
		$posts
	);

	return $cache;
}

/**
 * A service's local-context copy, with placeholders resolved.
 *
 * The copy is authored with `%JOB_PHOTOS%` rather than a literal URL, so the
 * link survives the Job Photos page being moved or renamed — and so the owner
 * can write one in wp-admin without knowing the permalink.
 *
 * @param array $service A service from newera_get_services().
 * @return string Empty when the service has no local copy.
 */
function newera_service_local_body( $service ) {
	$body = isset( $service['local_body'] ) ? (string) $service['local_body'] : '';

	if ( '' === trim( $body ) ) {
		return '';
	}

	return str_replace( '%JOB_PHOTOS%', esc_url( newera_photos_url() ), $body );
}

/**
 * Find a single service by its slug.
 *
 * @param string $slug Service slug.
 * @return array<string,mixed>|null
 */
function newera_get_service_by_slug( $slug ) {
	foreach ( newera_get_services() as $service ) {
		if ( $service['slug'] === $slug ) {
			return $service;
		}
	}

	return null;
}

/**
 * Service names, for the quote form's picker.
 *
 * @return string[]
 */
function newera_service_names() {
	return wp_list_pluck( newera_get_services(), 'name' );
}

/* -----------------------------------------------------------------
 * Testimonials
 * ----------------------------------------------------------------- */

/**
 * Customer testimonials, newest first.
 *
 * Prefers `newera_testimonial` posts and falls back to the seeded reviews.
 *
 * @return array<int,array<string,mixed>>
 */
function newera_get_testimonials() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$posts = get_posts(
		array(
			'post_type'        => 'newera_testimonial',
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
		)
	);

	if ( empty( $posts ) ) {
		$cache = newera_default_testimonials();
		return $cache;
	}

	$cache = array_map(
		static function ( $post ) {
			$rating = (int) get_post_meta( $post->ID, '_newera_rating', true );

			return array(
				'author' => $post->post_title,
				'source' => (string) get_post_meta( $post->ID, '_newera_source', true ),
				'rating' => $rating > 0 ? min( 5, $rating ) : 5,
				'date'   => get_the_date( 'Y-m-d', $post ),
				// Imported reviews are wrapped in a paragraph block; strip the
				// markup and the newlines it leaves behind.
				'text'   => trim( wp_strip_all_tags( $post->post_content ) ),
			);
		},
		$posts
	);

	return $cache;
}

/**
 * A row of filled stars.
 *
 * @param int $rating Number of stars, 1-5.
 * @return string
 */
function newera_stars( $rating ) {
	$rating = max( 0, min( 5, (int) $rating ) );
	$out    = '';

	for ( $i = 0; $i < $rating; $i++ ) {
		$out .= newera_icon( 'bi-star-fill' );
	}

	return $out;
}

/**
 * The Facebook / Yelp / Angi icon links.
 *
 * Shared by the footer and the home page hero card so the three inline SVGs
 * live in exactly one place.
 *
 * Note the base .social-link rules are written for the dark footer - white
 * glyph on a translucent white border. On a light surface they need a colour
 * override or they render invisible; see .hero-card-social in style.css.
 *
 * @param string $wrapper_class Class for the containing element.
 */
function newera_social_icon_links( $wrapper_class = 'public-footer-social' ) {
	$social = newera_social_links();
	?>
	<div class="<?php echo esc_attr( $wrapper_class ); ?>">
		<?php if ( ! empty( $social['facebook'] ) ) : ?>
			<a class="social-link social-facebook" href="<?php echo esc_url( $social['facebook'] ); ?>"
				target="_blank" rel="noopener noreferrer" aria-label="New Era Steam Cleaning on Facebook">
				<svg viewBox="0 0 320 512" fill="currentColor" aria-hidden="true" focusable="false">
					<path d="M80 299.3V512H196V299.3h86.5l18-97.8H196V166.9c0-51.7 20.3-71.5 72.7-71.5c16.3 0 29.4 .4 37 1.2V7.9C291.4 4 256.4 0 236.2 0C129.3 0 80 50.5 80 159.4v42.1H14v97.8H80z" />
				</svg>
			</a>
		<?php endif; ?>
		<?php if ( ! empty( $social['yelp'] ) ) : ?>
			<a class="social-link social-yelp" href="<?php echo esc_url( $social['yelp'] ); ?>"
				target="_blank" rel="noopener noreferrer" aria-label="New Era Steam Cleaning on Yelp">
				<svg viewBox="0 0 384 512" fill="currentColor" aria-hidden="true" focusable="false">
					<path d="M42.9 240.32l99.62 48.61c19.2 9.4 16.2 37.51-4.5 42.71L30.5 358.45a22.79 22.79 0 0 1-28.21-19.6 197.16 197.16 0 0 1 9-85.32 22.8 22.8 0 0 1 31.61-13.21zm44 239.25a199.45 199.45 0 0 0 79.42 32.11A22.78 22.78 0 0 0 192.94 490l3.9-110.82c.7-21.3-25.5-31.91-39.81-16.1l-74.21 82.4a22.82 22.82 0 0 0 4.09 34.09zm145.34-109.92l58.81 94a22.93 22.93 0 0 0 34 5.5 198.36 198.36 0 0 0 52.71-67.61A23 23 0 0 0 364.17 370l-105.42-34.26c-20.31-6.5-37.81 15.8-26.51 33.91zm148.33-132.23a197.44 197.44 0 0 0-50.41-69.31 22.85 22.85 0 0 0-34 4.4l-62 91.92c-11.9 17.7 4.7 40.61 25.2 34.71L366 268.63a23 23 0 0 0 14.61-31.21zM62.11 30.18a22.86 22.86 0 0 0-9.9 32l104.12 180.44c11.7 20.2 42.61 11.9 42.61-11.4V22.88a22.67 22.67 0 0 0-24.5-22.8 320.37 320.37 0 0 0-112.33 30.1z" />
				</svg>
			</a>
		<?php endif; ?>
		<?php if ( ! empty( $social['angi'] ) ) : ?>
			<a class="social-link social-angi" href="<?php echo esc_url( $social['angi'] ); ?>"
				target="_blank" rel="noopener noreferrer" aria-label="New Era Steam Cleaning on Angi">
				<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
					<path d="m32.464 39.567l5.895.053L32.61 4.5h-8.584l-2.663 18.146c-.214 1.97-1.725 15.785-4.31 15.238c-5.07-3.454.625-9.686 3.088-9.28c9.285-1.3 11.791 2.027 12.324 10.963Z" />
					<path d="m28.466 9.954l-1.94 13.23a8.2 8.2 0 0 1 2.6.726a14 14 0 0 1 1.364.76ZM14.82 42.677a10.48 10.48 0 0 1-4.631-12.436a9.71 9.71 0 0 1 10.997-6.696" />
					<path d="M14.682 42.607c8.203 4.103 10.078-6.858 11.131-14.003" />
				</svg>
			</a>
		<?php endif; ?>
	</div>
	<?php
}

/* -----------------------------------------------------------------
 * Media
 * ----------------------------------------------------------------- */

/**
 * Home page carousel slides.
 *
 * Exactly three slides: the CSS-only carousel in style.css defines the
 * checked-state rules for #carousel-0 through #carousel-2, so adding a fourth
 * slide needs a matching CSS rule.
 *
 * @return array<int,array{image:string,caption:string}>
 */
function newera_carousel_slides() {
	return (array) apply_filters(
		'newera_carousel_slides',
		array(
			array(
				'image'   => newera_asset( 'assets/img/carpet-carousel.jpg' ),
				'width'   => 1200,
				'height'  => 500,
				'caption' => 'Deep steam-cleaned carpets, restored to like-new',
			),
			array(
				'image'   => newera_asset( 'assets/img/tile-floor-carousel.jpg' ),
				'width'   => 1200,
				'height'  => 500,
				'caption' => 'Tile &amp; grout brought back to a brilliant shine',
			),
			array(
				'image'   => newera_asset( 'assets/img/commercial-carousel.jpg' ),
				'width'   => 1200,
				'height'  => 500,
				'caption' => 'Dependable commercial floor care for Houston businesses',
			),
		)
	);
}

/**
 * Job Photos gallery tiles.
 *
 * @return array<int,array{image:string,width:int,height:int,alt:string}>
 */
function newera_gallery_photos() {
	/*
	 * Filenames describe what is in the photo, and the intrinsic size of each
	 * file is recorded alongside it so the markup can carry width and height
	 * without a getimagesize() call per tile on every page load.
	 *
	 * All twenty are resized to 800px on the long edge. The grid renders a tile
	 * at roughly 380px, so 800 still covers a 2x screen with room to spare.
	 *
	 * The order follows the live site's Job Photos page. That page serves 27
	 * files, but seven are the same shot uploaded twice: nw-era-img5 through
	 * nw-era-img8 and new-era-cleaning are 740x343 banner crops of photos
	 * already here, nw-era-img8-1 is byte-identical to nw-era-img8, and
	 * neweracleaning-img15 is a letterboxed copy of the travertine pair. Each
	 * of those appears once, below.
	 *
	 * The text is alt text, not a caption — nothing here is rendered on the
	 * page. It describes what is in the frame, so the descriptions are worth
	 * checking against the image itself if a photo is ever swapped out.
	 */
	$photos = array(
		array( 'upholstery-cleaning-before-after.jpg', 800, 800, 'Sofa cushion before and after upholstery cleaning, soiled at left and clean at right' ),
		array( 'travertine-polishing-restoration.jpg', 800, 521, 'Travertine floor before and after polishing, dull at left and mirror-bright at right' ),
		array( 'pet-stain-odor-treatment.jpg',         800, 600, 'Technician steam cleaning a living room carpet, a cleaned lane trailing behind the wand' ),
		array( 'kitchen-stone-floor-restored.jpg',     800, 800, 'Kitchen travertine floor before and after cleaning' ),
		array( 'cleaning-results-gallery.jpg',         800, 533, 'Terracotta tile floor half cleaned, the cabinet base masked off with paper and tape' ),
		array( 'travertine-floor-cleaned.jpg',         800, 600, 'Cleaned travertine floor beside plantation shutters overlooking a pool' ),
		array( 'ceramic-tile-floor-cleaned.jpg',       800, 600, 'Cleaned grey ceramic tile floor meeting carpet at a doorway' ),
		array( 'tile-floor-before-after.jpg',          800, 800, 'Ceramic tile floor before and after cleaning, the grout brightened' ),
		array( 'commercial-floor-care.jpg',            800, 600, 'Cleaned tile entry steps at a brick commercial building' ),
		array( 'saltillo-tile-restored.jpg',           600, 800, 'Saltillo tile floor cleaned and drying beside a kitchen counter' ),
		array( 'stone-tile-cleaning.jpg',              800, 600, 'Brick-pattern tile floor cleaned to a shine in a game room' ),
		array( 'outdoor-stone-before-after.jpg',       800, 800, 'Outdoor stone walkway before and after cleaning, bordered by lawn' ),
		array( 'tile-floor-wand-cleaning.jpg',         600, 800, 'Tile floor being cleaned with a wand, the finished half meeting carpet' ),
		array( 'pool-deck-stone-before-after.jpg',     800, 800, 'Poolside travertine deck before and after cleaning' ),
		array( 'revived-flooring.jpg',                 800, 800, 'Cleaned travertine patio pavers running along a stone wall' ),
		array( 'bathroom-tile-before-after.jpg',       800, 800, 'Bathroom tile and pink grout before and after cleaning' ),
		array( 'tile-grout-deep-clean.jpg',            800, 600, 'Travertine floor cleaned beside a window wall overlooking a pool' ),
		array( 'stone-patio-half-cleaned.jpg',         600, 800, 'Stone patio mid-clean, cleaned pavers meeting untreated ones' ),
		array( 'new-era-service-van.jpg',              800, 600, 'New Era service van parked in the driveway of a two-story brick home' ),
		array( 'travertine-medallion-floor.jpg',       800, 615, 'Travertine floor with a tile medallion ringing a round glass floor feature' ),
	);

	$tiles = array();

	foreach ( $photos as $photo ) {
		list( $file, $width, $height, $alt ) = $photo;

		$tiles[] = array(
			'image'  => newera_asset( 'assets/img/' . $file ),
			'width'  => $width,
			'height' => $height,
			'alt'    => $alt,
		);
	}

	return (array) apply_filters( 'newera_gallery_photos', $tiles );
}

/**
 * The site logo URL, preferring a custom logo set in the Customizer.
 *
 * @return string
 */
function newera_logo_url() {
	$custom = get_theme_mod( 'custom_logo' );

	if ( $custom ) {
		$src = wp_get_attachment_image_src( $custom, 'full' );

		if ( $src ) {
			return $src[0];
		}
	}

	return newera_asset( 'assets/img/new-era-logo.png' );
}

/*
 * There is no newera_logo_dimensions() any more. It existed for the brand-logo
 * band, which was the only caller that needed an intrinsic size — the header
 * and footer logos are sized by CSS in both dimensions. The band now shows the
 * banner below, so the function had no callers left.
 */

/**
 * The wide brand banner used by the logo band on the front and About pages.
 *
 * Deliberately not newera_logo_url(). That one follows the Customizer's
 * custom_logo so the header, the footer, and the schema all track whatever the
 * owner sets there, and the shipped file behind it is 300px wide — enough for a
 * 46px header lockup and nothing more. The band is full-page furniture, so it
 * takes the company's own 960px artwork instead.
 *
 * @return string
 */
function newera_banner_url() {
	return (string) apply_filters( 'newera_banner_url', newera_asset( 'assets/img/new-era-banner.jpg' ) );
}

/**
 * The banner's intrinsic pixel size.
 *
 * The band's CSS is `max-width:720px` with `height:auto`, so the width and
 * height attributes are what reserve the right box and stop the section
 * shifting as the image arrives. Filtered next to the URL so anything that
 * replaces the banner can declare its own shape at the same time.
 *
 * @return array{0:int,1:int} Width, then height.
 */
function newera_banner_dimensions() {
	// The intrinsic size of assets/img/new-era-banner.jpg.
	return (array) apply_filters( 'newera_banner_dimensions', array( 960, 486 ) );
}

/* -----------------------------------------------------------------
 * Reusable blocks (ported Razor components)
 * ----------------------------------------------------------------- */

/**
 * Interior page hero banner with breadcrumb.
 *
 * Ported from Components/PageBanner.razor. This renders the page's single
 * <h1>, so templates must not output another one.
 *
 * @param array $args {
 *     @type string $title        Headline and final breadcrumb.
 *     @type string $subtitle     Optional supporting line.
 *     @type string $parent_label Optional intermediate breadcrumb label.
 *     @type string $parent_href  URL for the intermediate breadcrumb.
 * }
 */
function newera_page_banner( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'        => '',
			'subtitle'     => '',
			'parent_label' => '',
			'parent_href'  => '',
		)
	);

	/*
	 * Hand the trail to the schema layer as it is built, so the BreadcrumbList
	 * emitted in the footer is the same list rendered below rather than a
	 * second guess at it. See newera_breadcrumb_trail().
	 */
	$trail = array(
		array(
			'label' => 'Home',
			'url'   => home_url( '/' ),
		),
	);

	if ( $args['parent_label'] && $args['parent_href'] ) {
		$trail[] = array(
			'label' => newera_plain_text( $args['parent_label'] ),
			'url'   => $args['parent_href'],
		);
	}

	$trail[] = array(
		'label' => newera_plain_text( $args['title'] ),
		'url'   => '',
	);

	newera_breadcrumb_trail( $trail );
	?>
	<section class="page-banner">
		<div class="page-banner-inner">
			<nav class="breadcrumb" aria-label="Breadcrumb">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a>
				<span aria-hidden="true">/</span>
				<?php if ( $args['parent_label'] && $args['parent_href'] ) : ?>
					<a href="<?php echo esc_url( $args['parent_href'] ); ?>"><?php echo wp_kses_post( $args['parent_label'] ); ?></a>
					<span aria-hidden="true">/</span>
				<?php endif; ?>
				<span><?php echo wp_kses_post( $args['title'] ); ?></span>
			</nav>
			<h1><?php echo wp_kses_post( $args['title'] ); ?></h1>
			<?php if ( $args['subtitle'] ) : ?>
				<p><?php echo wp_kses_post( $args['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Recurring call-to-action band.
 *
 * Ported from Components/CtaBand.razor.
 *
 * @param array $args {
 *     @type string $heading     Main headline.
 *     @type string $subheading  Supporting line.
 *     @type string $quote_href  Where the primary CTA points.
 *     @type string $quote_label Label for the primary CTA.
 * }
 */
function newera_cta_band( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'heading'     => 'Ready for a cleaner space?',
			'subheading'  => 'Get a free, no-obligation quote today &mdash; or call and talk to a real person.',
			'quote_href'  => newera_quote_url(),
			'quote_label' => 'Get a Free Quote',
		)
	);
	?>
	<section class="cta-band">
		<div class="cta-band-inner">
			<div class="cta-band-copy">
				<h2><?php echo wp_kses_post( $args['heading'] ); ?></h2>
				<p><?php echo wp_kses_post( $args['subheading'] ); ?></p>
			</div>
			<div class="cta-band-actions">
				<a class="btn-ghost-lg" href="<?php echo esc_url( $args['quote_href'] ); ?>">
					<?php echo wp_kses_post( $args['quote_label'] ); ?>
					<?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a class="btn-primary-lg" href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>">
					<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo esc_html( newera_phone() ); ?>
				</a>
			</div>
		</div>
	</section>
	<?php
}

/**
 * A single service card for the services grid.
 *
 * @param array $service A service from newera_get_services().
 */
function newera_service_card( $service ) {
	?>
	<article class="service-card">
		<span class="service-icon"><?php echo newera_icon( $service['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h3 class="service-name"><?php echo esc_html( $service['name'] ); ?></h3>
		<p class="service-text"><?php echo wp_kses_post( $service['summary'] ); ?></p>
		<?php
		/*
		 * "Learn more" on its own is the non-descriptive anchor text Google's
		 * starter guide calls out, and it was identical on all eight cards. The
		 * service name is appended out of sight rather than shown, because the
		 * card design depends on the link being two words wide — and a screen
		 * reader listing links gets the same benefit.
		 */
		?>
		<a class="service-link" href="<?php echo esc_url( $service['permalink'] ); ?>">
			Learn more<span class="screen-reader-text"> about <?php echo esc_html( newera_plain_text( $service['name'] ) ); ?></span>
			<?php echo newera_icon( 'bi-arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	</article>
	<?php
}

/**
 * The contact details shown beside the quote form.
 *
 * The service-area line used to be behind a $with_area flag that only the quote
 * page passed. Every quote block shows the same details now, so there is no
 * flag and no way for the three to disagree.
 */
function newera_quote_contact_list() {
	?>
	<ul class="quote-contact">
		<li>
			<?php echo newera_icon( 'bi-telephone-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<a href="<?php echo esc_url( newera_phone( 'tel' ) ); ?>"><?php echo esc_html( newera_phone() ); ?></a>
		</li>
		<li>
			<?php echo newera_icon( 'bi-clock-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo wp_kses_post( newera_hours() ); ?>
		</li>
		<li>
			<?php echo newera_icon( 'bi-geo-alt-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			Serving Harris &amp; Fort Bend County
		</li>
		<li>
			<?php echo newera_icon( 'bi-flower1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			Ask about organic, green cleaning
		</li>
	</ul>
	<?php
}

/**
 * The whole quote-request block: intro copy, contact details, trust badges and
 * the form itself.
 *
 * The home page, the quote page and the eight service pages each used to carry
 * their own copy of this section, with a different heading, a differently worded
 * lead, and the area line and trust badges on one of the three. They render from
 * here now, so the only things that vary are the anchor id, the form name, and
 * which service the picker opens on.
 *
 * @param array $args {
 *     @type string $id        Optional id for the section, used as a scroll target.
 *     @type string $form_name Unique form name; namespaces the field ids.
 *     @type string $preselect Service to preselect in the picker.
 * }
 */
function newera_quote_section( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'id'        => '',
			'form_name' => 'quote-request',
			'preselect' => '',
		)
	);
	?>
	<section class="section quote-section"<?php echo $args['id'] ? ' id="' . esc_attr( $args['id'] ) . '"' : ''; ?>>
		<div class="section-inner quote-grid">
			<div class="quote-intro">
				<span class="section-eyebrow section-eyebrow-light">Get Started</span>
				<h2 class="quote-title">Let's Make Your Space Shine</h2>
				<p class="quote-lead">
					Tell us a little about the job and we'll get back to you with a free, no-obligation
					quote. Prefer to talk? Call us directly &mdash; we're happy to help.
				</p>
				<?php newera_quote_contact_list(); ?>
				<div class="quote-trust">
					<span><?php echo newera_icon( 'bi-shield-check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Guaranteed, Bonded &amp; Insured</span>
					<span><?php echo newera_icon( 'bi-people-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> Family-Owned Since <?php echo esc_html( newera_founded_year() ); ?></span>
				</div>
			</div>

			<?php
			newera_quote_form(
				array(
					'form_name' => $args['form_name'],
					'preselect' => $args['preselect'],
				)
			);
			?>
		</div>
	</section>
	<?php
}

/**
 * The public quote request form.
 *
 * Ported from Components/QuoteForm.razor. Posts to admin-post.php, where
 * newera_handle_quote_submission() validates it, stores a `newera_quote` post,
 * and emails the business. On success the visitor is redirected back here with
 * `newera_quote=sent`, which swaps the form for a thank-you panel.
 *
 * @param array $args {
 *     @type string $title      Heading above the fields.
 *     @type string $form_name  Unique name, used to namespace the field ids.
 *     @type string $preselect  Service to preselect in the picker.
 * }
 */
function newera_quote_form( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'title'     => 'Request Your Free Quote',
			'form_name' => 'quote-request',
			'preselect' => '',
		)
	);

	$id_prefix = sanitize_html_class( $args['form_name'] );
	$state     = newera_quote_form_state();
	$sent      = 'sent' === $state['status'] && $state['form'] === $args['form_name'];
	?>
	<div class="quote-form-wrap" id="<?php echo esc_attr( $id_prefix ); ?>">
		<?php if ( $sent ) : ?>
			<div class="quote-success" role="status">
				<?php echo newera_icon( 'bi-check-circle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<h3>Thank you<?php echo $state['name'] ? ', ' . esc_html( $state['name'] ) : ''; ?>!</h3>
				<p>Your request has been received. A member of our team will reach out shortly to confirm your free quote.</p>
				<a class="btn-primary-lg" href="<?php echo esc_url( remove_query_arg( array( 'newera_quote', 'newera_form', 'newera_name', 'newera_errors' ) ) ); ?>">Submit another request</a>
			</div>
		<?php else : ?>
			<?php newera_quote_form_errors( $args['form_name'] ); ?>

			<form class="quote-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'newera_quote', 'newera_quote_nonce' ); ?>
				<input type="hidden" name="action" value="newera_quote" />
				<input type="hidden" name="newera_form" value="<?php echo esc_attr( $args['form_name'] ); ?>" />
				<input type="hidden" name="newera_redirect" value="<?php echo esc_url( newera_current_url() ); ?>" />

				<h3 class="quote-form-title"><?php echo wp_kses_post( $args['title'] ); ?></h3>

				<div class="field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>-name">Name <span class="req">*</span></label>
					<input type="text" id="<?php echo esc_attr( $id_prefix ); ?>-name" name="newera_name"
						class="input" placeholder="Your full name" required
						value="<?php echo esc_attr( $state['old']['name'] ); ?>" />
				</div>

				<div class="field-row">
					<div class="field">
						<label for="<?php echo esc_attr( $id_prefix ); ?>-phone">Phone <span class="req">*</span></label>
						<input type="tel" id="<?php echo esc_attr( $id_prefix ); ?>-phone" name="newera_phone"
							class="input" placeholder="(713) 555-0123" required
							value="<?php echo esc_attr( $state['old']['phone'] ); ?>" />
					</div>
					<div class="field">
						<label for="<?php echo esc_attr( $id_prefix ); ?>-email">Email <span class="req">*</span></label>
						<input type="email" id="<?php echo esc_attr( $id_prefix ); ?>-email" name="newera_email"
							class="input" placeholder="you@example.com" required
							value="<?php echo esc_attr( $state['old']['email'] ); ?>" />
					</div>
				</div>

				<div class="field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>-service">Service Needed <span class="req">*</span></label>
					<select id="<?php echo esc_attr( $id_prefix ); ?>-service" name="newera_service" class="input" required>
						<option value="">Select a service&hellip;</option>
						<?php
						$selected = $state['old']['service'] ? $state['old']['service'] : $args['preselect'];
						foreach ( newera_service_names() as $name ) :
							$plain = html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
							?>
							<option value="<?php echo esc_attr( $plain ); ?>" <?php selected( $selected, $plain ); ?>>
								<?php echo esc_html( $plain ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="field">
					<label for="<?php echo esc_attr( $id_prefix ); ?>-message">How can we help? <span class="req">*</span></label>
					<textarea id="<?php echo esc_attr( $id_prefix ); ?>-message" name="newera_message"
						class="input textarea" rows="4" required
						placeholder="Tell us about the space, square footage, timing, pets, etc."><?php echo esc_textarea( $state['old']['message'] ); ?></textarea>
				</div>

				<div class="newera-hp" aria-hidden="true">
					<label for="<?php echo esc_attr( $id_prefix ); ?>-website">Leave this field empty</label>
					<input type="text" id="<?php echo esc_attr( $id_prefix ); ?>-website" name="newera_website" tabindex="-1" autocomplete="off" />
				</div>

				<button class="btn-primary-lg btn-block" type="submit">
					Send My Request
					<?php echo newera_icon( 'bi-send-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</button>
				<p class="quote-form-note">We'll never share your information. No obligation.</p>
			</form>
		<?php endif; ?>
	</div>
	<?php
}
