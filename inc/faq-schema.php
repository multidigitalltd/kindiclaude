<?php
/**
 * FAQ structured data for category archives.
 *
 * The questions are read from the category's bottom description (the
 * "שאלות ותשובות נפוצות" block: each <h3> is a question, the <p> after it the
 * answer), so the markup can never drift from the text a visitor sees — the
 * requirement Google states for FAQ rich results.
 *
 * Which categories qualify is a panel setting rather than a list of IDs in
 * code, so the store can add one without a developer.
 *
 * Yoast owns the page's structured data on this site, so the questions are
 * added INTO its graph (the page node also becomes an FAQPage, which is how
 * Yoast's own FAQ block does it) instead of emitting a second, disconnected
 * JSON-LD block. Without an SEO plugin the theme prints its own.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Panel field: which category archives publish their FAQ as structured data.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_faq_schema_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections'] ) ) {
		$tabs['texts']['sections']['סכמת שאלות ותשובות (קטגוריות)'] = array(
			'faq_schema_cats' => array(
				'type'  => 'taxonomy_multi',
				'label' => 'קטגוריות לסימון',
				'help'  => 'בקטגוריות שתסמנו, מקטע "שאלות ותשובות נפוצות" שבתיאור התחתון יפורסם גם כנתונים מובנים (FAQPage) לגוגל. השאלות נקראות מהטקסט עצמו, כך שאין סיכון לפער בין מה שגוגל רואה לבין מה שמוצג בעמוד. בתיאור התחתון יש לכתוב כל שאלה ככותרת H3 ומתחתיה את התשובה כפסקה.',
			),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_faq_schema_settings' );

/**
 * Question/answer pairs parsed out of a category's bottom description.
 *
 * @param int $term_id Category term ID.
 * @return array<int,array<string,mixed>> Schema.org Question nodes.
 */
function kindi_faq_schema_items( int $term_id ): array {
	if ( ! function_exists( 'kindi_archive_desc_value' ) ) {
		return array();
	}
	$html = kindi_archive_desc_value( $term_id );
	if ( '' === trim( $html ) ) {
		return array();
	}
	// Parse what the page actually shows: the renderer pipes the stored value
	// through wpautop(), so a description kept as bare lines only grows its <p>
	// tags on the way out. Matching that here keeps the questions and the markup
	// in step whichever way the text was saved.
	$html = wpautop( $html );

	/**
	 * Heading that opens the FAQ block inside the bottom description. Only the
	 * text after it is parsed, so other headings in the description are ignored.
	 *
	 * @param string $marker Marker text.
	 */
	$marker = (string) apply_filters( 'kindi_faq_schema_marker', 'שאלות ותשובות נפוצות' );
	$pos    = mb_strpos( $html, $marker );
	if ( false === $pos ) {
		return array();
	}

	if ( ! preg_match_all( '#<h3[^>]*>(.*?)</h3>\s*<p[^>]*>(.*?)</p>#su', mb_substr( $html, $pos ), $matches, PREG_SET_ORDER ) ) {
		return array();
	}

	$items = array();
	foreach ( $matches as $pair ) {
		$question = trim( wp_strip_all_tags( html_entity_decode( $pair[1], ENT_QUOTES, 'UTF-8' ) ) );
		$answer   = trim( wp_strip_all_tags( html_entity_decode( $pair[2], ENT_QUOTES, 'UTF-8' ) ) );
		if ( '' === $question || '' === $answer ) {
			continue;
		}
		$items[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	return $items;
}

/**
 * The FAQ of the category being viewed, when it is one of the selected ones.
 *
 * @return array<int,array<string,mixed>>
 */
function kindi_faq_schema_current_items(): array {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return array();
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return array();
	}

	$chosen = function_exists( 'kindi_opt' ) ? kindi_opt( 'faq_schema_cats' ) : array();
	$chosen = is_array( $chosen ) ? array_map( 'intval', $chosen ) : array();
	if ( ! in_array( (int) $term->term_id, $chosen, true ) ) {
		return array();
	}

	return kindi_faq_schema_items( (int) $term->term_id );
}

/**
 * Add the questions to Yoast's graph: the page node gains the FAQPage type and
 * the mainEntity list, exactly as Yoast's own FAQ block does it. Falls back to
 * appending a standalone node if the page node isn't where it's expected.
 *
 * @param array<int,array<string,mixed>> $graph Schema graph.
 * @return array<int,array<string,mixed>>
 */
function kindi_faq_schema_yoast_graph( $graph ): array {
	if ( ! is_array( $graph ) ) {
		return $graph;
	}
	$items = kindi_faq_schema_current_items();
	if ( ! $items ) {
		return $graph;
	}

	foreach ( $graph as $i => $node ) {
		if ( ! is_array( $node ) || ! isset( $node['@type'] ) ) {
			continue;
		}
		$types = (array) $node['@type'];
		if ( ! array_intersect( $types, array( 'CollectionPage', 'WebPage' ) ) ) {
			continue;
		}
		if ( ! in_array( 'FAQPage', $types, true ) ) {
			$types[] = 'FAQPage';
		}
		$graph[ $i ]['@type']      = count( $types ) > 1 ? array_values( $types ) : reset( $types );
		$graph[ $i ]['mainEntity'] = $items;
		return $graph;
	}

	$graph[] = array(
		'@type'      => 'FAQPage',
		'mainEntity' => $items,
	);
	return $graph;
}
add_filter( 'wpseo_schema_graph', 'kindi_faq_schema_yoast_graph', 11 );

/**
 * Standalone JSON-LD when no SEO plugin owns the page's structured data.
 *
 * @return void
 */
function kindi_faq_schema_standalone(): void {
	if ( function_exists( 'kindi_has_seo_plugin' ) && kindi_has_seo_plugin() ) {
		return; // Added to the plugin's graph above instead.
	}
	$items = kindi_faq_schema_current_items();
	if ( ! $items ) {
		return;
	}

	// Slashes stay ESCAPED (no JSON_UNESCAPED_SLASHES): a literal </script> in
	// a description would otherwise close this element and inject markup.
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode(
		array(
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $items,
		),
		JSON_UNESCAPED_UNICODE
	) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON-encoded, slashes escaped.
}
add_action( 'wp_head', 'kindi_faq_schema_standalone', 30 );
