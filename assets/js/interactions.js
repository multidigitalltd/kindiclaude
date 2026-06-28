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

		var show = function () {
			drawer.hidden = false;
			document.body.style.overflow = 'hidden';
			var firstLink = drawer.querySelector( '.kindi-drawer__close' );
			if ( firstLink ) {
				firstLink.focus();
			}
		};

		var hide = function () {
			drawer.hidden = true;
			document.body.style.overflow = '';
			openBtn.focus();
		};

		openBtn.addEventListener( 'click', show );
		closers.forEach( function ( el ) {
			el.addEventListener( 'click', hide );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! drawer.hidden ) {
				hide();
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

/* Expose the header height so the (position:fixed) mega panel anchors right below
 * the category nav even while the nav scrolls horizontally. */
( function () {
	'use strict';
	var header = document.querySelector( '.kindi-header' );
	if ( ! header ) {
		return;
	}
	var setH = function () {
		document.documentElement.style.setProperty( '--kindi-header-h', header.offsetHeight + 'px' );
	};
	setH();
	window.addEventListener( 'load', setH );
	window.addEventListener( 'resize', setH );
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
