<?php
/**
 * Theme setup — supports, image sizes, editor assets.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register theme support flags.
 *
 * @return void
 */
function kindi_setup(): void {
	// Translations — RTL/Hebrew first.
	load_theme_textdomain( 'kindi', KINDI_DIR . 'languages' );

	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
}
add_action( 'after_setup_theme', 'kindi_setup' );

/**
 * Guarantee a mobile viewport meta tag.
 *
 * Block themes rely on WordPress core (_block_template_viewport_meta_tag) to
 * print this, but some optimisation/cache plugins strip or reorder it — and
 * without it mobile browsers fall back to a ~980px desktop width, squashing the
 * layout into a corner with empty space beside it. We remove the core tag (if
 * present) and print our own at the very top of <head> so it is always there
 * exactly once.
 *
 * @return void
 */
function kindi_viewport_meta(): void {
	echo '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">' . "\n";
}
add_action( 'wp_head', 'kindi_viewport_meta', 1 );

// Drop core's block-theme viewport tag so exactly one is printed (ours).
add_action(
	'init',
	static function (): void {
		remove_action( 'wp_head', '_block_template_viewport_meta_tag', 0 );
	}
);

/**
 * Editor styles so the block editor mirrors the front end exactly.
 *
 * @return void
 */
function kindi_editor_assets(): void {
	add_editor_style( 'assets/css/base.css' );
	add_editor_style( 'assets/css/components.css' );
	add_editor_style( 'assets/css/sections.css' );
	add_editor_style( 'assets/css/animations.css' );
}
add_action( 'after_setup_theme', 'kindi_editor_assets' );

/**
 * Register the theme's block-pattern category.
 *
 * @return void
 */
function kindi_register_pattern_category(): void {
	register_block_pattern_category(
		'kindi',
		array( 'label' => __( 'קינדי', 'kindi' ) )
	);
}
add_action( 'init', 'kindi_register_pattern_category' );
