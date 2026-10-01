<?php
/**
 * Transaction-cancellation form (ביטול עסקה) — consumer-protection compliant.
 *
 * Shortcode [kindi_cancel_form] renders the form (put it on the ביטול עסקה
 * page); submission posts to admin-post.php, is nonce + honeypot + rate-limit
 * protected, emails the site admin (branded template, Reply-To the customer)
 * and redirects back with a success/error notice. The nonce action is
 * registered with the LiteSpeed ESI list so cached pages keep a fresh nonce.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * The form fields: key => [label, type, required].
 *
 * @return array<string,array{label:string,type:string,required:bool}>
 */
function kindi_cancel_fields(): array {
	return array(
		'full_name' => array( 'label' => 'שם מלא', 'type' => 'text', 'required' => true ),
		'id_number' => array( 'label' => 'מספר תעודת זהות', 'type' => 'text', 'required' => true ),
		'phone'     => array( 'label' => 'טלפון', 'type' => 'tel', 'required' => true ),
		'email'     => array( 'label' => 'אימייל', 'type' => 'email', 'required' => true ),
		'order_no'  => array( 'label' => 'מספר הזמנה', 'type' => 'text', 'required' => true ),
		'buy_date'  => array( 'label' => 'תאריך הרכישה', 'type' => 'date', 'required' => false ),
		'details'   => array( 'label' => 'פירוט המוצרים / העסקה לביטול', 'type' => 'textarea', 'required' => true ),
		'reason'    => array( 'label' => 'סיבת הביטול (לא חובה)', 'type' => 'textarea', 'required' => false ),
	);
}

/**
 * Shortcode: [kindi_cancel_form].
 *
 * @return string
 */
function kindi_cancel_form_shortcode(): string {
	$status = isset( $_GET['kindi_cancel'] ) ? sanitize_key( wp_unslash( $_GET['kindi_cancel'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.

	ob_start();

	if ( 'sent' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--ok" role="status">' . esc_html__( 'בקשת הביטול נשלחה בהצלחה. ניצור איתך קשר בהקדם לאישור הטיפול.', 'kindi' ) . '</div>';
	} elseif ( 'missing' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'חסרים פרטים בטופס — יש למלא את כל שדות החובה ולנסות שוב.', 'kindi' ) . '</div>';
	} elseif ( 'limit' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'נשלחו יותר מדי בקשות. נסו שוב בעוד מספר דקות, או צרו קשר טלפוני.', 'kindi' ) . '</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'אירעה שגיאה בשליחה. נסו שוב, או צרו איתנו קשר ישירות.', 'kindi' ) . '</div>';
	}

	if ( 'sent' !== $status ) :
		?>
		<form class="kindi-cancel" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kindi_cancel">
			<?php wp_nonce_field( 'kindi_cancel', 'kindi_cancel_nonce' ); ?>
			<?php /* Honeypot — hidden from humans, bots fill it. */ ?>
			<p class="kindi-hp" aria-hidden="true"><label>אל תמלאו שדה זה<input type="text" name="kindi_hp" tabindex="-1" autocomplete="off"></label></p>
			<input type="hidden" name="_kindi_back" value="<?php echo esc_url( get_permalink() ? (string) get_permalink() : home_url( '/' ) ); ?>">

			<div class="kindi-cancel__grid">
				<?php foreach ( kindi_cancel_fields() as $kindi_key => $kindi_f ) : ?>
				<p class="kindi-cancel__field<?php echo 'textarea' === $kindi_f['type'] ? ' kindi-cancel__field--wide' : ''; ?>">
					<label for="kindi-cancel-<?php echo esc_attr( $kindi_key ); ?>">
						<?php echo esc_html( $kindi_f['label'] ); ?><?php echo $kindi_f['required'] ? ' <span class="kindi-cancel__req" aria-hidden="true">*</span>' : ''; ?>
					</label>
					<?php if ( 'textarea' === $kindi_f['type'] ) : ?>
					<textarea id="kindi-cancel-<?php echo esc_attr( $kindi_key ); ?>" name="<?php echo esc_attr( $kindi_key ); ?>" rows="4" <?php echo $kindi_f['required'] ? 'required' : ''; ?>></textarea>
					<?php else : ?>
					<input type="<?php echo esc_attr( $kindi_f['type'] ); ?>" id="kindi-cancel-<?php echo esc_attr( $kindi_key ); ?>" name="<?php echo esc_attr( $kindi_key ); ?>" <?php echo $kindi_f['required'] ? 'required' : ''; ?><?php echo 'id_number' === $kindi_key ? ' inputmode="numeric" minlength="5" maxlength="9"' : ''; ?>>
					<?php endif; ?>
				</p>
				<?php endforeach; ?>
			</div>

			<button type="submit" class="kindi-cancel__submit"><?php esc_html_e( 'שליחת בקשת ביטול', 'kindi' ); ?></button>
			<p class="kindi-cancel__note"><?php esc_html_e( 'לאחר השליחה תישלח הבקשה לצוות החנות ותטופל בהתאם לתקנון האתר ולהוראות חוק הגנת הצרכן.', 'kindi' ); ?></p>
		</form>
		<?php
	endif;

	return (string) ob_get_clean();
}
add_shortcode( 'kindi_cancel_form', 'kindi_cancel_form_shortcode' );

/**
 * Handle the submission: verify, sanitise, email the admin, redirect back.
 *
 * @return void
 */
function kindi_cancel_handle(): void {
	$back = isset( $_POST['_kindi_back'] ) ? esc_url_raw( wp_unslash( $_POST['_kindi_back'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
	$back = wp_validate_redirect( $back, home_url( '/' ) );

	$redirect = static function ( string $status ) use ( $back ): void {
		wp_safe_redirect( add_query_arg( 'kindi_cancel', $status, $back ) );
		exit;
	};

	if ( ! isset( $_POST['kindi_cancel_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kindi_cancel_nonce'] ) ), 'kindi_cancel' ) ) {
		$redirect( 'error' );
	}

	// Honeypot — pretend success so bots stop retrying.
	if ( ! empty( $_POST['kindi_hp'] ) ) {
		$redirect( 'sent' );
	}

	// Rate limit: 3 per IP / 10 min.
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key  = 'kindi_cancel_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 3 ) {
		$redirect( 'limit' );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$values = array();
	foreach ( kindi_cancel_fields() as $field_key => $field ) {
		$raw = isset( $_POST[ $field_key ] ) ? wp_unslash( $_POST[ $field_key ] ) : '';
		if ( 'email' === $field['type'] ) {
			$value = sanitize_email( $raw );
		} elseif ( 'textarea' === $field['type'] ) {
			$value = sanitize_textarea_field( $raw );
		} else {
			$value = sanitize_text_field( $raw );
		}
		if ( $field['required'] && '' === $value ) {
			$redirect( 'missing' );
		}
		$values[ $field_key ] = $value;
	}
	if ( ! is_email( $values['email'] ) ) {
		$redirect( 'missing' );
	}

	// Branded admin email, Reply-To the customer.
	$rows = '';
	foreach ( kindi_cancel_fields() as $field_key => $field ) {
		if ( '' === $values[ $field_key ] ) {
			continue;
		}
		$rows .= '<p style="margin:0 0 10px"><strong>' . esc_html( $field['label'] ) . ':</strong><br>' . nl2br( esc_html( $values[ $field_key ] ) ) . '</p>';
	}
	$subject = sprintf( 'בקשת ביטול עסקה — הזמנה %s', $values['order_no'] );
	$html    = function_exists( 'kindi_email_template' )
		? kindi_email_template( 'התקבלה בקשת ביטול עסקה', $rows )
		: $rows;

	// Deliver to the store's contact address (the one shown in the footer),
	// falling back to the WordPress admin email.
	$to = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'email', '' ) : '';
	if ( ! is_email( $to ) ) {
		$to = (string) get_option( 'admin_email' );
	}
	$sent = wp_mail(
		$to,
		$subject,
		$html,
		array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $values['full_name'] . ' <' . $values['email'] . '>',
		)
	);

	$redirect( $sent ? 'sent' : 'error' );
}
add_action( 'admin_post_kindi_cancel', 'kindi_cancel_handle' );
add_action( 'admin_post_nopriv_kindi_cancel', 'kindi_cancel_handle' );
