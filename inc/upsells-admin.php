<?php
/**
 * Order-bumps (upsells) admin screen — קינדי → אפסיילים.
 *
 * A repeater of bump cards plus two global settings (heading + position),
 * saved into the KINDI_UPSELLS_OPTION structure that inc/upsells.php renders.
 * Product pickers reuse WooCommerce's own AJAX product-search select, so no
 * heavy product dropdown is printed. Nonce-protected; every field sanitised.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the אפסיילים submenu under the Kindi menu.
 *
 * @return void
 */
function kindi_upsells_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'קינדי — אפסיילים', 'kindi' ),
		__( 'אפסיילים', 'kindi' ),
		'manage_woocommerce',
		'kindi-upsells',
		'kindi_upsells_admin_render'
	);
}
add_action( 'admin_menu', 'kindi_upsells_menu', 20 );

/**
 * Load WooCommerce's product-search select assets on our screen only.
 *
 * The screen is identified by the page query arg — the hook suffix can't be
 * compared reliably because the parent menu title is Hebrew, which WordPress
 * URL-encodes into the hook name. Runs late (99) so WooCommerce has already
 * registered the handles.
 *
 * @return void
 */
function kindi_upsells_admin_assets(): void {
	if ( ! isset( $_GET['page'] ) || 'kindi-upsells' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- screen check only.
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	wp_enqueue_script( 'wc-enhanced-select' );
	wp_enqueue_style( 'woocommerce_admin_styles' );
	wp_enqueue_style( 'select2' );
}
add_action( 'admin_enqueue_scripts', 'kindi_upsells_admin_assets', 99 );

/**
 * Sanitise the posted repeater into the stored structure.
 *
 * @return array{settings:array<string,string>,items:array<int,array<string,mixed>>}
 */
function kindi_upsells_sanitize_post(): array {
	$settings_in = isset( $_POST['kindi_upsell_settings'] ) ? (array) wp_unslash( $_POST['kindi_upsell_settings'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
	$settings    = array(
		'heading'  => sanitize_text_field( (string) ( $settings_in['heading'] ?? '' ) ),
		'position' => 'after_order_table' === ( $settings_in['position'] ?? '' ) ? 'after_order_table' : 'before_payment',
	);

	$items_in = isset( $_POST['kindi_upsell'] ) ? (array) wp_unslash( $_POST['kindi_upsell'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by caller.
	$items    = array();
	foreach ( $items_in as $row ) {
		if ( ! is_array( $row ) || empty( $row['product_id'] ) ) {
			continue; // Drop rows without a product (e.g. the empty template).
		}
		$cta = array();
		foreach ( (array) ( $row['cta'] ?? array() ) as $line ) {
			$cta[] = sanitize_text_field( (string) $line );
		}
		$cta = array_slice( array_pad( $cta, 3, '' ), 0, 3 );

		$dtype = in_array( $row['discount_type'] ?? 'none', array( 'none', 'percent', 'fixed' ), true ) ? $row['discount_type'] : 'none';
		$ctype = in_array( $row['condition_type'] ?? 'always', array( 'always', 'if_product', 'if_category' ), true ) ? $row['condition_type'] : 'always';

		$items[] = array(
			// Stable identity for the stats ledger — survives reorder/removal.
			'uid'             => '' !== sanitize_key( (string) ( $row['uid'] ?? '' ) ) ? sanitize_key( (string) $row['uid'] ) : uniqid( 'up' ),
			'active'          => empty( $row['active'] ) ? 0 : 1,
			'product_id'      => absint( $row['product_id'] ),
			'badge'           => sanitize_text_field( (string) ( $row['badge'] ?? '' ) ),
			'title'           => sanitize_text_field( (string) ( $row['title'] ?? '' ) ),
			'description'     => sanitize_text_field( (string) ( $row['description'] ?? '' ) ),
			'cta'             => $cta,
			'urgency'         => sanitize_text_field( (string) ( $row['urgency'] ?? '' ) ),
			'button'          => sanitize_text_field( (string) ( $row['button'] ?? '' ) ),
			'button_added'    => sanitize_text_field( (string) ( $row['button_added'] ?? '' ) ),
			'discount_type'   => $dtype,
			'discount_value'  => (float) ( $row['discount_value'] ?? 0 ),
			'quantity'        => max( 1, absint( $row['quantity'] ?? 1 ) ),
			'hide_if_in_cart' => 1, // Store policy: a bump never shows once its product is in the cart.
			'condition_type'  => $ctype,
			'condition_value' => absint( $row['condition_value'] ?? 0 ),
		);
	}

	return array( 'settings' => $settings, 'items' => $items );
}

/**
 * Render (and save) the אפסיילים screen.
 *
 * @return void
 */
function kindi_upsells_admin_render(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$saved = false;
	if ( isset( $_POST['kindi_upsells_submit'] ) && check_admin_referer( 'kindi_save_upsells', 'kindi_upsells_nonce' ) ) {
		update_option( KINDI_UPSELLS_OPTION, kindi_upsells_sanitize_post() );
		$saved = true;
	}

	$data  = kindi_upsells_data();
	$items = $data['items'];

	echo '<div class="wrap kindi-settings"><h1>' . esc_html__( 'אפסיילים — הצעות בעמוד התשלום', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'כרטיסי הצעה שמופיעים בסיכום ההזמנה בעמוד התשלום. הגולש מוסיף מוצר להזמנה בלחיצה, עם אפשרות להנחה. העיצוב תמיד בשפת האתר.', 'kindi' ) . '</p>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'האפסיילים נשמרו.', 'kindi' ) . '</p></div>';
	}

	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_save_upsells', 'kindi_upsells_nonce' );

	// Global settings.
	echo '<h2>' . esc_html__( 'הגדרות כלליות', 'kindi' ) . '</h2>';
	echo '<table class="form-table" role="presentation"><tbody>';
	echo '<tr><th scope="row"><label for="kindi_upsell_heading">' . esc_html__( 'כותרת הבלוק', 'kindi' ) . '</label></th><td>';
	echo '<input type="text" id="kindi_upsell_heading" name="kindi_upsell_settings[heading]" class="regular-text" dir="rtl" value="' . esc_attr( $data['settings']['heading'] ) . '"></td></tr>';
	echo '<tr><th scope="row"><label for="kindi_upsell_position">' . esc_html__( 'מיקום', 'kindi' ) . '</label></th><td>';
	echo '<select id="kindi_upsell_position" name="kindi_upsell_settings[position]">';
	echo '<option value="before_payment"' . selected( $data['settings']['position'], 'before_payment', false ) . '>' . esc_html__( 'מעל אמצעי התשלום', 'kindi' ) . '</option>';
	echo '<option value="after_order_table"' . selected( $data['settings']['position'], 'after_order_table', false ) . '>' . esc_html__( 'מתחת לסיכום ההזמנה', 'kindi' ) . '</option>';
	echo '</select></td></tr>';
	echo '</tbody></table>';

	echo '<h2>' . esc_html__( 'כרטיסי אפסייל', 'kindi' ) . '</h2>';
	echo '<div id="kindi-upsell-rows">';
	if ( $items ) {
		foreach ( $items as $i => $item ) {
			echo kindi_upsell_admin_row( (int) $i, array_merge( kindi_upsell_defaults(), (array) $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping.
		}
	}
	echo '</div>';

	echo '<p><button type="button" class="button button-secondary" id="kindi-upsell-add">＋ ' . esc_html__( 'הוספת אפסייל', 'kindi' ) . '</button></p>';

	// Hidden template row (index token __i__), cloned by JS.
	echo '<script type="text/html" id="kindi-upsell-template">' . kindi_upsell_admin_row( '__i__', kindi_upsell_defaults() ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput

	submit_button( __( 'שמירת אפסיילים', 'kindi' ), 'primary', 'kindi_upsells_submit' );
	echo '</form>';

	kindi_upsells_admin_footer_assets();
	echo '</div>';
}

/**
 * Markup for one repeater row.
 *
 * @param int|string          $i    Index (int, or "__i__" for the template).
 * @param array<string,mixed> $item Config values.
 * @return string
 */
function kindi_upsell_admin_row( $i, array $item ): string {
	$name = 'kindi_upsell[' . $i . ']';
	$pid  = (int) $item['product_id'];

	$product     = $pid > 0 ? wc_get_product( $pid ) : null;
	$product_opt = $product
		? '<option value="' . esc_attr( (string) $pid ) . '" selected>' . esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ) . '</option>'
		: '';

	// Card header: product name, an active/inactive dot and the lifetime stats.
	$title = $product ? wp_strip_all_tags( $product->get_name() ) : __( 'אפסייל חדש — בחרו מוצר', 'kindi' );
	$stats = '';
	$uid   = (string) ( $item['uid'] ?? '' );
	if ( is_int( $i ) && '' !== $uid && function_exists( 'kindi_upsell_stats' ) ) {
		$s     = kindi_upsell_stats( $uid );
		$stats = sprintf(
			/* translators: 1: impressions, 2: adds, 3: orders, 4: revenue. */
			__( 'הופעות: %1$d · הוספות: %2$d · הזמנות: %3$d · הכנסה: %4$s', 'kindi' ),
			$s['views'],
			$s['adds'],
			$s['orders'],
			wp_strip_all_tags( wc_price( $s['revenue'] ) )
		);
	}

	// New/product-less cards start open; configured cards start collapsed.
	$o  = '<details class="kindi-uprow postbox" style="margin:0 0 1rem;max-width:820px"' . ( $product ? '' : ' open' ) . '>';
	$o .= '<summary style="padding:0.85rem 1rem;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:1rem">';
	$o .= '<span style="font-weight:600;display:inline-flex;align-items:center;gap:0.5rem"><span style="width:10px;height:10px;border-radius:50%;flex:0 0 auto;background:' . ( ! empty( $item['active'] ) ? '#15803d' : '#b91c1c' ) . '"></span>' . esc_html( $title ) . '</span>';
	if ( '' !== $stats ) {
		$o .= '<span class="description">' . esc_html( $stats ) . '</span>';
	}
	$o .= '</summary>';
	$o .= '<div style="padding:0.25rem 1rem 1rem;border-top:1px solid #dcdcde">';
	$o .= '<input type="hidden" name="' . esc_attr( $name ) . '[uid]" value="' . esc_attr( $uid ) . '">';
	$o .= '<div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;margin:0.75rem 0">';
	$o .= '<label style="font-weight:600"><input type="checkbox" name="' . esc_attr( $name ) . '[active]" value="1"' . checked( ! empty( $item['active'] ), true, false ) . '> ' . esc_html__( 'פעיל', 'kindi' ) . '</label>';
	$o .= '<button type="button" class="button-link kindi-uprow-remove" style="color:#b32d2e">' . esc_html__( 'הסרה', 'kindi' ) . '</button>';
	$o .= '</div>';

	$o .= '<table class="form-table" role="presentation"><tbody>';

	// Product search (WooCommerce enhanced select).
	$o .= '<tr><th>' . esc_html__( 'מוצר', 'kindi' ) . '</th><td>';
	$o .= '<select class="wc-product-search" style="width:100%;max-width:420px" name="' . esc_attr( $name ) . '[product_id]" data-placeholder="' . esc_attr__( 'חיפוש מוצר…', 'kindi' ) . '" data-action="woocommerce_json_search_products_and_variations" data-allow_clear="true">' . $product_opt . '</select>';
	$o .= '</td></tr>';

	$o .= kindi_upadmin_text( $name, 'badge', __( 'תגית (badge)', 'kindi' ), (string) $item['badge'], 'למשל: מומלץ!' );
	$o .= kindi_upadmin_text( $name, 'title', __( 'כותרת (ריק = שם המוצר)', 'kindi' ), (string) $item['title'], '' );
	$o .= kindi_upadmin_text( $name, 'description', __( 'תיאור קצר', 'kindi' ), (string) $item['description'], '' );

	// CTA lines.
	$cta = array_pad( (array) $item['cta'], 3, '' );
	$o  .= '<tr><th>' . esc_html__( 'שורות שכנוע (עד 3)', 'kindi' ) . '</th><td>';
	for ( $k = 0; $k < 3; $k++ ) {
		$o .= '<input type="text" name="' . esc_attr( $name ) . '[cta][]" class="regular-text" dir="rtl" style="display:block;margin-bottom:4px" value="' . esc_attr( (string) $cta[ $k ] ) . '">';
	}
	$o .= '</td></tr>';

	$o .= kindi_upadmin_text( $name, 'urgency', __( 'שורת דחיפות', 'kindi' ), (string) $item['urgency'], 'למשל: נשארו יחידות אחרונות' );
	$o .= kindi_upadmin_text( $name, 'button', __( 'טקסט כפתור', 'kindi' ), (string) $item['button'], '' );
	$o .= kindi_upadmin_text( $name, 'button_added', __( 'טקסט כפתור אחרי הוספה', 'kindi' ), (string) $item['button_added'], '' );

	// Discount.
	$o .= '<tr><th>' . esc_html__( 'הנחה', 'kindi' ) . '</th><td>';
	$o .= '<select name="' . esc_attr( $name ) . '[discount_type]">';
	foreach ( array( 'none' => __( 'ללא', 'kindi' ), 'percent' => __( 'אחוז (%)', 'kindi' ), 'fixed' => __( 'סכום (₪)', 'kindi' ) ) as $dv => $dl ) {
		$o .= '<option value="' . esc_attr( $dv ) . '"' . selected( $item['discount_type'], $dv, false ) . '>' . esc_html( $dl ) . '</option>';
	}
	$o .= '</select> ';
	$o .= '<input type="number" step="0.01" min="0" name="' . esc_attr( $name ) . '[discount_value]" value="' . esc_attr( (string) $item['discount_value'] ) . '" style="width:100px">';
	$o .= '</td></tr>';

	// Quantity. (Bumps always hide once their product is in the cart — no toggle.)
	$o .= '<tr><th>' . esc_html__( 'כמות', 'kindi' ) . '</th><td>';
	$o .= '<input type="number" min="1" name="' . esc_attr( $name ) . '[quantity]" value="' . esc_attr( (string) max( 1, (int) $item['quantity'] ) ) . '" style="width:80px">';
	$o .= '</td></tr>';

	// Condition.
	$o .= '<tr><th>' . esc_html__( 'תנאי הצגה', 'kindi' ) . '</th><td>';
	$o .= '<select class="kindi-upcond" name="' . esc_attr( $name ) . '[condition_type]">';
	foreach ( array( 'always' => __( 'תמיד', 'kindi' ), 'if_product' => __( 'אם מוצר מסוים בסל', 'kindi' ), 'if_category' => __( 'אם קטגוריה מסוימת בסל', 'kindi' ) ) as $cv => $cl ) {
		$o .= '<option value="' . esc_attr( $cv ) . '"' . selected( $item['condition_type'], $cv, false ) . '>' . esc_html( $cl ) . '</option>';
	}
	$o .= '</select> ';
	// Condition value: a product-search select OR a category select, toggled by JS.
	$cond_prod_opt = '';
	if ( 'if_product' === $item['condition_type'] && (int) $item['condition_value'] > 0 && ( $cp = wc_get_product( (int) $item['condition_value'] ) ) ) { // phpcs:ignore
		$cond_prod_opt = '<option value="' . esc_attr( (string) (int) $item['condition_value'] ) . '" selected>' . esc_html( wp_strip_all_tags( $cp->get_formatted_name() ) ) . '</option>';
	}
	$o .= '<select class="wc-product-search kindi-upcond-product" style="min-width:260px" name="' . esc_attr( $name ) . '[condition_value_product]" data-placeholder="' . esc_attr__( 'חיפוש מוצר…', 'kindi' ) . '" data-action="woocommerce_json_search_products_and_variations">' . $cond_prod_opt . '</select>';
	$o .= '<span class="kindi-upcond-cat">' . wp_dropdown_categories( array(
		'taxonomy'         => 'product_cat',
		'name'             => $name . '[condition_value_cat]',
		'orderby'          => 'name',
		'hide_empty'       => false,
		'show_option_none' => __( '— בחירת קטגוריה —', 'kindi' ),
		'option_none_value' => 0,
		'selected'         => 'if_category' === $item['condition_type'] ? (int) $item['condition_value'] : 0,
		'echo'             => false,
	) ) . '</span>';
	// Unified hidden field the sanitiser reads — kept in sync by JS from whichever control is active.
	$cond_val = (int) $item['condition_value'];
	$o .= '<input type="hidden" class="kindi-upcond-value" name="' . esc_attr( $name ) . '[condition_value]" value="' . esc_attr( (string) $cond_val ) . '">';
	$o .= '</td></tr>';

	$o .= '</tbody></table></div></details>';
	return $o;
}

/**
 * A labelled text row for the repeater.
 *
 * @param string $name        Field name prefix.
 * @param string $key         Field key.
 * @param string $label       Label.
 * @param string $value       Value.
 * @param string $placeholder Placeholder.
 * @return string
 */
function kindi_upadmin_text( string $name, string $key, string $label, string $value, string $placeholder ): string {
	return '<tr><th>' . esc_html( $label ) . '</th><td><input type="text" class="regular-text" dir="rtl" name="'
		. esc_attr( $name . '[' . $key . ']' ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></td></tr>';
}

/**
 * Repeater JS (add / remove / reindex, condition toggling, product-search init).
 *
 * @return void
 */
function kindi_upsells_admin_footer_assets(): void {
	?>
	<style>
	/* select2 opens at zero width when initialised inside a collapsed card. */
	.kindi-uprow .select2-container { width: 100% !important; max-width: 420px; }
	.kindi-uprow summary::-webkit-details-marker { display: none; }
	</style>
	<script>
	// Safety net: when WooCommerce's own localisation is absent for any reason,
	// supply the minimal params the enhanced-select AJAX search needs.
	window.wc_enhanced_select_params = window.wc_enhanced_select_params || <?php echo wp_json_encode( array(
		'ajax_url'              => admin_url( 'admin-ajax.php' ),
		'search_products_nonce' => wp_create_nonce( 'search-products' ),
		'i18n_no_matches'       => __( 'לא נמצאו תוצאות', 'kindi' ),
		'i18n_searching'        => __( 'מחפש…', 'kindi' ),
		'i18n_input_too_short_1' => __( 'הקלידו תו אחד לפחות', 'kindi' ),
		'i18n_input_too_short_n' => __( 'הקלידו עוד תווים', 'kindi' ),
		'i18n_load_more'        => __( 'טעינת תוצאות נוספות…', 'kindi' ),
	) ); ?>;
	( function () {
		var wrap = document.getElementById( 'kindi-upsell-rows' );
		var tpl  = document.getElementById( 'kindi-upsell-template' );
		if ( ! wrap || ! tpl ) { return; }
		var jq = window.jQuery;

		function enhance( scope ) {
			if ( ! jq ) { return; }
			jq( scope ).find( 'select.wc-product-search' ).each( function () {
				if ( jq( this ).data( 'select2' ) ) { return; }
				jq( document.body ).trigger( 'wc-enhanced-select-init' );
			} );
		}
		function toggleCond( row ) {
			var type = row.querySelector( '.kindi-upcond' );
			if ( ! type ) { return; }
			var prod = row.querySelector( '.kindi-upcond-product' );
			var catWrap = row.querySelector( '.kindi-upcond-cat' );
			var prodWrap = prod ? ( prod.closest( '.select2-container' ) || prod ) : null;
			var isProd = 'if_product' === type.value;
			var isCat  = 'if_category' === type.value;
			if ( prodWrap ) { prodWrap.style.display = isProd ? '' : 'none'; }
			if ( catWrap ) { catWrap.style.display = isCat ? '' : 'none'; }
		}
		function syncValue( row ) {
			var type = row.querySelector( '.kindi-upcond' );
			var hidden = row.querySelector( '.kindi-upcond-value' );
			if ( ! type || ! hidden ) { return; }
			if ( 'if_product' === type.value ) {
				var prod = row.querySelector( '.kindi-upcond-product' );
				hidden.value = prod ? ( prod.value || 0 ) : 0;
			} else if ( 'if_category' === type.value ) {
				var cat = row.querySelector( 'select[name*="condition_value_cat"]' );
				hidden.value = cat ? ( cat.value || 0 ) : 0;
			} else {
				hidden.value = 0;
			}
		}
		function wire( row ) {
			var type = row.querySelector( '.kindi-upcond' );
			if ( type ) {
				type.addEventListener( 'change', function () { toggleCond( row ); syncValue( row ); } );
			}
			row.addEventListener( 'change', function () { syncValue( row ); } );
			var rm = row.querySelector( '.kindi-uprow-remove' );
			if ( rm ) { rm.addEventListener( 'click', function () { row.remove(); } ); }
			toggleCond( row );
			enhance( row );
		}

		Array.prototype.forEach.call( wrap.children, wire );

		// select2 fires jQuery change events that native listeners can miss, so
		// resync every row's hidden condition value just before the form submits.
		var form = wrap.closest( 'form' );
		if ( form ) {
			form.addEventListener( 'submit', function () {
				Array.prototype.forEach.call( wrap.children, syncValue );
			} );
		}

		document.getElementById( 'kindi-upsell-add' ).addEventListener( 'click', function () {
			var i = Date.now();
			var html = tpl.textContent.replace( /__i__/g, String( i ) );
			var tmp = document.createElement( 'div' );
			tmp.innerHTML = html;
			var row = tmp.firstElementChild;
			wrap.appendChild( row );
			wire( row );
		} );
	}() );
	</script>
	<?php
}
