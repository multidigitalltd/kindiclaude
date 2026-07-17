<?php
/**
 * Single-product presentation — recreates the Lovable product-page design on top
 * of WooCommerce via summary/after-summary hooks: brand eyebrow, perks list,
 * delivery & gift card, trust strip, key-facts cards, "what's in the box" panel
 * and the skills chips. Pure presentation; data comes from product-meta.php.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Quantity stepper — a minus button before the number field and a plus button
 * after it, replacing the browser's native spinner arrows. Fires inside
 * WooCommerce's quantity-input template, so it covers both the product page and
 * the cart. Buttons are tabindex=-1 (the field itself stays keyboard-operable).
 *
 * @return void
 */
function kindi_qty_minus(): void {
	if ( kindi_qty_stepper_hidden() ) {
		return;
	}
	echo '<button type="button" class="kindi-qbtn kindi-qbtn--minus" tabindex="-1" aria-label="' . esc_attr__( 'הפחתת כמות', 'kindi' ) . '">&#8722;</button>';
}
add_action( 'woocommerce_before_quantity_input_field', 'kindi_qty_minus' );

/**
 * Plus button after the quantity field.
 *
 * @return void
 */
function kindi_qty_plus(): void {
	if ( kindi_qty_stepper_hidden() ) {
		return;
	}
	echo '<button type="button" class="kindi-qbtn kindi-qbtn--plus" tabindex="-1" aria-label="' . esc_attr__( 'הוספת כמות', 'kindi' ) . '">&#43;</button>';
}
add_action( 'woocommerce_after_quantity_input_field', 'kindi_qty_plus' );

/**
 * Whether the +/- stepper should be suppressed — when the product can't be
 * bought in more than one (sold individually, or max purchase quantity of 1).
 *
 * @return bool
 */
function kindi_qty_stepper_hidden(): bool {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	if ( $product->is_sold_individually() ) {
		return true;
	}
	return 1 === (int) $product->get_max_purchase_quantity();
}

/**
 * Brand eyebrow above the product title.
 *
 * @return void
 */
function kindi_pdp_brand_eyebrow(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! function_exists( 'kindi_product_brand' ) ) {
		return;
	}
	$brand = kindi_product_brand( $product );
	if ( '' === $brand ) {
		return;
	}
	echo '<div class="kindi-pdp__brand">' . esc_html( $brand ) . '</div>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_brand_eyebrow', 4 );

/**
 * Perks list (highlights) near the buy box.
 *
 * @return void
 */
function kindi_pdp_highlights(): void {
	global $product;
	$lines = function_exists( 'kindi_pmeta_lines' ) ? kindi_pmeta_lines( $product, 'highlights' ) : array();
	if ( ! $lines ) {
		return;
	}
	echo '<ul class="kindi-highlights">';
	foreach ( $lines as $line ) {
		echo '<li><span class="kindi-highlights__ic">' . kindi_icon( 'check', 'kindi-icon--sm' ) . '</span>' . esc_html( $line ) . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</ul>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_highlights', 33 );

/**
 * Delivery + gift-wrap reassurance card under the add-to-cart.
 *
 * @return void
 */
function kindi_pdp_delivery_card(): void {
	$threshold = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );

	echo '<div class="kindi-pdp__delivery">';
	echo '<div class="kindi-pdp__drow"><span class="kindi-pdp__dic kindi-pdp__dic--blue">' . kindi_icon( 'truck', 'kindi-icon--md' ) . '</span><div class="kindi-pdp__dtxt"><strong>' . esc_html( sprintf( 'משלוח חינם בהזמנה מעל ₪%d (למעט ריהוט)', $threshold ) ) . '</strong><span>' . kindi_icon( 'clock', 'kindi-icon--xs' ) . 'הזמינו היום — משלוח מהיר עד הבית</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-pdp__drow"><span class="kindi-pdp__dic kindi-pdp__dic--red">' . kindi_icon( 'gift', 'kindi-icon--md' ) . '</span><div class="kindi-pdp__dtxt"><strong>עטיפת מתנה</strong><span>סמנו בעגלה — נעטוף יפה ונצרף ברכה</span></div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '</div>';
}
add_action( 'woocommerce_single_product_summary', 'kindi_pdp_delivery_card', 34 );

/**
 * Product video URL: the Kindi field, falling back to the legacy Woodmart meta
 * so videos set on the previous theme reappear without re-entering them.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_product_video_url( WC_Product $product ): string {
	$url = (string) $product->get_meta( '_kindi_product_video' );
	if ( '' === $url ) {
		$url = (string) $product->get_meta( '_woodmart_product_video' );
	}
	return trim( $url );
}

/**
 * Build a lazy, responsive embed for a video URL: self-hosted files play in a
 * <video>; YouTube/Vimeo render as privacy-friendly, lazy-loaded iframes (no
 * oEmbed network call); anything else falls back to WordPress oEmbed then a link.
 *
 * @param string $url Video URL.
 * @return string HTML.
 */
function kindi_video_embed_html( string $url ): string {
	if ( preg_match( '/\.(mp4|webm|ogv|ogg)(\?.*)?$/i', $url ) ) {
		return sprintf( '<video class="kindi-pdp__video-el" controls preload="none" playsinline src="%s"></video>', esc_url( $url ) );
	}
	if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return sprintf(
			'<iframe class="kindi-pdp__video-el" src="%s" title="%s" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
			esc_url( 'https://www.youtube-nocookie.com/embed/' . $m[1] ),
			esc_attr__( 'סרטון מוצר', 'kindi' )
		);
	}
	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)~', $url, $m ) ) {
		return sprintf(
			'<iframe class="kindi-pdp__video-el" src="%s" title="%s" loading="lazy" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>',
			esc_url( 'https://player.vimeo.com/video/' . $m[1] ),
			esc_attr__( 'סרטון מוצר', 'kindi' )
		);
	}
	$embed = wp_oembed_get( $url );
	if ( $embed ) {
		return '<div class="kindi-pdp__video-oembed">' . $embed . '</div>'; // WP oEmbed provider HTML.
	}
	return sprintf( '<a href="%1$s" target="_blank" rel="noopener">%2$s</a>', esc_url( $url ), esc_html__( 'צפייה בסרטון', 'kindi' ) );
}

/**
 * Render the product video under the gallery (left media column).
 *
 * @return void
 */
function kindi_pdp_video(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$url = kindi_product_video_url( $product );
	if ( '' === $url ) {
		return;
	}
	echo '<div class="kindi-pdp__video">' . kindi_video_embed_html( $url ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- builder escapes each URL/attr; oEmbed HTML is trusted.
}
add_action( 'woocommerce_before_single_product_summary', 'kindi_pdp_video', 25 );

/**
 * Toy key-facts cards (pieces / age / play-time / players) — only those present.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_facts( $product ): void {
	$defs = array(
		array( 'icon' => 'blocks', 'label' => 'חלקים', 'key' => 'pieces' ),
		array( 'icon' => 'baby', 'label' => 'גיל מומלץ', 'key' => 'age' ),
		array( 'icon' => 'clock', 'label' => 'זמן משחק', 'key' => 'play_time' ),
		array( 'icon' => 'gamepad', 'label' => 'משתתפים', 'key' => 'players' ),
	);

	$cards = array();
	foreach ( $defs as $def ) {
		$value = kindi_pmeta( $product, $def['key'] );
		if ( '' !== $value ) {
			$cards[] = array( 'icon' => $def['icon'], 'label' => $def['label'], 'value' => $value );
		}
	}
	if ( ! $cards ) {
		return;
	}

	echo '<div class="kindi-facts">';
	foreach ( $cards as $card ) {
		echo '<div class="kindi-fact"><span class="kindi-fact__ic">' . kindi_icon( $card['icon'], 'kindi-icon--md' ) . '</span><span class="kindi-fact__l">' . esc_html( $card['label'] ) . '</span><span class="kindi-fact__v">' . esc_html( $card['value'] ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</div>';
}

/**
 * "In the box" navy gradient card with chips.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_inbox( $product ): void {
	$items = function_exists( 'kindi_pmeta_lines' ) ? kindi_pmeta_lines( $product, 'in_box' ) : array();
	if ( ! $items ) {
		return;
	}
	echo '<div class="kindi-inboxcard"><div class="kindi-inboxcard__head"><span class="kindi-inboxcard__ic">' . kindi_icon( 'gift', 'kindi-icon--md' ) . '</span><h3>מה יש בקופסה?</h3></div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div class="kindi-inboxcard__chips">';
	foreach ( $items as $item ) {
		echo '<span class="kindi-inboxcard__chip">' . esc_html( $item ) . '</span>';
	}
	echo '</div></div>';
}

/**
 * Render the left-column blocks (trust strip, key facts, skills, in-the-box),
 * called directly from the custom content-single-product.php under the gallery.
 *
 * @param WC_Product $product Product.
 * @return void
 */
function kindi_pdp_left_extras( $product ): void {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	echo '<div class="kindi-pdp-extras">';
	kindi_pdp_facts( $product );

	// Skills chips.
	$skills = function_exists( 'kindi_skill_items' ) ? kindi_skill_items( $product ) : array();
	if ( $skills ) {
		echo '<div class="kindi-skills"><h3>' . esc_html__( 'מיומנויות', 'kindi' ) . '</h3><ul class="kindi-skills__list">';
		foreach ( $skills as $skill ) {
			echo '<li class="kindi-skill">' . esc_html( $skill ) . '</li>';
		}
		echo '</ul></div>';
	}

	kindi_pdp_inbox( $product );
	echo '</div>';
}

/**
 * Reorder the buy box to match the design: brand → title → short description →
 * rating → price. (Move the excerpt from after the price up to just under the
 * title.)
 *
 * @return void
 */
function kindi_pdp_reorder_summary(): void {
	// Short description (clamped to two lines) directly under the title.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'kindi_pdp_short_excerpt', 6 );
	// Replace the default rating with our row: number + red stars + reviews + SKU.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	add_action( 'woocommerce_single_product_summary', 'kindi_pdp_rating', 9 );
	// Price box (soft red card with the saved amount).
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'kindi_pdp_price', 10 );
}
add_action( 'init', 'kindi_pdp_reorder_summary' );

/**
 * Price box — the WooCommerce price (sale + struck regular) inside a soft red
 * card, with a "חיסכון" pill showing the amount saved on simple sale products.
 *
 * @return void
 */
function kindi_pdp_price(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	echo '<div class="kindi-pdp__pricebox">';
	echo '<div class="kindi-pdp__price">' . $product->get_price_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce core, escaped.

	if ( $product->is_type( 'simple' ) && $product->is_on_sale() ) {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $regular > $sale && $sale > 0 ) {
			echo '<span class="kindi-pdp__save">' . esc_html__( 'חיסכון', 'kindi' ) . ' ' . wp_kses_post( wc_price( $regular - $sale ) ) . '</span>';
		}
	}
	echo '</div>';
}

/**
 * In-stock text: a plain "במלאי" — the remaining-units count is deliberately
 * NOT shown (online stock may differ from the physical store; see the notice
 * added to the "מידע נוסף" tab in kindi_pdp_stock_notice()).
 *
 * @param string     $text    Default text.
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_pdp_stock_text( $text, $product ): string {
	if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
		return $text;
	}
	return esc_html__( 'במלאי', 'kindi' );
}
add_filter( 'woocommerce_get_availability_text', 'kindi_pdp_stock_text', 10, 2 );

/**
 * Online-only stock/price disclaimer at the foot of the "מידע נוסף" tab.
 *
 * @return void
 */
function kindi_pdp_stock_notice(): void {
	echo '<p class="kindi-pdp-stocknote"><strong>' . esc_html__( 'שימו לב!', 'kindi' ) . '</strong> '
		. esc_html__( 'המלאי והמחירים באתר תקפים להזמנה אונליין בלבד. בחנות הפיזית ייתכנו הבדלים במלאי ובמחיר.', 'kindi' ) . '</p>';
}
add_action( 'woocommerce_product_additional_information', 'kindi_pdp_stock_notice', 20 );

/**
 * Short description under the title — clamped to two lines (CSS) with a
 * "מידע נוסף" control that opens the full description tab below.
 *
 * @return void
 */
function kindi_pdp_short_excerpt(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$short = $product->get_short_description();
	$long  = $product->get_description();
	$body  = '' !== $short ? $short : $long;
	if ( '' === $body ) {
		return;
	}
	echo '<div class="kindi-pdp__excerpt" data-kindi-excerpt>' . wp_kses_post( wpautop( $body ) ) . '</div>';
	// "קרא עוד" expands the clamped text in place. Rendered hidden and revealed by
	// JS only when the text actually overflows the two-line clamp, so it never
	// shows on short descriptions that already fit.
	echo '<button type="button" class="kindi-pdp__more" data-kindi-readmore aria-expanded="false" hidden>'
		. esc_html__( 'קרא עוד', 'kindi' )
		. kindi_icon( 'chevrondown', 'kindi-icon--xs' ) // phpcs:ignore WordPress.Security.EscapeOutput
		. '</button>';
}

/**
 * Rating row: numeric average + red stars + a reviews link that jumps to the
 * reviews tab + the SKU — matching the Lovable design.
 *
 * @return void
 */
function kindi_pdp_rating(): void {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$count   = (int) $product->get_review_count();
	$average = (float) $product->get_average_rating();
	$sku     = $product->get_sku();

	if ( $average <= 0 && '' === $sku ) {
		return;
	}

	echo '<div class="kindi-pdp__rating">';
	if ( $average > 0 ) {
		echo '<span class="kindi-pdp__rnum">' . esc_html( number_format( $average, 1 ) ) . '</span>';
		echo wc_get_rating_html( $average, $count ); // phpcs:ignore WordPress.Security.EscapeOutput -- WooCommerce core markup.
		if ( $count > 0 ) {
			// With Flashy reviews active the native tab is gone — jump to the
			// Flashy section instead.
			$kindi_flashy = function_exists( 'kindi_flashy_reviews_element' ) && '' !== kindi_flashy_reviews_element();
			echo '<a href="' . esc_attr( $kindi_flashy ? '#kindi-flashy-reviews' : '#tab-reviews' ) . '" class="kindi-pdp__reviews"' . ( $kindi_flashy ? '' : ' data-kindi-tab="reviews"' ) . ' rel="nofollow">('
				. esc_html( sprintf( _n( '%s ביקורת', '%s ביקורות', $count, 'kindi' ), number_format_i18n( $count ) ) )
				. ')</a>';
		}
	}
	if ( '' !== $sku ) {
		echo '<span class="kindi-pdp__sku">&middot; ' . esc_html__( 'מק"ט', 'kindi' ) . ' ' . esc_html( $sku ) . '</span>';
	}
	echo '</div>';
}

/**
 * Sale badge as "-X% חיסכון" (percentage saved) instead of the default "Sale!".
 *
 * @param string     $html    Default markup.
 * @param WP_Post    $post    Post.
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_pdp_sale_flash( $html, $post, $product ): string {
	if ( ! $product instanceof WC_Product ) {
		return $html;
	}
	$pct = 0;
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		$best   = 0;
		foreach ( ( $prices['regular_price'] ?? array() ) as $vid => $reg ) {
			$reg  = (float) $reg;
			$sale = (float) ( $prices['sale_price'][ $vid ] ?? $reg );
			if ( $reg > 0 && $sale < $reg ) {
				$best = max( $best, (int) round( ( 1 - $sale / $reg ) * 100 ) );
			}
		}
		$pct = $best;
	} else {
		$reg  = (float) $product->get_regular_price();
		$sale = (float) $product->get_sale_price();
		if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
			$pct = (int) round( ( 1 - $sale / $reg ) * 100 );
		}
	}
	$label = $pct > 0 ? sprintf( '%d%%- חיסכון', $pct ) : 'מבצע';

	return '<span class="onsale kindi-pdp__sale">' . esc_html( $label ) . '</span>';
}
add_filter( 'woocommerce_sale_flash', 'kindi_pdp_sale_flash', 10, 3 );

/**
 * Enable FlexSlider's prev/next arrows on the product gallery so shoppers can
 * page through the images straight from the main stage. FlexSlider marks the
 * arrows disabled when there's a single image, so they only surface on
 * multi-image galleries (styled subtly in woocommerce.css).
 *
 * @param array<string,mixed> $options FlexSlider options.
 * @return array<string,mixed>
 */
function kindi_gallery_carousel_options( array $options ): array {
	$options['directionNav']  = true;
	// Loop so neither arrow is disabled on the first/last image — both stay
	// visible on multi-image galleries (single-image galleries get no slider).
	$options['animationLoop']  = true;
	return $options;
}
add_filter( 'woocommerce_single_product_carousel_options', 'kindi_gallery_carousel_options' );

/**
 * "חדש בקינדי" badge in the gallery for recently-published products.
 *
 * @return void
 */
function kindi_pdp_new_badge(): void {
	global $product;
	if ( $product instanceof WC_Product && function_exists( 'kindi_is_new_product' ) && kindi_is_new_product( $product ) ) {
		echo '<span class="kindi-pdp__newbadge">' . kindi_icon( 'sparkles', 'kindi-icon--xs kindi-icon--white' ) . 'חדש בקינדי</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'woocommerce_before_single_product_summary', 'kindi_pdp_new_badge', 8 );

/**
 * "קנייה מהירה" (Buy Now) button under Add to Cart — adds to the cart and goes
 * straight to checkout. It carries the same name="add-to-cart" so WooCommerce
 * processes it normally; a hidden flag (set by store.js on click) triggers the
 * checkout redirect.
 *
 * @return void
 */
function kindi_pdp_buy_now(): void {
	global $product;
	if ( ! $product instanceof WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}
	echo '<input type="hidden" name="kindi_buy_now" value="0" data-kindi-buynow-flag>';
	echo '<button type="submit" name="add-to-cart" value="' . esc_attr( (string) $product->get_id() ) . '" class="button kindi-buynow" data-kindi-buynow>'
		. kindi_icon( 'check', 'kindi-icon--sm kindi-icon--white' ) // phpcs:ignore WordPress.Security.EscapeOutput
		. esc_html__( 'קנייה מהירה', 'kindi' )
		. '</button>';
}
add_action( 'woocommerce_after_add_to_cart_button', 'kindi_pdp_buy_now' );

/**
 * Redirect to checkout after a "Buy Now" add-to-cart.
 *
 * @param string $url Default redirect URL.
 * @return string
 */
function kindi_buy_now_redirect( $url ): string {
	// WooCommerce validates the add-to-cart request itself; this only reads a flag.
	if ( isset( $_REQUEST['kindi_buy_now'] ) && '1' === sanitize_text_field( wp_unslash( $_REQUEST['kindi_buy_now'] ) ) && function_exists( 'wc_get_checkout_url' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return wc_get_checkout_url();
	}
	return (string) $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'kindi_buy_now_redirect' );

/**
 * Show related/up-sell products five per row below the product.
 *
 * @param array<string,mixed> $args Loop args.
 * @return array<string,mixed>
 */
function kindi_pdp_related_args( array $args ): array {
	$args['posts_per_page'] = 5;
	$args['columns']        = 5;
	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'kindi_pdp_related_args' );
add_filter( 'woocommerce_upsell_display_args', 'kindi_pdp_related_args' );

/**
 * Drop the "מידע נוסף" (Read more) button for out-of-stock products — nothing to
 * do with them, so the button is just noise (e.g. in a grouped product's list).
 *
 * @param string     $html    Button HTML.
 * @param WC_Product $product Product.
 * @return string
 */
function kindi_hide_oos_loop_button( $html, $product ): string {
	if ( $product instanceof WC_Product && ! $product->is_in_stock() ) {
		return '';
	}
	return (string) $html;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'kindi_hide_oos_loop_button', 10, 2 );

/**
 * Add a "משלוחים והחזרות" product tab (between specs and reviews), built from the
 * shipping/return options so the content stays in sync with the panel.
 *
 * @param array<string,array<string,mixed>> $tabs Tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_pdp_shipping_tab( array $tabs ): array {
	$tabs['kindi_shipping'] = array(
		'title'    => __( 'משלוחים והחזרות', 'kindi' ),
		'priority' => 25,
		'callback' => 'kindi_pdp_shipping_tab_content',
	);
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'kindi_pdp_shipping_tab' );

/**
 * Shipping & returns tab content.
 *
 * @return void
 */
function kindi_pdp_shipping_tab_content(): void {
	$opt      = static fn( string $k, $d ) => function_exists( 'kindi_opt' ) ? kindi_opt( $k, $d ) : $d;
	$cost     = (int) $opt( 'ship_cost', 29 );
	$free     = (int) $opt( 'free_shipping', 299 );
	$min_days = (int) $opt( 'ship_days_min', 2 );
	$max_days = (int) $opt( 'ship_days_max', 4 );
	$address  = (string) $opt( 'store_address', '' );

	$ship_line = $cost > 0
		? sprintf( '₪%d, חינם מעל ₪%d (למעט ריהוט). אספקה תוך %d-%d ימי עסקים. באזורים רחוקים, קיבוצים ויישובים — עד 6 ימי עסקים.', $cost, $free, $min_days, $max_days )
		: sprintf( 'חינם (למעט ריהוט). אספקה תוך %d-%d ימי עסקים. באזורים רחוקים, קיבוצים ויישובים — עד 6 ימי עסקים.', $min_days, $max_days );

	echo '<div class="kindi-pdp-ship">';
	echo '<p><strong>' . esc_html__( 'משלוח עד הבית:', 'kindi' ) . '</strong> ' . esc_html( $ship_line ) . '</p>';
	if ( '' !== $address ) {
		echo '<p><strong>' . esc_html__( 'איסוף עצמי:', 'kindi' ) . '</strong> ' . esc_html( sprintf( 'בתיאום מראש, חינם מהחנות — %s.', $address ) ) . '</p>';
	}
	echo '</div>';
}

/* ============================ Flashy product reviews ============================ */

/**
 * The Flashy reviews element ID ('' = feature off, native reviews tab returns).
 *
 * @return string
 */
function kindi_flashy_reviews_element(): string {
	return trim( (string) kindi_opt( 'flashy_reviews' ) );
}

/**
 * Drop WooCommerce's native reviews tab while Flashy reviews render — one
 * reviews block on the page, not two.
 *
 * @param array<string,array<string,mixed>> $tabs Product tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_remove_native_reviews_tab( array $tabs ): array {
	if ( '' !== kindi_flashy_reviews_element() ) {
		unset( $tabs['reviews'] );
	}
	return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'kindi_remove_native_reviews_tab', 98 );

/**
 * Render the Flashy reviews element for THIS product — right after the tabs
 * (10) and BEFORE the upsells (15) / related products (20). The Flashy
 * plugin's own script populates it.
 *
 * @return void
 */
function kindi_flashy_reviews_render(): void {
	global $product;
	$element = kindi_flashy_reviews_element();
	if ( '' === $element || ! $product instanceof WC_Product ) {
		return;
	}
	echo '<section id="kindi-flashy-reviews" class="kindi-section kindi-flashy-reviews" aria-label="' . esc_attr__( 'ביקורות על המוצר', 'kindi' ) . '">';
	echo '<div data-inject-flashy-element="' . esc_attr( $element ) . '" data-item-id="' . esc_attr( (string) $product->get_id() ) . '"></div>';
	echo '</section>';
}
add_action( 'woocommerce_after_single_product_summary', 'kindi_flashy_reviews_render', 12 );
