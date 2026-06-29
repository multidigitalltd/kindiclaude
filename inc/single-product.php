<?php
/**
 * Single-product presentation — recreates the Lovable product-page design on top
 * of WooCommerce via summary/after-summary hooks: brand eyebrow, perks list,
 * delivery & gift card, trust strip, key-facts cards, "what's in the box" panel
 * and the skills chips. Pure presentation; data comes from product-meta.php.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Quantity stepper — a minus button before the number field and a plus button
 * after it, replacing the browser's native spinner arrows. Fires inside
 * WooCommerce's quantity-input template, so it covers both the product page and
 * the cart. Buttons are tabindex=-1 (the field itself stays keyboard-operable).
 *
 * @return void
 */
function kindi_qty_minus(): void {
	if ( kindi_qty_stepper_hidden() ) {
		return;
	}
	echo '<button type="button" class="kindi-qbtn kindi-qbtn--minus" tabindex="-1" aria-label="' . esc_attr__( 'הפחתת כמות', 'kindi' ) . '">&#8722;</button>';
}
add_action( 'woocommerce_before_quantity_input_field', 'kindi_qty_minus' );

/**
 * Plus button after the quantity field.
 *
 * @return void
 */
function kindi_qty_plus(): void {
	if ( kindi_qty_stepper_hidden() ) {
		return;
	}
	echo '<button type="button" class="kindi-qbtn kindi-qbtn--plus" tabindex="-1" aria-label="' . esc_attr__( 'הוספת כמות', 'kindi' ) . '">&#43;</button>';
}
add_action( 'woocommerce_after_quantity_input_field', 'kindi_qty_plus' );

/**
 * Whether the +/- stepper should be suppressed — when the product can't be
 * bought in more than one (sold individually, or max purchase quantity of 1).
 *
 * @return bool
 */
function kindi_qty_stepper_hidden(): bool {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	if ( $product->is_sold_individually() ) {
		return true;
	}
	return 1 === (int) $product->get_max_purchase_quantity();
}

/**
 * Brand eyebrow above the product title.
 *
 * @return void
 */
function kindi_pdp_brand_eyebrow(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! function_exists( 'kindi_product_brand' ) ) {
		return;
	}
	$brand = kindi_product_brand( $product );
	if ( '' === $brand ) {
		return;
	}
	echo '<div class="kindi-pdp__brand">' . esc_html( $brand ) . '</div>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_brand_eyebrow', 4 );

/**
 * Perks list (highlights) near the buy box.
 *
 * @return void
 */
function kindi_pdp_highlights(): void {
	global $product;
	$lines = function_exists( 'kindi_pmeta_lines' ) ? kindi_pmeta_lines( $product, 'highlights' ) : array();
	if ( ! $lines ) {
		return;
	}
	echo '<ul class="kindi-highlights">';
	foreach ( $lines as $line ) {
		echo '<li><span class="kindi-highlights__ic">' . kindi_icon( 'check', 'kindi-icon--sm' ) . '</span>' . esc_html( $line ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_highlights', 33 );

/**
 * Delivery + gift-wrap reassurance card under the add-to-cart.
 *
 * @return void
 */
function kindi_pdp_delivery_card(): void {
	$threshold = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );

	echo '<div class="kindi-pdp__delivery">';
	echo '<div class="kindi-pdp__drow"><span class="kindi-pdp__dic kindi-pdp__dic--blue">' . kindi_icon( 'truck', 'kindi-icon--md' ) . '</span><div><strong>' . esc_html( sprintf( 'משלוח חינם בהזמנה מעל ₪%d', $threshold ) ) . '</strong><span>הזמינו היום — משלוח מהיר עד הבית</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-pdp__drow"><span class="kindi-pdp__dic kindi-pdp__dic--red">' . kindi_icon( 'gift', 'kindi-icon--md' ) . '</span><div><strong>עטיפת מתנה חינם</strong><span>סמנו בעגלה — נעטוף יפה ונצרף ברכה</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_delivery_card', 34 );

/**
 * Toy key-facts cards (pieces / age / play-time / players) — only those present.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_facts( $product ): void {
	$defs = array(
		array( 'icon' => 'blocks', 'label' => 'חלקים', 'key' => 'pieces' ),
		array( 'icon' => 'baby', 'label' => 'גיל מומלץ', 'key' => 'age' ),
		array( 'icon' => 'clock', 'label' => 'זמן משחק', 'key' => 'play_time' ),
		array( 'icon' => 'gamepad', 'label' => 'משתתפים', 'key' => 'players' ),
	);

	$cards = array();
	foreach ( $defs as $def ) {
		$value = kindi_pmeta( $product, $def['key'] );
		if ( '' !== $value ) {
			$cards[] = array( 'icon' => $def['icon'], 'label' => $def['label'], 'value' => $value );
		}
	}
	if ( ! $cards ) {
		return;
	}

	echo '<div class="kindi-facts">';
	foreach ( $cards as $card ) {
		echo '<div class="kindi-fact"><span class="kindi-fact__ic">' . kindi_icon( $card['icon'], 'kindi-icon--md' ) . '</span><span class="kindi-fact__l">' . esc_html( $card['label'] ) . '</span><span class="kindi-fact__v">' . esc_html( $card['value'] ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}

/**
 * Trust strip (free shipping / returns / warranty).
 *
 * @return void
 */
function kindi_pdp_trust(): void {
	$threshold = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );
	$items     = array(
		array( 'icon' => 'truck', 'text' => sprintf( 'משלוח חינם ₪%d+', $threshold ) ),
		array( 'icon' => 'rotate', 'text' => 'החזרה תוך 14 יום' ),
		array( 'icon' => 'shield', 'text' => 'אחריות יבואן רשמי' ),
	);
	echo '<div class="kindi-trust">';
	foreach ( $items as $item ) {
		echo '<div class="kindi-trust__i">' . kindi_icon( $item['icon'], 'kindi-icon--sm' ) . '<span>' . esc_html( $item['text'] ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}

/**
 * "In the box" navy gradient card with chips.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_inbox( $product ): void {
	$items = function_exists( 'kindi_pmeta_lines' ) ? kindi_pmeta_lines( $product, 'in_box' ) : array();
	if ( ! $items ) {
		return;
	}
	echo '<div class="kindi-inboxcard"><div class="kindi-inboxcard__head"><span class="kindi-inboxcard__ic">' . kindi_icon( 'gift', 'kindi-icon--md' ) . '</span><h3>מה יש בקופסה?</h3></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-inboxcard__chips">';
	foreach ( $items as $item ) {
		echo '<span class="kindi-inboxcard__chip">' . esc_html( $item ) . '</span>';
	}
	echo '</div></div>';
}

/**
 * Render the left-column blocks (trust strip, key facts, skills, in-the-box),
 * called directly from the custom content-single-product.php under the gallery.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_left_extras( $product ): void {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	echo '<div class="kindi-pdp-extras">';
	kindi_pdp_trust();
	kindi_pdp_facts( $product );

	// Skills chips.
	$skills = function_exists( 'kindi_skill_items' ) ? kindi_skill_items( $product ) : array();
	if ( $skills ) {
		echo '<div class="kindi-skills"><h3>' . esc_html__( 'מיומנויות', 'kindi' ) . '</h3><ul class="kindi-skills__list">';
		foreach ( $skills as $skill ) {
			echo '<li class="kindi-skill">' . esc_html( $skill ) . '</li>';
		}
		echo '</ul></div>';
	}

	kindi_pdp_inbox( $product );
	echo '</div>';
}

/**
 * Reorder the buy box to match the design: brand → title → short description →
 * rating → price. (Move the excerpt from after the price up to just under the
 * title.)
 *
 * @return void
 */
function kindi_pdp_reorder_summary(): void {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 6 );
}
add_action( 'init', 'kindi_pdp_reorder_summary' );

/**
 * Sale badge as "-X% חיסכון" (percentage saved) instead of the default "Sale!".
 *
 * @param string     $html    Default markup.
 * @param WP_Post    $post    Post.
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_pdp_sale_flash( $html, $post, $product ): string {
	if ( ! $product instanceof WC_Product ) {
		return $html;
	}
	$pct = 0;
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		$best   = 0;
		foreach ( ( $prices['regular_price'] ?? array() ) as $vid => $reg ) {
			$reg  = (float) $reg;
			$sale = (float) ( $prices['sale_price'][ $vid ] ?? $reg );
			if ( $reg > 0 && $sale < $reg ) {
				$best = max( $best, (int) round( ( 1 - $sale / $reg ) * 100 ) );
			}
		}
		$pct = $best;
	} else {
		$reg  = (float) $product->get_regular_price();
		$sale = (float) $product->get_sale_price();
		if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
			$pct = (int) round( ( 1 - $sale / $reg ) * 100 );
		}
	}
	$label = $pct > 0 ? sprintf( '%d%%- חיסכון', $pct ) : 'מבצע';

	return '<span class="onsale kindi-pdp__sale">' . esc_html( $label ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'kindi_pdp_sale_flash', 10, 3 );

/**
 * "חדש בקינדי" badge in the gallery for recently-published products.
 *
 * @return void
 */
function kindi_pdp_new_badge(): void {
	global $product;
	if ( $product instanceof WC_Product && function_exists( 'kindi_is_new_product' ) && kindi_is_new_product( $product ) ) {
		echo '<span class="kindi-pdp__newbadge">' . kindi_icon( 'sparkles', 'kindi-icon--xs kindi-icon--white' ) . 'חדש בקינדי</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'woocommerce_before_single_product_summary', 'kindi_pdp_new_badge', 8 );
