<?php
/**
 * Archive filters — category chips, price range and product-attribute facets
 * (brand, age, …) on shop and product-taxonomy archives. WooCommerce applies
 * the chosen `filter_*` attributes automatically; price is applied via the
 * product meta-lookup table.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Apply min/max price from the query string to the main shop query.
 *
 * @param array<string,string> $clauses Query clauses.
 * @param WP_Query             $query   Query.
 * @return array<string,string>
 */
function kindi_price_filter_clauses( array $clauses, $query ): array {
	if ( is_admin() || ! $query->is_main_query() ) {
		return $clauses;
	}
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() || is_search() ) ) {
		return $clauses;
	}
	if ( ! isset( $_GET['min_price'] ) && ! isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $clauses;
	}

	global $wpdb;
	$min = isset( $_GET['min_price'] ) ? (float) $_GET['min_price'] : 0;          // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max = isset( $_GET['max_price'] ) ? (float) $_GET['max_price'] : PHP_INT_MAX; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$clauses['join']    .= " INNER JOIN {$wpdb->wc_product_meta_lookup} kpl ON {$wpdb->posts}.ID = kpl.product_id ";
	$clauses['where']   .= $wpdb->prepare( ' AND kpl.min_price >= %f AND kpl.max_price <= %f ', $min, $max );
	$clauses['groupby']  = "{$wpdb->posts}.ID";

	return $clauses;
}
add_filter( 'posts_clauses', 'kindi_price_filter_clauses', 20, 2 );

/**
 * Render the filter bar above the product loop.
 *
 * @return void
 */
function kindi_archive_filters(): void {
	if ( ! function_exists( 'is_shop' ) || ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}

	echo '<div class="kindi-filters">';

	// Category chips.
	$cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 14,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	if ( ! is_wp_error( $cats ) && $cats ) {
		echo '<div class="kindi-filters__cats">';
		printf( '<a class="kindi-chip%s" href="%s">הכל</a>', is_shop() ? ' is-active' : '', esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ) );
		foreach ( $cats as $term ) {
			printf(
				'<a class="kindi-chip%s" href="%s">%s</a>',
				is_tax( 'product_cat', $term->term_id ) ? ' is-active' : '',
				esc_url( (string) get_term_link( $term ) ),
				esc_html( $term->name )
			);
		}
		echo '</div>';
	}

	echo '<div class="kindi-filters__row">';

	// Attribute facets (brand, age, …).
	if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
		foreach ( wc_get_attribute_taxonomies() as $att ) {
			$taxonomy = wc_attribute_taxonomy_name( $att->attribute_name );
			$terms    = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => true ) );
			if ( is_wp_error( $terms ) || ! $terms ) {
				continue;
			}

			$param  = 'filter_' . $att->attribute_name;
			$chosen = isset( $_GET[ $param ] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			echo '<details class="kindi-filters__att"><summary>' . esc_html( $att->attribute_label ) . '</summary><div class="kindi-filters__opts">';
			foreach ( $terms as $term ) {
				$is_on = in_array( $term->slug, $chosen, true );
				$new   = $is_on ? array_diff( $chosen, array( $term->slug ) ) : array_merge( $chosen, array( $term->slug ) );
				$url   = $new ? add_query_arg( $param, implode( ',', $new ) ) : remove_query_arg( $param );
				printf(
					'<a class="kindi-fopt%s" href="%s">%s</a>',
					$is_on ? ' is-active' : '',
					esc_url( $url ),
					esc_html( $term->name )
				);
			}
			echo '</div></details>';
		}
	}

	// Price range.
	$min_v = isset( $_GET['min_price'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['min_price'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max_v = isset( $_GET['max_price'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['max_price'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<form class="kindi-filters__price" method="get">';
	foreach ( array( 'orderby', 'post_type', 's' ) as $keep ) {
		if ( isset( $_GET[ $keep ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			printf( '<input type="hidden" name="%s" value="%s">', esc_attr( $keep ), esc_attr( sanitize_text_field( wp_unslash( $_GET[ $keep ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}
	printf( '<input type="number" name="min_price" inputmode="numeric" placeholder="ממחיר" value="%s">', $min_v );
	printf( '<input type="number" name="max_price" inputmode="numeric" placeholder="עד מחיר" value="%s">', $max_v );
	echo '<button type="submit" class="kindi-chip kindi-chip--go">סינון</button>';
	echo '</form>';

	echo '</div></div>';
}
add_action( 'woocommerce_before_shop_loop', 'kindi_archive_filters', 5 );
