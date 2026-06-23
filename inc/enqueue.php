<?php
/**
 * Conditional asset loading.
 *
 * Per the Multi Digital standard: never load CSS/JS that the current view does
 * not need, minimise HTTP requests, defer JS, and preload only above-the-fold
 * fonts to protect LCP.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Resolve an asset's filesystem mtime for cache-busting (falls back to theme version).
 *
 * @param string $relative Path relative to the theme root.
 * @return string
 */
function kindi_asset_version( string $relative ): string {
	$path = KINDI_DIR . ltrim( $relative, '/' );

	return is_readable( $path ) ? (string) filemtime( $path ) : KINDI_VERSION;
}

/**
 * Whether the current view renders the decorative hero / motion (home or shop).
 *
 * is_shop() only exists when WooCommerce is active, so it must be guarded —
 * otherwise ordinary pages fatal when the plugin is inactive.
 *
 * @return bool
 */
function kindi_is_motion_view(): bool {
	return is_front_page() || ( function_exists( 'is_shop' ) && is_shop() );
}

/**
 * Enqueue front-end styles. Animation CSS loads only where motion is used.
 *
 * @return void
 */
function kindi_enqueue_styles(): void {
	// Shared, tiny base layer (custom utilities not expressible in theme.json).
	wp_enqueue_style(
		'kindi-base',
		KINDI_URI . 'assets/css/base.css',
		array(),
		kindi_asset_version( 'assets/css/base.css' )
	);

	// Component styles for theme parts/patterns (header/footer on every view).
	wp_enqueue_style(
		'kindi-components',
		KINDI_URI . 'assets/css/components.css',
		array( 'kindi-base' ),
		kindi_asset_version( 'assets/css/components.css' )
	);

	// Animations only on views that render decorative motion (home / shop).
	if ( kindi_is_motion_view() ) {
		wp_enqueue_style(
			'kindi-animations',
			KINDI_URI . 'assets/css/animations.css',
			array(),
			kindi_asset_version( 'assets/css/animations.css' )
		);
	}

	// Homepage section styles — front page only.
	if ( is_front_page() ) {
		wp_enqueue_style(
			'kindi-sections',
			KINDI_URI . 'assets/css/sections.css',
			array( 'kindi-components' ),
			kindi_asset_version( 'assets/css/sections.css' )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kindi_enqueue_styles' );

/**
 * Enqueue the (deferred) interactions script — header drawer + a11y toggle.
 *
 * @return void
 */
function kindi_enqueue_scripts(): void {
	wp_enqueue_script(
		'kindi-interactions',
		KINDI_URI . 'assets/js/interactions.js',
		array(),
		kindi_asset_version( 'assets/js/interactions.js' ),
		true
	);

	// Live header cart count (WooCommerce core script, no jQuery UI bloat).
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}
add_action( 'wp_enqueue_scripts', 'kindi_enqueue_scripts' );

/**
 * Add defer to theme scripts; never block rendering.
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Registered handle.
 * @return string
 */
function kindi_defer_scripts( string $tag, string $handle ): string {
	$deferred = array( 'kindi-header', 'kindi-interactions' );

	if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_defer_scripts', 10, 2 );
