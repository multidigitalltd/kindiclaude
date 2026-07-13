<?php
/**
 * Checkout order-bumps (upsells) — Kindi-native, in the spirit of wc-bump.
 *
 * Offer cards shown in the checkout order-review (before the payment methods,
 * or after the totals) let the shopper add a curated product to the order in
 * one tap, optionally at a discount. Each bump is fully configured in the
 * dashboard (קינדי → אפסיילים): product, badge, title, description, up to
 * three selling-point lines, an urgency line, a button label, a discount
 * (none / %, / ₪), a display condition (always / a product in cart / a
 * category in cart) and a "hide once added" toggle.
 *
 * Styling follows the theme's design tokens — no per-bump colour pickers — so
 * the cards always match the site (theme.json is the single source of truth).
 * Everything is server-rendered inside the order-review fragment, so the
 * "added ✓" state and totals refresh with WooCommerce's own update_checkout.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const KINDI_UPSELLS_OPTION = 'kindi_upsells';

/**
 * Stored upsell configuration: settings + items.
 *
 * @return array{settings:array<string,string>,items:array<int,array<string,mixed>>}
 */
function kindi_upsells_data(): array {
	$data = get_option( KINDI_UPSELLS_OPTION );
	$data = is_array( $data ) ? $data : array();
	return array(
		'settings' => array(
			'heading'  => (string) ( $data['settings']['heading'] ?? __( 'רגע לפני שמסיימים — כדאי להוסיף:', 'kindi' ) ),
			'position' => 'after_order_table' === ( $data['settings']['position'] ?? '' ) ? 'after_order_table' : 'before_payment',
		),
		'items'    => isset( $data['items'] ) && is_array( $data['items'] ) ? array_values( $data['items'] ) : array(),
	);
}

/**
 * Default shape for a single upsell (also the sanitisation whitelist).
 *
 * @return array<string,mixed>
 */
function kindi_upsell_defaults(): array {
	return array(
		'uid'             => '',
		'active'          => 1,
		'product_id'      => 0,
		'badge'           => '',
		'title'           => '',
		'description'     => '',
		'cta'             => array( '', '', '' ),
		'urgency'         => '',
		'button'          => __( 'הוספה להזמנה', 'kindi' ),
		'button_added'    => __( 'נוסף להזמנה ✓', 'kindi' ),
		'discount_type'   => 'none',
		'discount_value'  => 0.0,
		'quantity'        => 1,
		'hide_if_in_cart' => 1,
		'condition_type'  => 'always',
		'condition_value' => 0,
	);
}

/* ============================ Front-end display ============================ */

/*
 * The bump block prints INSIDE the order-summary template
 * (.woocommerce-checkout-review-order-table) — the fragment WooCommerce
 * re-renders wholesale on every checkout refresh — so the cards are part of
 * the refreshed markup itself and cannot flash-and-vanish. Both position
 * hooks are registered unconditionally at load time (no dependency on the
 * 'wp' action, which request lifecycles like wc-ajax may treat differently);
 * the configured position is resolved at render time.
 */

/**
 * Mid-summary position: below the coupon / gift-card area (its markers run at
 * priorities 6–90), above the totals.
 *
 * @return void
 */
function kindi_upsells_render_top(): void {
	if ( 'after_order_table' !== kindi_upsells_data()['settings']['position'] ) {
		kindi_upsells_render();
	}
}
add_action( 'kindi_summary_after_coupon', 'kindi_upsells_render_top', 97 );

/**
 * Bottom position: right under the grand total.
 *
 * @return void
 */
function kindi_upsells_render_bottom(): void {
	if ( 'after_order_table' === kindi_upsells_data()['settings']['position'] ) {
		kindi_upsells_render();
	}
}
add_action( 'woocommerce_review_order_after_order_total', 'kindi_upsells_render_bottom' );

/**
 * Does the cart already contain this product (added as this bump)?
 *
 * @param int $upsell_index Item index in the config array.
 * @param int $product_id   Product ID.
 * @return string Cart item key, or '' when absent.
 */
function kindi_upsell_cart_key( int $upsell_index, int $product_id ): string {
	if ( ! WC()->cart ) {
		return '';
	}
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		if ( (int) ( $item['kindi_upsell'] ?? -1 ) === $upsell_index && (int) $item['product_id'] === $product_id ) {
			return (string) $key;
		}
	}
	return '';
}

/**
 * Is this product in the cart at all (as a bump line or a regular line)?
 *
 * @param int $product_id Product ID.
 * @return bool
 */
function kindi_upsell_product_in_cart( int $product_id ): bool {
	if ( ! WC()->cart ) {
		return false;
	}
	foreach ( WC()->cart->get_cart() as $ci ) {
		if ( (int) $ci['product_id'] === $product_id || (int) $ci['variation_id'] === $product_id ) {
			return true;
		}
	}
	return false;
}

/**
 * Is a bump's display condition satisfied by the current cart?
 *
 * @param array<string,mixed> $item Upsell config.
 * @return bool
 */
function kindi_upsell_condition_met( array $item ): bool {
	$type = (string) $item['condition_type'];
	if ( 'always' === $type ) {
		return true;
	}
	$value = (int) $item['condition_value'];
	if ( $value <= 0 || ! WC()->cart ) {
		return true; // Misconfigured condition → don't hide the bump.
	}
	foreach ( WC()->cart->get_cart() as $ci ) {
		if ( 'if_product' === $type && ( (int) $ci['product_id'] === $value || (int) $ci['variation_id'] === $value ) ) {
			return true;
		}
		if ( 'if_category' === $type && has_term( $value, 'product_cat', (int) $ci['product_id'] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Print the bump block (once per request).
 *
 * @return void
 */
function kindi_upsells_render(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	echo kindi_upsells_cards_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within.
}

/**
 * Build the order-bump cards markup ('' when nothing should show).
 *
 * Deliberately NOT guarded by is_checkout(): every caller is a checkout-only
 * hook, and inside the update_order_review AJAX request is_checkout() can
 * report false — which returned an empty fragment and wiped the cards right
 * after the first refresh ("appears for a second, then vanishes").
 *
 * @return string
 */
function kindi_upsells_cards_html(): string {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return '<!-- kindi-upsells: no cart -->';
	}

	$data    = kindi_upsells_data();
	$cards   = array();
	$verdict = array();

	foreach ( $data['items'] as $index => $item ) {
		$item = array_merge( kindi_upsell_defaults(), (array) $item );
		if ( empty( $item['active'] ) || $item['product_id'] <= 0 ) {
			$verdict[] = $index . ':off';
			continue;
		}
		$product = wc_get_product( (int) $item['product_id'] );
		if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			$verdict[] = $index . ':product';
			continue;
		}
		if ( ! kindi_upsell_condition_met( $item ) ) {
			$verdict[] = $index . ':condition';
			continue;
		}

		// A bump never shows when its product is already in the cart — whether it
		// got there through the bump or straight from the shop.
		if ( kindi_upsell_product_in_cart( (int) $item['product_id'] ) ) {
			$verdict[] = $index . ':in-cart';
			continue;
		}
		$verdict[] = $index . ':show';
		kindi_upsell_track_view( (string) $item['uid'] );
		$cards[] = kindi_upsell_card_html( (int) $index, $item, $product, false );
	}

	// Render trail — makes "why is nothing showing?" answerable from view-source
	// on both the initial page and the AJAX-refreshed fragment.
	$trail = '<!-- kindi-upsells ' . esc_html( (string) wp_get_theme( get_template() )->get( 'Version' ) ) . ' [' . esc_html( implode( ' ', $verdict ) ) . '] ' . ( wp_doing_ajax() || defined( 'WC_DOING_AJAX' ) ? 'ajax' : 'page' ) . ' -->';

	if ( ! $cards ) {
		return $trail;
	}

	$out = $trail . '<section class="kindi-upsells" aria-label="' . esc_attr__( 'הצעות להוספה להזמנה', 'kindi' ) . '">';
	if ( '' !== $data['settings']['heading'] ) {
		$out .= '<h3 class="kindi-upsells__title">' . esc_html( $data['settings']['heading'] ) . '</h3>';
	}
	return $out . implode( '', $cards ) . '</section>';
}

/**
 * Build one bump card's HTML (all values escaped here).
 *
 * @param int        $index    Config index.
 * @param array<string,mixed> $item Config.
 * @param WC_Product $product  Product.
 * @param bool       $in_cart  Already added?
 * @return string
 */
function kindi_upsell_card_html( int $index, array $item, WC_Product $product, bool $in_cart ): string {
	$price_html = kindi_upsell_price_html( $item, $product );

	$out  = '<div class="kindi-upsell' . ( $in_cart ? ' is-added' : '' ) . '" data-kindi-upsell="' . esc_attr( (string) $index ) . '">';
	if ( '' !== (string) $item['badge'] ) {
		$out .= '<span class="kindi-upsell__badge">' . esc_html( (string) $item['badge'] ) . '</span>';
	}
	$out .= '<div class="kindi-upsell__body">';

	$img = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'kindi-upsell__img', 'loading' => 'lazy' ) );
	$out .= '<div class="kindi-upsell__media">' . $img . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- get_image returns safe markup.

	$out .= '<div class="kindi-upsell__content">';
	$out .= '<p class="kindi-upsell__name">' . esc_html( '' !== (string) $item['title'] ? (string) $item['title'] : $product->get_name() ) . '</p>';
	if ( '' !== (string) $item['description'] ) {
		$out .= '<p class="kindi-upsell__desc">' . esc_html( (string) $item['description'] ) . '</p>';
	}

	$lines = array_filter( array_map( 'strval', (array) $item['cta'] ), static fn( $l ) => '' !== trim( $l ) );
	if ( $lines ) {
		$out .= '<ul class="kindi-upsell__cta">';
		foreach ( $lines as $line ) {
			$out .= '<li>' . kindi_icon( 'check', 'kindi-icon--xs' ) . esc_html( $line ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput -- kindi_icon is safe SVG.
		}
		$out .= '</ul>';
	}
	$out .= '<div class="kindi-upsell__price">' . $price_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- wc price markup.
	$out .= '</div>'; // content.

	$out .= '</div>'; // body.

	$label = $in_cart ? (string) $item['button_added'] : (string) $item['button'];
	$out  .= '<button type="button" class="kindi-upsell__btn" data-kindi-upsell-toggle="' . esc_attr( (string) $index ) . '" data-action="' . ( $in_cart ? 'remove' : 'add' ) . '">'
		. kindi_icon( $in_cart ? 'check' : 'cart', 'kindi-icon--sm kindi-icon--white' ) // phpcs:ignore WordPress.Security.EscapeOutput
		. '<span>' . esc_html( $label ) . '</span></button>';

	if ( ! $in_cart && '' !== (string) $item['urgency'] ) {
		$out .= '<p class="kindi-upsell__urgency">' . kindi_icon( 'clock', 'kindi-icon--xs' ) . esc_html( (string) $item['urgency'] ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	$out .= '</div>';

	return $out;
}

/**
 * Discounted-price markup for a bump (shows the strike-through when discounted).
 *
 * @param array<string,mixed> $item    Config.
 * @param WC_Product          $product Product.
 * @return string
 */
function kindi_upsell_price_html( array $item, WC_Product $product ): string {
	$base = (float) wc_get_price_to_display( $product );
	$new  = kindi_upsell_apply_discount( $base, $item );
	if ( $new < $base ) {
		return '<del>' . wp_kses_post( wc_price( $base ) ) . '</del> <ins>' . wp_kses_post( wc_price( $new ) ) . '</ins>';
	}
	return wp_kses_post( wc_price( $base ) );
}

/**
 * Apply a bump's discount to a price (floored at zero).
 *
 * @param float               $price Base price.
 * @param array<string,mixed> $item  Config.
 * @return float
 */
function kindi_upsell_apply_discount( float $price, array $item ): float {
	$type  = (string) $item['discount_type'];
	$value = (float) $item['discount_value'];
	if ( $value <= 0 ) {
		return $price;
	}
	if ( 'percent' === $type ) {
		return max( 0.0, $price * ( 1 - min( 100, $value ) / 100 ) );
	}
	if ( 'fixed' === $type ) {
		return max( 0.0, $price - $value );
	}
	return $price;
}

/* ================================ Analytics ================================ */

/**
 * Read a bump's lifetime stats (stored in a non-autoloaded option, keyed by
 * the bump's stable uid so reordering/removing bumps never mixes numbers).
 *
 * @param string $uid Bump uid.
 * @return array{views:int,adds:int,orders:int,revenue:float}
 */
function kindi_upsell_stats( string $uid ): array {
	$all = get_option( 'kindi_upsell_stats' );
	$row = is_array( $all ) && isset( $all[ $uid ] ) && is_array( $all[ $uid ] ) ? $all[ $uid ] : array();
	return array(
		'views'   => (int) ( $row['views'] ?? 0 ),
		'adds'    => (int) ( $row['adds'] ?? 0 ),
		'orders'  => (int) ( $row['orders'] ?? 0 ),
		'revenue' => (float) ( $row['revenue'] ?? 0 ),
	);
}

/**
 * Increment one stat counter.
 *
 * @param string $uid    Bump uid.
 * @param string $key    views|adds|orders|revenue.
 * @param float  $amount Increment.
 * @return void
 */
function kindi_upsell_stat_bump( string $uid, string $key, float $amount = 1 ): void {
	if ( '' === $uid ) {
		return;
	}
	$all = get_option( 'kindi_upsell_stats' );
	$all = is_array( $all ) ? $all : array();
	$all[ $uid ][ $key ] = ( isset( $all[ $uid ][ $key ] ) ? (float) $all[ $uid ][ $key ] : 0 ) + $amount;
	update_option( 'kindi_upsell_stats', $all, false );
}

/**
 * Count an impression — once per shopper session per bump, so the checkout's
 * frequent AJAX re-renders don't inflate the number.
 *
 * @param string $uid Bump uid.
 * @return void
 */
function kindi_upsell_track_view( string $uid ): void {
	if ( '' === $uid || ! WC()->session ) {
		return;
	}
	$seen = (array) WC()->session->get( 'kindi_upsells_seen', array() );
	if ( isset( $seen[ $uid ] ) ) {
		return;
	}
	$seen[ $uid ] = 1;
	WC()->session->set( 'kindi_upsells_seen', $seen );
	kindi_upsell_stat_bump( $uid, 'views' );
}

/**
 * Persist the bump identity onto the order line item (hidden meta), so sales
 * can be attributed after checkout.
 *
 * @param WC_Order_Item_Product $item          Order line item.
 * @param string                $cart_item_key Cart key (unused).
 * @param array<string,mixed>   $values        Cart item values.
 * @return void
 */
function kindi_upsell_order_item_meta( $item, $cart_item_key, $values ): void {
	if ( ! isset( $values['kindi_upsell'] ) ) {
		return;
	}
	$conf = kindi_upsells_data()['items'][ (int) $values['kindi_upsell'] ] ?? null;
	$uid  = is_array( $conf ) ? (string) ( $conf['uid'] ?? '' ) : '';
	if ( '' !== $uid ) {
		$item->add_meta_data( '_kindi_upsell_uid', $uid, true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'kindi_upsell_order_item_meta', 10, 3 );

/**
 * When an order is placed, credit each bump line to its stats (orders count +
 * line revenue).
 *
 * @param int $order_id Order ID.
 * @return void
 */
function kindi_upsell_order_stats( $order_id ): void {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	foreach ( $order->get_items() as $order_item ) {
		$uid = (string) $order_item->get_meta( '_kindi_upsell_uid' );
		if ( '' !== $uid ) {
			kindi_upsell_stat_bump( $uid, 'orders' );
			kindi_upsell_stat_bump( $uid, 'revenue', (float) $order_item->get_total() );
		}
	}
}
add_action( 'woocommerce_checkout_order_processed', 'kindi_upsell_order_stats' );

/* ============================== Cart plumbing ============================== */

/**
 * Re-price bump line items before totals (survives session reload — the
 * kindi_upsell key is stored in the cart item and restored automatically).
 *
 * @param WC_Cart $cart Cart.
 * @return void
 */
function kindi_upsells_reprice( WC_Cart $cart ): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$items = kindi_upsells_data()['items'];
	foreach ( $cart->get_cart() as $ci ) {
		if ( ! isset( $ci['kindi_upsell'] ) ) {
			continue;
		}
		$conf = $items[ (int) $ci['kindi_upsell'] ] ?? null;
		if ( ! is_array( $conf ) ) {
			continue;
		}
		$conf = array_merge( kindi_upsell_defaults(), $conf );
		if ( 'none' === $conf['discount_type'] || (float) $conf['discount_value'] <= 0 ) {
			continue;
		}
		// Discount off the untouched catalogue price (a fresh product object), not
		// the cart item's own object — which this callback may have already lowered
		// on an earlier totals pass, which would compound the discount.
		$fresh = wc_get_product( $ci['data']->get_id() );
		if ( ! $fresh ) {
			continue;
		}
		$base = (float) $fresh->get_price( 'edit' );
		$ci['data']->set_price( kindi_upsell_apply_discount( $base, $conf ) );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'kindi_upsells_reprice', 99 );

/**
 * Keep bump lines visually distinct in the cart/checkout item name.
 *
 * @param string              $name Item name HTML.
 * @param array<string,mixed> $ci   Cart item.
 * @return string
 */
function kindi_upsells_item_name( string $name, array $ci ): string {
	if ( isset( $ci['kindi_upsell'] ) ) {
		$name .= ' <span class="kindi-upsell-tag">' . esc_html__( 'תוספת להזמנה', 'kindi' ) . '</span>';
	}
	return $name;
}
add_filter( 'woocommerce_cart_item_name', 'kindi_upsells_item_name', 10, 2 );

/**
 * AJAX: add or remove a bump product.
 *
 * @return void
 */
function kindi_upsells_ajax(): void {
	check_ajax_referer( 'kindi_upsell', 'nonce' );
	if ( ! WC()->cart ) {
		wp_send_json_error( array( 'msg' => 'cart' ), 400 );
	}

	$index  = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : -1;
	$action = isset( $_POST['do'] ) && 'remove' === $_POST['do'] ? 'remove' : 'add';
	$items  = kindi_upsells_data()['items'];
	$conf   = $items[ $index ] ?? null;
	if ( ! is_array( $conf ) ) {
		wp_send_json_error( array( 'msg' => 'config' ), 400 );
	}
	$conf       = array_merge( kindi_upsell_defaults(), $conf );
	$product_id = (int) $conf['product_id'];
	$existing   = kindi_upsell_cart_key( $index, $product_id );

	if ( 'remove' === $action ) {
		if ( '' !== $existing ) {
			WC()->cart->remove_cart_item( $existing );
		}
		wp_send_json_success( array( 'state' => 'removed' ) );
	}

	if ( '' !== $existing ) {
		wp_send_json_success( array( 'state' => 'added' ) ); // Already there.
	}
	$product = wc_get_product( $product_id );
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		wp_send_json_error( array( 'msg' => 'product' ), 400 );
	}
	$added = WC()->cart->add_to_cart( $product_id, max( 1, (int) $conf['quantity'] ), 0, array(), array( 'kindi_upsell' => $index ) );
	if ( $added ) {
		kindi_upsell_stat_bump( (string) $conf['uid'], 'adds' );
	}
	wp_send_json_success( array( 'state' => $added ? 'added' : 'error' ) );
}
add_action( 'wp_ajax_kindi_upsell', 'kindi_upsells_ajax' );
add_action( 'wp_ajax_nopriv_kindi_upsell', 'kindi_upsells_ajax' );

/**
 * Expose the AJAX nonce to the front-end store script (used on checkout).
 *
 * @param array<string,mixed> $data Localised store data.
 * @return array<string,mixed>
 */
function kindi_upsells_localize( array $data ): array {
	$data['upsellNonce'] = wp_create_nonce( 'kindi_upsell' );
	return $data;
}
add_filter( 'kindi_store_localize', 'kindi_upsells_localize' );
