<?php
/**
 * WooCommerce integration.
 *
 * Declares theme support and aligns the store UI with the Kindi design system.
 * Loaded only when WooCommerce is active (guarded in functions.php).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Declare WooCommerce support and block-theme compatibility.
 *
 * @return void
 */
function kindi_woocommerce_setup(): void {
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1000,
			'product_grid'          => array(
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 5,
			),
		)
	);

	// Native gallery interactions (Vanilla / core) — no extra libraries required.
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'kindi_woocommerce_setup' );

/**
 * Products per row on shop/category archives (matches the 4-up design grid).
 *
 * @return int
 */
function kindi_wc_loop_columns(): int {
	return 4;
}
add_filter( 'loop_shop_columns', 'kindi_wc_loop_columns' );

/**
 * Render product search results with the product-archive template, so a search
 * for products looks exactly like the shop/category archive (product-card grid,
 * filters, ordering) instead of the generic post search layout.
 *
 * @param string[] $templates Candidate template slugs.
 * @return string[]
 */
function kindi_product_search_template( array $templates ): array {
	if ( class_exists( 'WooCommerce' ) && is_search() && 'product' === get_query_var( 'post_type' ) ) {
		array_unshift( $templates, 'archive-product' );
	}
	return $templates;
}
add_filter( 'search_template_hierarchy', 'kindi_product_search_template' );

/**
 * Hide the informational "הלקוח תואם לאזור …" shipping-zone notice by removing
 * any WooCommerce notice containing that phrase before notices are printed.
 *
 * @return void
 */
function kindi_scrub_zone_notice(): void {
	if ( ! function_exists( 'wc_get_notices' ) || ! function_exists( 'wc_set_notices' ) || ! WC()->session ) {
		return;
	}
	$all = wc_get_notices();
	if ( empty( $all ) ) {
		return;
	}
	$needle = 'תואם לאזור';
	$found  = false;
	foreach ( $all as $type => $list ) {
		foreach ( $list as $i => $notice ) {
			$msg = is_array( $notice ) ? (string) ( $notice['notice'] ?? '' ) : (string) $notice;
			if ( '' !== $msg && false !== mb_strpos( $msg, $needle ) ) {
				unset( $all[ $type ][ $i ] );
				$found = true;
			}
		}
	}
	if ( $found ) {
		wc_set_notices( $all );
	}
}
add_action( 'woocommerce_before_single_product', 'kindi_scrub_zone_notice', 5 );
add_action( 'woocommerce_before_cart', 'kindi_scrub_zone_notice', 5 );
add_action( 'woocommerce_before_checkout_form', 'kindi_scrub_zone_notice', 5 );
add_action( 'woocommerce_before_shop_loop', 'kindi_scrub_zone_notice', 1 );
add_action( 'template_redirect', 'kindi_scrub_zone_notice', 99 );

/**
 * Products per page on archives.
 *
 * @return int
 */
function kindi_wc_per_page(): int {
	return (int) apply_filters( 'kindi_products_per_page', 12 );
}
add_filter( 'loop_shop_per_page', 'kindi_wc_per_page' );

/**
 * Live header cart updates without a page reload (WooCommerce cart fragments).
 *
 * @param array<string,string> $fragments Cart fragments keyed by CSS selector.
 * @return array<string,string>
 */
function kindi_cart_fragments( array $fragments ): array {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}

	$count = WC()->cart->get_cart_contents_count();

	$fragments['span.kindi-cart-count'] = '<span class="kindi-cart__badge kindi-cart-count">' . absint( $count ) . '</span>';
	$fragments['b.kindi-cart-amount']   = '<b class="kindi-cart-amount">' . wp_kses_post( $count . ' פריטים • ' . WC()->cart->get_cart_subtotal() ) . '</b>';

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'kindi_cart_fragments' );
