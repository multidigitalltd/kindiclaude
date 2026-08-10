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
 * Is the product itself still sellable at all (any quantity)?
 *
 * Deliberately limited to existence, publication and stock. `is_purchasable()`
 * is NOT used as a removal reason: plugins price some legitimate lines at
 * runtime (gift cards, club rewards, bundle children) and can report them as
 * unpurchasable mid-request — deleting those from a shopper's cart would be far
 * worse than the blocked checkout this module exists to prevent. WooCommerce
 * still validates purchasability itself.
 *
 * @param WC_Product $product Product (variation when applicable).
 * @return bool
 */
function kindi_cart_product_sellable( WC_Product $product ): bool {
	if ( ! $product->exists() ) {
		return false;
	}
	$status = $product->get_status();
	if ( '' !== $status && 'publish' !== $status ) {
		return false;
	}
	return $product->is_in_stock();
}

/**
 * Fix up the cart: drop what can no longer be sold, and where only part of the
 * requested quantity is left, keep the line and reduce it to what's in stock —
 * selling 2 of 5 beats losing the line entirely.
 *
 * @return void
 */
function kindi_cart_remove_unavailable(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$removed  = array();
	$adjusted = array();

	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product = $item['data'] ?? null;
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$wanted    = (int) ( $item['quantity'] ?? 0 );
		$parent_id = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : $product->get_id();

		if ( ! kindi_cart_product_sellable( $product ) ) {
			$removed[] = array( 'name' => $product->get_name(), 'id' => $parent_id );
			WC()->cart->remove_cart_item( $key );
			continue;
		}

		// Enough stock (or backorders allowed) — nothing to do.
		if ( $product->has_enough_stock( $wanted ) ) {
			continue;
		}

		// Partial stock: keep the line at the quantity actually available.
		$available = $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
		if ( $available > 0 ) {
			WC()->cart->set_quantity( $key, $available, false );
			$adjusted[] = array(
				'name'   => $product->get_name(),
				'wanted' => $wanted,
				'left'   => $available,
			);
		} else {
			$removed[] = array( 'name' => $product->get_name(), 'id' => $parent_id );
			WC()->cart->remove_cart_item( $key );
		}
	}

	if ( ! $removed && ! $adjusted ) {
		return;
	}

	WC()->cart->calculate_totals();
	if ( WC()->session ) {
		WC()->session->set( 'kindi_removed_items', $removed );
		WC()->session->set( 'kindi_adjusted_items', $adjusted );
	}
}
// Priority 0: WooCommerce registers its own check_cart_items() at priority 1,
// and once that has queued an error notice the checkout page refuses to render
// at all — cleaning the cart afterwards would be too late. Running first means
// there is nothing left for it to complain about.
add_action( 'woocommerce_check_cart_items', 'kindi_cart_remove_unavailable', 0 );

/**
 * In-stock alternatives for a removed product: siblings from its categories.
 *
 * @param int[] $removed_ids Removed product IDs (excluded from the results).
 * @param int   $limit       How many to return.
 * @return int[] Product IDs.
 */
function kindi_cart_alternatives( array $removed_ids, int $limit = 4 ): array {
	if ( ! $removed_ids || ! function_exists( 'wc_get_product_term_ids' ) ) {
		return array();
	}
	$cats = array();
	foreach ( $removed_ids as $pid ) {
		$cats = array_merge( $cats, (array) wc_get_product_term_ids( (int) $pid, 'product_cat' ) );
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
	$removed  = WC()->session->get( 'kindi_removed_items' );
	$adjusted = WC()->session->get( 'kindi_adjusted_items' );
	$removed  = is_array( $removed ) ? $removed : array();
	$adjusted = is_array( $adjusted ) ? $adjusted : array();
	if ( ! $removed && ! $adjusted ) {
		return;
	}
	// Show once — the shopper has been told.
	WC()->session->set( 'kindi_removed_items', array() );
	WC()->session->set( 'kindi_adjusted_items', array() );

	$ids = array_values( array_filter( array_map( static function ( $r ) {
		return isset( $r['id'] ) ? (int) $r['id'] : 0;
	}, $removed ) ) );

	$title = $removed
		? _n( 'מוצר אחד הוסר מהסל — אזל מהמלאי', 'מוצרים הוסרו מהסל — אזלו מהמלאי', count( $removed ), 'kindi' )
		: __( 'עדכנו את הכמות בסל לפי המלאי הזמין', 'kindi' );

	$icon = function_exists( 'kindi_icon' ) ? kindi_icon( 'info', 'kindi-icon--md' ) : '';

	echo '<div class="kindi-oos" role="alert">';
	echo '<div class="kindi-oos__head">' . $icon . '<strong>' . esc_html( $title ) . '</strong></div>'; // phpcs:ignore WordPress.Security.EscapeOutput

	if ( $removed ) {
		echo '<ul class="kindi-oos__list">';
		foreach ( $removed as $item ) {
			echo '<li>' . esc_html( (string) ( $item['name'] ?? '' ) ) . '</li>';
		}
		echo '</ul>';
	}

	if ( $adjusted ) {
		echo '<ul class="kindi-oos__list kindi-oos__list--qty">';
		foreach ( $adjusted as $item ) {
			printf(
				'<li>%1$s — <strong>%2$s</strong></li>',
				esc_html( (string) ( $item['name'] ?? '' ) ),
				esc_html( sprintf( /* translators: 1: quantity left, 2: quantity requested. */ __( 'נשארו %1$d במלאי במקום %2$d — הכמות עודכנה', 'kindi' ), (int) ( $item['left'] ?? 0 ), (int) ( $item['wanted'] ?? 0 ) ) )
			);
		}
		echo '</ul>';
	}

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
