<?php
/**
 * Faceted-navigation SEO — stop filter URLs from burning the crawl budget.
 *
 * Strategy (revised after ~1M filter URLs were already in Google's index):
 *
 *   1. Filter links carry rel="nofollow" — new combinations are not discovered
 *      by crawling the site in the first place.
 *   2. Every filter URL serves noindex,nofollow at the HTML level (meta robots
 *      via wp_robots + an X-Robots-Tag header), so each recrawl DROPS the URL
 *      from the index.
 *   3. Duplicate/re-ordered values are normalised and 301'd to one canonical
 *      form, so the same result set stops producing endless unique URLs.
 *
 * robots.txt deliberately does NOT disallow the filter parameters any more:
 * a robots block only stops crawling, it does not remove indexed URLs — and it
 * even prevents Google from fetching the pages to see the noindex. Removal
 * requires crawl access + noindex (Google's own guidance). Once the index has
 * emptied out, a Disallow can be reinstated in a physical robots.txt to save
 * crawl budget.
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
 * Whether the current request carries any facet/filter parameter.
 *
 * @return bool
 */
function kindi_is_facet_request(): bool {
	if ( empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}
	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = (string) $key;
		if ( 0 === strpos( $key, 'filter_' ) || in_array( $key, kindi_facet_extra_params(), true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * HTML-level deindexing: meta robots noindex,nofollow on every filter URL.
 * Duplicate robots tags from an SEO plugin are harmless — crawlers obey the
 * most restrictive directive present.
 *
 * @param array<string,bool|string> $robots wp_robots directives.
 * @return array<string,bool|string>
 */
function kindi_facets_noindex( array $robots ): array {
	if ( kindi_is_facet_request() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'kindi_facets_noindex' );

/**
 * The same directive as an X-Robots-Tag response header (belt and braces; also
 * covers responses whose <head> a crawler never parses).
 *
 * @return void
 */
function kindi_facets_noindex_header(): void {
	if ( ! is_admin() && ! wp_doing_ajax() && kindi_is_facet_request() && ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow' );
	}
}
add_action( 'template_redirect', 'kindi_facets_noindex_header', 6 );

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
