<?php
/**
 * Template tags — small markup helpers shared by patterns.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * URL to a theme image asset.
 *
 * @param string $relative Path under assets/img/.
 * @return string
 */
function kindi_img( string $relative ): string {
	return esc_url( KINDI_URI . 'assets/img/' . ltrim( $relative, '/' ) );
}

/**
 * Render a standard section heading (eyebrow + title with highlight).
 *
 * Mirrors the Lovable <SectionHead> component.
 *
 * @param array{eyebrow:string,title:string,highlight?:string,suffix?:string,desc?:string,cta?:?string} $args Heading parts.
 * @return string
 */
function kindi_section_head( array $args ): string {
	$args = wp_parse_args(
		$args,
		array(
			'eyebrow'   => '',
			'title'     => '',
			'highlight' => '',
			'suffix'    => '',
			'desc'      => '',
			'cta'       => null,
		)
	);

	$html  = '<div class="kindi-sechead">';
	$html .= '<div class="kindi-sechead__text">';
	$html .= '<span class="kindi-eyebrow">' . esc_html( $args['eyebrow'] ) . '</span>';
	$html .= '<h2 class="kindi-sec-title">' . esc_html( $args['title'] );
	if ( $args['highlight'] ) {
		$html .= ' <span class="kindi-hl">' . esc_html( $args['highlight'] ) . '</span>';
	}
	if ( $args['suffix'] ) {
		$html .= ' ' . esc_html( $args['suffix'] );
	}
	$html .= '</h2>';
	if ( $args['desc'] ) {
		$html .= '<p class="kindi-sec-desc">' . esc_html( $args['desc'] ) . '</p>';
	}
	$html .= '</div>';

	if ( ! empty( $args['cta'] ) ) {
		$html .= '<a class="kindi-sec-cta" href="' . esc_url( $args['cta_url'] ?? '#' ) . '">'
			. esc_html( $args['cta'] ) . kindi_icon( 'arrowleft', 'kindi-icon--sm' ) . '</a>';
	}

	$html .= '</div>';

	return $html;
}

/**
 * Shortcode: [kindi_hot_products] — popular products grid that degrades
 * gracefully to a placeholder when WooCommerce is inactive (so the raw
 * [products] tag is never shown to visitors).
 *
 * @return string
 */
function kindi_hot_products_shortcode(): string {
	if ( ! class_exists( 'WooCommerce' ) || ! shortcode_exists( 'products' ) ) {
		return '<div class="kindi-prod-empty">כאן יוצגו המוצרים שלכם. התקינו והפעילו את WooCommerce והוסיפו מוצרים כדי שיופיעו כאן אוטומטית.</div>';
	}

	$source = kindi_opt( 'home_products_source', 'popularity' );
	$count  = max( 1, (int) kindi_opt( 'home_products_count', 10 ) );
	$common = sprintf( 'limit="%d" columns="5" class="kindi-hot-products"', $count );

	switch ( $source ) {
		case 'date':
			return do_shortcode( "[recent_products {$common}]" );
		case 'sale':
			return do_shortcode( "[sale_products {$common}]" );
		case 'best_selling':
			return do_shortcode( "[best_selling_products {$common}]" );
		case 'featured':
			return do_shortcode( "[products {$common} visibility=\"featured\"]" );
		case 'category':
			$cid  = (int) kindi_opt( 'home_products_cat', 0 );
			$term = $cid ? get_term( $cid, 'product_cat' ) : null;
			if ( $term && ! is_wp_error( $term ) ) {
				return do_shortcode( '[product_category category="' . esc_attr( $term->slug ) . "\" {$common}]" );
			}
			// Fall through to popularity when no valid category.
		default:
			return do_shortcode( "[products {$common} orderby=\"popularity\" order=\"DESC\"]" );
	}
}
add_shortcode( 'kindi_hot_products', 'kindi_hot_products_shortcode' );
