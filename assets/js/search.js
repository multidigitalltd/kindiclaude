/*
 * Kindi live search — Vanilla JS, no dependencies. Debounced REST suggestions
 * with product thumbnails, prices and category matches. Full combobox a11y:
 * arrow-key navigation with aria-activedescendant and a polite result count.
 */
( function () {
	'use strict';

	if ( typeof window.kindiSearch === 'undefined' ) {
		return;
	}

	var cfg = window.kindiSearch;
	var uid = 0;

	document.querySelectorAll( '.kindi-search' ).forEach( function ( form ) {
		var input = form.querySelector( 'input[type="search"]' );
		if ( ! input ) {
			return;
		}

		var id = 'kindi-suggest-' + ( ++uid );
		var box = document.createElement( 'div' );
		box.className = 'kindi-suggest';
		box.id = id;
		box.setAttribute( 'role', 'listbox' );
		box.hidden = true;
		form.appendChild( box );

		// Combobox semantics on the input.
		input.setAttribute( 'role', 'combobox' );
		input.setAttribute( 'aria-autocomplete', 'list' );
		input.setAttribute( 'aria-expanded', 'false' );
		input.setAttribute( 'aria-controls', id );

		// Polite live region announcing the result count.
		var live = document.createElement( 'span' );
		live.className = 'screen-reader-text';
		live.setAttribute( 'aria-live', 'polite' );
		form.appendChild( live );

		var timer = null;
		var lastQuery = '';
		var options = [];
		var active = -1;

		var setActive = function ( i ) {
			if ( active > -1 && options[ active ] ) {
				options[ active ].classList.remove( 'is-active' );
				options[ active ].setAttribute( 'aria-selected', 'false' );
			}
			active = i;
			if ( active > -1 && options[ active ] ) {
				var opt = options[ active ];
				opt.classList.add( 'is-active' );
				opt.setAttribute( 'aria-selected', 'true' );
				input.setAttribute( 'aria-activedescendant', opt.id );
				opt.scrollIntoView( { block: 'nearest' } );
			} else {
				input.removeAttribute( 'aria-activedescendant' );
			}
		};

		var close = function () {
			box.hidden = true;
			input.setAttribute( 'aria-expanded', 'false' );
			input.removeAttribute( 'aria-activedescendant' );
			options = [];
			active = -1;
		};

		var render = function ( data ) {
			var html = '';

			if ( data.cats && data.cats.length ) {
				html += '<div class="kindi-suggest__group" role="presentation">קטגוריות</div>';
				data.cats.forEach( function ( c ) {
					html += '<a class="kindi-suggest__cat" role="option" href="' + c.url + '">' + c.name +
						' <span>(' + c.count + ')</span></a>';
				} );
			}

			if ( data.products && data.products.length ) {
				html += '<div class="kindi-suggest__group" role="presentation">מוצרים</div>';
				data.products.forEach( function ( p ) {
					html += '<a class="kindi-suggest__item" role="option" href="' + p.url + '">' +
						'<img src="' + p.img + '" alt="" loading="lazy" width="48" height="48">' +
						'<span class="kindi-suggest__t">' + p.title + '</span>' +
						'<span class="kindi-suggest__p">' + ( p.price || '' ) + '</span></a>';
				} );
				html += '<a class="kindi-suggest__all" role="option" href="' + data.all + '">' + cfg.allText + ' "' + lastQuery + '" ←</a>';
			} else if ( ! data.cats || ! data.cats.length ) {
				html = '<div class="kindi-suggest__empty">' + cfg.noResults + '</div>';
			}

			box.innerHTML = html;
			box.hidden = false;
			input.setAttribute( 'aria-expanded', 'true' );

			// Index the options and give each a stable id for aria-activedescendant.
			options = Array.prototype.slice.call( box.querySelectorAll( '[role="option"]' ) );
			options.forEach( function ( opt, i ) {
				opt.id = id + '-opt-' + i;
				opt.setAttribute( 'aria-selected', 'false' );
			} );
			active = -1;

			live.textContent = options.length
				? ( options.length + ' ' + ( cfg.resultsText || 'תוצאות' ) )
				: cfg.noResults;
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
				return;
			}
			if ( box.hidden || ! options.length ) {
				return;
			}
			if ( 'ArrowDown' === e.key ) {
				e.preventDefault();
				setActive( ( active + 1 ) % options.length );
			} else if ( 'ArrowUp' === e.key ) {
				e.preventDefault();
				setActive( active <= 0 ? options.length - 1 : active - 1 );
			} else if ( 'Enter' === e.key && active > -1 && options[ active ] ) {
				e.preventDefault();
				window.location.href = options[ active ].getAttribute( 'href' );
			}
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! form.contains( e.target ) ) {
				close();
			}
		} );
	} );
}() );
