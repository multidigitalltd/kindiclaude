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
 * Term-meta keys that may hold a colour for an attribute term. Our own key wins;
 * the rest cover values left behind by common swatch plugins so existing data
 * keeps working after the plugin is removed. Filterable.
 *
 * @return array<int,string>
 */
function kindi_color_meta_keys(): array {
	return (array) apply_filters(
		'kindi_color_meta_keys',
		array(
			'kindi_color',              // This theme.
			'product_attribute_color',  // Variation Swatches (GetWooPlugins / Emran Ahmed).
			'_swatch_color',
			'swatch_color',
			'color',
			'pa_color_swatches_id_swatch', // Some legacy variants store a hex here.
		)
	);
}

/**
 * Resolve a colour stored on the attribute term itself (meta), if any.
 *
 * @param WP_Term $term Attribute term.
 * @return string Hex colour or empty string.
 */
function kindi_term_color( $term ): string {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	foreach ( kindi_color_meta_keys() as $key ) {
		$val = get_term_meta( $term->term_id, $key, true );
		if ( is_string( $val ) && '' !== $val ) {
			$hex = sanitize_hex_color( $val ) ?: sanitize_hex_color( '#' . ltrim( $val, '#' ) );
			if ( $hex ) {
				return $hex;
			}
		}
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
		$term = null;
		if ( $is_tax ) {
			$term = get_term_by( 'slug', $slug, $attribute );
			if ( $term && ! is_wp_error( $term ) ) {
				$name = $term->name;
			} else {
				$term = null;
			}
		}

		$is_multi = (bool) preg_match( '/צבעוני|רב.?גוני|multi|rainbow|mix/iu', $name );
		$hex      = '';
		if ( $is_color && ! $is_multi ) {
			// Term meta wins (set in the admin / left by a swatch plugin), then
			// fall back to the colour-name map.
			$hex = kindi_term_color( $term );
			if ( '' === $hex ) {
				$hex = kindi_resolve_color( $name, $slug );
			}
		}

		if ( $is_color && ( '' !== $hex || $is_multi ) ) {
			$classes = 'kindi-swatch kindi-swatch--color' . ( $is_multi ? ' kindi-swatch--multi' : '' );
			$style   = '' !== $hex ? ' style="--sw:' . esc_attr( $hex ) . '"' : '';
			$buttons .= sprintf(
				'<button type="button" class="%1$s" data-value="%2$s" data-label="%3$s" aria-label="%3$s" aria-pressed="false"%4$s></button>',
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

/* -------------------------------------------------------------------------
 * Admin: a colour field on each colour-attribute term, so colours can be set
 * without a plugin. Stored as term meta `kindi_color` (read first above).
 * ---------------------------------------------------------------------- */

/**
 * Wire the colour field into the add/edit screens of every colour attribute
 * taxonomy (e.g. pa_color / pa_צבע).
 *
 * @return void
 */
function kindi_register_color_term_fields(): void {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return;
	}
	foreach ( wc_get_attribute_taxonomies() as $attr ) {
		$taxonomy = function_exists( 'wc_attribute_taxonomy_name' ) ? wc_attribute_taxonomy_name( $attr->attribute_name ) : 'pa_' . $attr->attribute_name;
		if ( ! kindi_is_color_attribute( $taxonomy ) ) {
			continue;
		}
		add_action( "{$taxonomy}_add_form_fields", 'kindi_color_term_add_field' );
		add_action( "{$taxonomy}_edit_form_fields", 'kindi_color_term_edit_field' );
		add_action( "created_{$taxonomy}", 'kindi_color_term_save' );
		add_action( "edited_{$taxonomy}", 'kindi_color_term_save' );
	}
}
add_action( 'admin_init', 'kindi_register_color_term_fields' );

/**
 * Colour input on the "add term" screen.
 *
 * @return void
 */
function kindi_color_term_add_field(): void {
	wp_nonce_field( 'kindi_color_term', 'kindi_color_nonce' );
	?>
	<div class="form-field">
		<label for="kindi_color"><?php esc_html_e( 'צבע (לתצוגת עיגול)', 'kindi' ); ?></label>
		<input type="color" name="kindi_color" id="kindi_color" value="#ffffff">
		<p class="description"><?php esc_html_e( 'הצבע שיוצג בעיגול בורר הצבעים בעמוד המוצר. השאירו לבן אם אין צורך.', 'kindi' ); ?></p>
	</div>
	<?php
}

/**
 * Colour input on the "edit term" screen.
 *
 * @param WP_Term $term Term being edited.
 * @return void
 */
function kindi_color_term_edit_field( $term ): void {
	$value = $term instanceof WP_Term ? (string) get_term_meta( $term->term_id, 'kindi_color', true ) : '';
	$value = '' !== $value ? $value : '#ffffff';
	wp_nonce_field( 'kindi_color_term', 'kindi_color_nonce' );
	?>
	<tr class="form-field">
		<th scope="row"><label for="kindi_color"><?php esc_html_e( 'צבע (לתצוגת עיגול)', 'kindi' ); ?></label></th>
		<td>
			<input type="color" name="kindi_color" id="kindi_color" value="<?php echo esc_attr( $value ); ?>">
			<p class="description"><?php esc_html_e( 'הצבע שיוצג בעיגול בורר הצבעים בעמוד המוצר.', 'kindi' ); ?></p>
		</td>
	</tr>
	<?php
}

/**
 * Persist the colour term meta.
 *
 * @param int $term_id Term ID.
 * @return void
 */
function kindi_color_term_save( int $term_id ): void {
	if ( ! isset( $_POST['kindi_color_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kindi_color_nonce'] ) ), 'kindi_color_term' ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( ! isset( $_POST['kindi_color'] ) ) {
		return;
	}
	$hex = sanitize_hex_color( sanitize_text_field( wp_unslash( $_POST['kindi_color'] ) ) );
	if ( $hex && '#ffffff' !== strtolower( $hex ) ) {
		update_term_meta( $term_id, 'kindi_color', $hex );
	} else {
		delete_term_meta( $term_id, 'kindi_color' );
	}
}
