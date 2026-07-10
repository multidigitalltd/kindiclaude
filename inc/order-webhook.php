<?php
/**
 * Order-completed webhook.
 *
 * When an order reaches the "completed" status, POST its details (customer,
 * products with links, invoice) as JSON to a configurable endpoint — a Pabbly
 * Connect / Zapier / Make workflow. Fully controllable from the dashboard
 * (Webhook — הזמנה שהושלמה): an on/off switch that stops everything instantly,
 * the endpoint URL, a "send a test" button, and a log of the last sends with
 * their HTTP response codes so you can see it working. Each order is sent once
 * (a meta flag guards against re-sends when the status is re-applied).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const KINDI_WEBHOOK_LOG   = 'kindi_webhook_log';
const KINDI_WEBHOOK_SENT  = '_kindi_webhook_sent';
const KINDI_WEBHOOK_LIMIT = 10;

/**
 * Is the webhook switched on and pointed at a valid https endpoint?
 *
 * @return bool
 */
function kindi_webhook_active(): bool {
	return '1' === (string) kindi_opt( 'webhook_enable' )
		&& 0 === strpos( (string) kindi_opt( 'webhook_url' ), 'https://' );
}

/**
 * Build the JSON payload for an order (identical shape for real + test sends).
 *
 * @param WC_Order $order Order.
 * @return array<string,mixed>
 */
function kindi_webhook_payload( WC_Order $order ): array {
	$products = array();
	foreach ( $order->get_items() as $item ) {
		$product_id = $item->get_product_id();
		$product    = wc_get_product( $product_id );
		if ( $product ) {
			$products[] = array(
				'name' => $product->get_name(),
				'url'  => get_permalink( $product_id ),
			);
		}
	}

	$invoice_data   = $order->get_meta( 'wetech_data' );
	$invoice_number = is_array( $invoice_data ) && isset( $invoice_data['invoice']['number'] ) ? $invoice_data['invoice']['number'] : '';

	return array(
		'order_id'       => $order->get_id(),
		'name'           => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
		'phone'          => $order->get_billing_phone(),
		'products'       => $products,
		'invoice_number' => $invoice_number,
		'invoice_url'    => $order->get_meta( '_invoice_url' ),
	);
}

/**
 * POST a payload to the configured endpoint and record the result in the log.
 *
 * @param array<string,mixed> $payload Data to send.
 * @param string              $label   Log label (order id or "בדיקה").
 * @return int HTTP status code (0 on transport error).
 */
function kindi_webhook_send( array $payload, string $label ): int {
	$resp = wp_remote_post(
		(string) kindi_opt( 'webhook_url' ),
		array(
			'method'  => 'POST',
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		)
	);

	$code = is_wp_error( $resp ) ? 0 : (int) wp_remote_retrieve_response_code( $resp );
	$err  = is_wp_error( $resp ) ? $resp->get_error_message() : '';

	$log   = get_option( KINDI_WEBHOOK_LOG );
	$log   = is_array( $log ) ? $log : array();
	array_unshift(
		$log,
		array(
			'time'  => current_time( 'mysql' ),
			'label' => $label,
			'code'  => $code,
			'err'   => $err,
		)
	);
	update_option( KINDI_WEBHOOK_LOG, array_slice( $log, 0, KINDI_WEBHOOK_LIMIT ), false );

	return $code;
}

/**
 * On order completion, send the webhook once.
 *
 * @param int $order_id Order ID.
 * @return void
 */
function kindi_webhook_on_completed( int $order_id ): void {
	if ( ! kindi_webhook_active() ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	// Guard against a second send if "completed" is re-applied.
	if ( $order->get_meta( KINDI_WEBHOOK_SENT ) ) {
		return;
	}

	kindi_webhook_send( kindi_webhook_payload( $order ), '#' . $order_id );

	$order->update_meta_data( KINDI_WEBHOOK_SENT, current_time( 'mysql' ) );
	$order->save();
}
add_action( 'woocommerce_order_status_completed', 'kindi_webhook_on_completed' );

/**
 * "Send a test" button handler — posts a sample payload built from the most
 * recent order (or a stub when there are none), so the connection can be
 * verified without waiting for a real completed order.
 *
 * @return void
 */
function kindi_webhook_test(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_webhook_test' );

	$status = 'disabled';
	if ( kindi_webhook_active() ) {
		$orders  = wc_get_orders( array( 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'objects' ) );
		$payload = ( $orders && $orders[0] instanceof WC_Order )
			? kindi_webhook_payload( $orders[0] )
			: array( 'order_id' => 0, 'name' => 'בדיקה', 'phone' => '', 'products' => array(), 'invoice_number' => '', 'invoice_url' => '' );
		$payload['test'] = true;

		$code   = kindi_webhook_send( $payload, __( 'בדיקה', 'kindi' ) );
		$status = ( $code >= 200 && $code < 300 ) ? 'ok' : 'fail';
	}

	wp_safe_redirect( add_query_arg(
		array( 'page' => 'kindi-settings', 'tab' => 'texts', 'kindi_webhook_test' => $status ),
		admin_url( 'admin.php' )
	) );
	exit;
}
add_action( 'admin_post_kindi_webhook_test', 'kindi_webhook_test' );

/**
 * Status + test-button + recent-log HTML for the settings note.
 *
 * @return string
 */
function kindi_webhook_status_html(): string {
	$active = kindi_webhook_active();
	$out    = '<p style="margin:.2em 0 1em">';
	$out   .= $active
		? '<strong style="color:#15803d">● ' . esc_html__( 'פעיל', 'kindi' ) . '</strong> — ' . esc_html__( 'הזמנות שהושלמו נשלחות אוטומטית.', 'kindi' )
		: '<strong style="color:#b91c1c">● ' . esc_html__( 'כבוי', 'kindi' ) . '</strong> — ' . esc_html__( 'לא נשלח דבר. הפעילו למעלה ושמרו כדי להתחיל.', 'kindi' );
	$out   .= '</p>';

	// Test-send trigger. A link (not a form) because this HTML renders inside the
	// settings page's own <form> — a nested form would be invalid markup.
	$test_url = wp_nonce_url( admin_url( 'admin-post.php?action=kindi_webhook_test' ), 'kindi_webhook_test' );
	if ( $active ) {
		$out .= '<p style="margin:0 0 1em"><a class="button" href="' . esc_url( $test_url ) . '">' . esc_html__( 'שליחת בדיקה עכשיו', 'kindi' ) . '</a></p>';
	} else {
		$out .= '<p style="margin:0 0 1em"><span class="button disabled" aria-disabled="true">' . esc_html__( 'שליחת בדיקה עכשיו', 'kindi' ) . '</span></p>';
	}

	$log = get_option( KINDI_WEBHOOK_LOG );
	if ( is_array( $log ) && $log ) {
		$out .= '<table class="widefat striped" style="max-width:620px"><thead><tr>'
			. '<th>' . esc_html__( 'זמן', 'kindi' ) . '</th><th>' . esc_html__( 'הזמנה', 'kindi' ) . '</th><th>' . esc_html__( 'תוצאה', 'kindi' ) . '</th></tr></thead><tbody>';
		foreach ( $log as $row ) {
			$code = (int) ( $row['code'] ?? 0 );
			$ok   = $code >= 200 && $code < 300;
			$res  = $ok
				? '<span style="color:#15803d">' . esc_html( sprintf( /* translators: %d: HTTP code. */ __( 'נשלח (%d)', 'kindi' ), $code ) ) . '</span>'
				: '<span style="color:#b91c1c">' . esc_html( 0 === $code ? ( (string) ( $row['err'] ?? __( 'שגיאה', 'kindi' ) ) ) : sprintf( /* translators: %d: HTTP code. */ __( 'נכשל (%d)', 'kindi' ), $code ) ) . '</span>';
			$out .= '<tr><td>' . esc_html( (string) ( $row['time'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['label'] ?? '' ) ) . '</td><td>' . $res . '</td></tr>';
		}
		$out .= '</tbody></table>';
	}

	return $out;
}

/**
 * Flash notice after a test send.
 *
 * @return void
 */
function kindi_webhook_test_notice(): void {
	if ( empty( $_GET['kindi_webhook_test'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$status = sanitize_key( wp_unslash( $_GET['kindi_webhook_test'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$map    = array(
		'ok'       => array( 'success', __( 'שליחת הבדיקה בוצעה בהצלחה — ראו את הלוג למטה.', 'kindi' ) ),
		'fail'     => array( 'error', __( 'שליחת הבדיקה נכשלה — בדקו את כתובת ה-Webhook ואת הלוג למטה.', 'kindi' ) ),
		'disabled' => array( 'warning', __( 'ה-Webhook כבוי או שהכתובת אינה תקינה — הפעילו ושמרו קודם.', 'kindi' ) ),
	);
	if ( isset( $map[ $status ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $status ][0] ), esc_html( $map[ $status ][1] ) );
	}
}
add_action( 'admin_notices', 'kindi_webhook_test_notice' );
