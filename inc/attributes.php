<?php
/**
 * Product attributes bridge — the toy meta fields as WooCommerce attributes.
 *
 * The filterable product properties (age, brand, pieces, players, play time,
 * skills) live as global WooCommerce attributes — the platform default that
 * archive filtering, the "additional information" table and third-party tools
 * all understand. This module resolves each property to its attribute
 * taxonomy (matching existing attributes by label, so nothing is duplicated)
 * so the theme's display and filtering always
 * find the right taxonomy. (The one-time meta→attributes migration ran and
 * was removed; the legacy meta remains readable as a display fallback.)
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Property map: field key => [attribute label candidates (match existing),
 * creation slug, creation label].
 *
 * @return array<string,array{labels:array<int,string>,slug:string,label:string}>
 */
function kindi_attr_map(): array {
	return array(
		'age'         => array( 'labels' => array( 'גיל', 'גיל מומלץ', 'גילאים', 'age' ), 'slug' => 'age', 'label' => 'גיל' ),
		'brand_label' => array( 'labels' => array( 'מותג', 'מותגים', 'brand' ), 'slug' => 'brand', 'label' => 'מותג' ),
		'pieces'      => array( 'labels' => array( 'מספר חלקים', 'חלקים', 'pieces' ), 'slug' => 'pieces', 'label' => 'מספר חלקים' ),
		'players'     => array( 'labels' => array( 'מספר שחקנים', 'שחקנים', 'players' ), 'slug' => 'players', 'label' => 'מספר שחקנים' ),
		'play_time'   => array( 'labels' => array( 'זמן משחק/בנייה', 'זמן משחק', 'play-time', 'play time' ), 'slug' => 'play-time', 'label' => 'זמן משחק/בנייה' ),
		'skills'      => array( 'labels' => array( 'מיומנויות', 'skills' ), 'slug' => 'skills', 'label' => 'מיומנויות' ),
	);
}

/**
 * Resolve a property to its attribute taxonomy (pa_*), matching existing
 * global attributes by label (case-insensitive).
 *
 * @param string $field Field key from kindi_attr_map().
 * @return string Taxonomy name, or '' when unresolved.
 */
function kindi_attr_tax_for( string $field ): string {
	static $cache = array();
	if ( isset( $cache[ $field ] ) ) {
		return $cache[ $field ];
	}

	$map = kindi_attr_map();
	if ( ! isset( $map[ $field ] ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return '';
	}

	$wanted = array_map( 'mb_strtolower', $map[ $field ]['labels'] );
	foreach ( wc_get_attribute_taxonomies() as $att ) {
		if ( in_array( mb_strtolower( trim( (string) $att->attribute_label ) ), $wanted, true )
			|| in_array( mb_strtolower( trim( (string) $att->attribute_name ) ), $wanted, true ) ) {
			$cache[ $field ] = 'pa_' . $att->attribute_name;
			return $cache[ $field ];
		}
	}

	$cache[ $field ] = '';
	return '';
}

/**
 * The age attribute's filter arg for a band: WooCommerce-native
 * filter_{attribute}={term-slug} when the attribute + term exist, otherwise
 * the legacy kindi_age meta filter (kept as a fallback for old links).
 *
 * @param string $band_key Band key from kindi_age_bands().
 * @return array{param:string,value:string}
 */
function kindi_age_filter_arg( string $band_key ): array {
	$tax = kindi_attr_tax_for( 'age' );
	if ( '' !== $tax && taxonomy_exists( $tax ) ) {
		$term = get_term_by( 'name', '13plus' === $band_key ? '13+' : $band_key, $tax );
		if ( $term instanceof WP_Term ) {
			return array(
				'param' => 'filter_' . preg_replace( '/^pa_/', '', $tax ),
				'value' => $term->slug,
			);
		}
	}
	return array( 'param' => 'kindi_age', 'value' => $band_key );
}
