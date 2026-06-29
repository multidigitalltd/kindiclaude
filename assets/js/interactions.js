/*
 * Kindi interactions — Vanilla JS only (no jQuery). Mobile category drawer
 * and an accessibility-contrast toggle stub. Keep this tiny and dependency-free.
 */
( function () {
	'use strict';

	// Mobile category drawer.
	var openBtn = document.querySelector( '[data-kindi-menu-open]' );
	var drawer = document.querySelector( '[data-kindi-drawer]' );

	if ( openBtn && drawer ) {
		var closers = drawer.querySelectorAll( '[data-kindi-menu-close]' );

		var panel = drawer.querySelector( '.kindi-drawer__panel' ) || drawer;
		var menuFocusables = function () {
			return Array.prototype.slice.call(
				panel.querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])' )
			).filter( function ( el ) { return el.offsetParent !== null; } );
		};

		var show = function () {
			drawer.hidden = false;
			document.body.style.overflow = 'hidden';
			openBtn.setAttribute( 'aria-expanded', 'true' );
			var firstLink = drawer.querySelector( '.kindi-drawer__close' );
			if ( firstLink ) {
				firstLink.focus();
			}
		};

		var hide = function () {
			drawer.hidden = true;
			document.body.style.overflow = '';
			openBtn.setAttribute( 'aria-expanded', 'false' );
			openBtn.focus();
		};

		openBtn.addEventListener( 'click', show );
		closers.forEach( function ( el ) {
			el.addEventListener( 'click', hide );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( drawer.hidden ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				hide();
			} else if ( 'Tab' === e.key ) {
				var f = menuFocusables();
				if ( ! f.length ) {
					return;
				}
				var first = f[0], last = f[ f.length - 1 ];
				if ( e.shiftKey && document.activeElement === first ) {
					e.preventDefault();
					last.focus();
				} else if ( ! e.shiftKey && document.activeElement === last ) {
					e.preventDefault();
					first.focus();
				}
			}
		} );
	}

}() );

/* Category nav: keep it on one line on desktop by shrinking the font when there
 * are many/long items; on mobile it scrolls horizontally instead. */
( function () {
	'use strict';
	var nav = document.querySelector( '.kindi-nav__inner' );
	if ( ! nav ) {
		return;
	}
	var fit = function () {
		nav.style.removeProperty( '--kindi-nav-fs' );
		if ( window.innerWidth < 1024 ) {
			return; // Mobile: horizontal scroll, no shrinking.
		}
		var fs = 14, guard = 0;
		while ( nav.scrollWidth > nav.clientWidth + 1 && fs > 10.5 && guard < 24 ) {
			fs -= 0.5;
			nav.style.setProperty( '--kindi-nav-fs', fs + 'px' );
			guard++;
		}
	};
	fit();
	window.addEventListener( 'load', fit );
	var t;
	window.addEventListener( 'resize', function () {
		clearTimeout( t );
		t = setTimeout( fit, 150 );
	} );
}() );

/* "Load more reviews" — reveal hidden testimonial cards 3 at a time. */
( function () {
	'use strict';
	var btn = document.querySelector( '[data-kindi-more-reviews]' );
	if ( ! btn ) {
		return;
	}
	btn.addEventListener( 'click', function () {
		var hidden = document.querySelectorAll( '.kindi-tst__card.is-hidden' );
		for ( var i = 0; i < 3 && i < hidden.length; i++ ) {
			hidden[ i ].classList.remove( 'is-hidden' );
		}
		if ( ! document.querySelectorAll( '.kindi-tst__card.is-hidden' ).length ) {
			btn.style.display = 'none';
		}
	} );
}() );

/* Anchor the (position:fixed) mega panel right below the category nav. Only the
 * nav is sticky now, so its bottom moves as the promo strips + search row scroll
 * away — track it on scroll so the mega stays glued under the nav. */
( function () {
	'use strict';
	var nav = document.querySelector( '.kindi-nav' );
	if ( ! nav ) {
		return;
	}
	var ticking = false;
	var setH = function () {
		ticking = false;
		var bottom = Math.max( nav.offsetHeight, nav.getBoundingClientRect().bottom );
		document.documentElement.style.setProperty( '--kindi-header-h', Math.round( bottom ) + 'px' );
	};
	var onScroll = function () {
		if ( ! ticking ) {
			ticking = true;
			window.requestAnimationFrame( setH );
		}
	};
	setH();
	window.addEventListener( 'load', setH );
	window.addEventListener( 'resize', setH );
	window.addEventListener( 'scroll', onScroll, { passive: true } );
}() );

/* Quantity stepper — the +/- buttons injected around the WooCommerce number
 * field (product page + cart). Delegated, so it also covers fields added by
 * AJAX cart updates. */
( function () {
	'use strict';
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.kindi-qbtn' );
		if ( ! btn ) {
			return;
		}
		var wrap = btn.closest( '.quantity' );
		var input = wrap && wrap.querySelector( 'input.qty' );
		if ( ! input || input.disabled || input.readOnly ) {
			return;
		}
		var step = parseFloat( input.getAttribute( 'step' ) ) || 1;
		var minAttr = input.getAttribute( 'min' );
		var maxAttr = input.getAttribute( 'max' );
		var min = ( minAttr !== null && minAttr !== '' ) ? parseFloat( minAttr ) : 1;
		var max = ( maxAttr !== null && maxAttr !== '' ) ? parseFloat( maxAttr ) : Infinity;
		var val = parseFloat( input.value ) || 0;

		val += btn.classList.contains( 'kindi-qbtn--plus' ) ? step : -step;
		if ( val < min ) {
			val = min;
		}
		if ( val > max ) {
			val = max;
		}
		input.value = val;
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
}() );

/* Variation swatches — drive the hidden WooCommerce attribute <select> from the
 * colour circles / pills, keep the active state in sync, and grey out options
 * that the current selection makes unavailable. */
( function () {
	'use strict';
	var boxes = document.querySelectorAll( '.kindi-swatches' );
	if ( ! boxes.length ) {
		return;
	}

	boxes.forEach( function ( box ) {
		var select = box.parentNode.querySelector( 'select[name^="attribute_"]' );
		if ( ! select ) {
			return;
		}
		select.classList.add( 'kindi-select--hidden' );

		var sync = function () {
			var value = select.value;
			box.querySelectorAll( '.kindi-swatch' ).forEach( function ( b ) {
				var v = b.getAttribute( 'data-value' );
				var active = v === value && '' !== value;
				b.classList.toggle( 'is-active', active );
				b.setAttribute( 'aria-pressed', active ? 'true' : 'false' );

				// WooCommerce rebuilds the <option> list per dependent selection;
				// an option that's gone (or disabled) is currently unavailable.
				var opt = select.querySelector( 'option[value="' + ( window.CSS && CSS.escape ? CSS.escape( v ) : v ) + '"]' );
				b.classList.toggle( 'is-unavailable', ! opt || opt.disabled );
			} );
		};

		box.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( '.kindi-swatch' );
			if ( ! b || b.classList.contains( 'is-unavailable' ) ) {
				return;
			}
			var v = b.getAttribute( 'data-value' );
			// Click an active swatch again to clear it.
			select.value = select.value === v ? '' : v;
			select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			sync();
		} );

		select.addEventListener( 'change', sync );
		// WooCommerce triggers its updates through jQuery; bind there too (only if
		// it's already on the page — we never load it ourselves) so the swatches
		// follow programmatic changes / the "clear" link.
		if ( window.jQuery ) {
			window.jQuery( select ).on( 'change', sync );
		}
		sync();
	} );

	// WCAG 1.4.13 — let Esc dismiss the name tooltip on the focused swatch, and
	// clear that suppression as soon as focus moves on.
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key && 'Esc' !== e.key ) {
			return;
		}
		var el = document.activeElement;
		if ( el && el.classList && el.classList.contains( 'kindi-swatch' ) ) {
			el.classList.add( 'kindi-tip-off' );
		}
	} );
	document.addEventListener( 'focusout', function ( e ) {
		if ( e.target && e.target.classList ) {
			e.target.classList.remove( 'kindi-tip-off' );
		}
	} );
}() );
