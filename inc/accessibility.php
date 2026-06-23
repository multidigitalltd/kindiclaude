<?php
/**
 * Accessibility toolbar — font sizing, contrast, link highlight, readable font,
 * stop motion. Preferences persist in localStorage (handled in a11y.js).
 * Complements ת"י 5568 / WCAG 2.2.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the accessibility script.
 *
 * @return void
 */
function kindi_a11y_assets(): void {
	wp_enqueue_script(
		'kindi-a11y',
		KINDI_URI . 'assets/js/a11y.js',
		array(),
		kindi_asset_version( 'assets/js/a11y.js' ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'kindi_a11y_assets' );

/**
 * Render the toolbar markup in the footer.
 *
 * @return void
 */
function kindi_a11y_panel(): void {
	$buttons = array(
		'font-inc' => 'הגדלת טקסט',
		'font-dec' => 'הקטנת טקסט',
		'contrast' => 'ניגודיות גבוהה',
		'links'    => 'הדגשת קישורים',
		'readable' => 'גופן קריא',
		'motion'   => 'עצירת אנימציות',
		'reset'    => 'איפוס הגדרות',
	);
	?>
	<div class="kindi-a11y" data-kindi-a11y-panel hidden role="dialog" aria-modal="false" aria-label="כלי נגישות">
		<div class="kindi-a11y__head">
			<strong>נגישות</strong>
			<button type="button" class="kindi-a11y__close" data-kindi-a11y-close aria-label="סגירה"><?php echo kindi_icon( 'close', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<div class="kindi-a11y__grid">
			<?php foreach ( $buttons as $action => $label ) : ?>
			<button type="button" class="kindi-a11y__btn<?php echo 'reset' === $action ? ' kindi-a11y__btn--reset' : ''; ?>" data-a11y="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
		<a class="kindi-a11y__statement" href="<?php echo esc_url( home_url( '/accessibility-statement/' ) ); ?>">הצהרת הנגישות</a>
	</div>
	<?php
}
add_action( 'wp_footer', 'kindi_a11y_panel' );
