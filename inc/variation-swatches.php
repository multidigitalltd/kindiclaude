<?php
/**
 * Variation swatches — turn WooCommerce's variation <select> dropdowns into
 * round color swatches (for the colour attribute) and rounded pills (for the
 * rest). The original <select> is kept in the DOM but visually hidden, so it
 * stays the source of truth for WooCommerce's add-to-cart / variation JS while
 * the swatches drive it. No plugin, no extra assets — pure theme markup + the
 * existing interactions.js.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Hebrew/English colour-name → hex map for rendering colour swatches.
 *
 * @return array<string,string>
 */
function kindi_color_map(): array {
	return array(
		// Hebrew.
		'אדום'      => '#e23b3b',
		'בורדו'     => '#7b1f2b',
		'ורוד'      => '#f368a0',
		'כתום'      => '#ff7a30',
		'צהוב'      => '#ffcf33',
		'זהב'       => '#d4af37',
		'ירוק'      => '#3bb45a',
		'ירוק כהה'  => '#2f6f3e',
		'טורקיז'    => '#1fc4c4',
		'תכלת'      => '#6cc6f0',
		'כחול'      => '#2f6fed',
		'כחול כהה'  => '#1b2a52',
		'נייבי'     => '#1b2a52',
		'סגול'      => '#8a4fd6',
		'לילך'      => '#b89be0',
		'חום'       => '#8b5e3c',
		'בז\''      => '#e3d3ad',
		'בז’'       => '#e3d3ad',
		'קרם'       => '#f3ead2',
		'שחור'      => '#1a1a1a',
		'אפור'      => '#9aa3ad',
		'כסף'       => '#c8ccd1',
		'לבן'       => '#ffffff',
		// English.
		'red'       => '#e23b3b',
		'pink'      => '#f368a0',
		'orange'    => '#ff7a30',
		'yellow'    => '#ffcf33',
		'gold'      => '#d4af37',
		'green'     => '#3bb45a',
		'teal'      => '#1fc4c4',
		'turquoise' => '#1fc4c4',
		'cyan'      => '#6cc6f0',
		'blue'      => '#2f6fed',
		'navy'      => '#1b2a52',
		'purple'    => '#8a4fd6',
		'violet'    => '#8a4fd6',
		'lilac'     => '#b89be0',
		'brown'     => '#8b5e3c',
		'beige'     => '#e3d3ad',
		'cream'     => '#f3ead2',
		'black'     => '#1a1a1a',
		'gray'      => '#9aa3ad',
		'grey'      => '#9aa3ad',
		'silver'    => '#c8ccd1',
		'white'     => '#ffffff',
	);
}

/**
 * Resolve a colour value (hex) from an option's display name / slug, or '' when
 * it isn't a recognisable colour.
 *
 * @param string $name Display name.
 * @param string $slug Option slug.
 * @return string Hex colour or empty string.
 */
function kindi_resolve_color( string $name, string $slug ): string {
	$name = trim( $name );

	// Direct hex value (e.g. a term literally named "#ff0000").
	if ( preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $name ) ) {
		return $name;
	}

	$map = kindi_color_map();
	if ( isset( $map[ $name ] ) ) {
		return $map[ $name ];
	}

	$slug_l = strtolower( $slug );
	if ( isset( $map[ $slug_l ] ) ) {
		return $map[ $slug_l ];
	}

	return '';
}

/**
 * Is this attribute the colour attribute?
 *
 * @param string $attribute Attribute key (e.g. pa_color).
 * @return bool
 */
function kindi_is_color_attribute( string $attribute ): bool {
	$label = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $attribute ) : $attribute;
	if ( false !== mb_stripos( $label, 'צבע' ) ) {
		return true;
	}
	return (bool) preg_match( '/colou?r|zeva|tzeva/i', $attribute . ' ' . $label );
}

/**
 * Append swatch markup after each variation <select>. Hooked on the dropdown
 * HTML filter so it covers every variation attribute on the product page.
 *
 * @param string $html Original <select> markup.
 * @param array  $args Dropdown args (options, attribute, product, …).
 * @return string
 */
function kindi_variation_swatches_html( string $html, array $args ): string {
	$options   = isset( $args['options'] ) ? (array) $args['options'] : array();
	$attribute = isset( $args['attribute'] ) ? (string) $args['attribute'] : '';
	$product   = isset( $args['product'] ) ? $args['product'] : null;

	if ( empty( $options ) || '' === $attribute ) {
		return $html;
	}

	$is_color  = kindi_is_color_attribute( $attribute );
	$is_tax    = taxonomy_exists( $attribute );
	$label     = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $attribute, $product ) : $attribute;

	$buttons = '';
	foreach ( $options as $option ) {
		$slug = (string) $option;

		// Display name: term name for taxonomy attributes, raw value otherwise.
		$name = $slug;
		if ( $is_tax ) {
			$term = get_term_by( 'slug', $slug, $attribute );
			if ( $term && ! is_wp_error( $term ) ) {
				$name = $term->name;
			}
		}

		$is_multi = (bool) preg_match( '/צבעוני|רב.?גוני|multi|rainbow|mix/iu', $name );
		$hex      = $is_color && ! $is_multi ? kindi_resolve_color( $name, $slug ) : '';

		if ( $is_color && ( '' !== $hex || $is_multi ) ) {
			$classes = 'kindi-swatch kindi-swatch--color' . ( $is_multi ? ' kindi-swatch--multi' : '' );
			$style   = '' !== $hex ? ' style="--sw:' . esc_attr( $hex ) . '"' : '';
			$buttons .= sprintf(
				'<button type="button" class="%1$s" data-value="%2$s" aria-label="%3$s" aria-pressed="false"%4$s></button>',
				esc_attr( $classes ),
				esc_attr( $slug ),
				esc_attr( $name ),
				$style // phpcs:ignore WordPress.Security.EscapeOutput -- built from esc_attr above.
			);
		} else {
			$buttons .= sprintf(
				'<button type="button" class="kindi-swatch kindi-swatch--text" data-value="%1$s" aria-pressed="false">%2$s</button>',
				esc_attr( $slug ),
				esc_html( $name )
			);
		}
	}

	if ( '' === $buttons ) {
		return $html;
	}

	$swatches = '<div class="kindi-swatches" role="group" aria-label="' . esc_attr( $label ) . '">' . $buttons . '</div>';

	return $html . $swatches; // phpcs:ignore WordPress.Security.EscapeOutput -- assembled from escaped parts.
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_html', 'kindi_variation_swatches_html', 20, 2 );
