<?php
/**
 * ACF bridge — read & adopt third-party custom-field data without depending on
 * the plugin that authored it.
 *
 * ACF (and most field plugins) store values as ordinary post meta. The data
 * therefore survives the plugin being deactivated/removed; only the editor UI
 * goes away. This bridge lets the admin map the store's existing meta keys
 * (auto-detected — no field names needed up front) onto the theme's toy fields,
 * so the gift-by-age filter and the product display read real data. An optional
 * one-click import copies the mapped values into the theme's own `_kindi_*`
 * meta, fully decoupling the catalogue from ACF before it is removed.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Theme field key => the option holding its mapped source meta key.
 *
 * @return array<string,string>
 */
function kindi_acf_map(): array {
	return array(
		'age'         => 'acf_key_age',
		'brand_label' => 'acf_key_brand',
		'pieces'      => 'acf_key_pieces',
		'players'     => 'acf_key_players',
		'play_time'   => 'acf_key_play_time',
	);
}

/**
 * Resolve a toy field: prefer the theme's own `_kindi_*`, fall back to the
 * admin-mapped source meta key (e.g. an ACF field).
 *
 * @param int    $post_id  Product ID.
 * @param string $kindi_key Field key without the `_kindi_` prefix.
 * @return string
 */
function kindi_resolve_field( int $post_id, string $kindi_key ): string {
	$native = (string) get_post_meta( $post_id, '_kindi_' . $kindi_key, true );
	if ( '' !== $native ) {
		return $native;
	}

	$map = kindi_acf_map();
	if ( isset( $map[ $kindi_key ] ) ) {
		$source = (string) kindi_opt( $map[ $kindi_key ], '' );
		if ( '' !== $source ) {
			return (string) get_post_meta( $post_id, $source, true );
		}
	}

	return '';
}

/**
 * Resolve the age label for a product (theme meta or mapped source).
 *
 * @param int $post_id Product ID.
 * @return string
 */
function kindi_resolve_age_label( int $post_id ): string {
	return kindi_resolve_field( $post_id, 'age' );
}

/**
 * Distinct product meta keys present in the store, for the mapping dropdowns.
 * Internal WordPress/WooCommerce keys are filtered out. Cached for 1 hour.
 *
 * @return array<int,string>
 */
function kindi_detected_meta_keys(): array {
	$cached = get_transient( 'kindi_meta_keys' );
	if ( is_array( $cached ) ) {
		return $cached;
	}

	global $wpdb;
	$keys = $wpdb->get_col(
		"SELECT DISTINCT pm.meta_key
		 FROM {$wpdb->postmeta} pm
		 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
		 WHERE p.post_type = 'product'
		 AND pm.meta_key NOT LIKE '\_%'
		 ORDER BY pm.meta_key
		 LIMIT 300"
	);

	$keys = is_array( $keys ) ? array_map( 'strval', $keys ) : array();
	set_transient( 'kindi_meta_keys', $keys, HOUR_IN_SECONDS );

	return $keys;
}

/**
 * Flush the detected-keys cache when products change.
 *
 * @return void
 */
function kindi_flush_meta_keys(): void {
	delete_transient( 'kindi_meta_keys' );
}
add_action( 'save_post_product', 'kindi_flush_meta_keys' );

/**
 * Import: copy mapped source values into the theme's `_kindi_*` meta for every
 * product, then rebuild the numeric age mirror — so the catalogue no longer
 * needs ACF. Capability + nonce verified by the caller.
 *
 * @return int Number of products updated.
 */
function kindi_acf_import(): int {
	$map = array_filter(
		kindi_acf_map(),
		static function ( $opt ) {
			return '' !== (string) kindi_opt( $opt, '' );
		}
	);
	if ( ! $map ) {
		return 0;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$count = 0;
	foreach ( $ids as $id ) {
		$id      = (int) $id;
		$touched = false;
		foreach ( $map as $kindi_key => $opt ) {
			$source = (string) kindi_opt( $opt, '' );
			$value  = (string) get_post_meta( $id, $source, true );
			if ( '' !== $value && '' === (string) get_post_meta( $id, '_kindi_' . $kindi_key, true ) ) {
				update_post_meta( $id, '_kindi_' . $kindi_key, sanitize_text_field( $value ) );
				$touched = true;
			}
		}
		if ( $touched ) {
			++$count;
		}
		if ( function_exists( 'kindi_sync_age_min' ) ) {
			kindi_sync_age_min( $id );
		}
	}

	// Force the gift-finder backfill to re-evaluate.
	delete_option( 'kindi_age_backfill' );

	return $count;
}

/**
 * Handle the import POST from the settings screen.
 *
 * @return void
 */
function kindi_acf_handle_import(): void {
	if ( empty( $_POST['kindi_acf_import'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kindi_acf_import', 'kindi_acf_nonce' ) ) {
		return;
	}

	$count = kindi_acf_import();
	set_transient( 'kindi_acf_import_done', $count, 60 );
	wp_safe_redirect( add_query_arg( array( 'page' => 'kindi-settings', 'tab' => 'texts', 'imported' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_init', 'kindi_acf_handle_import' );

/**
 * Inject the "custom fields (ACF)" mapping section + import tool into the
 * settings "texts" tab.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_acf_settings_section( array $tabs ): array {
	if ( ! isset( $tabs['texts'] ) ) {
		return $tabs;
	}

	$tabs['texts']['sections']['שדות מותאמים (ACF / מטא)'] = array(
		'_acf_intro'   => array( 'type' => 'note', 'label' => '', 'help' => 'מצא את שדות הגיל/המותג שכבר קיימים על המוצרים (גם אם נוצרו ב-ACF), מפה אותם כאן, ולחץ "ייבוא" — הערכים יועתקו לשדות של קינדי כך שהסינון יעבוד והנתונים יישמרו גם אחרי הסרת ACF.' ),
		'acf_key_age'       => array( 'type' => 'meta_select', 'label' => 'שדה "גיל מומלץ"' ),
		'acf_key_brand'     => array( 'type' => 'meta_select', 'label' => 'שדה "מותג"' ),
		'acf_key_pieces'    => array( 'type' => 'meta_select', 'label' => 'שדה "מספר חלקים"' ),
		'acf_key_players'   => array( 'type' => 'meta_select', 'label' => 'שדה "מספר שחקנים"' ),
		'acf_key_play_time' => array( 'type' => 'meta_select', 'label' => 'שדה "זמן משחק"' ),
	);

	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_acf_settings_section' );

/**
 * Render the import button + last-result notice below the settings form.
 *
 * @return void
 */
function kindi_acf_import_tool(): void {
	if ( isset( $_GET['imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count = (int) get_transient( 'kindi_acf_import_done' );
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'הייבוא הושלם — %d מוצרים עודכנו. כעת ניתן להסיר את תוסף ה-ACF בבטחה.', 'kindi' ), $count ) . '</p></div>';
	}

	echo '<hr><h2>' . esc_html__( 'ייבוא נתוני שדות מותאמים', 'kindi' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'מעתיק את ערכי השדות הממופים לשדות של קינדי ובונה את אינדקס הגיל לסינון. בטוח להריץ שוב — לא ידרוס נתונים קיימים של קינדי.', 'kindi' ) . '</p>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_acf_import', 'kindi_acf_nonce' );
	submit_button( __( 'ייבוא נתונים עכשיו', 'kindi' ), 'secondary', 'kindi_acf_import', false );
	echo '</form>';
}
add_action( 'kindi_settings_after_form', 'kindi_acf_import_tool' );
