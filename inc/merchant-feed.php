<?php
/**
 * Google Merchant Center product feed — RSS 2.0 with the g: namespace, served
 * at /?kindi_feed=google. Cached 6h, refreshed on product changes. Enables
 * Google Shopping / free product listings.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The feed URL.
 *
 * @return string
 */
function kindi_feed_url(): string {
	return add_query_arg( 'kindi_feed', 'google', home_url( '/' ) );
}

/**
 * Output the feed when requested.
 *
 * @return void
 */
function kindi_maybe_render_feed(): void {
	if ( empty( $_GET['kindi_feed'] ) || 'google' !== $_GET['kindi_feed'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		status_header( 404 );
		exit;
	}

	$xml = get_transient( 'kindi_google_feed' );
	if ( false === $xml ) {
		$xml = kindi_build_google_feed();
		set_transient( 'kindi_google_feed', $xml, 6 * HOUR_IN_SECONDS );
	}

	header( 'Content-Type: application/xml; charset=utf-8' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput -- Pre-escaped XML.
	exit;
}
add_action( 'template_redirect', 'kindi_maybe_render_feed' );

/**
 * Build the product feed XML.
 *
 * @return string
 */
function kindi_build_google_feed(): string {
	$products = wc_get_products(
		array(
			'status'     => 'publish',
			'limit'      => 1000,
			'visibility' => 'visible',
		)
	);

	$currency = get_woocommerce_currency();

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
	$xml .= '<title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>';
	$xml .= '<link>' . esc_url( home_url( '/' ) ) . '</link>';
	$xml .= '<description>' . esc_html( get_bloginfo( 'description' ) ) . '</description>';

	foreach ( $products as $product ) {
		if ( ! $product->is_visible() || '' === $product->get_price() ) {
			continue;
		}

		$image = wp_get_attachment_url( $product->get_image_id() );
		$brand = function_exists( 'kindi_product_brand' ) ? kindi_product_brand( $product ) : '';
		$cats  = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' &gt; ' ) );

		$xml .= '<item>';
		$xml .= '<g:id>' . (int) $product->get_id() . '</g:id>';
		$xml .= '<g:title>' . esc_html( $product->get_name() ) . '</g:title>';
		$xml .= '<g:description>' . esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ), 60 ) ) . '</g:description>';
		$xml .= '<g:link>' . esc_url( get_permalink( $product->get_id() ) ) . '</g:link>';
		if ( $image ) {
			$xml .= '<g:image_link>' . esc_url( $image ) . '</g:image_link>';
		}
		$xml .= '<g:availability>' . ( $product->is_in_stock() ? 'in_stock' : 'out_of_stock' ) . '</g:availability>';
		$xml .= '<g:price>' . esc_html( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) . ' ' . $currency ) . '</g:price>';
		if ( $product->is_on_sale() && '' !== $product->get_sale_price() ) {
			$xml .= '<g:sale_price>' . esc_html( wc_get_price_to_display( $product ) . ' ' . $currency ) . '</g:sale_price>';
		}
		$xml .= '<g:brand>' . esc_html( $brand ? $brand : get_bloginfo( 'name' ) ) . '</g:brand>';
		$xml .= '<g:condition>new</g:condition>';
		$xml .= '<g:identifier_exists>' . ( $product->get_sku() ? 'yes' : 'no' ) . '</g:identifier_exists>';
		if ( $product->get_sku() ) {
			$xml .= '<g:mpn>' . esc_html( $product->get_sku() ) . '</g:mpn>';
		}
		if ( $cats ) {
			$xml .= '<g:product_type>' . esc_html( $cats ) . '</g:product_type>';
		}
		$xml .= '</item>';
	}

	$xml .= '</channel></rss>';

	return $xml;
}

/**
 * Refresh the feed cache when a product changes.
 *
 * @return void
 */
function kindi_flush_feed_cache(): void {
	delete_transient( 'kindi_google_feed' );
}
add_action( 'save_post_product', 'kindi_flush_feed_cache' );
add_action( 'woocommerce_update_product', 'kindi_flush_feed_cache' );
