<?php
/**
 * WhatsApp customer service.
 *
 * A configurable click-to-chat link (number, hours, default + product message)
 * plus a product-page "ask about this toy" button that pre-fills the product
 * name and URL so support gets full context in one tap.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Build a wa.me click-to-chat URL with optional pre-filled text.
 *
 * @param string $text Optional message body.
 * @return string Empty string when no number is configured.
 */
function kindi_whatsapp_url( string $text = '' ): string {
	$number = preg_replace( '/\D+/', '', (string) kindi_opt( 'whatsapp' ) );
	if ( '' === $number ) {
		return '';
	}

	$url = 'https://wa.me/' . $number;
	if ( '' !== $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}

	return $url;
}

/**
 * Is support inside the configured opening hours? (Best-effort, display only.)
 * Returns null when no hours window is configured.
 *
 * @return bool|null
 */
function kindi_whatsapp_is_open(): ?bool {
	$from = (int) kindi_opt( 'whatsapp_from', 9 );
	$to   = (int) kindi_opt( 'whatsapp_to', 21 );
	if ( $from === $to ) {
		return null;
	}

	$hour = (int) wp_date( 'G' );

	return $from < $to ? ( $hour >= $from && $hour < $to ) : ( $hour >= $from || $hour < $to );
}

/**
 * Product-page WhatsApp enquiry button (context pre-filled).
 *
 * @return void
 */
function kindi_whatsapp_product_button(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	// WhatsApp's link detection stops at RTL characters, so a Hebrew product
	// slug arrives as a broken half-link once the pre-filled text is decoded.
	// Any non-ASCII permalink is swapped for the ?p= shortlink, which always
	// linkifies whole and redirects to the product page.
	$link = get_permalink( $product->get_id() );
	if ( preg_match( '/[^\x21-\x7E]/', rawurldecode( $link ) ) ) {
		$link = wp_get_shortlink( $product->get_id() ) ?: $link;
	}

	$template = (string) kindi_opt( 'whatsapp_product_msg' );
	$message  = str_replace(
		array( '{product}', '{url}' ),
		array( $product->get_name(), $link ),
		$template
	);

	$url = kindi_whatsapp_url( $message );
	if ( '' === $url ) {
		return;
	}

	$open  = kindi_whatsapp_is_open();
	$hours = (string) kindi_opt( 'whatsapp_hours' );

	echo '<a class="kindi-wa-ask" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">';
	echo kindi_icon( 'whatsapp', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<span class="kindi-wa-ask__txt"><strong>' . esc_html__( 'יש שאלה על המוצר?', 'kindi' ) . '</strong>';
	echo '<span>' . esc_html__( 'דברו איתנו בוואטסאפ', 'kindi' );
	if ( null !== $open ) {
		echo ' <span class="kindi-wa-ask__dot kindi-wa-ask__dot--' . ( $open ? 'on' : 'off' ) . '">' . ( $open ? esc_html__( 'זמינים עכשיו', 'kindi' ) : esc_html__( 'נחזור אליכם', 'kindi' ) ) . '</span>';
	}
	echo '</span></span>';
	if ( '' !== $hours ) {
		echo '<span class="kindi-wa-ask__hours">' . esc_html( $hours ) . '</span>';
	}
	echo '</a>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_whatsapp_product_button', 35 );
