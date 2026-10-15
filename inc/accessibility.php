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
	// Canvas pages render no toolbar, so the script has nothing to wire.
	if ( function_exists( 'kindi_is_canvas_page' ) && kindi_is_canvas_page() ) {
		return;
	}
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
 * Floating action buttons (WhatsApp + accessibility).
 *
 * Rendered on every view except the canvas page template, which is kept
 * deliberately chrome-free for landing pages. Note that pages using canvas
 * therefore carry no accessibility toolbar — keep it for standalone landing
 * pages rather than regular site content (ת"י 5568 / WCAG 2.2 AA).
 *
 * @return void
 */
function kindi_floating_buttons(): void {
	if ( function_exists( 'kindi_is_canvas_page' ) && kindi_is_canvas_page() ) {
		return;
	}

	$wa = preg_replace( '/\D+/', '', (string) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'whatsapp' ) : '' ) );
	// Panel toggle for the floating button only — the product-page button and
	// the checkout help box keep using the number regardless.
	if ( function_exists( 'kindi_opt' ) && '1' !== (string) kindi_opt( 'whatsapp_float_enable' ) ) {
		$wa = '';
	}
	if ( '' !== $wa ) {
		echo '<a class="kindi-float kindi-float--wa" href="' . esc_url( 'https://wa.me/' . $wa ) . '" aria-label="צ׳אט בוואטסאפ" target="_blank" rel="noopener">' . kindi_icon( 'whatsapp', 'kindi-icon--xl kindi-icon--white' ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '<button class="kindi-float kindi-float--a11y" type="button" aria-label="הגדרות נגישות" aria-controls="kindi-a11y-panel" aria-expanded="false" data-kindi-a11y>' . kindi_icon( 'accessibility', 'kindi-icon--md kindi-icon--white' ) . '</button>'; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_footer', 'kindi_floating_buttons', 20 );

/**
 * Render the toolbar markup in the footer.
 *
 * @return void
 */
function kindi_a11y_panel(): void {
	if ( function_exists( 'kindi_is_canvas_page' ) && kindi_is_canvas_page() ) {
		return;
	}
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
	<div class="kindi-a11y" id="kindi-a11y-panel" data-kindi-a11y-panel hidden role="dialog" aria-modal="false" aria-label="כלי נגישות">
		<div class="kindi-a11y__head">
			<strong>נגישות</strong>
			<button type="button" class="kindi-a11y__close" data-kindi-a11y-close aria-label="סגירה"><?php echo kindi_icon( 'close', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</div>
		<div class="kindi-a11y__grid">
			<?php foreach ( $buttons as $action => $label ) : ?>
			<button type="button" class="kindi-a11y__btn<?php echo 'reset' === $action ? ' kindi-a11y__btn--reset' : ''; ?>" data-a11y="<?php echo esc_attr( $action ); ?>"<?php echo 'reset' === $action ? '' : ' aria-pressed="false"'; ?>><?php echo esc_html( $label ); ?></button>
			<?php endforeach; ?>
		</div>
		<a class="kindi-a11y__statement" href="<?php echo esc_url( home_url( '/accessibility/' ) ); ?>">הצהרת הנגישות</a>
	</div>
	<?php
}
add_action( 'wp_footer', 'kindi_a11y_panel' );
