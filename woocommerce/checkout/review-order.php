<?php
/**
 * Checkout order summary — Kindi override.
 *
 * Custom interactive summary (1/3 column): item list with per-line quantity
 * steppers + remove, a coupon box, a free-shipping note and the totals. Shipping
 * *method selection* lives in its own card in the main column (see
 * inc/checkout.php → kindi_shipping_section), so it is intentionally omitted
 * here. The root keeps the `.woocommerce-checkout-review-order-table` class so
 * WooCommerce's AJAX fragment refresh keeps working.
 *
 * @package Kindi
 */

defined( 'ABSPATH' ) || exit;

$kindi_cart = WC()->cart;
if ( ! $kindi_cart ) {
	return;
}

// Free shipping available for the current packages?
$kindi_free = false;
if ( $kindi_cart->needs_shipping() && $kindi_cart->show_shipping() ) {
	foreach ( WC()->shipping()->get_packages() as $kindi_pkg ) {
		foreach ( (array) ( $kindi_pkg['rates'] ?? array() ) as $kindi_rate ) {
			if ( 0.0 === (float) $kindi_rate->get_cost() ) {
				$kindi_free = true;
				break 2;
			}
		}
	}
}
$kindi_count = (int) $kindi_cart->get_cart_contents_count();
?>
<div class="shop_table woocommerce-checkout-review-order-table kindi-summary">
	<div class="kindi-summary__head">
		<div class="kindi-summary__headtext">
			<strong><?php esc_html_e( 'סיכום הזמנה', 'kindi' ); ?></strong>
			<span><?php printf( esc_html( _n( '%d פריט', '%d פריטים', $kindi_count, 'kindi' ) ), $kindi_count ); ?></span>
		</div>
		<a class="kindi-summary__edit" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'ערוך עגלה', 'kindi' ); ?></a>
	</div>

	<?php if ( $kindi_free ) : ?>
	<div class="kindi-summary__free">
		<?php echo kindi_icon( 'check', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php esc_html_e( 'מזל טוב! משלוח חינם כלול בהזמנה', 'kindi' ); ?></span>
	</div>
	<?php endif; ?>

	<ul class="kindi-summary__items">
		<?php
		foreach ( $kindi_cart->get_cart() as $kindi_key => $kindi_item ) {
			$kindi_product = $kindi_item['data'] ?? null;
			if ( ! $kindi_product instanceof WC_Product || ! $kindi_product->exists() || $kindi_item['quantity'] <= 0 ) {
				continue;
			}
			$kindi_link  = $kindi_product->is_visible() ? $kindi_product->get_permalink( $kindi_item ) : '';
			$kindi_thumb = $kindi_product->get_image( 'woocommerce_gallery_thumbnail' );
			$kindi_meta  = wc_get_formatted_cart_item_data( $kindi_item, true );
			?>
			<li class="kindi-summary__item">
				<span class="kindi-summary__img"><?php echo $kindi_thumb; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<div class="kindi-summary__info">
					<?php if ( $kindi_link ) : ?>
						<a class="kindi-summary__title" href="<?php echo esc_url( $kindi_link ); ?>"><?php echo esc_html( $kindi_product->get_name() ); ?></a>
					<?php else : ?>
						<span class="kindi-summary__title"><?php echo esc_html( $kindi_product->get_name() ); ?></span>
					<?php endif; ?>
					<?php if ( $kindi_meta ) : ?>
						<span class="kindi-summary__meta"><?php echo wp_kses_post( $kindi_meta ); ?></span>
					<?php endif; ?>
					<div class="kindi-summary__controls">
						<button type="button" class="kindi-summary__remove" data-key="<?php echo esc_attr( $kindi_key ); ?>" aria-label="<?php esc_attr_e( 'הסרת פריט', 'kindi' ); ?>">&times;</button>
						<span class="kindi-mcqty" data-key="<?php echo esc_attr( $kindi_key ); ?>">
							<button type="button" class="kindi-mcqty__b" data-d="-1" aria-label="<?php esc_attr_e( 'הפחתת כמות', 'kindi' ); ?>">&#8722;</button>
							<span class="kindi-mcqty__n"><?php echo esc_html( (string) $kindi_item['quantity'] ); ?></span>
							<button type="button" class="kindi-mcqty__b" data-d="1" aria-label="<?php esc_attr_e( 'הוספת כמות', 'kindi' ); ?>">&#43;</button>
						</span>
					</div>
				</div>
				<span class="kindi-summary__price"><?php echo $kindi_product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</li>
			<?php
		}
		?>
	</ul>

	<div class="kindi-coupon" data-kindi-coupon>
		<span class="kindi-coupon__ic"><?php echo kindi_icon( 'tag', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		<input type="text" class="kindi-coupon__input" placeholder="<?php esc_attr_e( 'קוד קופון', 'kindi' ); ?>" data-kindi-coupon-input aria-label="<?php esc_attr_e( 'קוד קופון', 'kindi' ); ?>" />
		<button type="button" class="kindi-coupon__btn" data-kindi-coupon-apply><?php esc_html_e( 'החל', 'kindi' ); ?></button>
	</div>

	<div class="kindi-summary__totals">
		<div class="kindi-summary__row"><span><?php esc_html_e( 'סכום ביניים', 'kindi' ); ?></span><span><?php wc_cart_totals_subtotal_html(); ?></span></div>

		<?php foreach ( $kindi_cart->get_coupons() as $kindi_code => $kindi_coupon ) : ?>
		<div class="kindi-summary__row kindi-summary__row--coupon">
			<span><?php wc_cart_totals_coupon_label( $kindi_coupon ); ?> <button type="button" class="kindi-coupon__remove" data-kindi-coupon-remove="<?php echo esc_attr( $kindi_code ); ?>" aria-label="<?php esc_attr_e( 'הסרת קופון', 'kindi' ); ?>">&times;</button></span>
			<span><?php wc_cart_totals_coupon_html( $kindi_coupon ); ?></span>
		</div>
		<?php endforeach; ?>

		<?php if ( $kindi_cart->needs_shipping() && $kindi_cart->show_shipping() ) : ?>
		<div class="kindi-summary__row"><span><?php esc_html_e( 'משלוח', 'kindi' ); ?></span><span><?php echo wp_kses_post( $kindi_cart->get_cart_shipping_total() ); ?></span></div>
		<?php endif; ?>

		<?php foreach ( $kindi_cart->get_fees() as $kindi_fee ) : ?>
		<div class="kindi-summary__row"><span><?php echo esc_html( $kindi_fee->name ); ?></span><span><?php wc_cart_totals_fee_html( $kindi_fee ); ?></span></div>
		<?php endforeach; ?>

		<?php
		if ( wc_tax_enabled() && ! $kindi_cart->display_prices_including_tax() ) {
			foreach ( $kindi_cart->get_tax_totals() as $kindi_tax ) {
				echo '<div class="kindi-summary__row"><span>' . esc_html( $kindi_tax->label ) . '</span><span>' . wp_kses_post( $kindi_tax->formatted_amount ) . '</span></div>';
			}
		}
		?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>
		<div class="kindi-summary__row kindi-summary__row--total"><span><?php esc_html_e( 'סה"כ', 'kindi' ); ?></span><span><?php wc_cart_totals_order_total_html(); ?></span></div>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
	</div>
</div>
