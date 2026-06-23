<?php
/**
 * Admin content panel — "קינדי" settings screen.
 *
 * A native, dependency-free control panel (Settings-style) for every editable
 * string and link on the site. Saves into the single `kindi_options` option.
 * Security: capability check + nonce on save; each field sanitised by type.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tabs → sections → fields. Frequently-changed content lives in the "מבצעים
 * ותוכן" tab; rarely-touched section copy is tucked under "טקסטים והגדרות".
 *
 * @return array<string,array<string,mixed>>
 */
function kindi_settings_tabs(): array {
	return array(
		'promos'   => array(
			'label'    => 'מבצעים ותוכן',
			'sections' => array(
				'רצועות עליונות' => array(
					'shipbar' => array( 'type' => 'text', 'label' => 'רצועת המשלוח (מתחת ללוגו)' ),
					'ticker'  => array( 'type' => 'textarea', 'label' => 'רצועת מבצעים נעה', 'help' => 'שורה אחת לכל פריט' ),
				),
				'באנר מבצע גדול' => array(
					'promo1_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo1_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo1_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo1_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo1_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 2'    => array(
					'promo2_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo2_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo2_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo2_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo2_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 3'    => array(
					'promo3_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo3_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo3_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo3_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo3_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר ראשי (Hero)' => array(
					'hero_badge'    => array( 'type' => 'text', 'label' => 'תגית עליונה' ),
					'hero_title1'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 1' ),
					'hero_hl'       => array( 'type' => 'text', 'label' => 'כותרת — חלק מודגש (אדום)' ),
					'hero_title2'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 2 (כחול)' ),
					'hero_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'hero_cta1'     => array( 'type' => 'text', 'label' => 'כפתור ראשי — טקסט' ),
					'hero_cta1_url' => array( 'type' => 'url', 'label' => 'כפתור ראשי — קישור' ),
					'hero_cta2'     => array( 'type' => 'text', 'label' => 'כפתור משני — טקסט' ),
					'hero_cta2_url' => array( 'type' => 'url', 'label' => 'כפתור משני — קישור' ),
				),
			),
		),
		'texts'    => array(
			'label'    => 'טקסטים והגדרות',
			'sections' => array(
				'כללי'         => array(
					'phone'         => array( 'type' => 'text', 'label' => 'טלפון' ),
					'whatsapp'      => array( 'type' => 'text', 'label' => 'מספר וואטסאפ (בינלאומי, ללא +)', 'help' => 'לדוגמה: 972500000000' ),
					'free_shipping' => array( 'type' => 'number', 'label' => 'סף משלוח חינם (₪)' ),
				),
				'מועדון קינדי' => array(
					'club_title'    => array( 'type' => 'text', 'label' => 'כותרת' ),
					'club_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'club_benefits' => array( 'type' => 'textarea', 'label' => 'הטבות', 'help' => 'שורה אחת לכל הטבה' ),
					'club_cta_url'  => array( 'type' => 'url', 'label' => 'קישור הצטרפות' ),
				),
				'פרטי החנות'   => array(
					'store_address' => array( 'type' => 'text', 'label' => 'כתובת' ),
					'store_phone'   => array( 'type' => 'text', 'label' => 'טלפון בחנות' ),
					'store_hours'   => array( 'type' => 'text', 'label' => 'שעות פתיחה' ),
					'store_waze'    => array( 'type' => 'url', 'label' => 'קישור ניווט (Waze)' ),
					'news_title'    => array( 'type' => 'text', 'label' => 'ניוזלטר — כותרת' ),
					'news_sub'      => array( 'type' => 'textarea', 'label' => 'ניוזלטר — תיאור' ),
				),
				'פוטר ורשתות'  => array(
					'about' => array( 'type' => 'textarea', 'label' => 'טקסט "אודות"' ),
					'email' => array( 'type' => 'text', 'label' => 'אימייל' ),
					'fb'    => array( 'type' => 'url', 'label' => 'קישור פייסבוק' ),
					'ig'    => array( 'type' => 'url', 'label' => 'קישור אינסטגרם' ),
				),
			),
		),
	);
}

/**
 * Flat map of every field key → its definition (for saving/sanitising).
 *
 * @return array<string,array{type:string,label:string,help?:string}>
 */
function kindi_settings_all_fields(): array {
	$all = array();
	foreach ( kindi_settings_tabs() as $tab ) {
		foreach ( $tab['sections'] as $fields ) {
			$all += $fields;
		}
	}
	return $all;
}

/**
 * Sanitise a value according to its field type.
 *
 * @param string $type  Field type.
 * @param mixed  $value Raw value.
 * @return mixed
 */
function kindi_sanitize_field( string $type, $value ) {
	switch ( $type ) {
		case 'number':
			return absint( $value );
		case 'url':
			return esc_url_raw( trim( (string) $value ) );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Register the admin menu page.
 *
 * @return void
 */
function kindi_settings_menu(): void {
	add_menu_page(
		__( 'קינדי — תוכן האתר', 'kindi' ),
		__( 'קינדי', 'kindi' ),
		'manage_options',
		'kindi-settings',
		'kindi_settings_render',
		'dashicons-store',
		59
	);
}
add_action( 'admin_menu', 'kindi_settings_menu' );

/**
 * Handle save + render the settings screen.
 *
 * @return void
 */
function kindi_settings_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tabs    = kindi_settings_tabs();
	$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $tabs[ $current ] ) ) {
		$current = 'promos';
	}
	$saved = false;

	if ( isset( $_POST['kindi_settings_submit'] ) && check_admin_referer( 'kindi_save_settings', 'kindi_nonce' ) ) {
		$clean = get_option( 'kindi_options', array() );
		if ( ! is_array( $clean ) ) {
			$clean = array();
		}

		// Only persist fields actually submitted (the active tab), so other
		// tabs' values are never wiped.
		foreach ( kindi_settings_all_fields() as $key => $field ) {
			if ( ! isset( $_POST['kindi'][ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = kindi_sanitize_field( $field['type'], wp_unslash( $_POST['kindi'][ $key ] ) );
		}

		update_option( 'kindi_options', $clean );
		if ( function_exists( 'kindi_flush_store_cache' ) ) {
			kindi_flush_store_cache();
		}
		$saved = true;
	}

	echo '<div class="wrap kindi-settings"><h1>' . esc_html__( 'קינדי — ניהול תוכן האתר', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'קטגוריות ומוצרים נמשכים אוטומטית מ-WooCommerce. כאן עורכים את תוכן השיווק והטקסטים.', 'kindi' ) . '</p>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'התוכן נשמר.', 'kindi' ) . '</p></div>';
	}

	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $slug => $tab ) {
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=kindi-settings&tab=' . $slug ) ),
			$slug === $current ? ' nav-tab-active' : '',
			esc_html( $tab['label'] )
		);
	}
	echo '</h2>';

	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_save_settings', 'kindi_nonce' );

	foreach ( $tabs[ $current ]['sections'] as $section => $fields ) {
		echo '<h2 style="margin-top:2em">' . esc_html( $section ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $key => $field ) {
			$value = kindi_opt( $key );
			$id    = 'kindi_' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

			if ( 'textarea' === $field['type'] ) {
				printf(
					'<textarea id="%1$s" name="kindi[%2$s]" rows="4" class="large-text" dir="rtl">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_textarea( (string) $value )
				);
			} else {
				$input_type = 'number' === $field['type'] ? 'number' : ( 'url' === $field['type'] ? 'url' : 'text' );
				printf(
					'<input type="%1$s" id="%2$s" name="kindi[%3$s]" value="%4$s" class="regular-text" dir="rtl">',
					esc_attr( $input_type ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	submit_button( __( 'שמירת תוכן', 'kindi' ), 'primary', 'kindi_settings_submit' );
	echo '</form></div>';
}
