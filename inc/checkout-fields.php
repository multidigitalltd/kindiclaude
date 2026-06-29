<?php
/**
 * Checkout field configuration — labels, placeholders, required state and order
 * for the billing fields, plus two notes fields: the native order note relabelled
 * as "picker notes" and a custom "shipping notes" field printed for the courier.
 *
 * Bails automatically when a checkout-field-editor plugin is active, so the theme
 * never fights a plugin for ownership of the same fields. Can also be disabled
 * with the `kindi_manage_checkout_fields` filter.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Should the theme manage the checkout fields? False when a known
 * checkout-field-editor plugin owns them, or when disabled via filter.
 *
 * @return bool
 */
function kindi_manages_checkout_fields(): bool {
	$plugin_active = class_exists( 'WC_Checkout_Field_Editor' )
		|| class_exists( 'THWCFD' )
		|| function_exists( 'thwcfd_is_section_field' )
		|| defined( 'WOOCCM_PLUGIN_FILE' );

	return (bool) apply_filters( 'kindi_manage_checkout_fields', ! $plugin_active );
}

/**
 * Apply the desired labels / placeholders / required state / order to the
 * checkout fields.
 *
 * Note on billing_state: its required state is governed by WooCommerce's
 * per-country locale (Israel, the primary market, has no states), so we set the
 * label only and leave required/visibility to WooCommerce — forcing it required
 * would break checkout for stateless countries.
 *
 * @param array<string,array<string,mixed>> $fields Checkout fields.
 * @return array<string,array<string,mixed>>
 */
function kindi_checkout_fields( array $fields ): array {
	if ( ! kindi_manages_checkout_fields() || empty( $fields['billing'] ) ) {
		return $fields;
	}

	$billing = array(
		'billing_first_name' => array( 'label' => 'שם פרטי', 'required' => true, 'priority' => 10, 'class' => array( 'form-row-first' ) ),
		'billing_last_name'  => array( 'label' => 'שם משפחה', 'required' => true, 'priority' => 20, 'class' => array( 'form-row-last' ) ),
		'billing_company'    => array( 'label' => 'שם החברה', 'required' => false, 'priority' => 30, 'class' => array( 'form-row-wide' ) ),
		'billing_country'    => array( 'label' => 'מדינה / אזור', 'required' => true, 'priority' => 40, 'class' => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ) ),
		'billing_state'      => array( 'label' => 'מדינה / מחוז', 'priority' => 50, 'class' => array( 'form-row-wide', 'address-field' ) ),
		'billing_address_1'  => array( 'label' => 'כתובת רחוב', 'required' => true, 'priority' => 60, 'placeholder' => 'מספר בית ושם רחוב', 'class' => array( 'form-row-wide', 'address-field' ) ),
		'billing_address_2'  => array( 'label' => 'דירה, סוויטה, יחידה וכו׳', 'required' => false, 'priority' => 70, 'placeholder' => 'דירה, סוויטה, יחידה וכו׳ (אופציונלי)', 'class' => array( 'form-row-wide', 'address-field' ) ),
		'billing_city'       => array( 'label' => 'עיר', 'required' => true, 'priority' => 80, 'class' => array( 'form-row-wide', 'address-field' ) ),
		'billing_phone'      => array( 'label' => 'טלפון', 'required' => true, 'priority' => 90, 'type' => 'tel', 'class' => array( 'form-row-first' ) ),
		'billing_email'      => array( 'label' => 'כתובת אימייל', 'required' => true, 'priority' => 100, 'type' => 'email', 'class' => array( 'form-row-last' ) ),
	);

	foreach ( $billing as $key => $props ) {
		if ( ! isset( $fields['billing'][ $key ] ) ) {
			continue;
		}
		foreach ( $props as $prop => $value ) {
			$fields['billing'][ $key ][ $prop ] = $value;
		}
	}

	// Native order note → "picker notes".
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'הערות למלקט';
		$fields['order']['order_comments']['placeholder']  = 'הערות אלו יופיעו למלקט — אם יש לכם הערות מיוחדות למוצרים שהזמנתם, אנא ציינו כאן';
		$fields['order']['order_comments']['priority']     = 10;
	}

	// Custom "shipping notes" printed for the courier on the package.
	$fields['order']['order_information_customer'] = array(
		'type'        => 'textarea',
		'label'       => 'הערות למשלוח',
		'required'    => false,
		'priority'    => 20,
		'placeholder' => 'הערות מיוחדות בנוגע למשלוח — ההערה תופיע לשליח על גבי החבילה',
		'class'       => array( 'form-row-wide', 'notes' ),
	);

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'kindi_checkout_fields' );

/**
 * Persist the custom shipping note to the order. $data already holds the
 * sanitised, posted checkout fields (WooCommerce verifies its own nonce before
 * this runs), so no direct $_POST access is needed.
 *
 * @param WC_Order             $order Order being created.
 * @param array<string,mixed>  $data  Posted checkout data.
 * @return void
 */
function kindi_save_shipping_note( $order, array $data ): void {
	if ( ! empty( $data['order_information_customer'] ) ) {
		$order->update_meta_data( '_order_information_customer', sanitize_textarea_field( (string) $data['order_information_customer'] ) );
	}
}
add_action( 'woocommerce_checkout_create_order', 'kindi_save_shipping_note', 10, 2 );

/**
 * Show the shipping note on the admin order screen.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function kindi_admin_shipping_note( $order ): void {
	$note = $order instanceof WC_Order ? (string) $order->get_meta( '_order_information_customer' ) : '';
	if ( '' !== $note ) {
		echo '<p><strong>' . esc_html__( 'הערות למשלוח', 'kindi' ) . ':</strong><br>' . nl2br( esc_html( $note ) ) . '</p>';
	}
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'kindi_admin_shipping_note' );

/**
 * Include the shipping note in order emails.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Whether the email is for the admin.
 * @param bool     $plain_text    Whether the email is plain text.
 * @return void
 */
function kindi_email_shipping_note( $order, $sent_to_admin = false, $plain_text = false ): void {
	$note = $order instanceof WC_Order ? (string) $order->get_meta( '_order_information_customer' ) : '';
	if ( '' === $note ) {
		return;
	}
	$label = __( 'הערות למשלוח', 'kindi' );
	if ( $plain_text ) {
		echo "\n" . esc_html( $label ) . ': ' . esc_html( $note ) . "\n";
	} else {
		echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $note ) . '</p>';
	}
}
add_action( 'woocommerce_email_order_meta', 'kindi_email_shipping_note', 10, 3 );
