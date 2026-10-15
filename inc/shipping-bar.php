<?php
/**
 * Free-shipping progress bar — shown in the cart and the slide-out mini-cart.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the "X ₪ to free shipping" progress message.
 *
 * @return void
 */
function kindi_free_shipping_progress(): void {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return;
	}

	$threshold = (float) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );
	if ( $threshold <= 0 ) {
		return;
	}

	// Furniture is excluded from free shipping — when the cart contains it, a
	// progress bar would promise something the order can't get, so show the
	// exclusion note instead.
	if ( function_exists( 'kindi_cart_has_furniture' ) && kindi_cart_has_furniture() ) {
		echo '<div class="kindi-freeship"><p><span class="kindi-freeship__ic">' . kindi_icon( 'truck', 'kindi-icon--sm' ) . '</span>' . esc_html__( 'הטבת משלוח חינם לא כוללת מוצרי ריהוט', 'kindi' ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- kindi_icon returns escaped SVG.
		return;
	}

	$total     = (float) WC()->cart->get_displayed_subtotal();
	$remaining = $threshold - $total;
	$pct       = max( 0, min( 100, ( $total / $threshold ) * 100 ) );

	$reached = $remaining <= 0;
	echo '<div class="kindi-freeship' . ( $reached ? ' is-reached' : '' ) . '">';
	if ( ! $reached ) {
		echo '<p><span class="kindi-freeship__ic">' . kindi_icon( 'truck', 'kindi-icon--sm' ) . '</span>עוד <strong>' . wp_kses_post( wc_price( $remaining ) ) . '</strong> ותיהנו ממשלוח חינם!</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- kindi_icon returns escaped SVG.
	} else {
		echo '<p><span class="kindi-freeship__ic">' . kindi_icon( 'party', 'kindi-icon--sm' ) . '</span>איזה כייף! יש לכם <strong>משלוח חינם</strong>.</p>'; // phpcs:ignore WordPress.Security.EscapeOutput -- kindi_icon returns escaped SVG.
	}
	echo '<div class="kindi-freeship__bar"><span style="width:' . esc_attr( (string) $pct ) . '%"></span></div>';
	echo '</div>';
}
add_action( 'woocommerce_before_cart', 'kindi_free_shipping_progress', 5 );
// Mini-cart drawer: at the TOP, above the item list (refreshes with the cart
// fragment on every add-to-cart / quantity change).
add_action( 'woocommerce_before_mini_cart', 'kindi_free_shipping_progress', 5 );

/**
 * What the order's shipping currently costs, as the chosen rate resolves right
 * now: `cost` is the amount (0 = free) or null when shipping isn't applicable
 * or no rate is resolvable yet, and `pickup` says whether a free self-pickup
 * rate is on offer.
 *
 * @return array{cost:float|null,pickup:bool}
 */
function kindi_cart_shipping_info(): array {
	$info = array( 'cost' => null, 'pickup' => false );

	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart || ! WC()->cart->needs_shipping() ) {
		return $info;
	}

	// show_shipping() is deliberately NOT checked. With WooCommerce's "hide
	// shipping costs until an address is entered" it returns false — yet the
	// cost is ALREADY inside the order total (calculate_shipping() only checks
	// needs_shipping()). That mismatch is the unexplained jump this row exists
	// to remove, so the amount is read straight off the calculated totals.
	$chosen   = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	$resolved = false;

	foreach ( WC()->shipping()->get_packages() as $index => $package ) {
		$rates = (array) ( $package['rates'] ?? array() );
		foreach ( $rates as $rate ) {
			if ( 'local_pickup' === $rate->get_method_id() && (float) $rate->get_cost() <= 0 ) {
				$info['pickup'] = true;
			}
		}
		$selected = (string) ( $chosen[ $index ] ?? '' );
		if ( '' !== $selected && isset( $rates[ $selected ] ) ) {
			$resolved = true;
		}
		break; // Single-package store.
	}

	$total = (float) WC()->cart->get_shipping_total() + (float) WC()->cart->get_shipping_tax();
	if ( $resolved || $total > 0 ) {
		$info['cost'] = $total;
	}

	return $info;
}

/**
 * Shipping cost row in the cart totals, above the order total.
 *
 * WooCommerce's own shipping row is hidden on the cart page (choosing a method
 * belongs to checkout), which left the jump between subtotal and total
 * unexplained — the classic checkout-abandonment surprise. This row states the
 * cost of the method the order will actually use, and points at free self
 * pickup when that is offered.
 *
 * @return void
 */
function kindi_cart_shipping_row(): void {
	$info   = kindi_cart_shipping_info();
	$cost   = $info['cost'];
	$pickup = $info['pickup'];

	if ( null === $cost ) {
		return;
	}

	// Printed server-side through wc_price() like every other amount on the page.
	echo '<tr class="kindi-cartship"><th>' . esc_html__( 'משלוח', 'kindi' ) . '</th><td data-title="' . esc_attr__( 'משלוח', 'kindi' ) . '">';
	echo $cost > 0
		? wp_kses_post( wc_price( $cost ) )
		: '<strong class="kindi-cartship__free">' . esc_html__( 'חינם', 'kindi' ) . '</strong>';
	if ( $pickup && $cost > 0 ) {
		echo '<span class="kindi-cartship__note">' . esc_html__( 'איסוף עצמי מהחנות — חינם', 'kindi' ) . '</span>';
	}
	echo '</td></tr>';
}
add_action( 'woocommerce_cart_totals_before_order_total', 'kindi_cart_shipping_row' );
