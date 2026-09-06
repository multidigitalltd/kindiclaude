<?php
/**
 * Meta (Facebook) Pixel — a native, lean replacement for PixelYourSite.
 *
 * Enter a Pixel ID in the control panel and the base code is injected
 * automatically, plus the standard e-commerce events (ViewContent, AddToCart,
 * InitiateCheckout, Purchase), category/search/404 events, registration/login,
 * and optional engagement events (scroll, time, downloads, forms, comments).
 * Server data is rendered inline (no extra request); interaction events use one
 * tiny deferred script that loads only when something needs it. Also captures
 * first-touch attribution (UTMs / landing page / traffic source) onto the order.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Sanitised Pixel ID (digits only), or '' when not configured.
 *
 * @return string
 */
function kindi_pixel_id(): string {
	$id = function_exists( 'kindi_opt' ) ? trim( (string) kindi_opt( 'fb_pixel_id', '' ) ) : '';
	return preg_match( '/^\d{5,20}$/', $id ) ? $id : '';
}

/**
 * Whether the pixel should fire on the front end.
 *
 * @return bool
 */
function kindi_pixel_active(): bool {
	return ! is_admin()
		&& '' !== kindi_pixel_id()
		&& '0' !== (string) kindi_opt( 'pixel_enable', '1' );
}

/**
 * Is a given event/option toggle on (defaults to on)?
 *
 * @param string $key Option key.
 * @return bool
 */
function kindi_px_on( string $key ): bool {
	return '0' !== (string) kindi_opt( $key, '1' );
}

/**
 * Page-level custom parameters attached to PageView (mirrors the PixelYourSite
 * "WordPress parameters": title, type, id, category, url, user role).
 *
 * @return array<string,mixed>
 */
function kindi_pixel_global_params(): array {
	$params = array(
		'page_title' => wp_get_document_title(),
		'event_url'  => home_url( add_query_arg( array() ) ),
	);

	if ( is_singular() ) {
		$id                      = get_queried_object_id();
		$params['post_id']       = $id;
		$params['post_type']     = (string) get_post_type( $id );
		$params['content_name']  = get_the_title( $id );
		$taxonomy                = is_singular( 'product' ) ? 'product_cat' : 'category';
		$terms                   = get_the_terms( $id, $taxonomy );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$params['post_category'] = implode( ', ', wp_list_pluck( $terms, 'name' ) );
		}
	}

	if ( is_user_logged_in() ) {
		$roles                = (array) wp_get_current_user()->roles;
		$params['user_role']  = (string) ( $roles[0] ?? '' );
	}

	return $params;
}

/**
 * Base pixel + PageView in the head.
 *
 * @return void
 */
function kindi_pixel_base(): void {
	if ( ! kindi_pixel_active() ) {
		return;
	}
	$id     = kindi_pixel_id();
	$params = wp_json_encode( kindi_pixel_global_params(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
	?>
	<!-- Meta Pixel (Kindi) -->
	<script>
	!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
	fbq('init','<?php echo esc_js( $id ); ?>');
	fbq('track','PageView'<?php echo $params ? ',' . $params : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- JSON_HEX_TAG-encoded. ?>);
	</script>
	<noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id=<?php echo esc_attr( $id ); ?>&ev=PageView&noscript=1"/></noscript>
	<!-- End Meta Pixel -->
	<?php
}
add_action( 'wp_head', 'kindi_pixel_base', 5 );

/**
 * Build a `fbq('track', …)` call from an event + params (params JSON is
 * tag-escaped so it is safe to print inside a script element).
 *
 * @param string              $event  Event name.
 * @param array<string,mixed> $params Event params.
 * @param string              $method 'track' | 'trackCustom'.
 * @return string
 */
function kindi_px_call( string $event, array $params = array(), string $method = 'track' ): string {
	$json = $params ? ',' . wp_json_encode( $params, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) : '';
	return "fbq('" . ( 'trackCustom' === $method ? 'trackCustom' : 'track' ) . "'," . wp_json_encode( $event ) . $json . ');';
}

/**
 * Standard content params for a product.
 *
 * @param WC_Product $product Product.
 * @return array<string,mixed>
 */
function kindi_px_product_params( $product ): array {
	return array(
		'content_ids'      => array( (string) ( $product->get_sku() ? $product->get_sku() : $product->get_id() ) ),
		'content_type'     => 'product',
		'content_name'     => $product->get_name(),
		'content_category' => wp_strip_all_tags( wc_get_product_category_list( $product->get_id(), ', ' ) ),
		'value'            => (float) wc_get_price_to_display( $product ),
		'currency'         => get_woocommerce_currency(),
	);
}

/**
 * Contents/value params for the current cart.
 *
 * @return array<string,mixed>
 */
function kindi_px_cart_params(): array {
	$ids      = array();
	$contents = array();
	$num      = 0;
	$value    = 0.0;

	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $line ) {
			$product = $line['data'] ?? null;
			if ( ! $product instanceof WC_Product ) {
				continue;
			}
			$id         = (string) ( $product->get_sku() ? $product->get_sku() : $product->get_id() );
			$ids[]      = $id;
			$num       += (int) $line['quantity'];
			$contents[] = array(
				'id'         => $id,
				'quantity'   => (int) $line['quantity'],
				'item_price' => round( (float) wc_get_price_to_display( $product ), 2 ),
			);
		}
		$value = (float) WC()->cart->get_total( 'edit' );
	}

	return array(
		'content_ids'  => $ids,
		'contents'     => $contents,
		'content_type' => 'product',
		'num_items'    => $num,
		'value'        => round( $value, 2 ),
		'currency'     => get_woocommerce_currency(),
	);
}

/**
 * Purchase event for the order-received page, fired once per order.
 *
 * @return string
 */
function kindi_px_purchase_call(): string {
	global $wp;
	$order_id = absint( $wp->query_vars['order-received'] ?? 0 );
	$order    = $order_id ? wc_get_order( $order_id ) : false;
	if ( ! $order instanceof WC_Order || $order->get_meta( '_kindi_pixel_tracked' ) ) {
		return '';
	}

	$ids      = array();
	$contents = array();
	$num      = 0;
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		if ( ! $product instanceof WC_Product ) {
			continue;
		}
		$id         = (string) ( $product->get_sku() ? $product->get_sku() : $product->get_id() );
		$ids[]      = $id;
		$num       += (int) $item->get_quantity();
		$contents[] = array(
			'id'         => $id,
			'quantity'   => (int) $item->get_quantity(),
			'item_price' => round( (float) $order->get_item_total( $item ), 2 ),
		);
	}

	$params = array(
		'content_ids'  => $ids,
		'contents'     => $contents,
		'content_type' => 'product',
		'num_items'    => $num,
		'value'        => round( (float) $order->get_total(), 2 ),
		'currency'     => $order->get_currency(),
	);

	$order->update_meta_data( '_kindi_pixel_tracked', current_time( 'mysql' ) );
	$order->save();

	return kindi_px_call( 'Purchase', $params );
}

/**
 * Server-rendered events in the footer (no extra request).
 *
 * @return void
 */
function kindi_pixel_events(): void {
	if ( ! kindi_pixel_active() ) {
		return;
	}
	$js = '';

	if ( function_exists( 'is_product' ) && is_product() && kindi_px_on( 'px_view_content' ) ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$js .= kindi_px_call( 'ViewContent', kindi_px_product_params( $product ) );
		}
	}

	if ( function_exists( 'is_product_category' ) && is_product_category() && kindi_px_on( 'px_view_category' ) ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$js .= kindi_px_call( 'ViewCategory', array( 'content_name' => $term->name, 'content_category' => $term->name, 'content_type' => 'product_group' ), 'trackCustom' );
		}
	}

	if ( function_exists( 'is_checkout' ) && is_checkout()
		&& ! ( function_exists( 'is_order_received_page' ) && is_order_received_page() )
		&& kindi_px_on( 'px_initiate_checkout' ) ) {
		$js .= kindi_px_call( 'InitiateCheckout', kindi_px_cart_params() );
	}

	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() && kindi_px_on( 'px_purchase' ) ) {
		$js .= kindi_px_purchase_call();
	}

	if ( is_search() && kindi_px_on( 'px_search' ) ) {
		$js .= kindi_px_call( 'Search', array( 'search_string' => get_search_query() ) );
	}

	if ( is_404() && kindi_px_on( 'px_404' ) ) {
		$js .= kindi_px_call( 'Pageview404', array(), 'trackCustom' );
	}

	if ( '' !== $js ) {
		echo '<script>' . $js . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput -- params JSON tag-escaped above.
	}
}
add_action( 'wp_footer', 'kindi_pixel_events', 20 );

/**
 * Tag loop add-to-cart buttons with product data for the click-fired AddToCart.
 *
 * @param array<string,mixed> $args    Button args.
 * @param WC_Product          $product Product.
 * @return array<string,mixed>
 */
function kindi_px_loop_atts( array $args, $product ): array {
	if ( kindi_pixel_active() && kindi_px_on( 'px_add_to_cart' ) && $product instanceof WC_Product ) {
		$args['attributes']['data-fb-id']    = (string) ( $product->get_sku() ? $product->get_sku() : $product->get_id() );
		$args['attributes']['data-fb-name']  = $product->get_name();
		$args['attributes']['data-fb-value'] = (string) wc_get_price_to_display( $product );
	}
	return $args;
}
add_filter( 'woocommerce_loop_add_to_cart_args', 'kindi_px_loop_atts', 10, 2 );

/**
 * Set a short-lived cookie after registration / login, consumed by pixel.js so
 * CompleteRegistration / Login fire on the next page view.
 *
 * @return void
 */
function kindi_px_signup_cookie(): void {
	if ( kindi_pixel_active() && kindi_px_on( 'px_signup' ) ) {
		setcookie( 'kindi_px_signup', '1', time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
	}
}
add_action( 'user_register', 'kindi_px_signup_cookie' );

/**
 * Login cookie companion.
 *
 * @return void
 */
function kindi_px_login_cookie(): void {
	if ( kindi_pixel_active() && kindi_px_on( 'px_login' ) ) {
		setcookie( 'kindi_px_login', '1', time() + 300, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN );
	}
}
add_action( 'wp_login', 'kindi_px_login_cookie' );

/**
 * Which interaction events need the deferred script?
 *
 * @return bool
 */
function kindi_pixel_needs_js(): bool {
	if ( ! kindi_pixel_active() ) {
		return false;
	}
	foreach ( array( 'px_add_to_cart', 'px_scroll', 'px_time', 'px_downloads', 'px_forms', 'px_comments', 'px_signup', 'px_login', 'px_native_reporting' ) as $key ) {
		if ( kindi_px_on( $key ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Enqueue the deferred interaction script and hand it the active toggles +
 * current product data (single product).
 *
 * @return void
 */
function kindi_pixel_enqueue(): void {
	if ( ! kindi_pixel_needs_js() ) {
		return;
	}

	wp_enqueue_script(
		'kindi-pixel',
		KINDI_URI . 'assets/js/pixel.js',
		array(),
		kindi_asset_version( 'assets/js/pixel.js' ),
		true
	);

	$product_data = null;
	if ( function_exists( 'is_product' ) && is_product() && kindi_px_on( 'px_add_to_cart' ) ) {
		$product = wc_get_product( get_queried_object_id() );
		if ( $product instanceof WC_Product ) {
			$product_data = kindi_px_product_params( $product );
		}
	}

	wp_localize_script(
		'kindi-pixel',
		'kindiPixel',
		array(
			'currency'  => get_woocommerce_currency(),
			'product'   => $product_data,
			'reporting' => kindi_px_on( 'px_native_reporting' ),
			'events'    => array(
				'addToCart' => kindi_px_on( 'px_add_to_cart' ),
				'scroll'    => kindi_px_on( 'px_scroll' ),
				'time'      => kindi_px_on( 'px_time' ),
				'downloads' => kindi_px_on( 'px_downloads' ),
				'forms'     => kindi_px_on( 'px_forms' ),
				'comments'  => kindi_px_on( 'px_comments' ),
				'signup'    => kindi_px_on( 'px_signup' ),
				'login'     => kindi_px_on( 'px_login' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_pixel_enqueue' );

/**
 * Defer the pixel interaction script.
 *
 * @param string $tag    Script tag.
 * @param string $handle Handle.
 * @return string
 */
function kindi_pixel_defer( string $tag, string $handle ): string {
	if ( 'kindi-pixel' === $handle && false === strpos( $tag, 'defer' ) ) {
		$tag = str_replace( ' src', ' defer src', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'kindi_pixel_defer', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Native order attribution (UTMs / landing page / traffic source) — replaces
 * the PixelYourSite "Native Data Tracking and Reporting" feature.
 * ---------------------------------------------------------------------------
 */

/**
 * Allowed attribution keys.
 *
 * @return array<string,string>
 */
function kindi_px_attr_fields(): array {
	return array(
		'utm_source'     => 'מקור (utm_source)',
		'utm_medium'     => 'מדיום (utm_medium)',
		'utm_campaign'   => 'קמפיין (utm_campaign)',
		'utm_term'       => 'מונח (utm_term)',
		'utm_content'    => 'תוכן (utm_content)',
		'landing_page'   => 'דף נחיתה',
		'traffic_source' => 'מקור תנועה',
	);
}

/**
 * Save first-touch attribution from the cookie onto the order.
 *
 * @param WC_Order            $order Order.
 * @param array<string,mixed> $data  Posted data (unused).
 * @return void
 */
function kindi_px_save_attribution( $order, array $data ): void {
	unset( $data );
	if ( ! kindi_px_on( 'px_native_reporting' ) || empty( $_COOKIE['kindi_attr'] ) ) {
		return;
	}
	$raw = json_decode( sanitize_textarea_field( wp_unslash( $_COOKIE['kindi_attr'] ) ), true ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- first-party attribution cookie, not a form action.
	if ( ! is_array( $raw ) ) {
		return;
	}
	foreach ( array_keys( kindi_px_attr_fields() ) as $key ) {
		if ( ! empty( $raw[ $key ] ) ) {
			$value = 'landing_page' === $key ? esc_url_raw( (string) $raw[ $key ] ) : sanitize_text_field( (string) $raw[ $key ] );
			$order->update_meta_data( '_kindi_' . $key, $value );
		}
	}
}
add_action( 'woocommerce_checkout_create_order', 'kindi_px_save_attribution', 20, 2 );

/**
 * Show the attribution on the admin order screen.
 *
 * @param WC_Order $order Order.
 * @return void
 */
function kindi_px_admin_attribution( $order ): void {
	if ( ! $order instanceof WC_Order ) {
		return;
	}
	$rows = '';
	foreach ( kindi_px_attr_fields() as $key => $label ) {
		$value = (string) $order->get_meta( '_kindi_' . $key );
		if ( '' !== $value ) {
			$rows .= '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</li>';
		}
	}
	if ( '' !== $rows ) {
		echo '<div class="kindi-order-attr"><h4>' . esc_html__( 'מקור ההזמנה', 'kindi' ) . '</h4><ul style="margin:0;padding-inline-start:1.2em">' . $rows . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- rows built from esc_html above.
	}
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'kindi_px_admin_attribution' );

/**
 * Append attribution to the admin "New order" email.
 *
 * @param WC_Order $order         Order.
 * @param bool     $sent_to_admin Admin email?
 * @param bool     $plain_text    Plain text?
 * @return void
 */
function kindi_px_email_attribution( $order, $sent_to_admin = false, $plain_text = false ): void {
	if ( ! $sent_to_admin || ! $order instanceof WC_Order ) {
		return;
	}
	$rows = '';
	foreach ( kindi_px_attr_fields() as $key => $label ) {
		$value = (string) $order->get_meta( '_kindi_' . $key );
		if ( '' === $value ) {
			continue;
		}
		$rows .= $plain_text
			? "\n" . $label . ': ' . $value
			: '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</li>';
	}
	if ( '' === $rows ) {
		return;
	}
	if ( $plain_text ) {
		echo "\n" . esc_html__( 'מקור ההזמנה', 'kindi' ) . ':' . esc_html( $rows ) . "\n";
	} else {
		echo '<h3>' . esc_html__( 'מקור ההזמנה', 'kindi' ) . '</h3><ul>' . $rows . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput -- rows built from esc_html above.
	}
}
add_action( 'woocommerce_email_order_meta', 'kindi_px_email_attribution', 20, 3 );
