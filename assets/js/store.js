/*
 * Kindi store interactions — Vanilla JS. Slide-out mini-cart + wishlist.
 */
( function () {
	'use strict';

	/* ---------------- Mini-cart drawer ---------------- */
	var drawer = document.querySelector( '[data-kindi-cart]' );
	if ( drawer ) {
		var openCart = function () {
			drawer.hidden = false;
			document.body.style.overflow = 'hidden';
		};
		var closeCart = function () {
			drawer.hidden = true;
			document.body.style.overflow = '';
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
			if ( 'Escape' === e.key && ! drawer.hidden ) {
				closeCart();
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
			btn.classList.toggle( 'is-active', ids.indexOf( id ) !== -1 );
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
					form.innerHTML = '<p class="kindi-news__done">' + ( data.message || 'תודה!' ) + '</p>';
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

	/* Wishlist page — render saved products via REST. */
	var wrap = document.querySelector( '[data-kindi-wishlist-grid]' );
	if ( wrap && typeof window.kindiStore !== 'undefined' ) {
		var ids = read();
		if ( ! ids.length ) {
			wrap.innerHTML = '<p class="kindi-prod-empty">רשימת המועדפים ריקה. הוסיפו מוצרים בעזרת ❤</p>';
		} else {
			fetch( window.kindiStore.productsUrl + '?ids=' + ids.join( ',' ), {
				headers: { 'X-WP-Nonce': window.kindiStore.nonce },
			} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( items ) {
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
				.catch( function () {} );
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
