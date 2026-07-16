<?php
/**
 * Category notices — a text message attached to a product category that shows
 * beside the add-to-cart button on its products, and in the cart when such a
 * product is present. Optionally cascades to the category's subcategories.
 *
 * Use case: a whole category is temporarily out of supply ("no restock until
 * 1.1.27") and shoppers should see it on the product and again in the cart.
 *
 * Storage: term meta on product_cat — `kindi_cat_notice` (the text) and
 * `kindi_cat_notice_children` ('1' to include subcategories).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * Admin — category edit fields
 * ------------------------------------------------------------------ */

/**
 * Fields on the "add new category" screen.
 *
 * @return void
 */
function kindi_cat_notice_add_fields(): void {
	wp_nonce_field( 'kindi_cat_notice', 'kindi_cat_notice_nonce' );
	?>
	<div class="form-field">
		<label for="kindi_cat_notice"><?php esc_html_e( 'הודעת קטגוריה', 'kindi' ); ?></label>
		<textarea name="kindi_cat_notice" id="kindi_cat_notice" rows="3"></textarea>
		<p class="description"><?php esc_html_e( 'טקסט שיוצג ליד כפתור ההוספה לסל במוצרי הקטגוריה, וגם בסל הקניות. השאירו ריק כדי לא להציג.', 'kindi' ); ?></p>
	</div>
	<div class="form-field">
		<label><input type="checkbox" name="kindi_cat_notice_children" value="1"> <?php esc_html_e( 'החל את ההודעה גם על תת-הקטגוריות', 'kindi' ); ?></label>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'kindi_cat_notice_add_fields' );

/**
 * Fields on the "edit category" screen (table-row layout).
 *
 * @param WP_Term $term The category being edited.
 * @return void
 */
function kindi_cat_notice_edit_fields( $term ): void {
	$text     = (string) get_term_meta( $term->term_id, 'kindi_cat_notice', true );
	$children = '1' === (string) get_term_meta( $term->term_id, 'kindi_cat_notice_children', true );
	wp_nonce_field( 'kindi_cat_notice', 'kindi_cat_notice_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="kindi_cat_notice"><?php esc_html_e( 'הודעת קטגוריה', 'kindi' ); ?></label></th>
		<td>
			<textarea name="kindi_cat_notice" id="kindi_cat_notice" rows="3" cols="50"><?php echo esc_textarea( $text ); ?></textarea>
			<p class="description"><?php esc_html_e( 'טקסט שיוצג ליד כפתור ההוספה לסל במוצרי הקטגוריה, וגם בסל הקניות. השאירו ריק כדי לא להציג.', 'kindi' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
		<th scope="row"><?php esc_html_e( 'תת-קטגוריות', 'kindi' ); ?></th>
		<td><label><input type="checkbox" name="kindi_cat_notice_children" value="1" <?php checked( $children ); ?>> <?php esc_html_e( 'החל את ההודעה גם על תת-הקטגוריות', 'kindi' ); ?></label></td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'kindi_cat_notice_edit_fields' );

/**
 * Persist the notice fields. Guarded by nonce so WooCommerce quick-edit (which
 * carries no nonce and no fields) never wipes the saved values.
 *
 * @param int $term_id The saved term.
 * @return void
 */
function kindi_cat_notice_save( int $term_id ): void {
	if ( ! isset( $_POST['kindi_cat_notice_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kindi_cat_notice_nonce'] ) ), 'kindi_cat_notice' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$text = isset( $_POST['kindi_cat_notice'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kindi_cat_notice'] ) ) : '';
	if ( '' !== $text ) {
		update_term_meta( $term_id, 'kindi_cat_notice', $text );
	} else {
		delete_term_meta( $term_id, 'kindi_cat_notice' );
	}

	if ( ! empty( $_POST['kindi_cat_notice_children'] ) ) {
		update_term_meta( $term_id, 'kindi_cat_notice_children', '1' );
	} else {
		delete_term_meta( $term_id, 'kindi_cat_notice_children' );
	}
}
add_action( 'created_product_cat', 'kindi_cat_notice_save' );
add_action( 'edited_product_cat', 'kindi_cat_notice_save' );

/* ------------------------------------------------------------------ *
 * Resolution
 * ------------------------------------------------------------------ */

/**
 * The notice text + subcategory flag for one category term (per-request cache).
 *
 * @param int $term_id Term ID.
 * @return array{text:string,children:bool}
 */
function kindi_cat_notice_meta( int $term_id ): array {
	static $cache = array();
	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}
	$cache[ $term_id ] = array(
		'text'     => trim( (string) get_term_meta( $term_id, 'kindi_cat_notice', true ) ),
		'children' => '1' === (string) get_term_meta( $term_id, 'kindi_cat_notice_children', true ),
	);
	return $cache[ $term_id ];
}

/**
 * Notice texts attached to a single category: its own notice (always) plus any
 * ancestor whose notice is flagged to include subcategories.
 *
 * @param int $term_id Category term ID.
 * @return string[] Notice texts, keyed by the term they came from.
 */
function kindi_term_category_notices( int $term_id ): array {
	$notices = array();
	$own     = kindi_cat_notice_meta( $term_id );
	if ( '' !== $own['text'] ) {
		$notices[ $term_id ] = $own['text']; // Own notice always applies.
	}
	foreach ( get_ancestors( $term_id, 'product_cat', 'taxonomy' ) as $aid ) {
		$anc = kindi_cat_notice_meta( (int) $aid );
		if ( '' !== $anc['text'] && $anc['children'] ) {
			$notices[ (int) $aid ] = $anc['text']; // Cascades to subcategories.
		}
	}
	return $notices;
}

/**
 * Unique notice texts that apply to a product, across all of its categories.
 *
 * @param int $product_id Product ID.
 * @return string[] Unique notice texts.
 */
function kindi_product_category_notices( int $product_id ): array {
	if ( ! function_exists( 'wc_get_product_term_ids' ) ) {
		return array();
	}
	$term_ids = wc_get_product_term_ids( $product_id, 'product_cat' );
	if ( empty( $term_ids ) ) {
		return array();
	}

	$notices = array(); // Keyed by term ID to de-duplicate shared parents.
	foreach ( $term_ids as $tid ) {
		$notices += kindi_term_category_notices( (int) $tid );
	}
	return array_values( array_unique( $notices ) );
}

/**
 * Render a notice box for a set of texts.
 *
 * @param string[] $notices Notice texts.
 * @param string   $extra   Extra class on the wrapper.
 * @return void
 */
function kindi_cat_notice_box( array $notices, string $extra = '' ): void {
	if ( ! $notices ) {
		return;
	}
	printf( '<div class="kindi-catnotice %s" role="note">', esc_attr( $extra ) );
	echo '<span class="kindi-catnotice__ic">' . kindi_icon( 'info', 'kindi-icon--md' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-catnotice__txt">';
	foreach ( $notices as $text ) {
		echo '<p>' . nl2br( esc_html( $text ) ) . '</p>';
	}
	echo '</div></div>';
}

/* ------------------------------------------------------------------ *
 * Front-end display
 * ------------------------------------------------------------------ */

/**
 * Product page — notice just above the add-to-cart button.
 *
 * @return void
 */
function kindi_pdp_category_notice(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	kindi_cat_notice_box( kindi_product_category_notices( $product->get_id() ) );
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_category_notice', 29 );

/**
 * Category archive — the current category's notice above and below the grid.
 *
 * @return void
 */
function kindi_archive_category_notice(): void {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	kindi_cat_notice_box( array_values( kindi_term_category_notices( $term->term_id ) ), 'kindi-catnotice--archive' );
}
add_action( 'woocommerce_before_shop_loop', 'kindi_archive_category_notice', 5 );
add_action( 'woocommerce_after_shop_loop', 'kindi_archive_category_notice', 5 );

/**
 * Cart page — one notice for every distinct message among the cart's products.
 *
 * @return void
 */
function kindi_cart_category_notice(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$notices = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$pid = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : 0;
		if ( ! $pid ) {
			continue;
		}
		foreach ( kindi_product_category_notices( $pid ) as $text ) {
			$notices[ md5( $text ) ] = $text; // De-duplicate identical messages.
		}
	}
	kindi_cat_notice_box( array_values( $notices ), 'kindi-catnotice--cart' );
}
add_action( 'woocommerce_before_cart', 'kindi_cart_category_notice', 6 );
