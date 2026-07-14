<?php
/**
 * Newsletter — AJAX subscribe endpoint that stores emails and fires an action
 * for integrations (Mailchimp/ESP) via the kindi_newsletter_subscribe hook.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the subscribe REST route.
 *
 * @return void
 */
function kindi_register_subscribe_route(): void {
	register_rest_route(
		'kindi/v1',
		'/subscribe',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'kindi_rest_subscribe',
			'permission_callback' => '__return_true',
			'args'                => array(
				'email' => array( 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_email' ),
				'name'  => array( 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'kindi_register_subscribe_route' );

/**
 * Store the email and fire the integration hook.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function kindi_rest_subscribe( WP_REST_Request $request ): WP_REST_Response {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_REST_Response( array( 'message' => 'אירעה שגיאה. נסו שוב.' ), 403 );
	}

	// Rate limit: 5 per IP / 10 min (same pattern as the waitlist) — stops
	// automated nonce-valid submissions from bloating the subscribers option.
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key  = 'kindi_nl_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 5 ) {
		return new WP_REST_Response( array( 'message' => 'יותר מדי בקשות. נסו שוב בעוד מספר דקות.' ), 429 );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$email = sanitize_email( (string) $request->get_param( 'email' ) );
	if ( ! is_email( $email ) ) {
		return new WP_REST_Response( array( 'message' => 'כתובת אימייל לא תקינה.' ), 400 );
	}

	// Marketing consent is required (privacy-policy checkbox on the form) —
	// enforced server-side too, not only in the UI.
	if ( '1' !== (string) $request->get_param( 'consent' ) ) {
		return new WP_REST_Response( array( 'message' => 'כדי להירשם יש לאשר את קבלת הדיוור.' ), 400 );
	}

	$list = get_option( 'kindi_subscribers', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	if ( ! in_array( $email, $list, true ) ) {
		$list[] = $email;
		update_option( 'kindi_subscribers', $list, false );
	}

	$name = mb_substr( sanitize_text_field( (string) $request->get_param( 'name' ) ), 0, 80 );

	/**
	 * Integrate with an external ESP / mailing list.
	 *
	 * @param string $email Subscriber email.
	 * @param string $name  Subscriber first name ('' when not given).
	 */
	do_action( 'kindi_newsletter_subscribe', $email, $name );

	return new WP_REST_Response( array( 'message' => 'תודה! קוד ההנחה בדרך אליכם למייל.' ), 200 );
}

/**
 * Export subscribers as CSV from the admin (admin-post action).
 *
 * @return void
 */
function kindi_export_subscribers(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	check_admin_referer( 'kindi_export_subscribers' );

	$list = (array) get_option( 'kindi_subscribers', array() );
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=kindi-subscribers.csv' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'email' ) );
	foreach ( $list as $email ) {
		fputcsv( $out, array( $email ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_kindi_export_subscribers', 'kindi_export_subscribers' );

/* ================================ Flashy ================================ */

/**
 * Push a new subscriber to Flashy as a contact (create-or-update), optionally
 * straight into a list (marketing-eligible). The response is read and written
 * to a short admin log (last 10 pushes) so the panel shows whether each
 * signup really reached Flashy; the local subscribers option remains the
 * backup source of truth either way.
 *
 * @param string $email Subscriber email.
 * @param string $name  Subscriber first name (optional).
 * @return void
 */
function kindi_newsletter_flashy( string $email, string $name = '' ): void {
	$key = trim( (string) kindi_opt( 'flashy_key' ) );
	if ( '' === $key ) {
		return;
	}

	$contact = array( 'email' => $email );
	if ( '' !== $name ) {
		$contact['first_name'] = $name;
	}
	$list_id = trim( (string) kindi_opt( 'flashy_list' ) );
	if ( '' !== $list_id ) {
		$contact['lists'] = array( $list_id => true );
	}

	$resp = wp_remote_post(
		'https://api.flashy.app/contact',
		array(
			'timeout' => 4,
			'headers' => array(
				'Content-Type' => 'application/json',
				'x-api-key'    => $key,
			),
			'body'    => wp_json_encode(
				array(
					'primary_key' => 'email',
					'overwrite'   => true,
					'contact'     => $contact,
				)
			),
		)
	);

	$body = ! is_wp_error( $resp ) ? json_decode( wp_remote_retrieve_body( $resp ), true ) : null;
	$ok   = ! is_wp_error( $resp ) && ! empty( $body['success'] );
	$info = $ok
		? ( '' !== $list_id ? sprintf( 'נוסף/עודכן וצורף לרשימה %s', $list_id ) : 'נוסף/עודכן (ללא רשימה)' )
		: ( is_wp_error( $resp ) ? $resp->get_error_message() : 'קוד ' . wp_remote_retrieve_response_code( $resp ) );

	$log = get_option( 'kindi_flashy_log' );
	$log = is_array( $log ) ? $log : array();
	array_unshift(
		$log,
		array(
			'time'  => current_time( 'mysql' ),
			'email' => $email,
			'ok'    => $ok ? 1 : 0,
			'info'  => $info,
		)
	);
	update_option( 'kindi_flashy_log', array_slice( $log, 0, 5 ), false );
}
add_action( 'kindi_newsletter_subscribe', 'kindi_newsletter_flashy', 10, 2 );

/**
 * Connection status + account lists for the settings panel (admin only,
 * cached 5 minutes per key so the panel doesn't hit the API on every load).
 *
 * @return string
 */
function kindi_flashy_status_html(): string {
	$key = trim( (string) kindi_opt( 'flashy_key' ) );
	if ( '' === $key ) {
		return '<p class="description">' . esc_html__( 'הזינו מפתח API ושמרו — הסטטוס והרשימות מהחשבון יוצגו כאן.', 'kindi' ) . '</p>';
	}

	$cache = 'kindi_flashy_status_' . md5( $key );
	$html  = get_transient( $cache );
	if ( is_string( $html ) && '' !== $html ) {
		return $html . kindi_flashy_log_html();
	}

	$args = array( 'timeout' => 8, 'headers' => array( 'x-api-key' => $key ) );
	$resp = wp_remote_get( 'https://api.flashy.app/account', $args );
	$body = ! is_wp_error( $resp ) ? json_decode( wp_remote_retrieve_body( $resp ), true ) : null;

	if ( is_wp_error( $resp ) || 200 !== wp_remote_retrieve_response_code( $resp ) || empty( $body['success'] ) ) {
		$html = '<p style="color:#b91c1c;font-weight:600">● ' . esc_html__( 'החיבור נכשל — בדקו שהמפתח נכון ושהחשבון מאומת ב-Flashy.', 'kindi' ) . '</p>';
	} else {
		$name = (string) ( $body['data']['name'] ?? '' );
		$html = '<p style="color:#15803d;font-weight:600">● ' . esc_html( sprintf( /* translators: %s: Flashy account name. */ __( 'מחובר לחשבון Flashy: %s', 'kindi' ), $name ) ) . '</p>';

		$lists_resp = wp_remote_get( 'https://api.flashy.app/lists', $args );
		$lists_body = ! is_wp_error( $lists_resp ) ? json_decode( wp_remote_retrieve_body( $lists_resp ), true ) : null;
		if ( ! empty( $lists_body['data'] ) && is_array( $lists_body['data'] ) ) {
			$html .= '<p class="description">' . esc_html__( 'הרשימות בחשבון — העתיקו את המזהה של רשימת הניוזלטר לשדה למעלה:', 'kindi' ) . '</p><ul style="margin:0.25em 1em">';
			foreach ( $lists_body['data'] as $flist ) {
				$html .= '<li><code>' . esc_html( (string) ( $flist['id'] ?? '' ) ) . '</code> — ' . esc_html( (string) ( $flist['title'] ?? '' ) ) . '</li>';
			}
			$html .= '</ul>';
		}
	}

	set_transient( $cache, $html, 5 * MINUTE_IN_SECONDS );
	return $html . kindi_flashy_log_html();
}

/**
 * The last 5 newsletter→Flashy pushes, so the admin can verify each signup
 * actually reached the list. Rendered fresh (outside the status cache).
 *
 * @return string
 */
function kindi_flashy_log_html(): string {
	$log = get_option( 'kindi_flashy_log' );
	if ( ! is_array( $log ) || ! $log ) {
		return '';
	}
	$html = '<p style="margin:1em 0 .3em"><strong>' . esc_html__( 'צירופים אחרונים ל-Flashy:', 'kindi' ) . '</strong></p>';
	$html .= '<table class="widefat striped" style="max-width:640px"><thead><tr><th>' . esc_html__( 'זמן', 'kindi' ) . '</th><th>' . esc_html__( 'אימייל', 'kindi' ) . '</th><th>' . esc_html__( 'תוצאה', 'kindi' ) . '</th></tr></thead><tbody>';
	foreach ( $log as $row ) {
		$ok    = ! empty( $row['ok'] );
		$html .= '<tr><td>' . esc_html( (string) ( $row['time'] ?? '' ) ) . '</td><td style="direction:ltr;text-align:left">' . esc_html( (string) ( $row['email'] ?? '' ) ) . '</td><td><span style="color:' . ( $ok ? '#15803d' : '#b91c1c' ) . ';font-weight:600">' . esc_html( (string) ( $row['info'] ?? '' ) ) . '</span></td></tr>';
	}
	return $html . '</tbody></table>';
}
