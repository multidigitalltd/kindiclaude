<?php
/**
 * Recently viewed products — tracked via cookie, shown on product pages.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Record the current product in the recently-viewed cookie.
 *
 * @return void
 */
function kindi_track_recently_viewed(): void {
	if ( is_admin() || ! is_singular( 'product' ) || ! function_exists( 'wc_setcookie' ) ) {
		return;
	}

	$id  = (int) get_queried_object_id();
	$ids = kindi_recently_viewed_ids();
	$ids = array_values( array_diff( $ids, array( $id ) ) );
	array_unshift( $ids, $id );
	$ids = array_slice( array_unique( $ids ), 0, 12 );

	wc_setcookie( 'kindi_rv', implode( ',', $ids ) );
}
add_action( 'template_redirect', 'kindi_track_recently_viewed', 20 );

/**
 * Read recently-viewed product IDs from the cookie.
 *
 * @return array<int,int>
 */
function kindi_recently_viewed_ids(): array {
	if ( empty( $_COOKIE['kindi_rv'] ) ) {
		return array();
	}
	return array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_COOKIE['kindi_rv'] ) ) ) ) );
}

/**
 * Shortcode: [kindi_recently_viewed].
 *
 * @return string
 */
function kindi_recently_viewed_shortcode(): string {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return '';
	}

	$ids = array_filter( kindi_recently_viewed_ids(), static fn( $id ) => $id !== (int) get_the_ID() );
	if ( ! $ids ) {
		return '';
	}

	$list = implode( ',', array_slice( $ids, 0, 5 ) );

	// Same markup/columns as the "related products" row so the two look identical.
	return '<section class="kindi-section related products kindi-rv"><h2>נצפו לאחרונה</h2>'
		. do_shortcode( '[products ids="' . esc_attr( $list ) . '" columns="5" limit="5" orderby="post__in"]' )
		. '</section>';
}
add_shortcode( 'kindi_recently_viewed', 'kindi_recently_viewed_shortcode' );

/**
 * Auto-append the recently-viewed row after single products.
 *
 * @return void
 */
function kindi_output_recently_viewed(): void {
	echo do_shortcode( '[kindi_recently_viewed]' ); // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'woocommerce_after_single_product_summary', 'kindi_output_recently_viewed', 25 );
