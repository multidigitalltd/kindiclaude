<?php
/**
 * Product meta — toy-specific custom fields (age, brand, pieces, players, play
 * time, highlights, "in the box") shown on the product card and product page.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Field definitions: key => [label, type, placeholder].
 *
 * @return array<string,array{label:string,type:string,placeholder:string}>
 */
function kindi_product_meta_defs(): array {
	return array(
		'_kindi_age'         => array( 'label' => 'גיל מומלץ', 'type' => 'text', 'placeholder' => '3+' ),
		'_kindi_brand_label' => array( 'label' => 'מותג', 'type' => 'text', 'placeholder' => 'LEGO' ),
		'_kindi_pieces'      => array( 'label' => 'מספר חלקים', 'type' => 'text', 'placeholder' => '1,036' ),
		'_kindi_players'     => array( 'label' => 'מספר שחקנים', 'type' => 'text', 'placeholder' => '1-4' ),
		'_kindi_play_time'   => array( 'label' => 'זמן משחק/בנייה', 'type' => 'text', 'placeholder' => '~45 דקות' ),
		'_kindi_skills'      => array( 'label' => 'מיומנויות', 'type' => 'textarea', 'placeholder' => 'שורה אחת (או מופרד בפסיקים) לכל מיומנות' ),
		'_kindi_highlights'  => array( 'label' => 'נקודות בולטות', 'type' => 'textarea', 'placeholder' => 'שורה אחת לכל נקודה' ),
		'_kindi_in_box'      => array( 'label' => 'מה בקופסה', 'type' => 'textarea', 'placeholder' => 'שורה אחת לכל פריט' ),
	);
}

/**
 * Render the fields in the product "General" data tab.
 *
 * @return void
 */
function kindi_product_meta_fields(): void {
	echo '<div class="options_group">';
	foreach ( kindi_product_meta_defs() as $key => $field ) {
		if ( 'textarea' === $field['type'] ) {
			woocommerce_wp_textarea_input( array( 'id' => $key, 'label' => $field['label'], 'placeholder' => $field['placeholder'] ) );
		} else {
			woocommerce_wp_text_input( array( 'id' => $key, 'label' => $field['label'], 'placeholder' => $field['placeholder'] ) );
		}
	}
	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'kindi_product_meta_fields' );

/**
 * Persist the custom fields (WC handles the save nonce).
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_save_product_meta( $product ): void {
	foreach ( kindi_product_meta_defs() as $key => $field ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product->update_meta_data( $key, 'textarea' === $field['type'] ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw ) );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'kindi_save_product_meta' );

/**
 * Get a product meta value.
 *
 * @param WC_Product $product Product.
 * @param string     $key     Key without the _kindi_ prefix.
 * @return string
 */
function kindi_pmeta( $product, string $key ): string {
	$value = (string) $product->get_meta( '_kindi_' . $key );
	if ( '' === $value && function_exists( 'kindi_resolve_field' ) ) {
		$value = kindi_resolve_field( $product->get_id(), $key );
	}
	return $value;
}

/**
 * Get a textarea product meta value split into lines.
 *
 * @param WC_Product $product Product.
 * @param string     $key     Key without the _kindi_ prefix.
 * @return array<int,string>
 */
function kindi_pmeta_lines( $product, string $key ): array {
	$value = kindi_pmeta( $product, $key );
	return $value ? array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $value ) ?: array() ), 'strlen' ) ) : array();
}

/**
 * Skills as a clean list. Accepts newline- or comma-separated input (the latter
 * is common for ACF select/text fields).
 *
 * @param WC_Product $product Product.
 * @return array<int,string>
 */
function kindi_skill_items( $product ): array {
	$raw = kindi_pmeta( $product, 'skills' );
	if ( '' === $raw ) {
		return array();
	}
	$parts = preg_split( '/\r\n|\r|\n|,|،|;|\|/u', $raw ) ?: array();

	return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
}

// Single-product presentation (highlights, facts, in-box, etc.) lives in
// inc/single-product.php — this file is the data layer only.
