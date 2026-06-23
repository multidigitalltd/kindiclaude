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
