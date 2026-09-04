<?php
/**
 * "Frequently bought together" bundle on the product page — the current product
 * plus a couple of companions (cross-sells → up-sells → related), each with a
 * checkbox and a live combined total, added to the cart in one click. An
 * optional bundle coupon (set in the panel) is applied on add.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Companion products for a bundle — cross-sells first, then up-sells, then
 * related. Simple, purchasable, in-stock products only (so they can be added in
 * one click).
 *
 * @param WC_Product $product Main product.
 * @param int        $limit   Max companions.
 * @return WC_Product[]
 */
function kindi_bundle_companions( $product, int $limit = 2 ): array {
	if ( ! $product instanceof WC_Product ) {
		return array();
	}
	$ids = $product->get_cross_sell_ids();
	if ( empty( $ids ) ) {
		$ids = $product->get_upsell_ids();
	}
	if ( empty( $ids ) ) {
		$ids = wc_get_related_products( $product->get_id(), $limit + 4 );
	}

	$out = array();
	foreach ( $ids as $id ) {
		if ( count( $out ) >= $limit ) {
			break;
		}
		$candidate = wc_get_product( $id );
		if ( $candidate instanceof WC_Product
			&& 'publish' === $candidate->get_status()
			&& $candidate->is_purchasable()
			&& $candidate->is_in_stock()
			&& ! $candidate->is_type( 'variable' )
			&& $candidate->get_id() !== $product->get_id() ) {
			$out[] = $candidate;
		}
	}
	return $out;
}

/**
 * Render the bundle section after the product summary.
 *
 * @return void
 */
function kindi_render_bundle(): void {
	global $product;
	if ( ! $product instanceof WC_Product || $product->is_type( 'variable' )
		|| ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	$companions = kindi_bundle_companions( $product, 2 );
	if ( ! $companions ) {
		return;
	}
	$all = array_merge( array( $product ), $companions );

	echo '<section class="kindi-bundle" data-kindi-bundle aria-label="' . esc_attr__( 'נקנה יחד', 'kindi' ) . '">';
	echo '<h2 class="kindi-bundle__title">' . esc_html__( 'נקנה יחד — השלימו את הסט', 'kindi' ) . '</h2>';
	echo '<div class="kindi-bundle__items">';
	$last = count( $all ) - 1;
	foreach ( $all as $i => $p ) {
		$is_main = ( 0 === $i );
		$price   = round( (float) wc_get_price_to_display( $p ), 2 );
		echo '<label class="kindi-bundle__item">';
		echo '<input type="checkbox" class="kindi-bundle__cb" data-id="' . esc_attr( (string) $p->get_id() ) . '" data-price="' . esc_attr( (string) $price ) . '" checked' . ( $is_main ? ' disabled data-main="1"' : '' ) . '>';
		echo '<span class="kindi-bundle__media">' . $p->get_image( 'woocommerce_thumbnail' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce image markup.
		echo '<span class="kindi-bundle__info"><span class="kindi-bundle__name">' . esc_html( $p->get_name() ) . '</span><span class="kindi-bundle__price">' . wp_kses_post( wc_price( $price ) ) . '</span></span>';
		echo '</label>';
		if ( $i < $last ) {
			echo '<span class="kindi-bundle__plus" aria-hidden="true">+</span>';
		}
	}
	echo '</div>';
	echo '<div class="kindi-bundle__foot">';
	echo '<div class="kindi-bundle__total">' . esc_html__( 'סה"כ לפריטים שנבחרו:', 'kindi' ) . ' <strong data-kindi-bundle-total></strong></div>';
	echo '<button type="button" class="button kindi-bundle__add" data-kindi-bundle-add>' . esc_html__( 'הוספת הנבחרים לסל', 'kindi' ) . '</button>';
	echo '</div>';
	echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'kindi_render_bundle', 9 );

/**
 * Enqueue the bundle script on product pages.
 *
 * @return void
 */
function kindi_bundle_assets(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	wp_enqueue_script(
		'kindi-bundle',
		KINDI_URI . 'assets/js/bundle.js',
		array(),
		kindi_asset_version( 'assets/js/bundle.js' ),
		true
	);
	wp_localize_script(
		'kindi-bundle',
		'kindiBundle',
		array(
			'url'     => esc_url_raw( rest_url( 'kindi/v1/bundle-add' ) ),
			'cartUrl' => function_exists( 'wc_get_cart_url' ) ? esc_url_raw( wc_get_cart_url() ) : '',
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'symbol'  => html_entity_decode( get_woocommerce_currency_symbol() ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_bundle_assets' );

/**
 * Defer the bundle script.
 *
 * @param string $tag    Tag.
 * @param string $handle Handle.
 * @return string
 */
function kindi_bundle_defer( string $tag, string $handle ): string {
	if ( 'kindi-bundle' === $handle && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_bundle_defer', 10, 2 );

/**
 * REST endpoint that adds the selected bundle items to the cart. State-changing,
 * so it verifies the wp_rest nonce.
 *
 * @return void
 */
function kindi_bundle_rest_init(): void {
	register_rest_route(
		'kindi/v1',
		'/bundle-add',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'kindi_bundle_add_cb',
			'permission_callback' => static function ( WP_REST_Request $request ) {
				return wp_verify_nonce( (string) $request->get_header( 'X-WP-Nonce' ), 'wp_rest' )
					? true
					: new WP_Error( 'kindi_bad_nonce', __( 'בקשה לא תקפה.', 'kindi' ), array( 'status' => 403 ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'kindi_bundle_rest_init' );

/**
 * Add the posted items to the cart and apply the optional bundle coupon.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function kindi_bundle_add_cb( WP_REST_Request $request ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'kindi_no_wc', __( 'החנות אינה זמינה.', 'kindi' ), array( 'status' => 400 ) );
	}
	// WooCommerce doesn't boot the cart/session on REST requests — load it so
	// add_to_cart works (otherwise WC()->cart is null and the add fails).
	if ( ( null === WC()->cart || ! WC()->cart ) && function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();
	}
	if ( ! WC()->cart ) {
		return new WP_Error( 'kindi_no_cart', __( 'העגלה אינה זמינה.', 'kindi' ), array( 'status' => 400 ) );
	}

	$items = $request->get_param( 'items' );
	if ( ! is_array( $items ) ) {
		return new WP_Error( 'kindi_bad_items', __( 'לא נבחרו פריטים.', 'kindi' ), array( 'status' => 400 ) );
	}

	$added = 0;
	foreach ( $items as $item ) {
		$id  = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$qty = isset( $item['quantity'] ) ? max( 1, absint( $item['quantity'] ) ) : 1;
		if ( ! $id ) {
			continue;
		}
		$candidate = wc_get_product( $id );
		if ( $candidate instanceof WC_Product && $candidate->is_purchasable() && $candidate->is_in_stock() && ! $candidate->is_type( 'variable' ) ) {
			if ( WC()->cart->add_to_cart( $id, $qty ) ) {
				++$added;
			}
		}
	}

	$coupon = (string) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'bundle_coupon', '' ) : '' );
	if ( $added > 0 && '' !== $coupon && ! WC()->cart->has_discount( $coupon ) ) {
		WC()->cart->apply_coupon( $coupon );
	}

	return rest_ensure_response(
		array(
			'added'    => $added,
			'count'    => WC()->cart->get_cart_contents_count(),
			'subtotal' => wp_strip_all_tags( WC()->cart->get_cart_subtotal() ),
		)
	);
}
