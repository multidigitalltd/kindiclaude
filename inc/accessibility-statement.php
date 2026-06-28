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
	$name    = get_bloginfo( 'name' );
	$phone   = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'store_phone' ) : '';
	$email   = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'email' ) : '';
	$address = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'store_address' ) : '';
	$coord   = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'a11y_coordinator' ) : '';
	$updated = function_exists( 'kindi_opt' ) ? (string) kindi_opt( 'a11y_updated' ) : '';
	if ( '' === $updated ) {
		$updated = wp_date( 'd/m/Y' );
	}

	ob_start();
	?>
	<div class="kindi-a11y-statement">
		<h1>הצהרת נגישות</h1>

		<p>אתר <strong><?php echo esc_html( $name ); ?></strong> רואה חשיבות רבה במתן שירות שוויוני לכלל הלקוחות ובשיפור חוויית הגלישה עבור אנשים עם מוגבלות. אנו פועלים ככל יכולתנו להנגיש את האתר ולהפכו לזמין, ידידותי ונוח לשימוש עבור כולם, מתוך אמונה כי לכל אדם מגיעה הזכות לחיות בכבוד, בשוויון ובעצמאות.</p>

		<h2>רמת הנגישות באתר</h2>
		<p>האתר עומד בדרישות תקנות שוויון זכויות לאנשים עם מוגבלות (התאמות נגישות לשירות), התשע"ג–2013, ובהתאם לתקן הישראלי <strong>ת"י 5568</strong> המבוסס על הנחיות <strong>WCAG 2.2</strong> ברמת התאמה <strong>AA</strong>.</p>

		<h2>ההתאמות שבוצעו באתר</h2>
		<ul>
			<li>התאמת האתר לניווט מלא באמצעות מקלדת (Tab / Shift+Tab / Enter / Esc).</li>
			<li>תאימות לתוכנות הקראת מסך (NVDA, JAWS, VoiceOver) ושימוש מושכל בתגיות ARIA.</li>
			<li>סרגל נגישות ייעודי: הגדלת/הקטנת טקסט, ניגודיות גבוהה, הדגשת קישורים, גופן קריא ועצירת אנימציות.</li>
			<li>מבנה כותרות סמנטי והיררכי, טקסט חלופי (alt) לתמונות בעלות משמעות.</li>
			<li>כיבוד העדפת המשתמש להפחתת תנועה (prefers-reduced-motion).</li>
			<li>ניגודיות צבעים תקינה, מצבי פוקוס נראים, ותמיכה בהגדלת טקסט עד 200% ללא שבירת פריסה.</li>
			<li>תמיכה מלאה ב-RTL ובשפה העברית, וקישור "דלג לתוכן".</li>
		</ul>

		<h2>דפדפנים נתמכים</h2>
		<p>האתר נבדק ונתמך בגרסאות העדכניות של הדפדפנים הנפוצים: Chrome, Firefox, Safari ו-Edge, במחשב ובמכשירים ניידים.</p>

		<h2>מגבלות ידועות</h2>
		<p>על אף מאמצינו להנגיש את כלל הדפים, ייתכן שחלקים מסוימים באתר (למשל תכנים של צד שלישי או רכיבים שטרם עודכנו) אינם נגישים במלואם. אנו ממשיכים לפעול לשיפור מתמיד של נגישות האתר.</p>

		<h2>פנייה לרכז הנגישות</h2>
		<p>נתקלתם בקושי בגלישה או בבעיית נגישות? נשמח לקבל את פנייתכם ונטפל בה בהקדם:</p>
		<ul>
			<?php if ( '' !== $coord ) : ?><li><strong>רכז/ת הנגישות:</strong> <?php echo esc_html( $coord ); ?></li><?php endif; ?>
			<?php if ( '' !== $phone ) : ?><li><strong>טלפון:</strong> <a href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></li><?php endif; ?>
			<?php if ( '' !== $email ) : ?><li><strong>דוא"ל:</strong> <a href="<?php echo esc_attr( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a></li><?php endif; ?>
			<?php if ( '' !== $address ) : ?><li><strong>כתובת:</strong> <?php echo esc_html( $address ); ?></li><?php endif; ?>
		</ul>
		<p>נשתדל להשיב לפנייתכם תוך זמן סביר. אם לא קיבלתם מענה הולם, ניתן לפנות לנציבות שוויון זכויות לאנשים עם מוגבלות.</p>

		<p class="kindi-a11y-statement__date">הצהרת הנגישות עודכנה בתאריך: <?php echo esc_html( $updated ); ?></p>
	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'kindi_accessibility', 'kindi_accessibility_statement' );

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
