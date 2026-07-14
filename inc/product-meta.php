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

	// "גיל מומלץ" — fixed age-band tags (the same five bands the homepage tiles
	// and the archive filter use), replacing the old free-text field. Multiple
	// bands per product are welcome ("3-5" + "6-8" = suits 3-8).
	if ( function_exists( 'kindi_age_bands' ) ) {
		global $post;
		$chosen = $post instanceof WP_Post ? (array) get_post_meta( $post->ID, '_kindi_age_band' ) : array();
		$legacy = $post instanceof WP_Post ? (string) get_post_meta( $post->ID, '_kindi_age', true ) : '';
		echo '<p class="form-field"><label>' . esc_html__( 'גיל מומלץ', 'kindi' ) . '</label><span style="display:inline-flex;flex-wrap:wrap;gap:10px 16px">';
		foreach ( kindi_age_bands() as $band_key => $band ) {
			printf(
				'<label style="margin:0;display:inline-flex;align-items:center;gap:4px;float:none;width:auto"><input type="checkbox" name="_kindi_age_bands[]" value="%s"%s style="margin:0"> %s</label>',
				esc_attr( $band_key ),
				checked( in_array( $band_key, $chosen, true ), true, false ),
				esc_html( $band['label'] )
			);
		}
		echo '</span>';
		if ( ! $chosen && '' !== $legacy ) {
			echo '<span class="description" style="display:block;margin-top:4px">' . esc_html( sprintf( /* translators: %s: legacy free-text age. */ __( 'ערך ישן (טקסט חופשי): "%s" — סמנו טווחים ושמרו כדי לעבור לתגיות.', 'kindi' ), $legacy ) ) . '</span>';
		}
		echo '</p>';
	}

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

	// Age-band tags: one meta row per band (indexable equality queries), plus a
	// regenerated display label in the legacy _kindi_age key (the product page
	// keeps reading it) and the numeric _kindi_age_min mirror used by the
	// gift-finder range logic.
	if ( function_exists( 'kindi_age_bands' ) ) {
		$bands  = kindi_age_bands();
		$posted = isset( $_POST['_kindi_age_bands'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['_kindi_age_bands'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$posted = array_values( array_intersect( $posted, array_keys( $bands ) ) );

		delete_post_meta( $product->get_id(), '_kindi_age_band' );
		$labels = array();
		$min    = null;
		foreach ( $posted as $band_key ) {
			add_post_meta( $product->get_id(), '_kindi_age_band', $band_key );
			$labels[] = '13plus' === $band_key ? '13+' : $band_key;
			$min      = null === $min ? $bands[ $band_key ]['min'] : min( $min, $bands[ $band_key ]['min'] );
		}
		if ( $posted ) {
			$product->update_meta_data( '_kindi_age', implode( ', ', $labels ) );
			$product->update_meta_data( '_kindi_age_min', (string) $min );
		}
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
