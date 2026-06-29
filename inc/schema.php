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

	// WebSite + sitelinks search box.
	$blocks[] = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'WebSite',
		'url'             => home_url( '/' ),
		'name'            => get_bloginfo( 'name' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => home_url( '/?s={search_term_string}&post_type=product' ),
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	// LocalBusiness (ToyStore) with real Google reviews — front page only, so the
	// store's rating can earn review stars in search results.
	if ( is_front_page() && function_exists( 'kindi_google_reviews' ) ) {
		$gr = kindi_google_reviews();
		if ( ! empty( $gr['reviews'] ) ) {
			$rating = round( (float) ( $gr['rating'] ?? 0 ), 1 );
			$count  = (int) ( $gr['total'] ?? 0 );
			$count  = $count > 0 ? $count : count( $gr['reviews'] );

			$store = array(
				'@context'  => 'https://schema.org',
				'@type'     => 'ToyStore',
				'name'      => get_bloginfo( 'name' ),
				'url'       => home_url( '/' ),
				'image'     => function_exists( 'kindi_img' ) ? kindi_img( 'logo.png' ) : '',
				'telephone' => function_exists( 'kindi_opt' ) ? kindi_opt( 'store_phone' ) : '',
				'address'   => array(
					'@type'          => 'PostalAddress',
					'streetAddress'  => function_exists( 'kindi_opt' ) ? kindi_opt( 'store_address' ) : '',
					'addressCountry' => 'IL',
				),
			);

			if ( $rating >= 1 ) {
				$store['aggregateRating'] = array(
					'@type'       => 'AggregateRating',
					'ratingValue' => $rating,
					'reviewCount' => $count,
					'bestRating'  => 5,
					'worstRating' => 1,
				);
			}

			$items = array();
			foreach ( array_slice( $gr['reviews'], 0, 5 ) as $r ) {
				if ( empty( $r['text'] ) ) {
					continue;
				}
				$items[] = array(
					'@type'        => 'Review',
					'author'       => array( '@type' => 'Person', 'name' => (string) ( $r['name'] ?? '' ) ),
					'reviewRating' => array(
						'@type'       => 'Rating',
						'ratingValue' => (int) ( $r['rating'] ?? 5 ),
						'bestRating'  => 5,
					),
					'reviewBody'   => (string) $r['text'],
				);
			}
			if ( $items ) {
				$store['review'] = $items;
			}

			$blocks[] = $store;
		}
	}

	// Product (single product).
	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$offer = array(
				'@type'         => 'Offer',
				'price'         => $product->get_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'itemCondition' => 'https://schema.org/NewCondition',
				'url'           => get_permalink( $product->get_id() ),
			);

			$sale_end = $product->get_date_on_sale_to();
			if ( $sale_end ) {
				$offer['priceValidUntil'] = $sale_end->date( 'Y-m-d' );
			}

			$brand = function_exists( 'kindi_product_brand' ) ? kindi_product_brand( $product ) : '';

			$product_block = array(
				'@context'    => 'https://schema.org',
				'@type'       => 'Product',
				'name'        => $product->get_name(),
				'image'       => wp_get_attachment_url( $product->get_image_id() ) ?: '',
				'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
				'sku'         => $product->get_sku(),
				'offers'      => $offer,
			);

			if ( $brand ) {
				$product_block['brand'] = array( '@type' => 'Brand', 'name' => $brand );
			}

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

	// BreadcrumbList (product / product category).
	if ( function_exists( 'is_product' ) && ( is_product() || is_product_category() ) ) {
		$crumbs = array(
			array( 'name' => get_bloginfo( 'name' ), 'url' => home_url( '/' ) ),
		);
		$terms = get_the_terms( get_queried_object_id(), 'product_cat' );
		if ( is_product() && $terms && ! is_wp_error( $terms ) ) {
			$term     = $terms[0];
			$crumbs[] = array( 'name' => $term->name, 'url' => (string) get_term_link( $term ) );
			$crumbs[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
		} elseif ( is_product_category() ) {
			$obj      = get_queried_object();
			$crumbs[] = array( 'name' => $obj->name, 'url' => (string) get_term_link( $obj ) );
		}

		$items = array();
		foreach ( $crumbs as $i => $crumb ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $i + 1,
				'name'     => $crumb['name'],
				'item'     => $crumb['url'],
			);
		}
		$blocks[] = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		);
	}

	foreach ( $blocks as $block ) {
		// Keep slashes ESCAPED (no JSON_UNESCAPED_SLASHES): wp_json_encode then
		// renders "/" as "\/", so a literal </script> inside any value (e.g. a
		// Google review) cannot break out of the JSON-LD script element.
		echo '<script type="application/ld+json">' . wp_json_encode( $block, JSON_UNESCAPED_UNICODE ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-encoded, slashes escaped.
	}
}
add_action( 'wp_head', 'kindi_output_schema', 20 );
