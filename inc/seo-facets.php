<?php
/**
 * Faceted-navigation SEO — stop filter URLs from burning the crawl budget.
 *
 * noindex/canonical prevent INDEXING but not CRAWLING: Google still fetches
 * every filter combination to read the tag. With several multi-value facets the
 * URL space is effectively unlimited, so crawling is stopped at the source:
 *
 *   1. Filter links carry rel="nofollow" — the combinations are not discovered
 *      by crawling the site in the first place.
 *   2. robots.txt disallows the filter parameters — the crawler never fetches
 *      them (only applies to WordPress's virtual robots.txt; a physical file or
 *      an SEO plugin's own robots.txt must be edited there).
 *   3. Duplicate/re-ordered values are normalised and 301'd to one canonical
 *      form, so the same result set stops producing endless unique URLs.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Non-attribute filter parameters the theme/WooCommerce use.
 *
 * @return string[]
 */
function kindi_facet_extra_params(): array {
	return array( 'min_price', 'max_price', 'kindi_age', 'kindi_budget', 'orderby', 'product-page' );
}

/**
 * Disallow filter URLs in robots.txt so they are never crawled.
 *
 * @param string $output Robots.txt body.
 * @param string $public Whether the site is public.
 * @return string
 */
function kindi_facets_robots_txt( string $output, $public ): string {
	if ( '1' !== (string) $public ) {
		return $output;
	}

	$rules  = "\n# Kindi — faceted navigation: block crawling of filter combinations.\n";
	$rules .= "Disallow: /*?filter_\n";
	$rules .= "Disallow: /*&filter_\n";
	foreach ( kindi_facet_extra_params() as $param ) {
		$rules .= 'Disallow: /*?' . $param . "=\n";
		$rules .= 'Disallow: /*&' . $param . "=\n";
	}

	return $output . $rules;
}
add_filter( 'robots_txt', 'kindi_facets_robots_txt', 10, 2 );

/**
 * Canonical form of a filter value list: trimmed, de-duplicated, sorted.
 *
 * @param string $raw Comma-separated values.
 * @return string
 */
function kindi_facet_normalise_value( string $raw ): string {
	$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ), 'strlen' );
	$parts = array_values( array_unique( $parts ) );
	sort( $parts, SORT_STRING );
	return implode( ',', $parts );
}

/**
 * Redirect messy filter URLs to their canonical form (301).
 *
 * Fixes the observed multiplier where one value repeats inside a parameter
 * (?filter_brand=x,x,x,x) and where the same selection appears in different
 * orders — each variant being a unique URL serving identical content.
 *
 * @return void
 */
function kindi_facets_canonical_redirect(): void {
	if ( is_admin() || wp_doing_ajax() || is_robots() || empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	$changed = false;
	$args    = array();

	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = (string) $key;
		if ( 0 !== strpos( $key, 'filter_' ) || ! is_string( $value ) ) {
			continue;
		}
		$raw   = sanitize_text_field( wp_unslash( $value ) );
		$clean = kindi_facet_normalise_value( $raw );
		if ( $clean !== $raw ) {
			$changed = true;
		}
		if ( '' === $clean ) {
			$args[ $key ] = null; // Drop empty filters entirely.
			$changed      = true;
		} else {
			$args[ $key ] = $clean;
		}
	}

	if ( ! $changed ) {
		return;
	}

	$target = add_query_arg( array_filter( $args, static function ( $v ) { return null !== $v; } ) );
	foreach ( array_keys( array_filter( $args, static function ( $v ) { return null === $v; } ) ) as $drop ) {
		$target = remove_query_arg( $drop, $target );
	}

	wp_safe_redirect( $target, 301 );
	exit;
}
add_action( 'template_redirect', 'kindi_facets_canonical_redirect', 5 );
