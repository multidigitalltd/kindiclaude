/**
 * Gift card page — the two accessibility gaps the plugin leaves.
 *
 * 1. The amount buttons show which one is chosen with a class and a colour, and
 *    say nothing to a screen reader. The class is mirrored onto aria-pressed.
 * 2. The design tiles are plain <div>s: no keyboard focus, no role, no state.
 *    They become real buttons, operable with Enter and Space.
 *
 * Nothing here changes any text, and the plugin's own click handling is left
 * untouched — a keypress is forwarded as a click, which is what it listens for.
 */
( function () {
	'use strict';

	var amounts = document.querySelector( '.gift-cards-list' );
	var designs = document.querySelector( '.ywgc-design-list' );
	if ( ! amounts && ! designs ) {
		return;
	}

	/**
	 * Mirror a selection class onto aria-pressed for a set of controls.
	 *
	 * @param {Element} root     Container to look inside.
	 * @param {string}  selector Controls to mark.
	 * @param {string}  cls      Class the plugin sets on the chosen one.
	 */
	var reflect = function ( root, selector, cls ) {
		if ( ! root ) {
			return;
		}
		var sync = function () {
			root.querySelectorAll( selector ).forEach( function ( el ) {
				el.setAttribute( 'aria-pressed', el.classList.contains( cls ) ? 'true' : 'false' );
			} );
		};
		sync();
		// The plugin rewrites these nodes when a design or amount changes, so the
		// state is re-applied rather than set once.
		new window.MutationObserver( sync ).observe( root, {
			subtree: true,
			childList: true,
			attributes: true,
			attributeFilter: [ 'class' ],
		} );
	};

	reflect( amounts, 'button.ywgc-predefined-amount-button', 'selected_button' );

	if ( designs ) {
		designs.querySelectorAll( '.ywgc-preset-image' ).forEach( function ( tile ) {
			if ( 'BUTTON' === tile.tagName ) {
				return;
			}
			tile.setAttribute( 'role', 'button' );
			tile.setAttribute( 'tabindex', '0' );
			var img = tile.querySelector( 'img' );
			var alt = img && img.getAttribute( 'alt' );
			tile.setAttribute( 'aria-label', alt ? 'עיצוב: ' + alt : 'בחירת עיצוב לכרטיס המתנה' );
		} );
		designs.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' !== e.key && ' ' !== e.key ) {
				return;
			}
			var tile = e.target.closest( '.ywgc-preset-image' );
			if ( ! tile ) {
				return;
			}
			e.preventDefault(); // Space would otherwise scroll the page.
			tile.click();
		} );
		reflect( designs, '.ywgc-preset-image', 'selected_image_parent' );
	}
}() );
