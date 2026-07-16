<?php
/**
 * Category notices — a text message attached to a product category that shows
 * beside the add-to-cart button on its products, on the category archive (top
 * and bottom of the grid), and in the cart when such a product is present.
 * Optionally cascades to the category's subcategories.
 *
 * Use case: a whole category is temporarily out of supply ("no restock until
 * 1.1.27") and shoppers should see it wherever they meet the product.
 *
 * Managed centrally from the Kindi panel (tab "הודעות קטגוריה"). Storage is one
 * option row per rule: category ID, text, and an include-subcategories flag.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * Admin — Kindi panel tab + repeater field
 * ------------------------------------------------------------------ */

/**
 * Register the "הודעות קטגוריה" tab in the Kindi settings panel.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_cat_notices_settings_tab( array $tabs ): array {
	$tabs['catnotices'] = array(
		'label'    => 'הודעות קטגוריה',
		'sections' => array(
			'הודעות זמינות / מלאי לפי קטגוריה' => array(
				'cat_notices' => array(
					'type'  => 'cat_notices',
					'label' => 'הודעות',
					'help'  => 'בחרו קטגוריה והזינו טקסט. ההודעה תוצג ליד כפתור ההוספה לסל, בראש ובסוף ארכיון הקטגוריה, ובסל הקניות. סמנו "כולל תת-קטגוריות" כדי להחיל גם על הקטגוריות שמתחת.',
				),
			),
		),
	);
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_cat_notices_settings_tab' );

/**
 * Render the repeater field in the panel (dispatched from the settings render).
 *
 * @param string $key   Field key (always 'cat_notices').
 * @param mixed  $value Stored rows.
 * @return void
 */
function kindi_cat_notices_field_render( string $key, $value ): void {
	$rows = is_array( $value ) ? $value : array();
	$cats = function_exists( 'kindi_admin_product_cats' ) ? kindi_admin_product_cats() : array();

	// Category <option> markup reused by every row and the JS template.
	$options = '<option value="0">— בחרו קטגוריה —</option>';
	foreach ( $cats as $tid => $tname ) {
		$options .= '<option value="' . (int) $tid . '">' . esc_html( $tname ) . '</option>';
	}

	// Marker so an emptied list (all rows removed) still saves.
	echo '<input type="hidden" name="kindi__present[' . esc_attr( $key ) . ']" value="1">';
	echo '<div class="kindi-cn" data-kindi-cn>';
	echo '<div class="kindi-cn__rows" data-kindi-cn-rows>';

	$i = 0;
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$cat      = isset( $row['cat'] ) ? (int) $row['cat'] : 0;
		$text     = isset( $row['text'] ) ? (string) $row['text'] : '';
		$children = ! empty( $row['children'] );
		echo kindi_cat_notices_row_html( $key, $i, $options, $cat, $text, $children ); // phpcs:ignore WordPress.Security.EscapeOutput
		$i++;
	}

	echo '</div>';
	echo '<p><button type="button" class="button" data-kindi-cn-add>' . esc_html__( '+ הוספת הודעה', 'kindi' ) . '</button></p>';

	// Prototype row for JS cloning (index placeholder __i__).
	echo '<template data-kindi-cn-tpl>' . kindi_cat_notices_row_html( $key, '__i__', $options, 0, '', false ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';

	kindi_cat_notices_field_assets();
}

/**
 * Markup for a single repeater row. All dynamic parts are pre-escaped.
 *
 * @param string     $key      Field key.
 * @param int|string $i        Row index (or '__i__' placeholder).
 * @param string     $options  Pre-built, escaped <option> list.
 * @param int        $cat      Selected category ID.
 * @param string     $text     Notice text.
 * @param bool       $children Include subcategories.
 * @return string
 */
function kindi_cat_notices_row_html( string $key, $i, string $options, int $cat, string $text, bool $children ): string {
	$name  = 'kindi[' . $key . '][' . $i . ']';
	// Inject selected= into the shared options string for this row's category.
	$opts  = $cat > 0
		? str_replace( 'value="' . $cat . '"', 'value="' . $cat . '" selected', $options )
		: $options;
	$check = $children ? ' checked' : '';

	return '<div class="kindi-cn__row" data-kindi-cn-row>'
		. '<select name="' . esc_attr( $name ) . '[cat]" class="kindi-cn__cat">' . $opts . '</select>'
		. '<textarea name="' . esc_attr( $name ) . '[text]" rows="2" class="kindi-cn__text" dir="rtl" placeholder="' . esc_attr__( 'טקסט ההודעה…', 'kindi' ) . '">' . esc_textarea( $text ) . '</textarea>'
		. '<label class="kindi-cn__kids"><input type="checkbox" name="' . esc_attr( $name ) . '[children]" value="1"' . $check . '> ' . esc_html__( 'כולל תת-קטגוריות', 'kindi' ) . '</label>'
		. '<button type="button" class="button-link kindi-cn__del" data-kindi-cn-del aria-label="' . esc_attr__( 'הסרה', 'kindi' ) . '">✕</button>'
		. '</div>';
}

/**
 * Inline styles + repeater JS (printed once).
 *
 * @return void
 */
function kindi_cat_notices_field_assets(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
	<style>
	.kindi-cn__row{display:flex;align-items:flex-start;gap:10px;padding:10px;margin-bottom:8px;background:#fff;border:1px solid #dcdcde;border-radius:8px;max-width:820px}
	.kindi-cn__cat{flex:0 0 220px}
	.kindi-cn__text{flex:1 1 auto;min-width:200px}
	.kindi-cn__kids{flex:0 0 auto;white-space:nowrap;padding-top:6px;font-size:13px}
	.kindi-cn__del{flex:0 0 auto;color:#b32d2e;text-decoration:none;font-size:16px;line-height:1;padding-top:6px}
	</style>
	<script>
	( function () {
		function wire() {
			var box = document.querySelector( '[data-kindi-cn]' );
			if ( ! box || box.dataset.wired ) { return; }
			box.dataset.wired = '1';
			var rows = box.querySelector( '[data-kindi-cn-rows]' );
			var tpl = box.querySelector( '[data-kindi-cn-tpl]' );
			var n = Date.now();
			box.addEventListener( 'click', function ( e ) {
				if ( e.target.closest( '[data-kindi-cn-add]' ) ) {
					e.preventDefault();
					var html = tpl.innerHTML.replace( /__i__/g, 'n' + ( n++ ) );
					var tmp = document.createElement( 'div' );
					tmp.innerHTML = html;
					rows.appendChild( tmp.firstElementChild );
				} else if ( e.target.closest( '[data-kindi-cn-del]' ) ) {
					e.preventDefault();
					var row = e.target.closest( '[data-kindi-cn-row]' );
					if ( row ) { row.remove(); }
				}
			} );
		}
		if ( 'loading' !== document.readyState ) { wire(); } else { document.addEventListener( 'DOMContentLoaded', wire ); }
	}() );
	</script>
	<?php
}

/**
 * Sanitise the repeater rows on save. Empty rows (no category or no text) are
 * dropped.
 *
 * @param array<int,mixed> $rows Raw posted rows.
 * @return array<int,array{cat:int,text:string,children:string}>
 */
function kindi_sanitize_cat_notices( array $rows ): array {
	$out = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$cat  = isset( $row['cat'] ) ? absint( $row['cat'] ) : 0;
		$text = isset( $row['text'] ) ? sanitize_textarea_field( (string) $row['text'] ) : '';
		if ( ! $cat || '' === trim( $text ) ) {
			continue;
		}
		$out[] = array(
			'cat'      => $cat,
			'text'     => $text,
			'children' => empty( $row['children'] ) ? '' : '1',
		);
	}
	return $out;
}

/**
 * One-time carry-over: earlier versions stored notices as product_cat term meta
 * (edited on the category screen). Move any such values into the panel option
 * and clean up the old meta. Self-terminating via a flag.
 *
 * @return void
 */
function kindi_cat_notice_migrate(): void {
	if ( ! is_admin() || get_option( 'kindi_catnotice_migrated' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'meta_key'   => 'kindi_cat_notice', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'fields'     => 'ids',
		)
	);
	if ( is_wp_error( $terms ) ) {
		return;
	}

	if ( $terms ) {
		$opts = get_option( 'kindi_options', array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
		$rows     = ( isset( $opts['cat_notices'] ) && is_array( $opts['cat_notices'] ) ) ? $opts['cat_notices'] : array();
		$existing = array();
		foreach ( $rows as $r ) {
			if ( isset( $r['cat'] ) ) {
				$existing[ (int) $r['cat'] ] = true;
			}
		}
		foreach ( $terms as $tid ) {
			$tid  = (int) $tid;
			$text = trim( (string) get_term_meta( $tid, 'kindi_cat_notice', true ) );
			if ( '' !== $text && empty( $existing[ $tid ] ) ) {
				$rows[] = array(
					'cat'      => $tid,
					'text'     => $text,
					'children' => '1' === (string) get_term_meta( $tid, 'kindi_cat_notice_children', true ) ? '1' : '',
				);
			}
			delete_term_meta( $tid, 'kindi_cat_notice' );
			delete_term_meta( $tid, 'kindi_cat_notice_children' );
		}
		$opts['cat_notices'] = $rows;
		update_option( 'kindi_options', $opts );
	}

	update_option( 'kindi_catnotice_migrated', 1, false );
}
add_action( 'admin_init', 'kindi_cat_notice_migrate' );

/* ------------------------------------------------------------------ *
 * Resolution
 * ------------------------------------------------------------------ */

/**
 * Lookup of category ID => notice, built once per request from the option.
 *
 * @return array<int,array{text:string,children:bool}>
 */
function kindi_cat_notice_lookup(): array {
	static $lookup = null;
	if ( null !== $lookup ) {
		return $lookup;
	}
	$lookup = array();
	$rows   = function_exists( 'kindi_opt' ) ? kindi_opt( 'cat_notices' ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$cat  = isset( $row['cat'] ) ? (int) $row['cat'] : 0;
			$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			if ( $cat > 0 && '' !== $text ) {
				$lookup[ $cat ] = array( 'text' => $text, 'children' => ! empty( $row['children'] ) );
			}
		}
	}
	return $lookup;
}

/**
 * Notice texts attached to a single category: its own notice (always) plus any
 * ancestor whose notice is flagged to include subcategories.
 *
 * @param int $term_id Category term ID.
 * @return array<int,string> Notice texts, keyed by the term they came from.
 */
function kindi_term_category_notices( int $term_id ): array {
	$lookup  = kindi_cat_notice_lookup();
	$notices = array();

	if ( isset( $lookup[ $term_id ] ) ) {
		$notices[ $term_id ] = $lookup[ $term_id ]['text']; // Own notice always applies.
	}
	foreach ( get_ancestors( $term_id, 'product_cat', 'taxonomy' ) as $aid ) {
		$aid = (int) $aid;
		if ( isset( $lookup[ $aid ] ) && $lookup[ $aid ]['children'] ) {
			$notices[ $aid ] = $lookup[ $aid ]['text']; // Cascades to subcategories.
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
	if ( ! function_exists( 'wc_get_product_term_ids' ) || ! kindi_cat_notice_lookup() ) {
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
