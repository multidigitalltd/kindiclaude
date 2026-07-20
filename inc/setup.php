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

/**
 * Seed the physical front page with the homepage sections (one-time).
 *
 * The front-page template renders the page's own content (post-content), so
 * the physical "דף הבית" page is the single source of truth — what Google
 * indexes, what SEO plugins analyse and what the admin edits are all the same
 * document. This seeder writes the canonical section stack into that page
 * once; any text already on the page is kept BELOW the sections (the SEO-text
 * slot). Skipped when the page already contains the sections.
 *
 * @return void
 */
function kindi_seed_front_page(): void {
	if ( ! is_admin() || get_option( 'kindi_front_seeded' ) ) {
		return;
	}
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return;
	}
	$page_id = (int) get_option( 'page_on_front' );
	$page    = $page_id > 0 ? get_post( $page_id ) : null;
	if ( ! $page instanceof WP_Post ) {
		return;
	}

	if ( false !== strpos( (string) $page->post_content, 'kindi/hero' ) ) {
		update_option( 'kindi_front_seeded', 1, false ); // Already section-based.
		return;
	}

	$sections = implode(
		"\n\n",
		array(
			'<!-- wp:pattern {"slug":"kindi/hero"} /-->',
			'<!-- wp:pattern {"slug":"kindi/usp-strip"} /-->',
			'<!-- wp:kindi/categories /-->',
			'<!-- wp:pattern {"slug":"kindi/promo-banners"} /-->',
			'<!-- wp:kindi/featured-products /-->',
			'<!-- wp:pattern {"slug":"kindi/age-rail"} /-->',
			'<!-- wp:pattern {"slug":"kindi/brands"} /-->',
			'<!-- wp:pattern {"slug":"kindi/kindy-zone"} /-->',
			'<!-- wp:pattern {"slug":"kindi/testimonials"} /-->',
			'<!-- wp:pattern {"slug":"kindi/values"} /-->',
			'<!-- wp:pattern {"slug":"kindi/store-info"} /-->',
		)
	);

	$old = trim( (string) $page->post_content );
	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => $sections . ( '' !== $old ? "\n\n" . $old : '' ),
		)
	);
	update_option( 'kindi_front_seeded', 1, false );
}
add_action( 'admin_init', 'kindi_seed_front_page' );
