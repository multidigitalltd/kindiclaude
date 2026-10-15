<?php
/**
 * Security hardening — headers and surface reduction.
 *
 * Implements the Multi Digital security baseline at the theme layer. Application
 * data handling (sanitization/escaping/nonces) lives next to each feature.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Send conservative security headers on front-end responses.
 *
 * CSP is intentionally omitted here (it must be tuned per third-party stack);
 * a report-only policy can be layered via the kindi_security_headers filter.
 *
 * @return void
 */
function kindi_security_headers(): void {
	if ( is_admin() || headers_sent() ) {
		return;
	}

	$headers = apply_filters(
		'kindi_security_headers',
		array(
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options'        => 'SAMEORIGIN',
			'Referrer-Policy'        => 'strict-origin-when-cross-origin',
			'X-XSS-Protection'       => '0',
		)
	);

	foreach ( $headers as $name => $value ) {
		header( sprintf( '%s: %s', $name, $value ) );
	}
}
add_action( 'send_headers', 'kindi_security_headers' );

/**
 * Remove the WordPress version from feeds and scripts/styles query strings,
 * limiting fingerprinting.
 *
 * @return string
 */
function kindi_remove_version(): string {
	return '';
}
add_filter( 'the_generator', 'kindi_remove_version' );

/**
 * Disable XML-RPC (common brute-force / amplification vector) unless explicitly enabled.
 *
 * @return bool
 */
function kindi_disable_xmlrpc(): bool {
	return (bool) apply_filters( 'kindi_enable_xmlrpc', false );
}
add_filter( 'xmlrpc_enabled', 'kindi_disable_xmlrpc' );
