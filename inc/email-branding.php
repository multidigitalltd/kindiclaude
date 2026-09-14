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

/**
 * Make WooCommerce's HTML emails right-to-left (they ship LTR). Appends RTL
 * rules to the email stylesheet so order confirmations etc. read correctly in
 * Hebrew.
 *
 * @param string $css Existing email CSS.
 * @return string
 */
function kindi_wc_email_rtl( string $css ): string {
	if ( ! apply_filters( 'kindi_brand_all_emails', true ) ) {
		return $css;
	}
	return $css . '
		body, #wrapper, #template_container, #template_header, #body_content,
		#body_content_inner, #template_footer, .td, td, th, p, h1, h2, h3, ul, ol,
		.address, .order_item, .wc-item-meta { direction: rtl !important; text-align: right !important; }
		#template_footer td { text-align: center !important; }
	';
}
add_filter( 'woocommerce_email_styles', 'kindi_wc_email_rtl', 99 );

/**
 * Panel option key for an email's banner — each email has its own pair of
 * images. Filterable to add more emails.
 *
 * @param string $email_id WC_Email id.
 * @param string $pos      'top' or 'bottom'.
 * @return string Option key, or '' when this email carries no banners.
 */
function kindi_email_banner_opt( string $email_id, string $pos ): string {
	$map = (array) apply_filters(
		'kindi_email_banner_map',
		array(
			'customer_processing_order' => array( 'top' => 'email_proc_top', 'bottom' => 'email_proc_bottom' ),
			'customer_completed_order'  => array( 'top' => 'email_done_top', 'bottom' => 'email_done_bottom' ),
		)
	);
	return (string) ( $map[ $email_id ][ $pos ] ?? '' );
}

/**
 * Banner <img> markup for emails (inline styles only — email clients ignore
 * stylesheets). Empty when no image is configured.
 *
 * @param string $opt_key Panel option key.
 * @param string $margin  CSS margin for the wrapping paragraph.
 * @return string
 */
function kindi_email_banner_html( string $opt_key, string $margin ): string {
	$src = function_exists( 'kindi_opt' ) ? trim( (string) kindi_opt( $opt_key ) ) : '';
	if ( '' === $src ) {
		return '';
	}
	return '<p style="margin:' . esc_attr( $margin ) . ';text-align:center"><img src="' . esc_url( $src ) . '" alt="" style="max-width:100%;height:auto;border:0;border-radius:8px" /></p>';
}

/**
 * Top banner — right below the email header, above the order content.
 *
 * @param string        $heading Email heading (unused).
 * @param WC_Email|null $email   Email object.
 * @return void
 */
function kindi_email_top_banner( $heading = '', $email = null ): void {
	$opt = is_object( $email ) ? kindi_email_banner_opt( (string) $email->id, 'top' ) : '';
	if ( '' !== $opt ) {
		echo kindi_email_banner_html( $opt, '0 0 20px' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within.
	}
}
// Priority 20: after WooCommerce prints its header template (10).
add_action( 'woocommerce_email_header', 'kindi_email_top_banner', 20, 2 );

/**
 * Bottom banner — right after the order content, above the email footer.
 *
 * @param WC_Email|null $email Email object.
 * @return void
 */
function kindi_email_bottom_banner( $email = null ): void {
	$opt = is_object( $email ) ? kindi_email_banner_opt( (string) $email->id, 'bottom' ) : '';
	if ( '' !== $opt ) {
		echo kindi_email_banner_html( $opt, '20px 0 0' ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within.
	}
}
// Priority 5: before WooCommerce prints its footer template (10).
add_action( 'woocommerce_email_footer', 'kindi_email_bottom_banner', 5 );
