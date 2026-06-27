<?php
/**
 * Google reviews — pull real reviews + rating from the Google Places API and
 * surface them in the "customers" section. Cached for 12h; the request runs on
 * the site server (not the visitor), gracefully degrading to the static
 * testimonials when not configured.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Manually-curated "showcase" reviews from the control panel — no API key, no
 * external request. Each line: "name | rating(1-5) | text".
 *
 * @return array{rating:float,total:int,reviews:array<int,array{text:string,name:string,rating:int,letter:string}>}|array{}
 */
function kindi_showcase_reviews(): array {
	$lines = function_exists( 'kindi_opt_lines' ) ? kindi_opt_lines( 'reviews_manual' ) : array();

	$reviews = array();
	foreach ( $lines as $line ) {
		$parts = array_map( 'trim', explode( '|', $line ) );
		if ( count( $parts ) < 3 ) {
			continue;
		}
		$name = $parts[0];
		$text = $parts[2];
		if ( '' === $name || '' === $text ) {
			continue;
		}
		$reviews[] = array(
			'text'   => $text,
			'name'   => $name,
			'rating' => max( 1, min( 5, (int) $parts[1] ) ),
			'letter' => function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 ),
		);
	}

	if ( ! $reviews ) {
		return array();
	}

	return array(
		'rating'  => (float) ( kindi_opt( 'reviews_rating' ) ?: 5 ),
		'total'   => (int) ( kindi_opt( 'reviews_count' ) ?: count( $reviews ) ),
		'reviews' => $reviews,
		'link'    => (string) kindi_opt( 'reviews_link' ),
	);
}

/**
 * Reviews for the showcase — curated entries first (no API), falling back to the
 * Google Places API only if it is explicitly configured.
 *
 * @return array{rating:float,total:int,reviews:array<int,array{text:string,name:string,rating:int,letter:string}>}|array{}
 */
function kindi_google_reviews(): array {
	$manual = kindi_showcase_reviews();
	if ( $manual ) {
		return $manual;
	}

	$place = (string) kindi_opt( 'google_place_id' );
	$key   = (string) kindi_opt( 'google_api_key' );

	if ( ! $place || ! $key ) {
		return array();
	}

	$cached = get_transient( 'kindi_g_reviews' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	$url = add_query_arg(
		array(
			'place_id' => rawurlencode( $place ),
			'fields'   => 'reviews,rating,user_ratings_total',
			'language' => 'iw',
			'reviews_sort' => 'most_relevant',
			'key'      => rawurlencode( $key ),
		),
		'https://maps.googleapis.com/maps/api/place/details/json'
	);

	$response = wp_remote_get( $url, array( 'timeout' => 8 ) );
	if ( is_wp_error( $response ) ) {
		return array();
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body['result'] ) ) {
		set_transient( 'kindi_g_reviews', array(), HOUR_IN_SECONDS );
		return array();
	}

	$result  = $body['result'];
	$reviews = array();
	foreach ( array_slice( $result['reviews'] ?? array(), 0, 6 ) as $review ) {
		if ( (int) ( $review['rating'] ?? 0 ) < 4 ) {
			continue; // Showcase positive reviews only.
		}
		$name      = (string) ( $review['author_name'] ?? '' );
		$reviews[] = array(
			'text'   => wp_strip_all_tags( (string) ( $review['text'] ?? '' ) ),
			'name'   => $name,
			'rating' => (int) ( $review['rating'] ?? 5 ),
			'letter' => $name ? mb_substr( $name, 0, 1 ) : '★',
		);
	}

	$data = array(
		'rating'  => (float) ( $result['rating'] ?? 0 ),
		'total'   => (int) ( $result['user_ratings_total'] ?? 0 ),
		'reviews' => $reviews,
	);

	set_transient( 'kindi_g_reviews', $data, 12 * HOUR_IN_SECONDS );

	return $data;
}

/**
 * Clear the reviews cache when settings are saved.
 *
 * @return void
 */
function kindi_flush_reviews_cache(): void {
	delete_transient( 'kindi_g_reviews' );
}
add_action( 'update_option_kindi_options', 'kindi_flush_reviews_cache' );
