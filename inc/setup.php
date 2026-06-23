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

	// Serve modern formats; full sizes generated on demand keep the media library lean.
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'kindi_setup' );

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
