<?php
/**
 * Product attributes bridge — the toy meta fields as WooCommerce attributes.
 *
 * The filterable product properties (age, brand, pieces, players, play time,
 * skills) live as global WooCommerce attributes — the platform default that
 * archive filtering, the "additional information" table and third-party tools
 * all understand. This module resolves each property to its attribute
 * taxonomy (matching existing attributes by label, so nothing is duplicated)
 * and ships a one-time migration that copies the legacy per-product meta into
 * attribute terms — APPEND-ONLY: existing attribute values on a product are
 * never removed or overwritten.
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
 * global attributes by label (case-insensitive). Optionally creates the
 * attribute when absent (migration only).
 *
 * @param string $field  Field key from kindi_attr_map().
 * @param bool   $create Create the global attribute when missing.
 * @return string Taxonomy name, or '' when unresolved.
 */
function kindi_attr_tax_for( string $field, bool $create = false ): string {
	static $cache = array();
	$cache_key = $field . ( $create ? ':c' : '' );
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$map = kindi_attr_map();
	if ( ! isset( $map[ $field ] ) || ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return '';
	}

	$wanted = array_map( 'mb_strtolower', $map[ $field ]['labels'] );
	foreach ( wc_get_attribute_taxonomies() as $att ) {
		if ( in_array( mb_strtolower( trim( (string) $att->attribute_label ) ), $wanted, true )
			|| in_array( mb_strtolower( trim( (string) $att->attribute_name ) ), $wanted, true ) ) {
			$cache[ $cache_key ] = 'pa_' . $att->attribute_name;
			return $cache[ $cache_key ];
		}
	}

	if ( ! $create ) {
		$cache[ $cache_key ] = '';
		return '';
	}

	$id = wc_create_attribute(
		array(
			'name'         => $map[ $field ]['label'],
			'slug'         => $map[ $field ]['slug'],
			'type'         => 'select',
			'order_by'     => 'name',
			'has_archives' => false,
		)
	);
	if ( is_wp_error( $id ) ) {
		$cache[ $cache_key ] = '';
		return '';
	}
	$tax = 'pa_' . $map[ $field ]['slug'];
	// The taxonomy registers on the NEXT request — register it now so terms can
	// be created and assigned within the same (migration) request.
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy( $tax, array( 'product' ), array( 'hierarchical' => false, 'show_ui' => false, 'public' => false ) );
	}
	delete_transient( 'wc_attribute_taxonomies' );
	$cache[ $cache_key ] = $tax;
	return $tax;
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

/* ====================== One-time meta → attributes migration ====================== */

/**
 * Collect a product's legacy values per field ('' entries dropped).
 *
 * @param int $product_id Product ID.
 * @return array<string,array<int,string>> field => term names.
 */
function kindi_attr_values_for_product( int $product_id ): array {
	$out = array();

	// Age: the band tags (short labels as term names).
	$bands = (array) get_post_meta( $product_id, '_kindi_age_band' );
	$names = array();
	foreach ( $bands as $band ) {
		$names[] = '13plus' === $band ? '13+' : (string) $band;
	}
	if ( $names ) {
		$out['age'] = $names;
	}

	// Single-value fields.
	foreach ( array( 'brand_label', 'pieces', 'players', 'play_time' ) as $field ) {
		$value = trim( (string) get_post_meta( $product_id, '_kindi_' . $field, true ) );
		if ( '' !== $value ) {
			$out[ $field ] = array( $value );
		}
	}

	// Skills: newline/comma separated list → one term each.
	$skills = (string) get_post_meta( $product_id, '_kindi_skills', true );
	if ( '' !== trim( $skills ) ) {
		$parts = preg_split( '/\r\n|\r|\n|,|،|;|\|/u', $skills ) ?: array();
		$parts = array_values( array_filter( array_map( 'trim', $parts ), 'strlen' ) );
		if ( $parts ) {
			$out['skills'] = $parts;
		}
	}

	return $out;
}

/**
 * Migrate one product: append terms + expose the attribute on the product.
 * Existing attribute assignments are never removed.
 *
 * @param int $product_id Product ID.
 * @return void
 */
function kindi_attr_migrate_product( int $product_id ): void {
	$product_attributes = get_post_meta( $product_id, '_product_attributes', true );
	$product_attributes = is_array( $product_attributes ) ? $product_attributes : array();
	$changed            = false;

	foreach ( kindi_attr_values_for_product( $product_id ) as $field => $names ) {
		$tax = kindi_attr_tax_for( $field, true );
		if ( '' === $tax || ! taxonomy_exists( $tax ) ) {
			continue;
		}

		$term_ids = array();
		foreach ( $names as $name ) {
			$existing = term_exists( $name, $tax );
			if ( ! $existing ) {
				$existing = wp_insert_term( $name, $tax );
			}
			if ( ! is_wp_error( $existing ) && $existing ) {
				$term_ids[] = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
			}
		}
		if ( ! $term_ids ) {
			continue;
		}

		wp_set_object_terms( $product_id, $term_ids, $tax, true ); // Append — never replaces.

		// Expose the attribute on the product (additional-information table,
		// filters) — only when it isn't listed yet, so existing setups keep
		// their visibility/variation choices untouched.
		if ( ! isset( $product_attributes[ $tax ] ) ) {
			$product_attributes[ $tax ] = array(
				'name'         => $tax,
				'value'        => '',
				'position'     => count( $product_attributes ),
				'is_visible'   => 1,
				'is_variation' => 0,
				'is_taxonomy'  => 1,
			);
			$changed = true;
		}
	}

	if ( $changed ) {
		update_post_meta( $product_id, '_product_attributes', $product_attributes );
	}
	update_post_meta( $product_id, '_kindi_attr_migrated', 1 );

	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients( $product_id );
	}
}

/**
 * Batch runner (~10s per pass, continues via single-event cron).
 *
 * @return void
 */
function kindi_attr_migrate_run(): void {
	if ( 'running' !== get_option( 'kindi_attr_migrate', '' ) ) {
		return;
	}
	$deadline = time() + 10;

	do {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 50,
				'no_found_rows'  => true,
				'meta_query'     => array(
					array( 'key' => '_kindi_attr_migrated', 'compare' => 'NOT EXISTS' ),
				),
			)
		);
		if ( ! $ids ) {
			update_option( 'kindi_attr_migrate', 'done', false );
			// Refresh the archive facet caches so the new attributes/terms show.
			update_option( 'kindi_term_ver', (int) get_option( 'kindi_term_ver', 1 ) + 1, false );
			return;
		}
		foreach ( $ids as $id ) {
			kindi_attr_migrate_product( (int) $id );
			update_option( 'kindi_attr_migrated_count', (int) get_option( 'kindi_attr_migrated_count', 0 ) + 1, false );
		}
	} while ( time() < $deadline );

	if ( ! wp_next_scheduled( 'kindi_attr_migrate_cron' ) ) {
		wp_schedule_single_event( time() + 15, 'kindi_attr_migrate_cron' );
	}
}
add_action( 'kindi_attr_migrate_cron', 'kindi_attr_migrate_run' );

/**
 * "Start migration" button handler.
 *
 * @return void
 */
function kindi_attr_migrate_start(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_attr_migrate' );

	update_option( 'kindi_attr_migrate', 'running', false );
	kindi_attr_migrate_run();

	wp_safe_redirect( add_query_arg(
		array( 'page' => 'kindi-settings', 'tab' => 'texts', 'kindi_attr_migrate' => '1' ),
		admin_url( 'admin.php' )
	) );
	exit;
}
add_action( 'admin_post_kindi_attr_migrate', 'kindi_attr_migrate_start' );

/**
 * Migration panel (settings screen, texts tab).
 *
 * @return void
 */
function kindi_attr_migrate_panel(): void {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'texts' !== $tab ) {
		return;
	}

	$pending_q = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'fields'         => 'ids',
			'posts_per_page' => 1,
			'meta_query'     => array(
				array( 'key' => '_kindi_attr_migrated', 'compare' => 'NOT EXISTS' ),
			),
		)
	);
	$pending = (int) $pending_q->found_posts;
	wp_reset_postdata();

	$state = (string) get_option( 'kindi_attr_migrate', '' );
	$done  = (int) get_option( 'kindi_attr_migrated_count', 0 );

	echo '<hr><h2>' . esc_html__( 'העברת שדות המוצר לתכונות WooCommerce', 'kindi' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'מעתיק חד-פעמית את גיל / מותג / מספר חלקים / מספר שחקנים / זמן משחק / מיומנויות מהשדות הישנים אל תכונות המוצר — בהוספה בלבד, בלי לגעת בערכי תכונות קיימים. רץ ברקע.', 'kindi' ) . '</p>';
	echo '<p><strong>' . esc_html( sprintf( /* translators: 1: processed, 2: pending. */ __( 'טופלו עד כה: %1$d · ממתינים: %2$d', 'kindi' ), $done, $pending ) ) . '</strong></p>';

	if ( 0 === $pending ) {
		echo '<p style="color:#15803d;font-weight:600">' . esc_html__( 'ההעברה הושלמה — כל המוצרים טופלו.', 'kindi' ) . '</p>';
		return;
	}
	if ( 'running' === $state ) {
		echo '<p style="color:#996800;font-weight:600">' . esc_html__( 'ההעברה רצה ברקע… רעננו את העמוד לעדכון המונה.', 'kindi' ) . '</p>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=kindi_attr_migrate' ), 'kindi_attr_migrate' );
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'התחלת ההעברה עכשיו', 'kindi' ) . '</a></p>';
}
add_action( 'kindi_settings_after_form', 'kindi_attr_migrate_panel' );
