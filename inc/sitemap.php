<?php
/**
 * Product image sitemap — XML sitemap of products with their images, served at
 * /?kindi_sitemap=products and advertised in robots.txt. Complements the core
 * wp-sitemap.xml (which has no image support). Cached 12h.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The product sitemap URL.
 *
 * @return string
 */
function kindi_product_sitemap_url(): string {
	return add_query_arg( 'kindi_sitemap', 'products', home_url( '/' ) );
}

/**
 * Output the sitemap when requested.
 *
 * @return void
 */
function kindi_maybe_render_sitemap(): void {
	if ( empty( $_GET['kindi_sitemap'] ) || 'products' !== $_GET['kindi_sitemap'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		status_header( 404 );
		exit;
	}

	$xml = get_transient( 'kindi_product_sitemap' );
	if ( false === $xml ) {
		$xml = kindi_build_product_sitemap();
		set_transient( 'kindi_product_sitemap', $xml, 12 * HOUR_IN_SECONDS );
	}

	header( 'Content-Type: application/xml; charset=utf-8' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput -- Pre-escaped XML.
	exit;
}
add_action( 'template_redirect', 'kindi_maybe_render_sitemap' );

/**
 * Build the product sitemap XML (URLs + images).
 *
 * @return string
 */
function kindi_build_product_sitemap(): string {
	$ids = wc_get_products(
		array(
			'status'     => 'publish',
			'visibility' => 'visible',
			'limit'      => 2000,
			'return'     => 'ids',
		)
	);

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

	foreach ( $ids as $id ) {
		$xml .= '<url>';
		$xml .= '<loc>' . esc_url( get_permalink( $id ) ) . '</loc>';
		$xml .= '<lastmod>' . esc_html( get_post_modified_time( 'c', true, $id ) ) . '</lastmod>';

		$product   = wc_get_product( $id );
		$image_ids = array_merge( array( get_post_thumbnail_id( $id ) ), $product ? $product->get_gallery_image_ids() : array() );
		$seen      = array();
		foreach ( array_filter( $image_ids ) as $img_id ) {
			if ( isset( $seen[ $img_id ] ) ) {
				continue;
			}
			$seen[ $img_id ] = true;
			$src             = wp_get_attachment_url( $img_id );
			if ( $src ) {
				$xml .= '<image:image><image:loc>' . esc_url( $src ) . '</image:loc></image:image>';
			}
		}

		$xml .= '</url>';
	}

	$xml .= '</urlset>';

	return $xml;
}

/**
 * Advertise the sitemap in robots.txt.
 *
 * @param string $output robots.txt content.
 * @return string
 */
function kindi_robots_sitemap( string $output ): string {
	if ( class_exists( 'WooCommerce' ) ) {
		$output .= "\nSitemap: " . esc_url_raw( kindi_product_sitemap_url() ) . "\n";
	}
	return $output;
}
add_filter( 'robots_txt', 'kindi_robots_sitemap', 10, 1 );

/**
 * Refresh the sitemap cache on product changes.
 *
 * @return void
 */
function kindi_flush_sitemap_cache(): void {
	delete_transient( 'kindi_product_sitemap' );
}
add_action( 'save_post_product', 'kindi_flush_sitemap_cache' );
