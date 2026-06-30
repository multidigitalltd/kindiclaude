<?php
/**
 * Checkout billing form — Kindi override.
 *
 * Splits the billing fields into two numbered cards matching the design:
 *   1. "פרטי קשר"    — first name + last name + phone + email + marketing opt-in.
 *   2. "כתובת למשלוח" — street + apartment + city + postcode (+ billing-same note).
 *
 * Labels/placeholders/ordering are forced HERE (locally), not via the
 * `woocommerce_checkout_fields` filter, because locale/checkout plugins can
 * override that filter. Country/state are still rendered (hidden) so the order
 * validates and submits. In-field icons live in woocommerce.css.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $checkout ) || ! $checkout instanceof WC_Checkout ) {
	$checkout = WC()->checkout();
}

$kindi_fields = $checkout->get_checkout_fields( 'billing' );

$kindi_contact = array( 'billing_first_name', 'billing_last_name', 'billing_email', 'billing_phone' );
$kindi_address = array( 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_postcode' );

$kindi_overrides = array(
	'billing_first_name' => array( 'שם פרטי', 'ישראלה', 'form-row-first' ),
	'billing_last_name'  => array( 'שם משפחה', 'ישראלי', 'form-row-last' ),
	'billing_email'      => array( 'אימייל', 'name@example.com', 'form-row-first' ),
	'billing_phone'      => array( 'טלפון נייד', '050-1234567', 'form-row-last' ),
	'billing_address_1'  => array( 'רחוב ומספר', 'הרצל 12', 'form-row-first' ),
	'billing_address_2'  => array( 'דירה / כניסה', 'דירה 4, קומה 2', 'form-row-last' ),
	'billing_city'       => array( 'עיר', 'תל אביב', 'form-row-first' ),
	'billing_postcode'   => array( 'מיקוד', '6100000', 'form-row-last' ),
);

/**
 * Render one billing field with the Kindi label/placeholder/row class forced.
 */
$kindi_render = static function ( $key ) use ( $checkout, $kindi_fields, $kindi_overrides ) {
	if ( ! isset( $kindi_fields[ $key ] ) ) {
		return;
	}
	$field = $kindi_fields[ $key ];
	if ( isset( $kindi_overrides[ $key ] ) ) {
		$field['label']       = $kindi_overrides[ $key ][0];
		$field['placeholder'] = $kindi_overrides[ $key ][1];
		$field['class']       = array( $kindi_overrides[ $key ][2] );
	}
	woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
};
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
					$kindi_render( $kindi_key );
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
				foreach ( $kindi_address as $kindi_key ) {
					$kindi_render( $kindi_key );
				}

				// Render any remaining billing fields (country, state, …) so the order
				// still validates/submits. These are hidden (single-country store +
				// the kindi-hidden-field class on state) so the card shows 4 fields.
				foreach ( $kindi_fields as $kindi_key => $kindi_field ) {
					if ( in_array( $kindi_key, $kindi_contact, true ) || in_array( $kindi_key, $kindi_address, true ) || 'billing_company' === $kindi_key ) {
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
