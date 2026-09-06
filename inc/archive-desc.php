<?php
/**
 * Category archive bottom description.
 *
 * Adopts the site's ACF "תיאור תחתון לארכיון" term field: adds a native editor
 * on the product-category screen (so it stays editable after ACF is removed),
 * reads the ACF value as a fallback, and renders the text below the product
 * grid on category archives.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Resolve a category's bottom description: native theme meta, then the mapped
 * ACF term meta.
 *
 * @param int $term_id Term ID.
 * @return string
 */
function kindi_archive_desc_value( int $term_id ): string {
	$value = (string) get_term_meta( $term_id, '_kindi_archive_desc', true );
	if ( '' === $value ) {
		$key = (string) kindi_opt( 'acf_key_archive_desc' );
		if ( '' !== $key ) {
			$value = (string) get_term_meta( $term_id, $key, true );
		}
	}
	return $value;
}

/**
 * Render the bottom description on category archives (after the loop).
 *
 * @return void
 */
function kindi_archive_desc_render(): void {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	$html = kindi_archive_desc_value( $term->term_id );
	if ( '' === $html ) {
		return;
	}
	echo '<div class="kindi-archive-desc">' . wp_kses_post( wpautop( $html ) ) . '</div>';
}
add_action( 'woocommerce_after_shop_loop', 'kindi_archive_desc_render', 50 );

/**
 * Field on the "add category" screen.
 *
 * @return void
 */
function kindi_archive_desc_add_field(): void {
	?>
	<div class="form-field term-kindi-archive-desc-wrap">
		<label for="kindi_archive_desc"><?php esc_html_e( 'תיאור תחתון לארכיון', 'kindi' ); ?></label>
		<textarea name="kindi_archive_desc" id="kindi_archive_desc" rows="5"></textarea>
		<p><?php esc_html_e( 'טקסט שיוצג בתחתית עמוד הקטגוריה (טוב ל-SEO).', 'kindi' ); ?></p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'kindi_archive_desc_add_field' );

/**
 * Field on the "edit category" screen (pre-filled, ACF value as fallback).
 *
 * @param WP_Term $term Term.
 * @return void
 */
function kindi_archive_desc_edit_field( $term ): void {
	$value = kindi_archive_desc_value( (int) $term->term_id );
	?>
	<tr class="form-field term-kindi-archive-desc-wrap">
		<th scope="row"><label for="kindi_archive_desc"><?php esc_html_e( 'תיאור תחתון לארכיון', 'kindi' ); ?></label></th>
		<td>
			<textarea name="kindi_archive_desc" id="kindi_archive_desc" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
			<p class="description"><?php esc_html_e( 'טקסט שיוצג בתחתית עמוד הקטגוריה (טוב ל-SEO).', 'kindi' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'kindi_archive_desc_edit_field' );

/**
 * Persist the field (WordPress verifies the term-edit nonce before firing).
 *
 * @param int $term_id Term ID.
 * @return void
 */
function kindi_archive_desc_save( int $term_id ): void {
	if ( ! current_user_can( 'manage_product_terms' ) || ! isset( $_POST['kindi_archive_desc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}
	update_term_meta( $term_id, '_kindi_archive_desc', wp_kses_post( wp_unslash( $_POST['kindi_archive_desc'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
}
add_action( 'created_product_cat', 'kindi_archive_desc_save' );
add_action( 'edited_product_cat', 'kindi_archive_desc_save' );
