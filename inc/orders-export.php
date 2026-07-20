<?php
/**
 * TEMPORARY tool — Kindi ▸ "ייצוא הזמנות". Export orders of a chosen status and
 * date range to CSV, including the customer phone. Remove after use.
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
function kindi_orders_export_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'ייצוא הזמנות', 'kindi' ),
		__( 'ייצוא הזמנות', 'kindi' ),
		'manage_woocommerce',
		'kindi-orders-export',
		'kindi_orders_export_page'
	);
}
add_action( 'admin_menu', 'kindi_orders_export_menu' );

/**
 * Render the export form.
 *
 * @return void
 */
function kindi_orders_export_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array( 'wc-completed' => 'הושלמה' );

	echo '<div class="wrap"><h1>' . esc_html__( 'ייצוא הזמנות ל-CSV', 'kindi' ) . '</h1>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	echo '<input type="hidden" name="action" value="kindi_orders_export">';
	wp_nonce_field( 'kindi_orders_export' );

	echo '<table class="form-table" role="presentation"><tbody>';

	echo '<tr><th scope="row"><label for="kindi-oe-status">' . esc_html__( 'סטטוס', 'kindi' ) . '</label></th><td><select id="kindi-oe-status" name="status">';
	foreach ( $statuses as $slug => $label ) {
		$slug = preg_replace( '/^wc-/', '', (string) $slug );
		printf( '<option value="%s"%s>%s</option>', esc_attr( $slug ), selected( 'completed', $slug, false ), esc_html( $label ) );
	}
	echo '</select></td></tr>';

	echo '<tr><th scope="row"><label for="kindi-oe-from">' . esc_html__( 'מתאריך', 'kindi' ) . '</label></th><td><input type="date" id="kindi-oe-from" name="from" value="2026-07-05"></td></tr>';
	echo '<tr><th scope="row"><label for="kindi-oe-to">' . esc_html__( 'עד תאריך', 'kindi' ) . '</label></th><td><input type="date" id="kindi-oe-to" name="to" value="2026-07-11"></td></tr>';

	echo '</tbody></table>';
	submit_button( __( 'ייצוא ל-CSV (כולל טלפון)', 'kindi' ) );
	echo '</form>';
	echo '<p class="description">' . esc_html__( 'הטווח כולל את שני התאריכים במלואם. הקובץ נפתח באקסל (UTF-8) ומכיל טלפון, אימייל, שם, סכום וסטטוס.', 'kindi' ) . '</p>';
	echo '</div>';
}

/**
 * Stream the matching orders as a CSV download.
 *
 * @return void
 */
function kindi_orders_export_csv(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_orders_export' );

	$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'completed';
	$from   = isset( $_POST['from'] ) ? sanitize_text_field( wp_unslash( $_POST['from'] ) ) : '';
	$to     = isset( $_POST['to'] ) ? sanitize_text_field( wp_unslash( $_POST['to'] ) ) : '';

	// Validate YYYY-MM-DD.
	$valid = static function ( string $d ): bool {
		return (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d );
	};
	if ( ! $valid( $from ) || ! $valid( $to ) ) {
		wp_die( esc_html__( 'תאריכים לא תקינים.', 'kindi' ) );
	}

	$args = array(
		'status'  => array( $status ),
		'limit'   => -1,
		'orderby' => 'date',
		'order'   => 'ASC',
		'return'  => 'objects',
	);
	// Inclusive of both full days.
	$start = strtotime( $from . ' 00:00:00' );
	$end   = strtotime( $to . ' 23:59:59' );
	if ( $start && $end ) {
		$args['date_created'] = $start . '...' . $end;
	}

	$orders = function_exists( 'wc_get_orders' ) ? wc_get_orders( $args ) : array();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=orders-' . $status . '-' . $from . '_' . $to . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
	fputcsv( $out, array( 'מספר הזמנה', 'תאריך', 'שם', 'טלפון', 'אימייל', 'עיר', 'סכום', 'מטבע', 'סטטוס' ) );

	foreach ( $orders as $order ) {
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$date = $order->get_date_created();
		fputcsv(
			$out,
			array(
				'#' . $order->get_order_number(),
				$date ? $date->date_i18n( 'd/m/Y H:i' ) : '',
				trim( $order->get_formatted_billing_full_name() ),
				$order->get_billing_phone(),
				$order->get_billing_email(),
				$order->get_billing_city(),
				$order->get_total(),
				$order->get_currency(),
				wc_get_order_status_name( $order->get_status() ),
			)
		);
	}

	fclose( $out );
	exit;
}
add_action( 'admin_post_kindi_orders_export', 'kindi_orders_export_csv' );
