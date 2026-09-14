<?php
/**
 * Dashboard cleanup — choose which top-level admin menus (plugin tabs, WordPress
 * settings) are hidden, for a tidy dashboard. Managed from a "לוח בקרה" tab in
 * the Kindi panel.
 *
 * Hiding only removes the menu link; every page stays reachable by its direct
 * URL. The Kindi menu is never hidden, so the setting is always reversible.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Hidden top-level menu slugs (from the panel).
 *
 * @return string[]
 */
function kindi_dashclean_hidden(): array {
	$v = function_exists( 'kindi_opt' ) ? kindi_opt( 'hidden_menus' ) : array();
	return is_array( $v ) ? $v : array();
}

/**
 * Capture the full top-level menu (slug => title) so the panel checklist can
 * list every menu even on pages where some are removed. Updated only on change.
 *
 * @return void
 */
function kindi_dashclean_capture(): void {
	global $menu;
	if ( empty( $menu ) || ! is_array( $menu ) ) {
		return;
	}
	$catalog = array();
	foreach ( $menu as $item ) {
		$slug  = isset( $item[2] ) ? (string) $item[2] : '';
		$title = isset( $item[0] ) ? trim( wp_strip_all_tags( (string) $item[0] ) ) : '';
		if ( '' === $slug || '' === $title || false !== strpos( $slug, 'separator' ) || 'kindi-settings' === $slug ) {
			continue;
		}
		$catalog[ $slug ] = $title;
	}
	if ( get_option( 'kindi_menu_catalog' ) !== $catalog ) {
		update_option( 'kindi_menu_catalog', $catalog, false );
	}
}
add_action( 'admin_menu', 'kindi_dashclean_capture', 9990 );

/**
 * Remove the chosen menus. Never hides the Kindi menu.
 *
 * @return void
 */
function kindi_dashclean_apply(): void {
	foreach ( kindi_dashclean_hidden() as $slug ) {
		if ( 'kindi-settings' === $slug ) {
			continue;
		}
		remove_menu_page( $slug );
	}
}
add_action( 'admin_menu', 'kindi_dashclean_apply', 9999 );

/**
 * Register the "לוח בקרה" tab in the Kindi panel.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_dashclean_tab( array $tabs ): array {
	$tabs['dashboard'] = array(
		'label'    => 'לוח בקרה',
		'sections' => array(
			'ניקוי תפריטי לוח הבקרה' => array(
				'hidden_menus' => array(
					'type'  => 'menu_toggles',
					'label' => 'תפריטים להסתרה',
					'help'  => 'סמנו אילו תפריטים להסתיר מלוח הבקרה. ההסתרה מסתירה את הקישור בלבד — הדפים עדיין נגישים דרך הכתובת הישירה. תפריט קינדי לעולם לא מוסתר.',
				),
			),
		),
	);
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_dashclean_tab' );

/**
 * Render the menu checklist in the panel (dispatched from the settings render).
 *
 * @param string $key   Field key ('hidden_menus').
 * @param mixed  $value Stored hidden slugs.
 * @return void
 */
function kindi_dashclean_field_render( string $key, $value ): void {
	$hidden  = is_array( $value ) ? $value : array();
	$catalog = get_option( 'kindi_menu_catalog', array() );
	if ( ! is_array( $catalog ) ) {
		$catalog = array();
	}

	// Marker so unchecking everything still saves (empties the list).
	echo '<input type="hidden" name="kindi__present[' . esc_attr( $key ) . ']" value="1">';

	if ( ! $catalog ) {
		echo '<p>' . esc_html__( 'רשימת התפריטים תיטען לאחר רענון העמוד.', 'kindi' ) . '</p>';
		return;
	}

	echo '<div style="max-width:640px">';
	foreach ( $catalog as $slug => $title ) {
		printf(
			'<label style="display:block;margin:5px 0"><input type="checkbox" name="kindi[%1$s][]" value="%2$s"%3$s> %4$s <code style="opacity:.55">%2$s</code></label>',
			esc_attr( $key ),
			esc_attr( (string) $slug ),
			checked( in_array( (string) $slug, $hidden, true ), true, false ),
			esc_html( (string) $title )
		);
	}
	echo '</div>';
}

/**
 * Sanitise the checklist on save (drops the Kindi slug defensively).
 *
 * @param array<int,mixed> $slugs Posted slugs.
 * @return string[]
 */
function kindi_dashclean_sanitize( array $slugs ): array {
	$out = array();
	foreach ( $slugs as $slug ) {
		$slug = sanitize_text_field( (string) $slug );
		if ( '' !== $slug && 'kindi-settings' !== $slug ) {
			$out[] = $slug;
		}
	}
	return array_values( array_unique( $out ) );
}
