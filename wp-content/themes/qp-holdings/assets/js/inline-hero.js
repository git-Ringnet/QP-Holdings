/**
 * QP Holdings — inline hero-image editor (admin-only).
 *
 * Hover a hero banner ([data-qp-hero-key]) to reveal a floating toolbar with an
 * upload icon; click it to pick an image from the WordPress Media Library. The
 * banner swaps live and the choice is persisted per hero key via admin-ajax. A
 * reset control restores the hard-coded default.
 *
 * The toolbar is a single element appended to <body> and positioned over the
 * hovered hero, so it escapes the heroes' own stacking contexts (the hero
 * backgrounds use negative/zero z-index). Guests never receive this script;
 * the markup hooks are gated server-side.
 */
( function () {
	'use strict';

	if ( typeof window.qpInlineHero === 'undefined' || typeof window.wp === 'undefined' || ! window.wp.media ) {
		return;
	}

	var cfg = window.qpInlineHero;
	var frame = null;
	var activeSlot = null;   // slot the media frame is acting on
	var hoverSlot = null;    // slot the toolbar is currently anchored to
	var toolbar = null;
	var hideTimer = null;

	/* ---------------------------------------------------------------- toast */

	var toastTimer = null;
	function toast( message, kind ) {
		var node = document.getElementById( 'qp-hero-toast' );
		if ( ! node ) {
			node = document.createElement( 'div' );
			node.id = 'qp-hero-toast';
			node.className = 'qp-hero-toast';
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

	function escapeHtml( str ) {
		return String( str ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	// The slot for a picture/img hero is a display:contents span — it has no box
	// of its own, so hover and geometry must use a real ancestor (the hero
	// section). Background heroes are real elements; use them directly.
	function anchorFor( slot ) {
		if ( slot._qpAnchor ) {
			return slot._qpAnchor;
		}
		var anchor = slot;
		if ( slot.getAttribute( 'data-qp-hero-bg' ) !== '1' ) {
			anchor = slot.closest( 'section, .page-hero, .hero' ) || slot.parentElement || slot;
		}
		slot._qpAnchor = anchor;
		return anchor;
	}

	/* ------------------------------------------------------------- toolbar */

	function uploadIcon() {
		return '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" aria-hidden="true">' +
			'<path d="M12 16V6m0 0L8 10m4-4 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
			'<path d="M5 18h14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>' +
			'</svg>';
	}

	function resetIcon() {
		return '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">' +
			'<path d="M4 9a8 8 0 1 1-1 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
			'<path d="M4 4v5h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>' +
			'</svg>';
	}

	function ensureToolbar() {
		if ( toolbar ) {
			return toolbar;
		}
		toolbar = document.createElement( 'div' );
		toolbar.className = 'qp-hero-toolbar';
		toolbar.setAttribute( 'role', 'toolbar' );

		var change = document.createElement( 'button' );
		change.type = 'button';
		change.className = 'qp-hero-btn qp-hero-btn--change';
		change.innerHTML = uploadIcon() + '<span>' + escapeHtml( cfg.i18n.change ) + '</span>';
		change.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( hoverSlot ) {
				openFrame( hoverSlot );
			}
		} );
		toolbar.appendChild( change );

		var reset = document.createElement( 'button' );
		reset.type = 'button';
		reset.className = 'qp-hero-btn qp-hero-btn--reset';
		reset.innerHTML = resetIcon() + '<span>' + escapeHtml( cfg.i18n.reset ) + '</span>';
		reset.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( hoverSlot && window.confirm( cfg.i18n.confirm ) ) {
				saveHero( hoverSlot, 0, true );
			}
		} );
		toolbar.appendChild( reset );

		// Keep the toolbar visible while the pointer is on it.
		toolbar.addEventListener( 'mouseenter', function () {
			window.clearTimeout( hideTimer );
		} );
		toolbar.addEventListener( 'mouseleave', scheduleHide );

		document.body.appendChild( toolbar );
		return toolbar;
	}

	function positionToolbar( slot ) {
		var tb = ensureToolbar();
		var rect = anchorFor( slot ).getBoundingClientRect();
		// Anchor near the top-right of the hero, clamped into the viewport.
		var top = Math.max( 12, rect.top + 16 );
		tb.style.top = ( top + window.scrollY ) + 'px';
		tb.style.left = ( Math.min( rect.right, window.innerWidth ) - 16 + window.scrollX ) + 'px';
		tb.style.transform = 'translateX(-100%)';
	}

	function showToolbar( slot ) {
		window.clearTimeout( hideTimer );
		hoverSlot = slot;
		var tb = ensureToolbar();
		var reset = tb.querySelector( '.qp-hero-btn--reset' );
		if ( reset ) {
			reset.style.display = slot.getAttribute( 'data-qp-hero-override' ) === '1' ? '' : 'none';
		}
		positionToolbar( slot );
		tb.classList.add( 'is-visible' );
	}

	function scheduleHide() {
		window.clearTimeout( hideTimer );
		hideTimer = window.setTimeout( function () {
			if ( toolbar ) {
				toolbar.classList.remove( 'is-visible' );
			}
			hoverSlot = null;
		}, 180 );
	}

	/* ------------------------------------------------------------ media frame */

	function openFrame( slot ) {
		activeSlot = slot;

		if ( ! frame ) {
			frame = window.wp.media( {
				title: cfg.i18n.title,
				button: { text: cfg.i18n.button },
				library: { type: 'image' },
				multiple: false
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				if ( activeSlot && attachment && attachment.id ) {
					saveHero( activeSlot, attachment.id, false, attachment );
				}
			} );
		}

		frame.open();
	}

	/* --------------------------------------------------------------- saving */

	function bestUrl( attachment ) {
		if ( ! attachment ) {
			return '';
		}
		if ( attachment.sizes && attachment.sizes.full && attachment.sizes.full.url ) {
			return attachment.sizes.full.url;
		}
		return attachment.url || '';
	}

	function swapImage( slot, url ) {
		if ( ! url ) {
			return;
		}
		// CSS-background hero (legacy .page-header): set inline background-image.
		if ( slot.getAttribute( 'data-qp-hero-bg' ) === '1' ) {
			slot.style.backgroundImage = 'url("' + url + '")';
			return;
		}
		// Picture/img hero: collapse <picture> sources and point <img> at the url.
		var picture = slot.querySelector( 'picture' );
		if ( picture ) {
			picture.querySelectorAll( 'source' ).forEach( function ( s ) {
				s.removeAttribute( 'srcset' );
			} );
		}
		var img = slot.querySelector( 'img' );
		if ( img ) {
			img.removeAttribute( 'srcset' );
			img.setAttribute( 'src', url );
		}
	}

	function saveHero( slot, attachmentId, reset, attachment ) {
		var key = slot.getAttribute( 'data-qp-hero-key' );
		if ( ! key ) {
			return;
		}

		slot.classList.add( 'qp-hero--saving' );

		var body = new URLSearchParams();
		body.set( 'action', cfg.action );
		body.set( 'nonce', cfg.nonce );
		body.set( 'key', key );
		if ( reset ) {
			body.set( 'reset', '1' );
		} else {
			body.set( 'attachment_id', String( attachmentId ) );
		}

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
			slot.classList.remove( 'qp-hero--saving' );
			if ( ! json || ! json.success ) {
				throw { code: 'error' };
			}
			// Prefer the locally-selected attachment URL for an instant swap,
			// fall back to the server's canonical URL.
			var url = reset ? json.data.url : ( bestUrl( attachment ) || json.data.url );
			swapImage( slot, url );

			if ( json.data.isOverride ) {
				slot.setAttribute( 'data-qp-hero-override', '1' );
			} else {
				slot.removeAttribute( 'data-qp-hero-override' );
			}
			if ( hoverSlot === slot && toolbar ) {
				var resetBtn = toolbar.querySelector( '.qp-hero-btn--reset' );
				if ( resetBtn ) {
					resetBtn.style.display = json.data.isOverride ? '' : 'none';
				}
			}
			toast( reset ? cfg.i18n.reseted : cfg.i18n.saved, 'success' );
		} ).catch( function ( err ) {
			slot.classList.remove( 'qp-hero--saving' );
			if ( err && err.code === 'expired' ) {
				toast( cfg.i18n.expired, 'error' );
			} else {
				toast( cfg.i18n.saveError, 'error' );
			}
		} );
	}

	/* ----------------------------------------------------------------- init */

	function init() {
		var slots = document.querySelectorAll( '[data-qp-hero-key]' );
		if ( ! slots.length ) {
			return;
		}
		ensureToolbar();
		slots.forEach( function ( slot ) {
			// Picture/img heroes are wrapped in a .qp-hero-slot span (display:contents)
			// by PHP. CSS-background heroes are the element itself — don't give them
			// the display:contents class or their layout collapses.
			if ( slot.getAttribute( 'data-qp-hero-bg' ) !== '1' ) {
				slot.classList.add( 'qp-hero-slot' );
			}
			// A display:contents span generates no box, so bind hover to a real
			// ancestor (the hero section). Background heroes anchor to themselves.
			var anchor = anchorFor( slot );
			anchor.addEventListener( 'mouseenter', function () {
				showToolbar( slot );
			} );
			anchor.addEventListener( 'mouseleave', scheduleHide );
		} );

		window.addEventListener( 'scroll', function () {
			if ( hoverSlot && toolbar && toolbar.classList.contains( 'is-visible' ) ) {
				positionToolbar( hoverSlot );
			}
		}, { passive: true } );
		window.addEventListener( 'resize', function () {
			if ( hoverSlot && toolbar && toolbar.classList.contains( 'is-visible' ) ) {
				positionToolbar( hoverSlot );
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
