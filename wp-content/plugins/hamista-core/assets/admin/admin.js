/**
 * Hamista admin behaviours. Vanilla ES2019; loaded with `hamista-ui` on Hamista screens.
 *
 *   [data-hm-select]                 Selects the field's text on focus (keys, URLs, codes).
 *   [data-hm-toggle="#id"]           Shows/hides the target and keeps aria-expanded in sync.
 */
( function () {
	'use strict';

	const doc = document;

	doc.addEventListener( 'focusin', ( event ) => {
		const field = event.target;
		if ( field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement ) {
			if ( field.matches( '[data-hm-select]' ) ) {
				field.select();
			}
		}
	} );

	const sync = ( toggle ) => {
		const target = doc.querySelector( toggle.getAttribute( 'data-hm-toggle' ) || null );
		if ( target ) {
			toggle.setAttribute( 'aria-expanded', target.hidden ? 'false' : 'true' );
			if ( target.id ) {
				toggle.setAttribute( 'aria-controls', target.id );
			}
		}
		return target;
	};

	doc.addEventListener( 'click', ( event ) => {
		const toggle = event.target instanceof Element ? event.target.closest( '[data-hm-toggle]' ) : null;
		const target = toggle ? sync( toggle ) : null;
		if ( target ) {
			event.preventDefault();
			target.hidden = ! target.hidden;
			sync( toggle );
		}
	} );

	doc.querySelectorAll( '[data-hm-toggle]' ).forEach( sync );
} )();
