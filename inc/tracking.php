<?php
/**
 * LionWheel shipment tracking.
 *
 * Two surfaces:
 * - `[kindi_tracking]` shortcode for a dedicated status page: order number +
 *   phone form, verified server-side against the order's phones, answered via
 *   an AJAX lookup (nonce + per-IP rate limit, generic "not found" so order
 *   existence is never exposed).
 * - My Account → view order: the shipment status renders automatically for the
 *   order being viewed (the customer is already authenticated there).
 *
 * The API key lives in the Kindi panel and is used server-side only — it never
 * reaches HTML or JavaScript. API responses are cached for 2 minutes.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Panel section: API key + usage note.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_lw_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections'] ) ) {
		$tabs['texts']['sections']['מעקב משלוחים (LionWheel)'] = array(
			'lionwheel_key'    => array( 'type' => 'secret', 'label' => 'מפתח API של LionWheel', 'help' => 'המפתח נשמר בצד השרת בלבד ואינו מוצג במסך. כשהוא מוגדר: סטטוס המשלוח מוצג אוטומטית באזור האישי בעמוד ההזמנה, ואפשר גם ליצור עמוד ייעודי עם השורטקוד [kindi_tracking] — טופס בדיקת סטטוס לפי מספר הזמנה וטלפון.' ),
			'lionwheel_member' => array( 'type' => 'text', 'label' => 'מזהה חברה (Member ID)', 'help' => 'מזהה החברה בליונוויל. ברירת מחדל: 118376.' ),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_lw_settings' );

/**
 * The configured API key ('' = feature off).
 *
 * @return string
 */
function kindi_lw_key(): string {
	return trim( (string) kindi_opt( 'lionwheel_key' ) );
}

/**
 * Digits-only Israeli phone (international 972 prefix folded to local 0).
 *
 * @param string $phone Raw phone.
 * @return string
 */
function kindi_lw_normalize_phone( string $phone ): string {
	$phone = (string) preg_replace( '/\D+/', '', $phone );
	if ( 0 === strpos( $phone, '972' ) ) {
		$phone = '0' . substr( $phone, 3 );
	}
	return $phone;
}

/**
 * LionWheel status code => Hebrew label.
 *
 * @param int $status Status code.
 * @return string
 */
function kindi_lw_status_label( int $status ): string {
	$statuses = array(
		0  => 'המשלוח התקבל וממתין לשיבוץ',
		1  => 'המשלוח שובץ לשליח',
		2  => 'המשלוח בדרך אליכם',
		3  => 'המשלוח נמסר',
		4  => 'המשלוח בוטל',
		5  => 'המשלוח נמסר וחוזר לנקודת המוצא',
		6  => 'המשלוח נמצא במחסן',
		7  => 'המשלוח יצא מהמחסן',
		8  => 'ניסיון המסירה לא הצליח',
		9  => 'המשלוח לא נמסר',
		10 => 'המשלוח נמצא בהעברה',
	);
	return $statuses[ $status ] ?? 'סטטוס המשלוח מתעדכן';
}

/**
 * Pull the task object out of the possible LionWheel response shapes.
 *
 * @param array<mixed> $response Decoded response.
 * @return array<string,mixed>|null
 */
function kindi_lw_extract_task( array $response ): ?array {
	if ( isset( $response['status'] ) ) {
		return $response;
	}
	if ( isset( $response['task'] ) && is_array( $response['task'] ) ) {
		return $response['task'];
	}
	if ( isset( $response['data']['status'] ) && is_array( $response['data'] ) ) {
		return $response['data'];
	}
	if ( isset( $response['data']['task'] ) && is_array( $response['data']['task'] ) ) {
		return $response['data']['task'];
	}
	if ( isset( $response[0] ) && is_array( $response[0] ) ) {
		return kindi_lw_extract_task( $response[0] );
	}
	return null;
}

/**
 * Fetch (with a 2-minute cache) the LionWheel task of an order.
 *
 * @param int $order_id Order ID.
 * @return array<string,mixed>|null|WP_Error Task, null when no shipment
 *                                           exists, WP_Error on API failure.
 */
function kindi_lw_task( int $order_id ) {
	$cache_key = 'kindi_lw_' . $order_id;
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return empty( $cached ) ? null : $cached; // Empty array = cached "no shipment".
	}

	$url = add_query_arg(
		array(
			'key'       => kindi_lw_key(),
			'member_id' => rawurlencode( trim( (string) kindi_opt( 'lionwheel_member' ) ) ),
		),
		'https://members.lionwheel.com/api/v1/tasks/by_order_id/' . rawurlencode( (string) $order_id )
	);

	$response = wp_remote_get( $url, array( 'timeout' => 15, 'headers' => array( 'Accept' => 'application/json' ) ) );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( 404 === $code ) {
		set_transient( $cache_key, array(), 2 * MINUTE_IN_SECONDS );
		return null;
	}
	if ( 200 !== $code || ! is_array( $body ) ) {
		return new WP_Error( 'kindi_lw_bad_response', 'bad response', array( 'code' => $code ) );
	}

	$task = kindi_lw_extract_task( $body );
	set_transient( $cache_key, is_array( $task ) ? $task : array(), 2 * MINUTE_IN_SECONDS );

	return $task;
}

/**
 * A task reduced to the fields the front end shows.
 *
 * @param array<string,mixed> $task Task.
 * @return array{status:int,status_label:string,updated_at:string,tracking_link:string}
 */
function kindi_lw_task_view( array $task ): array {
	$status  = isset( $task['status'] ) ? (int) $task['status'] : -1;
	$updated = '';
	if ( ! empty( $task['updated_at'] ) ) {
		$updated = sanitize_text_field( (string) $task['updated_at'] );
	} elseif ( ! empty( $task['pickup_at'] ) ) {
		$updated = sanitize_text_field( (string) $task['pickup_at'] );
	}
	return array(
		'status'        => $status,
		'status_label'  => kindi_lw_status_label( $status ),
		'updated_at'    => $updated,
		'tracking_link' => ! empty( $task['tracking_link'] ) ? esc_url_raw( (string) $task['tracking_link'] ) : '',
	);
}

/* ------------------------------------------------------------------ *
 * AJAX lookup (shortcode form)
 * ------------------------------------------------------------------ */

/**
 * Generic response so order existence is never exposed.
 *
 * @return void
 */
function kindi_lw_not_found(): void {
	wp_send_json_error( array( 'message' => 'לא נמצאה הזמנה התואמת לפרטים שהוזנו.' ), 404 );
}

/**
 * Per-IP rate limit: 15 lookups per 5 minutes.
 *
 * @return void
 */
function kindi_lw_rate_limit(): void {
	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key      = 'kindi_lw_rate_' . md5( $ip );
	$attempts = (int) get_transient( $key );
	if ( $attempts >= 15 ) {
		wp_send_json_error( array( 'message' => 'בוצעו יותר מדי ניסיונות. נסו שוב בעוד מספר דקות.' ), 429 );
	}
	set_transient( $key, $attempts + 1, 5 * MINUTE_IN_SECONDS );
}

/**
 * AJAX: order + phone => shipment status.
 *
 * @return void
 */
function kindi_lw_lookup(): void {
	check_ajax_referer( 'kindi_lw_lookup', 'nonce' );

	if ( '' === kindi_lw_key() ) {
		wp_send_json_error( array( 'message' => 'שירות המעקב עדיין אינו מוגדר באתר.' ), 503 );
	}
	kindi_lw_rate_limit();

	$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;
	$phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	if ( ! $order_id || '' === $phone ) {
		wp_send_json_error( array( 'message' => 'יש להזין מספר הזמנה ומספר טלפון.' ), 400 );
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		kindi_lw_not_found();
	}

	// The submitted phone must match the order's billing OR shipping phone.
	$submitted = kindi_lw_normalize_phone( $phone );
	$known     = array_filter(
		array(
			kindi_lw_normalize_phone( (string) $order->get_billing_phone() ),
			kindi_lw_normalize_phone( (string) $order->get_shipping_phone() ),
		)
	);
	$match = false;
	foreach ( $known as $candidate ) {
		if ( '' !== $submitted && hash_equals( $candidate, $submitted ) ) {
			$match = true;
			break;
		}
	}
	if ( ! $match ) {
		kindi_lw_not_found();
	}

	$task = kindi_lw_task( $order_id );
	if ( is_wp_error( $task ) ) {
		wp_send_json_error( array( 'message' => 'לא ניתן לקבל כרגע את נתוני המשלוח. נסו שוב מאוחר יותר.' ), 502 );
	}
	if ( null === $task ) {
		wp_send_json_error( array( 'message' => 'עדיין לא נוצר משלוח להזמנה זו.' ), 404 );
	}

	wp_send_json_success( kindi_lw_task_view( $task ) );
}
add_action( 'wp_ajax_kindi_lw_lookup', 'kindi_lw_lookup' );
add_action( 'wp_ajax_nopriv_kindi_lw_lookup', 'kindi_lw_lookup' );

/* ------------------------------------------------------------------ *
 * Shortcode — dedicated status page
 * ------------------------------------------------------------------ */

/**
 * `[kindi_tracking]` — order-status form.
 *
 * @return string
 */
function kindi_lw_shortcode(): string {
	if ( ! function_exists( 'wc_get_order' ) ) {
		return '';
	}
	ob_start();
	?>
	<div class="kindi-track" data-kindi-track data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'kindi_lw_lookup' ) ); ?>">
		<form class="kindi-track__form">
			<p class="kindi-track__field">
				<label for="kindi-track-order"><?php esc_html_e( 'מספר הזמנה', 'kindi' ); ?></label>
				<input type="text" id="kindi-track-order" name="order_id" inputmode="numeric" placeholder="<?php esc_attr_e( 'לדוגמה: 12345', 'kindi' ); ?>" required>
			</p>
			<p class="kindi-track__field">
				<label for="kindi-track-phone"><?php esc_html_e( 'מספר טלפון', 'kindi' ); ?></label>
				<input type="tel" id="kindi-track-phone" name="phone" inputmode="tel" autocomplete="tel" placeholder="<?php esc_attr_e( 'הטלפון שהוזן בהזמנה', 'kindi' ); ?>" required>
			</p>
			<button type="submit" class="kindi-btn kindi-btn--red"><?php esc_html_e( 'בדיקת סטטוס המשלוח', 'kindi' ); ?></button>
		</form>
		<div class="kindi-track__result" aria-live="polite"></div>
	</div>
	<?php
	kindi_lw_shortcode_assets();
	return (string) ob_get_clean();
}
add_shortcode( 'kindi_tracking', 'kindi_lw_shortcode' );

/**
 * Inline styles + vanilla JS for the shortcode (printed once). The result is
 * built with DOM APIs — no HTML-string injection.
 *
 * @return void
 */
function kindi_lw_shortcode_assets(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	?>
	<style>
	.kindi-track{max-width:28rem}
	.kindi-track__field{display:flex;flex-direction:column;gap:.3rem;margin:0 0 .9rem}
	.kindi-track__field label{font-weight:700;color:var(--brand-navy);font-size:.9rem}
	.kindi-track__field input{border:1px solid var(--border);border-radius:.75rem;padding:.6rem .85rem;font-family:inherit;font-size:.95rem}
	.kindi-track__form .kindi-btn[disabled]{opacity:.6;cursor:wait}
	.kindi-track__result{margin-top:1rem}
	.kindi-track__box{border-radius:1rem;padding:1rem 1.15rem;font-size:.95rem;line-height:1.6}
	.kindi-track__box--ok{background:var(--brand-blue-soft);border:1px solid color-mix(in oklab, var(--brand-blue) 30%, transparent)}
	.kindi-track__box--err{background:var(--brand-red-soft);border:1px solid color-mix(in oklab, var(--brand-red) 30%, transparent)}
	.kindi-track__status{font-weight:800;color:var(--brand-navy)}
	.kindi-track__updated{font-size:.85rem;color:var(--foreground);margin-top:.2rem}
	.kindi-track__link{display:inline-block;margin-top:.6rem;color:var(--brand-red);font-weight:700}
	</style>
	<script>
	( function () {
		var box = document.querySelector( '[data-kindi-track]' );
		if ( ! box ) { return; }
		var form = box.querySelector( '.kindi-track__form' );
		var btn = form.querySelector( 'button' );
		var result = box.querySelector( '.kindi-track__result' );

		function show( ok, fill ) {
			result.textContent = '';
			var card = document.createElement( 'div' );
			card.className = 'kindi-track__box kindi-track__box--' + ( ok ? 'ok' : 'err' );
			fill( card );
			result.appendChild( card );
		}
		function line( parent, cls, text ) {
			var el = document.createElement( 'div' );
			el.className = cls;
			el.textContent = text;
			parent.appendChild( el );
		}

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			btn.disabled = true;
			var body = new FormData( form );
			body.append( 'action', 'kindi_lw_lookup' );
			body.append( 'nonce', box.dataset.nonce );
			fetch( box.dataset.ajax, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					btn.disabled = false;
					if ( ! res || ! res.success ) {
						show( false, function ( card ) {
							card.textContent = ( res && res.data && res.data.message ) || 'לא ניתן לבדוק את המשלוח כרגע.';
						} );
						return;
					}
					show( true, function ( card ) {
						line( card, 'kindi-track__status', res.data.status_label );
						if ( res.data.updated_at ) {
							line( card, 'kindi-track__updated', 'עדכון אחרון: ' + res.data.updated_at );
						}
						if ( res.data.tracking_link && /^https?:\/\//.test( res.data.tracking_link ) ) {
							var a = document.createElement( 'a' );
							a.className = 'kindi-track__link';
							a.href = res.data.tracking_link;
							a.target = '_blank';
							a.rel = 'noopener noreferrer';
							a.textContent = 'למעקב מפורט אחר המשלוח';
							card.appendChild( a );
						}
					} );
				} )
				.catch( function () {
					btn.disabled = false;
					show( false, function ( card ) { card.textContent = 'לא ניתן לבדוק את המשלוח כרגע.'; } );
				} );
		} );
	}() );
	</script>
	<?php
}

/* ------------------------------------------------------------------ *
 * My Account — automatic status on the view-order page
 * ------------------------------------------------------------------ */

/**
 * Shipment status box at the top of My Account → view order. The endpoint is
 * already restricted to the order's owner by WooCommerce.
 *
 * @param int $order_id Order ID.
 * @return void
 */
function kindi_lw_view_order( $order_id ): void {
	if ( '' === kindi_lw_key() ) {
		return;
	}
	$task = kindi_lw_task( (int) $order_id );
	if ( is_wp_error( $task ) || null === $task ) {
		return; // No shipment (or API hiccup) — say nothing on the order page.
	}
	$view = kindi_lw_task_view( $task );

	echo '<div class="kindi-track__box kindi-track__box--ok kindi-track__account">';
	echo '<div class="kindi-track__status">' . esc_html( $view['status_label'] ) . '</div>';
	if ( '' !== $view['updated_at'] ) {
		echo '<div class="kindi-track__updated">' . esc_html( 'עדכון אחרון: ' . $view['updated_at'] ) . '</div>';
	}
	if ( '' !== $view['tracking_link'] ) {
		echo '<a class="kindi-track__link" href="' . esc_url( $view['tracking_link'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'למעקב מפורט אחר המשלוח', 'kindi' ) . '</a>';
	}
	echo '</div>';
	echo '<style>.kindi-track__account{margin:0 0 1.25rem;border-radius:1rem;padding:1rem 1.15rem;background:var(--brand-blue-soft);border:1px solid color-mix(in oklab, var(--brand-blue) 30%, transparent)}.kindi-track__account .kindi-track__status{font-weight:800;color:var(--brand-navy)}.kindi-track__account .kindi-track__updated{font-size:.85rem;margin-top:.2rem}.kindi-track__account .kindi-track__link{display:inline-block;margin-top:.6rem;color:var(--brand-red);font-weight:700}</style>';
}
add_action( 'woocommerce_view_order', 'kindi_lw_view_order', 5 );
