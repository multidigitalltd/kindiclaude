<?php
/**
 * TEMPORARY tool — list every order that carries a personal gift greeting
 * (_kindi_gift_message). Kindi ▸ "ברכות מתנה". Works with both order storages
 * (HPOS wc_orders_meta and legacy postmeta). Remove once the list is pulled.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the report under the Kindi menu.
 *
 * @return void
 */
function kindi_gift_report_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'ברכות מתנה', 'kindi' ),
		__( 'ברכות מתנה', 'kindi' ),
		'manage_woocommerce',
		'kindi-gift-report',
		'kindi_gift_report_page'
	);
}
add_action( 'admin_menu', 'kindi_gift_report_menu' );

/**
 * Order IDs that have a non-empty gift greeting, newest first.
 *
 * @return int[]
 */
function kindi_gift_report_ids(): array {
	global $wpdb;

	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
		&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

	if ( $hpos ) {
		$table = $wpdb->prefix . 'wc_orders_meta';
		$rows  = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT order_id FROM {$table} WHERE meta_key = %s AND meta_value <> '' ORDER BY order_id DESC", '_kindi_gift_message' ) ); // phpcs:ignore WordPress.DB
	} else {
		$rows = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value <> '' ORDER BY post_id DESC", '_kindi_gift_message' ) ); // phpcs:ignore WordPress.DB
	}

	return array_map( 'intval', (array) $rows );
}

/**
 * Render the report table.
 *
 * @return void
 */
function kindi_gift_report_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$ids = kindi_gift_report_ids();

	echo '<div class="wrap"><h1>' . esc_html__( 'הזמנות עם ברכה אישית', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html( sprintf( _n( 'נמצאה %d הזמנה עם ברכה.', 'נמצאו %d הזמנות עם ברכה.', count( $ids ), 'kindi' ), count( $ids ) ) ) . '</p>';

	if ( $ids ) {
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=kindi_gift_export' ), 'kindi_gift_export' );
		echo '<p><a class="button button-primary" href="' . esc_url( $export_url ) . '">' . esc_html__( 'ייצוא לאקסל (CSV)', 'kindi' ) . '</a></p>';
	}
	echo '<table class="widefat striped"><thead><tr>'
		. '<th>' . esc_html__( 'הזמנה', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'תאריך', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'לקוח', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'מתנה', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'ברכה', 'kindi' ) . '</th>'
		. '</tr></thead><tbody>';

	if ( ! $ids ) {
		echo '<tr><td colspan="5">' . esc_html__( 'אין כרגע הזמנות עם ברכה.', 'kindi' ) . '</td></tr>';
	}

	foreach ( $ids as $id ) {
		$order = wc_get_order( $id );
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$gift = function_exists( 'kindi_gift_details' )
			? kindi_gift_details( $order )
			: array( 'bits' => array(), 'message' => (string) $order->get_meta( '_kindi_gift_message' ) );
		$date = $order->get_date_created();

		printf(
			'<tr><td><a href="%s">#%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			esc_url( $order->get_edit_order_url() ),
			esc_html( $order->get_order_number() ),
			esc_html( $date ? wc_format_datetime( $date, 'd/m/Y H:i' ) : '' ),
			esc_html( trim( $order->get_formatted_billing_full_name() ) ),
			esc_html( implode( ' + ', $gift['bits'] ) ),
			nl2br( esc_html( $gift['message'] ) )
		);
	}

	echo '</tbody></table></div>';
}

/**
 * Download the report as a CSV (opens in Excel; UTF-8 BOM keeps Hebrew intact).
 *
 * @return void
 */
function kindi_gift_export_csv(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_gift_export' );

	$ids = kindi_gift_report_ids();

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=gift-messages-' . gmdate( 'Y-m-d' ) . '.csv' );

	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
	fputcsv( $out, array( 'הזמנה', 'תאריך', 'לקוח', 'טלפון', 'מתנה', 'ברכה' ) );

	foreach ( $ids as $id ) {
		$order = wc_get_order( $id );
		if ( ! $order instanceof WC_Order ) {
			continue;
		}
		$gift = function_exists( 'kindi_gift_details' )
			? kindi_gift_details( $order )
			: array( 'bits' => array(), 'message' => (string) $order->get_meta( '_kindi_gift_message' ) );
		$date = $order->get_date_created();

		fputcsv(
			$out,
			array(
				'#' . $order->get_order_number(),
				$date ? wc_format_datetime( $date, 'd/m/Y H:i' ) : '',
				trim( $order->get_formatted_billing_full_name() ),
				$order->get_billing_phone(),
				implode( ' + ', $gift['bits'] ),
				$gift['message'],
			)
		);
	}

	fclose( $out );
	exit;
}
add_action( 'admin_post_kindi_gift_export', 'kindi_gift_export_csv' );
