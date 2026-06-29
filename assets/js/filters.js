/*
 * Kindi instant archive filters — Vanilla JS, progressive enhancement.
 * Intercepts filter / pagination / sort actions, fetches the SAME server-rendered
 * URL the link already points to, and swaps just the results region (no full
 * reload). Falls back to normal navigation if anything fails or JS is off.
 */
( function () {
	'use strict';

	// Regions replaced from the fetched document, in document order.
	var SWAP = [
		'.woocommerce-result-count',
		'.woocommerce-ordering',
		'.kindi-filters',
		'ul.products',
		'.woocommerce-pagination',
	];

	var scope = document.querySelector( '.woocommerce' ) || document.getElementById( 'main' );
	if ( ! scope || ! scope.querySelector( 'ul.products' ) ) {
		return;
	}

	var sameOrigin = function ( href ) {
		try {
			return new URL( href, location.href ).origin === location.origin;
		} catch ( e ) {
			return false;
		}
	};

	var setBusy = function ( on ) {
		var g = scope.querySelector( 'ul.products' );
		if ( g ) {
			g.setAttribute( 'aria-busy', on ? 'true' : 'false' );
			g.style.opacity = on ? '0.45' : '';
		}
	};

	var load = function ( url, push ) {
		setBusy( true );
		fetch( url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' } )
			.then( function ( r ) { return r.text(); } )
			.then( function ( html ) {
				var doc = new DOMParser().parseFromString( html, 'text/html' );
				SWAP.forEach( function ( sel ) {
					var cur = scope.querySelector( sel );
					var next = doc.querySelector( sel );
					if ( cur && next ) {
						cur.replaceWith( next );
					} else if ( cur && ! next ) {
						cur.remove();
					}
				} );
				if ( push ) {
					history.pushState( { kindiFilter: true }, '', url );
				}
				setBusy( false );
				var top = scope.querySelector( '.kindi-filters' ) || scope.querySelector( 'ul.products' );
				if ( top ) {
					var y = top.getBoundingClientRect().top + window.pageYOffset - 120;
					window.scrollTo( { top: y > 0 ? y : 0, behavior: 'auto' } );
				}
			} )
			.catch( function () { window.location.href = url; } );
	};

	// Filter chips/options + pagination links.
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( '.kindi-filters a, .woocommerce-pagination a' );
		if ( ! a || a.target === '_blank' ) {
			return;
		}
		var href = a.getAttribute( 'href' );
		if ( ! href || ! sameOrigin( href ) ) {
			return;
		}
		e.preventDefault();
		load( new URL( href, location.href ).href, true );
	} );

	// Price form (explicit submit via the "סינון" button).
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target.closest( '.kindi-filters form' );
		if ( ! form ) {
			return;
		}
		e.preventDefault();
		var params = new URLSearchParams( location.search );
		new FormData( form ).forEach( function ( v, k ) {
			if ( '' !== v ) {
				params.set( k, v );
			} else {
				params.delete( k );
			}
		} );
		var u = new URL( location.href );
		u.search = params.toString();
		load( u.href, true );
	} );

	// Sort dropdown — WooCommerce submits programmatically (no submit event),
	// so listen on the select's change directly.
	document.addEventListener( 'change', function ( e ) {
		var sel = e.target.closest( '.woocommerce-ordering select.orderby' );
		if ( ! sel ) {
			return;
		}
		var params = new URLSearchParams( location.search );
		params.set( 'orderby', sel.value );
		params.delete( 'paged' );
		var u = new URL( location.href );
		u.search = params.toString();
		load( u.href, true );
	} );

	window.addEventListener( 'popstate', function () {
		load( location.href, false );
	} );
}() );
