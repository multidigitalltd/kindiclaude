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
 * Is the "Rich Showcase for Google Reviews" plugin installed & active?
 *
 * @return bool
 */
function kindi_reviews_plugin_active(): bool {
	return defined( 'GRW_VERSION' ) || class_exists( 'WP_Rplg_Google_Reviews\\Includes\\Core\\Database' );
}

/**
 * Admin warning on the Kindi settings screen when the reviews plugin (the data
 * source) is missing.
 *
 * @return void
 */
function kindi_reviews_plugin_notice(): void {
	if ( kindi_reviews_plugin_active() || ! function_exists( 'get_current_screen' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || false === strpos( (string) $screen->id, 'kindi-settings' ) ) {
		return;
	}
	$install = esc_url( admin_url( 'plugin-install.php?s=Rich+Showcase+for+Google+Reviews&tab=search&type=term' ) );
	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'ביקורות גוגל:', 'kindi' ) . '</strong> '
		. esc_html__( 'כדי להציג ביקורות גוגל אמיתיות נדרש להתקין ולהפעיל את התוסף', 'kindi' )
		. ' <a href="' . $install . '">Rich Showcase for Google Reviews</a>. '
		. esc_html__( 'ללא התוסף יוצגו ביקורות הגיבוי הידניות בלבד.', 'kindi' ) . '</p></div>';
}
add_action( 'admin_notices', 'kindi_reviews_plugin_notice' );

/**
 * Read real Google reviews straight from the "Rich Showcase for Google Reviews"
 * plugin's own database tables (it fetches + refreshes them via its cron). This
 * renders them in the theme's designed cards with zero extra front-end assets
 * and no per-request network call — the result is itself cached for 6h.
 *
 * @return array{rating:float,total:int,reviews:array<int,array<string,mixed>>}|array{}
 */
function kindi_grp_reviews(): array {
	$cached = get_transient( 'kindi_grp_reviews' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;
	$t_place  = $wpdb->prefix . 'grp_google_place';
	$t_review = $wpdb->prefix . 'grp_google_review';
	$t_text   = $wpdb->prefix . 'grp_google_review_text';

	// Bail quietly when the plugin (and its tables) aren't present.
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $t_review ) ) !== $t_review ) { // phpcs:ignore WordPress.DB
		return array();
	}

	$limit = (int) apply_filters( 'kindi_reviews_limit', 15 );
	$min   = (int) apply_filters( 'kindi_reviews_min_rating', 4 );

	// One indexed query: reviews + their text (new text table or legacy column)
	// + the business rating/total. Text moved to a separate table keyed by
	// md5(provider:place_id:author_url) in recent plugin versions.
	$sql = "SELECT r.rating AS rating, r.author_name AS author_name, r.profile_photo_url AS photo,
				COALESCE( NULLIF( r.text, '' ), t.text ) AS body,
				p.rating AS biz_rating, p.review_count AS biz_total,
				COALESCE( NULLIF( p.map_url, '' ), p.url ) AS biz_link
			FROM {$t_review} r
			JOIN {$t_place} p ON p.id = r.google_place_id
			LEFT JOIN {$t_text} t
				ON t.review_id = MD5( CONCAT( COALESCE( r.provider, 'google' ), ':', p.place_id, ':', COALESCE( r.author_url, '' ) ) )
				AND t.lang = r.language
			WHERE r.hide = '' AND r.rating >= %d
			HAVING body IS NOT NULL AND body <> ''
			ORDER BY r.time DESC
			LIMIT %d";

	$rows = $wpdb->get_results( $wpdb->prepare( $sql, $min, $limit ) ); // phpcs:ignore WordPress.DB

	if ( empty( $rows ) ) {
		set_transient( 'kindi_grp_reviews', array(), HOUR_IN_SECONDS );
		return array();
	}

	$reviews = array();
	$rating  = 0.0;
	$total   = 0;
	$link    = '';
	foreach ( $rows as $row ) {
		$name      = (string) $row->author_name;
		$reviews[] = array(
			'text'   => wp_strip_all_tags( (string) $row->body ),
			'name'   => $name,
			'rating' => (int) $row->rating,
			'letter' => '' !== $name ? ( function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 ) ) : '★',
			'photo'  => (string) ( $row->photo ?? '' ),
		);
		$rating = (float) $row->biz_rating;
		$total  = (int) $row->biz_total;
		$link   = (string) ( $row->biz_link ?? '' );
	}

	// A manual link from the panel wins over the plugin's stored business URL.
	$opt_link = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'reviews_link' ) : '';

	$data = array(
		'rating'  => $rating,
		'total'   => $total,
		'reviews' => $reviews,
		'link'    => '' !== $opt_link ? $opt_link : $link,
	);

	set_transient( 'kindi_grp_reviews', $data, 6 * HOUR_IN_SECONDS );

	return $data;
}

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
	// 1) Real reviews already stored by the reviews plugin (fast, cached, lean).
	$plugin = kindi_grp_reviews();
	if ( ! empty( $plugin['reviews'] ) ) {
		return $plugin;
	}

	// 2) Curated showcase from the panel.
	$manual = kindi_showcase_reviews();
	if ( $manual ) {
		return $manual;
	}

	// 3) Optional direct Google Places API (only if explicitly configured).
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
