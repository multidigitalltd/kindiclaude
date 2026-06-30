<?php
/**
 * Google Merchant Center product feed — RSS 2.0 with the g: namespace, served
 * at /?kindi_feed=google. Cached 6h, refreshed on product changes. Enables
 * Google Shopping / free product listings.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The feed URL. The same RSS/g: feed is valid for both Google Merchant Center
 * and Facebook/Meta catalogs; the type only changes the query value.
 *
 * @param string $type 'google' | 'facebook'.
 * @return string
 */
function kindi_feed_url( string $type = 'google' ): string {
	$type = in_array( $type, array( 'google', 'facebook' ), true ) ? $type : 'google';
	return add_query_arg( 'kindi_feed', $type, home_url( '/' ) );
}

/**
 * Output the feed when requested.
 *
 * @return void
 */
function kindi_maybe_render_feed(): void {
	$req = isset( $_GET['kindi_feed'] ) ? sanitize_key( wp_unslash( $_GET['kindi_feed'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! in_array( $req, array( 'google', 'facebook' ), true ) ) {
		return;
	}
	if ( ! class_exists( 'WooCommerce' ) ) {
		status_header( 404 );
		exit;
	}

	$xml = get_transient( 'kindi_google_feed' );
	if ( false === $xml ) {
		$xml = kindi_build_google_feed();
		set_transient( 'kindi_google_feed', $xml, 6 * HOUR_IN_SECONDS );
	}

	header( 'Content-Type: application/xml; charset=utf-8' );
	echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput -- Pre-escaped XML.
	exit;
}
add_action( 'template_redirect', 'kindi_maybe_render_feed' );

/**
 * Build the product feed XML. Variable products are exported as one item per
 * in-stock variation, linked by item_group_id (Google/Facebook standard), so
 * colour/size variants surface individually.
 *
 * @return string
 */
function kindi_build_google_feed(): string {
	$products = wc_get_products(
		array(
			'status'     => 'publish',
			'limit'      => 1000,
			'visibility' => 'visible',
		)
	);

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
	$xml .= '<title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>';
	$xml .= '<link>' . esc_url( home_url( '/' ) ) . '</link>';
	$xml .= '<description>' . esc_html( get_bloginfo( 'description' ) ) . '</description>';

	foreach ( $products as $product ) {
		if ( ! $product->is_visible() ) {
			continue;
		}

		if ( $product->is_type( 'variable' ) ) {
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation instanceof WC_Product && '' !== $variation->get_price() ) {
					$xml .= kindi_feed_item( $variation, $product );
				}
			}
			continue;
		}

		if ( '' !== $product->get_price() ) {
			$xml .= kindi_feed_item( $product );
		}
	}

	$xml .= '</channel></rss>';

	return $xml;
}

/**
 * Render a single feed <item>. When $parent is supplied, $product is a variation
 * and the item is grouped under the parent via item_group_id.
 *
 * @param WC_Product      $product Simple product or a variation.
 * @param WC_Product|null $parent  Parent variable product (for variations).
 * @return string
 */
function kindi_feed_item( $product, $parent = null ): string {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}
	$currency = get_woocommerce_currency();
	$source   = $parent instanceof WC_Product ? $parent : $product; // For shared data (brand, gallery, categories).

	$image = wp_get_attachment_url( $product->get_image_id() );
	if ( ! $image ) {
		$image = wp_get_attachment_url( $source->get_image_id() );
	}
	$brand = function_exists( 'kindi_product_brand' ) ? kindi_product_brand( $source ) : '';
	$cats  = wp_strip_all_tags( wc_get_product_category_list( $source->get_id(), ' > ' ) );
	$desc  = $product->get_description() ? $product->get_description() : $source->get_short_description();
	$desc  = $desc ? $desc : $source->get_description();

	$permalink = get_permalink( $source->get_id() );

	$item  = '<item>';
	$item .= '<g:id>' . (int) $product->get_id() . '</g:id>';
	// item_group_id groups variations; for simple products it equals the id
	// (matches the store's existing woo-feed output).
	$item .= '<g:item_group_id>' . (int) ( $parent instanceof WC_Product ? $parent->get_id() : $product->get_id() ) . '</g:item_group_id>';
	$item .= '<g:title>' . esc_html( $product->get_name() ) . '</g:title>';
	$item .= '<g:description>' . esc_html( wp_trim_words( wp_strip_all_tags( (string) $desc ), 100 ) ) . '</g:description>';
	$item .= '<link>' . esc_url( $permalink ) . '</link>';
	$item .= '<g:canonical_link>' . esc_url( $permalink ) . '</g:canonical_link>';
	if ( $image ) {
		$item .= '<g:image_link>' . esc_url( $image ) . '</g:image_link>';
	}

	// Additional images — the parent gallery (max 10, per Google).
	$gallery = array_slice( $source->get_gallery_image_ids(), 0, 10 );
	foreach ( $gallery as $gid ) {
		$gurl = wp_get_attachment_url( $gid );
		if ( $gurl && $gurl !== $image ) {
			$item .= '<g:additional_image_link>' . esc_url( $gurl ) . '</g:additional_image_link>';
		}
	}

	$item .= '<g:availability>' . ( $product->is_in_stock() ? 'in_stock' : 'out_of_stock' ) . '</g:availability>';
	$item .= '<g:price>' . esc_html( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) . ' ' . $currency ) . '</g:price>';
	if ( $product->is_on_sale() && '' !== $product->get_sale_price() ) {
		$item .= '<g:sale_price>' . esc_html( wc_get_price_to_display( $product ) . ' ' . $currency ) . '</g:sale_price>';
		$from = $product->get_date_on_sale_from();
		$to   = $product->get_date_on_sale_to();
		if ( $to ) {
			$item .= '<g:sale_price_effective_date>'
				. esc_html( ( $from ? $from->date( 'c' ) : gmdate( 'c', 0 ) ) . '/' . $to->date( 'c' ) )
				. '</g:sale_price_effective_date>';
		}
	}

	$item .= '<g:brand>' . esc_html( $brand ? $brand : get_bloginfo( 'name' ) ) . '</g:brand>';
	$item .= '<g:condition>new</g:condition>';

	// Identifiers — native WooCommerce GTIN (WC 9.2+) or common meta, plus SKU as MPN.
	$gtin = kindi_feed_gtin( $product );
	$sku  = $product->get_sku() ? $product->get_sku() : $source->get_sku();
	if ( '' !== $gtin ) {
		$item .= '<g:gtin>' . esc_html( $gtin ) . '</g:gtin>';
	}
	if ( $sku ) {
		$item .= '<g:mpn>' . esc_html( $sku ) . '</g:mpn>';
	}
	$item .= '<g:identifier_exists>' . ( ( '' !== $gtin || $sku ) ? 'yes' : 'no' ) . '</g:identifier_exists>';

	// Google product category — per-product meta, else the global default.
	$gpc = (string) $product->get_meta( '_google_product_category' );
	if ( '' === $gpc && $parent instanceof WC_Product ) {
		$gpc = (string) $parent->get_meta( '_google_product_category' );
	}
	if ( '' === $gpc && function_exists( 'kindi_opt' ) ) {
		$gpc = (string) kindi_opt( 'google_category', '' );
	}
	if ( '' !== $gpc ) {
		$item .= '<g:google_product_category>' . esc_html( $gpc ) . '</g:google_product_category>';
	}
	if ( $cats ) {
		$item .= '<g:product_type>' . esc_html( $cats ) . '</g:product_type>';
	}

	// Variant attributes (colour / size / material / pattern) for variations.
	foreach ( kindi_feed_variant_attributes( $product ) as $key => $value ) {
		$item .= '<g:' . $key . '>' . esc_html( $value ) . '</g:' . $key . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $key is a fixed feed tag.
	}

	// Shipping weight.
	$weight = $product->get_weight() ? $product->get_weight() : $source->get_weight();
	if ( $weight ) {
		$item .= '<g:shipping_weight>' . esc_html( $weight . ' ' . get_option( 'woocommerce_weight_unit', 'kg' ) ) . '</g:shipping_weight>';
	}

	// Shipping cost (flat domestic rate; free over the configured threshold).
	$ship_cost = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'ship_cost', 29 ) : 29 );
	$threshold = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );
	$price_now = (float) wc_get_price_to_display( $product );
	$ship_now  = ( $threshold > 0 && $price_now >= $threshold ) ? 0 : $ship_cost;
	$item     .= '<g:shipping><g:country>IL</g:country><g:service>Standard</g:service><g:price>'
		. esc_html( number_format( (float) $ship_now, 2, '.', '' ) . ' ' . $currency ) . '</g:price></g:shipping>';

	$item .= '</item>';

	return $item;
}

/**
 * Best-available GTIN/EAN/UPC for a product — native WooCommerce field first
 * (WC 9.2+ exposes get_global_unique_id), then common third-party meta keys.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_feed_gtin( $product ): string {
	if ( method_exists( $product, 'get_global_unique_id' ) ) {
		$native = (string) $product->get_global_unique_id();
		if ( '' !== $native ) {
			return $native;
		}
	}
	foreach ( array( '_gtin', '_ean', '_upc', '_barcode', '_wpm_gtin_code', 'hwp_var_gtin' ) as $meta_key ) {
		$value = (string) $product->get_meta( $meta_key );
		if ( '' !== $value ) {
			return $value;
		}
	}
	return '';
}

/**
 * Map a variation's attributes to the Google/Facebook variant fields
 * (colour / size / material / pattern). Hebrew attribute names are matched too.
 *
 * @param WC_Product $product Variation (or product).
 * @return array<string,string>
 */
function kindi_feed_variant_attributes( $product ): array {
	if ( ! $product->is_type( 'variation' ) ) {
		return array();
	}
	$map = array(
		'color'    => array( 'color', 'colour', 'צבע' ),
		'size'     => array( 'size', 'גודל', 'מידה' ),
		'material' => array( 'material', 'חומר' ),
		'pattern'  => array( 'pattern', 'דוגמה', 'דגם' ),
	);

	$out = array();
	foreach ( $product->get_variation_attributes() as $attr_key => $value ) {
		if ( '' === $value ) {
			continue;
		}
		$name = strtolower( str_replace( array( 'attribute_pa_', 'attribute_' ), '', $attr_key ) );
		$term = get_term_by( 'slug', $value, str_replace( 'attribute_', '', $attr_key ) );
		$label = $term && ! is_wp_error( $term ) ? $term->name : $value;
		foreach ( $map as $g_field => $needles ) {
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $name, $needle ) && empty( $out[ $g_field ] ) ) {
					$out[ $g_field ] = $label;
				}
			}
		}
	}
	return $out;
}

/**
 * Refresh the feed cache when a product changes.
 *
 * @return void
 */
function kindi_flush_feed_cache(): void {
	delete_transient( 'kindi_google_feed' );
}
add_action( 'save_post_product', 'kindi_flush_feed_cache' );
add_action( 'woocommerce_update_product', 'kindi_flush_feed_cache' );

/**
 * Show copy-able feed/sitemap URLs at the bottom of the Kindi settings screen.
 *
 * @return void
 */
function kindi_feeds_admin_panel(): void {
	// Only on the "פיקסל ומעקב" tab — the feeds live there now.
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'pixel' !== $tab ) {
		return;
	}

	$rows = array(
		array( 'Google Merchant — פיד מוצרים', kindi_feed_url( 'google' ), 'מתאים ל-Google Merchant Center (Shopping / רישומים חינמיים).' ),
		array( 'Facebook / Meta — פיד קטלוג', kindi_feed_url( 'facebook' ), 'הדביקו ב-Meta Commerce Manager → קטלוג → מקור נתונים → Data Feed.' ),
	);

	echo '<hr><h2>' . esc_html__( 'פידים למוצרים', 'kindi' ) . '</h2>';
	echo '<p class="description">' . esc_html__( 'העתיקו את הכתובות והדביקו במערכות הפרסום. הפיד מתעדכן אוטומטית בכל שינוי מוצר.', 'kindi' ) . '</p>';
	echo '<table class="form-table" role="presentation"><tbody>';
	foreach ( $rows as $i => $row ) {
		list( $label, $url, $help ) = $row;
		$id = 'kindi-feed-' . $i;
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>';
		echo '<div style="display:flex;gap:8px;max-width:680px">';
		echo '<input type="text" id="' . esc_attr( $id ) . '" value="' . esc_url( $url ) . '" readonly class="regular-text" style="flex:1;direction:ltr;text-align:left" onclick="this.select()">';
		echo '<button type="button" class="button" data-kindi-copy="' . esc_attr( $id ) . '">' . esc_html__( 'העתקה', 'kindi' ) . '</button>';
		echo '<a class="button" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html__( 'פתיחה', 'kindi' ) . '</a>';
		echo '</div><p class="description">' . esc_html( $help ) . '</p>';
		echo '</td></tr>';
	}
	echo '</tbody></table>';
	?>
	<script>
	document.querySelectorAll('[data-kindi-copy]').forEach(function(b){b.addEventListener('click',function(){var el=document.getElementById(b.getAttribute('data-kindi-copy'));if(!el)return;el.select();if(navigator.clipboard){navigator.clipboard.writeText(el.value);}else{document.execCommand('copy');}var t=b.textContent;b.textContent='הועתק ✓';setTimeout(function(){b.textContent=t;},1500);});});
	</script>
	<?php
}
add_action( 'kindi_settings_after_form', 'kindi_feeds_admin_panel' );
