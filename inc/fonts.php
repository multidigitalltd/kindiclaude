<?php
/**
 * Fonts — bridge the WordPress Font Library into the theme, robustly.
 *
 * Rather than depending on WordPress's own @font-face output (which optimisers
 * often strip) or on exact Font Library family names, the theme scans the
 * physical font directory that WordPress stores uploads in, classifies each
 * file (Ploni body vs Ploni Yad display + weight) and declares @font-face under
 * names the theme controls ('Ploni' / 'PloniYad'). The theme's CSS references
 * those names first, so typography resolves no matter how the fonts were named
 * on upload. A diagnostic HTML comment lists what was found.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Derive a numeric font weight from a filename.
 *
 * @param string $name Lowercased filename.
 * @return string
 */
function kindi_weight_from_name( string $name ): string {
	if ( preg_match( '/(?:^|[^0-9])(100|200|300|400|500|600|700|800|900)(?:[^0-9]|$)/', $name, $m ) ) {
		return $m[1];
	}
	if ( false !== strpos( $name, 'thin' ) ) { return '100'; }
	if ( false !== strpos( $name, 'extralight' ) || false !== strpos( $name, 'ultralight' ) ) { return '200'; }
	if ( false !== strpos( $name, 'light' ) ) { return '300'; }
	if ( false !== strpos( $name, 'medium' ) ) { return '500'; }
	if ( false !== strpos( $name, 'semibold' ) || false !== strpos( $name, 'demibold' ) || false !== strpos( $name, 'semi' ) || false !== strpos( $name, 'demi' ) ) { return '600'; }
	if ( false !== strpos( $name, 'extrabold' ) || false !== strpos( $name, 'ultrabold' ) ) { return '800'; }
	if ( false !== strpos( $name, 'black' ) || false !== strpos( $name, 'heavy' ) ) { return '900'; }
	if ( false !== strpos( $name, 'bold' ) ) { return '700'; }
	return '400';
}

/**
 * Scan the WordPress font directory and classify Ploni / Ploni Yad faces.
 *
 * @return array<int,array{family:string,weight:string,style:string,src:string,ext:string,file:string}>
 */
function kindi_scan_font_faces(): array {
	$faces = array();

	if ( ! function_exists( 'wp_get_font_dir' ) ) {
		return $faces;
	}

	$dir  = wp_get_font_dir();
	$path = $dir['path'] ?? '';
	$url  = trailingslashit( $dir['url'] ?? '' );

	if ( ! $path || ! is_dir( $path ) ) {
		return $faces;
	}

	$files = array();
	foreach ( array( 'woff2', 'woff', 'ttf', 'otf' ) as $ext ) {
		$found = glob( $path . '/*.' . $ext );
		if ( $found ) {
			$files = array_merge( $files, $found );
		}
	}
	if ( ! $files ) {
		return $faces;
	}

	foreach ( $files as $file ) {
		$base  = basename( $file );
		$lower = strtolower( $base );

		// Only handle the brand fonts.
		if ( false === strpos( $lower, 'ploni' ) && false === strpos( $lower, 'yad' ) ) {
			continue;
		}

		$faces[] = array(
			'family' => false !== strpos( $lower, 'yad' ) ? 'PloniYad' : 'Ploni',
			'weight' => kindi_weight_from_name( $lower ),
			'style'  => false !== strpos( $lower, 'italic' ) ? 'italic' : 'normal',
			'src'    => $url . rawurlencode( $base ),
			'ext'    => strtolower( pathinfo( $base, PATHINFO_EXTENSION ) ),
			'file'   => $base,
		);
	}

	return $faces;
}

/**
 * Map a file extension to a CSS @font-face format() value.
 *
 * @param string $ext File extension.
 * @return string
 */
function kindi_font_format( string $ext ): string {
	$map = array( 'woff2' => 'woff2', 'woff' => 'woff', 'ttf' => 'truetype', 'otf' => 'opentype' );
	return $map[ $ext ] ?? 'woff2';
}

/**
 * Print @font-face rules (and a diagnostic comment) in the document head.
 *
 * @return void
 */
function kindi_print_font_faces(): void {
	$faces = kindi_scan_font_faces();

	// Diagnostic: lists exactly what the theme found on this server.
	$names = wp_list_pluck( $faces, 'file' );
	printf(
		"<!-- kindi-fonts: %d face(s) found%s -->\n",
		count( $faces ),
		$faces ? ' [' . esc_html( implode( ', ', $names ) ) . ']' : ' (none in WordPress font dir)'
	);

	if ( ! $faces ) {
		return;
	}

	echo '<style id="kindi-fonts">';
	foreach ( $faces as $face ) {
		printf(
			"@font-face{font-family:'%s';font-style:%s;font-weight:%s;font-display:swap;src:url('%s') format('%s');}",
			esc_attr( $face['family'] ),
			esc_attr( $face['style'] ),
			esc_attr( $face['weight'] ),
			esc_url( $face['src'] ),
			esc_attr( kindi_font_format( $face['ext'] ) )
		);
	}
	echo '</style>' . "\n";
}
add_action( 'wp_head', 'kindi_print_font_faces', 2 );

/**
 * Preload the display weight used by the hero heading (LCP) on home/shop.
 *
 * @return void
 */
function kindi_preload_brand_fonts(): void {
	$is_motion = function_exists( 'kindi_is_motion_view' ) && kindi_is_motion_view();
	// The About page's H1 uses the same display face above the fold.
	$is_about  = function_exists( 'kindi_is_about_view' ) && kindi_is_about_view();
	if ( ! $is_motion && ! $is_about ) {
		return;
	}

	foreach ( kindi_scan_font_faces() as $face ) {
		if ( 'PloniYad' === $face['family'] && in_array( $face['weight'], array( '700', '900' ), true ) && 'woff2' === $face['ext'] ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( $face['src'] )
			);
			break;
		}
	}
}
add_action( 'wp_head', 'kindi_preload_brand_fonts', 1 );
