<?php
/**
 * Checkout shipping form — Kindi override.
 *
 * A "משלוח לכתובת אחרת" toggle right under the address card (Box 2); checking
 * it opens exactly four fields (רחוב · מספר · עיר · מיקוד — mapped in
 * kindi_checkout_field_layout). The checkbox keeps WooCommerce's native
 * id/name, so checkout.js itself shows/hides div.shipping_address, triggers
 * update_checkout, and validates the fields only while checked.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="woocommerce-shipping-fields kindi-shipto">
	<?php if ( true === WC()->cart->needs_shipping_address() ) : ?>

		<h3 id="ship-to-different-address" class="kindi-shipto__toggle">
			<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
				<input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" <?php checked( apply_filters( 'woocommerce_ship_to_different_address_checked', 'shipping' === get_option( 'woocommerce_ship_to_destination' ) ? 1 : 0 ), 1 ); ?> type="checkbox" name="ship_to_different_address" value="1" />
				<span><?php esc_html_e( 'משלוח לכתובת אחרת', 'kindi' ); ?></span>
			</label>
		</h3>

		<div class="shipping_address kindi-shipto__fields">
			<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>
			<div class="kindi-cobox__grid">
				<?php
				foreach ( $checkout->get_checkout_fields( 'shipping' ) as $kindi_key => $kindi_field ) {
					woocommerce_form_field( $kindi_key, $kindi_field, $checkout->get_value( $kindi_key ) );
				}
				?>
			</div>
			<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
		</div>

	<?php endif; ?>
</div>
