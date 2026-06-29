<?php
/**
 * Accessibility statement (ת"י 5568 / WCAG 2.2 AA).
 *
 * Provides the [kindi_accessibility] shortcode and auto-creates the
 * "accessibility-statement" page (linked from the accessibility toolbar) so the
 * site ships a compliant, editable statement out of the box.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the accessibility statement.
 *
 * @return string
 */
function kindi_accessibility_statement(): string {
	$text = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'a11y_statement', '' ) : '';
	if ( '' === trim( $text ) ) {
		$text = kindi_a11y_default_statement();
	}

	return '<div class="kindi-a11y-statement kindi-prose">' . wp_kses_post( wpautop( $text ) ) . '</div>';
}
add_shortcode( 'kindi_accessibility', 'kindi_accessibility_statement' );

/**
 * Default, compliant statement text (used when the admin field is empty). The
 * admin can override the whole thing from קינדי → נגישות.
 *
 * @return string
 */
function kindi_a11y_default_statement(): string {
	$name  = esc_html( get_bloginfo( 'name' ) );
	$phone = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'store_phone' ) : '';
	$email = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'email' ) : '';

	$contact = '';
	if ( '' !== $phone ) {
		$contact .= '<li><strong>טלפון:</strong> <a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">' . esc_html( $phone ) . '</a></li>';
	}
	if ( '' !== $email ) {
		$contact .= '<li><strong>דוא"ל:</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></li>';
	}

	return '<h2>הצהרת נגישות</h2>'
		. '<p>אתר <strong>' . $name . '</strong> רואה חשיבות רבה במתן שירות שוויוני לכלל הלקוחות ובשיפור חוויית הגלישה עבור אנשים עם מוגבלות, מתוך אמונה כי לכל אדם מגיעה הזכות לגלוש בכבוד, בשוויון ובעצמאות.</p>'
		. '<h2>רמת הנגישות באתר</h2>'
		. '<p>האתר עומד בדרישות תקנות שוויון זכויות לאנשים עם מוגבלות (התאמות נגישות לשירות), התשע"ג–2013, ובהתאם לתקן הישראלי ת"י 5568 המבוסס על הנחיות WCAG 2.2 ברמת AA.</p>'
		. '<h2>ההתאמות שבוצעו</h2>'
		. '<ul><li>ניווט מלא במקלדת ומצבי פוקוס נראים.</li><li>תאימות להקראת מסך ושימוש מושכל ב-ARIA.</li><li>סרגל נגישות: הגדלת טקסט, ניגודיות, הדגשת קישורים, גופן קריא ועצירת אנימציות.</li><li>מבנה כותרות סמנטי וטקסט חלופי לתמונות.</li><li>כיבוד prefers-reduced-motion ותמיכה מלאה ב-RTL.</li></ul>'
		. '<h2>פנייה בנושאי נגישות</h2>'
		. '<p>נתקלתם בקושי? נשמח לקבל את פנייתכם ונטפל בה בהקדם:</p>'
		. ( '' !== $contact ? '<ul>' . $contact . '</ul>' : '' );
}

/**
 * Ensure the accessibility-statement page exists (linked from the a11y toolbar).
 *
 * @return void
 */
function kindi_ensure_a11y_page(): void {
	if ( 'done' === get_option( 'kindi_a11y_page', '' ) ) {
		return;
	}

	$existing = get_page_by_path( 'accessibility-statement' );
	if ( ! $existing ) {
		wp_insert_post(
			array(
				'post_title'   => 'הצהרת נגישות',
				'post_name'    => 'accessibility-statement',
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_content' => '[kindi_accessibility]',
			)
		);
	}

	update_option( 'kindi_a11y_page', 'done', false );
}
add_action( 'after_switch_theme', 'kindi_ensure_a11y_page' );
add_action( 'admin_init', 'kindi_ensure_a11y_page' );
