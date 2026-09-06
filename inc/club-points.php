<?php
/**
 * Club points vs. coupons — mutual exclusion (store policy).
 *
 * Simply Club redeems loyalty points by injecting a virtual coupon into the
 * cart (labelled "הנחה לחברי מועדון"); some club/points plugins use a negative
 * fee instead. Both mechanisms are detected. A shopper coupon and a points
 * redemption must never be combined in one order, enforced server-side in
 * both directions:
 *   1. While points are redeemed, applying any regular coupon is rejected
 *      with a clear message — the cart form, the checkout coupon box and
 *      wc-ajax all pass through the same WooCommerce coupon validation.
 *   2. Redeeming points while a regular coupon is already applied removes
 *      the coupon (never the redemption itself), tells the shopper, and
 *      recalculates the totals so the same refresh renders correct amounts.
 * The checkout coupon box is also replaced by a short note while points are
 * active (see woocommerce/checkout/review-order.php).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is this applied coupon a club-points redemption rather than a shopper code?
 *
 * A redemption coupon is recognised by club keywords in its code, its
 * description, or its cart label (Simply Club renames the label to
 * "הנחה לחברי מועדון" through the same filter applied here) — or by being a
 * virtual coupon a plugin injected on the fly (no backing post, ID 0).
 * Adjustable via the `kindi_coupon_is_club_points` filter.
 *
 * @param WC_Coupon $coupon Applied coupon.
 * @return bool
 */
function kindi_coupon_is_club_points( WC_Coupon $coupon ): bool {
	$label = (string) apply_filters( 'woocommerce_cart_totals_coupon_label', $coupon->get_code(), $coupon );
	$hay   = strtolower( $coupon->get_code() . ' ' . $label . ' ' . $coupon->get_description() );

	$is = 0 === $coupon->get_id();
	if ( ! $is ) {
		foreach ( array( 'simply', 'club', 'points', 'מועדון', 'נקודות' ) as $kw ) {
			if ( false !== mb_strpos( $hay, $kw ) ) {
				$is = true;
				break;
			}
		}
	}
	return (bool) apply_filters( 'kindi_coupon_is_club_points', $is, $coupon );
}

/**
 * Does the cart carry a club-points redemption?
 *
 * Checks the applied coupons for a redemption coupon (Simply Club) and the
 * fees for a negative amount (the other common mechanism; filterable via
 * `kindi_fee_is_club_points`).
 *
 * @param string $exclude Coupon code to skip — pass the code currently being
 *                        validated so it never counts itself.
 * @return bool
 */
function kindi_cart_redeems_points( string $exclude = '' ): bool {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
		if ( $code !== $exclude && kindi_coupon_is_club_points( $coupon ) ) {
			return true;
		}
	}
	foreach ( WC()->cart->get_fees() as $fee ) {
		if ( apply_filters( 'kindi_fee_is_club_points', (float) $fee->amount < 0, $fee ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Direction 1: no regular coupon may join an active points redemption.
 *
 * The redemption coupon itself always passes — otherwise WooCommerce's
 * re-validation of applied coupons would strip the redemption too.
 *
 * @param bool      $valid  Whether the coupon is valid so far.
 * @param WC_Coupon $coupon Coupon being validated.
 * @return bool
 * @throws Exception When points are redeemed — WC_Discounts catches it and
 *                   surfaces the message as the coupon error.
 */
function kindi_block_coupon_with_points( bool $valid, WC_Coupon $coupon ): bool {
	if ( $valid && ! kindi_coupon_is_club_points( $coupon ) && kindi_cart_redeems_points( $coupon->get_code() ) ) {
		throw new Exception( esc_html__( 'לא ניתן לשלב קוד קופון עם מימוש נקודות מועדון. בטלו את מימוש הנקודות כדי להשתמש בקופון.', 'kindi' ) );
	}
	return $valid;
}
add_filter( 'woocommerce_coupon_is_valid', 'kindi_block_coupon_with_points', 10, 2 );

/**
 * Direction 2: redeeming points removes already-applied regular coupons.
 *
 * Runs after totals so the plugin's redemption (coupon or fee) is in place.
 * The redemption itself is kept; only shopper coupons are dropped, and the
 * totals are recalculated once (static guard prevents recursion) so the
 * response of the same AJAX refresh is already coupon-free.
 *
 * @return void
 */
function kindi_points_remove_coupons(): void {
	static $running = false;
	if ( $running || ( is_admin() && ! wp_doing_ajax() ) ) {
		return;
	}
	$cart = function_exists( 'WC' ) ? WC()->cart : null;
	if ( ! $cart || ! $cart->get_applied_coupons() ) {
		return;
	}

	$regular = array();
	foreach ( $cart->get_coupons() as $code => $coupon ) {
		if ( ! kindi_coupon_is_club_points( $coupon ) ) {
			$regular[] = $code;
		}
	}
	if ( ! $regular || ! kindi_cart_redeems_points() ) {
		return;
	}

	foreach ( $regular as $code ) {
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
