<?php
/**
 * Holiday shipping notice — a one-time popup shown only to shoppers who add a
 * product to the cart, plus a prominent box at the top of the checkout page.
 * Managed from the Kindi panel ("מבצעים ותוכן" → "פופאפ הודעת חגים"): on/off
 * toggle, title and body text.
 *
 * Trigger mechanics:
 * - AJAX add (archive/loop buttons): WooCommerce announces success only as a
 *   jQuery `added_to_cart` event, so a tiny guarded bridge listens on the
 *   site's existing jQuery — the theme itself enqueues none.
 * - Regular add (product-page form → reload/redirect): `woocommerce_add_to_cart`
 *   sets a WC-session flag which the next page render reads and clears.
 * The popup shows once per browser (localStorage, keyed by a hash of the text
 * so an edited message shows again), and never on checkout — the box is there.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ *
 * Admin — panel fields
 * ------------------------------------------------------------------ */

/**
 * Add the holiday-notice section to the "מבצעים ותוכן" tab.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_holiday_settings_fields( array $tabs ): array {
	if ( isset( $tabs['promos']['sections'] ) ) {
		$tabs['promos']['sections']['פופאפ הודעת חגים (משלוחים)'] = array(
			'holiday_enable' => array(
				'type'    => 'select',
				'label'   => 'הצגת ההודעה',
				'options' => array( '1' => 'מופעל', '0' => 'כבוי' ),
				'help'    => 'כשמופעל: פופאפ מעוצב נפתח פעם אחת לכל גולש — רק אחרי שהוסיף מוצר לסל — וההודעה מוצגת גם בראש עמוד התשלום. אחרי החגים פשוט מכבים כאן.',
			),
			'holiday_title'  => array( 'type' => 'text', 'label' => 'כותרת' ),
			'holiday_text'   => array( 'type' => 'textarea', 'label' => 'טקסט ההודעה', 'help' => 'שורה ריקה בין פסקאות יוצרת פסקה חדשה.' ),
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_holiday_settings_fields' );

/* ------------------------------------------------------------------ *
 * Shared helpers
 * ------------------------------------------------------------------ */

/**
 * Whether the holiday notice is enabled and has content.
 *
 * @return bool
 */
function kindi_holiday_enabled(): bool {
	return '1' === (string) kindi_opt( 'holiday_enable' )
		&& '' !== trim( (string) kindi_opt( 'holiday_text' ) );
}

/**
 * The notice body as paragraph HTML (blank line = new paragraph).
 *
 * @return string Escaped markup.
 */
function kindi_holiday_body_html(): string {
	$parts = preg_split( '/\n{2,}/', trim( (string) kindi_opt( 'holiday_text' ) ) ) ?: array();
	$html  = '';
	foreach ( $parts as $part ) {
		$part = trim( $part );
		if ( '' !== $part ) {
			$html .= '<p>' . nl2br( esc_html( $part ) ) . '</p>';
		}
	}
	return $html;
}

/* ------------------------------------------------------------------ *
 * Checkout — prominent box above the form
 * ------------------------------------------------------------------ */

/**
 * Render the notice box at the top of the checkout page (before the view
 * notices at 3 and the stock-cleanup notice at 4).
 *
 * @return void
 */
function kindi_holiday_checkout_box(): void {
	if ( ! kindi_holiday_enabled() ) {
		return;
	}
	echo '<div class="kindi-holiday" role="note">';
	echo '<span class="kindi-holiday__ic">' . kindi_icon( 'truck', 'kindi-icon--md' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	echo '<div>';
	echo '<p class="kindi-holiday__tt">' . esc_html( (string) kindi_opt( 'holiday_title' ) ) . '</p>';
	echo '<div class="kindi-holiday__txt">' . kindi_holiday_body_html() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in builder.
	echo '</div></div>';
}
add_action( 'woocommerce_before_checkout_form', 'kindi_holiday_checkout_box', 2 );

/* ------------------------------------------------------------------ *
 * Popup — after add to cart
 * ------------------------------------------------------------------ */

/**
 * Non-AJAX add (product-page form): flag the session so the popup opens on the
 * page the shopper lands on (same page or the cart, per the WC redirect
 * setting). AJAX adds are caught client-side instead.
 *
 * @return void
 */
function kindi_holiday_flag_added(): void {
	if ( ! wp_doing_ajax() && kindi_holiday_enabled() && function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'kindi_holiday_pop', 1 );
	}
}
add_action( 'woocommerce_add_to_cart', 'kindi_holiday_flag_added' );

/**
 * Print the (hidden) popup with its scoped styles and vanilla JS in the footer.
 * Nothing is printed when the feature is off — zero footprint.
 *
 * @return void
 */
function kindi_holiday_popup(): void {
	if ( ! kindi_holiday_enabled() || is_admin() ) {
		return;
	}
	// Checkout has the box; canvas pages carry no marketing chrome at all.
	if ( ( function_exists( 'is_checkout' ) && is_checkout() ) || ( function_exists( 'kindi_is_canvas_page' ) && kindi_is_canvas_page() ) ) {
		return;
	}

	$title = (string) kindi_opt( 'holiday_title' );
	$key   = substr( md5( $title . (string) kindi_opt( 'holiday_text' ) ), 0, 8 );

	// Was the flag set by a non-AJAX add on the previous request?
	$open = false;
	if ( function_exists( 'WC' ) && WC()->session && WC()->session->get( 'kindi_holiday_pop' ) ) {
		WC()->session->set( 'kindi_holiday_pop', null );
		$open = true;
	}
	?>
	<div class="kindi-holipop" data-kindi-holiday data-key="<?php echo esc_attr( $key ); ?>" data-open="<?php echo $open ? '1' : '0'; ?>" hidden>
		<div class="kindi-holipop__backdrop" data-kindi-holiday-close></div>
		<div class="kindi-holipop__card" role="dialog" aria-modal="true" aria-labelledby="kindi-holipop-title">
			<button type="button" class="kindi-holipop__x" data-kindi-holiday-close aria-label="<?php esc_attr_e( 'סגירת ההודעה', 'kindi' ); ?>"><?php echo kindi_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<span class="kindi-holipop__ic"><?php echo kindi_icon( 'truck' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h2 class="kindi-holipop__title" id="kindi-holipop-title"><?php echo esc_html( $title ); ?></h2>
			<div class="kindi-holipop__txt"><?php echo kindi_holiday_body_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in builder. ?></div>
			<button type="button" class="kindi-btn kindi-btn--red kindi-holipop__cta" data-kindi-holiday-close><?php esc_html_e( 'הבנתי, ממשיכים בקנייה', 'kindi' ); ?></button>
		</div>
	</div>
	<style>
	.kindi-holipop{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:1rem}
	.kindi-holipop__backdrop{position:absolute;inset:0;background:color-mix(in oklab, var(--brand-navy) 55%, transparent);backdrop-filter:blur(2px)}
	.kindi-holipop__card{position:relative;background:#fff;width:100%;max-width:29rem;max-height:calc(100dvh - 2rem);overflow-y:auto;border-radius:1.25rem;border-top:6px solid var(--brand-yellow);box-shadow:var(--shadow-pop);padding:2rem 1.75rem 1.75rem;text-align:center;opacity:0;transform:translateY(14px) scale(.97);transition:opacity .3s ease,transform .3s ease}
	.kindi-holipop.is-on .kindi-holipop__card{opacity:1;transform:none}
	.kindi-holipop__x{position:absolute;top:.65rem;inset-inline-start:.65rem;display:inline-flex;padding:.35rem;background:none;border:0;border-radius:50%;cursor:pointer;color:var(--brand-navy)}
	.kindi-holipop__x svg{width:1.25rem;height:1.25rem}
	.kindi-holipop__ic{display:inline-flex;align-items:center;justify-content:center;width:3.75rem;height:3.75rem;border-radius:50%;background:var(--brand-red-soft);margin-bottom:.85rem}
	.kindi-holipop__ic svg{width:2.1rem;height:2.1rem}
	.kindi-holipop__title{font-family:var(--wp--preset--font-family--display);color:var(--brand-navy);font-size:1.4rem;line-height:1.3;margin:0 0 .8rem}
	.kindi-holipop__txt{color:var(--foreground);font-size:.95rem;line-height:1.7;margin-bottom:1.35rem}
	.kindi-holipop__txt p{margin:0 0 .8em}
	.kindi-holipop__txt p:last-child{margin-bottom:0}
	.kindi-holipop__cta{width:100%}
	html.kindi-holipop-lock{overflow:hidden}
	@media (prefers-reduced-motion:reduce){.kindi-holipop__card{transition:none;transform:none;opacity:1}}
	</style>
	<script>
	( function () {
		var el = document.querySelector( '[data-kindi-holiday]' );
		if ( ! el ) { return; }
		var key = 'kindiHoliday.' + ( el.dataset.key || '1' );
		var lastFocus = null;
		function seen() { try { return !! localStorage.getItem( key ); } catch ( e ) { return false; } }
		function mark() { try { localStorage.setItem( key, '1' ); } catch ( e ) {} }
		function open() {
			if ( seen() || ! el.hidden ) { return; }
			lastFocus = document.activeElement;
			el.hidden = false;
			requestAnimationFrame( function () { el.classList.add( 'is-on' ); } );
			document.documentElement.classList.add( 'kindi-holipop-lock' );
			var x = el.querySelector( '.kindi-holipop__x' );
			if ( x ) { x.focus(); }
			mark();
		}
		function close() {
			if ( el.hidden ) { return; }
			el.classList.remove( 'is-on' );
			el.hidden = true;
			document.documentElement.classList.remove( 'kindi-holipop-lock' );
			if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
		}
		el.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '[data-kindi-holiday-close]' ) ) { close(); }
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( el.hidden ) { return; }
			if ( 'Escape' === e.key ) { close(); return; }
			if ( 'Tab' === e.key ) {
				var f = el.querySelectorAll( 'button' );
				var first = f[ 0 ], last = f[ f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
				else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
			}
		} );
		// Non-AJAX add: the server flagged this page load as "just added".
		if ( '1' === el.dataset.open ) { setTimeout( open, 350 ); }
		// AJAX add: WooCommerce emits `added_to_cart` only as a jQuery event, so
		// the bridge rides the jQuery WC already loads — the theme enqueues none.
		if ( window.jQuery ) { window.jQuery( document.body ).on( 'added_to_cart', function () { setTimeout( open, 250 ); } ); }
	}() );
	</script>
	<?php
}
add_action( 'wp_footer', 'kindi_holiday_popup', 30 );
