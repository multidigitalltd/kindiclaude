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
 * Build the product feed XML.
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

	$currency = get_woocommerce_currency();

	$xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0"><channel>';
	$xml .= '<title>' . esc_html( get_bloginfo( 'name' ) ) . '</title>';
	$xml .= '<link>' . esc_url( home_url( '/' ) ) . '</link>';
	$xml .= '<description>' . esc_html( get_bloginfo( 'description' ) ) . '</description>';

	foreach ( $products as $product ) {
		if ( ! $product->is_visible() || '' === $product->get_price() ) {
			continue;
		}

		$image = wp_get_attachment_url( $product->get_image_id() );
		$brand = function_exists( 'kindi_product_brand' ) ? kindi_product_brand( $product ) : '';
		$cats  = wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ' &gt; ' ) );

		$xml .= '<item>';
		$xml .= '<g:id>' . (int) $product->get_id() . '</g:id>';
		$xml .= '<g:title>' . esc_html( $product->get_name() ) . '</g:title>';
		$xml .= '<g:description>' . esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ), 60 ) ) . '</g:description>';
		$xml .= '<g:link>' . esc_url( get_permalink( $product->get_id() ) ) . '</g:link>';
		if ( $image ) {
			$xml .= '<g:image_link>' . esc_url( $image ) . '</g:image_link>';
		}
		$xml .= '<g:availability>' . ( $product->is_in_stock() ? 'in_stock' : 'out_of_stock' ) . '</g:availability>';
		$xml .= '<g:price>' . esc_html( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ) . ' ' . $currency ) . '</g:price>';
		if ( $product->is_on_sale() && '' !== $product->get_sale_price() ) {
			$xml .= '<g:sale_price>' . esc_html( wc_get_price_to_display( $product ) . ' ' . $currency ) . '</g:sale_price>';
		}
		$xml .= '<g:brand>' . esc_html( $brand ? $brand : get_bloginfo( 'name' ) ) . '</g:brand>';
		$xml .= '<g:condition>new</g:condition>';
		$xml .= '<g:identifier_exists>' . ( $product->get_sku() ? 'yes' : 'no' ) . '</g:identifier_exists>';
		if ( $product->get_sku() ) {
			$xml .= '<g:mpn>' . esc_html( $product->get_sku() ) . '</g:mpn>';
		}
		if ( $cats ) {
			$xml .= '<g:product_type>' . esc_html( $cats ) . '</g:product_type>';
		}
		$xml .= '</item>';
	}

	$xml .= '</channel></rss>';

	return $xml;
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
	$rows = array(
		array( 'Google Merchant — פיד מוצרים', kindi_feed_url( 'google' ), 'מתאים ל-Google Merchant Center (Shopping / רישומים חינמיים).' ),
		array( 'Facebook / Meta — פיד קטלוג', kindi_feed_url( 'facebook' ), 'הדביקו ב-Meta Commerce Manager → קטלוג → מקור נתונים → Data Feed.' ),
	);
	if ( function_exists( 'kindi_product_sitemap_url' ) ) {
		$rows[] = array( 'Sitemap מוצרים (XML)', kindi_product_sitemap_url(), 'מפת אתר מוצרים עם תמונות — Google Search Console.' );
	}

	echo '<hr><h2>' . esc_html__( 'פידים ומפות אתר', 'kindi' ) . '</h2>';
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
