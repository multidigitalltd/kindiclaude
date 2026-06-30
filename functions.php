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
kindi_require( 'blocks.php' );
kindi_require( 'search.php' );
kindi_require( 'nav.php' );
kindi_require( 'store.php' );
kindi_require( 'reviews.php' );
kindi_require( 'newsletter.php' );
kindi_require( 'accessibility.php' );
kindi_require( 'accessibility-statement.php' );
kindi_require( 'cookie-consent.php' );
kindi_require( 'schema.php' );
kindi_require( 'pixel.php' );
kindi_require( 'critical-css.php' );
kindi_require( 'acf-bridge.php' );
kindi_require( 'emails.php' );
kindi_require( 'email-branding.php' );
kindi_require( 'setup.php' );

if ( is_admin() ) {
	kindi_require( 'admin-settings.php' );
	kindi_require( 'nav-menu-fields.php' );
}
kindi_require( 'enqueue.php' );
kindi_require( 'performance.php' );
kindi_require( 'security.php' );

if ( class_exists( 'WooCommerce' ) ) {
	kindi_require( 'woocommerce.php' );
	kindi_require( 'product-card.php' );
	kindi_require( 'product-meta.php' );
	kindi_require( 'single-product.php' );
	kindi_require( 'variation-swatches.php' );
	kindi_require( 'waitlist.php' );
	kindi_require( 'shipping-bar.php' );
	kindi_require( 'recently-viewed.php' );
	kindi_require( 'filters.php' );
	kindi_require( 'checkout.php' );
	kindi_require( 'checkout-fields.php' );
	kindi_require( 'bundle.php' );
	kindi_require( 'merchant-feed.php' );
	kindi_require( 'sitemap.php' );
	kindi_require( 'gift-finder.php' );
	kindi_require( 'sticky-cart.php' );
	kindi_require( 'whatsapp.php' );
	kindi_require( 'saved-cart.php' );
	kindi_require( 'archive-desc.php' );
}
