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
				// Bỏ tick: coi như chưa sao lưu, plugin chặn chạy hàng loạt / thay ảnh gốc cho tới khi tick lại.
				call( 'unconfirm_backup' ).then( function ( res ) { if ( show( res ) ) { $( 'qpwc-backup-at' ).textContent = ''; } } );
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

	/* ---------------- 4. Thay ảnh gốc bằng WebP ---------------- */
	var rpRunning = false;

	function rpCall( action ) {
		var body = new URLSearchParams();
		body.set( 'action', 'qpwc_replace' );
		body.set( 'nonce', QPWC.nonce );
		body.set( 'do', action );
		var p = chain.then( function () {
			return fetch( QPWC.ajax, { method: 'POST', credentials: 'same-origin', body: body } ).then( function ( r ) { return r.json(); } );
		} );
		chain = p.catch( function () {} );
		return p;
	}

	function rpShow( res ) {
		var msg = $( 'qpwc-rp-msg' );
		if ( ! res || ! res.success ) {
			msg.textContent = ( res && res.data && res.data.error ) || 'Có lỗi xảy ra.';
			return null;
		}
		msg.textContent = '';
		var d = res.data, s = d.state;
		$( 'rp-status' ).textContent = s.status + ( s.mode === 'undo' ? ' (hoàn tác)' : '' );
		$( 'rp-status' ).setAttribute( 'data-status', s.status );
		$( 'rp-status' ).setAttribute( 'data-mode', s.mode );
		$( 'rp-processed' ).textContent = s.total ? s.processed + ' / ' + s.total + ' (' + Math.min( 100, 100 * s.processed / s.total ).toFixed( 1 ).replace( '.', ',' ) + '%)' : s.processed;
		$( 'rp-replaced' ).textContent = d.replaced;
		$( 'rp-skipped' ).textContent = s.skipped;
		$( 'rp-failed' ).textContent = s.failed;
		$( 'rp-backup' ).textContent = fmt( d.backup );
		$( 'rp-backup' ).setAttribute( 'data-bytes', d.backup );
		$( 'rp-purged' ).textContent = fmt( s.purged );
		$( 'qpwc-rp-purge' ).disabled = ! d.backup;
		if ( s.purge_result ) {
			msg.style.color = '#1a7f37';
			msg.textContent = 'Đã xóa vĩnh viễn ' + s.purge_result.files + ' file ảnh gốc, giải phóng ' + fmt( s.purge_result.bytes ) + '.';
		} else {
			msg.style.color = '#cf222e';
		}
		return d;
	}

	function rpLoop() {
		if ( rpRunning ) { return; }
		rpRunning = true;
		( function next() {
			rpCall( 'step' ).then( function ( res ) {
				var d = rpShow( res );
				if ( d && d.state.status === 'running' && rpRunning ) {
					next();
				} else {
					rpRunning = false;
					// Xong lượt: tải lại để bảng mục 3, danh sách ảnh lỗi và nhật ký cập nhật theo.
					if ( d && d.state.status === 'done' ) { window.location.reload(); }
				}
			} ).catch( function () {
				rpRunning = false;
				$( 'qpwc-rp-msg' ).textContent = 'Mất kết nối, bấm "Chạy tiếp" để tiếp tục.';
			} );
		}() );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		if ( ! $( 'qpwc-rp-start' ) ) { return; }
		$( 'qpwc-rp-start' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.confirm( 'Thay ảnh gốc JPG/PNG bằng WebP và cập nhật đường dẫn trong các bài viết?\n\nẢnh gốc được chuyển vào thư mục tạm, còn hoàn tác được cho tới khi bấm "Xóa vĩnh viễn ảnh gốc".' ) ) { return; }
			rpCall( 'start' ).then( function ( res ) { if ( rpShow( res ) ) { rpLoop(); } } );
		} );
		$( 'qpwc-rp-pause' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			rpRunning = false;
			rpCall( 'pause' ).then( rpShow );
		} );
		$( 'qpwc-rp-resume' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			rpCall( 'resume' ).then( function ( res ) { if ( rpShow( res ) ) { rpLoop(); } } );
		} );
		$( 'qpwc-rp-undo' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.confirm( 'Trả lại ảnh gốc JPG/PNG cho các ảnh đã thay (chỉ được với ảnh gốc chưa bị xóa vĩnh viễn)?' ) ) { return; }
			rpCall( 'undo' ).then( function ( res ) { if ( rpShow( res ) ) { rpLoop(); } } );
		} );
		$( 'qpwc-rp-purge' ).addEventListener( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.confirm( 'XÓA VĨNH VIỄN toàn bộ ảnh gốc trong thư mục tạm?\n\nChỉ làm khi đã kiểm tra website hiển thị đầy đủ ảnh. Sau bước này KHÔNG hoàn tác được.' ) ) { return; }
			rpCall( 'purge' ).then( rpShow );
		} );
		// Tải lại trang khi đang chạy dở (vd. mất mạng) → tự chạy tiếp.
		if ( $( 'rp-status' ).getAttribute( 'data-status' ) === 'running' ) { rpLoop(); }
	} );
}() );
