<?php
/**
 * TEMPORARY tool — Kindi ▸ "וידאו — אבחון והעברה".
 *
 * Diagnose which product meta keys hold video data (the old Woodmart key is
 * unknown), then migrate a chosen key into the theme's `_kindi_product_video`.
 * Remove once the catalogue is migrated.
 *
 * Products always live in postmeta (HPOS only moves orders), so a plain
 * postmeta scan is complete.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the tool under the Kindi menu.
 *
 * @return void
 */
function kindi_video_tool_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'וידאו — אבחון והעברה', 'kindi' ),
		__( 'וידאו — אבחון', 'kindi' ),
		'manage_woocommerce',
		'kindi-video-migrate',
		'kindi_video_tool_page'
	);
}
add_action( 'admin_menu', 'kindi_video_tool_menu' );

/**
 * Product meta keys whose values look like a video (key contains "video", or the
 * value points at YouTube / Vimeo / a video file), with a per-key count.
 *
 * @return array<int,object>
 */
function kindi_video_scan(): array {
	global $wpdb;

	$like_video = '%' . $wpdb->esc_like( 'video' ) . '%';
	$like_yt    = '%' . $wpdb->esc_like( 'youtube.com' ) . '%';
	$like_ytb   = '%' . $wpdb->esc_like( 'youtu.be' ) . '%';
	$like_vim   = '%' . $wpdb->esc_like( 'vimeo.com' ) . '%';
	$like_mp4   = '%' . $wpdb->esc_like( '.mp4' ) . '%';

	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	return (array) $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm.meta_key AS meta_key, COUNT(*) AS c
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'product'
			WHERE pm.meta_value <> '' AND (
				pm.meta_key LIKE %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s
				OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s
			)
			GROUP BY pm.meta_key
			ORDER BY c DESC",
			$like_video,
			$like_yt,
			$like_ytb,
			$like_vim,
			$like_mp4
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery
}

/**
 * A few example products for a meta key.
 *
 * @param string $key   Meta key.
 * @param int    $limit Rows.
 * @return array<int,object>
 */
function kindi_video_examples( string $key, int $limit = 3 ): array {
	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	return (array) $wpdb->get_results(
		$wpdb->prepare(
			"SELECT pm.post_id AS post_id, pm.meta_value AS meta_value
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'product'
			WHERE pm.meta_key = %s AND pm.meta_value <> ''
			LIMIT %d",
			$key,
			$limit
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery
}

/**
 * Render the diagnostic + migration screen.
 *
 * @return void
 */
function kindi_video_tool_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'וידאו במוצרים — אבחון והעברה', 'kindi' ) . '</h1>';

	if ( isset( $_GET['migrated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$n = absint( $_GET['migrated'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( sprintf( _n( 'הועבר סרטון ל-%d מוצר.', 'הועברו סרטונים ל-%d מוצרים.', $n, 'kindi' ), $n ) ) . '</p></div>';
	}

	global $wpdb;
	// phpcs:disable WordPress.DB.DirectDatabaseQuery
	$already = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> ''", '_kindi_product_video' ) );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery
	echo '<p class="description">' . esc_html( sprintf( __( 'כרגע יש סרטון בשדה החדש (_kindi_product_video) ב-%d מוצרים.', 'kindi' ), $already ) ) . '</p>';

	$rows = kindi_video_scan();

	echo '<table class="widefat striped"><thead><tr>'
		. '<th>' . esc_html__( 'מפתח מטא', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'מוצרים', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'דוגמאות', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'פעולה', 'kindi' ) . '</th>'
		. '</tr></thead><tbody>';

	if ( ! $rows ) {
		echo '<tr><td colspan="4">' . esc_html__( 'לא נמצאו מפתחות מטא עם וידאו.', 'kindi' ) . '</td></tr>';
	}

	foreach ( $rows as $row ) {
		$key      = (string) $row->meta_key;
		$examples = kindi_video_examples( $key );
		$ex_html  = array();
		foreach ( $examples as $ex ) {
			$val       = (string) $ex->meta_value;
			$snippet   = mb_strlen( $val ) > 60 ? mb_substr( $val, 0, 57 ) . '…' : $val;
			$ex_html[] = sprintf(
				'<a href="%s">#%d</a>: <code>%s</code>',
				esc_url( (string) get_edit_post_link( (int) $ex->post_id ) ),
				(int) $ex->post_id,
				esc_html( $snippet )
			);
		}

		echo '<tr>';
		echo '<td><code>' . esc_html( $key ) . '</code></td>';
		echo '<td>' . esc_html( (string) (int) $row->c ) . '</td>';
		echo '<td>' . wp_kses_post( implode( '<br>', $ex_html ) ) . '</td>';
		echo '<td>';
		if ( '_kindi_product_video' !== $key ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
			echo '<input type="hidden" name="action" value="kindi_video_migrate">';
			echo '<input type="hidden" name="source" value="' . esc_attr( $key ) . '">';
			wp_nonce_field( 'kindi_video_migrate' );
			echo '<button type="submit" class="button button-primary">' . esc_html__( 'העבר מפתח זה לשדה החדש', 'kindi' ) . '</button>';
			echo '</form>';
		} else {
			echo '&mdash;';
		}
		echo '</td></tr>';
	}

	echo '</tbody></table>';
	echo '<p class="description">' . esc_html__( 'ההעברה מעתיקה את הערך לשדה החדש רק במוצרים שבהם השדה החדש ריק (לא דורסת). קישורי קבצים ומזהי מדיה מומרים אוטומטית לכתובת מלאה.', 'kindi' ) . '</p>';
	echo '</div>';
}

/**
 * Migrate a chosen source meta key into `_kindi_product_video`.
 *
 * @return void
 */
function kindi_video_migrate(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_video_migrate' );

	$source   = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
	$migrated = 0;

	if ( '' !== $source && '_kindi_product_video' !== $source ) {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => $source,
						'value'   => '',
						'compare' => '!=',
					),
				),
			)
		);

		foreach ( $ids as $id ) {
			$id  = (int) $id;
			$val = trim( (string) get_post_meta( $id, $source, true ) );
			if ( '' === $val ) {
				continue;
			}
			// Woodmart may store an attachment ID for self-hosted videos.
			if ( ctype_digit( $val ) ) {
				$val = (string) wp_get_attachment_url( (int) $val );
			}
			$val = esc_url_raw( $val );
			if ( '' === $val ) {
				continue;
			}
			// Never overwrite a value already set on the new field.
			if ( '' !== trim( (string) get_post_meta( $id, '_kindi_product_video', true ) ) ) {
				continue;
			}
			update_post_meta( $id, '_kindi_product_video', $val );
			$migrated++;
		}
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'kindi-video-migrate', 'migrated' => $migrated ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_kindi_video_migrate', 'kindi_video_migrate' );
