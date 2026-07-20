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

	// Native gallery interactions. Zoom is intentionally NOT enabled — the hover
	// magnifier overlaid a transparent zoom over the original image, which looked
	// poor; lightbox (click to enlarge) + slider stay.
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'kindi_woocommerce_setup' );

/**
 * Belt-and-braces: make sure the hover-zoom never runs even if another plugin
 * (or a cached page) re-enables it — dequeue the zoom library on product pages.
 *
 * @return void
 */
function kindi_dequeue_zoom(): void {
	if ( function_exists( 'is_product' ) && is_product() ) {
		wp_dequeue_script( 'zoom' );
	}
}
add_action( 'wp_enqueue_scripts', 'kindi_dequeue_zoom', 100 );

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
 * Accurate sizes attribute for product-card thumbnails. WordPress's default
 * claims ~100vw, so phones pick a desktop-sized source from the srcset; the
 * card grid is really 2-up on phones (≈46vw), 3-up on tablets and 4–5-up on
 * desktop (≈320px each) — declaring that lets the browser download the small
 * candidate.
 *
 * @param array<string,string> $attr Image attributes.
 * @param WP_Post|null         $attachment Attachment (unused).
 * @param string|int[]         $size Requested size name.
 * @return array<string,string>
 */
function kindi_product_thumb_sizes( array $attr, $attachment = null, $size = '' ): array {
	if ( 'woocommerce_thumbnail' === $size ) {
		$attr['sizes'] = '(max-width: 639px) 46vw, (max-width: 1023px) 30vw, 320px';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'kindi_product_thumb_sizes', 10, 3 );

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
// Priority 9999: WooCommerce unshifts its own product-search-results slug on
// this hierarchy at priority 10, and it registers later than the theme — so at
// equal priority its default block template would win over the archive layout.
// Running last keeps archive-product first (templates/product-search-results.html
// covers any path that still resolves WooCommerce's slug).
add_filter( 'search_template_hierarchy', 'kindi_product_search_template', 9999 );

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

/*
 * ---------------------------------------------------------------------------
 * Free shipping excludes furniture.
 * ---------------------------------------------------------------------------
 */

/**
 * Term ids of the categories excluded from free shipping, including all their
 * subcategories (cached for the request).
 *
 * Source: the categories chosen in the Kindi panel (free_ship_exclude_cats).
 * When none are chosen there is no exclusion at all — every product qualifies
 * for free shipping by the usual threshold.
 *
 * @return int[]
 */
function kindi_furniture_term_ids(): array {
	static $ids = null;
	if ( null !== $ids ) {
		return $ids;
	}
	$ids = array();

	$chosen = function_exists( 'kindi_opt' ) ? kindi_opt( 'free_ship_exclude_cats' ) : array();
	if ( ! is_array( $chosen ) || ! $chosen ) {
		return $ids; // Nothing configured → no free-shipping exclusions.
	}

	$roots = array_map( 'intval', $chosen );
	$ids   = $roots;
	foreach ( $roots as $kindi_tid ) {
		$children = get_term_children( (int) $kindi_tid, 'product_cat' );
		if ( ! is_wp_error( $children ) ) {
			$ids = array_merge( $ids, array_map( 'intval', $children ) );
		}
	}
	$ids = array_values( array_unique( $ids ) );
	return $ids;
}

/**
 * Does the cart contain a product from a furniture category?
 *
 * @return bool
 */
function kindi_cart_has_furniture(): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	$ids = kindi_furniture_term_ids();
	if ( ! $ids ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $kindi_item ) {
		$pid = (int) ( $kindi_item['product_id'] ?? 0 ); // Parent id, so variations count too.
		if ( $pid && has_term( $ids, 'product_cat', $pid ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Withhold WooCommerce's free_shipping method when the cart contains
 * furniture — those orders always ship at the flat rate, whatever the total.
 *
 * @param bool $is_available Whether free shipping qualifies.
 * @return bool
 */
function kindi_free_shipping_excludes_furniture( $is_available ) {
	return $is_available && ! kindi_cart_has_furniture();
}
add_filter( 'woocommerce_shipping_free_shipping_is_available', 'kindi_free_shipping_excludes_furniture' );
