/**
 * Hamista theme — main.js (vanilla ES2019, loaded with defer; minified by tools/build-assets.mjs).
 *
 * - Colour mode: cycles light → dark → system, persists the choice when allowed, keeps
 *   data-theme / data-theme-mode, the toggle's label and the theme-color metas in sync.
 * - Header: .is-scrolled from an IntersectionObserver sentinel (no scroll listeners).
 * - Overlays (mobile drawer, search): focus trap, Esc, inert + aria-hidden on the rest of the
 *   page, scroll lock, focus return.
 * - Dropdown navigation: aria-expanded toggles, Esc, closes when focus or a click leaves.
 * - .hm-reveal on intersection, back to top, dismissible top bar.
 *
 * Runtime config and strings come from window.hamistaTheme (printed by the theme before this file).
 */
( function () {
	'use strict';

	var doc = document;
	var root = doc.documentElement;
	var config = window.hamistaTheme || {};
	var i18n = config.i18n || {};
	var colorMode = config.colorMode || {};
	var MODES = [ 'light', 'dark', 'system' ];

	function all( selector, context ) {
		return Array.prototype.slice.call( ( context || doc ).querySelectorAll( selector ) );
	}

	function media( query ) {
		return window.matchMedia( query );
	}

	function onChange( query, callback ) {
		if ( query.addEventListener ) {
			query.addEventListener( 'change', callback );
		} else {
			query.addListener( callback );
		}
	}

	function store( key, value ) {
		try {
			localStorage.setItem( key, value );
		} catch ( e ) {}
	}

	var darkScheme = media( '(prefers-color-scheme: dark)' );
	var reducedMotion = media( '(prefers-reduced-motion: reduce)' );
	var liveRegion;

	/** Speaks a short status message through a polite live region. */
	function announce( message ) {
		if ( ! liveRegion ) {
			liveRegion = doc.createElement( 'div' );
			liveRegion.className = 'hm-sr-only';
			liveRegion.setAttribute( 'aria-live', 'polite' );
			doc.body.appendChild( liveRegion );
		}
		liveRegion.textContent = '';
		setTimeout( function () {
			liveRegion.textContent = message;
		}, 60 );
	}

	/* Colour mode ------------------------------------------------------------------------ */

	function currentMode() {
		var mode = root.getAttribute( 'data-theme-mode' );
		return MODES.indexOf( mode ) > -1 ? mode : 'light';
	}

	function nextMode( mode ) {
		return MODES[ ( MODES.indexOf( mode ) + 1 ) % MODES.length ];
	}

	function modeName( mode ) {
		return ( i18n.modes && i18n.modes[ mode ] ) || mode;
	}

	function syncColorMode() {
		var mode = currentMode();
		var label = ( i18n.toggle || 'Color mode: %1$s. Switch to %2$s.' )
			.replace( '%1$s', modeName( mode ) )
			.replace( '%2$s', modeName( nextMode( mode ) ) );
		all( '[data-hm-color-toggle]' ).forEach( function ( button ) {
			button.setAttribute( 'aria-label', label );
			button.setAttribute( 'title', label );
		} );

		// An explicit choice overrides the OS-scheme metas; "system" restores them.
		var background = getComputedStyle( root ).getPropertyValue( '--hm-color-bg' ).trim();
		all( 'meta[name="theme-color"]' ).forEach( function ( meta ) {
			if ( ! meta.hasAttribute( 'data-hm-content' ) ) {
				meta.setAttribute( 'data-hm-content', meta.getAttribute( 'content' ) );
			}
			meta.setAttribute( 'content', 'system' !== mode && background ? background : meta.getAttribute( 'data-hm-content' ) );
		} );
	}

	function applyMode( mode, persist ) {
		root.setAttribute( 'data-theme-mode', mode );
		root.setAttribute( 'data-theme', 'system' === mode ? ( darkScheme.matches ? 'dark' : 'light' ) : mode );
		if ( persist && colorMode.remember ) {
			store( 'hamista-color-mode', mode );
		}
		syncColorMode();
	}

	onChange( darkScheme, function () {
		if ( 'system' === currentMode() ) {
			applyMode( 'system', false );
		}
	} );

	/* Overlays: drawer + search ---------------------------------------------------------- */

	var openOverlay = null;
	var returnFocus = null;
	var hidden = [];

	function focusables( container ) {
		return all( 'a[href],button:not([disabled]),input:not([disabled]):not([type="hidden"]),select:not([disabled]),textarea:not([disabled]),[tabindex]:not([tabindex="-1"])', container ).filter( function ( element ) {
			return element.offsetWidth || element.offsetHeight || element.getClientRects().length;
		} );
	}

	function setExpanded( id, expanded ) {
		all( '[aria-controls="' + id + '"]' ).forEach( function ( control ) {
			control.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
		} );
	}

	/** Makes everything outside the overlay inert (and aria-hidden for older browsers). */
	function isolate( overlay ) {
		Array.prototype.forEach.call( doc.body.children, function ( element ) {
			if ( element === overlay || element.hasAttribute( 'inert' ) || 'true' === element.getAttribute( 'aria-hidden' ) || /^(SCRIPT|STYLE|LINK|TEMPLATE)$/.test( element.tagName ) ) {
				return;
			}
			element.setAttribute( 'inert', '' );
			element.setAttribute( 'aria-hidden', 'true' );
			hidden.push( element );
		} );
	}

	function release() {
		hidden.forEach( function ( element ) {
			element.removeAttribute( 'inert' );
			element.removeAttribute( 'aria-hidden' );
		} );
		hidden = [];
	}

	function openPanel( overlay, trigger ) {
		if ( openOverlay ) {
			closePanel( false );
		}
		openOverlay = overlay;
		returnFocus = trigger;
		overlay.classList.add( 'is-open' );
		setExpanded( overlay.id, true );
		isolate( overlay );
		root.classList.add( 'hm-lock' );
		var target = overlay.querySelector( '[data-hm-autofocus]' ) || focusables( overlay )[ 0 ];
		if ( target ) {
			target.focus();
		}
	}

	function closePanel( restore ) {
		var overlay = openOverlay;
		if ( ! overlay ) {
			return;
		}
		openOverlay = null;
		overlay.classList.remove( 'is-open' );
		setExpanded( overlay.id, false );
		release();
		root.classList.remove( 'hm-lock' );
		if ( false !== restore && returnFocus ) {
			returnFocus.focus();
		}
	}

	function trapFocus( event ) {
		var items = focusables( openOverlay );
		var first = items[ 0 ];
		var last = items[ items.length - 1 ];
		var active = doc.activeElement;
		if ( ! first ) {
			event.preventDefault();
		} else if ( event.shiftKey && ( active === first || ! openOverlay.contains( active ) ) ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && ( active === last || ! openOverlay.contains( active ) ) ) {
			event.preventDefault();
			first.focus();
		}
	}

	// The drawer is a mobile control: close it when the layout switches to desktop.
	onChange( media( '(min-width: 64em)' ), function ( query ) {
		if ( query.matches && openOverlay && openOverlay.classList.contains( 'hm-drawer' ) ) {
			closePanel( false );
		}
	} );

	/* Dropdown navigation ---------------------------------------------------------------- */

	function setSubmenu( toggle, open ) {
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}

	/** Closes open header submenus that do not contain the given node. */
	function closeSubmenus( keep ) {
		all( '.hm-header .hm-nav__toggle[aria-expanded="true"]' ).forEach( function ( toggle ) {
			if ( ! keep || ! toggle.parentNode.contains( keep ) ) {
				setSubmenu( toggle, false );
			}
		} );
	}

	doc.addEventListener( 'focusin', function ( event ) {
		closeSubmenus( event.target );
	} );

	/* Events ----------------------------------------------------------------------------- */

	doc.addEventListener( 'click', function ( event ) {
		var target = event.target;
		var control;

		if ( ( control = target.closest( '[data-hm-color-toggle]' ) ) ) {
			applyMode( nextMode( currentMode() ), true );
			announce( ( i18n.changed || 'Color mode: %s' ).replace( '%s', modeName( currentMode() ) ) );
			return;
		}

		if ( ( control = target.closest( '[data-hm-open]' ) ) ) {
			var overlay = doc.getElementById( control.getAttribute( 'data-hm-open' ) );
			if ( overlay ) {
				event.preventDefault();
				openPanel( overlay, control );
			}
			return;
		}

		if ( openOverlay && ( target.closest( '[data-hm-close]' ) || target.closest( 'a[href^="#"]' ) ) ) {
			closePanel( ! target.closest( 'a' ) );
			return;
		}

		if ( ( control = target.closest( '.hm-nav__toggle' ) ) ) {
			var open = 'true' !== control.getAttribute( 'aria-expanded' );
			if ( open && control.closest( '.hm-header' ) ) {
				closeSubmenus( control.parentNode );
			}
			setSubmenu( control, open );
			return;
		}

		if ( ( control = target.closest( '[data-hm-topbar-close]' ) ) ) {
			var bar = control.closest( '[data-hm-topbar]' );
			store( 'hamista-topbar', bar ? bar.getAttribute( 'data-hm-topbar' ) : '1' );
			root.setAttribute( 'data-topbar', 'off' );
			var logo = doc.querySelector( '.hm-header .hm-logo' );
			if ( logo ) {
				logo.focus();
			}
			return;
		}

		closeSubmenus( target );
	} );

	doc.addEventListener( 'keydown', function ( event ) {
		if ( openOverlay ) {
			if ( 'Escape' === event.key ) {
				event.preventDefault();
				closePanel();
			} else if ( 'Tab' === event.key ) {
				trapFocus( event );
			}
			return;
		}
		if ( 'Escape' === event.key && event.target.closest ) {
			// Close the nearest open submenu around the focus and return focus to its toggle.
			var item = event.target.closest( '.hm-header .hm-nav li' );
			while ( item ) {
				var toggle = item.querySelector( ':scope > .hm-nav__toggle[aria-expanded="true"]' );
				if ( toggle ) {
					setSubmenu( toggle, false );
					toggle.focus();
					return;
				}
				item = item.parentNode.closest( 'li' );
			}
		}
	} );

	/* Scroll-driven state, without scroll listeners -------------------------------------- */

	var sentinel = doc.querySelector( '[data-hm-sentinel]' );
	var header = doc.querySelector( '[data-hm-header]' );
	var toTop = doc.querySelector( '[data-hm-to-top]' );
	var observe = 'IntersectionObserver' in window;

	if ( sentinel && observe ) {
		var adminBar = doc.getElementById( 'wpadminbar' );
		if ( header ) {
			new IntersectionObserver( function ( entries ) {
				header.classList.toggle( 'is-scrolled', ! entries[ 0 ].isIntersecting );
			}, { rootMargin: '-' + ( adminBar ? adminBar.offsetHeight : 0 ) + 'px 0px 0px' } ).observe( sentinel );
		}
		if ( toTop ) {
			// The root grows one viewport upwards: the button shows after a full screen of scrolling.
			new IntersectionObserver( function ( entries ) {
				toTop.classList.toggle( 'is-visible', ! entries[ 0 ].isIntersecting );
			}, { rootMargin: '100% 0px 0px' } ).observe( sentinel );
		}
	}

	if ( toTop ) {
		toTop.addEventListener( 'click', function () {
			window.scrollTo( { top: 0, behavior: reducedMotion.matches ? 'auto' : 'smooth' } );
			var logo = doc.querySelector( '.hm-header .hm-logo' );
			if ( logo ) {
				logo.focus( { preventScroll: true } );
			}
		} );
	}

	var reveals = all( '.hm-reveal' );
	if ( reveals.length ) {
		if ( observe && ! reducedMotion.matches && 'off' !== root.getAttribute( 'data-animations' ) ) {
			var revealer = new IntersectionObserver( function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( entry.isIntersecting ) {
						entry.target.classList.add( 'is-visible' );
						revealer.unobserve( entry.target );
					}
				} );
			}, { rootMargin: '0px 0px -8%', threshold: 0.1 } );
			reveals.forEach( function ( element ) {
				revealer.observe( element );
			} );
		} else {
			reveals.forEach( function ( element ) {
				element.classList.add( 'is-visible' );
			} );
		}
	}

	syncColorMode();
}() );
