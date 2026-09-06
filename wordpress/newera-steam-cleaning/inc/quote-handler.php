<?php
/**
 * Quote request handling.
 *
 * Replaces the in-memory PlaceholderDataService the Blazor form posted into.
 * A submission is validated, stored as a private `newera_quote` post, and
 * emailed to the business. The visitor is redirected back to the page they
 * submitted from, so a refresh cannot resubmit the form.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * The URL of the page currently being rendered.
 *
 * Used as the form's return address so the same form on the home page, the
 * quote page, and each service page all redirect back to themselves.
 *
 * Built from the queried object rather than REQUEST_URI: on an install in a
 * subdirectory, home_url() already carries that prefix and REQUEST_URI carries
 * it again, which produces /blog/blog/quote/.
 *
 * @return string
 */
function newera_current_url() {
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_singular() ) {
		$url = get_permalink();
	} elseif ( is_post_type_archive( 'newera_service' ) ) {
		$url = get_post_type_archive_link( 'newera_service' );
	} else {
		global $wp;
		$path = isset( $wp->request ) ? $wp->request : '';
		$url  = home_url( user_trailingslashit( $path ) );
	}

	if ( ! $url ) {
		$url = home_url( '/' );
	}

	return remove_query_arg( array( 'newera_quote', 'newera_form', 'newera_token' ), $url );
}

/**
 * Form state carried across the post-submit redirect.
 *
 * Errors and previously entered values are stashed in a short-lived transient
 * rather than the query string, which keeps the URL clean and avoids echoing
 * user input straight back out of the address bar.
 *
 * @return array{status:string,form:string,name:string,errors:string[],old:array<string,string>}
 */
function newera_quote_form_state() {
	static $state = null;

	if ( null !== $state ) {
		return $state;
	}

	$state = array(
		'status' => '',
		'form'   => '',
		'name'   => '',
		'errors' => array(),
		'old'    => array(
			'name'    => '',
			'phone'   => '',
			'email'   => '',
			'service' => '',
			'message' => '',
		),
	);

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display state.
	$status = isset( $_GET['newera_quote'] ) ? sanitize_key( wp_unslash( $_GET['newera_quote'] ) ) : '';

	if ( ! in_array( $status, array( 'sent', 'error' ), true ) ) {
		return $state;
	}

	$state['status'] = $status;
	$state['form']   = isset( $_GET['newera_form'] ) ? sanitize_text_field( wp_unslash( $_GET['newera_form'] ) ) : '';
	$token           = isset( $_GET['newera_token'] ) ? sanitize_key( wp_unslash( $_GET['newera_token'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$stash = $token ? get_transient( 'newera_quote_' . $token ) : false;

	if ( is_array( $stash ) ) {
		$state['name']   = isset( $stash['name'] ) ? $stash['name'] : '';
		$state['errors'] = isset( $stash['errors'] ) ? (array) $stash['errors'] : array();

		if ( isset( $stash['old'] ) && is_array( $stash['old'] ) ) {
			$state['old'] = array_merge( $state['old'], $stash['old'] );
		}
	}

	return $state;
}

/**
 * Render validation errors above the form.
 *
 * @param string $form_name The form asking; errors only show on the form that produced them.
 */
function newera_quote_form_errors( $form_name ) {
	$state = newera_quote_form_state();

	if ( 'error' !== $state['status'] || $state['form'] !== $form_name || empty( $state['errors'] ) ) {
		return;
	}
	?>
	<div class="newera-notice newera-notice-error" role="alert">
		<?php echo newera_icon( 'bi-exclamation-triangle-fill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div>
			<strong>We couldn't send your request.</strong>
			<ul>
				<?php foreach ( $state['errors'] as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php
}

/**
 * Validate and process a quote submission.
 *
 * Hooked to both the logged-in and logged-out admin-post actions.
 */
function newera_handle_quote_submission() {
	$referer  = wp_get_referer();
	$redirect = isset( $_POST['newera_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['newera_redirect'] ) ) : '';

	// Only ever redirect back into this site.
	$redirect = wp_validate_redirect( $redirect, wp_validate_redirect( $referer, home_url( '/' ) ) );

	$form_name = isset( $_POST['newera_form'] ) ? sanitize_text_field( wp_unslash( $_POST['newera_form'] ) ) : 'quote-request';

	if ( ! isset( $_POST['newera_quote_nonce'] ) ||
		! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['newera_quote_nonce'] ) ), 'newera_quote' ) ) {
		newera_quote_redirect( $redirect, $form_name, 'error', array( 'errors' => array( 'Your session expired. Please try again.' ) ) );
	}

	// Honeypot: a human never sees this field, so anything in it is a bot.
	// Report success so the bot has nothing to learn, but store nothing.
	$honeypot = isset( $_POST['newera_website'] ) ? trim( wp_unslash( $_POST['newera_website'] ) ) : '';

	if ( '' !== $honeypot ) {
		newera_quote_redirect( $redirect, $form_name, 'sent', array( 'name' => '' ) );
	}

	$fields = array(
		'name'    => isset( $_POST['newera_name'] ) ? sanitize_text_field( wp_unslash( $_POST['newera_name'] ) ) : '',
		'phone'   => isset( $_POST['newera_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['newera_phone'] ) ) : '',
		'email'   => isset( $_POST['newera_email'] ) ? sanitize_email( wp_unslash( $_POST['newera_email'] ) ) : '',
		'service' => isset( $_POST['newera_service'] ) ? sanitize_text_field( wp_unslash( $_POST['newera_service'] ) ) : '',
		'message' => isset( $_POST['newera_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['newera_message'] ) ) : '',
	);

	$errors = array();

	if ( '' === $fields['name'] ) {
		$errors[] = 'Please enter your name.';
	}

	if ( '' === $fields['phone'] ) {
		$errors[] = 'Please enter a phone number.';
	} elseif ( preg_match_all( '/\d/', $fields['phone'] ) < 10 ) {
		$errors[] = 'Please enter a valid phone number.';
	}

	if ( '' === $fields['email'] ) {
		$errors[] = 'Please enter your email.';
	} elseif ( ! is_email( $fields['email'] ) ) {
		$errors[] = 'Please enter a valid email address.';
	}

	// Compare against the decoded catalog names, which is what the picker submits.
	$valid_services = array_map(
		static function ( $name ) {
			return html_entity_decode( $name, ENT_QUOTES, 'UTF-8' );
		},
		newera_service_names()
	);

	if ( '' === $fields['service'] ) {
		$errors[] = 'Please choose a service.';
	} elseif ( ! in_array( $fields['service'], $valid_services, true ) ) {
		$errors[] = 'Please choose a service from the list.';
	}

	if ( '' === $fields['message'] ) {
		$errors[] = 'Please tell us a little about the job.';
	}

	if ( $errors ) {
		newera_quote_redirect(
			$redirect,
			$form_name,
			'error',
			array(
				'errors' => $errors,
				'old'    => $fields,
			)
		);
	}

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'newera_quote',
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — %s', $fields['name'], $fields['service'] ),
			'post_content' => $fields['message'],
		),
		true
	);

	if ( ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_newera_quote_name', $fields['name'] );
		update_post_meta( $post_id, '_newera_quote_email', $fields['email'] );
		update_post_meta( $post_id, '_newera_quote_phone', $fields['phone'] );
		update_post_meta( $post_id, '_newera_quote_service', $fields['service'] );
		update_post_meta( $post_id, '_newera_quote_status', 'new' );

		newera_send_quote_notification( $fields, $post_id );
	}

	newera_quote_redirect( $redirect, $form_name, 'sent', array( 'name' => $fields['name'] ) );
}
add_action( 'admin_post_nopriv_newera_quote', 'newera_handle_quote_submission' );
add_action( 'admin_post_newera_quote', 'newera_handle_quote_submission' );

/**
 * Email the business about a new quote request.
 *
 * Reply-To is set to the customer so the owner can answer straight from the
 * notification. The From address stays on-domain so the message is not
 * rejected by SPF/DMARC.
 *
 * @param array $fields  Sanitized submission.
 * @param int   $post_id The stored quote post.
 */
function newera_send_quote_notification( $fields, $post_id ) {
	$to = apply_filters( 'newera_quote_recipient', get_option( 'admin_email' ) );

	if ( ! $to ) {
		return;
	}

	$subject = sprintf(
		'[%s] New quote request from %s',
		wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		$fields['name']
	);

	$lines = array(
		'A new quote request came in through the website.',
		'',
		'Name:    ' . $fields['name'],
		'Phone:   ' . $fields['phone'],
		'Email:   ' . $fields['email'],
		'Service: ' . $fields['service'],
		'',
		'Message:',
		$fields['message'],
		'',
		'View in admin: ' . get_edit_post_link( $post_id, '' ),
	);

	wp_mail(
		$to,
		$subject,
		implode( "\n", $lines ),
		array( 'Reply-To: ' . $fields['name'] . ' <' . $fields['email'] . '>' )
	);
}

/**
 * Stash state in a transient and redirect back to the form.
 *
 * This function ends the request.
 *
 * @param string $redirect  Validated return URL.
 * @param string $form_name Which form on the page submitted.
 * @param string $status    'sent' or 'error'.
 * @param array  $payload   Data for the next page load.
 */
function newera_quote_redirect( $redirect, $form_name, $status, $payload = array() ) {
	$token = wp_generate_password( 12, false, false );
	set_transient( 'newera_quote_' . $token, $payload, 5 * MINUTE_IN_SECONDS );

	$url = add_query_arg(
		array(
			'newera_quote' => $status,
			'newera_form'  => rawurlencode( $form_name ),
			'newera_token' => $token,
		),
		$redirect
	);

	wp_safe_redirect( $url . '#' . sanitize_html_class( $form_name ) );
	exit;
}

/* -----------------------------------------------------------------
 * wp-admin listing for stored quote requests
 * ----------------------------------------------------------------- */

/**
 * Columns for the Quote Requests list table.
 *
 * @param array $columns Default columns.
 * @return array
 */
function newera_quote_columns( $columns ) {
	return array(
		'cb'              => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'           => 'Request',
		'newera_phone'    => 'Phone',
		'newera_email'    => 'Email',
		'newera_service'  => 'Service',
		'date'            => 'Received',
	);
}
add_filter( 'manage_newera_quote_posts_columns', 'newera_quote_columns' );

/**
 * Render the custom Quote Requests columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post being rendered.
 */
function newera_quote_column_content( $column, $post_id ) {
	if ( 'newera_phone' === $column ) {
		$phone = get_post_meta( $post_id, '_newera_quote_phone', true );
		if ( $phone ) {
			echo '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a>';
		}
	} elseif ( 'newera_email' === $column ) {
		$email = get_post_meta( $post_id, '_newera_quote_email', true );
		if ( $email ) {
			echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
	} elseif ( 'newera_service' === $column ) {
		echo esc_html( get_post_meta( $post_id, '_newera_quote_service', true ) );
	}
}
add_action( 'manage_newera_quote_posts_custom_column', 'newera_quote_column_content', 10, 2 );
