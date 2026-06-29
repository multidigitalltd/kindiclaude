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
			'api_version'     => 2,
			'title'           => __( 'Kindi: קטגוריות', 'kindi' ),
			'category'        => 'widgets',
			'icon'            => 'screenoptions',
			'render_callback' => 'kindi_block_categories',
		)
	);

	register_block_type(
		'kindi/featured-products',
		array(
			'api_version'     => 2,
			'title'           => __( 'Kindi: מוצרים חמים', 'kindi' ),
			'category'        => 'widgets',
			'icon'            => 'star-filled',
			'render_callback' => 'kindi_block_featured_products',
		)
	);
}
add_action( 'init', 'kindi_register_blocks' );

/**
 * Register the blocks on the editor side too (live server preview via
 * ServerSideRender) so the Site Editor recognises them instead of erroring on
 * an "unsupported" block.
 *
 * @return void
 */
function kindi_blocks_editor_assets(): void {
	wp_enqueue_script(
		'kindi-blocks-editor',
		KINDI_URI . 'assets/js/blocks-editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-server-side-render' ),
		kindi_asset_version( 'assets/js/blocks-editor.js' ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'kindi_blocks_editor_assets' );

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
	$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

	$head = function_exists( 'kindi_section_head' )
		? kindi_section_head(
			array(
				'eyebrow'   => 'מוצרים חמים',
				'title'     => 'הנבחרים',
				'highlight' => 'של קינדי',
				'cta'       => 'לכל המוצרים',
				'cta_url'   => $shop,
			)
		)
		: '';

	// Quick-filter tabs (switch the grid source via AJAX — see store.js).
	$tabs    = array(
		'best_selling' => 'הכי נמכרים',
		'date'         => 'חדשים',
		'sale'         => 'במבצע',
		'featured'     => 'מומלצי קינדי',
	);
	$count   = max( 1, (int) kindi_opt( 'home_products_count', 10 ) );
	$default = (string) kindi_opt( 'home_products_source', 'best_selling' );
	if ( ! isset( $tabs[ $default ] ) ) {
		$default = 'best_selling';
	}

	$tabs_html = '<div class="kindi-tabs" role="tablist" aria-label="סינון מוצרים" data-kindi-hot-tabs>';
	foreach ( $tabs as $src => $label ) {
		$on         = $src === $default;
		$tabs_html .= sprintf(
			'<button type="button" class="kindi-tab%1$s" role="tab" aria-selected="%2$s" data-source="%3$s">%4$s</button>',
			$on ? ' is-active' : '',
			$on ? 'true' : 'false',
			esc_attr( $src ),
			esc_html( $label )
		);
	}
	$tabs_html .= '</div>';

	$grid = '<div class="kindi-hot-grid" data-kindi-hot-grid aria-live="polite">'
		. ( function_exists( 'kindi_hot_products_html' ) ? kindi_hot_products_html( $default, $count ) : '' )
		. '</div>';

	return '<section class="kindi-section">' . $head . $tabs_html . $grid . '</section>';
}

/**
 * REST: return the hot-products grid HTML for a given source (drives the tabs).
 * Cached per source/count; flushed when products change.
 *
 * @return void
 */
function kindi_register_hot_route(): void {
	register_rest_route(
		'kindi/v1',
		'/hot',
		array(
			'methods'             => 'GET',
			'callback'            => 'kindi_rest_hot',
			'permission_callback' => '__return_true',
			'args'                => array(
				'source' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'kindi_register_hot_route' );

/**
 * REST callback for the hot-products tabs.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function kindi_rest_hot( WP_REST_Request $request ): WP_REST_Response {
	$source  = (string) $request->get_param( 'source' );
	$allowed = array( 'best_selling', 'date', 'sale', 'featured', 'popularity' );
	if ( ! in_array( $source, $allowed, true ) ) {
		$source = 'best_selling';
	}
	$count = max( 1, (int) kindi_opt( 'home_products_count', 10 ) );
	$key   = 'kindi_hot_' . $source . '_' . $count;

	$html = get_transient( $key );
	if ( false === $html ) {
		$html = function_exists( 'kindi_hot_products_html' ) ? kindi_hot_products_html( $source, $count ) : '';
		set_transient( $key, $html, HOUR_IN_SECONDS );
	}

	return rest_ensure_response( array( 'html' => $html ) );
}

/**
 * Clear the cached hot-products grids when products change.
 *
 * @return void
 */
function kindi_flush_hot_cache(): void {
	$count = max( 1, (int) kindi_opt( 'home_products_count', 10 ) );
	foreach ( array( 'best_selling', 'date', 'sale', 'featured', 'popularity' ) as $src ) {
		delete_transient( 'kindi_hot_' . $src . '_' . $count );
	}
}
add_action( 'save_post_product', 'kindi_flush_hot_cache' );
add_action( 'woocommerce_product_set_stock_status', 'kindi_flush_hot_cache' );
