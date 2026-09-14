<?php
/**
 * Minimum order amount for delivery — a notice on the cart and checkout pages
 * while the cart is under the threshold, plus real enforcement on checkout
 * submission (delivery only; local pickup always goes through). Managed from
 * the Kindi panel: amount (0 = off) and message text.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Add the section to the panel's "טקסטים והגדרות" tab.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_min_order_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections'] ) ) {
		$tabs['texts']['sections']['מינימום הזמנה למשלוח'] = array(
			'min_order_amount'  => array( 'type' => 'number', 'label' => 'סכום מינימום (₪)', 'help' => 'מתחת לסכום הזה מוצגת ההודעה בסל ובעמוד התשלום (איסוף עצמי תמיד אפשרי). 0 = כבוי.' ),
			'min_order_enforce' => array( 'type' => 'select', 'label' => 'אכיפה', 'options' => array( '1' => 'חסימת הזמנה (לא ניתן לשלוח מתחת למינימום)', '0' => 'הודעה בלבד (ההזמנה עוברת)' ), 'help' => 'קובע מה קורה כשלוחצים "אישור הזמנה" עם משלוח מתחת למינימום.' ),
			'min_order_msg'    => array( 'type' => 'textarea', 'label' => 'טקסט ההודעה', 'help' => 'מוצגת בסל ובעמוד התשלום כשהסל מתחת למינימום, וגם כהודעת השגיאה אם מנסים לשלוח בכל זאת. {amount} מוחלף בסכום המינימום.' ),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_min_order_settings' );

/**
 * The configured minimum (0 = feature off).
 *
 * @return float
 */
function kindi_min_order_amount(): float {
	return (float) kindi_opt( 'min_order_amount' );
}

/**
 * The message with {amount} filled in.
 *
 * @return string
 */
function kindi_min_order_msg(): string {
	$min    = kindi_min_order_amount();
	$amount = ( floor( $min ) === $min ? number_format( $min, 0 ) : number_format( $min, 2 ) ) . ' ₪';
	return str_replace( '{amount}', $amount, trim( (string) kindi_opt( 'min_order_msg' ) ) );
}

/**
 * The cart's product total as the shopper perceives it (after discounts,
 * including tax, excluding shipping).
 *
 * @return float
 */
function kindi_min_order_cart_total(): float {
	$cart = WC()->cart;
	return (float) $cart->get_cart_contents_total() + (float) $cart->get_cart_contents_tax();
}

/**
 * Whether the current cart is under the minimum and the feature applies
 * (enabled, cart not empty, order needs shipping, message not blank).
 *
 * @return bool
 */
function kindi_min_order_below(): bool {
	if ( kindi_min_order_amount() <= 0 || ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return false;
	}
	if ( ! WC()->cart->needs_shipping() || '' === kindi_min_order_msg() ) {
		return false;
	}
	return kindi_min_order_cart_total() < kindi_min_order_amount();
}

/**
 * Notice box on the cart and checkout pages while under the minimum.
 *
 * @return void
 */
function kindi_min_order_notice(): void {
	if ( kindi_min_order_below() && function_exists( 'kindi_cat_notice_box' ) ) {
		kindi_cat_notice_box( array( kindi_min_order_msg() ), 'kindi-catnotice--min' );
	}
}
add_action( 'woocommerce_before_cart', 'kindi_min_order_notice', 3 );
add_action( 'woocommerce_before_checkout_form', 'kindi_min_order_notice', 5 );

/**
 * Enforcement: block checkout submission under the minimum, unless the chosen
 * shipping method is local pickup — or the panel is set to notice-only mode.
 *
 * @return void
 */
function kindi_min_order_check(): void {
	if ( '1' !== (string) kindi_opt( 'min_order_enforce' ) || ! kindi_min_order_below() ) {
		return;
	}
	$chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	foreach ( $chosen as $method ) {
		if ( false !== strpos( (string) $method, 'local_pickup' ) ) {
			return; // Pickup orders are always allowed.
		}
	}
	wc_add_notice( kindi_min_order_msg(), 'error' );
}
add_action( 'woocommerce_checkout_process', 'kindi_min_order_check' );
