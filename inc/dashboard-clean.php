<?php
/**
 * Dashboard cleanup — choose which top-level admin menus (plugin tabs, WordPress
 * settings) are hidden, to give the site owner a tidy dashboard.
 *
 * Safety: hiding applies to everyone EXCEPT the exempt users (by email — the
 * agency), so you never lock your own menu away. Hiding only removes the menu
 * link; every page stays reachable by its direct URL. The Kindi menu and this
 * tool are never hidden.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Hidden top-level menu slugs.
 *
 * @return string[]
 */
function kindi_dashclean_hidden(): array {
	$v = get_option( 'kindi_hidden_menus', array() );
	return is_array( $v ) ? $v : array();
}

/**
 * Exempt user emails (always see the full menu), lowercased.
 *
 * @return string[]
 */
function kindi_dashclean_exempt(): array {
	$raw = (string) get_option( 'kindi_menu_exempt', '' );
	$out = array();
	foreach ( preg_split( '/[\s,]+/', $raw ) ?: array() as $e ) {
		$e = strtolower( trim( $e ) );
		if ( '' !== $e ) {
			$out[] = $e;
		}
	}
	return $out;
}

/**
 * Is the current user exempt (agency)? True when their email is on the list.
 *
 * @return bool
 */
function kindi_dashclean_is_exempt(): bool {
	$user = wp_get_current_user();
	if ( ! $user || ! $user->exists() ) {
		return false;
	}
	return in_array( strtolower( (string) $user->user_email ), kindi_dashclean_exempt(), true );
}

/**
 * May the current user open the cleanup tool? Yes for exempt users, or for any
 * admin while no exempt list exists yet (so it can be set up).
 *
 * @return bool
 */
function kindi_dashclean_can_manage(): bool {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	$exempt = kindi_dashclean_exempt();
	return empty( $exempt ) || kindi_dashclean_is_exempt();
}

/**
 * Register the tool under the Kindi menu (only for those who may manage it).
 *
 * @return void
 */
function kindi_dashclean_menu(): void {
	if ( ! kindi_dashclean_can_manage() ) {
		return;
	}
	add_submenu_page(
		'kindi-settings',
		__( 'ניקוי לוח בקרה', 'kindi' ),
		__( 'ניקוי לוח בקרה', 'kindi' ),
		'manage_options',
		'kindi-dashboard',
		'kindi_dashclean_page'
	);
}
add_action( 'admin_menu', 'kindi_dashclean_menu' );

/**
 * Remove the chosen menus for non-exempt users. Never runs on the tool's own
 * page (so the full list is always available to re-enable), and never hides the
 * Kindi menu.
 *
 * @return void
 */
function kindi_dashclean_apply(): void {
	if ( isset( $_GET['page'] ) && 'kindi-dashboard' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	if ( kindi_dashclean_is_exempt() ) {
		return;
	}
	foreach ( kindi_dashclean_hidden() as $slug ) {
		if ( 'kindi-settings' === $slug ) {
			continue;
		}
		remove_menu_page( $slug );
	}
}
add_action( 'admin_menu', 'kindi_dashclean_apply', 9999 );

/**
 * Render the cleanup screen.
 *
 * @return void
 */
function kindi_dashclean_page(): void {
	if ( ! kindi_dashclean_can_manage() ) {
		return;
	}

	if ( isset( $_POST['kindi_dashclean_save'] ) && check_admin_referer( 'kindi_dashclean' ) ) {
		$hide = isset( $_POST['hide'] ) ? (array) wp_unslash( $_POST['hide'] ) : array();
		$hide = array_values( array_filter( array_map( 'sanitize_text_field', $hide ), static function ( $s ) {
			return 'kindi-settings' !== $s;
		} ) );
		update_option( 'kindi_hidden_menus', $hide, false );

		$exempt = isset( $_POST['exempt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['exempt'] ) ) : '';
		update_option( 'kindi_menu_exempt', $exempt, false );

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'נשמר.', 'kindi' ) . '</p></div>';
	}

	global $menu;
	$hidden     = kindi_dashclean_hidden();
	$exempt_raw = (string) get_option( 'kindi_menu_exempt', '' );
	if ( '' === $exempt_raw ) {
		$exempt_raw = (string) wp_get_current_user()->user_email; // Prefill so setup can't lock you out.
	}

	echo '<div class="wrap"><h1>' . esc_html__( 'ניקוי לוח הבקרה', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'סמנו אילו תפריטים להסתיר מבעל האתר. ההסתרה חלה על כל המשתמשים חוץ מהאימיילים שברשימת הפטורים — כך אתם תמיד רואים הכל. הסתרה מסתירה רק את הקישור בתפריט; הדפים עצמם נגישים תמיד דרך הכתובת הישירה.', 'kindi' ) . '</p>';

	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_dashclean' );

	echo '<h2>' . esc_html__( 'משתמשים פטורים (רואים את התפריט המלא)', 'kindi' ) . '</h2>';
	echo '<textarea name="exempt" rows="2" class="large-text" dir="ltr" placeholder="name@example.com">' . esc_textarea( $exempt_raw ) . '</textarea>';
	echo '<p class="description">' . esc_html__( 'אימייל אחד בכל שורה (או מופרד בפסיקים). מומלץ להשאיר כאן את האימייל שלכם.', 'kindi' ) . '</p>';

	echo '<h2>' . esc_html__( 'תפריטים להסתרה', 'kindi' ) . '</h2>';
	echo '<table class="widefat striped" style="max-width:640px"><tbody>';

	foreach ( (array) $menu as $item ) {
		$slug  = isset( $item[2] ) ? (string) $item[2] : '';
		$title = isset( $item[0] ) ? trim( wp_strip_all_tags( (string) $item[0] ) ) : '';
		// Skip separators, empty rows, and the Kindi menu itself.
		if ( '' === $slug || '' === $title || false !== strpos( $slug, 'separator' ) || 'kindi-settings' === $slug ) {
			continue;
		}
		printf(
			'<tr><td><label><input type="checkbox" name="hide[]" value="%s"%s> %s</label> <code style="opacity:.6">%s</code></td></tr>',
			esc_attr( $slug ),
			checked( in_array( $slug, $hidden, true ), true, false ),
			esc_html( $title ),
			esc_html( $slug )
		);
	}

	echo '</tbody></table>';
	submit_button( __( 'שמירה', 'kindi' ), 'primary', 'kindi_dashclean_save' );
	echo '</form></div>';
}
