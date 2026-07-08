/*
 * Kindi instant archive filters + view toggle — Vanilla JS, progressive
 * enhancement. Intercepts sidebar filter / pagination / sort actions, fetches
 * the same server-rendered URL and swaps just the results regions (no full
 * reload). Falls back to normal navigation if anything fails or JS is off.
 */
( function () {
	'use strict';

	// Swap the whole archive block as one unit. Doing it region-by-region drops
	// the "no results" message when a filter yields zero products (the fetched
	// page has no ul.products), so replace the entire .kindi-archive instead —
	// toolbar, sidebar, grid (or empty state) and pagination all stay in sync.
	var SWAP = [ '.kindi-archive' ];

	var scope = document.querySelector( '.woocommerce' ) || document.getElementById( 'main' );
	if ( ! scope || ! scope.querySelector( '.kindi-archive' ) ) {
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
				// The swapped-in markup has no is-filters-open class, so the mobile
				// drawer closes itself after every selection — the shopper lands
				// straight on the filtered results.
				document.body.style.overflow = '';
				setBusy( false );
				applyView();
				initPriceSlider();
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

	/* ---- Price range slider (dual handle, RTL) -------------------------- */
	function initPriceSlider() {
		document.querySelectorAll( '.kindi-priceslider' ).forEach( function ( box ) {
			if ( box.dataset.wired ) { return; }
			box.dataset.wired = '1';
			var lo = box.querySelector( '.kindi-priceslider__lo' );
			var hi = box.querySelector( '.kindi-priceslider__hi' );
			var fill = box.querySelector( '.kindi-priceslider__fill' );
			var pill = box.querySelector( '[data-pl-pill]' );
			if ( ! lo || ! hi ) { return; }
			var min = parseInt( box.dataset.min, 10 ) || 0;
			var max = parseInt( box.dataset.max, 10 ) || 100;
			var range = ( max - min ) || 1;
			function paint() {
				var a = Math.min( +lo.value, +hi.value );
				var b = Math.max( +lo.value, +hi.value );
				if ( fill ) {
					fill.style.right = ( ( a - min ) / range * 100 ) + '%';
					fill.style.width = ( ( b - a ) / range * 100 ) + '%';
				}
				if ( pill ) { pill.textContent = 'עד ₪' + b; }
			}
			function commit() {
				var a = Math.min( +lo.value, +hi.value );
				var b = Math.max( +lo.value, +hi.value );
				var u = new URL( location.href );
				u.searchParams.set( 'min_price', a );
				u.searchParams.set( 'max_price', b );
				u.searchParams.delete( 'paged' );
				load( u.href, true );
			}
			lo.addEventListener( 'input', paint );
			hi.addEventListener( 'input', paint );
			lo.addEventListener( 'change', commit );
			hi.addEventListener( 'change', commit );
			paint();
		} );
	}
	initPriceSlider();

	/* ---- Mobile filter drawer ------------------------------------------ */
	document.addEventListener( 'click', function ( e ) {
		var arch = document.querySelector( '.kindi-archive' );
		if ( ! arch ) { return; }
		if ( e.target.closest( '[data-kindi-filters-open]' ) ) {
			arch.classList.add( 'is-filters-open' );
			document.body.style.overflow = 'hidden';
		} else if ( e.target.closest( '[data-kindi-filters-close]' ) ) {
			arch.classList.remove( 'is-filters-open' );
			document.body.style.overflow = '';
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key ) {
			var arch = document.querySelector( '.kindi-archive.is-filters-open' );
			if ( arch ) { arch.classList.remove( 'is-filters-open' ); document.body.style.overflow = ''; }
		}
	} );
}() );
