<?php
/**
 * Back-in-stock waitlist — visitors register on out-of-stock products and get
 * an email when restocked. Per-product meta storage, nonce + honeypot + rate
 * limiting, restock notifications, and an admin list.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Show the signup form on out-of-stock single products.
 *
 * @return void
 */
function kindi_waitlist_form(): void {
	global $product;
	if ( ! $product || $product->is_in_stock() ) {
		return;
	}
	?>
	<form class="kindi-waitlist" data-kindi-waitlist>
		<h3><?php echo kindi_icon( 'truck', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?> אזל מהמלאי — קבלו התראה כשחוזר</h3>
		<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product->get_id() ); ?>">
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'kindi_waitlist' ) ); ?>">
		<input type="text" name="kindi_hp" class="kindi-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
		<div class="kindi-waitlist__row">
			<label class="screen-reader-text" for="kindi-wl-name">השם שלכם</label>
			<input type="text" id="kindi-wl-name" name="name" placeholder="השם שלכם" required>
			<label class="screen-reader-text" for="kindi-wl-email">אימייל</label>
			<input type="email" id="kindi-wl-email" name="email" placeholder="אימייל" required>
		</div>
		<label class="kindi-waitlist__terms"><input type="checkbox" name="terms" required> אני מאשר/ת קבלת עדכון על המוצר</label>
		<button type="submit" class="kindi-btn kindi-btn--red">עדכנו אותי כשחוזר</button>
		<p class="kindi-waitlist__msg" aria-live="polite"></p>
	</form>
	<?php
}
add_action( 'woocommerce_single_product_summary', 'kindi_waitlist_form', 31 );

/**
 * AJAX signup handler.
 *
 * @return void
 */
function kindi_waitlist_signup(): void {
	check_ajax_referer( 'kindi_waitlist', 'nonce' );

	// Honeypot — silently accept bots.
	if ( ! empty( $_POST['kindi_hp'] ) ) {
		wp_send_json_success( array( 'message' => 'תודה!' ) );
	}

	// Rate limit: 5 per IP / 10 min.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'kindi_wl_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 5 ) {
		wp_send_json_error( array( 'message' => 'יותר מדי בקשות. נסו שוב בעוד מספר דקות.' ), 429 );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$pid   = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	$name  = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! $pid || ! is_email( $email ) || empty( $_POST['terms'] ) ) {
		wp_send_json_error( array( 'message' => 'אנא מלאו שם, אימייל תקין ואישור.' ), 400 );
	}

	$product = wc_get_product( $pid );
	if ( ! $product || $product->is_in_stock() ) {
		wp_send_json_error( array( 'message' => 'המוצר זמין במלאי כעת.' ), 400 );
	}

	$list = get_post_meta( $pid, '_kindi_waitlist', true );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	foreach ( $list as $entry ) {
		if ( isset( $entry['email'] ) && $entry['email'] === $email ) {
			wp_send_json_success( array( 'message' => 'כבר נרשמת לעדכון 👍' ) );
		}
	}

	$list[] = array(
		'name'     => $name,
		'email'    => $email,
		'time'     => time(),
		'notified' => 0,
	);
	update_post_meta( $pid, '_kindi_waitlist', $list );

	wp_send_json_success( array( 'message' => 'מעולה! נעדכן אותך במייל כשהמוצר יחזור.' ) );
}
add_action( 'wp_ajax_kindi_waitlist', 'kindi_waitlist_signup' );
add_action( 'wp_ajax_nopriv_kindi_waitlist', 'kindi_waitlist_signup' );

/**
 * Notify the waitlist when a product is restocked.
 *
 * @param int        $product_id Product ID.
 * @param string     $status     New stock status.
 * @param WC_Product $product    Product.
 * @return void
 */
function kindi_waitlist_restock( $product_id, $status, $product ): void {
	if ( 'instock' !== $status ) {
		return;
	}
	$list = get_post_meta( $product_id, '_kindi_waitlist', true );
	if ( ! is_array( $list ) || ! $list ) {
		return;
	}

	$title   = get_the_title( $product_id );
	$url     = get_permalink( $product_id );
	$changed = false;

	$subject_tpl = (string) kindi_opt( 'wl_email_subject' );
	$body_tpl    = (string) kindi_opt( 'wl_email_body' );

	foreach ( $list as &$entry ) {
		if ( ! empty( $entry['notified'] ) ) {
			continue;
		}
		$vars = array(
			'name'    => (string) ( $entry['name'] ?? '' ),
			'product' => $title,
			'url'     => $url,
		);
		$subject = kindi_email_fill( $subject_tpl, $vars );
		$body    = kindi_email_fill( $body_tpl, $vars );

		if ( function_exists( 'kindi_send_html_mail' ) ) {
			$html = kindi_email_template(
				$subject,
				wpautop( esc_html( $body ) ),
				array( 'label' => 'לרכישת המוצר', 'url' => $url )
			);
			kindi_send_html_mail( $entry['email'], $subject, $html );
		} else {
			wp_mail( $entry['email'], $subject, $body );
		}

		$entry['notified'] = time();
		$changed           = true;
	}
	unset( $entry );

	if ( $changed ) {
		update_post_meta( $product_id, '_kindi_waitlist', $list );
	}
}
add_action( 'woocommerce_product_set_stock_status', 'kindi_waitlist_restock', 20, 3 );

/**
 * Admin submenu listing waitlist entries.
 *
 * @return void
 */
function kindi_waitlist_admin_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'רשימת המתנה', 'kindi' ),
		__( 'רשימת המתנה', 'kindi' ),
		'manage_woocommerce',
		'kindi-waitlist',
		'kindi_waitlist_admin_page'
	);
}
add_action( 'admin_menu', 'kindi_waitlist_admin_menu' );

/**
 * Render the waitlist admin table.
 *
 * @return void
 */
function kindi_waitlist_admin_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	$rows = get_posts(
		array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'posts_per_page'         => 200,
			'meta_key'               => '_kindi_waitlist', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	echo '<div class="wrap"><h1>' . esc_html__( 'רשימת המתנה — חזרה למלאי', 'kindi' ) . '</h1>';
	echo '<table class="widefat striped"><thead><tr><th>מוצר</th><th>שם</th><th>אימייל</th><th>תאריך</th><th>סטטוס</th></tr></thead><tbody>';

	$found = false;
	foreach ( $rows as $row ) {
		$list = get_post_meta( $row->ID, '_kindi_waitlist', true );
		if ( ! is_array( $list ) ) {
			continue;
		}
		foreach ( $list as $entry ) {
			$found = true;
			printf(
				'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
				esc_url( get_edit_post_link( $row->ID ) ),
				esc_html( get_the_title( $row->ID ) ),
				esc_html( $entry['name'] ?? '' ),
				esc_html( $entry['email'] ?? '' ),
				esc_html( ! empty( $entry['time'] ) ? wp_date( 'd/m/Y', (int) $entry['time'] ) : '' ),
				// Text-only status (no emoji — the theme strips WP's emoji script,
				// leaving broken image fallbacks in admin tables).
				! empty( $entry['notified'] )
					? '<span style="color:#15803d;font-weight:600">עודכן</span>'
					: '<span style="color:#996800;font-weight:600">ממתין</span>'
			);
		}
	}
	if ( ! $found ) {
		echo '<tr><td colspan="5">אין רישומים כרגע.</td></tr>';
	}
	echo '</tbody></table></div>';
}
