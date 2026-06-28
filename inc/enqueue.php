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
 * Whether the current request is a WooCommerce store view.
 *
 * @return bool
 */
function kindi_is_wc_view(): bool {
	return ( function_exists( 'is_woocommerce' ) && is_woocommerce() )
		|| ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );
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

	// Does the current page embed the theme's dynamic shortcodes? Those need the
	// section/store styling even off the front page.
	$has_section_shortcode = false;
	if ( ! is_front_page() && is_singular() ) {
		$post = get_post();
		$body = $post ? (string) $post->post_content : '';
		foreach ( array( 'kindi_categories', 'kindi_hot_products', 'kindi_gift_finder', 'kindi_recently_viewed', 'kindi_wishlist' ) as $sc ) {
			if ( $body && has_shortcode( $body, $sc ) ) {
				$has_section_shortcode = true;
				break;
			}
		}
	}

	// Homepage section styles — front page, or any page using a section shortcode.
	if ( is_front_page() || $has_section_shortcode ) {
		wp_enqueue_style(
			'kindi-sections',
			KINDI_URI . 'assets/css/sections.css',
			array( 'kindi-components' ),
			kindi_asset_version( 'assets/css/sections.css' )
		);
	}

	// WooCommerce store styling — store views, the front page, or shortcode pages.
	if ( class_exists( 'WooCommerce' ) && ( is_front_page() || kindi_is_wc_view() || $has_section_shortcode ) ) {
		wp_enqueue_style(
			'kindi-woocommerce',
			KINDI_URI . 'assets/css/woocommerce.css',
			array( 'kindi-components' ),
			kindi_asset_version( 'assets/css/woocommerce.css' )
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

		wp_enqueue_script(
			'kindi-search',
			KINDI_URI . 'assets/js/search.js',
			array(),
			kindi_asset_version( 'assets/js/search.js' ),
			true
		);
		wp_localize_script(
			'kindi-search',
			'kindiSearch',
			array(
				'url'       => esc_url_raw( rest_url( 'kindi/v1/search' ) ),
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'allText'   => __( 'כל התוצאות עבור', 'kindi' ),
				'noResults' => __( 'לא נמצאו תוצאות', 'kindi' ),
			)
		);
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
	$deferred = array( 'kindi-header', 'kindi-interactions', 'kindi-search', 'kindi-a11y' );

	if ( in_array( $handle, $deferred, true ) && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}

	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_defer_scripts', 10, 2 );
