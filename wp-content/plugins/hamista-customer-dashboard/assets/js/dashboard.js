/**
 * Hamista Customer Dashboard — My Account shell behaviours.
 * Vanilla ES2019, no dependencies besides `hamista-ui` (registered as a dependency).
 */
( function () {
	'use strict';

	/**
	 * Scrolls the current pill into view on the mobile horizontal nav.
	 *
	 * @param {Element} nav The `[data-hm-account-nav]` element.
	 */
	function scrollActivePillIntoView( nav ) {
		var current = nav.querySelector( '.hm-account-nav__item.is-current' );
		if ( current && typeof current.scrollIntoView === 'function' ) {
			current.scrollIntoView( { behavior: 'auto', inline: 'center', block: 'nearest' } );
		}
	}

	function init( root ) {
		var scope = root || document;
		var navs = scope.querySelectorAll( '[data-hm-account-nav]' );
		for ( var i = 0; i < navs.length; i++ ) {
			scrollActivePillIntoView( navs[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}

	// Content added later (e.g. by another script) can re-run this.
	window.hamistaDashboard = window.hamistaDashboard || {};
	window.hamistaDashboard.init = init;
}() );
