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
 * Free-shipping threshold messaging used by the top announcement bar.
 * Single source of truth so templates and JS stay in sync.
 *
 * @return int Threshold in NIS.
 */
function kindi_free_shipping_threshold(): int {
	return (int) apply_filters( 'kindi_free_shipping_threshold', 299 );
}
