<?php
/**
 * Outbound mail configuration.
 *
 * Two separate concerns, both driven by constants defined in wp-config.php so no
 * credential is ever committed to the theme:
 *
 * 1. Where quote notifications are delivered (NEWERA_QUOTE_RECIPIENT).
 * 2. How mail leaves the server (NEWERA_SMTP_*). Shared hosts frequently accept
 *    a mail() call and then drop the message, which looks identical to a working
 *    form from the visitor's side. Routing through authenticated SMTP makes
 *    delivery observable.
 *
 * Every hook here is inert unless its constants are defined, so an install that
 * configures nothing keeps stock WordPress behaviour.
 *
 * @package newera
 */

defined( 'ABSPATH' ) || exit;

/**
 * Redirect quote notifications to a configured address.
 *
 * Defining NEWERA_QUOTE_RECIPIENT *replaces* the site admin address, which is
 * what a staging site wants and what production must not have: on a live site it
 * would divert real customer leads to whichever inbox is named here. Leave the
 * constant undefined in production.
 *
 * Accepts a comma-separated list. Addresses that are not valid are dropped, and
 * if none survive the default is left untouched rather than sending nowhere.
 *
 * @param string $recipient Default recipient (the site admin email).
 * @return string
 */
function newera_quote_recipient_override( $recipient ) {
	if ( ! defined( 'NEWERA_QUOTE_RECIPIENT' ) || ! NEWERA_QUOTE_RECIPIENT ) {
		return $recipient;
	}

	$configured = array_filter(
		array_map( 'trim', explode( ',', (string) NEWERA_QUOTE_RECIPIENT ) ),
		'is_email'
	);

	return $configured ? implode( ',', $configured ) : $recipient;
}
add_filter( 'newera_quote_recipient', 'newera_quote_recipient_override' );

/**
 * Whether SMTP delivery has been configured.
 *
 * @return bool
 */
function newera_smtp_configured() {
	return defined( 'NEWERA_SMTP_HOST' ) && NEWERA_SMTP_HOST;
}

/**
 * Route wp_mail() through an authenticated SMTP server.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer The mailer, passed by reference.
 */
function newera_configure_smtp( $phpmailer ) {
	if ( ! newera_smtp_configured() ) {
		return;
	}

	$phpmailer->isSMTP();
	$phpmailer->Host = NEWERA_SMTP_HOST; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$phpmailer->Port = defined( 'NEWERA_SMTP_PORT' ) ? (int) NEWERA_SMTP_PORT : 587; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

	// 'tls' is STARTTLS on port 587; 'ssl' is implicit TLS on 465. Anything else
	// (including an explicit empty string) means an unencrypted connection.
	$secure = defined( 'NEWERA_SMTP_SECURE' ) ? strtolower( (string) NEWERA_SMTP_SECURE ) : 'tls';

	if ( in_array( $secure, array( 'tls', 'ssl' ), true ) ) {
		$phpmailer->SMTPSecure = $secure; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	if ( defined( 'NEWERA_SMTP_USER' ) && NEWERA_SMTP_USER ) {
		$phpmailer->SMTPAuth = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Username = NEWERA_SMTP_USER; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$phpmailer->Password = defined( 'NEWERA_SMTP_PASS' ) ? NEWERA_SMTP_PASS : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}
}
add_action( 'phpmailer_init', 'newera_configure_smtp' );

/**
 * Send as the SMTP account.
 *
 * Gmail rewrites the From header to the authenticated account regardless, and a
 * mismatched From is a reliable way to fail SPF/DMARC elsewhere, so the sender is
 * aligned with the account actually doing the sending.
 *
 * @param string $from Default from address.
 * @return string
 */
function newera_mail_from( $from ) {
	if ( ! newera_smtp_configured() ) {
		return $from;
	}

	if ( defined( 'NEWERA_SMTP_FROM' ) && is_email( NEWERA_SMTP_FROM ) ) {
		return NEWERA_SMTP_FROM;
	}

	if ( defined( 'NEWERA_SMTP_USER' ) && is_email( NEWERA_SMTP_USER ) ) {
		return NEWERA_SMTP_USER;
	}

	return $from;
}
add_filter( 'wp_mail_from', 'newera_mail_from' );

/**
 * Use the site name as the sender name instead of the WordPress default.
 *
 * @param string $name Default from name ("WordPress").
 * @return string
 */
function newera_mail_from_name( $name ) {
	if ( ! newera_smtp_configured() || 'WordPress' !== $name ) {
		return $name;
	}

	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}
add_filter( 'wp_mail_from_name', 'newera_mail_from_name' );

/**
 * Record delivery failures.
 *
 * The visitor is intentionally still shown a success message when this fires: the
 * request has already been stored as a newera_quote post, so the lead is not lost
 * and there is nothing useful for the customer to do about a server-side mail
 * problem. The owner, however, needs to know — otherwise a misconfigured mailer is
 * indistinguishable from nobody filling in the form.
 *
 * @param WP_Error $error Why PHPMailer refused the message.
 */
function newera_log_mail_failure( $error ) {
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	error_log( 'New Era: wp_mail() failed — ' . $error->get_error_message() );
}
add_action( 'wp_mail_failed', 'newera_log_mail_failure' );
