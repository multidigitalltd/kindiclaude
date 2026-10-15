/**
 * Meta Pixel — deferred interaction events. Fires fbq() (defined by the base
 * code in the head) for: AddToCart (single + loop), scroll depth, time on page,
 * downloads, form submits, comments, and post-login/registration. Also captures
 * first-touch attribution (UTMs / landing / traffic source) into a cookie that
 * the server saves onto the order. Vanilla JS, no dependencies.
 */
( function () {
	'use strict';

	var cfg = window.kindiPixel || {};
	var ev = cfg.events || {};
	var fbq = window.fbq;

	function track( name, params, custom ) {
		if ( typeof window.fbq !== 'function' ) {
			return;
		}
		window.fbq( custom ? 'trackCustom' : 'track', name, params || {} );
	}

	/* ---- First-touch attribution -------------------------------------- */
	if ( cfg.reporting ) {
		try {
			if ( ! /(?:^|;\s*)kindi_attr=/.test( document.cookie ) ) {
				var qs = new URLSearchParams( window.location.search );
				var ref = document.referrer || '';
				var host = window.location.hostname;
				var source = 'direct';
				if ( qs.get( 'utm_source' ) ) {
					source = qs.get( 'utm_source' );
				} else if ( ref && ref.indexOf( host ) === -1 ) {
					source = ref.split( '/' )[ 2 ] || 'referral';
				}
				var attr = {
					utm_source: qs.get( 'utm_source' ) || '',
					utm_medium: qs.get( 'utm_medium' ) || '',
					utm_campaign: qs.get( 'utm_campaign' ) || '',
					utm_term: qs.get( 'utm_term' ) || '',
					utm_content: qs.get( 'utm_content' ) || '',
					landing_page: window.location.href.split( '#' )[ 0 ],
					traffic_source: source
				};
				document.cookie = 'kindi_attr=' + encodeURIComponent( JSON.stringify( attr ) ) +
					';path=/;max-age=' + ( 60 * 60 * 24 * 30 ) + ';SameSite=Lax';
			}
		} catch ( e ) {}
	}

	/* ---- Post-login / registration ------------------------------------ */
	function consumeCookie( name ) {
		var m = document.cookie.match( new RegExp( '(?:^|;\\s*)' + name + '=([^;]*)' ) );
		if ( m ) {
			document.cookie = name + '=;path=/;max-age=0';
			return true;
		}
		return false;
	}
	if ( ev.signup && consumeCookie( 'kindi_px_signup' ) ) {
		track( 'CompleteRegistration' );
	}
	if ( ev.login && consumeCookie( 'kindi_px_login' ) ) {
		track( 'Login', {}, true );
	}

	/* ---- AddToCart ----------------------------------------------------- */
	if ( ev.addToCart ) {
		// Loop / archive buttons carry data-fb-* attributes.
		document.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest && e.target.closest( '.add_to_cart_button[data-fb-id]' );
			if ( ! btn || btn.classList.contains( 'product_type_variable' ) ) {
				return;
			}
			var value = parseFloat( btn.getAttribute( 'data-fb-value' ) ) || 0;
			var id = btn.getAttribute( 'data-fb-id' );
			track( 'AddToCart', {
				content_ids: [ id ],
				content_type: 'product',
				content_name: btn.getAttribute( 'data-fb-name' ) || '',
				value: value,
				currency: cfg.currency,
				contents: [ { id: id, quantity: 1, item_price: value } ]
			} );
		}, true );

		// Single product form.
		if ( cfg.product ) {
			var form = document.querySelector( 'form.cart' );
			if ( form ) {
				form.addEventListener( 'submit', function () {
					var qtyEl = form.querySelector( 'input.qty' );
					var qty = qtyEl ? parseInt( qtyEl.value, 10 ) || 1 : 1;
					var p = cfg.product;
					track( 'AddToCart', {
						content_ids: p.content_ids,
						content_type: 'product',
						content_name: p.content_name,
						value: p.value * qty,
						currency: p.currency,
						contents: [ { id: p.content_ids[ 0 ], quantity: qty, item_price: p.value } ]
					} );
				} );
			}
		}
	}

	/* ---- Scroll depth (90%) ------------------------------------------- */
	if ( ev.scroll ) {
		var scrolled = false;
		window.addEventListener( 'scroll', function () {
			if ( scrolled ) {
				return;
			}
			var h = document.documentElement;
			var pct = ( h.scrollTop + window.innerHeight ) / h.scrollHeight;
			if ( pct >= 0.9 ) {
				scrolled = true;
				track( 'Scroll', { percent: 90 }, true );
			}
		}, { passive: true } );
	}

	/* ---- Time on page (30s) ------------------------------------------- */
	if ( ev.time ) {
		setTimeout( function () {
			track( 'TimeOnPage', { seconds: 30 }, true );
		}, 30000 );
	}

	/* ---- Downloads ----------------------------------------------------- */
	if ( ev.downloads ) {
		var rx = /\.(pdf|zip|rar|7z|docx?|xlsx?|pptx?|csv|txt|mp3|mp4|wav|avi|mov|dmg|pkg|exe|apk)(\?|$)/i;
		document.addEventListener( 'click', function ( e ) {
			var a = e.target.closest && e.target.closest( 'a[href]' );
			if ( a && rx.test( a.getAttribute( 'href' ) ) ) {
				track( 'Download', { file: a.getAttribute( 'href' ) }, true );
			}
		}, true );
	}

	/* ---- Forms (excluding cart / checkout / search) ------------------- */
	if ( ev.forms ) {
		document.addEventListener( 'submit', function ( e ) {
			var f = e.target;
			if ( ! ( f instanceof HTMLFormElement ) ) {
				return;
			}
			if ( f.classList.contains( 'cart' ) || f.classList.contains( 'checkout' ) ||
				f.classList.contains( 'woocommerce-form-login' ) || f.getAttribute( 'role' ) === 'search' ||
				f.querySelector( 'input[type="search"]' ) || f.id === 'commentform' ) {
				return;
			}
			track( 'Lead', {}, true );
		}, true );
	}

	/* ---- Comments ------------------------------------------------------ */
	if ( ev.comments ) {
		var cform = document.getElementById( 'commentform' );
		if ( cform ) {
			cform.addEventListener( 'submit', function () {
				track( 'Comment', {}, true );
			} );
		}
	}
}() );
