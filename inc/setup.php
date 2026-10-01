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
		// The WordPress Site Icon links point at /wp-content/uploads/, which this
		// site blocks in robots.txt — so Google can't fetch the favicon and shows
		// none. Drop those links; kindi_favicon() serves the same icon from the
		// (crawlable) theme folder instead. See kindi_favicon().
		remove_action( 'wp_head', 'wp_site_icon', 99 );
	}
);

/**
 * Print the favicon from the theme folder so it stays crawlable even when
 * /wp-content/uploads/ (where the WordPress Site Icon lives) is blocked in
 * robots.txt. Serves the same brand icon; the uploads-path core links are
 * removed above so Google only sees this fetchable one.
 *
 * @return void
 */
function kindi_favicon(): void {
	$icon = function_exists( 'kindi_img' ) ? (string) kindi_img( 'favicon.png' ) : '';
	if ( '' === $icon ) {
		return;
	}
	$icon = esc_url( $icon );
	echo '<link rel="icon" type="image/png" href="' . $icon . '" sizes="192x192">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . $icon . '">' . "\n";
}
add_action( 'wp_head', 'kindi_favicon', 2 );

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

/**
 * Clear stale Elementor page templates (one-time, self-terminating).
 *
 * Elementor stored its own page template (elementor_canvas /
 * elementor_header_footer) in _wp_page_template. After the plugin was removed
 * that template no longer exists, so the block editor's template lookup
 * (/wp/v2/templates/lookup?slug=page-…) never resolves and the editor hangs on
 * those pages. Resetting the value to "default" — and dropping the leftover
 * builder flag — lets the pages open normally. Runs once, then flags itself off.
 *
 * @return void
 */
function kindi_clear_elementor_templates(): void {
	if ( ! is_admin() || get_option( 'kindi_elementor_tpl_cleared' ) ) {
		return;
	}

	$ids = get_posts(
		array(
			'post_type'   => 'any',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
			'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_wp_page_template',
					'value'   => 'elementor',
					'compare' => 'LIKE',
				),
			),
		)
	);

	foreach ( $ids as $id ) {
		update_post_meta( (int) $id, '_wp_page_template', 'default' );
		delete_post_meta( (int) $id, '_elementor_edit_mode' );
	}

	update_option( 'kindi_elementor_tpl_cleared', 1, false );
}
add_action( 'admin_init', 'kindi_clear_elementor_templates' );
