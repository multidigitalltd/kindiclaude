/*
 * Kindi store interactions — Vanilla JS. Slide-out mini-cart + wishlist.
 */
( function () {
	'use strict';

	/* ---------------- Mini-cart drawer ---------------- */
	var drawer = document.querySelector( '[data-kindi-cart]' );
	if ( drawer ) {
		var panel = drawer.querySelector( '.kindi-cartdrawer__panel' );
		var lastFocus = null;
		var focusables = function () {
			return Array.prototype.slice.call(
				( panel || drawer ).querySelectorAll( 'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])' )
			).filter( function ( el ) { return el.offsetParent !== null; } );
		};
		var openCart = function () {
			lastFocus = document.activeElement;
			drawer.hidden = false;
			document.body.style.overflow = 'hidden';
			var f = focusables();
			( f[0] || panel ).focus();
		};
		var closeCart = function () {
			drawer.hidden = true;
			document.body.style.overflow = '';
			if ( lastFocus && lastFocus.focus ) {
				lastFocus.focus();
			}
		};

		document.querySelectorAll( '.kindi-cart' ).forEach( function ( link ) {
			link.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				openCart();
			} );
		} );
		drawer.querySelectorAll( '[data-kindi-cart-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', closeCart );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( drawer.hidden ) {
				return;
			}
			if ( 'Escape' === e.key ) {
				closeCart();
			} else if ( 'Tab' === e.key ) {
				// Trap focus inside the dialog (WCAG 2.1.2 / 2.4.3).
				var f = focusables();
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

		// Open automatically when an item is added (cart count changes).
		var badge = document.querySelector( '.kindi-cart-count' );
		if ( badge && window.MutationObserver ) {
			var prev = badge.textContent.trim();
			new MutationObserver( function () {
				var now = badge.textContent.trim();
				if ( now !== prev && parseInt( now, 10 ) > parseInt( prev, 10 ) ) {
					openCart();
				}
				prev = now;
			} ).observe( badge, { childList: true, characterData: true, subtree: true } );
		}
	}

	/* ---------------- Wishlist (localStorage) ---------------- */
	var KEY = 'kindi_wishlist';

	var read = function () {
		try {
			return JSON.parse( localStorage.getItem( KEY ) || '[]' );
		} catch ( e ) {
			return [];
		}
	};
	var write = function ( ids ) {
		try {
			localStorage.setItem( KEY, JSON.stringify( ids ) );
		} catch ( e ) {}
	};

	var updateCount = function () {
		var n = read().length;
		document.querySelectorAll( '[data-kindi-wish-count]' ).forEach( function ( el ) {
			el.textContent = n;
			el.hidden = n === 0;
		} );
	};

	var paintButtons = function () {
		var ids = read();
		document.querySelectorAll( '[data-kindi-wish]' ).forEach( function ( btn ) {
			var id = btn.getAttribute( 'data-kindi-wish' );
			var on = ids.indexOf( id ) !== -1;
			btn.classList.toggle( 'is-active', on );
			btn.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
	};

	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-kindi-wish]' );
		if ( ! btn ) {
			return;
		}
		e.preventDefault();
		var id = btn.getAttribute( 'data-kindi-wish' );
		var ids = read();
		var i = ids.indexOf( id );
		if ( i === -1 ) {
			ids.push( id );
		} else {
			ids.splice( i, 1 );
		}
		write( ids );
		paintButtons();
		updateCount();
	} );

	paintButtons();
	updateCount();

	/* ---------------- Newsletter subscribe ---------------- */
	document.querySelectorAll( '.kindi-news__form' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( typeof window.kindiStore === 'undefined' ) {
				return;
			}
			var input = form.querySelector( 'input[type="email"]' );
			var btn = form.querySelector( 'button' );
			if ( ! input || ! input.value ) {
				return;
			}
			if ( btn ) {
				btn.disabled = true;
			}
			fetch( window.kindiStore.subscribeUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': window.kindiStore.nonce },
				body: JSON.stringify( { email: input.value } ),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					form.innerHTML = '<p class="kindi-news__done" role="status">' + ( data.message || 'תודה!' ) + '</p>';
				} )
				.catch( function () {
					if ( btn ) {
						btn.disabled = false;
					}
				} );
		} );
	} );

	/* ---------------- Back-in-stock waitlist ---------------- */
	document.querySelectorAll( '[data-kindi-waitlist]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( typeof window.kindiStore === 'undefined' ) {
				return;
			}
			var msg = form.querySelector( '.kindi-waitlist__msg' );
			var btn = form.querySelector( 'button' );
			var body = new URLSearchParams( new FormData( form ) );
			body.append( 'action', 'kindi_waitlist' );
			if ( btn ) {
				btn.disabled = true;
			}
			fetch( window.kindiStore.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: body.toString(),
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					if ( msg ) {
						msg.textContent = ( data.data && data.data.message ) || 'תודה!';
					}
					if ( data.success ) {
						form.querySelector( '.kindi-waitlist__row' ).style.display = 'none';
						if ( btn ) {
							btn.style.display = 'none';
						}
					} else if ( btn ) {
						btn.disabled = false;
					}
				} )
				.catch( function () {
					if ( btn ) {
						btn.disabled = false;
					}
				} );
		} );
	} );

	/* Hot-products quick-filter tabs — fetch the grid for the chosen source and
	 * swap it in, no full reload. */
	var tabsWrap = document.querySelector( '[data-kindi-hot-tabs]' );
	var hotGrid = document.querySelector( '[data-kindi-hot-grid]' );
	if ( tabsWrap && hotGrid && typeof window.kindiStore !== 'undefined' && window.kindiStore.hotUrl ) {
		tabsWrap.addEventListener( 'click', function ( e ) {
			var tab = e.target.closest( '.kindi-tab' );
			if ( ! tab || tab.classList.contains( 'is-active' ) ) {
				return;
			}
			tabsWrap.querySelectorAll( '.kindi-tab' ).forEach( function ( t ) {
				t.classList.remove( 'is-active' );
				t.setAttribute( 'aria-selected', 'false' );
			} );
			tab.classList.add( 'is-active' );
			tab.setAttribute( 'aria-selected', 'true' );

			var source = tab.getAttribute( 'data-source' );
			var sep = window.kindiStore.hotUrl.indexOf( '?' ) === -1 ? '?' : '&';
			hotGrid.style.opacity = '0.45';
			fetch( window.kindiStore.hotUrl + sep + 'source=' + encodeURIComponent( source ), { credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					hotGrid.innerHTML = ( data && data.html ) ? data.html : '';
					hotGrid.style.opacity = '';
				} )
				.catch( function () { hotGrid.style.opacity = ''; } );
		} );
	}

	/* Wishlist page — render saved products via REST. */
	var wrap = document.querySelector( '[data-kindi-wishlist-grid]' );
	if ( wrap && typeof window.kindiStore !== 'undefined' ) {
		var ids = read();
		if ( ! ids.length ) {
			wrap.innerHTML = '<p class="kindi-prod-empty">רשימת המועדפים ריקה. הוסיפו מוצרים בעזרת ❤</p>';
		} else {
			// Use the correct separator — rest_url() already contains "?" on sites
			// with plain permalinks (?rest_route=...).
			var sep = window.kindiStore.productsUrl.indexOf( '?' ) === -1 ? '?' : '&';
			fetch( window.kindiStore.productsUrl + sep + 'ids=' + encodeURIComponent( ids.join( ',' ) ), {
				headers: { 'X-WP-Nonce': window.kindiStore.nonce },
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( data ) {
					// New shape: { items, html }. Prefer the rendered grid (identical
					// to the shop archive); fall back to simple cards if absent.
					var items = ( data && data.items ) ? data.items : ( Array.isArray( data ) ? data : [] );
					if ( data && data.html ) {
						wrap.innerHTML = data.html;
						return;
					}
					if ( ! items.length ) {
						wrap.innerHTML = '<p class="kindi-prod-empty">המוצרים אינם זמינים יותר.</p>';
						return;
					}
					wrap.innerHTML = items.map( function ( p ) {
						return '<a class="kindi-wish-card" href="' + p.url + '">' +
							'<img src="' + p.img + '" alt="" loading="lazy">' +
							'<span class="kindi-wish-card__t">' + p.title + '</span>' +
							'<span class="kindi-wish-card__p">' + ( p.price || '' ) + '</span></a>';
					} ).join( '' );
				} )
				.catch( function () {
					wrap.innerHTML = '<p class="kindi-prod-empty">לא ניתן לטעון את המועדפים כעת. נסו לרענן.</p>';
				} );
		}
	}
}() );

/* Save & share cart — posts the current cart to the server and shows a share link. */
( function () {
	'use strict';
	var panel = document.querySelector( '[data-kindi-savecart]' );
	if ( ! panel || typeof window.kindiStore === 'undefined' ) {
		return;
	}
	var btn = panel.querySelector( '[data-kindi-savecart-btn]' );
	var emailEl = panel.querySelector( '[data-kindi-savecart-email]' );
	var nonceEl = panel.querySelector( 'input[name="nonce"]' );
	var result = panel.querySelector( '[data-kindi-savecart-result]' );

	btn.addEventListener( 'click', function () {
		btn.disabled = true;
		var body = new URLSearchParams();
		body.set( 'action', 'kindi_save_cart' );
		body.set( 'nonce', nonceEl ? nonceEl.value : '' );
		body.set( 'email', emailEl ? emailEl.value : '' );

		fetch( window.kindiStore.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				btn.disabled = false;
				if ( ! res || ! res.success ) {
					result.hidden = false;
					result.textContent = ( res && res.data && res.data.message ) || 'אירעה שגיאה.';
					return;
				}
				result.hidden = false;
				result.innerHTML = '';
				var msg = document.createElement( 'p' );
				msg.textContent = res.data.message;
				var box = document.createElement( 'div' );
				box.className = 'kindi-savecart__link';
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
					if ( navigator.clipboard ) {
						navigator.clipboard.writeText( input.value );
					} else {
						document.execCommand( 'copy' );
					}
					copy.textContent = 'הועתק ✓';
				} );
				box.appendChild( input );
				box.appendChild( copy );
				result.appendChild( msg );
				result.appendChild( box );
			} )
			.catch( function () {
				btn.disabled = false;
				result.hidden = false;
				result.textContent = 'אירעה שגיאה. נסו שוב.';
			} );
	} );
}() );

/* Product summary "more info" / reviews links → open the matching tab + scroll. */
( function () {
	document.addEventListener( 'click', function ( e ) {
		var trigger = e.target.closest && e.target.closest( '[data-kindi-tab]' );
		if ( ! trigger ) {
			return;
		}
		var key = trigger.getAttribute( 'data-kindi-tab' );
		var tabLink = document.querySelector( '.wc-tabs li.' + key + '_tab a, .wc-tabs li a[href="#tab-' + key + '"]' );
		var panel = document.getElementById( 'tab-' + key );
		var target = panel || tabLink || document.querySelector( '.woocommerce-tabs' );
		if ( ! tabLink && ! panel ) {
			return; // No tabs on this layout — let the default anchor behaviour run.
		}
		e.preventDefault();
		if ( tabLink ) {
			tabLink.click();
		}
		if ( target ) {
			target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	} );
}() );

/* "Buy now" — set the flag so the add-to-cart redirects to checkout. */
( function () {
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest && e.target.closest( '[data-kindi-buynow]' );
		if ( ! btn ) {
			return;
		}
		var form = btn.closest( 'form.cart' );
		var flag = form && form.querySelector( '[data-kindi-buynow-flag]' );
		if ( flag ) {
			flag.value = '1';
		}
	} );
}() );
