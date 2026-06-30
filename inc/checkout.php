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
 * Club / benefits banner above the checkout form.
 *
 * @return void
 */
function kindi_checkout_club_banner(): void {
	if ( is_user_logged_in() ) {
		return;
	}
	$chevron = '<svg class="kindi-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
	echo '<div class="kindi-club" data-kindi-club>'
		. '<span class="kindi-club__ic">' . kindi_icon( 'crown', 'kindi-icon--lg kindi-icon--white' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<div class="kindi-club__text">'
		. '<strong>' . esc_html__( 'כבר חברי מועדון קינדי טויס? התחברו וקבלו הנחות ונקודות', 'kindi' ) . '</strong>'
		. '<span>' . esc_html__( 'חברי מועדון צוברים נקודות על כל קנייה, מקבלים מתנות יום הולדת והטבות בלעדיות', 'kindi' ) . '</span>'
		. '</div>'
		. '<a class="kindi-club__btn" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">' . esc_html__( 'התחברות / הצטרפות', 'kindi' ) . '</a>'
		. '<button type="button" class="kindi-club__toggle" data-kindi-club-toggle aria-label="' . esc_attr__( 'כיווץ', 'kindi' ) . '">' . $chevron . '</button>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '</div>';
}
add_action( 'woocommerce_before_checkout_form', 'kindi_checkout_club_banner', 4 );

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
	$b = &$fields['billing'];

	$set = static function ( array &$b, string $key, string $label, string $placeholder, int $priority, string $row ): void {
		if ( ! isset( $b[ $key ] ) ) {
			return;
		}
		$b[ $key ]['label']       = $label;
		$b[ $key ]['placeholder'] = $placeholder;
		$b[ $key ]['priority']    = $priority;
		$b[ $key ]['class']       = array( $row );
	};

	$set( $b, 'billing_first_name', 'שם פרטי', 'ישראלה', 10, 'form-row-first' );
	$set( $b, 'billing_last_name', 'שם משפחה', 'ישראלי', 20, 'form-row-last' );
	$set( $b, 'billing_phone', 'טלפון נייד', '050-1234567', 30, 'form-row-first' );
	$set( $b, 'billing_email', 'אימייל', 'name@example.com', 40, 'form-row-last' );
	$set( $b, 'billing_address_1', 'רחוב ומספר', 'הרצל 12', 50, 'form-row-first' );
	$set( $b, 'billing_address_2', 'דירה / כניסה', 'דירה 4, קומה 2', 60, 'form-row-last' );
	$set( $b, 'billing_city', 'עיר', 'תל אביב', 70, 'form-row-first' );
	$set( $b, 'billing_postcode', 'מיקוד', '6100000', 80, 'form-row-last' );

	unset( $b['billing_company'] );

	if ( isset( $b['billing_email'] ) ) {
		$b['billing_email']['type'] = 'email';
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'kindi_checkout_field_layout' );

/**
 * Persist the marketing opt-in checkbox (from the contact card) onto the order.
 * The surrounding checkout flow has already verified the checkout nonce.
 *
 * @param WC_Order $order Order being created.
 * @return void
 */
function kindi_save_marketing_optin( WC_Order $order ): void {
	$optin = isset( $_POST['kindi_marketing_optin'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['kindi_marketing_optin'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$order->update_meta_data( '_kindi_marketing_optin', $optin ? 'yes' : 'no' );
}
add_action( 'woocommerce_checkout_create_order', 'kindi_save_marketing_optin' );

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
							?>
							<li class="kindi-ship__item">
								<input type="radio" name="shipping_method[<?php echo esc_attr( (string) $i ); ?>]" data-index="<?php echo esc_attr( (string) $i ); ?>" id="<?php echo esc_attr( $rid ); ?>" value="<?php echo esc_attr( $method->get_id() ); ?>" class="shipping_method" <?php checked( $method->get_id(), $picked ); ?> />
								<label for="<?php echo esc_attr( $rid ); ?>">
									<span class="kindi-ship__ic"><?php echo kindi_icon( 'truck', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
									<span class="kindi-ship__main">
										<span class="kindi-ship__title"><?php echo esc_html( wp_strip_all_tags( $method->get_label() ) ); ?></span>
									</span>
									<span class="kindi-ship__price">
										<?php
										if ( $cost > 0 ) {
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
}
add_action( 'wp', 'kindi_checkout_relocate_payment' );

/**
 * Open the two-column wrapper + the main (process) column.
 *
 * @return void
 */
function kindi_checkout_cols_open(): void {
	echo '<div class="kindi-co"><div class="kindi-co__main">';
}
add_action( 'woocommerce_checkout_before_customer_details', 'kindi_checkout_cols_open', 5 );

/**
 * Close the main column and open the summary (side) column. Runs after the
 * relocated payment block (priority 20) on the same hook.
 *
 * @return void
 */
function kindi_checkout_cols_mid(): void {
	echo '</div><div class="kindi-co__side">';
}
add_action( 'woocommerce_checkout_after_customer_details', 'kindi_checkout_cols_mid', 30 );

/**
 * Close the summary column and the two-column wrapper.
 *
 * @return void
 */
function kindi_checkout_cols_close(): void {
	echo '</div></div>';
}
add_action( 'woocommerce_checkout_after_order_review', 'kindi_checkout_cols_close', 50 );

/**
 * Prepend a truck icon to each shipping-method label (cart & checkout) so the
 * methods can render as cards with an icon + title + price.
 *
 * @param string $label  Method label HTML (title + cost).
 * @param mixed  $method Shipping rate (unused).
 * @return string
 */
function kindi_shipping_method_icon( string $label, $method = null ): string {
	return '<span class="kindi-ship__ic">' . kindi_icon( 'truck', 'kindi-icon--md' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<span class="kindi-ship__txt">' . $label . '</span>';
}
add_filter( 'woocommerce_cart_shipping_method_full_label', 'kindi_shipping_method_icon', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Gift card & Gifta integrations.
 * ---------------------------------------------------------------------------
 */

/**
 * Render the Simply gift-card redemption box on the same hook as the coupon
 * toggle (woocommerce_before_checkout_form) so the two sit together at the top
 * of the checkout instead of the box dropping into the billing column. The
 * filter only does anything when the gift-card plugin is active.
 */
add_filter( 'simply_offerbox_checkout_action', static fn(): string => 'woocommerce_before_checkout_form' );

// Hide the Simply gift-card plugin's own top bar (no-op when the plugin is absent).
add_filter( 'simply_show_top_bar', '__return_false' );

/**
 * Notice before the payment methods: Gifta gift-cards can't be combined with
 * coupons. Escaped + translatable; keeps the original `custom-payment-text`
 * class so existing styling still applies.
 *
 * @return void
 */
function kindi_gifta_coupon_notice(): void {
	echo '<p class="kindi-gifta-note custom-payment-text">'
		. esc_html__( 'בתשלום עם כרטיס Gifta לא ניתן להשתמש בקופונים. רוצים להשתמש בקופון? פשוט בחרו אמצעי תשלום אחר.', 'kindi' )
		. '</p>';
}
add_action( 'woocommerce_review_order_before_payment', 'kindi_gifta_coupon_notice' );

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
		return $content . kindi_reviews_band() . kindi_why_choose_band();
	}
	return $content;
}
add_filter( 'the_content', 'kindi_cart_checkout_trust', 20 );

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
