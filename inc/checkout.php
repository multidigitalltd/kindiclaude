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
	echo '<div class="kindi-checkout-club">'
		. '<span class="kindi-checkout-club__ic">' . kindi_icon( 'gift', 'kindi-icon--lg kindi-icon--white' ) . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput
		. '<div><strong>כבר חברי מועדון קינדי?</strong><span> התחברו לקבלת הטבות, נקודות וצבירה.</span></div>'
		. '<a class="kindi-checkout-club__btn" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">התחברות / הרשמה</a>'
		. '</div>';
}
add_action( 'woocommerce_before_checkout_form', 'kindi_checkout_club_banner', 4 );

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
