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
 * Parse the footer FAQ option into question/answer pairs.
 *
 * Two authoring formats, auto-detected:
 *  - When a line of only dashes (`---`) appears, it separates Q&A blocks, and
 *    blank lines *inside* an answer are kept (rendered as paragraphs). This is
 *    how you get spacing between lines in an answer.
 *  - Otherwise (legacy) blocks are separated by a blank line — simple, but an
 *    answer can't then contain blank lines.
 * In both, the first line of a block is the question and the rest the answer.
 *
 * @return array<int,array{0:string,1:string}> [ [question, answer], … ]
 */
function kindi_faq_items(): array {
	$raw = trim( str_replace( "\r", '', (string) kindi_opt( 'faq_items' ) ) );
	if ( '' === $raw ) {
		return array();
	}

	$blocks = preg_match( '/^---+$/m', $raw )
		? preg_split( '/^---+$/m', $raw )   // Explicit separators: blank lines stay inside answers.
		: preg_split( '/\n\s*\n/', $raw );  // Legacy: blank line = separator.

	$out = array();
	foreach ( $blocks as $block ) {
		$block = trim( (string) $block );
		if ( '' === $block ) {
			continue;
		}
		$lines    = explode( "\n", $block );
		$question = trim( (string) array_shift( $lines ) );
		$answer   = trim( implode( "\n", $lines ) );
		if ( '' !== $question && '' !== $answer ) {
			$out[] = array( $question, $answer );
		}
	}
	return $out;
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
			'cta_url'   => '#',
		)
	);

	// Section-heading tag is admin-configurable (default h2) for SEO/structure.
	$tag = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'section_heading_tag', 'h2' ) : 'h2';
	$tag = in_array( $tag, array( 'h2', 'h3', 'h4' ), true ) ? $tag : 'h2';

	$html  = '<div class="kindi-sechead">';
	$html .= '<div class="kindi-sechead__text">';
	$html .= '<span class="kindi-eyebrow">' . esc_html( $args['eyebrow'] ) . '</span>';
	$html .= '<' . $tag . ' class="kindi-sec-title">' . esc_html( $args['title'] );
	if ( $args['highlight'] ) {
		$html .= ' <span class="kindi-hl">' . esc_html( $args['highlight'] ) . '</span>';
	}
	if ( $args['suffix'] ) {
		$html .= ' ' . esc_html( $args['suffix'] );
	}
	$html .= '</' . $tag . '>';
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
 * Resolve a mascot image URL from an admin option (a control-panel upload),
 * falling back to a bundled default. Accepts either a stored URL or attachment ID.
 *
 * @param string $opt_key     Option key (e.g. 'hero_mascot').
 * @param string $default_rel Bundled fallback relative to assets/img/.
 * @return string Image URL.
 */
function kindi_mascot_src( string $opt_key, string $default_rel ): string {
	$val = function_exists( 'kindi_opt' ) ? (string) kindi_opt( $opt_key, '' ) : '';
	if ( '' !== $val ) {
		if ( is_numeric( $val ) ) {
			$url = wp_get_attachment_image_url( (int) $val, 'large' );
			return $url ? $url : ( function_exists( 'kindi_img' ) ? kindi_img( $default_rel ) : '' );
		}
		return $val;
	}
	return function_exists( 'kindi_img' ) ? kindi_img( $default_rel ) : '';
}

/**
 * Attachment ID behind a mascot option's URL (0 when it isn't a media-library
 * image). attachment_url_to_postid() is a DB query, so the answer is cached.
 *
 * @param string $url Image URL.
 * @return int
 */
function kindi_mascot_attachment_id( string $url ): int {
	if ( false === strpos( $url, '/uploads/' ) ) {
		return 0;
	}
	$key = 'kindi_imgid_' . md5( $url );
	$id  = get_transient( $key );
	if ( false === $id ) {
		// Sized URLs (…-683x1024.png) don't resolve — strip the dimensions suffix.
		$base = preg_replace( '/-\d+x\d+(\.[a-z]{3,4})$/i', '$1', $url );
		$id   = (int) attachment_url_to_postid( $url );
		if ( 0 === $id && $base !== $url ) {
			$id = (int) attachment_url_to_postid( (string) $base );
		}
		set_transient( $key, $id, WEEK_IN_SECONDS );
	}
	return (int) $id;
}

/**
 * Render a mascot <img> responsively: media-library images get WordPress's
 * full srcset + an accurate sizes attribute, so phones download a phone-sized
 * file instead of the original. Bundled theme mascots (small WebP files) and
 * non-library URLs fall back to the plain tag rendered until now.
 *
 * @param string               $opt_key     Option key (e.g. 'hero_mascot').
 * @param string               $default_rel Bundled fallback under assets/img/.
 * @param string               $class       CSS classes for the img.
 * @param string               $sizes       The sizes attribute (real rendered widths).
 * @param string               $alt         Alt text ('' for decorative).
 * @param array<string,string> $extra       Extra attributes (loading, fetchpriority, decoding).
 * @return string
 */
function kindi_mascot_img( string $opt_key, string $default_rel, string $class, string $sizes, string $alt = '', array $extra = array() ): string {
	$src = kindi_mascot_src( $opt_key, $default_rel );
	if ( '' === $src ) {
		return '';
	}

	$id = kindi_mascot_attachment_id( $src );
	if ( $id > 0 ) {
		$attrs = array_merge(
			array(
				'class' => $class,
				'sizes' => $sizes,
				'alt'   => $alt,
			),
			$extra
		);
		$html = wp_get_attachment_image( $id, 'large', false, $attrs );
		if ( '' !== $html ) {
			return $html;
		}
	}

	$out = '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $src ) . '" width="520" height="520" alt="' . esc_attr( $alt ) . '"';
	foreach ( $extra as $attr => $value ) {
		$out .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
	}
	return $out . '>';
}

/**
 * Shortcode: [kindi_hot_products] — popular products grid that degrades
 * gracefully to a placeholder when WooCommerce is inactive (so the raw
 * [products] tag is never shown to visitors).
 *
 * @return string
 */
function kindi_hot_products_html( string $source = 'popularity', int $count = 10 ): string {
	if ( ! class_exists( 'WooCommerce' ) || ! shortcode_exists( 'products' ) ) {
		return '<div class="kindi-prod-empty">כאן יוצגו המוצרים שלכם. התקינו והפעילו את WooCommerce והוסיפו מוצרים כדי שיופיעו כאן אוטומטית.</div>';
	}

	$count  = max( 1, $count );
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

/**
 * Shortcode: [kindi_hot_products] — uses the control-panel source/count.
 *
 * @return string
 */
function kindi_hot_products_shortcode(): string {
	return kindi_hot_products_html( (string) kindi_opt( 'home_products_source', 'popularity' ), (int) kindi_opt( 'home_products_count', 10 ) );
}
add_shortcode( 'kindi_hot_products', 'kindi_hot_products_shortcode' );
