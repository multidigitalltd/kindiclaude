<?php
/**
 * Save-cart popup — once a shopper has 2+ items in the cart, a one-time popup
 * offers to save the cart by email (so nothing they picked gets lost), using
 * the existing save-and-share mechanism from inc/saved-cart.php (same AJAX
 * action, nonce, emails and admin list).
 *
 * Trigger mechanics mirror the holiday popup (inc/holiday-popup.php): AJAX
 * adds via a guarded bridge on WooCommerce's jQuery `added_to_cart` event with
 * a client-side item counter seeded from the server; regular adds via a WC
 * session flag. Shown once per browser (localStorage), never on the cart page
 * (the inline panel lives there), never on checkout, and never on top of the
 * holiday popup — it simply waits for the next add.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Minimum cart-item count (sum of quantities) that triggers the popup.
 */
const KINDI_SAVECART_POP_MIN = 2;

/**
 * Add the on/off toggle to the panel's "טקסטים והגדרות" → "כללי" section.
 *
 * @param array<string,array<string,mixed>> $tabs Settings tabs.
 * @return array<string,array<string,mixed>>
 */
function kindi_savecart_pop_settings( array $tabs ): array {
	if ( isset( $tabs['texts']['sections']['כללי'] ) ) {
		$tabs['texts']['sections']['כללי']['savecart_popup_enable'] = array(
			'type'    => 'select',
			'label'   => 'פופאפ שמירת עגלה',
			'options' => array( '1' => 'מופעל', '0' => 'כבוי' ),
			'help'    => 'נפתח פעם אחת לכל גולש אחרי שהוסיף 2 מוצרים ומעלה לסל, ומציע לשמור את העגלה במייל כדי שלא תאבד.',
		);
	}
	return $tabs;
}
add_filter( 'kindi_settings_tabs', 'kindi_savecart_pop_settings' );

/**
 * Whether the save-cart popup is enabled.
 *
 * @return bool
 */
function kindi_savecart_pop_enabled(): bool {
	return '1' === (string) kindi_opt( 'savecart_popup_enable' );
}

/**
 * Non-AJAX add (product-page form): flag the session so the next page render
 * can open the popup if the cart already holds enough items.
 *
 * @return void
 */
function kindi_savecart_pop_flag_added(): void {
	if ( ! wp_doing_ajax() && kindi_savecart_pop_enabled() && function_exists( 'WC' ) && WC()->session ) {
		WC()->session->set( 'kindi_savecart_pop', 1 );
	}
}
add_action( 'woocommerce_add_to_cart', 'kindi_savecart_pop_flag_added' );

/**
 * Print the (hidden) popup with scoped styles and vanilla JS in the footer.
 * Nothing is printed when the feature is off — zero footprint.
 *
 * @return void
 */
function kindi_savecart_popup(): void {
	if ( ! kindi_savecart_pop_enabled() || is_admin() || ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	// The cart page has the inline panel; checkout must stay distraction-free;
	// canvas pages carry no marketing chrome.
	if ( ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'kindi_is_canvas_page' ) && kindi_is_canvas_page() ) ) {
		return;
	}

	$count = (int) WC()->cart->get_cart_contents_count();
	$email = is_user_logged_in() ? wp_get_current_user()->user_email : '';

	// Was the flag set by a non-AJAX add on the previous request?
	$open = false;
	if ( WC()->session && WC()->session->get( 'kindi_savecart_pop' ) ) {
		WC()->session->set( 'kindi_savecart_pop', null );
		$open = $count >= KINDI_SAVECART_POP_MIN;
	}
	?>
	<div class="kindi-scpop" data-kindi-scpop data-count="<?php echo esc_attr( (string) $count ); ?>" data-open="<?php echo $open ? '1' : '0'; ?>" hidden>
		<div class="kindi-scpop__backdrop" data-kindi-scpop-close></div>
		<div class="kindi-scpop__card" role="dialog" aria-modal="true" aria-labelledby="kindi-scpop-title">
			<button type="button" class="kindi-scpop__x" data-kindi-scpop-close aria-label="<?php esc_attr_e( 'סגירת ההודעה', 'kindi' ); ?>"><?php echo kindi_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<span class="kindi-scpop__ic"><?php echo kindi_icon( 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<h2 class="kindi-scpop__title" id="kindi-scpop-title"><?php esc_html_e( 'שמרו את העגלה לזמן אחר', 'kindi' ); ?></h2>
			<p class="kindi-scpop__txt"><?php esc_html_e( 'יש לכם כבר כמה מוצרים בסל — השאירו אימייל ונשלח קישור לשחזור העגלה, כדי שלא תאבדו את מה שבחרתם. אפשר גם לשתף את הקישור.', 'kindi' ); ?></p>
			<div class="kindi-scpop__row">
				<label class="screen-reader-text" for="kindi-scpop-email"><?php esc_html_e( 'אימייל (לקבלת הקישור)', 'kindi' ); ?></label>
				<input type="email" id="kindi-scpop-email" value="<?php echo esc_attr( $email ); ?>" placeholder="<?php esc_attr_e( 'אימייל (לקבלת הקישור)', 'kindi' ); ?>" required>
				<button type="button" class="kindi-btn kindi-btn--navy" data-kindi-scpop-save data-nonce="<?php echo esc_attr( wp_create_nonce( 'kindi_save_cart' ) ); ?>" data-ajax="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"><?php esc_html_e( 'שמירת עגלה', 'kindi' ); ?></button>
			</div>
			<div class="kindi-scpop__result" data-kindi-scpop-result hidden aria-live="polite"></div>
			<button type="button" class="kindi-scpop__skip" data-kindi-scpop-close><?php esc_html_e( 'לא עכשיו, תודה', 'kindi' ); ?></button>
		</div>
	</div>
	<style>
	.kindi-scpop{position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:1rem}
	.kindi-scpop[hidden]{display:none!important}
	.kindi-scpop__backdrop{position:absolute;inset:0;background:color-mix(in oklab, var(--brand-navy) 55%, transparent);backdrop-filter:blur(2px)}
	.kindi-scpop__card{position:relative;background:#fff;width:100%;max-width:29rem;max-height:calc(100dvh - 2rem);overflow-y:auto;border-radius:1.25rem;border-top:6px solid var(--brand-blue);box-shadow:var(--shadow-pop);padding:2rem 1.75rem 1.5rem;text-align:center;opacity:0;transform:translateY(14px) scale(.97);transition:opacity .3s ease,transform .3s ease}
	.kindi-scpop.is-on .kindi-scpop__card{opacity:1;transform:none}
	.kindi-scpop__x{position:absolute;top:.65rem;inset-inline-start:.65rem;display:inline-flex;padding:.35rem;background:none;border:0;border-radius:50%;cursor:pointer;color:var(--brand-navy)}
	.kindi-scpop__x svg{width:1.25rem;height:1.25rem}
	.kindi-scpop__ic{display:inline-flex;align-items:center;justify-content:center;width:3.75rem;height:3.75rem;border-radius:50%;background:var(--brand-blue-soft);margin-bottom:.85rem}
	.kindi-scpop__ic svg{width:2.1rem;height:2.1rem}
	.kindi-scpop__title{font-family:var(--wp--preset--font-family--display);color:var(--brand-navy);font-size:1.4rem;line-height:1.3;margin:0 0 .6rem}
	.kindi-scpop__txt{color:var(--foreground);font-size:.95rem;line-height:1.65;margin:0 0 1.15rem}
	.kindi-scpop__row{display:flex;gap:.5rem}
	.kindi-scpop__row input{flex:1 1 auto;min-width:0;border:1px solid var(--border);border-radius:.75rem;padding:.6rem .85rem;font-family:inherit;font-size:.95rem}
	.kindi-scpop__result{margin-top:.9rem;font-size:.9rem;color:var(--foreground)}
	.kindi-scpop__result[hidden]{display:none!important}
	.kindi-scpop__link{display:flex;gap:.5rem;margin-top:.5rem}
	.kindi-scpop__link input{flex:1 1 auto;min-width:0;border:1px solid var(--border);border-radius:.75rem;padding:.5rem .75rem;font-size:.85rem;direction:ltr}
	.kindi-scpop__skip{display:block;margin:1rem auto 0;background:none;border:0;cursor:pointer;font-family:inherit;font-size:.85rem;color:color-mix(in oklab, var(--foreground) 65%, transparent);text-decoration:underline}
	@media (prefers-reduced-motion:reduce){.kindi-scpop__card{transition:none;transform:none;opacity:1}}
	</style>
	<script>
	( function () {
		var el = document.querySelector( '[data-kindi-scpop]' );
		if ( ! el ) { return; }
		var key = 'kindiSavecart.seen';
		var count = parseInt( el.dataset.count, 10 ) || 0;
		var lastFocus = null;
		function seen() { try { return !! localStorage.getItem( key ); } catch ( e ) { return false; } }
		function mark() { try { localStorage.setItem( key, '1' ); } catch ( e ) {} }
		function open() {
			if ( count < <?php echo (int) KINDI_SAVECART_POP_MIN; ?> || seen() || ! el.hidden ) { return; }
			// Never on top of the holiday popup — the next add gets another chance.
			if ( document.querySelector( '.kindi-holipop:not([hidden])' ) ) { return; }
			lastFocus = document.activeElement;
			el.hidden = false;
			requestAnimationFrame( function () { el.classList.add( 'is-on' ); } );
			document.documentElement.classList.add( 'kindi-holipop-lock' );
			var mail = el.querySelector( '#kindi-scpop-email' );
			if ( mail ) { mail.focus(); }
			mark();
		}
		function close() {
			if ( el.hidden ) { return; }
			el.classList.remove( 'is-on' );
			el.hidden = true;
			document.documentElement.classList.remove( 'kindi-holipop-lock' );
			if ( lastFocus && lastFocus.focus ) { lastFocus.focus(); }
		}
		function save( btn ) {
			var mail = el.querySelector( '#kindi-scpop-email' );
			var result = el.querySelector( '[data-kindi-scpop-result]' );
			if ( mail && ! mail.checkValidity() ) { mail.reportValidity(); return; }
			btn.disabled = true;
			var body = new URLSearchParams();
			body.set( 'action', 'kindi_save_cart' );
			body.set( 'nonce', btn.dataset.nonce || '' );
			body.set( 'email', mail ? mail.value : '' );
			fetch( btn.dataset.ajax, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString()
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					btn.disabled = false;
					result.hidden = false;
					result.innerHTML = '';
					var msg = document.createElement( 'p' );
					msg.textContent = ( res && res.data && res.data.message ) || 'אירעה שגיאה. נסו שוב.';
					result.appendChild( msg );
					if ( res && res.success && res.data.url ) {
						var box = document.createElement( 'div' );
						box.className = 'kindi-scpop__link';
						var input = document.createElement( 'input' );
						input.type = 'text';
						input.readOnly = true;
						input.value = res.data.url;
						var copy = document.createElement( 'button' );
						copy.type = 'button';
						copy.className = 'kindi-btn kindi-btn--red';
						copy.textContent = 'העתקה';
						copy.addEventListener( 'click', function () {
							input.select();
							if ( navigator.clipboard ) { navigator.clipboard.writeText( input.value ); } else { document.execCommand( 'copy' ); }
							copy.textContent = 'הועתק';
						} );
						box.appendChild( input );
						box.appendChild( copy );
						result.appendChild( box );
					}
				} )
				.catch( function () {
					btn.disabled = false;
					result.hidden = false;
					result.textContent = 'אירעה שגיאה. נסו שוב.';
				} );
		}
		el.addEventListener( 'click', function ( e ) {
			var saveBtn = e.target.closest( '[data-kindi-scpop-save]' );
			if ( saveBtn ) { save( saveBtn ); return; }
			if ( e.target.closest( '[data-kindi-scpop-close]' ) ) { close(); }
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( el.hidden ) { return; }
			if ( 'Escape' === e.key ) { close(); return; }
			if ( 'Tab' === e.key ) {
				var f = el.querySelectorAll( 'button, input' );
				var first = f[ 0 ], last = f[ f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) { e.preventDefault(); last.focus(); }
				else if ( ! e.shiftKey && document.activeElement === last ) { e.preventDefault(); first.focus(); }
			}
		} );
		// Non-AJAX add: the server flagged this page load (count already checked).
		if ( '1' === el.dataset.open ) { setTimeout( open, 600 ); }
		// AJAX add: WooCommerce emits `added_to_cart` only as a jQuery event, so
		// the bridge rides the jQuery WC already loads — the theme enqueues none.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart', function () {
				count++;
				setTimeout( open, 500 );
			} );
		}
	}() );
	</script>
	<?php
}
add_action( 'wp_footer', 'kindi_savecart_popup', 31 );
