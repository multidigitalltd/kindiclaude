<?php
/**
 * Save & share cart.
 *
 * Persists a shopper's cart to the database under an unguessable token, returns
 * a shareable restore link, and sends two emails: one immediately on save and
 * one reminder after a configurable delay (skipped if the cart was already
 * restored). Managed from a "עגלות שמורות" tab under the Kindi menu.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const KINDI_SAVED_CART_CPT = 'kindi_saved_cart';

/**
 * Register the private storage post type.
 *
 * @return void
 */
function kindi_saved_cart_register(): void {
	register_post_type(
		KINDI_SAVED_CART_CPT,
		array(
			'public'              => false,
			'show_ui'             => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'supports'            => array( 'title' ),
		)
	);
}
add_action( 'init', 'kindi_saved_cart_register' );

/**
 * The share URL for a token.
 *
 * @param string $token Token.
 * @return string
 */
function kindi_saved_cart_url( string $token ): string {
	$base = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/' );
	return add_query_arg( 'kindi_cart', rawurlencode( $token ), $base );
}

/**
 * Render the "save cart" panel after the cart table.
 *
 * @return void
 */
function kindi_saved_cart_panel(): void {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}
	$email = '';
	if ( is_user_logged_in() ) {
		$user  = wp_get_current_user();
		$email = $user->user_email;
	}
	?>
	<div class="kindi-savecart" data-kindi-savecart>
		<div class="kindi-savecart__head">
			<?php echo kindi_icon( 'heart', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div>
				<strong><?php esc_html_e( 'שמרו את העגלה לזמן אחר', 'kindi' ); ?></strong>
				<span><?php esc_html_e( 'נשלח לכם קישור לשחזור — וגם תוכלו לשתף אותו', 'kindi' ); ?></span>
			</div>
		</div>
		<div class="kindi-savecart__row">
			<label class="screen-reader-text" for="kindi-savecart-email"><?php esc_html_e( 'אימייל (לקבלת הקישור)', 'kindi' ); ?></label>
			<input type="email" id="kindi-savecart-email" name="email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'אימייל (לקבלת הקישור)', 'kindi' ); ?>" data-kindi-savecart-email>
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'kindi_save_cart' ) ); ?>">
			<button type="button" class="kindi-btn kindi-btn--navy" data-kindi-savecart-btn><?php esc_html_e( 'שמירת עגלה', 'kindi' ); ?></button>
		</div>
		<div class="kindi-savecart__result" data-kindi-savecart-result hidden aria-live="polite"></div>
	</div>
	<?php
}
add_action( 'woocommerce_after_cart', 'kindi_saved_cart_panel' );

/**
 * AJAX: save the current cart.
 *
 * @return void
 */
function kindi_saved_cart_save(): void {
	check_ajax_referer( 'kindi_save_cart', 'nonce' );

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		wp_send_json_error( array( 'message' => 'העגלה ריקה.' ), 400 );
	}

	$items = array();
	foreach ( WC()->cart->get_cart() as $item ) {
		$items[] = array(
			'product_id'   => (int) $item['product_id'],
			'variation_id' => (int) $item['variation_id'],
			'quantity'     => (int) $item['quantity'],
			'variation'    => is_array( $item['variation'] ) ? $item['variation'] : array(),
		);
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$token = wp_generate_password( 20, false );

	$post_id = wp_insert_post(
		array(
			'post_type'   => KINDI_SAVED_CART_CPT,
			'post_status' => 'publish',
			'post_title'  => 'Saved cart ' . gmdate( 'Y-m-d H:i' ) . ' · ' . $token,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'אירעה שגיאה. נסו שוב.' ), 500 );
	}

	update_post_meta( $post_id, '_kindi_cart_items', $items );
	update_post_meta( $post_id, '_kindi_cart_token', $token );
	update_post_meta( $post_id, '_kindi_cart_email', $email );
	update_post_meta( $post_id, '_kindi_cart_count', WC()->cart->get_cart_contents_count() );
	update_post_meta( $post_id, '_kindi_cart_total', WC()->cart->get_cart_total() );

	$url = kindi_saved_cart_url( $token );

	if ( is_email( $email ) ) {
		kindi_saved_cart_email( (int) $post_id, 'immediate' );

		$delay = max( 1, (int) kindi_opt( 'cart_reminder_delay', 24 ) ) * HOUR_IN_SECONDS;
		wp_schedule_single_event( time() + $delay, 'kindi_saved_cart_reminder', array( (int) $post_id ) );
	}

	wp_send_json_success(
		array(
			'url'     => $url,
			'message' => is_email( $email ) ? '✅ נשמר! שלחנו את הקישור למייל. אפשר גם להעתיק:' : '✅ העגלה נשמרה! העתיקו את הקישור לשחזור:',
		)
	);
}
add_action( 'wp_ajax_kindi_save_cart', 'kindi_saved_cart_save' );
add_action( 'wp_ajax_nopriv_kindi_save_cart', 'kindi_saved_cart_save' );

/**
 * Restore a saved cart from a share link.
 *
 * @return void
 */
function kindi_saved_cart_restore(): void {
	if ( empty( $_GET['kindi_cart'] ) || ! function_exists( 'WC' ) || ! WC()->cart ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	$token = sanitize_text_field( wp_unslash( $_GET['kindi_cart'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$found = get_posts(
		array(
			'post_type'      => KINDI_SAVED_CART_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => '_kindi_cart_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'no_found_rows'  => true,
		)
	);

	if ( ! $found ) {
		wc_add_notice( 'קישור העגלה אינו תקין או שפג תוקפו.', 'error' );
		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	$post  = $found[0];
	$items = get_post_meta( $post->ID, '_kindi_cart_items', true );

	WC()->cart->empty_cart();
	$restored = 0;
	if ( is_array( $items ) ) {
		foreach ( $items as $it ) {
			$ok = WC()->cart->add_to_cart(
				(int) $it['product_id'],
				max( 1, (int) $it['quantity'] ),
				(int) ( $it['variation_id'] ?? 0 ),
				is_array( $it['variation'] ?? null ) ? $it['variation'] : array()
			);
			if ( $ok ) {
				++$restored;
			}
		}
	}

	update_post_meta( $post->ID, '_kindi_cart_recovered', time() );

	wc_add_notice(
		$restored
			? sprintf( 'שחזרנו %d פריטים לעגלה שלך 🎉', $restored )
			: 'חלק מהפריטים בעגלה השמורה אינם זמינים יותר.',
		$restored ? 'success' : 'notice'
	);
	wp_safe_redirect( wc_get_cart_url() );
	exit;
}
add_action( 'template_redirect', 'kindi_saved_cart_restore' );

/**
 * Build the saved-cart items list as email-safe HTML.
 *
 * @param array<int,array<string,mixed>> $items Stored items.
 * @return string
 */
function kindi_saved_cart_items_html( array $items ): string {
	$rows = '';
	foreach ( $items as $it ) {
		$product = wc_get_product( (int) ( $it['variation_id'] ?: $it['product_id'] ) );
		if ( ! $product ) {
			continue;
		}
		$rows .= '<tr><td style="padding:7px 0;border-bottom:1px solid #eef1f6">'
			. esc_html( $product->get_name() ) . ' × ' . (int) $it['quantity'] . '</td></tr>';
	}
	return $rows ? '<table role="presentation" style="width:100%;margin:14px 0">' . $rows . '</table>' : '';
}

/**
 * Send a saved-cart email (immediate or reminder).
 *
 * @param int    $post_id Saved-cart post ID.
 * @param string $type    'immediate' | 'reminder'.
 * @return void
 */
function kindi_saved_cart_email( int $post_id, string $type ): void {
	$email = (string) get_post_meta( $post_id, '_kindi_cart_email', true );
	if ( ! is_email( $email ) || ! function_exists( 'kindi_send_html_mail' ) ) {
		return;
	}

	$token = (string) get_post_meta( $post_id, '_kindi_cart_token', true );
	$items = get_post_meta( $post_id, '_kindi_cart_items', true );
	$url   = kindi_saved_cart_url( $token );

	if ( 'reminder' === $type ) {
		$subject = (string) kindi_opt( 'cart_reminder_subject' );
		$body    = (string) kindi_opt( 'cart_reminder_body' );
	} else {
		$subject = (string) kindi_opt( 'cart_email_subject' );
		$body    = (string) kindi_opt( 'cart_email_body' );
	}

	$vars    = array( 'site' => get_bloginfo( 'name' ), 'url' => $url );
	$subject = kindi_email_fill( $subject, $vars );
	$body    = kindi_email_fill( $body, $vars );

	$inner = wpautop( esc_html( $body ) ) . ( is_array( $items ) ? kindi_saved_cart_items_html( $items ) : '' );
	$html  = kindi_email_template( $subject, $inner, array( 'label' => 'חזרה לעגלה שלי', 'url' => $url ) );

	kindi_send_html_mail( $email, $subject, $html );
}

/**
 * Cron: send the delayed reminder, unless already recovered or reminded.
 *
 * @param int $post_id Saved-cart post ID.
 * @return void
 */
function kindi_saved_cart_reminder_cb( int $post_id ): void {
	if ( get_post_meta( $post_id, '_kindi_cart_recovered', true ) || get_post_meta( $post_id, '_kindi_cart_reminded', true ) ) {
		return;
	}
	kindi_saved_cart_email( $post_id, 'reminder' );
	update_post_meta( $post_id, '_kindi_cart_reminded', time() );
}
add_action( 'kindi_saved_cart_reminder', 'kindi_saved_cart_reminder_cb' );

/**
 * Admin submenu: saved carts list.
 *
 * @return void
 */
function kindi_saved_cart_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'עגלות שמורות', 'kindi' ),
		__( 'עגלות שמורות', 'kindi' ),
		'manage_woocommerce',
		'kindi-saved-carts',
		'kindi_saved_cart_admin_page'
	);
}
add_action( 'admin_menu', 'kindi_saved_cart_menu' );

/**
 * Render the saved-carts admin table.
 *
 * @return void
 */
function kindi_saved_cart_admin_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$carts = get_posts(
		array(
			'post_type'      => KINDI_SAVED_CART_CPT,
			'post_status'    => 'publish',
			'posts_per_page' => 200,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	echo '<div class="wrap"><h1>' . esc_html__( 'עגלות שמורות', 'kindi' ) . '</h1>';
	echo '<table class="widefat striped"><thead><tr><th>תאריך</th><th>אימייל</th><th>פריטים</th><th>סכום</th><th>סטטוס</th><th>קישור שחזור</th></tr></thead><tbody>';

	if ( ! $carts ) {
		echo '<tr><td colspan="6">אין עגלות שמורות עדיין.</td></tr>';
	}
	foreach ( $carts as $cart ) {
		$token     = (string) get_post_meta( $cart->ID, '_kindi_cart_token', true );
		$recovered = (int) get_post_meta( $cart->ID, '_kindi_cart_recovered', true );
		$reminded  = (int) get_post_meta( $cart->ID, '_kindi_cart_reminded', true );
		$status    = $recovered ? '✅ שוחזרה' : ( $reminded ? '🔔 נשלחה תזכורת' : '⏳ ממתינה' );
		printf(
			'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td><a href="%s" target="_blank" rel="noopener">פתיחה ↗</a></td></tr>',
			esc_html( get_the_date( 'd/m/Y H:i', $cart ) ),
			esc_html( (string) get_post_meta( $cart->ID, '_kindi_cart_email', true ) ?: '—' ),
			esc_html( (string) get_post_meta( $cart->ID, '_kindi_cart_count', true ) ),
			wp_kses_post( (string) get_post_meta( $cart->ID, '_kindi_cart_total', true ) ),
			esc_html( $status ),
			esc_url( kindi_saved_cart_url( $token ) )
		);
	}
	echo '</tbody></table></div>';
}
