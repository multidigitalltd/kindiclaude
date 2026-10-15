/*
 * Kindi dynamic blocks — editor registration. Renders a live server preview in
 * the editor (ServerSideRender) so the Site Editor recognises kindi/categories
 * and kindi/featured-products instead of showing an "unsupported block" error.
 */
( function ( blocks, element, serverSideRender ) {
	'use strict';
	if ( ! blocks || ! element || ! serverSideRender ) {
		return;
	}
	var el = element.createElement;
	var SSR = serverSideRender;

	var register = function ( name, title, icon ) {
		if ( blocks.getBlockType && blocks.getBlockType( name ) ) {
			return; // Already registered (e.g. from block.json).
		}
		blocks.registerBlockType( name, {
			apiVersion: 2,
			title: title,
			category: 'widgets',
			icon: icon,
			supports: { html: false, reusable: false },
			edit: function () {
				return el( SSR, { block: name } );
			},
			save: function () {
				return null; // Dynamic — rendered by PHP.
			},
		} );
	};

	register( 'kindi/categories', 'Kindi: קטגוריות', 'screenoptions' );
	register( 'kindi/featured-products', 'Kindi: מוצרים חמים', 'star-filled' );
}( window.wp.blocks, window.wp.element, window.wp.serverSideRender ) );
