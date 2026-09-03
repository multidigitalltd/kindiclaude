<?php
/**
 * Site notices — a text message shown by display condition, managed from the
 * Kindi panel (tab "הודעות קטגוריה").
 *
 * Conditions per row:
 * - Product categories (multi-select): shows beside the add-to-cart button on
 *   their products, on the category archives (top and bottom of the grid) and
 *   in the cart when such a product is present; optionally cascades to
 *   subcategories.
 * - Specific pages (multi-select): shows at the top of each page's content.
 * - Shop / cart / checkout: shows at the top of that view for everyone.
 *
 * Rows are stored with a `type` field plus `cats`/`pages` id arrays; legacy
 * rows (no type, singular `cat`/`page`) still resolve via kindi_cn_row_ids(),
 * so existing notices keep working. New condition types slot in by extending
 * kindi_cn_types() + one lookup.
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
			'הודעות לפי תנאי תצוגה' => array(
				'cat_notices' => array(
					'type'  => 'cat_notices',
					'label' => 'הודעות',
					'help'  => 'בחרו תנאי תצוגה והזינו טקסט. קטגוריה: אפשר לסמן כמה קטגוריות לאותה הודעה — תוצג ליד כפתור ההוספה לסל, בראש ובסוף ארכיון הקטגוריה ובסל (אפשר לכלול תת-קטגוריות). עמוד: אפשר לסמן כמה עמודים — תוצג בראש תוכן העמוד. עמוד החנות / סל הקניות / עמוד התשלום: בראש אותו עמוד — לכל הגולשים.',
				),
			),
		),
	);
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_cat_notices_settings_tab' );

/**
 * Display-condition types (key => label). Adding a condition = a new entry
 * here + a render hook; 'cat' and 'page' also carry a target checklist.
 *
 * @return array<string,string>
 */
function kindi_cn_types(): array {
	return array(
		'cat'      => __( 'קטגוריה', 'kindi' ),
		'page'     => __( 'עמוד', 'kindi' ),
		'shop'     => __( 'עמוד החנות', 'kindi' ),
		'cart'     => __( 'סל הקניות', 'kindi' ),
		'checkout' => __( 'עמוד התשלום', 'kindi' ),
	);
}

/**
 * Pages for the condition select (id => title).
 *
 * @return array<int,string>
 */
function kindi_cn_admin_pages(): array {
	$out = array();
	foreach ( get_pages( array( 'sort_column' => 'post_title', 'number' => 300 ) ) as $page ) {
		$out[ (int) $page->ID ] = (string) $page->post_title;
	}
	return $out;
}

/**
 * Render the repeater field in the panel (dispatched from the settings render).
 *
 * @param string $key   Field key (always 'cat_notices').
 * @param mixed  $value Stored rows.
 * @return void
 */
function kindi_cat_notices_field_render( string $key, $value ): void {
	$rows  = is_array( $value ) ? $value : array();
	$cats  = function_exists( 'kindi_admin_product_cats' ) ? kindi_admin_product_cats() : array();
	$pages = kindi_cn_admin_pages();

	// Marker so an emptied list (all rows removed) still saves.
	echo '<input type="hidden" name="kindi__present[' . esc_attr( $key ) . ']" value="1">';
	echo '<div class="kindi-cn" data-kindi-cn>';
	echo '<div class="kindi-cn__rows" data-kindi-cn-rows>';

	$i = 0;
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		echo kindi_cat_notices_row_html( $key, $i, $cats, $pages, $row ); // phpcs:ignore WordPress.Security.EscapeOutput
		$i++;
	}

	echo '</div>';
	echo '<p><button type="button" class="button" data-kindi-cn-add>' . esc_html__( '+ הוספת הודעה', 'kindi' ) . '</button></p>';

	// Prototype row for JS cloning (index placeholder __i__).
	echo '<template data-kindi-cn-tpl>' . kindi_cat_notices_row_html( $key, '__i__', $cats, $pages, array() ) . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';

	kindi_cat_notices_field_assets();
}

/**
 * A searchable checkbox list for a row's targets (categories or pages).
 *
 * @param string             $name    Input name (submits as an array).
 * @param string             $class   Wrapper class hook (kindi-cn__cat / __page).
 * @param array<int,string>  $items   id => label.
 * @param int[]              $chosen  Checked ids.
 * @param string             $filter_placeholder Search placeholder.
 * @return string
 */
function kindi_cn_checklist_html( string $name, string $class, array $items, array $chosen, string $filter_placeholder ): string {
	$html  = '<div class="' . esc_attr( $class ) . ' kindi-cn__list">';
	$html .= '<input type="search" class="kindi-cn__filter" placeholder="' . esc_attr( $filter_placeholder ) . '" autocomplete="off">';
	$html .= '<div class="kindi-cn__opts">';
	foreach ( $items as $id => $label ) {
		$html .= '<label><input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . (int) $id . '"'
			. checked( in_array( (int) $id, $chosen, true ), true, false ) . '> ' . esc_html( $label ) . '</label>';
	}
	$html .= '</div></div>';
	return $html;
}

/**
 * Legacy-tolerant read of a row's target ids: the plural array when present,
 * else the old single value.
 *
 * @param array<string,mixed> $row      Stored row.
 * @param string              $plural   Array key ('cats'/'pages').
 * @param string              $singular Legacy key ('cat'/'page').
 * @return int[]
 */
function kindi_cn_row_ids( array $row, string $plural, string $singular ): array {
	if ( isset( $row[ $plural ] ) && is_array( $row[ $plural ] ) ) {
		return array_values( array_filter( array_map( 'intval', $row[ $plural ] ) ) );
	}
	$single = isset( $row[ $singular ] ) ? (int) $row[ $singular ] : 0;
	return $single > 0 ? array( $single ) : array();
}

/**
 * Markup for a single repeater row. All dynamic parts are pre-escaped.
 *
 * @param string              $key   Field key.
 * @param int|string          $i     Row index (or '__i__' placeholder).
 * @param array<int,string>   $cats  Category id => name.
 * @param array<int,string>   $pages Page id => title.
 * @param array<string,mixed> $row   Stored row (empty for the template).
 * @return string
 */
function kindi_cat_notices_row_html( string $key, $i, array $cats, array $pages, array $row ): string {
	$name     = 'kindi[' . $key . '][' . $i . ']';
	$types    = kindi_cn_types();
	$type     = ( isset( $row['type'] ) && isset( $types[ $row['type'] ] ) ) ? (string) $row['type'] : 'cat';
	$text     = isset( $row['text'] ) ? (string) $row['text'] : '';
	$children = ! empty( $row['children'] );

	$type_opts = '';
	foreach ( $types as $tkey => $tlabel ) {
		$type_opts .= '<option value="' . esc_attr( $tkey ) . '"' . selected( $tkey, $type, false ) . '>' . esc_html( $tlabel ) . '</option>';
	}

	return '<div class="kindi-cn__row" data-type="' . esc_attr( $type ) . '" data-kindi-cn-row>'
		. '<select name="' . esc_attr( $name ) . '[type]" class="kindi-cn__type" aria-label="' . esc_attr__( 'תנאי תצוגה', 'kindi' ) . '">' . $type_opts . '</select>'
		. kindi_cn_checklist_html( $name . '[cats]', 'kindi-cn__cat', $cats, kindi_cn_row_ids( $row, 'cats', 'cat' ), __( 'חיפוש קטגוריה…', 'kindi' ) )
		. kindi_cn_checklist_html( $name . '[pages]', 'kindi-cn__page', $pages, kindi_cn_row_ids( $row, 'pages', 'page' ), __( 'חיפוש עמוד…', 'kindi' ) )
		. '<textarea name="' . esc_attr( $name ) . '[text]" rows="2" class="kindi-cn__text" dir="rtl" placeholder="' . esc_attr__( 'טקסט ההודעה…', 'kindi' ) . '">' . esc_textarea( $text ) . '</textarea>'
		. '<label class="kindi-cn__kids"><input type="checkbox" name="' . esc_attr( $name ) . '[children]" value="1"' . ( $children ? ' checked' : '' ) . '> ' . esc_html__( 'כולל תת-קטגוריות', 'kindi' ) . '</label>'
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
	.kindi-cn__row{display:flex;align-items:flex-start;gap:10px;padding:10px;margin-bottom:8px;background:#fff;border:1px solid #dcdcde;border-radius:8px;max-width:900px}
	.kindi-cn__type{flex:0 0 110px}
	.kindi-cn__list{flex:0 0 220px;border:1px solid #dcdcde;border-radius:6px;background:#fff;padding:6px}
	.kindi-cn__filter{width:100%;margin-bottom:4px}
	.kindi-cn__opts{max-height:140px;overflow-y:auto}
	.kindi-cn__opts label{display:block;margin:0;padding:2px 0;font-size:13px}
	.kindi-cn__text{flex:1 1 auto;min-width:180px}
	.kindi-cn__kids{flex:0 0 auto;white-space:nowrap;padding-top:6px;font-size:13px}
	.kindi-cn__del{flex:0 0 auto;color:#b32d2e;text-decoration:none;font-size:16px;line-height:1;padding-top:6px}
	.kindi-cn__row:not([data-type="cat"]) .kindi-cn__cat,
	.kindi-cn__row:not([data-type="cat"]) .kindi-cn__kids,
	.kindi-cn__row:not([data-type="page"]) .kindi-cn__page{display:none}
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
			// Condition select swaps which target list the row shows.
			box.addEventListener( 'change', function ( e ) {
				var sel = e.target.closest( '.kindi-cn__type' );
				if ( ! sel ) { return; }
				var row = sel.closest( '[data-kindi-cn-row]' );
				if ( row ) { row.dataset.type = sel.value; }
			} );
			// Search box narrows its own checklist (checked items stay visible).
			box.addEventListener( 'input', function ( e ) {
				var filter = e.target.closest( '.kindi-cn__filter' );
				if ( ! filter ) { return; }
				var q = filter.value.trim();
				filter.parentElement.querySelectorAll( '.kindi-cn__opts label' ).forEach( function ( label ) {
					label.hidden = q && ! label.textContent.includes( q ) && ! label.querySelector( 'input' ).checked;
				} );
			} );
		}
		if ( 'loading' !== document.readyState ) { wire(); } else { document.addEventListener( 'DOMContentLoaded', wire ); }
	}() );
	</script>
	<?php
}

/**
 * Sanitise the repeater rows on save. Rows without a text or without a target
 * for their condition are dropped.
 *
 * @param array<int,mixed> $rows Raw posted rows.
 * @return array<int,array{type:string,cats:int[],pages:int[],text:string,children:string}>
 */
function kindi_sanitize_cat_notices( array $rows ): array {
	$out = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$type  = ( isset( $row['type'] ) && isset( kindi_cn_types()[ $row['type'] ] ) ) ? (string) $row['type'] : 'cat';
		$cats  = kindi_cn_sanitize_ids( $row['cats'] ?? null );
		$pages = kindi_cn_sanitize_ids( $row['pages'] ?? null );
		$text  = isset( $row['text'] ) ? sanitize_textarea_field( (string) $row['text'] ) : '';

		if ( '' === trim( $text ) ) {
			continue;
		}
		// Only category/page conditions need targets; view conditions are complete
		// with the text alone.
		if ( ( 'cat' === $type && ! $cats ) || ( 'page' === $type && ! $pages ) ) {
			continue;
		}

		$out[] = array(
			'type'     => $type,
			'cats'     => 'cat' === $type ? $cats : array(),
			'pages'    => 'page' === $type ? $pages : array(),
			'text'     => $text,
			'children' => ( 'cat' === $type && ! empty( $row['children'] ) ) ? '1' : '',
		);
	}
	return $out;
}

/**
 * A posted target list → unique positive ints.
 *
 * @param mixed $ids Raw posted value.
 * @return int[]
 */
function kindi_cn_sanitize_ids( $ids ): array {
	if ( ! is_array( $ids ) ) {
		return array();
	}
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

/* ------------------------------------------------------------------ *
 * Resolution
 * ------------------------------------------------------------------ */

/**
 * Lookup of category ID => notice, built once per request from the option.
 * Legacy rows without a `type` are category rows.
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
			if ( ! is_array( $row ) || ( isset( $row['type'] ) && 'cat' !== $row['type'] ) ) {
				continue;
			}
			$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			if ( '' === $text ) {
				continue;
			}
			foreach ( kindi_cn_row_ids( $row, 'cats', 'cat' ) as $cat ) {
				$lookup[ $cat ] = array( 'text' => $text, 'children' => ! empty( $row['children'] ) );
			}
		}
	}
	return $lookup;
}

/**
 * Lookup of page ID => notice texts, built once per request from the option.
 *
 * @return array<int,string[]>
 */
function kindi_page_notice_lookup(): array {
	static $lookup = null;
	if ( null !== $lookup ) {
		return $lookup;
	}
	$lookup = array();
	$rows   = function_exists( 'kindi_opt' ) ? kindi_opt( 'cat_notices' ) : array();
	if ( is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || 'page' !== ( $row['type'] ?? 'cat' ) ) {
				continue;
			}
			$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			if ( '' === $text ) {
				continue;
			}
			foreach ( kindi_cn_row_ids( $row, 'pages', 'page' ) as $pid ) {
				$lookup[ $pid ][] = $text;
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

/**
 * Pages — a page-condition notice at the top of the page's content.
 *
 * @param string $content Post content.
 * @return string
 */
function kindi_page_notice_content( string $content ): string {
	if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$lookup = kindi_page_notice_lookup();
	$pid    = get_queried_object_id();
	if ( empty( $lookup[ $pid ] ) ) {
		return $content;
	}
	ob_start();
	kindi_cat_notice_box( array_values( array_unique( $lookup[ $pid ] ) ), 'kindi-catnotice--page' );
	return (string) ob_get_clean() . $content;
}
add_filter( 'the_content', 'kindi_page_notice_content', 8 );

/**
 * Lookup of view-condition type (shop/cart/checkout) => notice texts, built
 * once per request from the option.
 *
 * @return array<string,string[]>
 */
function kindi_view_notice_lookup(): array {
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
			$type = (string) ( $row['type'] ?? 'cat' );
			if ( ! in_array( $type, array( 'shop', 'cart', 'checkout' ), true ) ) {
				continue;
			}
			$text = isset( $row['text'] ) ? trim( (string) $row['text'] ) : '';
			if ( '' !== $text ) {
				$lookup[ $type ][] = $text;
			}
		}
	}
	return $lookup;
}

/**
 * Render the notices of one view condition (shop / cart / checkout).
 *
 * @param string $view Condition key.
 * @return void
 */
function kindi_view_notice_render( string $view ): void {
	$lookup = kindi_view_notice_lookup();
	if ( empty( $lookup[ $view ] ) ) {
		return;
	}

	/**
	 * Filter the notice texts of one view before rendering (e.g. the holiday
	 * feature drops a checkout notice that repeats its own message).
	 *
	 * @param string[] $texts Notice texts.
	 * @param string   $view  Condition key (shop/cart/checkout).
	 */
	$texts = apply_filters( 'kindi_view_notices', array_values( array_unique( $lookup[ $view ] ) ), $view );
	if ( $texts ) {
		kindi_cat_notice_box( $texts, 'kindi-catnotice--view' );
	}
}

// Shop archive (top of the grid, before the category-archive notice at 5).
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( function_exists( 'is_shop' ) && is_shop() ) {
			kindi_view_notice_render( 'shop' );
		}
	},
	4
);

// Cart page — above the item table (steps render at 1, shipping bar at 5).
add_action(
	'woocommerce_before_cart',
	static function (): void {
		kindi_view_notice_render( 'cart' );
	},
	2
);

// Checkout — above the form (the stock-cleanup notice renders at 4).
add_action(
	'woocommerce_before_checkout_form',
	static function (): void {
		kindi_view_notice_render( 'checkout' );
	},
	3
);
