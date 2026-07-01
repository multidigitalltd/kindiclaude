<?php
/**
 * Cart & checkout enhancements — progress steps, free-shipping bar on checkout,
 * and a club/coupon banner. Styling lives in woocommerce.css.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Force shipping to the billing address on the front end, which removes the
 * "Ship to a different address?" toggle and the separate shipping fields
 * (shipping still calculates from the billing address). Front-end only, so the
 * WooCommerce settings screen keeps showing the real stored value.
 *
 * @param mixed $value Stored option value.
 * @return mixed
 */
function kindi_force_ship_to_billing( $value ) {
	if ( is_admin() || ! apply_filters( 'kindi_force_billing_shipping', true ) ) {
		return $value;
	}
	return 'billing_only';
}
add_filter( 'option_woocommerce_ship_to_destination', 'kindi_force_ship_to_billing' );

/**
 * Render the cart → details → payment → done progress steps.
 *
 * @param string $active Active step key: 'cart' | 'details'.
 * @return void
 */
function kindi_checkout_steps( string $active ): void {
	$steps = array(
		'cart'    => 'עגלה',
		'details' => 'פרטים ומשלוח',
		'payment' => 'תשלום',
		'done'    => 'אישור',
	);
	$order   = array_keys( $steps );
	$active_i = array_search( $active, $order, true );

	echo '<ol class="kindi-steps">';
	foreach ( $order as $i => $key ) {
		$state = $i < $active_i ? 'done' : ( $i === $active_i ? 'active' : 'future' );
		printf(
			'<li class="kindi-step is-%s"><span class="kindi-step__n">%s</span><span class="kindi-step__l">%s</span></li>',
			esc_attr( $state ),
			'done' === $state ? '✓' : esc_html( (string) ( $i + 1 ) ),
			esc_html( $steps[ $key ] )
		);
	}
	echo '</ol>';
}

/**
 * Steps on the cart page.
 *
 * @return void
 */
function kindi_cart_steps(): void {
	kindi_checkout_steps( 'cart' );
}
add_action( 'woocommerce_before_cart', 'kindi_cart_steps', 1 );

/**
 * Steps + free-shipping bar on the checkout page.
 *
 * @return void
 */
function kindi_checkout_top(): void {
	kindi_checkout_steps( 'details' );
	if ( function_exists( 'kindi_free_shipping_progress' ) ) {
		kindi_free_shipping_progress();
	}
}
add_action( 'woocommerce_before_checkout_form', 'kindi_checkout_top', 5 );

/**
 * Simply Club box inside the "פרטי קשר" contact card (step 1), centred.
 *
 * @return void
 */
function kindi_checkout_login_row(): void {
	if ( ! shortcode_exists( 'simply_club_offerbox' ) ) {
		return;
	}
	$club = trim( do_shortcode( '[simply_club_offerbox]' ) );
	if ( '' === $club ) {
		return;
	}
	echo '<div class="kindi-clublogin">' . $club . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- Simply Club shortcode output.
}
add_action( 'kindi_before_contact_fields', 'kindi_checkout_login_row' );

// Hide Simply Club's own top bar (rendered site-wide by the plugin).
add_filter( 'simply_show_top_bar', '__return_false' );

/**
 * Relabel + reorder the billing fields to match the design (form-billing.php
 * splits them into the "contact" and "address" cards). Drops the company field;
 * pairs fields two-up; country/state are left to WooCommerce (auto-hidden when
 * the store sells to a single country).
 *
 * @param array<string,array<string,array<string,mixed>>> $fields Checkout fields.
 * @return array<string,array<string,array<string,mixed>>>
 */
function kindi_checkout_field_layout( array $fields ): array {
	if ( empty( $fields['billing'] ) ) {
		return $fields;
	}

	// Guarantee the contact fields exist. Some stores/plugins strip
	// billing_phone (or billing_email) from the checkout, which leaves the
	// design's "פרטי קשר" card without a phone input. Re-add them so they both
	// render and save onto the order.
	if ( ! isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone'] = array(
			'type'         => 'tel',
			'required'     => true,
			'validate'     => array( 'phone' ),
			'autocomplete' => 'tel',
		);
	}
	if ( ! isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email'] = array(
			'type'         => 'email',
			'required'     => true,
			'validate'     => array( 'email' ),
			'autocomplete' => 'email username',
		);
	}

	// label, placeholder, priority, row-class.
	$map = array(
		'billing_first_name' => array( 'שם פרטי', 'ישראלה', 10, 'form-row-first' ),
		'billing_last_name'  => array( 'שם משפחה', 'ישראלי', 20, 'form-row-last' ),
		'billing_phone'      => array( 'טלפון נייד', '050-1234567', 30, 'form-row-first' ),
		'billing_email'      => array( 'אימייל', 'name@example.com', 40, 'form-row-last' ),
		'billing_address_1'  => array( 'רחוב', 'הרצל', 50, 'form-row-first' ),
		'billing_address_2'  => array( 'מספר', '12', 60, 'form-row-last' ),
		'billing_city'       => array( 'עיר', 'תל אביב', 70, 'form-row-first' ),
		'billing_postcode'   => array( 'מיקוד', '6100000', 80, 'form-row-last' ),
	);

	foreach ( $map as $key => $cfg ) {
		if ( ! isset( $fields['billing'][ $key ] ) ) {
			continue;
		}
		$fields['billing'][ $key ]['label']       = $cfg[0];
		$fields['billing'][ $key ]['placeholder'] = $cfg[1];
		$fields['billing'][ $key ]['priority']    = $cfg[2];
		$fields['billing'][ $key ]['class']       = array( $cfg[3] );
		$fields['billing'][ $key ]['input_class'] = array( 'input-text' );
	}

	unset( $fields['billing']['billing_company'] );

	// Israel-only store: drop the unused "state/region" field so the address card
	// shows exactly street · apartment · city · postcode.
	if ( isset( $fields['billing']['billing_state'] ) ) {
		$fields['billing']['billing_state']['required'] = false;
		$fields['billing']['billing_state']['class']    = array( 'kindi-hidden-field' );
	}

	// Single-country store: keep the country field in the DOM (so the order still
	// submits "IL") but hide it from view — there is nothing to choose.
	if ( isset( $fields['billing']['billing_country'] ) ) {
		$fields['billing']['billing_country']['class'] = array( 'kindi-hidden-field' );
	}
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['type'] = 'email';
	}

	// address_2 is repurposed as the (required) house-number field, so give it a
	// visible label instead of WooCommerce's screen-reader-only optional one.
	if ( isset( $fields['billing']['billing_address_2'] ) ) {
		$fields['billing']['billing_address_2']['required']    = true;
		$fields['billing']['billing_address_2']['label_class'] = array();
	}

	return $fields;
}
// Late priority so our labels/layout win over locale + plugin field filters.
add_filter( 'woocommerce_checkout_fields', 'kindi_checkout_field_layout', 9999 );

/**
 * Shipping-method card (box 3) HTML, rendered in the main process column and
 * refreshed as an AJAX fragment. Uses WooCommerce's exact radio names so the
 * native change handler recalculates totals and the form submits the choice.
 *
 * @return string
 */
function kindi_shipping_section_html(): string {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->cart->needs_shipping() || ! WC()->cart->show_shipping() ) {
		return '<section class="kindi-cobox kindi-shipsection" id="kindi-box-shipping" hidden></section>';
	}
	WC()->cart->calculate_shipping();
	$packages = WC()->shipping()->get_packages();
	$chosen   = WC()->session->get( 'chosen_shipping_methods' );
	$chosen   = is_array( $chosen ) ? $chosen : array();

	ob_start();
	?>
	<section class="kindi-cobox kindi-shipsection" id="kindi-box-shipping">
		<header class="kindi-cobox__head">
			<span class="kindi-cobox__n">3</span>
			<div class="kindi-cobox__heading">
				<h3 class="kindi-cobox__title"><?php esc_html_e( 'שיטת משלוח', 'kindi' ); ?></h3>
				<p class="kindi-cobox__sub"><?php esc_html_e( 'בחרו כיצד נשלח אליכם', 'kindi' ); ?></p>
			</div>
		</header>
		<div class="kindi-cobox__body">
			<?php
			foreach ( $packages as $i => $package ) :
				$available = $package['rates'];
				$picked    = $chosen[ $i ] ?? '';
				if ( '' === $picked && $available ) {
					$picked = current( $available )->get_id();
				}
				if ( empty( $available ) ) :
					?>
					<p class="kindi-ship__none"><?php esc_html_e( 'אין שיטות משלוח זמינות לכתובת זו.', 'kindi' ); ?></p>
					<?php
				else :
					?>
					<ul class="kindi-ship" id="shipping_method">
						<?php
						foreach ( $available as $method ) :
							$cost = (float) $method->get_cost();
							$rid  = 'shipping_method_' . $i . '_' . sanitize_title( $method->get_id() );
							$icon = kindi_shipping_icon_name( $method );
							?>
							<li class="kindi-ship__item">
								<input type="radio" name="shipping_method[<?php echo esc_attr( (string) $i ); ?>]" data-index="<?php echo esc_attr( (string) $i ); ?>" id="<?php echo esc_attr( $rid ); ?>" value="<?php echo esc_attr( $method->get_id() ); ?>" class="shipping_method" <?php checked( $method->get_id(), $picked ); ?> />
								<label for="<?php echo esc_attr( $rid ); ?>">
									<span class="kindi-ship__ic"><?php echo kindi_icon( $icon, 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="kindi-ship__main">
										<span class="kindi-ship__title"><?php echo esc_html( wp_strip_all_tags( $method->get_label() ) ); ?></span>
									</span>
									<span class="kindi-ship__price">
										<?php
										if ( kindi_shipping_is_coordinated( $method ) ) {
											echo '<span class="kindi-ship__coord">' . esc_html__( 'עלות בתיאום טלפוני', 'kindi' ) . '</span>';
										} elseif ( $cost > 0 ) {
											echo wp_kses_post( wc_price( $cost ) );
										} else {
											echo '<span class="kindi-ship__free">' . esc_html__( 'חינם', 'kindi' ) . '</span>';
										}
										?>
									</span>
								</label>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
				endif;
			endforeach;
			?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Render the shipping-method card in the main column (before payment).
 *
 * @return void
 */
function kindi_shipping_section(): void {
	echo kindi_shipping_section_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping.
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_shipping_section', 10 );

/**
 * Keep the shipping-method card in sync with WooCommerce's AJAX recalculation.
 *
 * @param array<string,string> $fragments Order-review fragments.
 * @return array<string,string>
 */
function kindi_shipping_section_fragment( array $fragments ): array {
	$fragments['.kindi-shipsection'] = kindi_shipping_section_html();
	return $fragments;
}
add_filter( 'woocommerce_update_order_review_fragments', 'kindi_shipping_section_fragment' );

/**
 * Wrap the (relocated) payment block in a numbered "4 — אמצעי תשלום" card.
 *
 * @return void
 */
function kindi_payment_box_open(): void {
	echo '<section class="kindi-cobox kindi-cobox--payment" id="kindi-box-payment">'
		. '<header class="kindi-cobox__head"><span class="kindi-cobox__n">4</span>'
		. '<div class="kindi-cobox__heading"><h3 class="kindi-cobox__title">' . esc_html__( 'אמצעי תשלום', 'kindi' ) . '</h3>'
		. '<p class="kindi-cobox__sub">' . esc_html__( 'עסקה מוצפנת ומאובטחת לחלוטין', 'kindi' ) . '</p></div></header>'
		. '<div class="kindi-cobox__body">';
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_payment_box_open', 18 );

/**
 * Close the payment card wrapper (after the payment block at priority 20).
 *
 * @return void
 */
function kindi_payment_box_close(): void {
	echo '</div></section>';
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_payment_box_close', 22 );

/**
 * Greeting-card add-on fee (₪). Gift wrapping itself is free.
 *
 * @return int
 */
function kindi_gift_card_fee(): int {
	return (int) apply_filters( 'kindi_gift_card_fee', 10 );
}

/**
 * Render the gift box below the payment card: two independent add-ons — free
 * gift wrapping and a paid greeting card — plus a personal-greeting textarea.
 *
 * @return void
 */
function kindi_gift_wrap_box(): void {
	$card_fee = kindi_gift_card_fee();
	$chevron  = '<svg class="kindi-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	$message  = isset( $_POST['kindi_gift_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kindi_gift_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	?>
	<section class="kindi-cobox kindi-giftbox" data-kindi-gift>
		<header class="kindi-cobox__head kindi-giftbox__head">
			<span class="kindi-giftbox__ic"><?php echo kindi_icon( 'gift', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div class="kindi-cobox__heading">
				<h3 class="kindi-giftbox__title"><?php esc_html_e( 'זו מתנה? נארוז ונוסיף ברכה', 'kindi' ); ?> <span aria-hidden="true">❤️</span></h3>
				<p class="kindi-cobox__sub"><?php printf( esc_html__( 'אריזת מתנה חינם • כרטיס ברכה %d₪', 'kindi' ), $card_fee ); ?></p>
			</div>
			<button type="button" class="kindi-giftbox__toggle" data-kindi-gift-toggle aria-label="<?php esc_attr_e( 'כיווץ', 'kindi' ); ?>"><?php echo $chevron; // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</header>
		<div class="kindi-cobox__body kindi-giftbox__body">
			<label class="kindi-giftbox__check">
				<input type="checkbox" name="kindi_gift_wrap" value="1" <?php checked( ! empty( $_POST['kindi_gift_wrap'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?> />
				<span><?php esc_html_e( 'הוסף אריזת מתנה (חינם)', 'kindi' ); ?></span>
			</label>
			<label class="kindi-giftbox__check">
				<input type="checkbox" name="kindi_gift_card" value="1" <?php checked( ! empty( $_POST['kindi_gift_card'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing ?> />
				<span><?php printf( esc_html__( 'הוסף כרטיס ברכה (%d₪)', 'kindi' ), $card_fee ); ?></span>
			</label>
			<textarea class="kindi-giftbox__msg" name="kindi_gift_message" rows="3" placeholder="<?php esc_attr_e( 'כתבו ברכה אישית…', 'kindi' ); ?>"><?php echo esc_textarea( $message ); ?></textarea>
		</div>
	</section>
	<?php
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_gift_wrap_box', 24 );

/**
 * Whether a gift add-on checkbox is selected, reading either the direct POST
 * (order submit) or the serialised checkout form (`post_data`, sent on AJAX
 * recalcs). Both flows are nonce-verified by WooCommerce before this runs.
 *
 * @param string $field Field name (kindi_gift_wrap | kindi_gift_card).
 * @return bool
 */
function kindi_gift_option_selected( string $field ): bool {
	if ( isset( $_POST[ $field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return '1' === sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	if ( isset( $_POST['post_data'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		parse_str( sanitize_text_field( wp_unslash( $_POST['post_data'] ) ), $data ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return ! empty( $data[ $field ] );
	}
	return false;
}

/**
 * Add the greeting-card fee when selected. Gift wrapping is free, so it adds
 * no fee.
 *
 * @param WC_Cart $cart Cart.
 * @return void
 */
function kindi_gift_wrap_fee_add( WC_Cart $cart ): void {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	if ( kindi_gift_option_selected( 'kindi_gift_card' ) ) {
		$cart->add_fee( __( 'כרטיס ברכה', 'kindi' ), kindi_gift_card_fee() );
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'kindi_gift_wrap_fee_add' );

/**
 * Persist the gift-wrap / greeting-card choices + greeting onto the order.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function kindi_save_gift_wrap( WC_Order $order ): void {
	$wrap = isset( $_POST['kindi_gift_wrap'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['kindi_gift_wrap'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$card = isset( $_POST['kindi_gift_card'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['kindi_gift_card'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$msg  = isset( $_POST['kindi_gift_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['kindi_gift_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$order->update_meta_data( '_kindi_gift_wrap', $wrap ? 'yes' : 'no' );
	$order->update_meta_data( '_kindi_gift_card', $card ? 'yes' : 'no' );
	if ( '' !== $msg ) {
		$order->update_meta_data( '_kindi_gift_message', $msg );
	}
}
add_action( 'woocommerce_checkout_create_order', 'kindi_save_gift_wrap' );

/*
 * ---------------------------------------------------------------------------
 * Checkout layout — two columns: the process (contact, shipping, payment) on
 * 2/3, the order summary on a sticky 1/3. We wrap the WooCommerce checkout in
 * .kindi-co / .kindi-co__main / .kindi-co__side via the checkout-form hooks and
 * relocate the payment block from the order-review column into the main one.
 * The grid itself lives in woocommerce.css.
 * ---------------------------------------------------------------------------
 */

/**
 * Move the payment block out of the order-review (summary) column into the main
 * process column, right after the customer details. Safe for AJAX: WooCommerce
 * refreshes #payment by its `.woocommerce-checkout-payment` fragment selector,
 * regardless of where it sits in the DOM.
 *
 * @return void
 */
function kindi_checkout_relocate_payment(): void {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}
	remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	add_action( 'woocommerce_checkout_after_customer_details', 'woocommerce_checkout_payment', 20 );

	// Drop the native "Have a coupon?" form at the top of the checkout — coupons
	// are entered in the order-summary coupon box instead (review-order.php).
	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
}
add_action( 'wp', 'kindi_checkout_relocate_payment' );

/**
 * Open the two-column wrapper + the main (process) column.
 *
 * @return void
 */
function kindi_checkout_cols_open(): void {
	echo '<div class="kindi-co"><div class="kindi-co__main">';
	$GLOBALS['kindi_co_open'] = true;
}
add_action( 'woocommerce_checkout_before_customer_details', 'kindi_checkout_cols_open', 5 );

/**
 * Close the main column and open the summary (side) column. Runs after the
 * relocated payment block (priority 20) on the same hook.
 *
 * @return void
 */
function kindi_checkout_cols_mid(): void {
	if ( empty( $GLOBALS['kindi_co_open'] ) ) {
		return;
	}
	echo '</div><div class="kindi-co__side">';
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_checkout_cols_mid', 30 );

/**
 * Close the summary column and the two-column wrapper.
 *
 * @return void
 */
/**
 * Trust badges + a WhatsApp "need help?" box, below the order summary in the
 * sticky side column (outside the AJAX-refreshed summary).
 *
 * @return void
 */
function kindi_checkout_side_extras(): void {
	$wa   = function_exists( 'kindi_whatsapp_url' ) ? kindi_whatsapp_url( 'היי, אשמח לעזרה בהשלמת ההזמנה' ) : '';
	$href = '' !== $wa ? $wa : '#';
	?>
	<div class="kindi-coextras">
		<div class="kindi-trustrow">
			<div class="kindi-trustrow__item"><span class="kindi-trustrow__ic"><?php echo kindi_icon( 'truck', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php esc_html_e( 'משלוח מהיר', 'kindi' ); ?></span></div>
			<div class="kindi-trustrow__item"><span class="kindi-trustrow__ic"><?php echo kindi_icon( 'rotate', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php esc_html_e( 'החזרה חינם 30 יום', 'kindi' ); ?></span></div>
			<div class="kindi-trustrow__item"><span class="kindi-trustrow__ic"><?php echo kindi_icon( 'shield', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span><span><?php esc_html_e( 'תשלום מאובטח', 'kindi' ); ?></span></div>
		</div>
		<a class="kindi-helpbox" href="<?php echo esc_url( $href ); ?>"<?php echo '' !== $wa ? ' target="_blank" rel="noopener"' : ''; ?>>
			<span class="kindi-helpbox__text">
				<strong><?php esc_html_e( 'צריכים עזרה?', 'kindi' ); ?></strong>
				<span><?php esc_html_e( 'צוות השירות שלנו זמין בוואטסאפ', 'kindi' ); ?></span>
			</span>
			<span class="kindi-helpbox__wa"><?php echo kindi_icon( 'whatsapp', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
		</a>
	</div>
	<?php
}
add_action( 'woocommerce_checkout_after_order_review', 'kindi_checkout_side_extras', 40 );

/**
 * Close the summary column and the two-column wrapper.
 *
 * @return void
 */
function kindi_checkout_cols_close(): void {
	if ( empty( $GLOBALS['kindi_co_open'] ) ) {
		return;
	}
	echo '</div></div>';
	$GLOBALS['kindi_co_open'] = false;
}
add_action( 'woocommerce_checkout_after_order_review', 'kindi_checkout_cols_close', 50 );

/**
 * Pick the icon for a shipping rate: a storefront for self-pickup, a truck for
 * everything else. Pickup is detected by WooCommerce's local_pickup method id
 * or a Hebrew/English "pickup" hint in the rate id or label.
 *
 * @param mixed $method Shipping rate (WC_Shipping_Rate) or null.
 * @return string Icon key for kindi_icon().
 */
function kindi_shipping_icon_name( $method = null ): string {
	if ( ! is_object( $method ) || ! method_exists( $method, 'get_label' ) ) {
		return 'truck';
	}
	$label = wp_strip_all_tags( (string) $method->get_label() );

	// Detect self-pickup by the label text, NOT the WooCommerce method id: this
	// store configures extra "local_pickup" instances for delivery too, so only
	// a real "איסוף" / "pickup" label should get the storefront icon.
	$is_pickup = '' !== $label
		&& ( false !== mb_stripos( $label, 'איסוף' ) || false !== stripos( $label, 'pickup' ) );

	return $is_pickup ? 'store' : 'truck';
}

/**
 * Whether a shipping rate's real cost is arranged by phone rather than free:
 * its WooCommerce cost is 0 but the label states a separate/coordinated cost
 * ("עלות … בתיאום טלפוני"). Such methods show that note instead of "חינם".
 * The free self-pickup label also mentions "בתיאום" but never "עלות", so the
 * "עלות" check keeps pickup showing as free.
 *
 * @param mixed $method Shipping rate.
 * @return bool
 */
function kindi_shipping_is_coordinated( $method = null ): bool {
	if ( ! is_object( $method ) || ! method_exists( $method, 'get_label' ) ) {
		return false;
	}
	$label = wp_strip_all_tags( (string) $method->get_label() );
	if ( '' === $label || false === mb_stripos( $label, 'עלות' ) ) {
		return false;
	}
	return false !== mb_stripos( $label, 'תיאום' ) || false !== mb_stripos( $label, 'טלפוני' );
}

/**
 * Prepend an icon to each shipping-method label (cart & checkout) so the
 * methods can render as cards with an icon + title + price. Self-pickup gets a
 * storefront icon; all other methods get a truck.
 *
 * @param string $label  Method label HTML (title + cost).
 * @param mixed  $method Shipping rate.
 * @return string
 */
function kindi_shipping_method_icon( string $label, $method = null ): string {
	return '<span class="kindi-ship__ic">' . kindi_icon( kindi_shipping_icon_name( $method ), 'kindi-icon--md' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<span class="kindi-ship__txt">' . $label . '</span>';
}
add_filter( 'woocommerce_cart_shipping_method_full_label', 'kindi_shipping_method_icon', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Gift card & Gifta integrations.
 * ---------------------------------------------------------------------------
 */

/**
 * Display title for a payment gateway. Falls back to the admin method title and
 * then a sensible default, because some gateways (e.g. Gifta) leave the
 * front-end title empty, which would render an empty payment card.
 *
 * @param mixed $gateway Payment gateway.
 * @return string Plain-text title.
 */
function kindi_payment_method_title( $gateway ): string {
	if ( ! is_object( $gateway ) ) {
		return __( 'תשלום', 'kindi' );
	}
	// Normalise: strip tags, decode entities, fold &nbsp;/whitespace, so a title
	// that is only markup or blank space counts as empty and gets a fallback.
	$norm = static function ( string $s ): string {
		$s = str_replace( "\xc2\xa0", ' ', html_entity_decode( wp_strip_all_tags( $s ), ENT_QUOTES, 'UTF-8' ) );
		return trim( (string) preg_replace( '/\s+/', ' ', $s ) );
	};
	$title = method_exists( $gateway, 'get_title' ) ? $norm( (string) $gateway->get_title() ) : '';
	if ( '' === $title && method_exists( $gateway, 'get_method_title' ) ) {
		$title = $norm( (string) $gateway->get_method_title() );
	}
	if ( '' === $title ) {
		$id    = (string) ( $gateway->id ?? '' );
		$title = false !== stripos( $id, 'gifta' ) ? __( 'כרטיס מתנה Gifta', 'kindi' ) : __( 'תשלום', 'kindi' );
	}
	return $title;
}

/**
 * Icon HTML for a payment gateway. Known local methods (Gifta, phone payment,
 * bank transfer, price quote) get a brand Kindi icon; everything else keeps the
 * gateway's own logo/icon.
 *
 * @param mixed $gateway Payment gateway.
 * @return string Icon HTML ('' if none).
 */
function kindi_payment_method_icon_html( $gateway ): string {
	if ( ! is_object( $gateway ) ) {
		return '';
	}
	$id     = (string) ( $gateway->id ?? '' );
	$title  = method_exists( $gateway, 'get_title' ) ? wp_strip_all_tags( (string) $gateway->get_title() ) : '';
	$mtitle = method_exists( $gateway, 'get_method_title' ) ? wp_strip_all_tags( (string) $gateway->get_method_title() ) : '';
	$hay    = $id . ' ' . $title . ' ' . $mtitle;

	$key = '';
	if ( false !== stripos( $hay, 'gifta' ) || false !== mb_stripos( $hay, 'מתנה' ) ) {
		$key = 'gift';
	} elseif ( false !== mb_stripos( $hay, 'טלפוני' ) || false !== stripos( $hay, 'phone' ) ) {
		$key = 'phone';
	} elseif ( 'bacs' === $id || false !== mb_stripos( $hay, 'העברה' ) || false !== mb_stripos( $hay, 'בנקאית' ) || false !== stripos( $hay, 'bank' ) ) {
		$key = 'bank';
	} elseif ( false !== mb_stripos( $hay, 'הצעת מחיר' ) || false !== mb_stripos( $hay, 'הצעה' ) || false !== stripos( $hay, 'quote' ) ) {
		$key = 'doc';
	}

	if ( '' !== $key ) {
		return kindi_icon( $key, 'kindi-icon--md' );
	}
	return (string) ( method_exists( $gateway, 'get_icon' ) ? $gateway->get_icon() : '' );
}

/**
 * Place-order button label.
 *
 * @return string
 */
function kindi_order_button_text(): string {
	return __( 'אישור הזמנה ותשלום', 'kindi' );
}
add_filter( 'woocommerce_order_button_text', 'kindi_order_button_text' );

/**
 * Whether a gateway is a Gifta gift-card gateway (id or title mentions Gifta).
 *
 * @param string $id    Gateway id.
 * @param string $label Gateway title / method title.
 * @return bool
 */
function kindi_is_gifta_gateway( string $id, string $label = '' ): bool {
	return false !== stripos( $id, 'gifta' )
		|| ( '' !== $label && ( false !== mb_stripos( $label, 'gifta' ) || false !== mb_stripos( $label, 'גיפט' ) ) );
}

/**
 * Remove Gifta's stray "connect/login" payment gateway. Gifta is a gift-card
 * *redemption* box (rendered in the summary), not a payment method, so its
 * gateway otherwise shows as an empty payment card.
 *
 * @param array<string,WC_Payment_Gateway> $gateways Available gateways.
 * @return array<string,WC_Payment_Gateway>
 */
function kindi_order_payment_gateways( array $gateways ): array {
	if ( is_admin() ) {
		return $gateways;
	}
	foreach ( $gateways as $id => $gateway ) {
		$label = '';
		if ( is_object( $gateway ) ) {
			$title  = method_exists( $gateway, 'get_title' ) ? (string) $gateway->get_title() : '';
			$mtitle = method_exists( $gateway, 'get_method_title' ) ? (string) $gateway->get_method_title() : '';
			$label  = trim( $title . ' ' . $mtitle );
		}
		if ( kindi_is_gifta_gateway( (string) $id, $label ) ) {
			unset( $gateways[ $id ] );
		}
	}
	return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'kindi_order_payment_gateways' );

/**
 * Note above the payment methods: a Gifta gift card can't be combined with
 * coupons. Shown only when a Gifta gateway is enabled.
 *
 * @return void
 */
function kindi_gifta_coupon_notice(): void {
	echo '<p class="kindi-gifta-note">'
		. esc_html__( 'לתשומת ליבכם: לא ניתן לממש כרטיס Gifta יחד עם קופון. לשימוש בכרטיס Gifta הסירו את הקופון; לשימוש בקופון בחרו אמצעי תשלום אחר.', 'kindi' )
		. '</p>';
}
// Render inside the Gifta column, below the Gifta redemption box.
add_action( 'kindi_summary_after_coupon', 'kindi_gifta_coupon_notice', 22 );

/*
 * ---------------------------------------------------------------------------
 * Gift-card redemption forms next to the coupon. Instead of moving the plugin
 * markup with JS (fragile), we re-point each plugin's own render hook to
 * kindi_summary_after_coupon (fired right below the coupon box in
 * review-order.php) and wrap them in a two-column row. YITH exposes a filter
 * for its checkout hook; Gifta is re-hooked from the payment area.
 * ---------------------------------------------------------------------------
 */

// YITH: render its checkout gift-card form below the coupon instead of at the
// top of the checkout (woocommerce_before_checkout_form).
add_filter( 'ywgc_gift_card_code_form_checkout_hook', static fn(): string => 'kindi_summary_after_coupon' );

/**
 * Remove Gifta's own redemption-form hook (it renders before the payment
 * methods) so we can render it below the coupon instead — see
 * kindi_render_gifta_form. Runs early (before the payment block renders).
 *
 * @return void
 */
function kindi_unhook_gifta_form(): void {
	global $wp_filter;
	if ( empty( $wp_filter['woocommerce_review_order_before_payment'] ) ) {
		return;
	}
	foreach ( $wp_filter['woocommerce_review_order_before_payment']->callbacks as $prio => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			$fn = $callback['function'] ?? null;
			if ( is_array( $fn ) && isset( $fn[0], $fn[1] ) && is_object( $fn[0] )
				&& 'dropdown' === $fn[1] && false !== stripos( get_class( $fn[0] ), 'gifta' ) ) {
				remove_action( 'woocommerce_review_order_before_payment', $fn, $prio );
			}
		}
	}
}
add_action( 'wp', 'kindi_unhook_gifta_form', 99 );

/**
 * Render the Gifta redemption form below the coupon. We call the plugin's own
 * renderer on a fresh instance so the markup + its delegated JS stay intact.
 *
 * @return void
 */
function kindi_render_gifta_form(): void {
	if ( ! class_exists( 'Gifta_Public' ) ) {
		return;
	}
	$gifta = new Gifta_Public( 'gifta', '1' );
	if ( method_exists( $gifta, 'dropdown' ) ) {
		$gifta->dropdown( '' );
	}
}
add_action( 'kindi_summary_after_coupon', 'kindi_render_gifta_form', 20 );

/**
 * Open/close the two-column wrapper around the relocated gift-card forms.
 * Priorities bracket YITH (10) and Gifta (20) on kindi_summary_after_coupon.
 *
 * @param string $html Literal wrapper markup.
 * @return void
 */
function kindi_giftcards_markup( string $html ): void {
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- literal wrapper markup.
}
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '<div class="kindi-giftcards">' ), 6 );
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '<div class="kindi-giftcards__col kindi-giftcards__yith">' ), 8 );
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '</div>' ), 12 );
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '<div class="kindi-giftcards__col kindi-giftcards__gifta">' ), 14 );
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '</div>' ), 24 );
add_action( 'kindi_summary_after_coupon', static fn() => kindi_giftcards_markup( '</div>' ), 90 );

/*
 * ---------------------------------------------------------------------------
 * Trust content on cart & checkout, above the footer: the homepage Google
 * reviews (both pages) and a "why choose us" band (checkout). Appended to the
 * page content so it lands below the cart/checkout and above the footer
 * regardless of the underlying layout.
 * ---------------------------------------------------------------------------
 */

/**
 * Append the trust content to the cart/checkout page body.
 *
 * Cart: reviews band (top 3 Google reviews, compact format).
 * Checkout: "why choose us" band only — the full testimonials section is
 * rendered via woocommerce_after_checkout_form (kindi_checkout_reviews).
 *
 * @param string $content Post content.
 * @return string
 */
function kindi_cart_checkout_trust( string $content ): string {
	if ( ! is_main_query() || ! in_the_loop() || is_admin() ) {
		return $content;
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return $content . kindi_reviews_band();
	}
	if ( function_exists( 'is_checkout' ) && is_checkout()
		&& ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
		return $content . kindi_why_choose_band();
	}
	return $content;
}
add_filter( 'the_content', 'kindi_cart_checkout_trust', 20 );

/*
 * ---------------------------------------------------------------------------
 * Template override enforcement — force WooCommerce to use the theme's
 * form-checkout.php regardless of any plugin that registers a lower-priority
 * woocommerce_locate_template filter (e.g. YITH, Simply, Gifta).  Without
 * this, those plugins return their own form-checkout.php first, which still
 * wraps fields in the native col2-set / col-1 / col-2 structure that breaks
 * our two-card layout.
 * ---------------------------------------------------------------------------
 */

/**
 * Force the theme's form-checkout.php via the locate step (runs before caching).
 *
 * @param string $template      Resolved template path.
 * @param string $template_name Template name relative to the WC templates dir.
 * @return string
 */
function kindi_locate_checkout_form( string $template, string $template_name ): string {
	if ( 'checkout/form-checkout.php' === $template_name ) {
		$override = KINDI_DIR . 'woocommerce/checkout/form-checkout.php';
		if ( is_readable( $override ) ) {
			return $override;
		}
	}
	return $template;
}
add_filter( 'woocommerce_locate_template', 'kindi_locate_checkout_form', 9999, 2 );

/**
 * Force the theme's form-checkout.php at the include step (runs even when the
 * path was already cached by wc_get_template's internal wp_cache_get).
 *
 * @param string $template      Template path about to be included.
 * @param string $template_name Template name relative to the WC templates dir.
 * @return string
 */
function kindi_use_checkout_form( string $template, string $template_name ): string {
	if ( 'checkout/form-checkout.php' === $template_name ) {
		$override = KINDI_DIR . 'woocommerce/checkout/form-checkout.php';
		if ( is_readable( $override ) ) {
			return $override;
		}
	}
	return $template;
}
add_filter( 'wc_get_template', 'kindi_use_checkout_form', 9999, 2 );

/**
 * Full testimonials section after the checkout form — identical markup and
 * fallback data to patterns/testimonials.php so the design matches the homepage.
 * Hooked to woocommerce_after_checkout_form, which fires reliably on both
 * classic and FSE themes (unlike the_content which needs in_the_loop()).
 *
 * @return void
 */
function kindi_checkout_reviews(): void {
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		return;
	}

	$data       = function_exists( 'kindi_google_reviews' ) ? kindi_google_reviews() : array();
	$has_google = ! empty( $data['reviews'] );
	$reviews    = $has_google ? $data['reviews'] : array(
		array(
			'text'   => 'חנות מדהימה! קניתי לבן שלי ילקוט ומחברות לכיתה א\' — השירות מקסים, המחירים נהדרים והמשלוח הגיע למחרת. ממליצה לכולם!',
			'name'   => 'שרה כהן',
			'role'   => 'אמא לארבעה • בני ברק',
			'letter' => 'ש',
		),
		array(
			'text'   => 'אני גננת שמזמינה מקינדר טויס כבר 5 שנים. המבחר של חומרי היצירה מצוין, המחירים הוגנים והם תמיד מוכנים להמליץ. מקצועיים אמיתיים.',
			'name'   => 'מירי פרץ',
			'role'   => 'גננת • ירושלים',
			'letter' => 'מ',
		),
		array(
			'text'   => 'אתר נוח להזמנה, מבחר ענק של משחקי קופסה ובובות. הילדים שלי מתים על המשחקים שקניתי לחנוכה. גם השירות הטלפוני מעולה!',
			'name'   => 'יוסי לוי',
			'role'   => 'אבא מאושר • פתח תקווה',
			'letter' => 'י',
		),
	);

	$grev_link = $has_google && ! empty( $data['link'] ) ? (string) $data['link'] : '';
	$grev_tag  = $grev_link ? 'a' : 'div';

	ob_start();
	?>
	<section class="kindi-section" aria-label="<?php esc_attr_e( 'ביקורות לקוחות', 'kindi' ); ?>">
		<div class="kindi-sechead">
			<div class="kindi-sechead__text">
				<span class="kindi-eyebrow"><?php echo $has_google ? '★ Google' : esc_html__( 'לקוחות מספרים', 'kindi' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h2 class="kindi-sec-title"><?php esc_html_e( 'למה ', 'kindi' ); ?><span class="kindi-hl"><?php esc_html_e( 'בוחרים בקינדי', 'kindi' ); ?></span>?</h2>
			</div>
			<?php if ( $has_google ) : ?>
			<<?php echo $grev_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="kindi-grev__score<?php echo $grev_link ? ' kindi-grev__score--link' : ''; ?>"<?php echo $grev_link ? ' href="' . esc_url( $grev_link ) . '" target="_blank" rel="noopener" title="' . esc_attr__( 'לצפייה בכל הביקורות בגוגל', 'kindi' ) . '"' : ''; ?>>
				<strong><?php echo esc_html( number_format( (float) $data['rating'], 1 ) ); ?></strong>
				<span class="kindi-grev__stars"><?php for ( $s = 0; $s < 5; $s++ ) {
						echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} ?></span>
				<span class="kindi-grev__count"><?php echo esc_html( number_format_i18n( (int) ( $data['total'] ?? count( $reviews ) ) ) ); ?>+ <?php esc_html_e( 'ביקורות בגוגל', 'kindi' ); ?></span>
			</<?php echo $grev_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endif; ?>
		</div>
		<div class="kindi-tst">
			<?php foreach ( $reviews as $idx => $t ) : ?>
			<article class="kindi-tst__card<?php echo $idx >= 3 ? ' is-hidden' : ''; ?>">
				<span class="kindi-tst__quote" aria-hidden="true">"</span>
				<div class="kindi-tst__stars">
					<?php for ( $s = 0; $s < 5; $s++ ) {
						echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} ?>
				</div>
				<p class="kindi-tst__text"><?php echo esc_html( $t['text'] ); ?></p>
				<div class="kindi-tst__foot">
					<?php if ( ! empty( $t['photo'] ) ) : ?>
					<img class="kindi-tst__avatar kindi-tst__avatar--img" src="<?php echo esc_url( $t['photo'] ); ?>" alt="" loading="lazy" decoding="async" width="44" height="44" referrerpolicy="no-referrer">
					<?php else : ?>
					<span class="kindi-tst__avatar"><?php echo esc_html( $t['letter'] ?? '★' ); ?></span>
					<?php endif; ?>
					<span>
						<span class="kindi-tst__name"><?php echo esc_html( $t['name'] ); ?></span><br>
						<span class="kindi-tst__role"><?php echo esc_html( $t['role'] ?? 'ביקורת מאומתת מ-Google' ); ?></span>
					</span>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
		<?php if ( count( $reviews ) > 3 ) : ?>
		<div class="kindi-tst-more">
			<button type="button" class="kindi-btn kindi-btn--ghost" data-kindi-more-reviews>
				<?php echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php esc_html_e( 'טען עוד ביקורות', 'kindi' ); ?>
			</button>
		</div>
		<?php endif; ?>
	</section>
	<?php
	echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaping.
}
add_action( 'woocommerce_after_checkout_form', 'kindi_checkout_reviews', 10 );

/**
 * The homepage Google reviews as a compact band (top 3), reusing the
 * testimonial card styles. Returns '' when there are no reviews to show.
 *
 * @return string
 */
function kindi_reviews_band(): string {
	$data    = function_exists( 'kindi_google_reviews' ) ? kindi_google_reviews() : array();
	$reviews = ! empty( $data['reviews'] ) ? array_slice( $data['reviews'], 0, 3 ) : array();
	if ( ! $reviews ) {
		return '';
	}
	$has_score = ! empty( $data['rating'] );
	$link      = ! empty( $data['link'] ) ? (string) $data['link'] : '';

	ob_start();
	?>
	<section class="kindi-section kindi-trustband" aria-label="<?php esc_attr_e( 'ביקורות לקוחות', 'kindi' ); ?>">
		<div class="kindi-sechead">
			<div class="kindi-sechead__text">
				<span class="kindi-eyebrow"><?php echo $has_score ? '★ Google' : esc_html__( 'לקוחות מספרים', 'kindi' ); ?></span>
				<h2 class="kindi-sec-title"><?php esc_html_e( 'הלקוחות שלנו ממליצים', 'kindi' ); ?></h2>
			</div>
			<?php if ( $has_score ) :
				$tag = $link ? 'a' : 'div'; ?>
			<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput -- literal 'a'|'div'. ?> class="kindi-grev__score<?php echo $link ? ' kindi-grev__score--link' : ''; ?>"<?php echo $link ? ' href="' . esc_url( $link ) . '" target="_blank" rel="noopener" title="' . esc_attr__( 'לצפייה בכל הביקורות בגוגל', 'kindi' ) . '"' : ''; ?>>
				<strong><?php echo esc_html( number_format( (float) $data['rating'], 1 ) ); ?></strong>
				<span class="kindi-grev__stars"><?php for ( $s = 0; $s < 5; $s++ ) {
					echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
				} ?></span>
				<span class="kindi-grev__count"><?php echo esc_html( number_format_i18n( (int) ( $data['total'] ?? count( $reviews ) ) ) ); ?>+ <?php esc_html_e( 'ביקורות בגוגל', 'kindi' ); ?></span>
			</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endif; ?>
		</div>
		<div class="kindi-tst">
			<?php foreach ( $reviews as $t ) : ?>
			<article class="kindi-tst__card">
				<span class="kindi-tst__quote" aria-hidden="true">”</span>
				<div class="kindi-tst__stars"><?php for ( $i = 0; $i < 5; $i++ ) {
					echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
				} ?></div>
				<p class="kindi-tst__text"><?php echo esc_html( $t['text'] ); ?></p>
				<div class="kindi-tst__foot">
					<?php if ( ! empty( $t['photo'] ) ) : ?>
					<img class="kindi-tst__avatar kindi-tst__avatar--img" src="<?php echo esc_url( $t['photo'] ); ?>" alt="" loading="lazy" decoding="async" width="44" height="44" referrerpolicy="no-referrer">
					<?php else : ?>
					<span class="kindi-tst__avatar"><?php echo esc_html( $t['letter'] ?? '★' ); ?></span>
					<?php endif; ?>
					<span>
						<span class="kindi-tst__name"><?php echo esc_html( $t['name'] ); ?></span><br>
						<span class="kindi-tst__role"><?php echo esc_html( $t['role'] ?? 'ביקורת מאומתת מ-Google' ); ?></span>
					</span>
				</div>
			</article>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * "למה לבחור בנו?" band — four reassurance points, reusing the USP strip look.
 *
 * @return string
 */
function kindi_why_choose_band(): string {
	$items = array(
		array( 'icon' => 'phone', 'title' => 'שירות לקוחות אישי', 'sub' => 'עם מענה מהיר' ),
		array( 'icon' => 'gem', 'title' => 'מוצרים איכותיים', 'sub' => 'בלבד' ),
		array( 'icon' => 'truck', 'title' => 'משלוח עד הבית', 'sub' => 'ארוז בצורה נקייה ומסודרת' ),
		array( 'icon' => 'rocket', 'title' => 'משלוח מהיר', 'sub' => 'החבילה שלכם נארזת באותו יום' ),
	);

	ob_start();
	?>
	<section class="kindi-section kindi-why" aria-label="<?php esc_attr_e( 'למה לבחור בנו', 'kindi' ); ?>">
		<div class="kindi-sechead kindi-sechead--center">
			<div class="kindi-sechead__text">
				<span class="kindi-eyebrow"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'היתרונות שלנו', 'kindi' ); ?></span>
				<h2 class="kindi-sec-title"><?php esc_html_e( 'למה לבחור בנו?', 'kindi' ); ?></h2>
			</div>
		</div>
		<div class="kindi-usp kindi-usp--card">
			<?php foreach ( $items as $u ) : ?>
			<div class="kindi-usp__item">
				<span class="kindi-usp__ic"><?php echo kindi_icon( $u['icon'], 'kindi-icon--xl' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span>
					<span class="kindi-usp__title"><?php echo esc_html( $u['title'] ); ?></span><br>
					<span class="kindi-usp__sub"><?php echo esc_html( $u['sub'] ); ?></span>
				</span>
			</div>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
	return (string) ob_get_clean();
}
