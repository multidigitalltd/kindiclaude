<?php
/**
 * Out-of-stock cart cleanup.
 *
 * A saved cart (or one left open while stock sold out) blocked checkout with a
 * validation error until the shopper hunted down and removed each unavailable
 * line themselves — a guaranteed abandonment. Those lines are now dropped
 * automatically, reported at the top of the cart/checkout, and answered with
 * in-stock alternatives from the same category so the visit can continue.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is this cart line still buyable at the requested quantity?
 *
 * @param WC_Product $product  Product (variation when applicable).
 * @param int        $quantity Requested quantity.
 * @return bool
 */
function kindi_cart_line_available( WC_Product $product, int $quantity ): bool {
	if ( ! $product->exists() || 'publish' !== $product->get_status() ) {
		return false;
	}
	if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return false;
	}
	return $product->has_enough_stock( $quantity );
}

/**
 * Drop unavailable lines from the cart and remember them for the notice.
 *
 * @return void
 */
function kindi_cart_remove_unavailable(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$removed = array();

	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product = $item['data'] ?? null;
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		if ( kindi_cart_line_available( $product, (int) ( $item['quantity'] ?? 0 ) ) ) {
			continue;
		}

		$parent_id = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : $product->get_id();
		$removed[] = array(
			'name' => $product->get_name(),
			'id'   => $parent_id,
		);
		WC()->cart->remove_cart_item( $key );
	}

	if ( ! $removed ) {
		return;
	}

	WC()->cart->calculate_totals();
	if ( WC()->session ) {
		WC()->session->set( 'kindi_removed_items', $removed );
	}
}
// Runs on both the cart page and checkout, before WooCommerce's own validation
// would turn the same lines into a blocking error.
add_action( 'woocommerce_check_cart_items', 'kindi_cart_remove_unavailable', 1 );

/**
 * In-stock alternatives for a removed product: siblings from its categories.
 *
 * @param int[] $removed_ids Removed product IDs (excluded from the results).
 * @param int   $limit       How many to return.
 * @return int[] Product IDs.
 */
function kindi_cart_alternatives( array $removed_ids, int $limit = 4 ): array {
	$cats = array();
	foreach ( $removed_ids as $pid ) {
		$cats = array_merge( $cats, wc_get_product_term_ids( (int) $pid, 'product_cat' ) );
	}
	if ( ! $cats ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'        => 'product',
			'post_status'      => 'publish',
			'posts_per_page'   => $limit,
			'post__not_in'     => $removed_ids,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'ignore_sticky_posts' => true,
			'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				'relation' => 'AND',
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'term_id',
					'terms'    => array_values( array_unique( array_map( 'intval', $cats ) ) ),
				),
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'outofstock' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);
}

/**
 * Notice listing what was removed, plus alternatives to add in one click.
 *
 * @return void
 */
function kindi_cart_removed_notice(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}
	$removed = WC()->session->get( 'kindi_removed_items' );
	if ( ! is_array( $removed ) || ! $removed ) {
		return;
	}
	// Show once — the shopper has been told.
	WC()->session->set( 'kindi_removed_items', array() );

	$ids = array_values( array_filter( array_map( static function ( $r ) {
		return isset( $r['id'] ) ? (int) $r['id'] : 0;
	}, $removed ) ) );

	echo '<div class="kindi-oos" role="alert">';
	echo '<div class="kindi-oos__head">' . kindi_icon( 'info', 'kindi-icon--md' ) . '<strong>' . esc_html( _n( 'מוצר אחד הוסר מהסל — אזל מהמלאי', 'מוצרים הוסרו מהסל — אזלו מהמלאי', count( $removed ), 'kindi' ) ) . '</strong></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	echo '<ul class="kindi-oos__list">';
	foreach ( $removed as $item ) {
		echo '<li>' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</li>';
	}
	echo '</ul>';
	echo '<p class="kindi-oos__note">' . esc_html__( 'השארנו את שאר המוצרים בסל — אפשר להמשיך לתשלום.', 'kindi' ) . '</p>';

	$alts = kindi_cart_alternatives( $ids );
	if ( $alts ) {
		echo '<div class="kindi-oos__alts"><span class="kindi-oos__altstitle">' . esc_html__( 'אולי יתאימו לכם במקום:', 'kindi' ) . '</span><div class="kindi-oos__grid">';
		foreach ( $alts as $alt_id ) {
			$alt = wc_get_product( $alt_id );
			if ( ! $alt ) {
				continue;
			}
			printf(
				'<a class="kindi-oos__card" href="%s">%s<span class="kindi-oos__name">%s</span><span class="kindi-oos__price">%s</span></a>',
				esc_url( (string) $alt->get_permalink() ),
				wp_kses_post( $alt->get_image( 'woocommerce_thumbnail' ) ),
				esc_html( $alt->get_name() ),
				wp_kses_post( $alt->get_price_html() )
			);
		}
		echo '</div></div>';
	}

	echo '</div>';
}
add_action( 'woocommerce_before_cart', 'kindi_cart_removed_notice', 4 );
add_action( 'woocommerce_before_checkout_form', 'kindi_cart_removed_notice', 4 );
