<?php
/**
 * Checkout form — Kindi override.
 *
 * Removes WooCommerce's col2-set / col-1 / col-2 wrappers so billing and
 * shipping fields render flat inside #customer_details. This lets our
 * .kindi-co two-column grid (injected via the before/after_customer_details
 * hooks in inc/checkout.php) work correctly without the native floating
 * columns fighting the layout.
 *
 * Without this override, wc-address-i18n.js would re-append all billing
 * fields into the .woocommerce-billing-fields__field-wrapper in priority
 * order, collapsing our two-card layout (contact + address) into one.
 *
 * @package Kindi
 * @see woocommerce/templates/checkout/form-checkout.php
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>"
	enctype="multipart/form-data">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div id="customer_details">
			<?php do_action( 'woocommerce_checkout_billing' ); ?>
			<?php do_action( 'woocommerce_checkout_shipping' ); ?>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

	<?php endif; ?>

	<div id="order_review" class="woocommerce-checkout-review-order" aria-live="polite" aria-atomic="false">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

</form>
<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
