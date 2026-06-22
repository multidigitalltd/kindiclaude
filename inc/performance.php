<?php
/**
 * Performance hardening — trim wp_head bloat and unneeded global requests.
 *
 * Caching plugins (LiteSpeed, WP Rocket, Redis/Object Cache) remain fully
 * compatible; this only removes output the theme never relies on.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Remove emoji detection script/styles (extra requests + render cost).
 *
 * @return void
 */
function kindi_disable_emojis(): void {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'kindi_disable_emojis' );

/**
 * Clean non-essential tags from <head>.
 *
 * @return void
 */
function kindi_clean_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
add_action( 'init', 'kindi_clean_head' );

/**
 * Drop the global block-library inline duotone/SVG filters when unused, and
 * dequeue classic-theme styles that the block theme does not need.
 *
 * @return void
 */
function kindi_dequeue_unused(): void {
	// Classic-theme compatibility sheet is unnecessary in a block theme.
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'kindi_dequeue_unused', 20 );

/**
 * Add resource hints for self-hosted fonts origin (same-origin → preconnect noop,
 * kept for CDN/Cloudflare setups where assets may be offloaded).
 *
 * @param array<int,mixed> $hints    Current hints.
 * @param string           $relation Relation type.
 * @return array<int,mixed>
 */
function kindi_resource_hints( array $hints, string $relation ): array {
	if ( 'preconnect' === $relation ) {
		// Placeholder for an assets/CDN origin if configured via filter.
		$origin = apply_filters( 'kindi_assets_origin', '' );
		if ( $origin ) {
			$hints[] = array(
				'href'        => esc_url( $origin ),
				'crossorigin' => 'anonymous',
			);
		}
	}

	return $hints;
}
add_filter( 'wp_resource_hints', 'kindi_resource_hints', 10, 2 );
