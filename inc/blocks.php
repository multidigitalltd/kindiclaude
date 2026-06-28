<?php
/**
 * Dynamic theme blocks.
 *
 * The homepage sections that depend on WooCommerce (category grid, hot products)
 * must be generated at RENDER time — not when block patterns are registered on
 * `init`, because at that point WooCommerce may not have registered its
 * `product_cat` taxonomy or its `[products]` shortcode yet, which would bake an
 * empty result into the pattern. Server-rendered blocks (render_callback) run
 * during template output, when the store is fully loaded, so the markup is
 * always live and correct.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the theme's dynamic blocks.
 *
 * @return void
 */
function kindi_register_blocks(): void {
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}

	register_block_type(
		'kindi/categories',
		array(
			'render_callback' => 'kindi_block_categories',
		)
	);

	register_block_type(
		'kindi/featured-products',
		array(
			'render_callback' => 'kindi_block_featured_products',
		)
	);
}
add_action( 'init', 'kindi_register_blocks' );

/**
 * Render the live category grid section.
 *
 * @return string
 */
function kindi_block_categories(): string {
	return function_exists( 'kindi_categories_shortcode' ) ? kindi_categories_shortcode() : '';
}

/**
 * Render the "hot products" section (heading + live WooCommerce grid).
 *
 * @return string
 */
function kindi_block_featured_products(): string {
	$head = function_exists( 'kindi_section_head' )
		? kindi_section_head(
			array(
				'eyebrow'   => 'נבחרים בשבילכם',
				'title'     => 'המוצרים',
				'highlight' => 'החמים שלנו',
			)
		)
		: '';

	$grid = function_exists( 'kindi_hot_products_shortcode' ) ? kindi_hot_products_shortcode() : '';

	return '<section class="kindi-section">' . $head . $grid . '</section>';
}
