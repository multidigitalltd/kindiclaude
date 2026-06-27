/*
 * Kindi sticky mobile add-to-cart — Vanilla JS, no dependencies.
 * Reveals the bottom bar once the real buy button leaves the viewport, then
 * delegates the actual add-to-cart to the product's own form so quantity and
 * variation selections are respected exactly.
 */
( function () {
	'use strict';

	var bar = document.querySelector( '[data-kindi-stickycart]' );
	var form = document.querySelector( 'form.cart' );
	var realBtn = form ? form.querySelector( '.single_add_to_cart_button' ) : null;
	if ( ! bar || ! form || ! realBtn ) {
		return;
	}

	var addBtn = bar.querySelector( '[data-kindi-stickycart-add]' );

	// Reveal the bar only while the real button is scrolled out of view.
	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver(
			function ( entries ) {
				bar.hidden = entries[ 0 ].isIntersecting;
			},
			{ rootMargin: '0px 0px -40px 0px' }
		);
		observer.observe( realBtn );
	} else {
		bar.hidden = false;
	}

	// Drive the real form: respect required variation choices, then submit.
	addBtn.addEventListener( 'click', function () {
		if ( realBtn.classList.contains( 'disabled' ) || realBtn.disabled ) {
			form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			return;
		}
		if ( typeof form.requestSubmit === 'function' ) {
			form.requestSubmit( realBtn );
		} else {
			realBtn.click();
		}
	} );

	// Mirror live variation price into the sticky bar when WooCommerce updates it.
	var priceEl = bar.querySelector( '[data-kindi-stickycart-price]' );
	form.addEventListener( 'change', function () {
		var live = document.querySelector( '.woocommerce-variation-price .price, .single-product div.product p.price' );
		if ( live && priceEl ) {
			priceEl.innerHTML = live.innerHTML;
		}
	} );
}() );
