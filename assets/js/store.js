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

		// WooCommerce fires this (via jQuery) after an AJAX add-to-cart — open the
		// drawer so the shopper sees the item landed in the cart.
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'added_to_cart', function () {
				openCart();
			} );
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

	/* Hot-products quick-filter tabs — accessible tablist. Click or keyboard:
	 * arrow keys move focus with roving tabindex (RTL-aware); Enter/Space (native
	 * <button>) activates and swaps the grid in with no full reload. */
	var tabsWrap = document.querySelector( '[data-kindi-hot-tabs]' );
	var hotGrid = document.querySelector( '[data-kindi-hot-grid]' );
	if ( tabsWrap && hotGrid && typeof window.kindiStore !== 'undefined' && window.kindiStore.hotUrl ) {
		var tabs = Array.prototype.slice.call( tabsWrap.querySelectorAll( '.kindi-tab' ) );

		var activateTab = function ( tab ) {
			if ( ! tab || tab.classList.contains( 'is-active' ) ) {
				return;
			}
			tabs.forEach( function ( t ) {
				t.classList.remove( 'is-active' );
				t.setAttribute( 'aria-selected', 'false' );
				t.setAttribute( 'tabindex', '-1' );
			} );
			tab.classList.add( 'is-active' );
			tab.setAttribute( 'aria-selected', 'true' );
			tab.setAttribute( 'tabindex', '0' );
			if ( tab.id ) {
				hotGrid.setAttribute( 'aria-labelledby', tab.id );
			}

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
		};

		tabsWrap.addEventListener( 'click', function ( e ) {
			activateTab( e.target.closest( '.kindi-tab' ) );
		} );

		tabsWrap.addEventListener( 'keydown', function ( e ) {
			var current = tabs.indexOf( document.activeElement );
			if ( current === -1 ) {
				return;
			}
			var next;
			if ( 'ArrowLeft' === e.key ) {
				next = current + 1; // RTL: left arrow → next tab.
			} else if ( 'ArrowRight' === e.key ) {
				next = current - 1; // RTL: right arrow → previous tab.
			} else if ( 'Home' === e.key ) {
				next = 0;
			} else if ( 'End' === e.key ) {
				next = tabs.length - 1;
			} else {
				return;
			}
			e.preventDefault();
			next = ( next + tabs.length ) % tabs.length;
			tabs[ current ].setAttribute( 'tabindex', '-1' );
			tabs[ next ].setAttribute( 'tabindex', '0' );
			tabs[ next ].focus();
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

/* Hide the informational "הלקוח תואם לאזור …" shipping-zone notice site-wide
   (catch-all for any source, incl. AJAX-injected notices). */
( function () {
	'use strict';
	var NEEDLE = 'תואם לאזור';
	var SEL = '.woocommerce-message, .woocommerce-info, .woocommerce-notice, .wc-block-components-notice-banner, .woocommerce-notices-wrapper > *';
	function scrub() {
		document.querySelectorAll( SEL ).forEach( function ( el ) {
			if ( el.textContent && el.textContent.indexOf( NEEDLE ) !== -1 ) {
				el.style.display = 'none';
			}
		} );
	}
	scrub();
	try {
		new MutationObserver( scrub ).observe( document.body, { childList: true, subtree: true } );
	} catch ( e ) {}
}() );

/* ---------------- Mini-cart quantity steppers + single-product AJAX add ----- */
( function () {
	'use strict';
	var cfg = window.kindiStore || {};

	function openDrawer() {
		var link = document.querySelector( '.kindi-cart' );
		if ( link ) { link.click(); }
	}

	function applyFragments( fragments ) {
		if ( ! fragments ) { return; }
		Object.keys( fragments ).forEach( function ( sel ) {
			try {
				document.querySelectorAll( sel ).forEach( function ( el ) {
					var tmp = document.createElement( 'div' );
					tmp.innerHTML = fragments[ sel ];
					if ( tmp.firstElementChild ) { el.replaceWith( tmp.firstElementChild ); }
				} );
			} catch ( e ) {}
		} );
		wireMiniCartQty();
	}

	function postQty( key, qty ) {
		var body = new URLSearchParams();
		body.append( 'action', 'kindi_cart_qty' );
		body.append( 'nonce', cfg.qtyNonce || '' );
		body.append( 'key', key );
		body.append( 'qty', qty );
		return fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) { if ( d && d.success ) { applyFragments( d.data.fragments ); } return d; } )
			.catch( function () {} );
	}

	/* Trigger WooCommerce to recalculate + re-render the checkout summary. */
	function refreshCheckout() {
		if ( window.jQuery ) { window.jQuery( document.body ).trigger( 'update_checkout' ); }
	}

	/* Checkout order-summary: quantity steppers + remove (delegated, survives the
	   AJAX fragment refresh). After changing the cart, recalc the checkout. */
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.kindi-summary' ) ) { return; }
		var stepBtn = e.target.closest( '.kindi-summary .kindi-mcqty__b' );
		var rm = e.target.closest( '.kindi-summary__remove' );
		if ( stepBtn ) {
			var wrap = stepBtn.closest( '.kindi-mcqty' );
			var numEl = wrap.querySelector( '.kindi-mcqty__n' );
			var q = ( parseInt( numEl.textContent, 10 ) || 1 ) + parseInt( stepBtn.getAttribute( 'data-d' ), 10 );
			if ( q < 0 ) { q = 0; }
			numEl.textContent = q;
			postQty( wrap.getAttribute( 'data-key' ), q ).then( refreshCheckout );
		} else if ( rm ) {
			postQty( rm.getAttribute( 'data-key' ), 0 ).then( refreshCheckout );
		}
	} );

	/* Checkout summary coupon box: apply / remove via WooCommerce's wc-ajax. */
	function couponAjax( endpoint, params ) {
		var url = ( cfg.wcAjaxUrl || '/?wc-ajax=%%endpoint%%' ).replace( '%%endpoint%%', endpoint );
		var body = new URLSearchParams();
		Object.keys( params ).forEach( function ( k ) { body.append( k, params[ k ] ); } );
		fetch( url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString() } )
			.then( function ( r ) { return r.text(); } )
			.then( refreshCheckout )
			.catch( function () {} );
	}
	document.addEventListener( 'click', function ( e ) {
		var apply = e.target.closest( '[data-kindi-coupon-apply]' );
		var rmc = e.target.closest( '[data-kindi-coupon-remove]' );
		if ( apply ) {
			var cbox = apply.closest( '[data-kindi-coupon]' );
			var input = cbox && cbox.querySelector( '[data-kindi-coupon-input]' );
			var code = input ? input.value.trim() : '';
			if ( code ) { couponAjax( 'apply_coupon', { coupon_code: code, security: cfg.couponApplyNonce || '' } ); }
		} else if ( rmc ) {
			couponAjax( 'remove_coupon', { coupon: rmc.getAttribute( 'data-kindi-coupon-remove' ), security: cfg.couponRemoveNonce || '' } );
		}
	} );
	// Apply the coupon on Enter inside the code field.
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Enter' !== e.key ) { return; }
		var input = e.target.closest( '[data-kindi-coupon-input]' );
		if ( ! input ) { return; }
		e.preventDefault();
		var cbox = input.closest( '[data-kindi-coupon]' );
		var btn = cbox && cbox.querySelector( '[data-kindi-coupon-apply]' );
		if ( btn ) { btn.click(); }
	} );

	/* Gift-wrap box collapse toggle (checkout). */
	document.addEventListener( 'click', function ( e ) {
		var gift = e.target.closest( '[data-kindi-gift-toggle]' );
		if ( gift ) {
			var g = gift.closest( '[data-kindi-gift]' );
			if ( g ) { g.classList.toggle( 'is-collapsed' ); }
		}
	} );

	/* Toggling gift-wrap recalculates the order (adds/removes the wrap fee). */
	document.addEventListener( 'change', function ( e ) {
		if ( e.target.closest( 'input[name="kindi_gift_wrap"]' ) ) { refreshCheckout(); }
	} );

	/* Gift-card redemption boxes (YITH / Gifta) are positioned server-side, below
	   the coupon, via the kindi_summary_after_coupon hook (see inc/checkout.php). */

	function wireMiniCartQty() {
		document.querySelectorAll( '.kindi-cartdrawer .woocommerce-mini-cart-item' ).forEach( function ( item ) {
			if ( item.dataset.kqty ) { return; }
			var remove = item.querySelector( 'a.remove[data-cart_item_key]' );
			var qEl = item.querySelector( '.quantity' );
			if ( ! remove || ! qEl ) { return; }
			item.dataset.kqty = '1';
			var key = remove.getAttribute( 'data-cart_item_key' );
			// The quantity span reads "{qty} × {price}" — the qty is the leading
			// text node, so parse that (NOT every digit, which would fold the price
			// into the number and show e.g. 1990 instead of 1).
			var qtyNode = qEl.firstChild;
			var qtyText = qtyNode && 3 === qtyNode.nodeType ? qtyNode.nodeValue : qEl.textContent;
			var cur = parseInt( qtyText, 10 ) || 1;
			var amount = qEl.querySelector( '.woocommerce-Price-amount, .amount' );

			var line = document.createElement( 'div' );
			line.className = 'kindi-mcline';
			line.innerHTML =
				'<span class="kindi-mcqty"><button type="button" class="kindi-mcqty__b" data-d="-1" aria-label="הפחתת כמות">&#8722;</button>' +
				'<span class="kindi-mcqty__n">' + cur + '</span>' +
				'<button type="button" class="kindi-mcqty__b" data-d="1" aria-label="הוספת כמות">&#43;</button></span>' +
				'<span class="kindi-mcprice">' + ( amount ? amount.outerHTML : '' ) + '</span>';
			qEl.style.display = 'none';
			qEl.parentNode.insertBefore( line, qEl.nextSibling );

			var num = line.querySelector( '.kindi-mcqty__n' );
			line.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '.kindi-mcqty__b' );
				if ( ! b ) { return; }
				var q = ( parseInt( num.textContent, 10 ) || 1 ) + parseInt( b.getAttribute( 'data-d' ), 10 );
				if ( q < 0 ) { q = 0; }
				num.textContent = q;
				postQty( key, q );
			} );
		} );
	}

	wireMiniCartQty();
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'wc_fragments_refreshed wc_fragments_loaded added_to_cart', wireMiniCartQty );
	}

	/* Single-product add-to-cart via AJAX so the drawer opens (no full reload).
	   Buy-now (flag set) and grouped forms fall through to a normal submit. */
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! ( form instanceof HTMLFormElement ) || ! form.classList.contains( 'cart' ) || form.classList.contains( 'grouped_form' ) ) {
			return;
		}
		var flag = form.querySelector( '[data-kindi-buynow-flag]' );
		if ( flag && '1' === flag.value ) { return; }
		var fd = new FormData( form );
		var pid = fd.get( 'product_id' ) || fd.get( 'add-to-cart' );
		if ( ! pid ) { return; }
		e.preventDefault();
		if ( ! fd.get( 'product_id' ) ) { fd.set( 'product_id', pid ); }
		if ( ! fd.get( 'add-to-cart' ) ) { fd.set( 'add-to-cart', pid ); }

		var base = ( window.wc_cart_fragments_params && window.wc_cart_fragments_params.wc_ajax_url ) || '/?wc-ajax=%%endpoint%%';
		fetch( base.replace( '%%endpoint%%', 'add_to_cart' ), { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( d ) {
				if ( ! d ) { form.submit(); return; }
				if ( d.error && d.product_url ) { window.location = d.product_url; return; }
				applyFragments( d.fragments );
				if ( window.jQuery ) {
					window.jQuery( document.body ).trigger( 'added_to_cart', [ d.fragments, d.cart_hash ] );
				} else {
					openDrawer();
				}
			} )
			.catch( function () { form.submit(); } );
	} );
}() );

/* Checkout "please wait" overlay (markup in inc/checkout.php). Shows when the
 * order form submits; hides again whenever WooCommerce reports a problem
 * (checkout_error / a refreshed review / new notices), so the shopper is never
 * stuck behind it. WooCommerce's own blockUI still guards double-submits; this
 * layer is purely reassurance UX. */
( function () {
	'use strict';
	var overlay = document.querySelector( '[data-kindi-wait]' );
	var form = document.querySelector( 'form.checkout' );
	if ( ! overlay || ! form ) {
		return;
	}

	// Hard double-submit guard: duplicate orders were observed on the live
	// store from rapid double-clicks that beat WooCommerce's blockUI. The
	// button is disabled only AFTER the submit event fires (so serialization
	// is unaffected) and re-enabled by every failure path below.
	var lockBtn = function ( on ) {
		var btn = document.getElementById( 'place_order' );
		if ( ! btn ) {
			return;
		}
		btn.disabled = on;
		if ( on ) {
			btn.setAttribute( 'aria-busy', 'true' );
		} else {
			btn.removeAttribute( 'aria-busy' );
		}
	};

	var show = function () {
		overlay.hidden = false;
		document.body.classList.add( 'kindi-wait-open' );
		form.setAttribute( 'aria-busy', 'true' );
		lockBtn( true );
	};
	var hide = function () {
		overlay.hidden = true;
		document.body.classList.remove( 'kindi-wait-open' );
		form.removeAttribute( 'aria-busy' );
		lockBtn( false );
	};

	form.addEventListener( 'submit', function () {
		// Native validation failures never leave the page — don't cover them.
		if ( 'function' === typeof form.checkValidity && ! form.checkValidity() ) {
			return;
		}
		show();
	} );

	// WooCommerce signals failures/refreshes through jQuery events (it always
	// loads jQuery on checkout; the guard is for safety only).
	if ( window.jQuery ) {
		window.jQuery( document.body ).on( 'checkout_error updated_checkout', hide );
	}

	// Backstop: any new WooCommerce notice (validation errors rendered into the
	// page) means processing stopped — release the overlay.
	var notices = document.querySelector( '.woocommerce-notices-wrapper' );
	if ( notices && 'MutationObserver' in window ) {
		new MutationObserver( hide ).observe( notices, { childList: true, subtree: true } );
	}

	// Returning via the browser back button (bfcache) must never restore a
	// stale overlay.
	window.addEventListener( 'pageshow', hide );
}() );
