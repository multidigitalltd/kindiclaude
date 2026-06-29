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

			// Shipping & return policy — Google Merchant Listing recommended fields.
			$shipping = kindi_offer_shipping_details();
			if ( $shipping ) {
				$offer['shippingDetails'] = $shipping;
			}
			$returns = kindi_offer_return_policy();
			if ( $returns ) {
				$offer['hasMerchantReturnPolicy'] = $returns;
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

/**
 * OfferShippingDetails for the Product schema, built from theme options.
 * Declares a flat domestic rate (free over the configured threshold) and a
 * handling + transit delivery time. Returns an empty array if shipping data
 * isn't configured.
 *
 * @return array<string,mixed>
 */
function kindi_offer_shipping_details(): array {
	if ( ! function_exists( 'kindi_opt' ) ) {
		return array();
	}
	$currency  = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'ILS';
	$cost      = max( 0, (int) kindi_opt( 'ship_cost', 29 ) );
	$threshold = max( 0, (int) kindi_opt( 'free_shipping', 299 ) );
	$min_days  = max( 0, (int) kindi_opt( 'ship_days_min', 1 ) );
	$max_days  = max( $min_days, (int) kindi_opt( 'ship_days_max', 4 ) );

	$details = array(
		'@type'               => 'OfferShippingDetails',
		'shippingRate'        => array(
			'@type'    => 'MonetaryAmount',
			'value'    => (string) $cost,
			'currency' => $currency,
		),
		'shippingDestination' => array(
			'@type'         => 'DefinedRegion',
			'addressCountry' => 'IL',
		),
		'deliveryTime'        => array(
			'@type'        => 'ShippingDeliveryTime',
			'handlingTime' => array(
				'@type'    => 'QuantitativeValue',
				'minValue' => 0,
				'maxValue' => 1,
				'unitCode' => 'DAY',
			),
			'transitTime'  => array(
				'@type'    => 'QuantitativeValue',
				'minValue' => $min_days,
				'maxValue' => $max_days,
				'unitCode' => 'DAY',
			),
		),
	);

	// Free-shipping threshold expressed as an eligible-transaction volume.
	if ( $cost > 0 && $threshold > 0 ) {
		$details['shippingRate']['freeShippingThreshold'] = array(
			'@type'                  => 'DeliveryChargeSpecification',
			'appliesToDeliveryMethod' => 'https://purl.org/goodrelations/v1#DeliveryModeMail',
			'eligibleTransactionVolume' => array(
				'@type'         => 'PriceSpecification',
				'minPrice'      => $threshold,
				'priceCurrency' => $currency,
			),
		);
	}

	return $details;
}

/**
 * MerchantReturnPolicy for the Product schema, built from theme options.
 * Returns an empty array if the return window is set to 0 (no returns).
 *
 * @return array<string,mixed>
 */
function kindi_offer_return_policy(): array {
	if ( ! function_exists( 'kindi_opt' ) ) {
		return array();
	}
	$days = max( 0, (int) kindi_opt( 'return_days', 14 ) );
	if ( 0 === $days ) {
		return array(
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'IL',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnNotPermitted',
		);
	}

	return array(
		'@type'                 => 'MerchantReturnPolicy',
		'applicableCountry'     => 'IL',
		'returnPolicyCategory'  => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'    => $days,
		'returnMethod'          => 'https://schema.org/ReturnByMail',
		'returnFees'            => 'https://schema.org/FreeReturn',
	);
}
