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
add_action( 'woocommerce_widget_shopping_cart_before_buttons', 'kindi_free_shipping_progress', 5 );
