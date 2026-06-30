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

	<div class="form-row place-order">
		<noscript>
			<?php esc_html_e( 'מאחר שהדפדפן אינו תומך ב-JavaScript או שהיא מושבתת, יש ללחוץ על "עדכון סכומים" לפני ביצוע ההזמנה.', 'kindi' ); ?>
			<br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'עדכון סכומים', 'kindi' ); ?>"><?php esc_html_e( 'עדכון סכומים', 'kindi' ); ?></button>
		</noscript>

		<?php wc_get_template( 'checkout/terms.php' ); ?>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
