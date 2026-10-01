<?php
/**
 * About page — load its section styles only on the About page.
 *
 * The page design lives in patterns/about.php + templates/page-about.html (which
 * WordPress applies to a Page whose slug is "about"/"אודות"). Those sections
 * reuse the shared section styling (.kindi-eyebrow / .kindi-value / hot-products),
 * so this loads sections.css + woocommerce.css + animations.css + about.css there
 * — additively, without touching the global enqueue logic.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is the current request the About page?
 *
 * @return bool
 */
function kindi_is_about_view(): bool {
	if ( ! is_page() ) {
		return false;
	}
	// The "אודות (Kindi)" template is assigned to the page…
	if ( 'page-about' === get_page_template_slug( get_queried_object_id() ) ) {
		return true;
	}
	// …or the page uses the one slug page-about.html auto-applies to. Other
	// slugs without the template assigned would load ~30KB of section CSS for
	// nothing, so the fallback stays narrow.
	return is_page( 'about' );
}

/**
 * Enqueue the About page's section styles.
 *
 * @return void
 */
function kindi_about_assets(): void {
	if ( ! kindi_is_about_view() ) {
		return;
	}

	$dep = array( 'kindi-components' );

	wp_enqueue_style( 'kindi-sections', KINDI_URI . 'assets/css/sections.css', $dep, kindi_asset_version( 'assets/css/sections.css' ) );

	// The hot-products block reuses the WooCommerce card styling.
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'kindi-woocommerce', KINDI_URI . 'assets/css/woocommerce.css', $dep, kindi_asset_version( 'assets/css/woocommerce.css' ) );
	}

	// Mascot sway (respects prefers-reduced-motion inside the file).
	wp_enqueue_style( 'kindi-animations', KINDI_URI . 'assets/css/animations.css', array(), kindi_asset_version( 'assets/css/animations.css' ) );

	// Deliberately render-blocking (unlike sections/woocommerce, which the
	// critical-css module swaps to preload→onload): about.css styles the
	// above-the-fold hero and is ~2KB gzipped — blocking avoids a hero FOUC.
	wp_enqueue_style( 'kindi-about', KINDI_URI . 'assets/css/about.css', array( 'kindi-sections' ), kindi_asset_version( 'assets/css/about.css' ) );
}
add_action( 'wp_enqueue_scripts', 'kindi_about_assets', 20 );
