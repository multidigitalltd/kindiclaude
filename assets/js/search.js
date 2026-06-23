/*
 * Kindi live search — Vanilla JS, no dependencies. Debounced REST suggestions
 * with product thumbnails, prices and category matches. Keyboard accessible.
 */
( function () {
	'use strict';

	if ( typeof window.kindiSearch === 'undefined' ) {
		return;
	}

	var cfg = window.kindiSearch;

	document.querySelectorAll( '.kindi-search' ).forEach( function ( form ) {
		var input = form.querySelector( 'input[type="search"]' );
		if ( ! input ) {
			return;
		}

		var box = document.createElement( 'div' );
		box.className = 'kindi-suggest';
		box.setAttribute( 'role', 'listbox' );
		box.hidden = true;
		form.appendChild( box );

		var timer = null;
		var lastQuery = '';

		var close = function () {
			box.hidden = true;
			input.setAttribute( 'aria-expanded', 'false' );
		};

		var render = function ( data ) {
			var html = '';

			if ( data.cats && data.cats.length ) {
				html += '<div class="kindi-suggest__group">קטגוריות</div>';
				data.cats.forEach( function ( c ) {
					html += '<a class="kindi-suggest__cat" role="option" href="' + c.url + '">' + c.name +
						' <span>(' + c.count + ')</span></a>';
				} );
			}

			if ( data.products && data.products.length ) {
				html += '<div class="kindi-suggest__group">מוצרים</div>';
				data.products.forEach( function ( p ) {
					html += '<a class="kindi-suggest__item" role="option" href="' + p.url + '">' +
						'<img src="' + p.img + '" alt="" loading="lazy" width="48" height="48">' +
						'<span class="kindi-suggest__t">' + p.title + '</span>' +
						'<span class="kindi-suggest__p">' + ( p.price || '' ) + '</span></a>';
				} );
				html += '<a class="kindi-suggest__all" href="' + data.all + '">' + cfg.allText + ' "' + lastQuery + '" ←</a>';
			} else if ( ! data.cats || ! data.cats.length ) {
				html = '<div class="kindi-suggest__empty">' + cfg.noResults + '</div>';
			}

			box.innerHTML = html;
			box.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );
		};

		var fetchResults = function ( q ) {
			fetch( cfg.url + '?q=' + encodeURIComponent( q ), {
				headers: { 'X-WP-Nonce': cfg.nonce },
			} )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( data ) {
					if ( input.value.trim() === q ) {
						render( data );
					}
				} )
				.catch( function () {} );
		};

		input.addEventListener( 'input', function () {
			var q = input.value.trim();
			lastQuery = q;
			window.clearTimeout( timer );

			if ( q.length < 2 ) {
				close();
				return;
			}

			timer = window.setTimeout( function () {
				fetchResults( q );
			}, 250 );
		} );

		input.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				close();
			}
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! form.contains( e.target ) ) {
				close();
			}
		} );
	} );
}() );
