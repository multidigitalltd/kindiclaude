<?php
/**
 * Gift card product page — YITH WooCommerce Gift Cards.
 *
 * The plugin's own form is left to do its job (designs, amounts, greeting,
 * scheduling, preview, add-to-cart); this file only dresses it in the store's
 * design, finishes the Hebrew, and fixes the two accessibility gaps the plugin
 * leaves — the amount buttons never announce which one is chosen, and the
 * design tiles are plain <div>s that the keyboard cannot reach.
 *
 * Nothing here edits the plugin or overrides its templates, so an update to it
 * cannot collide with this. The stylesheet and script load on the gift card
 * product page alone.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Whether the current view is a single YITH gift card product.
 *
 * @return bool
 */
function kindi_is_gift_card_page(): bool {
	static $is = null;
	if ( null !== $is ) {
		return $is;
	}
	$is = false;
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		$is      = $product instanceof WC_Product && 'gift-card' === $product->get_type();
	}
	return $is;
}

/**
 * Load the page's own stylesheet and script — there and nowhere else.
 *
 * @return void
 */
function kindi_gift_card_assets(): void {
	if ( ! kindi_is_gift_card_page() ) {
		return;
	}
	wp_enqueue_style(
		'kindi-gift-card',
		KINDI_URI . 'assets/css/gift-card.css',
		array( 'kindi-woocommerce' ),
		kindi_asset_version( 'assets/css/gift-card.css' )
	);
	wp_enqueue_script(
		'kindi-gift-card',
		KINDI_URI . 'assets/js/gift-card.js',
		array(),
		kindi_asset_version( 'assets/js/gift-card.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_gift_card_assets', 20 );

/**
 * Scope the stylesheet to this page.
 *
 * @param array<int,string> $classes Body classes.
 * @return array<int,string>
 */
function kindi_gift_card_body_class( array $classes ): array {
	if ( kindi_is_gift_card_page() ) {
		$classes[] = 'kindi-gc';
	}
	return $classes;
}
add_filter( 'body_class', 'kindi_gift_card_body_class' );

/**
 * Hebrew for the strings the plugin ships untranslated.
 *
 * Done through WordPress's own translation filter rather than CSS labels or a
 * sweep over the rendered text, so the wording follows the string wherever it
 * is used — the form, the cart, the checkout and the gift card e-mail — and
 * survives a plugin update.
 *
 * @return array<string,string>
 */
function kindi_gift_card_strings(): array {
	return array(
		'Name'                                                                         => 'שם מקבל המתנה',
		'Email'                                                                        => 'כתובת המייל של מקבל המתנה',
		'Message'                                                                      => 'הברכה שלכם',
		'Your name'                                                                    => 'שם שולח המתנה',
		'Delivery on'                                                                  => 'מועד שליחת המתנה',
		'Check to receive an email when the gift card has been sent to the recipient'   => 'שלחו לי אישור במייל כשכרטיס המתנה נשלח לנמען',
		'Custom'                                                                       => 'סכום אחר',
		'Now'                                                                          => 'עכשיו',
		'Gift Card'                                                                    => 'כרטיס מתנה',
		'Gift Cards'                                                                   => 'כרטיסי מתנה',
		// The preview prints these with the colon and spacing baked into the
		// string, so each spelling is mapped rather than guessed at.
		'From'                                                                         => 'מאת',
		'From:'                                                                        => 'מאת:',
		'From: '                                                                       => 'מאת: ',
		'To'                                                                           => 'לכבוד',
		'To:'                                                                          => 'לכבוד:',
		'To: '                                                                         => 'לכבוד: ',
		'Message:'                                                                     => 'הברכה שלכם:',
		'Message: '                                                                    => 'הברכה שלכם: ',
	);
}

/**
 * Translate a plugin string, leaving every other text domain untouched.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function kindi_gift_card_gettext( $translated, $text, $domain ): string {
	if ( 'yith-woocommerce-gift-cards' !== $domain ) {
		return (string) $translated;
	}
	$map = kindi_gift_card_strings();
	return $map[ $text ] ?? (string) $translated;
}
add_filter( 'gettext', 'kindi_gift_card_gettext', 20, 3 );

/**
 * The same, for strings the plugin registers with a context.
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $context    Context.
 * @param string $domain     Text domain.
 * @return string
 */
function kindi_gift_card_gettext_ctx( $translated, $text, $context, $domain ): string {
	return kindi_gift_card_gettext( $translated, $text, $domain );
}
add_filter( 'gettext_with_context', 'kindi_gift_card_gettext_ctx', 20, 4 );

/**
 * Name the button for what it adds.
 *
 * @param string $text Button text.
 * @return string
 */
function kindi_gift_card_cart_text( $text ): string {
	global $product;
	if ( $product instanceof WC_Product && 'gift-card' === $product->get_type() ) {
		return 'הוספת כרטיס המתנה לסל';
	}
	return (string) $text;
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'kindi_gift_card_cart_text', 20 );

/**
 * Panel field: the note printed under the form.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_gift_card_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections'] ) ) {
		$tabs['texts']['sections']['כרטיס מתנה'] = array(
			'giftcard_note' => array(
				'type'  => 'textarea',
				'label' => 'הערה מתחת לטופס',
				'rows'  => 2,
				'help'  => 'מוצגת בעמוד כרטיס המתנה, מתחת לכפתור ההוספה לסל. ריק = להסתיר.',
			),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_gift_card_settings' );

/**
 * Print that note, directly below the plugin's form.
 *
 * @return void
 */
function kindi_gift_card_note(): void {
	if ( ! kindi_is_gift_card_page() ) {
		return;
	}
	$note = trim( (string) kindi_opt( 'giftcard_note' ) );
	if ( '' === $note ) {
		return;
	}
	printf( '<p class="kindi-gc__note">%s</p>', esc_html( $note ) );
}
add_action( 'woocommerce_single_product_summary', 'kindi_gift_card_note', 32 );
