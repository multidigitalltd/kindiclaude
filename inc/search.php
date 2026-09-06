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

	$cache_key = 'kindi_search_' . md5( $query );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return rest_ensure_response( $cached );
	}

	$products = array();
	$wp_query = new WP_Query(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 6,
			's'                      => $query,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $wp_query->posts as $post ) {
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! $product->is_visible() ) {
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
