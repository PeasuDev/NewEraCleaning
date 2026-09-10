<?php
/**
 * Theme settings that belong to the business rather than to the code.
 *
 * Most company facts are constants with filters over them, which suits values
 * that change once a decade. The Google Business Profile URL is different: it
 * is a real thing the owner holds, it is needed for local search, and asking
 * someone to edit PHP to set it is why it stays unset. So it gets a field.
 *
 * The field lives on Settings > General rather than behind a theme options
 * page. One setting does not justify a page of its own, and General is where
 * the site title and tagline it sits beside already are.
 *
 * The same screen also carries a read-only readout of where quote notifications
 * are delivered. That is not a setting and cannot be edited here — it is here
 * because the administration email field is directly above it, and the one thing
 * this site's mail configuration can do silently is ignore that field.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option name holding the Google Business Profile URL.
 */
const NEWERA_GBP_OPTION = 'newera_google_business_profile';

/**
 * Register the setting and its field.
 *
 * @return void
 */
function newera_register_settings() {
	register_setting(
		'general',
		NEWERA_GBP_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'newera_sanitize_profile_url',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);

	add_settings_field(
		NEWERA_GBP_OPTION,
		'Google Business Profile',
		'newera_render_profile_field',
		'general',
		'default',
		array( 'label_for' => NEWERA_GBP_OPTION )
	);

	add_settings_field(
		'newera_quote_recipient_status',
		'Quote notifications',
		'newera_render_quote_recipient_status',
		'general',
		'default'
	);
}
add_action( 'admin_init', 'newera_register_settings' );

/**
 * Keep only a well-formed http(s) URL.
 *
 * An empty value is valid and means "not set yet"; the SEO checklist will keep
 * asking for it rather than the field rejecting a blank.
 *
 * @param mixed $value Submitted value.
 * @return string
 */
function newera_sanitize_profile_url( $value ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	$value = esc_url_raw( $value, array( 'http', 'https' ) );

	return $value ? $value : '';
}

/**
 * Render the input.
 *
 * @return void
 */
function newera_render_profile_field() {
	$value = get_option( NEWERA_GBP_OPTION, '' );

	printf(
		'<input type="url" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="https://maps.app.goo.gl/…" />',
		esc_attr( NEWERA_GBP_OPTION ),
		esc_attr( $value )
	);

	echo '<p class="description">'
		. 'The share link for the business&rsquo;s Google listing. It is published in the site&rsquo;s '
		. '<code>sameAs</code> structured data, which is how Google ties this site and that listing '
		. 'to the same company. Leave blank if you do not have it yet.'
		. '</p>';
}

/**
 * Where a quote request notification would be delivered right now.
 *
 * Resolved by running the same filter the send path runs
 * (newera_send_quote_notification()) rather than by re-reading the constant, so
 * a plugin, code snippet or mu-plugin hooking newera_quote_recipient is
 * reflected here too. A readout that only knew about wp-config.php would be
 * accurate until the day something else took over, which is the day it matters.
 *
 * @return string Comma-separated address list; '' if nothing resolves.
 */
function newera_effective_quote_recipient() {
	return (string) apply_filters( 'newera_quote_recipient', get_option( 'admin_email' ) );
}

/**
 * Why the effective recipient is what it is, phrased for whoever is reading.
 *
 * @param string $recipient Result of newera_effective_quote_recipient().
 * @return string HTML fragment.
 */
function newera_quote_recipient_source( $recipient ) {
	$configured = newera_configured_quote_recipient();

	/*
	 * Defined but unusable. newera_quote_recipient_override() drops anything
	 * is_email() rejects and returns the admin address rather than sending
	 * nowhere, so a typo in wp-config.php reverts delivery in perfect silence.
	 * Without this branch the readout would report the fallback as if it had
	 * been chosen.
	 */
	if ( defined( 'NEWERA_QUOTE_RECIPIENT' ) && NEWERA_QUOTE_RECIPIENT && '' === $configured ) {
		return '<strong><code>NEWERA_QUOTE_RECIPIENT</code> is defined but was ignored</strong> &mdash; '
			. 'nothing in it is a valid email address, so delivery has fallen back to the '
			. 'administration email above. Check <code>wp-config.php</code> for a typo.';
	}

	if ( '' !== $configured && $recipient === $configured ) {
		return 'Pinned by <code>NEWERA_QUOTE_RECIPIENT</code> in <code>wp-config.php</code>, which '
			. '<strong>replaces</strong> the administration email above rather than adding to it. '
			. 'Editing that field will not move quote notifications.';
	}

	if ( $recipient === (string) get_option( 'admin_email' ) ) {
		return 'Using the administration email above. Change that field and quote notifications '
			. 'follow it.';
	}

	return 'Set by a filter on <code>newera_quote_recipient</code> &mdash; a plugin, code snippet '
		. 'or mu-plugin. Not this screen, and not <code>wp-config.php</code>.';
}

/**
 * Read-only readout of the quote notification recipient.
 *
 * There is deliberately no field here. The recipient is not editable from this
 * screen; the point is that it is *knowable* from it. Pinning the address in
 * wp-config.php buys protection against someone changing the administration
 * email by accident, at the cost of a destination wp-admin cannot see — and the
 * person most likely to be misled is the one on this screen, editing the field
 * directly above and reasonably assuming leads follow it.
 *
 * @return void
 */
function newera_render_quote_recipient_status() {
	$recipient = newera_effective_quote_recipient();

	if ( '' === $recipient ) {
		echo '<p><strong>Nowhere &mdash; no recipient resolves.</strong> Quote requests are not '
			. 'being emailed to anyone.</p>';
	} else {
		$addresses = array_filter( array_map( 'trim', explode( ',', $recipient ) ) );

		echo '<p style="margin-top:0">';
		foreach ( $addresses as $i => $address ) {
			echo $i > 0 ? ', ' : '';
			printf( '<strong><code>%s</code></strong>', esc_html( $address ) );
		}
		echo '</p>';

		echo '<p class="description">' . wp_kses_post( newera_quote_recipient_source( $recipient ) ) . '</p>';
	}

	printf(
		'<p class="description">Every submission is stored under <a href="%s">Quote Requests</a> '
			. 'before any mail is attempted, so a delivery failure loses the notification, never '
			. 'the lead.</p>',
		esc_url( admin_url( 'edit.php?post_type=newera_quote' ) )
	);
}
