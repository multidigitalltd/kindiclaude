<?php
/**
 * Checkout payment — Kindi override.
 *
 * Renders the payment methods as a row of cards, then the fields for the
 * *selected* method in a single full-width panel BELOW the row (instead of
 * inside the chosen card). WooCommerce toggles each `.payment_box` by its
 * `.payment_method_{id}` class globally, so moving the boxes out of their list
 * items keeps the native show/hide + AJAX behaviour intact.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;

if ( ! wp_doing_ajax() ) {
	do_action( 'woocommerce_review_order_before_payment' );
}
?>
<div id="payment" class="woocommerce-checkout-payment">
	<?php if ( WC()->cart->needs_payment() ) : ?>
		<ul class="wc_payment_methods payment_methods methods kindi-paymethods">
			<?php
			if ( ! empty( $available_gateways ) ) {
				foreach ( $available_gateways as $gateway ) {
					?>
					<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?>">
						<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />
						<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
							<?php echo wp_kses_post( $gateway->get_title() ); ?> <?php echo $gateway->get_icon(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</label>
					</li>
					<?php
				}
			} else {
				echo '<li>';
				wc_print_notice( apply_filters( 'woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ? esc_html__( 'מצטערים, נראה שאין אמצעי תשלום זמינים. אנא צרו קשר אם דרושה עזרה.', 'kindi' ) : esc_html__( 'מלאו את הפרטים למעלה כדי לראות את אמצעי התשלום הזמינים.', 'kindi' ) ), 'notice' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</li>';
			}
			?>
		</ul>

		<?php if ( ! empty( $available_gateways ) ) : ?>
		<div class="kindi-payfields">
			<?php
			foreach ( $available_gateways as $gateway ) {
				if ( ! $gateway->has_fields() && ! $gateway->get_description() ) {
					continue;
				}
				?>
				<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>"<?php echo $gateway->chosen ? '' : ' style="display:none;"'; ?>>
					<?php $gateway->payment_fields(); ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php
	// The place-order button + terms + nonce are rendered in the order-summary
	// column instead (see checkout/review-order.php → kindi-summary__place).
	?>
</div>
