/**
 * Hamista Settings — the unified "Hamista Settings" panel.
 *
 * Vanilla JS. jQuery is not used; `wp.media` (image field) is the only
 * wp-admin dependency. Depends on `hamista-admin` / `hamista-ui` for
 * `[data-hm-confirm]` (reset tab) and `[data-hm-dismiss]` (notices).
 */
( function () {
	'use strict';

	var cfg = window.hamistaSettings || {};
	var i18n = cfg.i18n || {};

	function ready( fn ) {
		if ( 'loading' !== document.readyState ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function reinitUi( el ) {
		if ( window.hamistaUI && 'function' === typeof window.hamistaUI.init ) {
			window.hamistaUI.init( el );
		}
	}

	/* ------------------------------------------------------------- search */

	/**
	 * One search box filters the nav's tab links (by title) and, on the
	 * current tab, the visible field rows (by their text) — client side.
	 */
	function initSearch( root ) {
		var input = root.querySelector( '[data-hm-tab-search]' );
		if ( ! input ) {
			return;
		}
		var links  = root.querySelectorAll( '.hm-settings__nav-link' );
		var groups = root.querySelectorAll( '.hm-settings__nav-group' );
		var fields = root.querySelectorAll( '.hm-settings__content .hm-field' );

		input.addEventListener( 'input', function () {
			var q = input.value.trim().toLowerCase();

			links.forEach( function ( a ) {
				var label = a.getAttribute( 'data-hm-tab-label' ) || '';
				a.hidden  = '' !== q && -1 === label.indexOf( q );
			} );
			groups.forEach( function ( group ) {
				group.hidden = 0 === group.querySelectorAll( '.hm-settings__nav-link:not([hidden])' ).length;
			} );

			var any = 0 === fields.length;
			fields.forEach( function ( field ) {
				var match = '' === q || -1 !== ( field.textContent || '' ).toLowerCase().indexOf( q );
				field.hidden = ! match;
				any = any || match;
			} );
			toggleNoResults( root, '' !== q && fields.length > 0 && ! any );
		} );
	}

	function toggleNoResults( root, show ) {
		var el = root.querySelector( '[data-hm-no-results]' );
		if ( show && ! el ) {
			var content = root.querySelector( '.hm-settings__content' );
			if ( ! content ) {
				return;
			}
			el = document.createElement( 'p' );
			el.className = 'hm-empty__text';
			el.setAttribute( 'data-hm-no-results', '' );
			el.textContent = i18n.noResults || 'No settings match your search.';
			content.insertBefore( el, content.firstChild );
		} else if ( el ) {
			el.hidden = ! show;
		}
	}

	/* ------------------------------------------------------------ show_if */

	function fieldValue( form, id ) {
		var name = 'hamista_field[' + id + ']';
		var els  = Array.prototype.slice.call( form.querySelectorAll( '[name="' + name + '"]' ) );
		if ( 0 === els.length ) {
			return undefined;
		}
		var checkable = els.filter( function ( el ) { return 'checkbox' === el.type || 'radio' === el.type; } );
		if ( checkable.length ) {
			var checked = checkable.filter( function ( el ) { return el.checked; } );
			if ( checked.length ) {
				return 'checkbox' === checked[ 0 ].type ? true : checked[ 0 ].value;
			}
			var hidden = els.filter( function ( el ) { return 'hidden' === el.type; } )[ 0 ];
			return hidden ? ( '1' === hidden.value ) : false;
		}
		return els[ 0 ].value;
	}

	function valueMatches( value, expected ) {
		if ( 'boolean' === typeof expected ) {
			return Boolean( value ) === expected;
		}
		return String( value ) === String( expected );
	}

	function ruleMatches( value, rule ) {
		if ( Object.prototype.hasOwnProperty.call( rule, 'value' ) ) {
			return valueMatches( value, rule.value );
		}
		if ( Object.prototype.hasOwnProperty.call( rule, 'not' ) ) {
			return ! valueMatches( value, rule.not );
		}
		return true;
	}

	function initShowIf( root ) {
		root.querySelectorAll( 'form.hm-settings-form' ).forEach( function ( form ) {
			var watchers = form.querySelectorAll( '[data-hm-show-if]' );
			if ( 0 === watchers.length ) {
				return;
			}
			function refresh() {
				watchers.forEach( function ( el ) {
					var rule;
					try {
						rule = JSON.parse( el.getAttribute( 'data-hm-show-if' ) );
					} catch ( e ) {
						return;
					}
					el.hidden = ! ruleMatches( fieldValue( form, rule.field ), rule );
				} );
			}
			form.addEventListener( 'input', refresh );
			form.addEventListener( 'change', refresh );
			refresh();
		} );
	}

	/* ----------------------------------------------------------- repeater */

	function renumberRows( wrap ) {
		Array.prototype.forEach.call( wrap.children, function ( row, index ) {
			row.querySelectorAll( '[name]' ).forEach( function ( el ) {
				el.name = el.name.replace( /\[(?:\d+|__INDEX__)\](\[[^\[\]]+\])$/, '[' + index + ']$1' );
			} );
			row.querySelectorAll( '[id]' ).forEach( function ( el ) {
				el.id = el.id.replace( /(?:\d+|__INDEX__)(?=-[^-]*$)/, String( index ) );
			} );
		} );
	}

	function repeaterRowTitle( row ) {
		var titleEl = row.querySelector( '.hm-repeater__row-title' );
		var input   = row.querySelector( '.hm-repeater__row-body .hm-field:first-child input, .hm-repeater__row-body .hm-field:first-child textarea, .hm-repeater__row-body .hm-field:first-child select' );
		if ( titleEl && input ) {
			titleEl.textContent = input.value || '';
		}
	}

	function initRepeaters( root ) {
		root.querySelectorAll( '[data-hm-repeater]' ).forEach( function ( rep ) {
			var rowsWrap = rep.querySelector( '.hm-repeater__rows' );
			var template = rep.querySelector( '.hm-repeater__template' );
			var addBtn   = rep.querySelector( '.hm-repeater__add' );
			var max      = parseInt( rep.getAttribute( 'data-hm-repeater-max' ) || '0', 10 );

			function limit() {
				if ( addBtn ) {
					addBtn.hidden = max > 0 && rowsWrap.children.length >= max;
				}
			}

			rowsWrap.addEventListener( 'input', function ( e ) {
				var row = e.target.closest( '.hm-repeater__row' );
				if ( row ) {
					repeaterRowTitle( row );
				}
			} );

			rowsWrap.addEventListener( 'click', function ( e ) {
				var row = e.target.closest( '.hm-repeater__row' );
				if ( ! row ) {
					return;
				}
				if ( e.target.closest( '.hm-repeater__remove' ) ) {
					row.remove();
					renumberRows( rowsWrap );
					limit();
				} else if ( e.target.closest( '[data-hm-move="up"]' ) ) {
					var prev = row.previousElementSibling;
					if ( prev ) {
						rowsWrap.insertBefore( row, prev );
						renumberRows( rowsWrap );
					}
				} else if ( e.target.closest( '[data-hm-move="down"]' ) ) {
					var next = row.nextElementSibling;
					if ( next ) {
						rowsWrap.insertBefore( next, row );
						renumberRows( rowsWrap );
					}
				} else if ( e.target.closest( '[data-hm-toggle-row]' ) ) {
					row.classList.toggle( 'is-collapsed' );
				}
			} );

			if ( addBtn ) {
				addBtn.addEventListener( 'click', function () {
					if ( max > 0 && rowsWrap.children.length >= max ) {
						return;
					}
					var source = template.content ? template.content.firstElementChild : template.firstElementChild;
					if ( ! source ) {
						return;
					}
					var clone = source.cloneNode( true );
					rowsWrap.appendChild( clone );
					renumberRows( rowsWrap );
					reinitUi( clone );
					limit();
				} );
			}

			limit();
		} );
	}

	/* ----------------------------------------------------------- sortable */

	function initSortables( root ) {
		root.querySelectorAll( '[data-hm-sortable]' ).forEach( function ( list ) {
			var dragging = null;

			function rows() {
				return Array.prototype.slice.call( list.children );
			}

			list.addEventListener( 'click', function ( e ) {
				var row = e.target.closest( '.hm-sortable__row' );
				if ( ! row ) {
					return;
				}
				if ( e.target.closest( '[data-hm-move="up"]' ) ) {
					var prev = row.previousElementSibling;
					if ( prev ) {
						list.insertBefore( row, prev );
						renumberRows( list );
					}
				} else if ( e.target.closest( '[data-hm-move="down"]' ) ) {
					var next = row.nextElementSibling;
					if ( next ) {
						list.insertBefore( next, row );
						renumberRows( list );
					}
				}
			} );

			rows().forEach( function ( row ) {
				row.setAttribute( 'draggable', 'true' );
			} );

			list.addEventListener( 'dragstart', function ( e ) {
				var row = e.target.closest( '.hm-sortable__row' );
				if ( ! row ) {
					return;
				}
				dragging = row;
				row.classList.add( 'is-dragging' );
				if ( e.dataTransfer ) {
					e.dataTransfer.effectAllowed = 'move';
				}
			} );
			list.addEventListener( 'dragend', function () {
				if ( dragging ) {
					dragging.classList.remove( 'is-dragging' );
				}
				dragging = null;
				renumberRows( list );
			} );
			list.addEventListener( 'dragover', function ( e ) {
				if ( ! dragging ) {
					return;
				}
				e.preventDefault();
				var row = e.target.closest( '.hm-sortable__row' );
				if ( ! row || row === dragging ) {
					return;
				}
				var rect   = row.getBoundingClientRect();
				var before = ( e.clientY - rect.top ) < rect.height / 2;
				list.insertBefore( dragging, before ? row : row.nextElementSibling );
			} );

			renumberRows( list );
		} );
	}

	/* -------------------------------------------------------------- media */

	function initMedia( root ) {
		root.querySelectorAll( '[data-hm-media]' ).forEach( function ( field ) {
			var valueInput = field.querySelector( '.hm-media-field__value' );
			var preview    = field.querySelector( '.hm-media-field__preview' );
			var selectBtn  = field.querySelector( '.hm-media-field__select' );
			var removeBtn  = field.querySelector( '.hm-media-field__remove' );
			var frame;

			if ( selectBtn ) {
				selectBtn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					if ( ! window.wp || ! window.wp.media ) {
						return;
					}
					if ( ! frame ) {
						frame = window.wp.media( {
							title: i18n.selectImage || 'Select image',
							multiple: false,
							library: { type: 'image' },
							button: { text: i18n.useImage || 'Use this image' },
						} );
						frame.on( 'select', function () {
							var attachment = frame.state().get( 'selection' ).first().toJSON();
							valueInput.value = attachment.id;
							preview.innerHTML = '';
							var img = document.createElement( 'img' );
							img.src = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;
							img.alt = '';
							preview.appendChild( img );
							if ( removeBtn ) {
								removeBtn.hidden = false;
							}
							valueInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
						} );
					}
					frame.open();
				} );
			}
			if ( removeBtn ) {
				removeBtn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					valueInput.value = '0';
					preview.innerHTML = '';
					removeBtn.hidden = true;
					valueInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				} );
			}
		} );
	}

	/* -------------------------------------------------------------- color */

	function initColor( root ) {
		root.querySelectorAll( '[data-hm-color]' ).forEach( function ( field ) {
			var native = field.querySelector( '.hm-color-field__native' );
			var text   = field.querySelector( '.hm-color-field__text' );
			if ( ! native || ! text ) {
				return;
			}
			native.addEventListener( 'input', function () {
				text.value = native.value;
				text.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
			text.addEventListener( 'input', function () {
				var v = text.value.trim();
				if ( /^#[0-9a-fA-F]{6}/.test( v ) ) {
					native.value = v.slice( 0, 7 );
				}
			} );
		} );
	}

	/* --------------------------------------------------------------- code */

	function initCode( root ) {
		root.querySelectorAll( '[data-hm-code]' ).forEach( function ( el ) {
			el.addEventListener( 'keydown', function ( e ) {
				if ( 'Tab' !== e.key || el.hasAttribute( 'readonly' ) ) {
					return;
				}
				e.preventDefault();
				var start = el.selectionStart;
				var end   = el.selectionEnd;
				el.value  = el.value.slice( 0, start ) + '\t' + el.value.slice( end );
				el.selectionStart = el.selectionEnd = start + 1;
				el.dispatchEvent( new Event( 'input', { bubbles: true } ) );
			} );
		} );
	}

	/* -------------------------------------------------------------- dirty */

	function initDirty( root ) {
		root.querySelectorAll( '[data-hm-track-dirty]' ).forEach( function ( form ) {
			var dirty     = false;
			var indicator = form.querySelector( '[data-hm-dirty-indicator]' );

			form.addEventListener( 'input', function () {
				dirty = true;
				if ( indicator ) {
					indicator.hidden = false;
				}
			} );
			form.addEventListener( 'change', function () {
				dirty = true;
				if ( indicator ) {
					indicator.hidden = false;
				}
			} );
			form.addEventListener( 'submit', function () {
				dirty = false;
			} );
			window.addEventListener( 'beforeunload', function ( e ) {
				if ( ! dirty ) {
					return;
				}
				e.preventDefault();
				e.returnValue = i18n.unsavedWarning || '';
			} );
		} );
	}

	/* --------------------------------------------------------------- init */

	ready( function () {
		var root = document.querySelector( '[data-hm-settings]' );
		if ( ! root ) {
			return;
		}
		initSearch( root );
		initShowIf( root );
		initRepeaters( root );
		initSortables( root );
		initMedia( root );
		initColor( root );
		initCode( root );
		initDirty( root );
	} );
}() );
