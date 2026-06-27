<?php
/**
 * Sticky mobile add-to-cart bar.
 *
 * On single-product views (mobile only) a slim bar pins to the bottom of the
 * screen once the main buy button scrolls out of view, keeping price + buy
 * always reachable. It drives the real product form (quantity / variation), so
 * behaviour matches the native button exactly — no duplicated cart logic.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the sticky-bar script on product pages only.
 *
 * @return void
 */
function kindi_sticky_cart_assets(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	wp_enqueue_script(
		'kindi-sticky-cart',
		KINDI_URI . 'assets/js/sticky-cart.js',
		array(),
		kindi_asset_version( 'assets/js/sticky-cart.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_sticky_cart_assets' );

/**
 * Defer the sticky-cart script.
 *
 * @param string $tag    Tag.
 * @param string $handle Handle.
 * @return string
 */
function kindi_sticky_cart_defer( string $tag, string $handle ): string {
	if ( 'kindi-sticky-cart' === $handle && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_sticky_cart_defer', 10, 2 );

/**
 * Render the sticky bar markup in the footer (hidden until JS reveals it).
 *
 * @return void
 */
function kindi_sticky_cart_bar(): void {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	global $product;
	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_queried_object_id() );
	}
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$thumb = get_the_post_thumbnail_url( $product->get_id(), 'woocommerce_gallery_thumbnail' );
	$thumb = $thumb ?: wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );
	?>
	<div class="kindi-stickycart" data-kindi-stickycart hidden>
		<div class="kindi-stickycart__info">
			<img class="kindi-stickycart__img" src="<?php echo esc_url( $thumb ); ?>" alt="" width="48" height="48" loading="lazy" decoding="async">
			<span class="kindi-stickycart__meta">
				<span class="kindi-stickycart__title"><?php echo esc_html( wp_trim_words( $product->get_name(), 5 ) ); ?></span>
				<span class="kindi-stickycart__price" data-kindi-stickycart-price><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			</span>
		</div>
		<button type="button" class="kindi-btn kindi-btn--red kindi-stickycart__btn" data-kindi-stickycart-add>
			<?php echo kindi_icon( 'cart', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<span><?php esc_html_e( 'הוספה לסל', 'kindi' ); ?></span>
		</button>
	</div>
	<?php
}
add_action( 'wp_footer', 'kindi_sticky_cart_bar' );
