/**
 * "Frequently bought together" bundle — live combined total + one-click add of
 * the selected products via the secure REST endpoint. Vanilla JS, no deps.
 */
( function () {
	'use strict';

	var cfg = window.kindiBundle || {};
	var root = document.querySelector( '[data-kindi-bundle]' );
	if ( ! root || ! cfg.url ) {
		return;
	}

	var totalEl = root.querySelector( '[data-kindi-bundle-total]' );
	var addBtn = root.querySelector( '[data-kindi-bundle-add]' );
	var boxes = Array.prototype.slice.call( root.querySelectorAll( '.kindi-bundle__cb' ) );

	function money( value ) {
		return value.toFixed( 2 ).replace( /\.00$/, '' ) + ' ' + ( cfg.symbol || '' );
	}

	function refresh() {
		var total = 0;
		boxes.forEach( function ( cb ) {
			if ( cb.checked ) {
				total += parseFloat( cb.getAttribute( 'data-price' ) ) || 0;
			}
		} );
		if ( totalEl ) {
			totalEl.textContent = money( total );
		}
	}

	boxes.forEach( function ( cb ) {
		cb.addEventListener( 'change', refresh );
	} );
	refresh();

	addBtn.addEventListener( 'click', function () {
		var items = boxes.filter( function ( cb ) {
			return cb.checked;
		} ).map( function ( cb ) {
			return { id: parseInt( cb.getAttribute( 'data-id' ), 10 ), quantity: 1 };
		} );
		if ( ! items.length ) {
			return;
		}

		addBtn.disabled = true;
		addBtn.classList.add( 'is-loading' );

		fetch( cfg.url, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( { items: items } )
		} )
			.then( function ( r ) {
				return r.ok ? r.json() : Promise.reject( r );
			} )
			.then( function () {
				if ( cfg.cartUrl ) {
					window.location.href = cfg.cartUrl;
				} else {
					window.location.reload();
				}
			} )
			.catch( function () {
				addBtn.disabled = false;
				addBtn.classList.remove( 'is-loading' );
				addBtn.textContent = 'אירעה שגיאה — נסו שוב';
			} );
	} );
}() );
