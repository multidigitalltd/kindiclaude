<?php
/**
 * Store interactions — slide-out mini-cart, wishlist (localStorage) and the
 * products-by-ids REST endpoint that backs the wishlist page.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the store script + localise endpoints.
 *
 * @return void
 */
function kindi_store_assets(): void {
	wp_enqueue_script(
		'kindi-store',
		KINDI_URI . 'assets/js/store.js',
		array(),
		kindi_asset_version( 'assets/js/store.js' ),
		true
	);
	wp_localize_script(
		'kindi-store',
		'kindiStore',
		array(
			'productsUrl'  => esc_url_raw( rest_url( 'kindi/v1/products' ) ),
			'subscribeUrl' => esc_url_raw( rest_url( 'kindi/v1/subscribe' ) ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_store_assets' );

/**
 * Defer the store script.
 *
 * @param string $tag    Tag.
 * @param string $handle Handle.
 * @return string
 */
function kindi_store_defer( string $tag, string $handle ): string {
	if ( 'kindi-store' === $handle && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_store_defer', 10, 2 );

/**
 * Render the slide-out mini-cart in the footer.
 *
 * @return void
 */
function kindi_mini_cart_drawer(): void {
	if ( ! class_exists( 'WooCommerce' ) || is_cart() || is_checkout() ) {
		return;
	}
	?>
	<div class="kindi-cartdrawer" data-kindi-cart hidden>
		<div class="kindi-cartdrawer__overlay" data-kindi-cart-close></div>
		<aside class="kindi-cartdrawer__panel" role="dialog" aria-modal="true" aria-label="סל קניות">
			<div class="kindi-cartdrawer__head">
				<strong>הסל שלי</strong>
				<button type="button" data-kindi-cart-close aria-label="סגירה"><?php echo kindi_icon( 'close', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<div class="kindi-cartdrawer__body widget_shopping_cart_content">
				<?php woocommerce_mini_cart(); ?>
			</div>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'kindi_mini_cart_drawer' );

/**
 * REST: products by IDs (for the wishlist grid).
 *
 * @return void
 */
function kindi_register_products_route(): void {
	register_rest_route(
		'kindi/v1',
		'/products',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'kindi_rest_products',
			'permission_callback' => '__return_true',
			'args'                => array(
				'ids' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'kindi_register_products_route' );

/**
 * Return visible products for the given comma-separated IDs.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function kindi_rest_products( WP_REST_Request $request ): WP_REST_Response {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'ids' ) ) ) );
	$out = array();

	if ( ! $ids || ! class_exists( 'WooCommerce' ) ) {
		return rest_ensure_response( $out );
	}

	foreach ( array_slice( $ids, 0, 40 ) as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		$out[] = array(
			'id'    => $id,
			'title' => $product->get_name(),
			'url'   => get_permalink( $id ),
			'price' => $product->get_price_html(),
			'img'   => get_the_post_thumbnail_url( $id, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		);
	}

	return rest_ensure_response( $out );
}

/**
 * Shortcode: [kindi_wishlist] — client-rendered wishlist grid.
 *
 * @return string
 */
function kindi_wishlist_shortcode(): string {
	return '<div class="kindi-section"><h2 class="kindi-sec-title">המועדפים שלי</h2><div class="kindi-wish-grid" data-kindi-wishlist-grid><p class="kindi-prod-empty">טוען…</p></div></div>';
}
add_shortcode( 'kindi_wishlist', 'kindi_wishlist_shortcode' );
