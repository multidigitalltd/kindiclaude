<?php
/**
 * Checkout billing form — Kindi override.
 *
 * Splits the billing fields into two numbered cards matching the design:
 *   1. "פרטי קשר"   — email + phone + a marketing opt-in.
 *   2. "כתובת למשלוח" — name + street + apartment + city + postcode.
 *
 * Field labels/placeholders/ordering are set in inc/checkout.php via the
 * `woocommerce_checkout_fields` filter; in-field icons are applied in
 * woocommerce.css. Shipping is collected on the billing address
 * (ship-to-billing), so there is no separate shipping-fields form.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $checkout ) || ! $checkout instanceof WC_Checkout ) {
	$checkout = WC()->checkout();
}

$kindi_fields  = $checkout->get_checkout_fields( 'billing' );
$kindi_contact = array( 'billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email' );
?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">

		<section class="kindi-cobox" id="kindi-box-contact">
			<header class="kindi-cobox__head">
				<span class="kindi-cobox__n">1</span>
				<div class="kindi-cobox__heading">
					<h3 class="kindi-cobox__title"><?php esc_html_e( 'פרטי קשר', 'kindi' ); ?></h3>
					<p class="kindi-cobox__sub"><?php esc_html_e( 'נשתמש בהם רק לעדכוני ההזמנה', 'kindi' ); ?></p>
				</div>
			</header>
			<div class="kindi-cobox__body kindi-cobox__grid">
				<?php
				foreach ( $kindi_contact as $kindi_key ) {
					if ( isset( $kindi_fields[ $kindi_key ] ) ) {
						woocommerce_form_field( $kindi_key, $kindi_fields[ $kindi_key ], $checkout->get_value( $kindi_key ) );
					}
				}
				?>
				<p class="form-row kindi-optin" id="kindi_optin_field">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="kindi_marketing_optin" value="1" checked />
						<span><?php esc_html_e( 'שלחו לי מבצעים והטבות', 'kindi' ); ?></span>
					</label>
					<span class="kindi-optin__gift">🎁 <?php esc_html_e( '10% הנחה במייל הראשון', 'kindi' ); ?></span>
				</p>
			</div>
		</section>

		<section class="kindi-cobox" id="kindi-box-address">
			<header class="kindi-cobox__head">
				<span class="kindi-cobox__n">2</span>
				<div class="kindi-cobox__heading">
					<h3 class="kindi-cobox__title"><?php esc_html_e( 'כתובת למשלוח', 'kindi' ); ?></h3>
					<p class="kindi-cobox__sub"><?php esc_html_e( 'לאן שולחים את ההזמנה', 'kindi' ); ?></p>
				</div>
			</header>
			<div class="kindi-cobox__body kindi-cobox__grid">
				<?php
				foreach ( $kindi_fields as $kindi_key => $kindi_field ) {
					if ( in_array( $kindi_key, $kindi_contact, true ) ) {
						continue;
					}
					woocommerce_form_field( $kindi_key, $kindi_field, $checkout->get_value( $kindi_key ) );
				}
				?>
				<p class="form-row kindi-billsame" id="kindi_billsame_field">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input type="checkbox" class="input-checkbox" checked disabled />
						<span><?php esc_html_e( 'כתובת החיוב זהה לכתובת המשלוח', 'kindi' ); ?></span>
					</label>
				</p>
			</div>
		</section>

	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
