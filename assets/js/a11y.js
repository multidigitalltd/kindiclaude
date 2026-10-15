/*
 * Kindi accessibility toolbar — Vanilla JS. Persists preferences in localStorage
 * and applies them to <html>. Honours keyboard + screen readers.
 */
( function () {
	'use strict';

	var KEY = 'kindi_a11y';
	var root = document.documentElement;
	var panel = document.querySelector( '[data-kindi-a11y-panel]' );
	var toggle = document.querySelector( '[data-kindi-a11y]' );

	var state = ( function () {
		try {
			return JSON.parse( localStorage.getItem( KEY ) || '{}' );
		} catch ( e ) {
			return {};
		}
	}() );

	var save = function () {
		try {
			localStorage.setItem( KEY, JSON.stringify( state ) );
		} catch ( e ) {}
	};

	var apply = function () {
		root.classList.toggle( 'a11y-contrast', !! state.contrast );
		root.classList.toggle( 'a11y-links', !! state.links );
		root.classList.toggle( 'a11y-readable', !! state.readable );
		root.classList.toggle( 'a11y-stop-motion', !! state.motion );
		var step = state.font || 0;
		root.style.fontSize = step ? ( 100 + step * 12.5 ) + '%' : '';
		// Reflect each toggle's on/off state to screen readers.
		if ( panel ) {
			panel.querySelectorAll( '[data-a11y][aria-pressed]' ).forEach( function ( btn ) {
				btn.setAttribute( 'aria-pressed', state[ btn.getAttribute( 'data-a11y' ) ] ? 'true' : 'false' );
			} );
		}
	};

	apply();

	if ( toggle && panel ) {
		var openPanel = function () {
			panel.hidden = false;
			toggle.setAttribute( 'aria-expanded', 'true' );
			var closeBtn = panel.querySelector( '[data-kindi-a11y-close]' );
			if ( closeBtn ) {
				closeBtn.focus();
			}
		};
		var closePanel = function ( returnFocus ) {
			panel.hidden = true;
			toggle.setAttribute( 'aria-expanded', 'false' );
			if ( returnFocus ) {
				toggle.focus();
			}
		};
		toggle.addEventListener( 'click', function () {
			if ( panel.hidden ) {
				openPanel();
			} else {
				closePanel( true );
			}
		} );
		var closer = panel.querySelector( '[data-kindi-a11y-close]' );
		if ( closer ) {
			closer.addEventListener( 'click', function () {
				closePanel( true );
			} );
		}
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! panel.hidden ) {
				closePanel( true );
			}
		} );

		panel.querySelectorAll( '[data-a11y]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				switch ( btn.getAttribute( 'data-a11y' ) ) {
					case 'font-inc':
						state.font = Math.min( 4, ( state.font || 0 ) + 1 );
						break;
					case 'font-dec':
						state.font = Math.max( -2, ( state.font || 0 ) - 1 );
						break;
					case 'contrast':
						state.contrast = ! state.contrast;
						break;
					case 'links':
						state.links = ! state.links;
						break;
					case 'readable':
						state.readable = ! state.readable;
						break;
					case 'motion':
						state.motion = ! state.motion;
						break;
					case 'reset':
						state = {};
						break;
				}
				save();
				apply();
			} );
		} );
	}
}() );
