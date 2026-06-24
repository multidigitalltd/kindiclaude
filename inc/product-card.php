<?php
/**
 * Product card — rebuild the WooCommerce loop item to match the Lovable
 * ProductCard (badges, wishlist heart, brand, rating, price, round add button).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Swap the default loop hooks for the Kindi card layout.
 *
 * @return void
 */
function kindi_product_card_hooks(): void {
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

	add_action( 'woocommerce_before_shop_loop_item_title', 'kindi_card_media', 10 );
	add_action( 'woocommerce_shop_loop_item_title', 'kindi_card_head', 10 );
	add_action( 'woocommerce_after_shop_loop_item_title', 'kindi_card_rating', 10 );
	add_action( 'woocommerce_after_shop_loop_item', 'kindi_card_foot', 10 );
}
add_action( 'init', 'kindi_product_card_hooks' );

/**
 * Is the product newly published (last 30 days)?
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function kindi_is_new_product( $product ): bool {
	$created = $product->get_date_created();
	if ( ! $created ) {
		return false;
	}
	$days = (int) apply_filters( 'kindi_new_product_days', 30 );

	return ( time() - $created->getTimestamp() ) < ( $days * DAY_IN_SECONDS );
}

/**
 * Best-effort brand label (product brand taxonomy, brand attribute, else category).
 *
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_product_brand( $product ): string {
	foreach ( array( 'product_brand', 'pa_brand', 'pwb-brand' ) as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			$terms = wp_get_post_terms( $product->get_id(), $tax, array( 'fields' => 'names' ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				return (string) $terms[0];
			}
		}
	}
	$cats = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );

	return ( ! is_wp_error( $cats ) && $cats ) ? (string) $cats[0] : '';
}

/**
 * Card media: badges, wishlist heart, linked thumbnail.
 *
 * @return void
 */
function kindi_card_media(): void {
	global $product;

	$pid = $product->get_id();
	echo '<div class="kindi-pc__media">';

	echo '<div class="kindi-pc__badges">';
	if ( $product->is_featured() ) {
		echo '<span class="kindi-pc__badge kindi-pc__badge--best">הכי נמכר</span>';
	} elseif ( $product->is_on_sale() ) {
		echo '<span class="kindi-pc__badge kindi-pc__badge--sale">מבצע</span>';
	}
	if ( kindi_is_new_product( $product ) ) {
		echo '<span class="kindi-pc__badge kindi-pc__badge--new">חדש</span>';
	}
	echo '</div>';

	printf(
		'<button type="button" class="kindi-pc__wish" data-kindi-wish="%d" aria-label="הוספה למועדפים">%s</button>',
		(int) $pid,
		kindi_icon( 'heart', 'kindi-icon--sm' ) // phpcs:ignore WordPress.Security.EscapeOutput
	);

	printf(
		'<a class="kindi-pc__imglink" href="%s">%s</a>',
		esc_url( get_permalink( $pid ) ),
		$product->get_image( 'woocommerce_thumbnail' ) // phpcs:ignore WordPress.Security.EscapeOutput -- WC-escaped <img>.
	);

	echo '</div>';
}

/**
 * Card head: brand + rating + linked title.
 *
 * @return void
 */
function kindi_card_head(): void {
	global $product;

	$brand = kindi_product_brand( $product );
	if ( $brand ) {
		echo '<span class="kindi-pc__brand">' . esc_html( $brand ) . '</span>';
	}

	printf(
		'<a class="kindi-pc__title" href="%s">%s</a>',
		esc_url( get_permalink( $product->get_id() ) ),
		esc_html( $product->get_name() )
	);
}

/**
 * Card rating row: stars + review count.
 *
 * @return void
 */
function kindi_card_rating(): void {
	global $product;

	$count = (int) $product->get_rating_count();
	if ( $count < 1 && (float) $product->get_average_rating() <= 0 ) {
		return;
	}

	echo '<div class="kindi-pc__rating">';
	echo wc_get_rating_html( (float) $product->get_average_rating(), $count ); // phpcs:ignore WordPress.Security.EscapeOutput -- WC markup.
	echo '<span class="kindi-pc__rcount">(' . esc_html( (string) $count ) . ')</span>';
	echo '</div>';
}

/**
 * Card footer: price + round add-to-cart button.
 *
 * @return void
 */
function kindi_card_foot(): void {
	global $product;

	echo '<div class="kindi-pc__foot">';
	echo '<span class="kindi-pc__price">' . $product->get_price_html() . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput -- WC price HTML.
	woocommerce_template_loop_add_to_cart( array( 'class' => 'button kindi-pc__add' ) );
	echo '</div>';
}
