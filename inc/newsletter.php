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

	return new WP_REST_Response( array( 'message' => '🎉 תודה! קוד ההנחה בדרך אליכם למייל.' ), 200 );
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
