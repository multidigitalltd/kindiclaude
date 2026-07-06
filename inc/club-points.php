<?php
/**
 * Club points vs. coupons — mutual exclusion (store policy).
 *
 * Simply Club redeems loyalty points as a negative cart fee. A coupon and a
 * points redemption must never be combined in one order, enforced server-side
 * in both directions:
 *   1. While points are redeemed, applying any coupon is rejected with a clear
 *      message — the cart form, the checkout coupon box and wc-ajax all pass
 *      through the same WooCommerce coupon validation.
 *   2. Redeeming points while a coupon is already applied removes the coupon,
 *      tells the shopper, and recalculates the totals so the same refresh
 *      already renders correct amounts.
 * The checkout coupon box is also replaced by a short note while points are
 * active (see woocommerce/checkout/review-order.php).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Does the cart carry a club-points redemption?
 *
 * Any negative fee counts as one — Simply Club (like most club plugins)
 * applies the redemption as a negative fee, and nothing else in this store
 * produces negative fees. Filter `kindi_fee_is_club_points` if a plugin
 * update ever changes the mechanism.
 *
 * @return bool
 */
function kindi_cart_redeems_points(): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_fees() as $fee ) {
		if ( apply_filters( 'kindi_fee_is_club_points', (float) $fee->amount < 0, $fee ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Direction 1: no coupon may join an active points redemption.
 *
 * @param bool      $valid  Whether the coupon is valid so far.
 * @param WC_Coupon $coupon Coupon being validated.
 * @return bool
 * @throws Exception When points are redeemed — WC_Discounts catches it and
 *                   surfaces the message as the coupon error.
 */
function kindi_block_coupon_with_points( bool $valid, WC_Coupon $coupon ): bool {
	if ( $valid && kindi_cart_redeems_points() ) {
		throw new Exception( esc_html__( 'לא ניתן לשלב קוד קופון עם מימוש נקודות מועדון. בטלו את מימוש הנקודות כדי להשתמש בקופון.', 'kindi' ) );
	}
	return $valid;
}
add_filter( 'woocommerce_coupon_is_valid', 'kindi_block_coupon_with_points', 10, 2 );

/**
 * Direction 2: redeeming points removes an already-applied coupon.
 *
 * Runs after totals so the plugin's redemption fee is in place. When both are
 * present the coupons are dropped and the totals recalculated once (static
 * guard prevents recursion), so the response of the same AJAX refresh is
 * already coupon-free.
 *
 * @return void
 */
function kindi_points_remove_coupons(): void {
	static $running = false;
	if ( $running || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}
	$cart = function_exists( 'WC' ) ? WC()->cart : null;
	if ( ! $cart || ! $cart->get_applied_coupons() || ! kindi_cart_redeems_points() ) {
		return;
	}
	foreach ( $cart->get_applied_coupons() as $code ) {
		$cart->remove_coupon( $code );
	}
	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( __( 'קוד הקופון הוסר — לא ניתן לשלב קופון עם מימוש נקודות מועדון.', 'kindi' ), 'notice' );
	}
	$running = true;
	$cart->calculate_totals();
	$running = false;
}
add_action( 'woocommerce_after_calculate_totals', 'kindi_points_remove_coupons' );
