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
			'hotUrl'       => esc_url_raw( rest_url( 'kindi/v1/hot' ) ),
			'subscribeUrl' => esc_url_raw( rest_url( 'kindi/v1/subscribe' ) ),
			'ajaxUrl'      => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
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
		<aside class="kindi-cartdrawer__panel" role="dialog" aria-modal="true" aria-label="סל קניות" tabindex="-1">
			<div class="kindi-cartdrawer__head">
				<strong>הסל שלי</strong>
				<button type="button" data-kindi-cart-close aria-label="סגירה"><?php echo kindi_icon( 'close', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<?php // The scroll container must stay put across AJAX updates. WooCommerce's
			// mini-cart fragment (div.widget_shopping_cart_content) is replaced wholesale
			// on every add-to-cart, so it lives INSIDE __body rather than on it — otherwise
			// the swap would drop the scroll styles and the footer would slide off-screen. ?>
			<div class="kindi-cartdrawer__body">
				<div class="widget_shopping_cart_content">
					<?php woocommerce_mini_cart(); ?>
				</div>
			</div>
			<?php // Dedicated footer OUTSIDE the AJAX-replaced mini-cart, so the total + checkout button are always visible while the items list scrolls. ?>
			<div class="kindi-cartdrawer__foot" data-kindi-cart-foot>
				<?php echo kindi_cart_foot_total_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within. ?>
				<div class="kindi-cartdrawer__footbtns">
					<a class="button kindi-cartdrawer__viewcart" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'צפייה בסל', 'kindi' ); ?></a>
					<a class="button checkout kindi-cartdrawer__checkout" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'מעבר לתשלום', 'kindi' ); ?></a>
				</div>
			</div>
		</aside>
	</div>
	<?php
}
add_action( 'wp_footer', 'kindi_mini_cart_drawer' );

/**
 * Drawer-footer total markup (kept in sync via a cart fragment).
 *
 * @return string
 */
function kindi_cart_foot_total_html(): string {
	$subtotal = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_subtotal() : '';
	$count    = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

	return '<div class="kindi-cartdrawer__foottotal' . ( $count ? '' : ' is-empty' ) . '">'
		. '<span>' . esc_html__( 'סה"כ ביניים', 'kindi' ) . '</span>'
		. '<strong>' . wp_kses_post( (string) $subtotal ) . '</strong>'
		. '</div>';
}

/**
 * Keep the drawer-footer total fresh on add/remove via WooCommerce fragments.
 *
 * @param array<string,string> $fragments Cart fragments.
 * @return array<string,string>
 */
function kindi_cart_foot_fragment( array $fragments ): array {
	$fragments['div.kindi-cartdrawer__foottotal'] = kindi_cart_foot_total_html();
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'kindi_cart_foot_fragment' );

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

/**
 * Render the wishlist grid automatically on the /wishlist/ page, so it works
 * even when the page content has no [kindi_wishlist] shortcode.
 *
 * @param string $content Post content.
 * @return string
 */
function kindi_wishlist_auto_content( string $content ): string {
	if ( ! is_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$post = get_post();
	if ( ! $post instanceof WP_Post ) {
		return $content;
	}
	$path     = trim( (string) wp_parse_url( get_permalink( $post ), PHP_URL_PATH ), '/' );
	$is_wish  = 'wishlist' === $post->post_name || 'wishlist' === substr( $path, -8 );
	$has_grid = false !== strpos( $content, 'data-kindi-wishlist-grid' );
	if ( $is_wish && ! $has_grid ) {
		return $content . kindi_wishlist_shortcode();
	}
	return $content;
}
add_filter( 'the_content', 'kindi_wishlist_auto_content', 20 );
