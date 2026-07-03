<?php
/**
 * LiteSpeed Cache + persistent object cache (Redis / Memcached) compatibility.
 *
 * The theme is page-cache friendly out of the box: the dynamic store views
 * (cart, checkout, my-account) are excluded by LiteSpeed's built-in WooCommerce
 * integration, the header cart count refreshes through `wc-cart-fragments`, and
 * every piece of computed data the theme caches uses Transients — which are
 * stored in the persistent object cache automatically when Redis/Memcached is
 * present, and fall back to the options table otherwise.
 *
 * Cart, checkout and my-account are never cached, so their nonces are always
 * fresh. The hazard is the nonces that ride along on *cacheable* pages via the
 * header and product templates: the live search box (wp_rest) and the mini-cart
 * quantity control (kindi_cart_qty) sit in the header on every page, and the
 * back-in-stock waitlist (kindi_waitlist) sits on product pages. On a page
 * cached beyond the 12–24h nonce window those baked-in nonces go stale and the
 * AJAX starts returning 403s for guests. We register the theme's nonce actions
 * with LiteSpeed so it serves them via ESI (fresh per request). Every call here
 * is a no-op when LiteSpeed is not installed.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Nonce actions the theme prints into front-end JS / forms on cacheable pages.
 *
 * @return array<int,string>
 */
function kindi_cache_nonce_actions(): array {
	return array(
		'wp_rest',          // REST search / products / bundle / pixel.
		'kindi_cart_qty',   // mini-cart quantity AJAX (store.js).
		'apply-coupon',     // summary coupon apply (store.js).
		'remove-coupon',    // summary coupon remove (store.js).
		'kindi_save_cart',  // saved-cart form.
		'kindi_waitlist',   // back-in-stock waitlist form.
		'kindi_cancel',     // transaction-cancellation form.
		'kindi_contact',    // contact form.
	);
}

/**
 * Register the theme's nonces with LiteSpeed's ESI so they stay fresh on cached
 * pages. `do_action( 'litespeed_nonce', ... )` is LiteSpeed's documented API and
 * is silently ignored when the plugin is inactive.
 *
 * @return void
 */
function kindi_litespeed_fresh_nonces(): void {
	if ( ! defined( 'LSCWP_V' ) ) {
		return;
	}
	foreach ( kindi_cache_nonce_actions() as $action ) {
		do_action( 'litespeed_nonce', $action );
	}
}
add_action( 'init', 'kindi_litespeed_fresh_nonces', 20 );

/**
 * When LiteSpeed is active it can generate its own critical CSS and load the
 * stylesheets asynchronously. Let it own that pipeline so the two approaches
 * don't fight — the theme's inline critical CSS is disabled while LiteSpeed's
 * CSS optimisation is available. Override with the `kindi_use_critical_css`
 * filter if LiteSpeed's CSS optimisation is turned off.
 *
 * @param bool $enabled Whether the theme's own critical CSS runs.
 * @return bool
 */
function kindi_litespeed_defer_critical_css( bool $enabled ): bool {
	return defined( 'LSCWP_V' ) ? false : $enabled;
}
add_filter( 'kindi_use_critical_css', 'kindi_litespeed_defer_critical_css' );
