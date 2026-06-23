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
