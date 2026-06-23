<?php
/**
 * Fonts — bridge the WordPress Font Library into the theme.
 *
 * WordPress stores Font Library faces as `wp_font_face` posts and normally
 * prints their @font-face rules itself. On stacks where an optimiser strips or
 * defers that output, the brand fonts silently fall back. To guarantee the
 * theme's typography always resolves, we read the installed faces straight from
 * WordPress and re-declare them (and preload the hero weights) from the theme.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Read installed Font Library faces (family, weight, style, file URL).
 *
 * @return array<int,array{family:string,weight:string,style:string,src:string,ext:string}>
 */
function kindi_installed_font_faces(): array {
	$cache = wp_cache_get( 'kindi_font_faces' );
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$faces = array();

	if ( ! post_type_exists( 'wp_font_face' ) ) {
		wp_cache_set( 'kindi_font_faces', $faces, '', HOUR_IN_SECONDS );
		return $faces;
	}

	$posts = get_posts(
		array(
			'post_type'              => 'wp_font_face',
			'post_status'            => 'publish',
			'numberposts'            => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$dir     = function_exists( 'wp_get_font_dir' ) ? wp_get_font_dir() : array( 'url' => content_url( '/uploads/fonts' ) );
	$dir_url = trailingslashit( $dir['url'] ?? '' );

	foreach ( $posts as $post ) {
		$data = json_decode( (string) $post->post_content, true );
		if ( ! is_array( $data ) || empty( $data['src'] ) ) {
			continue;
		}

		$src = is_array( $data['src'] ) ? reset( $data['src'] ) : $data['src'];
		$src = (string) $src;

		// Normalise a stored filename/relative path to a full URL.
		if ( 0 !== strpos( $src, 'http' ) ) {
			$src = $dir_url . ltrim( $src, '/' );
		}

		$ext   = strtolower( pathinfo( (string) wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_EXTENSION ) );
		$faces[] = array(
			'family' => trim( (string) ( $data['fontFamily'] ?? '' ), "\"' " ),
			'weight' => (string) ( $data['fontWeight'] ?? '400' ),
			'style'  => (string) ( $data['fontStyle'] ?? 'normal' ),
			'src'    => $src,
			'ext'    => $ext,
		);
	}

	wp_cache_set( 'kindi_font_faces', $faces, '', HOUR_IN_SECONDS );

	return $faces;
}

/**
 * Map a file extension to a CSS @font-face format() value.
 *
 * @param string $ext File extension.
 * @return string
 */
function kindi_font_format( string $ext ): string {
	$map = array(
		'woff2' => 'woff2',
		'woff'  => 'woff',
		'ttf'   => 'truetype',
		'otf'   => 'opentype',
	);

	return $map[ $ext ] ?? 'woff2';
}

/**
 * Print @font-face rules for the installed brand fonts, read from WordPress.
 *
 * @return void
 */
function kindi_print_font_faces(): void {
	$faces = kindi_installed_font_faces();
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
 * Preload the display weight used by the hero heading (LCP) when on home/shop.
 *
 * @return void
 */
function kindi_preload_brand_fonts(): void {
	if ( ! function_exists( 'kindi_is_motion_view' ) || ! kindi_is_motion_view() ) {
		return;
	}

	foreach ( kindi_installed_font_faces() as $face ) {
		// Hero title uses the heaviest "Yad" weight; preload just that one.
		if ( false !== stripos( $face['family'], 'yad' ) && in_array( $face['weight'], array( '700', '900' ), true ) && 'woff2' === $face['ext'] ) {
			printf(
				'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
				esc_url( $face['src'] )
			);
			break;
		}
	}
}
add_action( 'wp_head', 'kindi_preload_brand_fonts', 1 );
