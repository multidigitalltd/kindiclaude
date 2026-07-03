<?php
/**
 * Single blog post foot — prev/next navigation + related-post suggestions,
 * appended after the content (blog posts only; pages/products untouched).
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Append the navigation + suggestions to the post content.
 *
 * @param string $content Post content.
 * @return string
 */
function kindi_post_extras( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	return $content . kindi_post_prev_next() . kindi_post_related();
}
add_filter( 'the_content', 'kindi_post_extras', 40 );

/**
 * Previous / next post cards.
 *
 * @return string
 */
function kindi_post_prev_next(): string {
	$prev = get_previous_post();
	$next = get_next_post();
	if ( ! $prev && ! $next ) {
		return '';
	}

	$card = static function ( $post, string $label, string $mod ): string {
		if ( ! $post instanceof WP_Post ) {
			// Keeps the grid balanced when only one neighbour exists.
			return '<span class="kindi-pn__card kindi-pn__card--empty" aria-hidden="true"></span>';
		}
		return '<a class="kindi-pn__card kindi-pn__card--' . esc_attr( $mod ) . '" href="' . esc_url( (string) get_permalink( $post ) ) . '">'
			. '<span class="kindi-pn__label">' . esc_html( $label ) . '</span>'
			. '<span class="kindi-pn__title">' . esc_html( get_the_title( $post ) ) . '</span>'
			. '</a>';
	};

	return '<nav class="kindi-pn" aria-label="' . esc_attr__( 'ניווט בין פוסטים', 'kindi' ) . '">'
		. $card( $prev, __( '→ הפוסט הקודם', 'kindi' ), 'prev' )
		. $card( $next, __( 'הפוסט הבא ←', 'kindi' ), 'next' )
		. '</nav>';
}

/**
 * Related posts — same category first, latest posts as a fallback.
 *
 * @return string
 */
function kindi_post_related(): string {
	$id   = (int) get_the_ID();
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $id ),
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);

	$cats = wp_get_post_categories( $id );
	if ( $cats ) {
		$args['category__in'] = $cats;
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() && $cats ) {
		unset( $args['category__in'] );
		$q = new WP_Query( $args );
	}
	if ( ! $q->have_posts() ) {
		return '';
	}

	$html = '<section class="kindi-related"><h2 class="kindi-related__title">' . esc_html__( 'פוסטים נוספים שיעניינו אתכם', 'kindi' ) . '</h2><div class="kindi-related__grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		$html .= '<a class="kindi-relcard" href="' . esc_url( (string) get_permalink() ) . '">'
			. '<span class="kindi-relcard__img">' . get_the_post_thumbnail( null, 'medium_large' ) . '</span>'
			. '<span class="kindi-relcard__title">' . esc_html( get_the_title() ) . '</span>'
			. '<span class="kindi-relcard__date">' . esc_html( get_the_date() ) . '</span>'
			. '</a>';
	}
	wp_reset_postdata();

	return $html . '</div></section>';
}
