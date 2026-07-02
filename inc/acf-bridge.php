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
		'skills'      => 'acf_key_skills',
		'players'     => 'acf_key_players',
		'play_time'   => 'acf_key_play_time',
		'pieces'      => 'acf_key_pieces',
	);
}

/**
 * Toy fields that hold a list (stored multiline). Their values are sanitised /
 * normalised keeping line breaks; everything else is single-line.
 *
 * @return array<int,string>
 */
function kindi_acf_multiline_keys(): array {
	return array( 'skills' );
}

/**
 * Flatten a raw meta value to a string. ACF select/checkbox/repeater fields are
 * stored as arrays — join them to newlines so they read as a clean list.
 *
 * @param mixed $raw Raw meta value.
 * @return string
 */
function kindi_flatten_meta( $raw ): string {
	if ( is_array( $raw ) ) {
		$flat = array();
		array_walk_recursive(
			$raw,
			static function ( $v ) use ( &$flat ) {
				if ( is_scalar( $v ) && '' !== (string) $v ) {
					$flat[] = (string) $v;
				}
			}
		);
		return implode( "\n", $flat );
	}
	return (string) $raw;
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
			return kindi_flatten_meta( get_post_meta( $post_id, $source, true ) );
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
function kindi_acf_import_batch( int $limit = 50 ): array {
	$map = array_filter(
		kindi_acf_map(),
		static function ( $opt ) {
			return '' !== (string) kindi_opt( $opt, '' );
		}
	);
	if ( ! $map ) {
		return array( 'done' => true, 'count' => 0 );
	}

	$run = (int) get_option( 'kindi_acf_run', 0 );

	// Products not yet processed in THIS run (run-id versioning avoids bulk meta
	// deletes when re-importing).
	$ids = get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'any',
			'posts_per_page' => $limit,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				'relation' => 'OR',
				array( 'key' => '_kindi_acf_run', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_kindi_acf_run', 'value' => $run, 'compare' => '!=' ),
			),
		)
	);

	if ( ! $ids ) {
		return array( 'done' => true, 'count' => 0 );
	}

	$multiline = kindi_acf_multiline_keys();
	$count     = 0;
	foreach ( $ids as $id ) {
		$id      = (int) $id;
		$touched = false;
		foreach ( $map as $kindi_key => $opt ) {
			$source = (string) kindi_opt( $opt, '' );
			$value  = kindi_flatten_meta( get_post_meta( $id, $source, true ) );
			if ( '' !== $value && '' === (string) get_post_meta( $id, '_kindi_' . $kindi_key, true ) ) {
				$clean = in_array( $kindi_key, $multiline, true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
				update_post_meta( $id, '_kindi_' . $kindi_key, $clean );
				$touched = true;
			}
		}
		if ( $touched ) {
			++$count;
		}
		if ( function_exists( 'kindi_sync_age_min' ) ) {
			kindi_sync_age_min( $id );
		}
		update_post_meta( $id, '_kindi_acf_run', $run );
	}

	return array( 'done' => false, 'count' => $count );
}

/**
 * Cron worker: process one batch, accumulate the count, reschedule until done.
 *
 * @return void
 */
function kindi_acf_import_cron(): void {
	$res   = kindi_acf_import_batch( 50 );
	$total = (int) get_transient( 'kindi_acf_import_count' ) + (int) $res['count'];
	set_transient( 'kindi_acf_import_count', $total, DAY_IN_SECONDS );

	if ( $res['done'] ) {
		delete_option( 'kindi_acf_importing' );
		delete_option( 'kindi_age_backfill' ); // Re-evaluate the gift-finder age index.
		set_transient( 'kindi_acf_import_done', $total, 5 * MINUTE_IN_SECONDS );
	} elseif ( ! wp_next_scheduled( 'kindi_acf_import_cron' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'kindi_acf_import_cron' );
	}
}
add_action( 'kindi_acf_import_cron', 'kindi_acf_import_cron' );

/**
 * Handle the import POST from the settings screen — starts a background run.
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

	// Begin a new run (bumping the run id marks every product as pending again).
	update_option( 'kindi_acf_run', (int) get_option( 'kindi_acf_run', 0 ) + 1, false );
	update_option( 'kindi_acf_importing', 1, false );
	delete_transient( 'kindi_acf_import_count' );
	delete_transient( 'kindi_acf_import_done' );
	if ( ! wp_next_scheduled( 'kindi_acf_import_cron' ) ) {
		wp_schedule_single_event( time() + 5, 'kindi_acf_import_cron' );
	}
	// Kick WP-Cron right away (a loopback request) instead of waiting for the
	// next visit; on hosts where WP-Cron is blocked entirely, the settings page
	// itself also processes batches inline on every refresh (see the tool below).
	if ( function_exists( 'spawn_cron' ) ) {
		spawn_cron();
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'kindi-settings', 'tab' => 'texts', 'imported' => 'started' ), admin_url( 'admin.php' ) ) );
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
		'acf_key_skills'    => array( 'type' => 'meta_select', 'label' => 'שדה "מיומנויות"' ),
		'acf_key_players'   => array( 'type' => 'meta_select', 'label' => 'שדה "מספר שחקנים"' ),
		'acf_key_play_time' => array( 'type' => 'meta_select', 'label' => 'שדה "זמן משחק"' ),
		'acf_key_pieces'    => array( 'type' => 'meta_select', 'label' => 'שדה "מספר חלקים" (אופציונלי)' ),
		'acf_key_archive_desc' => array( 'type' => 'text', 'label' => 'שדה "תיאור תחתון לארכיון" (קטגוריה)', 'help' => 'שם שדה ה-ACF ברמת הקטגוריה. נטען אוטומטית בתחתית עמוד הקטגוריה.' ),
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
	// WP-Cron can be disabled or stalled on some hosts, which would leave the
	// background run stuck at 0 forever. Make each settings-page load drive real
	// progress: process a few batches inline while a run is active. Idempotent —
	// the _kindi_acf_run marker stops a product being processed twice even if
	// the cron worker also fires.
	if ( get_option( 'kindi_acf_importing' ) && current_user_can( 'manage_options' ) ) {
		for ( $i = 0; $i < 4 && get_option( 'kindi_acf_importing' ); $i++ ) {
			kindi_acf_import_cron();
		}
	}

	// No fields mapped = nothing to import — surface that instead of a 0-count.
	$kindi_mapped = array_filter(
		kindi_acf_map(),
		static function ( $opt ) {
			return '' !== (string) kindi_opt( $opt, '' );
		}
	);
	if ( ! $kindi_mapped && ! get_option( 'kindi_acf_importing' ) && false === get_transient( 'kindi_acf_import_done' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'עדיין לא מופו שדות: בחרו למעלה את שדות הגיל/המותג ולחצו "שמירה" — ורק אז הריצו את הייבוא.', 'kindi' ) . '</p></div>';
	}

	// A finished run shows the final count; a still-running one shows progress.
	$done = get_transient( 'kindi_acf_import_done' );
	if ( false !== $done ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'הייבוא הושלם — %d מוצרים עודכנו. כעת ניתן להסיר את תוסף ה-ACF בבטחה.', 'kindi' ), (int) $done ) . '</p></div>';
	} elseif ( get_option( 'kindi_acf_importing' ) ) {
		$so_far = (int) get_transient( 'kindi_acf_import_count' );
		echo '<div class="notice notice-info"><p>' . sprintf( esc_html__( 'הייבוא רץ ברקע… %d מוצרים עודכנו עד כה. אפשר להמשיך לעבוד; רעננו את העמוד לעדכון.', 'kindi' ), $so_far ) . '</p></div>';
	}

	echo '<hr><h2>' . esc_html__( 'ייבוא נתוני שדות מותאמים', 'kindi' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'מעתיק את ערכי השדות הממופים לשדות של קינדי ובונה את אינדקס הגיל לסינון. בטוח להריץ שוב — לא ידרוס נתונים קיימים של קינדי.', 'kindi' ) . '</p>';
	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_acf_import', 'kindi_acf_nonce' );
	submit_button( __( 'ייבוא נתונים עכשיו', 'kindi' ), 'secondary', 'kindi_acf_import', false );
	echo '</form>';
}
add_action( 'kindi_settings_after_form', 'kindi_acf_import_tool' );
