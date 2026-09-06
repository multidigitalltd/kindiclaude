<?php
/**
 * Custom embed code — two panel fields ("קוד בהידר" / "קוד בפוטר") printed
 * verbatim at wp_head / wp_footer for external scripts (analytics, chat, etc.).
 *
 * Deliberately minimal: no wrappers, no conditions, no extra markup — the code
 * is stored as-is and echoed as-is. Saving raw script tags requires the
 * `unfiltered_html` capability (admins); anyone else's input is reduced to safe
 * HTML by the sanitiser in admin-settings.php ('code' field type).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Add the embed-code section to the panel's "טקסטים והגדרות" tab.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_custom_code_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections'] ) ) {
		$tabs['texts']['sections']['הטמעת קוד חיצוני'] = array(
			'_code_note'  => array( 'type' => 'note', 'label' => '', 'help' => 'להדבקת קטעי קוד של שירותים חיצוניים (מעקב, צ׳אט וכו׳). הקוד מודפס כמו שהוא — להדביק רק קוד ממקור מהימן. לשמירה על מהירות האתר: עדיף להטמיע בפוטר, ולסקריפטים להוסיף defer או async.' ),
			'head_code'   => array( 'type' => 'code', 'label' => 'קוד בהידר (לפני </head>)' ),
			'footer_code' => array( 'type' => 'code', 'label' => 'קוד בפוטר (לפני </body>)' ),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_custom_code_settings' );

/**
 * Print one stored code field verbatim (nothing when empty).
 *
 * @param string $key Option key.
 * @return void
 */
function kindi_custom_code_print( string $key ): void {
	$code = trim( (string) kindi_opt( $key ) );
	if ( '' !== $code ) {
		echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- verbatim embed field; raw script tags are only saveable by unfiltered_html admins.
	}
}

/**
 * Header slot.
 *
 * @return void
 */
function kindi_custom_code_head(): void {
	kindi_custom_code_print( 'head_code' );
}
add_action( 'wp_head', 'kindi_custom_code_head', 99 );

/**
 * Footer slot.
 *
 * @return void
 */
function kindi_custom_code_footer(): void {
	kindi_custom_code_print( 'footer_code' );
}
add_action( 'wp_footer', 'kindi_custom_code_footer', 99 );
