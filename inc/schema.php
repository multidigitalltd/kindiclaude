<?php
/**
 * Structured data (JSON-LD) — Organization, Product, BreadcrumbList.
 * Skipped entirely when a dedicated SEO plugin is active (it owns schema).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is a schema-managing SEO plugin active?
 *
 * @return bool
 */
function kindi_has_seo_plugin(): bool {
	return defined( 'WPSEO_VERSION' )
		|| class_exists( 'RankMath' )
		|| defined( 'SEOPRESS_VERSION' )
		|| function_exists( 'the_seo_framework' );
}

/**
 * Output JSON-LD blocks in the head.
 *
 * @return void
 */
function kindi_output_schema(): void {
	if ( kindi_has_seo_plugin() ) {
		return;
	}

	$blocks = array();

	// Organization (every page).
	$blocks[] = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'Organization',
		'name'      => get_bloginfo( 'name' ),
		'url'       => home_url( '/' ),
		'logo'      => function_exists( 'kindi_img' ) ? kindi_img( 'logo.png' ) : '',
		'telephone' => function_exists( 'kindi_opt' ) ? kindi_opt( 'phone' ) : '',
		'address'   => array(
			'@type'         => 'PostalAddress',
			'streetAddress' => function_exists( 'kindi_opt' ) ? kindi_opt( 'store_address' ) : '',
			'addressCountry' => 'IL',
		),
	);

	// Product (single product).
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$offer = array(
				'@type'         => 'Offer',
				'price'         => $product->get_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'url'           => get_permalink( $product->get_id() ),
			);

			$product_block = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'Product',
				'name'        => $product->get_name(),
				'image'       => wp_get_attachment_url( $product->get_image_id() ) ?: '',
				'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
				'sku'         => $product->get_sku(),
				'offers'      => $offer,
			);

			if ( $product->get_review_count() > 0 ) {
				$product_block['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => $product->get_average_rating(),
					'reviewCount' => $product->get_review_count(),
				);
			}

			$blocks[] = $product_block;
		}
	}

	foreach ( $blocks as $block ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'kindi_output_schema', 20 );
