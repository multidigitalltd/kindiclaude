<?php
/**
 * Cart & checkout enhancements — progress steps, free-shipping bar on checkout,
 * and a club/coupon banner. Styling lives in woocommerce.css.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the cart → details → payment → done progress steps.
 *
 * @param string $active Active step key: 'cart' | 'details'.
 * @return void
 */
function kindi_checkout_steps( string $active ): void {
	$steps = array(
		'cart'    => 'עגלה',
		'details' => 'פרטים ומשלוח',
		'payment' => 'תשלום',
		'done'    => 'אישור',
	);
	$order   = array_keys( $steps );
	$active_i = array_search( $active, $order, true );

	echo '<ol class="kindi-steps">';
	foreach ( $order as $i => $key ) {
		$state = $i < $active_i ? 'done' : ( $i === $active_i ? 'active' : 'future' );
		printf(
			'<li class="kindi-step is-%s"><span class="kindi-step__n">%s</span><span class="kindi-step__l">%s</span></li>',
			esc_attr( $state ),
			'done' === $state ? '✓' : esc_html( (string) ( $i + 1 ) ),
			esc_html( $steps[ $key ] )
		);
	}
	echo '</ol>';
}

/**
 * Steps on the cart page.
 *
 * @return void
 */
function kindi_cart_steps(): void {
	kindi_checkout_steps( 'cart' );
}
add_action( 'woocommerce_before_cart', 'kindi_cart_steps', 1 );

/**
 * Steps + free-shipping bar on the checkout page.
 *
 * @return void
 */
function kindi_checkout_top(): void {
	kindi_checkout_steps( 'details' );
	if ( function_exists( 'kindi_free_shipping_progress' ) ) {
		kindi_free_shipping_progress();
	}
}
add_action( 'woocommerce_before_checkout_form', 'kindi_checkout_top', 5 );

/**
 * Club / benefits banner above the checkout form.
 *
 * @return void
 */
function kindi_checkout_club_banner(): void {
	if ( is_user_logged_in() ) {
		return;
	}
	echo '<div class="kindi-checkout-club">'
		. '<span class="kindi-checkout-club__ic">' . kindi_icon( 'gift', 'kindi-icon--lg kindi-icon--white' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<div><strong>כבר חברי מועדון קינדי?</strong><span> התחברו לקבלת הטבות, נקודות וצבירה.</span></div>'
		. '<a class="kindi-checkout-club__btn" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">התחברות / הרשמה</a>'
		. '</div>';
}
add_action( 'woocommerce_before_checkout_form', 'kindi_checkout_club_banner', 4 );

/*
 * ---------------------------------------------------------------------------
 * Gift card & Gifta integrations.
 * ---------------------------------------------------------------------------
 */

/**
 * Move the Simply gift-card redemption box up to the top of the checkout form,
 * so it sits alongside the coupon and Gifta fields instead of further down.
 * The filter only does anything when the gift-card plugin is active.
 */
add_filter( 'simply_offerbox_checkout_action', static fn(): string => 'woocommerce_checkout_before_customer_details' );

/**
 * Notice before the payment methods: Gifta gift-cards can't be combined with
 * coupons. Escaped + translatable; keeps the original `custom-payment-text`
 * class so existing styling still applies.
 *
 * @return void
 */
function kindi_gifta_coupon_notice(): void {
	echo '<p class="kindi-gifta-note custom-payment-text">'
		. esc_html__( 'בתשלום עם כרטיס Gifta לא ניתן להשתמש בקופונים. רוצים להשתמש בקופון? פשוט בחרו אמצעי תשלום אחר.', 'kindi' )
		. '</p>';
}
add_action( 'woocommerce_review_order_before_payment', 'kindi_gifta_coupon_notice' );
