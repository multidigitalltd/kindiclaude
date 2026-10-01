<?php
/**
 * Live product search (FiboSearch-style) — lean AJAX suggestions.
 *
 * Exposes a read-only REST route that returns matching products (thumb, title,
 * price) and product categories as JSON. Results are short-lived transient
 * cached to keep the store fast under typing load.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the search REST route.
 *
 * @return void
 */
function kindi_register_search_route(): void {
	register_rest_route(
		'kindi/v1',
		'/search',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'kindi_rest_search',
			'permission_callback' => '__return_true', // Public, read-only catalogue search.
			'args'                => array(
				'q' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'kindi_register_search_route' );

/**
 * Is a product listable in SEARCH results?
 *
 * WC_Product::is_visible() answers by the current page type, and a REST request
 * is not a search page — so it judged these candidates by shop rules: products
 * set to "search results only" were treated as hidden (and "shop only" ones as
 * visible), the exact inverse of what a search dropdown needs. This applies the
 * search-context rules directly, while keeping the status / out-of-stock checks
 * and the third-party `woocommerce_product_is_visible` filter intact.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function kindi_search_product_visible( WC_Product $product ): bool {
	$visible = in_array( $product->get_catalog_visibility(), array( 'visible', 'search' ), true );

	if ( 'publish' !== $product->get_status() ) {
		$visible = false;
	}
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock() ) {
		$visible = false;
	}

	/** This filter is documented in WooCommerce: includes/abstracts/abstract-wc-product.php */
	return (bool) apply_filters( 'woocommerce_product_is_visible', $visible, $product->get_id() );
}

/**
 * Search products + categories for the live dropdown.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function kindi_rest_search( WP_REST_Request $request ): WP_REST_Response {
	$query = trim( (string) $request->get_param( 'q' ) );
	$empty = array(
		'products' => array(),
		'cats'     => array(),
		'all'      => '',
	);

	if ( mb_strlen( $query ) < 2 || ! class_exists( 'WooCommerce' ) ) {
		return rest_ensure_response( $empty );
	}

	// v2 key: the visibility fix below changes what a cached entry holds.
	$cache_key = 'kindi_search_v2_' . md5( $query );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return rest_ensure_response( $cached );
	}

	$limit    = 6;
	$products = array();

	// Over-fetch: candidates are dropped by the visibility check below, and
	// asking for exactly $limit meant a single hidden-from-catalog product in
	// the first six left the dropdown nearly empty. The loop stops as soon as
	// $limit products are collected, so the extra candidates cost nothing in the
	// common case.
	$wp_query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 18,
			's'                      => $query,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $wp_query->posts as $post ) {
		if ( count( $products ) >= $limit ) {
			break;
		}
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! kindi_search_product_visible( $product ) ) {
			continue;
		}
		$products[] = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'price' => $product->get_price_html(),
			'img'   => get_the_post_thumbnail_url( $post, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		);
	}
	wp_reset_postdata();

	$cats  = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 4,
			'search'     => $query,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$cats[] = array(
				'name'  => $term->name,
				'url'   => (string) get_term_link( $term ),
				'count' => (int) $term->count,
			);
		}
	}

	$data = array(
		'products' => $products,
		'cats'     => $cats,
		'all'      => add_query_arg(
			array(
				's'         => rawurlencode( $query ),
				'post_type' => 'product',
			),
			home_url( '/' )
		),
	);

	set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );

	return rest_ensure_response( $data );
}
