<?php
/**
 * Open Graph + Twitter Card meta for link sharing (WhatsApp, Facebook, X…).
 *
 * Guarantees every page exposes a clean, plain-text title / description / image
 * so social scrapers never fall back to reading the JSON-LD schema script as the
 * preview text (which is what produces the "@context…@graph…" gibberish in a
 * WhatsApp preview). Skipped when a dedicated SEO plugin is already emitting
 * Open Graph, so we never print duplicate tags.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Is an active SEO plugin already outputting Open Graph tags? When true the
 * theme stays out of the way; when false (no plugin, or a schema-only plugin, or
 * an SEO plugin with its social module switched off) the theme fills the gap.
 *
 * @return bool
 */
function kindi_seo_owns_og(): bool {
	// The SEO Framework always outputs Open Graph.
	if ( function_exists( 'the_seo_framework' ) ) {
		return true;
	}
	// Yoast — only when its Open Graph toggle is on (the default).
	if ( defined( 'WPSEO_VERSION' ) ) {
		$social = get_option( 'wpseo_social' );
		return ! is_array( $social ) || ! empty( $social['opengraph'] );
	}
	// SEOPress — only when its Facebook Open Graph toggle is on.
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		$social = get_option( 'seopress_social_option_name' );
		return ! is_array( $social ) || ! empty( $social['seopress_social_facebook_og'] );
	}
	// Rank Math outputs Open Graph by default when active.
	if ( class_exists( 'RankMath' ) ) {
		return true;
	}
	return false;
}

/**
 * Best plain-text description for the current view (≤ ~200 chars).
 *
 * @return string
 */
function kindi_og_description(): string {
	$text = '';

	if ( function_exists( 'is_product' ) && is_product() ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product ) {
			$text = $product->get_short_description();
			if ( '' === trim( $text ) ) {
				$text = $product->get_description();
			}
		}
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			$text = '' !== $post->post_excerpt ? $post->post_excerpt : $post->post_content;
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$text = term_description( $term );
		}
	}

	if ( '' === trim( (string) $text ) ) {
		$text = (string) get_bloginfo( 'description' );
	}

	$text = wp_strip_all_tags( strip_shortcodes( (string) $text ), true );
	$text = trim( (string) preg_replace( '/\s+/', ' ', $text ) );

	if ( function_exists( 'mb_strlen' ) && mb_strlen( $text ) > 200 ) {
		$text = rtrim( mb_substr( $text, 0, 197 ) ) . '…';
	}
	return $text;
}

/**
 * Best title for the current view.
 *
 * @return string
 */
function kindi_og_title(): string {
	if ( is_front_page() ) {
		return (string) get_bloginfo( 'name' );
	}
	if ( is_singular() ) {
		return (string) get_the_title( get_queried_object_id() );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			return $term->name;
		}
	}
	return wp_get_document_title();
}

/**
 * Best canonical URL for the current view.
 *
 * @return string
 */
function kindi_og_url(): string {
	if ( is_singular() ) {
		return (string) get_permalink( get_queried_object_id() );
	}
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				return (string) $link;
			}
		}
	}
	return home_url( '/' );
}

/**
 * Best share image: the post/product image, the category thumbnail, or the site
 * logo as a last resort.
 *
 * @return string
 */
function kindi_og_image(): string {
	$img = '';

	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$img = (string) get_the_post_thumbnail_url( get_queried_object_id(), 'large' );
	} elseif ( is_category() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$tid = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
			if ( $tid ) {
				$img = (string) wp_get_attachment_image_url( $tid, 'large' );
			}
		}
	}

	if ( '' === $img && function_exists( 'kindi_img' ) ) {
		$img = (string) kindi_img( 'logo.png' );
	}

	/**
	 * Filter the Open Graph share image URL.
	 *
	 * @param string $img Image URL.
	 */
	return (string) apply_filters( 'kindi_og_image', $img );
}

/**
 * Print the Open Graph + Twitter Card tags.
 *
 * @return void
 */
function kindi_open_graph(): void {
	if ( is_admin() || kindi_seo_owns_og() ) {
		return;
	}
	/**
	 * Allow disabling the theme's Open Graph output entirely.
	 *
	 * @param bool $enabled Whether to print the tags.
	 */
	if ( ! apply_filters( 'kindi_output_open_graph', true ) ) {
		return;
	}

	$title = kindi_og_title();
	$desc  = kindi_og_description();
	$url   = kindi_og_url();
	$image = kindi_og_image();
	$type  = is_singular( 'post' ) ? 'article'
		: ( ( function_exists( 'is_product' ) && is_product() ) ? 'product' : 'website' );

	// [ attribute, key, value, is_url ].
	$tags = array(
		array( 'property', 'og:locale', 'he_IL', false ),
		array( 'property', 'og:type', $type, false ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ), false ),
		array( 'property', 'og:title', $title, false ),
		array( 'property', 'og:url', $url, true ),
	);
	if ( '' !== $desc ) {
		$tags[] = array( 'property', 'og:description', $desc, false );
	}
	if ( '' !== $image ) {
		$tags[] = array( 'property', 'og:image', $image, true );
	}
	$tags[] = array( 'name', 'twitter:card', '' !== $image ? 'summary_large_image' : 'summary', false );
	$tags[] = array( 'name', 'twitter:title', $title, false );
	if ( '' !== $desc ) {
		$tags[] = array( 'name', 'twitter:description', $desc, false );
	}
	if ( '' !== $image ) {
		$tags[] = array( 'name', 'twitter:image', $image, true );
	}

	foreach ( $tags as $tag ) {
		printf(
			"<meta %s=\"%s\" content=\"%s\">\n",
			esc_attr( $tag[0] ),
			esc_attr( $tag[1] ),
			$tag[3] ? esc_url( $tag[2] ) : esc_attr( $tag[2] )
		);
	}
}
add_action( 'wp_head', 'kindi_open_graph', 4 );
