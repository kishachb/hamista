/**
 * Hamista UI behaviours (spec §4.2). Vanilla ES2019, no dependencies.
 *
 * Progressive enhancement: the markup works without JavaScript, and every
 * behaviour is wired through event delegation on `document`.
 *
 *   [data-hm-copy="text"]            Copies the text, or the value/text of the element
 *                                    matched by [data-hm-copy-target] when the value is empty.
 *                                    Sets [data-hm-copied="true|false"] for 2s and announces the result.
 *   [data-hm-tabs]                   Tabs: .hm-tabs__tab buttons (aria-controls → panel id, or the
 *                                    nth .hm-tabs__panel). Arrow keys (direction-aware), Home, End.
 *   [data-hm-confirm="message"]      Asks before following a link, pressing a button or submitting a form.
 *   [data-hm-dropzone]               Drag-and-drop onto the input[type=file] inside; lists the chosen
 *                                    files in [data-hm-dropzone-files]; .is-dragover while dragging.
 *   form[data-hm-busy]               aria-busy + .is-loading on the submitter while submitting;
 *                                    blocks double submits.
 *   [data-hm-dismiss]                Removes the closest [data-hm-dismissible] / .hm-alert (or the
 *                                    element matched by a "#id" value); remembers it when the
 *                                    target has [data-hm-dismiss-key].
 *
 * Config (printed by PHP): window.hamistaUI = { i18n: { copied, copyFailed, confirm } }.
 * window.hamistaUI.init( root ) initialises tabs and dismissals in content added later.
 */
( function () {
	'use strict';

	const doc = document;
	const ui = ( window.hamistaUI = window.hamistaUI || {} );
	const i18n = ui.i18n || {};
	const STORE_PREFIX = 'hm-dismissed:';

	const closest = ( event, selector ) => ( event.target instanceof Element ? event.target.closest( selector ) : null );

	// Screen-reader announcements.
	const announce = ( message ) => {
		let region = doc.getElementById( 'hm-live' );
		if ( ! region ) {
			region = doc.createElement( 'div' );
			region.id = 'hm-live';
			region.className = 'hm-sr-only';
			region.setAttribute( 'aria-live', 'polite' );
			doc.body.appendChild( region );
		}
		region.textContent = '';
		setTimeout( () => {
			region.textContent = message || '';
		}, 60 );
	};

	// Copy to clipboard.
	const copyText = ( text ) => {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( ( resolve, reject ) => {
			const area = doc.createElement( 'textarea' );
			area.value = text;
			area.setAttribute( 'readonly', '' );
			area.style.cssText = 'position:fixed;inset-block-start:0;opacity:0';
			doc.body.appendChild( area );
			area.select();
			const ok = doc.execCommand( 'copy' );
			area.remove();
			return ok ? resolve() : reject( new Error( 'copy' ) );
		} );
	};

	doc.addEventListener( 'click', ( event ) => {
		const trigger = closest( event, '[data-hm-copy]' );
		if ( ! trigger ) {
			return;
		}
		event.preventDefault();
		let text = trigger.getAttribute( 'data-hm-copy' );
		if ( ! text ) {
			const source = doc.querySelector( trigger.getAttribute( 'data-hm-copy-target' ) || null );
			text = source ? ( 'value' in source ? source.value : source.textContent ) : '';
		}
		const done = ( ok ) => {
			trigger.setAttribute( 'data-hm-copied', ok ? 'true' : 'false' );
			announce( ok ? i18n.copied : i18n.copyFailed );
			clearTimeout( trigger.hmCopyTimer );
			trigger.hmCopyTimer = setTimeout( () => trigger.removeAttribute( 'data-hm-copied' ), 2000 );
		};
		copyText( String( text ).trim() ).then( () => done( true ), () => done( false ) );
	} );

	// Confirmation (capture phase, so it runs before other handlers).
	const confirmed = ( element ) => window.confirm( element.getAttribute( 'data-hm-confirm' ) || i18n.confirm || '' );

	doc.addEventListener(
		'click',
		( event ) => {
			const trigger = closest( event, '[data-hm-confirm]' );
			if ( trigger && 'FORM' !== trigger.tagName && ! confirmed( trigger ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		},
		true
	);

	doc.addEventListener(
		'submit',
		( event ) => {
			const form = event.target;
			if ( form.matches( 'form[data-hm-confirm]' ) && ! confirmed( form ) ) {
				event.preventDefault();
				event.stopImmediatePropagation();
			}
		},
		true
	);

	// Busy forms.
	const setBusy = ( form, busy, submitter ) => {
		if ( busy ) {
			form.setAttribute( 'aria-busy', 'true' );
		} else {
			form.removeAttribute( 'aria-busy' );
		}
		const button = submitter || form.querySelector( '[type="submit"]' );
		if ( button ) {
			button.classList.toggle( 'is-loading', busy );
		}
	};

	doc.addEventListener( 'submit', ( event ) => {
		const form = event.target;
		if ( ! form.matches( 'form[data-hm-busy]' ) || event.defaultPrevented ) {
			return;
		}
		if ( 'true' === form.getAttribute( 'aria-busy' ) ) {
			event.preventDefault();
			return;
		}
		setBusy( form, true, event.submitter );
		// Another handler may still cancel the submission.
		setTimeout( () => {
			if ( event.defaultPrevented ) {
				setBusy( form, false, event.submitter );
			}
		}, 0 );
	} );

	window.addEventListener( 'pageshow', ( event ) => {
		if ( event.persisted ) {
			doc.querySelectorAll( 'form[data-hm-busy][aria-busy]' ).forEach( ( form ) => setBusy( form, false ) );
		}
	} );

	// Tabs.
	const tabsOf = ( root ) =>
		Array.from( root.querySelectorAll( '.hm-tabs__tab, [role="tab"]' ) ).filter( ( tab ) => tab.closest( '[data-hm-tabs]' ) === root );

	const panelOf = ( root, tab ) => {
		const id = tab.getAttribute( 'aria-controls' );
		if ( id ) {
			return doc.getElementById( id );
		}
		const panels = Array.from( root.querySelectorAll( '.hm-tabs__panel' ) ).filter( ( panel ) => panel.closest( '[data-hm-tabs]' ) === root );
		return panels[ tabsOf( root ).indexOf( tab ) ] || null;
	};

	const selectTab = ( root, selected, focus ) => {
		tabsOf( root ).forEach( ( tab ) => {
			const on = tab === selected;
			const panel = panelOf( root, tab );
			tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
			tab.tabIndex = on ? 0 : -1;
			if ( panel ) {
				panel.hidden = ! on;
			}
		} );
		if ( focus ) {
			selected.focus();
		}
	};

	const initTabs = ( root ) => {
		const tabs = tabsOf( root );
		if ( ! tabs.length || root.hmTabsReady ) {
			return;
		}
		root.hmTabsReady = true;
		const list = root.querySelector( '.hm-tabs__list' );
		if ( list ) {
			list.setAttribute( 'role', 'tablist' );
		}
		tabs.forEach( ( tab ) => {
			tab.setAttribute( 'role', 'tab' );
			const panel = panelOf( root, tab );
			if ( panel ) {
				panel.setAttribute( 'role', 'tabpanel' );
				if ( tab.id ) {
					panel.setAttribute( 'aria-labelledby', tab.id );
				}
			}
		} );
		const hash = window.location.hash.slice( 1 );
		const initial =
			tabs.find( ( tab ) => 'true' === tab.getAttribute( 'aria-selected' ) ) ||
			tabs.find( ( tab ) => hash && tab.getAttribute( 'aria-controls' ) === hash ) ||
			tabs[ 0 ];
		selectTab( root, initial, false );
	};

	doc.addEventListener( 'click', ( event ) => {
		const tab = closest( event, '[data-hm-tabs] .hm-tabs__tab, [data-hm-tabs] [role="tab"]' );
		if ( tab ) {
			event.preventDefault();
			selectTab( tab.closest( '[data-hm-tabs]' ), tab, false );
		}
	} );

	doc.addEventListener( 'keydown', ( event ) => {
		const tab = closest( event, '[data-hm-tabs] [role="tab"]' );
		if ( ! tab ) {
			return;
		}
		const root = tab.closest( '[data-hm-tabs]' );
		const tabs = tabsOf( root );
		const index = tabs.indexOf( tab );
		const step = 'rtl' === window.getComputedStyle( root ).direction ? -1 : 1;
		const moves = {
			ArrowRight: index + step,
			ArrowLeft: index - step,
			Home: 0,
			End: tabs.length - 1,
		};
		if ( event.key in moves ) {
			event.preventDefault();
			selectTab( root, tabs[ ( moves[ event.key ] + tabs.length ) % tabs.length ], true );
		}
	} );

	// Dropzones.
	const dropzoneOf = ( event ) => closest( event, '[data-hm-dropzone]' );

	[ 'dragenter', 'dragover' ].forEach( ( type ) =>
		doc.addEventListener( type, ( event ) => {
			const zone = dropzoneOf( event );
			if ( zone ) {
				event.preventDefault();
				zone.classList.add( 'is-dragover' );
			}
		} )
	);

	doc.addEventListener( 'dragleave', ( event ) => {
		const zone = dropzoneOf( event );
		if ( zone && ! zone.contains( event.relatedTarget ) ) {
			zone.classList.remove( 'is-dragover' );
		}
	} );

	doc.addEventListener( 'drop', ( event ) => {
		const zone = dropzoneOf( event );
		if ( ! zone ) {
			return;
		}
		event.preventDefault();
		zone.classList.remove( 'is-dragover' );
		const input = zone.querySelector( 'input[type="file"]' );
		const files = event.dataTransfer ? Array.from( event.dataTransfer.files ) : [];
		if ( ! input || ! files.length || input.disabled ) {
			return;
		}
		const transfer = new DataTransfer();
		files.slice( 0, input.multiple ? files.length : 1 ).forEach( ( file ) => transfer.items.add( file ) );
		input.files = transfer.files;
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );

	doc.addEventListener( 'change', ( event ) => {
		const input = event.target;
		const zone = input.closest && input.closest( '[data-hm-dropzone]' );
		const list = zone && zone.querySelector( '[data-hm-dropzone-files]' );
		if ( list && input.files ) {
			const separator = 'rtl' === window.getComputedStyle( list ).direction ? '، ' : ', ';
			list.textContent = Array.from( input.files )
				.map( ( file ) => file.name )
				.join( separator );
		}
	} );

	// Dismissible elements.
	const remember = ( key ) => {
		try {
			window.localStorage.setItem( STORE_PREFIX + key, '1' );
		} catch ( error ) {
			// Storage can be unavailable (private mode); dismissal still works for this page.
		}
	};

	const dismissed = ( key ) => {
		try {
			return '1' === window.localStorage.getItem( STORE_PREFIX + key );
		} catch ( error ) {
			return false;
		}
	};

	doc.addEventListener( 'click', ( event ) => {
		const trigger = closest( event, '[data-hm-dismiss]' );
		if ( ! trigger ) {
			return;
		}
		const selector = trigger.getAttribute( 'data-hm-dismiss' );
		const target =
			selector && '#' === selector.charAt( 0 ) ? doc.querySelector( selector ) : trigger.closest( '[data-hm-dismissible], .hm-alert' );
		if ( ! target ) {
			return;
		}
		event.preventDefault();
		const key = target.getAttribute( 'data-hm-dismiss-key' );
		if ( key ) {
			remember( key );
		}
		target.remove();
	} );

	// Initialisation.
	const init = ( root ) => {
		const scope = root || doc;
		scope.querySelectorAll( '[data-hm-tabs]' ).forEach( initTabs );
		scope.querySelectorAll( '[data-hm-dismiss-key]' ).forEach( ( element ) => {
			if ( dismissed( element.getAttribute( 'data-hm-dismiss-key' ) ) ) {
				element.remove();
			}
		} );
	};

	ui.init = init;

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', () => init() );
	} else {
		init();
	}
} )();
