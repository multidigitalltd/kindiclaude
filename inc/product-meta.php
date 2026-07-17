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
	// Only the CONTENT fields remain here — the product PROPERTIES (age, brand,
	// pieces, players, play time, skills) are WooCommerce attributes now, edited
	// in the product's "תכונות" tab (see inc/attributes.php).
	return array(
		'_kindi_highlights'    => array( 'label' => 'נקודות בולטות', 'type' => 'textarea', 'placeholder' => 'שורה אחת לכל נקודה' ),
		'_kindi_in_box'        => array( 'label' => 'מה בקופסה', 'type' => 'textarea', 'placeholder' => 'שורה אחת לכל פריט' ),
		'_kindi_product_video' => array( 'label' => 'סרטון מוצר', 'type' => 'url', 'placeholder' => 'קישור ליוטיוב/וימאו או לקובץ MP4' ),
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
		if ( '_kindi_product_video' === $key ) {
			continue; // Rendered below with a media-library picker.
		}
		if ( 'textarea' === $field['type'] ) {
			woocommerce_wp_textarea_input( array( 'id' => $key, 'label' => $field['label'], 'placeholder' => $field['placeholder'] ) );
		} else {
			woocommerce_wp_text_input( array( 'id' => $key, 'label' => $field['label'], 'placeholder' => $field['placeholder'] ) );
		}
	}

	// Product video: a link field, plus a media-library picker for a self-hosted
	// file. The note steers editors to a streaming embed (YouTube/Vimeo) for a
	// fast page load, since a self-hosted file is heavier.
	woocommerce_wp_text_input(
		array(
			'id'          => '_kindi_product_video',
			'label'       => __( 'סרטון מוצר', 'kindi' ),
			'placeholder' => 'https://youtu.be/…',
			'description' => __( 'כדי לאפשר טעינה מהירה של העמוד לגולשים — ישנה עדיפות לשימוש בהטמעה משירות הזרמת מדיה כמו יוטיוב/וימאו, על פני העלאת קובץ וידאו לאתר. ניתן להזין קישור, או לבחור/להעלות קובץ מספריית המדיה.', 'kindi' ),
		)
	);
	echo '<p class="form-field">'
		. '<button type="button" class="button kindi-video-pick" data-target="_kindi_product_video">' . esc_html__( 'בחירה/העלאה מספריית המדיה', 'kindi' ) . '</button> '
		. '<button type="button" class="button-link kindi-video-clear" data-target="_kindi_product_video">' . esc_html__( 'ניקוי', 'kindi' ) . '</button>'
		. '</p>';

	echo '</div>';
}
add_action( 'woocommerce_product_options_general_product_data', 'kindi_product_meta_fields' );

/**
 * Media-library picker wiring for the product-video field (product edit screen).
 *
 * @param string $hook Current admin page.
 * @return void
 */
function kindi_product_video_admin_assets( string $hook ): void {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_media();

	$js = '(function(){function wire(){'
		. 'document.querySelectorAll(".kindi-video-pick").forEach(function(btn){if(btn.dataset.wired)return;btn.dataset.wired="1";btn.addEventListener("click",function(e){e.preventDefault();if(typeof wp==="undefined"||!wp.media)return;var input=document.getElementById(btn.getAttribute("data-target"));var frame=wp.media({title:"בחירת סרטון",library:{type:"video"},button:{text:"שימוש בסרטון"},multiple:false});frame.on("select",function(){var a=frame.state().get("selection").first().toJSON();if(input){input.value=a.url;}});frame.open();});});'
		. 'document.querySelectorAll(".kindi-video-clear").forEach(function(btn){if(btn.dataset.wired)return;btn.dataset.wired="1";btn.addEventListener("click",function(e){e.preventDefault();var input=document.getElementById(btn.getAttribute("data-target"));if(input){input.value="";}});});'
		. '}if(document.readyState!=="loading"){wire();}else{document.addEventListener("DOMContentLoaded",wire);}})();';
	wp_add_inline_script( 'media-editor', $js );
}
add_action( 'admin_enqueue_scripts', 'kindi_product_video_admin_assets' );

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
		if ( 'textarea' === $field['type'] ) {
			$clean = sanitize_textarea_field( $raw );
		} elseif ( 'url' === $field['type'] ) {
			$clean = esc_url_raw( trim( (string) $raw ) );
		} else {
			$clean = sanitize_text_field( $raw );
		}
		$product->update_meta_data( $key, $clean );
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
	// Attribute-first: the property lives as a WooCommerce attribute when the
	// product carries one; the legacy meta below covers unmigrated products.
	if ( function_exists( 'kindi_attr_tax_for' ) ) {
		$tax = kindi_attr_tax_for( $key );
		if ( '' !== $tax && taxonomy_exists( $tax ) ) {
			$terms = get_the_terms( $product->get_id(), $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$names = wp_list_pluck( $terms, 'name' );
				usort( $names, 'strnatcmp' );
				return implode( ', ', $names );
			}
		}
	}

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
	if ( function_exists( 'kindi_attr_tax_for' ) ) {
		$tax = kindi_attr_tax_for( 'skills' );
		if ( '' !== $tax && taxonomy_exists( $tax ) ) {
			$terms = get_the_terms( $product->get_id(), $tax );
			if ( $terms && ! is_wp_error( $terms ) ) {
				return array_values( wp_list_pluck( $terms, 'name' ) );
			}
		}
	}
	$raw = kindi_pmeta( $product, 'skills' );
	if ( '' === $raw ) {
		return array();
	}
	$parts = preg_split( '/\r\n|\r|\n|,|،|;|\|/u', $raw ) ?: array();

	return array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
}

// Single-product presentation (highlights, facts, in-box, etc.) lives in
// inc/single-product.php — this file is the data layer only.
