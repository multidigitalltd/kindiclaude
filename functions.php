<?php
/**
 * Kindi theme bootstrap.
 *
 * Loads modular includes. Each module is small, focused and self-registering,
 * per the Multi Digital development standard (clean code, separation of concerns).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'KINDI_VERSION' ) ) {
	define( 'KINDI_VERSION', wp_get_theme()->get( 'Version' ) ?: '0.1.0' );
}

if ( ! defined( 'KINDI_DIR' ) ) {
	define( 'KINDI_DIR', trailingslashit( get_template_directory() ) );
}

if ( ! defined( 'KINDI_URI' ) ) {
	define( 'KINDI_URI', trailingslashit( get_template_directory_uri() ) );
}

/**
 * Load a theme include once, only if it exists.
 *
 * @param string $relative Path relative to the inc/ directory.
 * @return void
 */
function kindi_require( string $relative ): void {
	$path = KINDI_DIR . 'inc/' . ltrim( $relative, '/' );

	if ( is_readable( $path ) ) {
		require_once $path;
	}
}

kindi_require( 'options.php' );
kindi_require( 'icons.php' );
kindi_require( 'template-tags.php' );
kindi_require( 'fonts.php' );
kindi_require( 'dynamic.php' );
kindi_require( 'setup.php' );

if ( is_admin() ) {
	kindi_require( 'admin-settings.php' );
}
kindi_require( 'enqueue.php' );
kindi_require( 'performance.php' );
kindi_require( 'security.php' );

if ( class_exists( 'WooCommerce' ) ) {
	kindi_require( 'woocommerce.php' );
}
