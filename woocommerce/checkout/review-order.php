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

// The "free shipping included" banner fires ONLY when the CHOSEN method is a
// real free_shipping rate. Any-0-cost-rate matching misfired for free self
// pickup: the banner claimed "משלוח חינם" while the order actually went out as
// איסוף עצמי. The chosen method's label is also captured for the totals row so
// the shopper always sees WHICH method the order uses. Furniture orders never
// get the banner (free shipping excludes furniture — see inc/woocommerce.php).
$kindi_free       = false;
$kindi_ship_label = '';
if ( $kindi_cart->needs_shipping() && $kindi_cart->show_shipping() ) {
	$kindi_chosen = WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
	foreach ( WC()->shipping()->get_packages() as $kindi_i => $kindi_pkg ) {
		$kindi_rates = (array) ( $kindi_pkg['rates'] ?? array() );
		$kindi_sel   = (string) ( $kindi_chosen[ $kindi_i ] ?? '' );
		$kindi_rate  = ( '' !== $kindi_sel && isset( $kindi_rates[ $kindi_sel ] ) ) ? $kindi_rates[ $kindi_sel ] : null;
		if ( $kindi_rate ) {
			$kindi_ship_label = wp_strip_all_tags( (string) $kindi_rate->get_label() );
			if ( 'free_shipping' === $kindi_rate->get_method_id()
				&& ! ( function_exists( 'kindi_cart_has_furniture' ) && kindi_cart_has_furniture() ) ) {
				$kindi_free = true;
			}
			break;
		}
	}
}
$kindi_count    = (int) $kindi_cart->get_cart_contents_count();
$kindi_btn_text = apply_filters( 'woocommerce_order_button_text', __( 'Place order', 'woocommerce' ) );
?>
<div class="shop_table woocommerce-checkout-review-order-table kindi-summary">
	<div class="kindi-summary__head">
		<div class="kindi-summary__headtext">
			<strong><?php esc_html_e( 'סיכום הזמנה', 'kindi' ); ?></strong>
			<span><?php printf( esc_html( _n( '%d פריט', '%d פריטים', $kindi_count, 'kindi' ) ), $kindi_count ); ?></span>
		</div>
		<a class="kindi-summary__edit" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'ערוך עגלה', 'kindi' ); ?></a>
	</div>

	<?php
	// Furniture orders: free shipping never applies — state it. Otherwise, below
	// the threshold, nudge with the exact amount left instead of silence.
	$kindi_furn  = $kindi_cart->needs_shipping()
		&& function_exists( 'kindi_cart_has_furniture' ) && kindi_cart_has_furniture();
	$kindi_nudge = 0.0;
	if ( ! $kindi_free && ! $kindi_furn && $kindi_cart->needs_shipping() ) {
		$kindi_threshold = (float) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );
		if ( $kindi_threshold > 0 ) {
			$kindi_nudge = max( 0.0, $kindi_threshold - (float) $kindi_cart->get_displayed_subtotal() );
		}
	}
	?>
	<?php if ( $kindi_free ) : ?>
	<div class="kindi-summary__free">
		<?php echo kindi_icon( 'check', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php esc_html_e( 'מזל טוב! משלוח חינם כלול בהזמנה', 'kindi' ); ?></span>
	</div>
	<?php elseif ( $kindi_furn ) : ?>
	<div class="kindi-summary__free kindi-summary__free--nudge">
		<?php echo kindi_icon( 'truck', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php esc_html_e( 'הטבת משלוח חינם לא כוללת מוצרי ריהוט', 'kindi' ); ?></span>
	</div>
	<?php elseif ( $kindi_nudge > 0 ) : ?>
	<div class="kindi-summary__free kindi-summary__free--nudge">
		<?php echo kindi_icon( 'truck', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<span><?php printf( wp_kses_post( __( 'עוד %s למשלוח חינם!', 'kindi' ) ), wp_kses_post( wc_price( $kindi_nudge ) ) ); ?></span>
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
						<button type="button" class="kindi-summary__remove" data-key="<?php echo esc_attr( $kindi_key ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'הסרת פריט: %s', 'kindi' ), $kindi_product->get_name() ) ); ?>">&times;</button>
						<span class="kindi-mcqty" data-key="<?php echo esc_attr( $kindi_key ); ?>">
							<button type="button" class="kindi-mcqty__b" data-d="-1" aria-label="<?php echo esc_attr( sprintf( __( 'הפחתת כמות: %s', 'kindi' ), $kindi_product->get_name() ) ); ?>">&#8722;</button>
							<span class="kindi-mcqty__n"><?php echo esc_html( (string) $kindi_item['quantity'] ); ?></span>
							<button type="button" class="kindi-mcqty__b" data-d="1" aria-label="<?php echo esc_attr( sprintf( __( 'הוספת כמות: %s', 'kindi' ), $kindi_product->get_name() ) ); ?>">&#43;</button>
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

	<?php
	// Anchor for the Gifta gift-card box + its notice, so they sit in the summary
	// column directly below the coupon (see inc/checkout.php).
	do_action( 'kindi_summary_after_coupon' );
	?>

	<div class="kindi-summary__totals">
		<?php // Core `cart-subtotal` class kept alongside BEM: third-party plugins (e.g. Simply Club) read the amount via `.cart-subtotal .woocommerce-Price-amount.amount`. ?>
		<div class="kindi-summary__row cart-subtotal"><span><?php esc_html_e( 'סכום ביניים', 'kindi' ); ?></span><span><?php wc_cart_totals_subtotal_html(); ?></span></div>

		<?php foreach ( $kindi_cart->get_coupons() as $kindi_code => $kindi_coupon ) : ?>
		<div class="kindi-summary__row kindi-summary__row--coupon cart-discount coupon-<?php echo esc_attr( sanitize_title( $kindi_code ) ); ?>">
			<span><?php wc_cart_totals_coupon_label( $kindi_coupon ); ?> <button type="button" class="kindi-coupon__remove" data-kindi-coupon-remove="<?php echo esc_attr( $kindi_code ); ?>" aria-label="<?php esc_attr_e( 'הסרת קופון', 'kindi' ); ?>">&times;</button></span>
			<span><?php wc_cart_totals_coupon_html( $kindi_coupon ); ?></span>
		</div>
		<?php endforeach; ?>

		<?php if ( $kindi_cart->needs_shipping() && $kindi_cart->show_shipping() ) : ?>
		<div class="kindi-summary__row"><span><?php esc_html_e( 'משלוח', 'kindi' ); ?><?php echo '' !== $kindi_ship_label ? ' — ' . esc_html( $kindi_ship_label ) : ''; ?></span><span><?php echo wp_kses_post( $kindi_cart->get_cart_shipping_total() ); ?></span></div>
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
	</div>

	<div class="kindi-summary__place">
		<?php // Core `order-total` class kept alongside BEM: Simply Club's av-box.js resolves the order total via `.order-total .woocommerce-Price-amount` and crashes when absent. ?>
		<div class="kindi-summary__grand order-total">
			<span class="kindi-summary__grand-label"><?php esc_html_e( 'סה"כ לתשלום', 'kindi' ); ?><small><?php esc_html_e( 'כולל מע"מ', 'kindi' ); ?></small></span>
			<span class="kindi-summary__grand-amount"><?php wc_cart_totals_order_total_html(); ?></span>
		</div>
		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>
		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>
		<?php
		// Marketing-consent + terms checkboxes render here, stacked just above the
		// place-order button (terms moved up from below the button).
		wc_get_template( 'checkout/terms.php' );
		?>
		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt kindi-placeorder" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $kindi_btn_text ) . '" data-value="' . esc_attr( $kindi_btn_text ) . '">' . esc_html( $kindi_btn_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
		<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>
	</div>
</div>
