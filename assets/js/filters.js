/*
 * Kindi instant archive filters + view toggle — Vanilla JS, progressive
 * enhancement. Intercepts sidebar filter / pagination / sort actions, fetches
 * the same server-rendered URL and swaps just the results regions (no full
 * reload). Falls back to normal navigation if anything fails or JS is off.
 */
( function () {
	'use strict';

	// Regions replaced from the fetched document, in document order.
	var SWAP = [
		'.kindi-archive__toolbar',
		'.kindi-archive__side',
		'ul.products',
		'.woocommerce-pagination',
	];

	var scope = document.querySelector( '.woocommerce' ) || document.getElementById( 'main' );
	if ( ! scope || ! scope.querySelector( 'ul.products' ) ) {
		return;
	}

	var VIEW_KEY = 'kindi_view';

	/* ---- List / grid view toggle ---------------------------------------- */
	function applyView() {
		var root = document.querySelector( '.kindi-archive' );
		var view = 'list';
		try { view = localStorage.getItem( VIEW_KEY ) === 'list' ? 'list' : 'grid'; } catch ( e ) { view = 'grid'; }
		if ( root ) {
			root.classList.toggle( 'is-list', 'list' === view );
		}
		document.querySelectorAll( '[data-kindi-view] .kindi-view__btn' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', b.getAttribute( 'data-view' ) === view );
		} );
	}
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-kindi-view] .kindi-view__btn' );
		if ( ! btn ) { return; }
		try { localStorage.setItem( VIEW_KEY, btn.getAttribute( 'data-view' ) ); } catch ( e2 ) {}
		applyView();
	} );
	applyView();

	/* ---- AJAX filtering -------------------------------------------------- */
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
				applyView();
				var top = scope.querySelector( '.kindi-archive__toolbar' ) || scope.querySelector( 'ul.products' );
				if ( top ) {
					var y = top.getBoundingClientRect().top + window.pageYOffset - 120;
					window.scrollTo( { top: y > 0 ? y : 0, behavior: 'auto' } );
				}
			} )
			.catch( function () { window.location.href = url; } );
	};

	// Sidebar filter/option links + pagination (NOT the hero category chips —
	// those change the whole context, so they navigate normally).
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( '.kindi-archive__side a, .woocommerce-pagination a' );
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
		var form = e.target.closest( '.kindi-archive__side form' );
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

	// Sort dropdown — WooCommerce submits programmatically (no submit event).
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
