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

	$list = get_option( 'kindi_subscribers', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	if ( ! in_array( $email, $list, true ) ) {
		$list[] = $email;
		update_option( 'kindi_subscribers', $list, false );
	}

	/**
	 * Integrate with an external ESP / mailing list.
	 *
	 * @param string $email Subscriber email.
	 */
	do_action( 'kindi_newsletter_subscribe', $email );

	return new WP_REST_Response( array( 'message' => 'תודה! קוד ההנחה בדרך אליכם למייל.' ), 200 );
}

/**
 * Push a new subscriber to the configured external webhook (Zapier / Make /
 * ActiveTrail / smoove / n8n …). Non-blocking so it never delays the visitor;
 * the local list remains the source-of-truth backup. The email field name and
 * an optional shared-secret header are configurable for the target system.
 *
 * @param string $email Subscriber email.
 * @return void
 */
function kindi_newsletter_webhook( string $email ): void {
	$url = trim( (string) kindi_opt( 'newsletter_webhook' ) );
	if ( '' === $url || ! wp_http_validate_url( $url ) ) {
		return;
	}

	$field = (string) kindi_opt( 'newsletter_field', 'email' );
	$field = '' !== $field ? sanitize_key( $field ) : 'email';

	$body = array(
		$field    => $email,
		'source'  => 'kindi-newsletter',
		'site'    => home_url( '/' ),
	);

	$headers = array( 'Content-Type' => 'application/json' );
	$secret  = trim( (string) kindi_opt( 'newsletter_secret' ) );
	if ( '' !== $secret ) {
		$headers['X-Kindi-Secret'] = $secret;
	}

	wp_remote_post(
		$url,
		array(
			'timeout'  => 5,
			'blocking' => false,
			'headers'  => $headers,
			'body'     => wp_json_encode( $body ),
		)
	);
}
add_action( 'kindi_newsletter_subscribe', 'kindi_newsletter_webhook' );

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
 * straight into a list (marketing-eligible). Non-blocking — never delays the
 * visitor; the local subscribers option remains the backup source of truth.
 *
 * @param string $email Subscriber email.
 * @return void
 */
function kindi_newsletter_flashy( string $email ): void {
	$key = trim( (string) kindi_opt( 'flashy_key' ) );
	if ( '' === $key ) {
		return;
	}

	$contact = array( 'email' => $email );
	$list_id = trim( (string) kindi_opt( 'flashy_list' ) );
	if ( '' !== $list_id ) {
		$contact['lists'] = array( $list_id => true );
	}

	wp_remote_post(
		'https://api.flashy.app/contact',
		array(
			'timeout'  => 5,
			'blocking' => false,
			'headers'  => array(
				'Content-Type' => 'application/json',
				'x-api-key'    => $key,
			),
			'body'     => wp_json_encode(
				array(
					'primary_key' => 'email',
					'overwrite'   => true,
					'contact'     => $contact,
				)
			),
		)
	);
}
add_action( 'kindi_newsletter_subscribe', 'kindi_newsletter_flashy' );

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
		return $html;
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
	return $html;
}
