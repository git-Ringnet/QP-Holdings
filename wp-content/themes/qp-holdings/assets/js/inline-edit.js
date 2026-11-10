/**
 * QP Holdings — inline static-text editor (admin-only).
 *
 * Double-click a [data-qp-editable] element to edit its text in place.
 * Enter (or blur) saves; Esc cancels; Ctrl/Cmd+Enter saves multi-line.
 * Values are saved per current page language (vi/en/zh) via admin-ajax.
 *
 * Guests never receive this script; markup is gated server-side.
 */
( function () {
	'use strict';

	if ( typeof window.qpInlineEdit === 'undefined' ) {
		return;
	}

	var cfg = window.qpInlineEdit;
	var activeEl = null;
	var activeOrig = '';

	/* ---------------------------------------------------------------- utils */

	function isHtmlEl( el ) {
		return el.getAttribute( 'data-qp-html' ) === '1';
	}

	function isMultiline( el ) {
		return el.getAttribute( 'data-qp-multiline' ) === '1';
	}

	function readValue( el ) {
		if ( isHtmlEl( el ) ) {
			return el.innerHTML.trim();
		}
		// Plain text: normalize NBSP + collapse contentEditable line breaks.
		var text = el.innerText.replace( /\u00a0/g, ' ' );
		if ( ! isMultiline( el ) ) {
			text = text.replace( /\s*\n\s*/g, ' ' );
		}
		return text.replace( /[ \t]+/g, ' ' ).replace( /\n{3,}/g, '\n\n' ).trim();
	}

	function snapshot( el ) {
		return isHtmlEl( el ) ? el.innerHTML : el.innerText;
	}

	function restore( el, value ) {
		if ( isHtmlEl( el ) ) {
			el.innerHTML = value;
		} else {
			el.innerText = value;
		}
	}

	/* ---------------------------------------------------------------- toast */

	var toastTimer = null;
	function toast( message, kind ) {
		var node = document.getElementById( 'qp-ie-toast' );
		if ( ! node ) {
			node = document.createElement( 'div' );
			node.id = 'qp-ie-toast';
			node.className = 'qp-ie-toast';
			document.body.appendChild( node );
		}
		node.textContent = message;
		node.setAttribute( 'data-kind', kind || 'info' );
		node.classList.add( 'is-visible' );
		window.clearTimeout( toastTimer );
		toastTimer = window.setTimeout( function () {
			node.classList.remove( 'is-visible' );
		}, 2600 );
	}

	/* ---------------------------------------------------------------- badge */

	function ensureBadge() {
		if ( document.getElementById( 'qp-ie-badge' ) ) {
			return;
		}
		var badge = document.createElement( 'div' );
		badge.id = 'qp-ie-badge';
		badge.className = 'qp-ie-badge';
		var label = ( cfg.i18n && cfg.i18n.editing ? cfg.i18n.editing : 'Editing' );
		badge.innerHTML = '<span class="qp-ie-badge__dot"></span>' +
			'<span class="qp-ie-badge__label">' + escapeHtml( label ) + '</span>' +
			'<span class="qp-ie-badge__lang">' + escapeHtml( cfg.langLabel || cfg.lang || '' ) + '</span>';
		badge.title = ( cfg.i18n && cfg.i18n.hint ) ? cfg.i18n.hint : '';
		document.body.appendChild( badge );
	}

	function escapeHtml( str ) {
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return {
				'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
			}[ c ];
		} );
	}

	/* --------------------------------------------------------------- saving */

	function save( el ) {
		var value = readValue( el );
		var orig = activeOrig;
		// Nothing changed → skip the round-trip.
		var changed = isHtmlEl( el ) ? ( el.innerHTML.trim() !== orig.trim() )
			: ( value !== orig.replace( /\u00a0/g, ' ' ).trim() );
		if ( ! changed ) {
			return;
		}

		var body = new URLSearchParams();
		body.set( 'nonce', cfg.nonce );
		body.set( 'lang', cfg.lang );
		body.set( 'value', value );
		if ( el.getAttribute( 'data-qp-post-field' ) === 'excerpt' ) {
			body.set( 'action', 'qp_save_news_lede' );
			body.set( 'post_id', el.getAttribute( 'data-qp-post-id' ) || '' );
		} else {
			body.set( 'action', cfg.action );
			body.set( 'key', el.getAttribute( 'data-qp-key' ) );
		}
		if ( isHtmlEl( el ) ) {
			body.set( 'html', '1' );
		}
		if ( isMultiline( el ) ) {
			body.set( 'multiline', '1' );
		}

		el.classList.add( 'qp-ie--saving' );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( res ) {
			if ( res.status === 403 ) {
				throw { code: 'expired' };
			}
			return res.json();
		} ).then( function ( json ) {
			el.classList.remove( 'qp-ie--saving' );
			if ( ! json || ! json.success ) {
				throw { code: 'error' };
			}
			// Reflect the server's canonical value (sanitized / reset-to-default).
			restore( el, json.data.value );
			if ( json.data.isOverride ) {
				el.setAttribute( 'data-qp-override', '1' );
			} else {
				el.removeAttribute( 'data-qp-override' );
			}
			el.removeAttribute( 'data-qp-fallback' );
			toast( cfg.i18n.saved, 'success' );
		} ).catch( function ( err ) {
			el.classList.remove( 'qp-ie--saving' );
			restore( el, orig );
			if ( err && err.code === 'expired' ) {
				toast( cfg.i18n.expired, 'error' );
			} else {
				toast( cfg.i18n.saveError, 'error' );
			}
		} );
	}

	/* -------------------------------------------------------------- editing */

	function beginEdit( el ) {
		if ( activeEl === el ) {
			return;
		}
		if ( activeEl ) {
			endEdit( activeEl, true );
		}
		activeEl = el;
		activeOrig = snapshot( el );
		el.setAttribute( 'contenteditable', isHtmlEl( el ) ? 'true' : 'plaintext-only' );
		el.classList.add( 'qp-ie--editing' );
		el.focus();
		selectAll( el );
	}

	function endEdit( el, doSave ) {
		if ( ! el ) {
			return;
		}
		if ( doSave ) {
			save( el );
		}
		el.removeAttribute( 'contenteditable' );
		el.classList.remove( 'qp-ie--editing' );
		if ( activeEl === el ) {
			activeEl = null;
		}
	}

	function selectAll( el ) {
		try {
			var range = document.createRange();
			range.selectNodeContents( el );
			var sel = window.getSelection();
			sel.removeAllRanges();
			sel.addRange( range );
		} catch ( e ) { /* noop */ }
	}

	/* ---------------------------------------------------------------- wires */

	document.addEventListener( 'dblclick', function ( e ) {
		var el = e.target.closest ? e.target.closest( '[data-qp-editable]' ) : null;
		if ( ! el ) {
			return;
		}
		e.preventDefault();
		beginEdit( el );
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( ! activeEl ) {
			return;
		}
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			restore( activeEl, activeOrig );
			endEdit( activeEl, false );
			return;
		}
		if ( e.key === 'Enter' ) {
			var ml = isMultiline( activeEl );
			// Multi-line: Enter inserts newline, Ctrl/Cmd+Enter saves.
			if ( ! ml || e.ctrlKey || e.metaKey ) {
				e.preventDefault();
				endEdit( activeEl, true );
			}
		}
	} );

	document.addEventListener( 'blur', function ( e ) {
		var el = e.target;
		if ( el && el.getAttribute && el.getAttribute( 'data-qp-editable' ) && el === activeEl ) {
			endEdit( el, true );
		}
	}, true );

	// Strip formatting on paste so editors can't inject styled markup.
	document.addEventListener( 'paste', function ( e ) {
		if ( ! activeEl ) {
			return;
		}
		e.preventDefault();
		var text = ( e.clipboardData || window.clipboardData ).getData( 'text/plain' );
		if ( document.queryCommandSupported && document.queryCommandSupported( 'insertText' ) ) {
			document.execCommand( 'insertText', false, text );
		} else {
			var sel = window.getSelection();
			if ( sel && sel.rangeCount ) {
				sel.getRangeAt( 0 ).insertNode( document.createTextNode( text ) );
				sel.collapseToEnd();
			}
		}
	} );

	/* ----------------------------------------------------------------- init */

	function init() {
		ensureBadge();
	}
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
