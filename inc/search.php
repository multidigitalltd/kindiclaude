<?php
/**
 * Live product search (FiboSearch-style) — lean AJAX suggestions.
 *
 * Exposes a read-only REST route that returns matching products (thumb, title,
 * price) and product categories as JSON. Results are short-lived transient
 * cached to keep the store fast under typing load.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the search REST route.
 *
 * @return void
 */
function kindi_register_search_route(): void {
	register_rest_route(
		'kindi/v1',
		'/search',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'kindi_rest_search',
			'permission_callback' => '__return_true', // Public, read-only catalogue search.
			'args'                => array(
				'q' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'kindi_register_search_route' );

/**
 * How well a product title matches the search term.
 *
 * WordPress searches with a bare LIKE '%term%', which in Hebrew matches inside
 * unrelated words — "לגו" hits "ספריי גליטר לשיער ולגוף" through "ולגוף". The
 * tiers below separate a real word match from that noise, allowing for the
 * single-letter prefixes (ו/ה/ב/ל/מ/ש/כ) and the plural endings Hebrew glues
 * onto words, so "פאזלים" still scores as a full match for "פאזל".
 *
 * @param string $title Product title.
 * @param string $term  Search term.
 * @return int 100 = whole word, 50 = starts a word, 20 = anywhere, 0 = not in the title.
 */
function kindi_search_score( string $title, string $term ): int {
	$title = trim( $title );
	$term  = trim( $term );
	if ( '' === $title || '' === $term ) {
		return 0;
	}

	$quoted = preg_quote( $term, '/' );
	$letter = '\x{05D0}-\x{05EA}a-zA-Z0-9';      // Hebrew, Latin and digits.
	$prefix = '[\x{05D5}\x{05D4}\x{05D1}\x{05DC}\x{05DE}\x{05E9}\x{05DB}]?';
	$suffix = '(?:\x{05D9}\x{05DD}|\x{05D5}\x{05EA}|\x{05D9}\x{05D5}\x{05EA}|\x{05D9}|\x{05D4})?';

	if ( preg_match( '/(?<![' . $letter . '])' . $prefix . $quoted . $suffix . '(?![' . $letter . '])/ui', $title ) ) {
		return 100;
	}
	if ( preg_match( '/(?<![' . $letter . '])' . $prefix . $quoted . '/ui', $title ) ) {
		return 50;
	}
	if ( false !== mb_stripos( $title, $term ) ) {
		return 20;
	}
	return 0;
}

/**
 * Is a product listable in SEARCH results?
 *
 * WC_Product::is_visible() answers by the current page type, and a REST request
 * is not a search page — so it judged these candidates by shop rules: products
 * set to "search results only" were treated as hidden (and "shop only" ones as
 * visible), the exact inverse of what a search dropdown needs. This applies the
 * search-context rules directly, while keeping the status / out-of-stock checks
 * and the third-party `woocommerce_product_is_visible` filter intact.
 *
 * @param WC_Product $product Product.
 * @return bool
 */
function kindi_search_product_visible( WC_Product $product ): bool {
	$visible = in_array( $product->get_catalog_visibility(), array( 'visible', 'search' ), true );

	if ( 'publish' !== $product->get_status() ) {
		$visible = false;
	}
	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $product->is_in_stock() ) {
		$visible = false;
	}

	/** This filter is documented in WooCommerce: includes/abstracts/abstract-wc-product.php */
	return (bool) apply_filters( 'woocommerce_product_is_visible', $visible, $product->get_id() );
}

/**
 * Dedicated "חיפוש באתר" tab for the synonym dictionary.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_search_settings( array $tabs ): array {
	$tabs['search'] = array(
		'label'    => 'חיפוש באתר',
		'sections' => array(
			'מילים נרדפות בחיפוש' => array(
				'search_synonyms' => array(
					'type'  => 'textarea',
					'rows'  => 14,
					'label' => 'מילון מילים נרדפות',
					'help'  => 'שורה אחת לכל קבוצת מילים שוות ערך, מופרדות בפסיקים. לדוגמה: "לגו, lego" — גולש שיחפש "לגו" יקבל גם מוצרים שבשמם כתוב LEGO באנגלית, ולהפך. שימושי במיוחד לשמות מותגים באנגלית, לכינויים מקובלים ולשגיאות כתיב נפוצות. חל על החיפוש המהיר ועל עמוד התוצאות, ומתייחס לביטוי החיפוש המלא.',
				),
			),
		),
	);
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_search_settings' );

/**
 * The synonym dictionary as groups of equivalent terms.
 *
 * @return array<int,string[]>
 */
function kindi_search_synonym_groups(): array {
	static $groups = null;
	if ( null !== $groups ) {
		return $groups;
	}

	$groups = array();
	foreach ( function_exists( 'kindi_opt_lines' ) ? kindi_opt_lines( 'search_synonyms' ) : array() as $line ) {
		$terms = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( string $t ): string => trim( $t ),
						preg_split( '/[,=|]/u', $line ) ?: array()
					),
					static fn( string $t ): bool => '' !== $t
				)
			)
		);
		if ( count( $terms ) > 1 ) {
			$groups[] = $terms;
		}
	}
	return $groups;
}

/**
 * Terms equivalent to a search phrase, excluding the phrase itself.
 *
 * Matching is on the whole phrase: the dictionary exists for brand names and
 * nicknames typed on their own ("לגו", "פליימוביל"), and expanding individual
 * words inside a longer query would widen results unpredictably.
 *
 * @param string $term Search phrase.
 * @return string[]
 */
function kindi_search_synonyms_for( string $term ): array {
	$term = trim( $term );
	if ( '' === $term ) {
		return array();
	}

	$out = array();
	foreach ( kindi_search_synonym_groups() as $group ) {
		foreach ( $group as $candidate ) {
			if ( 0 === strcasecmp( $candidate, $term ) ) {
				$out = array_merge( $out, $group );
				break;
			}
		}
	}

	return array_values(
		array_filter(
			array_unique( $out ),
			static fn( string $t ): bool => 0 !== strcasecmp( $t, $term )
		)
	);
}

/**
 * Widen the site's main search to the phrase's synonyms (OR).
 *
 * WordPress builds its search SQL as " AND (((title LIKE …) OR …))"; the
 * original clause is kept intact and the synonym clauses are OR'd beside it,
 * so nothing that already matched stops matching. The dropdown does its own
 * expansion in PHP (see kindi_rest_search) rather than relying on this.
 *
 * @param string   $search Search SQL.
 * @param WP_Query $query  Query.
 * @return string
 */
function kindi_search_apply_synonyms( string $search, $query ): string {
	if ( '' === trim( $search ) || ! $query instanceof WP_Query ) {
		return $search;
	}
	if ( is_admin() || ! $query->is_search() || ! $query->is_main_query() ) {
		return $search;
	}

	$terms = kindi_search_synonyms_for( (string) $query->get( 's' ) );
	if ( ! $terms ) {
		return $search;
	}

	global $wpdb;
	$clauses = array();
	foreach ( $terms as $term ) {
		$like      = '%' . $wpdb->esc_like( $term ) . '%';
		$clauses[] = $wpdb->prepare(
			"({$wpdb->posts}.post_title LIKE %s) OR ({$wpdb->posts}.post_excerpt LIKE %s) OR ({$wpdb->posts}.post_content LIKE %s)",
			$like,
			$like,
			$like
		);
	}

	// Only rewrite WordPress's own shape; anything else is left untouched.
	$inner = preg_replace( '/^\s*AND\s+/i', '', $search, 1, $replaced );
	if ( ! $replaced ) {
		return $search;
	}

	return ' AND ( ' . $inner . ' OR ' . implode( ' OR ', $clauses ) . ' ) ';
}
add_filter( 'posts_search', 'kindi_search_apply_synonyms', 20, 2 );

/**
 * The best score a title reaches against any of the search terms.
 *
 * @param string   $title Product title.
 * @param string[] $terms Search phrase plus its synonyms.
 * @return int
 */
function kindi_search_best_score( string $title, array $terms ): int {
	$best = 0;
	foreach ( $terms as $term ) {
		$best = max( $best, kindi_search_score( $title, $term ) );
		if ( 100 === $best ) {
			break;
		}
	}
	return $best;
}

/**
 * Search products + categories for the live dropdown.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function kindi_rest_search( WP_REST_Request $request ): WP_REST_Response {
	$query = trim( (string) $request->get_param( 'q' ) );
	$empty = array(
		'products' => array(),
		'cats'     => array(),
		'all'      => '',
	);

	if ( mb_strlen( $query ) < 2 || ! class_exists( 'WooCommerce' ) ) {
		return rest_ensure_response( $empty );
	}

	// v5 key: the candidate pool is built differently below.
	$cache_key = 'kindi_search_v5_' . md5( $query );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return rest_ensure_response( $cached );
	}

	$limit    = 6;
	$products = array();
	// The phrase plus anything the panel's dictionary calls equivalent, so a
	// Hebrew "לגו" also reaches a title that spells the brand "LEGO".
	$terms = array_merge( array( $query ), kindi_search_synonyms_for( $query ) );

	// One plain query per term, merged by post ID. OR-ing the synonyms into the
	// SQL (as the results page does) depends on WordPress's search clause
	// keeping its exact shape; running the searches separately is predictable
	// and still cheap — one query per term, at most a handful, and the whole
	// response is cached. The pool is deliberately wide because WordPress ranks
	// a title match no higher than a passing mention in a description.
	$candidates = array();
	$seen       = array();
	$per_term   = array();
	foreach ( $terms as $term ) {
		$wp_query = new WP_Query(
			array(
				'post_type'              => 'product',
				'post_status'            => 'publish',
				'posts_per_page'         => 60,
				's'                      => $term,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
			)
		);
		$per_term[ $term ] = count( $wp_query->posts );
		foreach ( $wp_query->posts as $post ) {
			if ( isset( $seen[ $post->ID ] ) ) {
				continue;
			}
			$seen[ $post->ID ] = true;
			$candidates[]      = $post;
		}
	}

	// Rank by title match before loading any product: scoring is plain string
	// work, so only the handful of products actually shown get instantiated.
	$ranked = array();
	foreach ( $candidates as $post ) {
		$ranked[] = array( 'post' => $post, 'score' => kindi_search_best_score( (string) $post->post_title, $terms ) );
	}
	// PHP 8 sorts are stable, so WordPress's own ordering survives within a tier.
	usort( $ranked, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );

	// Keep the best tier that actually has results: a real word match wins over
	// a mid-word coincidence, but a search whose only hits are weak still shows
	// them rather than nothing.
	foreach ( array( 100, 50, 20 ) as $floor ) {
		$tier = array_values( array_filter( $ranked, static fn( array $r ): bool => $r['score'] >= $floor ) );
		if ( $tier ) {
			$ranked = $tier;
			break;
		}
	}

	foreach ( $ranked as $entry ) {
		if ( count( $products ) >= $limit ) {
			break;
		}
		$post    = $entry['post'];
		$product = wc_get_product( $post->ID );
		if ( ! $product || ! kindi_search_product_visible( $product ) ) {
			continue;
		}
		$products[] = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'price' => $product->get_price_html(),
			'img'   => get_the_post_thumbnail_url( $post, 'woocommerce_thumbnail' ) ?: wc_placeholder_img_src( 'woocommerce_thumbnail' ),
		);
	}
	wp_reset_postdata();

	$cats  = array();
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 4,
			'search'     => $query,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$cats[] = array(
				'name'  => $term->name,
				'url'   => (string) get_term_link( $term ),
				'count' => (int) $term->count,
			);
		}
	}

	$data = array(
		'products' => $products,
		'cats'     => $cats,
		'all'      => add_query_arg(
			array(
				's'         => rawurlencode( $query ),
				'post_type' => 'product',
			),
			home_url( '/' )
		),
	);

	// Temporary diagnostics (?debug=1): counts and scores only, the same
	// catalogue data the dropdown already returns. Remove once the live
	// behaviour is understood.
	if ( '1' === (string) $request->get_param( 'debug' ) ) {
		$sample = array();
		foreach ( array_slice( $ranked, 0, 10 ) as $entry ) {
			$product  = wc_get_product( $entry['post']->ID );
			$sample[] = array(
				'title'   => (string) $entry['post']->post_title,
				'score'   => (int) $entry['score'],
				'product' => (bool) $product,
				'visible' => $product ? kindi_search_product_visible( $product ) : null,
			);
		}
		$data['debug'] = array(
			'terms'      => $terms,
			'per_term'   => $per_term,
			'candidates' => count( $candidates ),
			'ranked'     => count( $ranked ),
			'shown'      => count( $products ),
			'sample'     => $sample,
		);
		return rest_ensure_response( $data ); // Never cached.
	}

	set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );

	return rest_ensure_response( $data );
}
