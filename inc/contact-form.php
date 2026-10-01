<?php
/**
 * Contact form — shortcode [kindi_contact_form].
 *
 * Same hardened pipeline as the cancellation form: posts to admin-post.php,
 * nonce (in the LiteSpeed ESI list) + honeypot + IP rate limit, emails the
 * store's contact address (branded template, Reply-To the sender) and
 * redirects back with an accessible status notice. Reuses the .kindi-cancel
 * form styling from content.css.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode: [kindi_contact_form].
 *
 * @return string
 */
function kindi_contact_form_shortcode(): string {
	$status = isset( $_GET['kindi_contact'] ) ? sanitize_key( wp_unslash( $_GET['kindi_contact'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only notice flag.

	ob_start();

	if ( 'sent' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--ok" role="status">' . esc_html__( 'ההודעה נשלחה בהצלחה! נחזור אליכם בהקדם.', 'kindi' ) . '</div>';
	} elseif ( 'missing' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'חסרים פרטים — יש למלא את כל שדות החובה ולנסות שוב.', 'kindi' ) . '</div>';
	} elseif ( 'limit' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'נשלחו יותר מדי הודעות. נסו שוב בעוד מספר דקות, או התקשרו אלינו.', 'kindi' ) . '</div>';
	} elseif ( 'error' === $status ) {
		echo '<div class="kindi-cancel__notice kindi-cancel__notice--err" role="alert">' . esc_html__( 'אירעה שגיאה בשליחה. נסו שוב, או צרו קשר טלפוני.', 'kindi' ) . '</div>';
	}

	if ( 'sent' !== $status ) :
		?>
		<form class="kindi-cancel kindi-contactform" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="kindi_contact">
			<?php wp_nonce_field( 'kindi_contact', 'kindi_contact_nonce' ); ?>
			<p class="kindi-hp" aria-hidden="true"><label>אל תמלאו שדה זה<input type="text" name="kindi_hp" tabindex="-1" autocomplete="off"></label></p>
			<input type="hidden" name="_kindi_back" value="<?php echo esc_url( get_permalink() ? (string) get_permalink() : home_url( '/' ) ); ?>">

			<div class="kindi-cancel__grid">
				<p class="kindi-cancel__field">
					<label for="kindi-contact-name"><?php esc_html_e( 'שם מלא', 'kindi' ); ?> <span class="kindi-cancel__req" aria-hidden="true">*</span></label>
					<input type="text" id="kindi-contact-name" name="full_name" required>
				</p>
				<p class="kindi-cancel__field">
					<label for="kindi-contact-phone"><?php esc_html_e( 'טלפון', 'kindi' ); ?> <span class="kindi-cancel__req" aria-hidden="true">*</span></label>
					<input type="tel" id="kindi-contact-phone" name="phone" required>
				</p>
				<p class="kindi-cancel__field">
					<label for="kindi-contact-email"><?php esc_html_e( 'אימייל', 'kindi' ); ?> <span class="kindi-cancel__req" aria-hidden="true">*</span></label>
					<input type="email" id="kindi-contact-email" name="email" required>
				</p>
				<p class="kindi-cancel__field">
					<label for="kindi-contact-subject"><?php esc_html_e( 'נושא הפנייה', 'kindi' ); ?></label>
					<input type="text" id="kindi-contact-subject" name="subject">
				</p>
				<p class="kindi-cancel__field kindi-cancel__field--wide">
					<label for="kindi-contact-message"><?php esc_html_e( 'תוכן ההודעה', 'kindi' ); ?> <span class="kindi-cancel__req" aria-hidden="true">*</span></label>
					<textarea id="kindi-contact-message" name="message" rows="5" required></textarea>
				</p>
			</div>

			<button type="submit" class="kindi-cancel__submit"><?php esc_html_e( 'שליחת הודעה', 'kindi' ); ?></button>
			<p class="kindi-cancel__note"><?php esc_html_e( 'אנחנו עונים לכל פנייה — בדרך כלל בתוך יום עסקים אחד.', 'kindi' ); ?></p>
		</form>
		<?php
	endif;

	return (string) ob_get_clean();
}
add_shortcode( 'kindi_contact_form', 'kindi_contact_form_shortcode' );

/**
 * Handle the submission: verify, sanitise, email the store, redirect back.
 *
 * @return void
 */
function kindi_contact_handle(): void {
	$back = isset( $_POST['_kindi_back'] ) ? esc_url_raw( wp_unslash( $_POST['_kindi_back'] ) ) : home_url( '/' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified below.
	$back = wp_validate_redirect( $back, home_url( '/' ) );

	$redirect = static function ( string $status ) use ( $back ): void {
		wp_safe_redirect( add_query_arg( 'kindi_contact', $status, $back ) );
		exit;
	};

	if ( ! isset( $_POST['kindi_contact_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['kindi_contact_nonce'] ) ), 'kindi_contact' ) ) {
		$redirect( 'error' );
	}

	// Honeypot — pretend success so bots stop retrying.
	if ( ! empty( $_POST['kindi_hp'] ) ) {
		$redirect( 'sent' );
	}

	// Rate limit: 3 per IP / 10 min.
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key  = 'kindi_contact_' . md5( $ip );
	$hits = (int) get_transient( $key );
	if ( $hits >= 3 ) {
		$redirect( 'limit' );
	}
	set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

	$name    = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email   = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$subject = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( '' === $name || '' === $phone || '' === $message || ! is_email( $email ) ) {
		$redirect( 'missing' );
	}

	$rows  = '<p style="margin:0 0 10px"><strong>שם:</strong><br>' . esc_html( $name ) . '</p>';
	$rows .= '<p style="margin:0 0 10px"><strong>טלפון:</strong><br>' . esc_html( $phone ) . '</p>';
	$rows .= '<p style="margin:0 0 10px"><strong>אימייל:</strong><br>' . esc_html( $email ) . '</p>';
	if ( '' !== $subject ) {
		$rows .= '<p style="margin:0 0 10px"><strong>נושא:</strong><br>' . esc_html( $subject ) . '</p>';
	}
	$rows .= '<p style="margin:0 0 10px"><strong>הודעה:</strong><br>' . nl2br( esc_html( $message ) ) . '</p>';

	$mail_subject = '' !== $subject
		? sprintf( 'פנייה חדשה מהאתר — %s', $subject )
		: sprintf( 'פנייה חדשה מהאתר — %s', $name );
	$html = function_exists( 'kindi_email_template' )
		? kindi_email_template( 'התקבלה פנייה חדשה מטופס יצירת הקשר', $rows )
		: $rows;

	// Deliver to the store's contact address, falling back to the admin email.
	$to = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'email', '' ) : '';
	if ( ! is_email( $to ) ) {
		$to = (string) get_option( 'admin_email' );
	}

	$sent = wp_mail(
		$to,
		$mail_subject,
		$html,
		array(
			'Content-Type: text/html; charset=UTF-8',
			'Reply-To: ' . $name . ' <' . $email . '>',
		)
	);

	$redirect( $sent ? 'sent' : 'error' );
}
add_action( 'admin_post_kindi_contact', 'kindi_contact_handle' );
add_action( 'admin_post_nopriv_kindi_contact', 'kindi_contact_handle' );
