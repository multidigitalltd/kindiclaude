<?php
/**
 * Global email branding.
 *
 * Wraps every plain-text email leaving the site (password reset, new account,
 * core/plugin notifications…) in the shared branded template, and aligns
 * WooCommerce's own HTML emails to the brand palette + logo. Emails that are
 * already full HTML documents (WooCommerce, the theme's transactional mail) are
 * left untouched to avoid double-wrapping. Toggle with `kindi_brand_all_emails`.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Brand outgoing plain-text emails via the wp_mail filter.
 *
 * @param array<string,mixed> $atts wp_mail arguments (to, subject, message, headers, attachments).
 * @return array<string,mixed>
 */
function kindi_brand_outgoing_mail( array $atts ): array {
	if ( ! apply_filters( 'kindi_brand_all_emails', true ) || ! function_exists( 'kindi_email_template' ) ) {
		return $atts;
	}

	$message = isset( $atts['message'] ) ? (string) $atts['message'] : '';
	if ( '' === $message ) {
		return $atts;
	}

	// Already a full HTML document (WooCommerce / our transactional mail) — skip.
	if ( false !== stripos( $message, '<html' ) || false !== stripos( $message, '<!doctype' ) ) {
		return $atts;
	}
	// Already HTML markup from the sender — respect it, don't re-wrap.
	if ( $message !== wp_strip_all_tags( $message ) ) {
		return $atts;
	}

	$subject = isset( $atts['subject'] ) ? (string) $atts['subject'] : '';
	$inner   = wpautop( make_clickable( esc_html( $message ) ) );

	$atts['message'] = kindi_email_template( wp_specialchars_decode( $subject, ENT_QUOTES ), $inner );

	// Force an HTML content-type, dropping any plain-text one.
	$headers = $atts['headers'] ?? array();
	if ( is_string( $headers ) ) {
		$headers = preg_split( '/\r\n|\r|\n/', $headers ) ?: array();
	}
	if ( ! is_array( $headers ) ) {
		$headers = array();
	}
	$clean = array();
	foreach ( $headers as $header ) {
		$header = trim( (string) $header );
		if ( '' === $header || 0 === stripos( $header, 'content-type:' ) ) {
			continue;
		}
		$clean[] = $header;
	}
	$clean[]          = 'Content-Type: text/html; charset=UTF-8';
	$atts['headers']  = $clean;

	return $atts;
}
add_filter( 'wp_mail', 'kindi_brand_outgoing_mail', 99 );

/**
 * Use the brand red for WooCommerce emails — only when the admin still has the
 * WooCommerce factory default, so customised colours are respected.
 *
 * @param string $color Current base colour.
 * @return string
 */
function kindi_wc_email_base_color( string $color ): string {
	if ( ! apply_filters( 'kindi_brand_all_emails', true ) ) {
		return $color;
	}
	return '#7f54b3' === strtolower( $color ) ? '#E63946' : $color;
}
add_filter( 'woocommerce_email_base_color', 'kindi_wc_email_base_color' );

/**
 * Default the WooCommerce email header image to the site logo (if none set).
 *
 * @param string $image Current header image URL.
 * @return string
 */
function kindi_wc_email_header_image( string $image ): string {
	if ( $image || ! apply_filters( 'kindi_brand_all_emails', true ) || ! function_exists( 'kindi_img' ) ) {
		return $image;
	}
	return (string) kindi_img( 'logo.png' );
}
add_filter( 'woocommerce_email_header_image', 'kindi_wc_email_header_image' );
