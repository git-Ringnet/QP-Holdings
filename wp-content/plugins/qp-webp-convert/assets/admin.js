( function () {
	'use strict';

	var $ = function ( id ) { return document.getElementById( id ); };
	var running = false;

	function fmt( b ) {
		if ( b >= 1073741824 ) { return ( b / 1073741824 ).toFixed( 2 ) + ' GB'; }
		if ( b >= 1048576 ) { return ( b / 1048576 ).toFixed( 2 ) + ' MB'; }
		if ( b >= 1024 ) { return ( b / 1024 ).toFixed( 2 ) + ' KB'; }
		return b + ' B';
	}

	// Các yêu cầu được xếp hàng để chạy đúng thứ tự bấm (ví dụ bấm "Bắt đầu" rồi "Tạm dừng" ngay).
	var chain = Promise.resolve();

	function call( action ) {
		var body = new URLSearchParams();
		body.set( 'action', 'qpwc_bulk' );
		body.set( 'nonce', QPWC.nonce );
		body.set( 'do', action );
		var p = chain.then( function () {
			return fetch( QPWC.ajax, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) { return r.json(); } );
		} );
		chain = p.catch( function () {} );
		return p;
	}

	function paint( data ) {
		var s = data.state, r = data.report;
		$( 'qpwc-status' ).textContent = s.status;
		$( 'qpwc-state' ).setAttribute( 'data-status', s.status );
		$( 'qpwc-processed' ).textContent = s.processed;
		$( 'r-total' ).textContent = r.total;
		$( 'r-done' ).textContent = r.done;
		$( 'r-skipped' ).textContent = r.skipped;
		$( 'r-failed' ).textContent = r.failed;
		$( 'r-pending' ).textContent = r.pending;
		$( 'r-src' ).textContent = fmt( r.src_bytes );
		$( 'r-src' ).setAttribute( 'data-bytes', r.src_bytes );
		$( 'r-webp' ).textContent = fmt( r.webp_bytes );
		$( 'r-webp' ).setAttribute( 'data-bytes', r.webp_bytes );
		$( 'r-saving' ).textContent = r.src_bytes ? ( 100 - 100 * r.webp_bytes / r.src_bytes ).toFixed( 1 ) + '%' : '—';
	}

	function show( res ) {
		var msg = $( 'qpwc-msg' );
		if ( res && res.success ) {
			msg.textContent = '';
			paint( res.data );
			return res.data;
		}
		msg.textContent = ( res && res.data && res.data.error ) || 'Có lỗi xảy ra.';
		return null;
	}

	function loop() {
		if ( running ) { return; }
		running = true;
		( function next() {
			call( 'step' ).then( function ( res ) {
				var d = show( res );
				if ( d && d.state.status === 'running' && running ) {
					next();
				} else {
					running = false;
					if ( d && d.state.status === 'done' ) { window.location.reload(); }
				}
			} ).catch( function () {
				running = false;
				$( 'qpwc-msg' ).textContent = 'Mất kết nối, bấm "Chạy tiếp" để tiếp tục.';
			} );
		}() );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! $( 'qpwc-start' ) ) { return; }

		$( 'qpwc-backup' ).addEventListener( 'change', function ( e ) {
			if ( e.target.checked ) {
				call( 'confirm_backup' ).then( function ( res ) { if ( show( res ) ) { $( 'qpwc-backup-at' ).textContent = '(vừa xác nhận)'; } } );
			} else {
				e.target.checked = true; // đã xác nhận thì giữ nguyên
			}
		} );
		$( 'qpwc-start' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			call( 'start' ).then( function ( res ) { if ( show( res ) ) { loop(); } } );
		} );
		$( 'qpwc-pause' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			running = false;
			call( 'pause' ).then( show );
		} );
		$( 'qpwc-resume' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			call( 'resume' ).then( function ( res ) { if ( show( res ) ) { loop(); } } );
		} );
		$( 'qpwc-reset' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			running = false;
			call( 'reset' ).then( show );
		} );
	} );
}() );
