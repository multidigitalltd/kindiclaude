<?php
/**
 * Custom single-product layout — recreates the Lovable product page.
 *
 * Two-column grid: LEFT = gallery + trust strip + key-facts + "in the box";
 * RIGHT = the buy box (brand, title, price, stock, variations, add-to-cart,
 * perks, delivery card). Tabs + related products span full width below.
 *
 * Dynamic parts are produced by the standard WooCommerce hooks, so variations,
 * pricing, stock and cart behaviour are unchanged. Overrides woocommerce/
 * content-single-product.php.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;

global $product;

/**
 * woocommerce_before_single_product hook.
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // WPCS: XSS ok.
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'kindi-pdp', $product ); ?>>

	<div class="kindi-pdp__grid">

		<div class="kindi-pdp__media">
			<?php
			/**
			 * Sale flash + product gallery.
			 *
			 * @hooked woocommerce_show_product_sale_flash - 10
			 * @hooked woocommerce_show_product_images - 20
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
		</div>

		<div class="summary entry-summary kindi-pdp__summary">
			<?php
			/**
			 * Buy box.
			 *
			 * @hooked kindi_pdp_brand_eyebrow - 4
			 * @hooked woocommerce_template_single_title - 5
			 * @hooked woocommerce_template_single_rating - 10
			 * @hooked woocommerce_template_single_price - 10
			 * @hooked woocommerce_template_single_excerpt - 20
			 * @hooked woocommerce_template_single_add_to_cart - 30
			 * @hooked kindi_waitlist_form - 31
			 * @hooked kindi_pdp_highlights - 33
			 * @hooked kindi_pdp_delivery_card - 34
			 * @hooked kindi_whatsapp_product_button - 35
			 * @hooked woocommerce_template_single_meta - 40
			 */
			do_action( 'woocommerce_single_product_summary' );
			?>
		</div>

		<?php
		// Toy blocks (trust strip, key facts, skills, in-the-box). A direct grid
		// child so it sits under the gallery on desktop but drops below the buy box
		// on mobile (single column) — see the grid placement in woocommerce.css.
		if ( function_exists( 'kindi_pdp_left_extras' ) ) {
			kindi_pdp_left_extras( $product );
		}
		?>

	</div>

	<div class="kindi-pdp__below">
		<?php
		/**
		 * Tabs, related products, recently viewed — full width.
		 *
		 * @hooked woocommerce_output_product_data_tabs - 10
		 * @hooked woocommerce_output_related_products - 20
		 * @hooked kindi_output_recently_viewed - 25
		 */
		do_action( 'woocommerce_after_single_product_summary' );
		?>
	</div>

</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
