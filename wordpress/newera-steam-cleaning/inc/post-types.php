<?php
/**
 * Custom post types and their editing screens.
 *
 * Three types back the site: services (the catalog ported from ServiceCatalog.cs),
 * testimonials (the Yelp and Angi reviews), and quote requests (submissions from
 * the public form).
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The base path for the service catalogue.
 *
 * Deliberately the long path neweracleaning.com has served since 2014, not the
 * shorter `/services/` the Blazor rebuild used. Keeping it means every existing
 * service URL survives the migration unchanged — eight pages that carry real
 * ranking history — which is worth more than a tidier address bar.
 *
 * Everything that builds a service URL reads this, so changing it here moves
 * the whole catalogue. Change it only with a redirect plan.
 *
 * @return string
 */
function newera_services_base() {
	return (string) apply_filters( 'newera_services_base', 'carpet-tile-grout-steam-cleaning-services' );
}

/**
 * Register the theme's post types.
 *
 * The service rewrite reproduces the live site's URLs exactly: the base path
 * lists the catalogue and {base}/{slug}/ is a detail page. Because the archive
 * owns that path, no WordPress page may sit on it — see the admin notice in
 * functions.php, which checks for exactly that.
 */
function newera_register_post_types() {
	$services_base = newera_services_base();

	register_post_type(
		'newera_service',
		array(
			'labels'        => array(
				'name'               => 'Services',
				'singular_name'      => 'Service',
				'add_new_item'       => 'Add New Service',
				'edit_item'          => 'Edit Service',
				'new_item'           => 'New Service',
				'view_item'          => 'View Service',
				'search_items'       => 'Search Services',
				'not_found'          => 'No services found',
				'menu_name'          => 'Services',
			),
			'public'        => true,
			'has_archive'   => $services_base,
			'rewrite'       => array(
				'slug'       => $services_base,
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-sparkles',
			'menu_position' => 20,
			'supports'      => array( 'title', 'editor', 'page-attributes', 'thumbnail', 'excerpt' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'newera_testimonial',
		array(
			'labels'        => array(
				'name'          => 'Testimonials',
				'singular_name' => 'Testimonial',
				'add_new_item'  => 'Add New Testimonial',
				'edit_item'     => 'Edit Testimonial',
				'menu_name'     => 'Testimonials',
			),
			'public'        => false,
			'show_ui'       => true,
			'menu_icon'     => 'dashicons-star-filled',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor' ),
			'show_in_rest'  => true,
		)
	);

	register_post_type(
		'newera_quote',
		array(
			'labels'          => array(
				'name'          => 'Quote Requests',
				'singular_name' => 'Quote Request',
				'edit_item'     => 'Quote Request',
				'menu_name'     => 'Quote Requests',
				'not_found'     => 'No quote requests yet',
			),
			'public'          => false,
			'show_ui'         => true,
			'menu_icon'       => 'dashicons-email-alt',
			'menu_position'   => 22,
			'supports'        => array( 'title', 'editor' ),
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'delete_with_user' => false,
		)
	);
}
add_action( 'init', 'newera_register_post_types' );

/**
 * Register the meta fields so they survive the REST API and the block editor.
 */
function newera_register_meta() {
	$service_fields = array(
		'_newera_tagline'     => 'string',
		'_newera_summary'     => 'string',
		'_newera_icon'        => 'string',
		'_newera_highlights'  => 'string',
		'_newera_local_title' => 'string',
		'_newera_local_body'  => 'string',
	);

	foreach ( $service_fields as $key => $type ) {
		register_post_meta(
			'newera_service',
			$key,
			array(
				'type'              => $type,
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'wp_kses_post',
				'auth_callback'     => static function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	register_post_meta(
		'newera_testimonial',
		'_newera_source',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	register_post_meta(
		'newera_testimonial',
		'_newera_rating',
		array(
			'type'              => 'integer',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static function () {
				return current_user_can( 'edit_posts' );
			},
		)
	);
}
add_action( 'init', 'newera_register_meta' );

/**
 * Add the editing panels for service and testimonial details.
 */
function newera_add_meta_boxes() {
	add_meta_box(
		'newera-service-details',
		'Service Details',
		'newera_render_service_meta_box',
		'newera_service',
		'normal',
		'high'
	);

	add_meta_box(
		'newera-testimonial-details',
		'Review Details',
		'newera_render_testimonial_meta_box',
		'newera_testimonial',
		'side',
		'default'
	);

	add_meta_box(
		'newera-quote-details',
		'Contact Details',
		'newera_render_quote_meta_box',
		'newera_quote',
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'newera_add_meta_boxes' );

/**
 * Service detail fields.
 *
 * @param WP_Post $post Post being edited.
 */
function newera_render_service_meta_box( $post ) {
	wp_nonce_field( 'newera_save_meta', 'newera_meta_nonce' );

	$tagline     = get_post_meta( $post->ID, '_newera_tagline', true );
	$summary     = get_post_meta( $post->ID, '_newera_summary', true );
	$icon        = get_post_meta( $post->ID, '_newera_icon', true );
	$highlights  = get_post_meta( $post->ID, '_newera_highlights', true );
	$local_title = get_post_meta( $post->ID, '_newera_local_title', true );
	$local_body  = get_post_meta( $post->ID, '_newera_local_body', true );

	if ( is_array( $highlights ) ) {
		$highlights = implode( "\n", $highlights );
	}
	?>
	<p>
		<label for="newera-tagline"><strong>Tagline</strong></label><br />
		<input type="text" id="newera-tagline" name="newera_tagline" class="widefat"
			value="<?php echo esc_attr( $tagline ); ?>" />
		<span class="description">Short line under the page title, e.g. "Recapture your carpet's youth."</span>
	</p>
	<p>
		<label for="newera-summary"><strong>Summary</strong></label><br />
		<textarea id="newera-summary" name="newera_summary" class="widefat" rows="2"><?php echo esc_textarea( $summary ); ?></textarea>
		<span class="description">One or two sentences shown on the service cards.</span>
	</p>
	<p>
		<label for="newera-icon"><strong>Icon</strong></label><br />
		<input type="text" id="newera-icon" name="newera_icon" class="regular-text"
			value="<?php echo esc_attr( $icon ); ?>" placeholder="bi-stars" />
		<span class="description">A <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">Bootstrap Icons</a> name, e.g. <code>bi-droplet-half</code>.</span>
	</p>
	<p>
		<label for="newera-highlights"><strong>What's included</strong></label><br />
		<textarea id="newera-highlights" name="newera_highlights" class="widefat" rows="6"><?php echo esc_textarea( $highlights ); ?></textarea>
		<span class="description">One bullet per line.</span>
	</p>
	<hr />
	<p>
		<label for="newera-local-title"><strong>Local section heading</strong></label><br />
		<input type="text" id="newera-local-title" name="newera_local_title" class="widefat"
			value="<?php echo esc_attr( $local_title ); ?>" />
		<span class="description">
			Describe what the section actually says, e.g. "Where we work, and when". Avoid writing
			the same heading with a different city on every service &mdash; that is the pattern
			search engines treat as doorway content.
		</span>
	</p>
	<p>
		<label for="newera-local-body"><strong>Local section copy</strong></label><br />
		<textarea id="newera-local-body" name="newera_local_body" class="widefat" rows="6"><?php echo esc_textarea( $local_body ); ?></textarea>
		<span class="description">
			Where this service is offered and anything specific about how it is done here. Basic
			HTML links are allowed. Use <code>%JOB_PHOTOS%</code> to link to the Job Photos page.
			Leave blank to omit the section entirely.
		</span>
	</p>
	<?php
}

/**
 * Testimonial detail fields.
 *
 * @param WP_Post $post Post being edited.
 */
function newera_render_testimonial_meta_box( $post ) {
	wp_nonce_field( 'newera_save_meta', 'newera_meta_nonce' );

	$source = get_post_meta( $post->ID, '_newera_source', true );
	$rating = (int) get_post_meta( $post->ID, '_newera_rating', true );
	$rating = $rating > 0 ? $rating : 5;
	?>
	<p>
		<label for="newera-source"><strong>Source</strong></label><br />
		<input type="text" id="newera-source" name="newera_source" class="widefat"
			value="<?php echo esc_attr( $source ); ?>" placeholder="Yelp" />
	</p>
	<p>
		<label for="newera-rating"><strong>Rating</strong></label><br />
		<select id="newera-rating" name="newera_rating">
			<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
				<option value="<?php echo esc_attr( $i ); ?>" <?php selected( $rating, $i ); ?>>
					<?php echo esc_html( $i ); ?> star<?php echo 1 === $i ? '' : 's'; ?>
				</option>
			<?php endfor; ?>
		</select>
	</p>
	<p class="description">The reviewer's name goes in the title field, and the review text in the editor.</p>
	<?php
}

/**
 * Read-only contact details for a stored quote request.
 *
 * @param WP_Post $post Post being viewed.
 */
function newera_render_quote_meta_box( $post ) {
	$name    = get_post_meta( $post->ID, '_newera_quote_name', true );
	$email   = get_post_meta( $post->ID, '_newera_quote_email', true );
	$phone   = get_post_meta( $post->ID, '_newera_quote_phone', true );
	$service = get_post_meta( $post->ID, '_newera_quote_service', true );
	?>
	<p><strong>Name</strong><br /><?php echo esc_html( $name ); ?></p>
	<p><strong>Phone</strong><br />
		<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', (string) $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
	</p>
	<p><strong>Email</strong><br />
		<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
	</p>
	<p><strong>Service</strong><br /><?php echo esc_html( $service ); ?></p>
	<?php
}

/**
 * Persist the meta box fields.
 *
 * @param int $post_id Post being saved.
 */
function newera_save_meta( $post_id ) {
	if ( ! isset( $_POST['newera_meta_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['newera_meta_nonce'] ) ), 'newera_save_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$text_fields = array(
		'newera_tagline'     => '_newera_tagline',
		'newera_summary'     => '_newera_summary',
		'newera_icon'        => '_newera_icon',
		'newera_highlights'  => '_newera_highlights',
		'newera_local_title' => '_newera_local_title',
		'newera_source'      => '_newera_source',
	);

	foreach ( $text_fields as $input => $meta_key ) {
		if ( isset( $_POST[ $input ] ) ) {
			update_post_meta(
				$post_id,
				$meta_key,
				sanitize_textarea_field( wp_unslash( $_POST[ $input ] ) )
			);
		}
	}

	/*
	 * Kept out of the loop above: this field is allowed to carry a link, and
	 * sanitize_textarea_field() strips tags, so running it through the same
	 * branch would silently delete the markup every time the post was saved.
	 */
	if ( isset( $_POST['newera_local_body'] ) ) {
		update_post_meta(
			$post_id,
			'_newera_local_body',
			wp_kses_post( wp_unslash( $_POST['newera_local_body'] ) )
		);
	}

	if ( isset( $_POST['newera_rating'] ) ) {
		$rating = absint( wp_unslash( $_POST['newera_rating'] ) );
		update_post_meta( $post_id, '_newera_rating', max( 1, min( 5, $rating ) ) );
	}
}
add_action( 'save_post', 'newera_save_meta' );
