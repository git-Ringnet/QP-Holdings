/* QP Auto Translate – "Dịch ngay" button and background progress toast. */
(function ($) {
	'use strict';

	var cfg = window.qpatEditor || {};
	var i18n = cfg.i18n || {};
	var pollTimer = null;
	var hideTimer = null;
	var wasBusy = false;

	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function toast() {
		var $el = $('#qpat-toast');
		if ($el.length) {
			return $el;
		}
		$el = $(
			'<div id="qpat-toast" class="qpat-toast" role="status" hidden>' +
				'<div class="qpat-toast__bar"></div>' +
				'<div class="qpat-toast__body">' +
					'<span class="qpat-toast__spin" aria-hidden="true"></span>' +
					'<div class="qpat-toast__copy"><strong class="qpat-toast__title"></strong><ul class="qpat-toast__jobs"></ul></div>' +
					'<button type="button" class="qpat-toast__close" aria-label="Đóng">&times;</button>' +
				'</div>' +
			'</div>'
		);
		$('body').append($el);
		$el.on('click', '.qpat-toast__close', function () {
			hide(true);
		});
		return $el;
	}

	function render(jobs) {
		jobs = jobs || [];
		if (!jobs.length) {
			stopPoll();
			return;
		}
		var busy = jobs.some(function (j) { return j.status === 'queued' || j.status === 'running'; });
		var failed = jobs.some(function (j) { return j.status === 'error' || j.status === 'paused'; });
		var $el = toast();

		$el.removeAttr('hidden')
			.toggleClass('is-busy', busy)
			.toggleClass('is-done', !busy && !failed)
			.toggleClass('is-err', !busy && failed);
		$el.find('.qpat-toast__title').text(busy ? i18n.busy : failed ? i18n.failed : i18n.done);
		$el.find('.qpat-toast__jobs').html(jobs.map(function (j) {
			var label = (i18n.status || {})[j.status] || j.status;
			var msg = j.message && j.status !== 'running' ? ' · ' + j.message : '';
			return '<li><span class="qpat-toast__lang">' + esc(String(j.lang || '').toUpperCase()) + '</span> ' +
				'<span class="qpat-toast__post">' + esc(j.title || '') + '</span> — ' + esc(label) + esc(msg) + '</li>';
		}).join(''));

		if (busy) {
			clearTimeout(hideTimer);
			startPoll();
		} else {
			stopPoll();
			if (wasBusy) {
				refreshBox();
			}
			hideTimer = setTimeout(function () { hide(false); }, 8000);
		}
		wasBusy = busy;
	}

	function hide(now) {
		clearTimeout(hideTimer);
		var $el = $('#qpat-toast');
		if (now) {
			$el.attr('hidden', true);
			return;
		}
		$el.addClass('is-out');
		setTimeout(function () { $el.attr('hidden', true).removeClass('is-out'); }, 280);
	}

	function startPoll() {
		if (!pollTimer) {
			pollTimer = setInterval(fetchStatus, 3000);
		}
	}

	function stopPoll() {
		clearInterval(pollTimer);
		pollTimer = null;
	}

	function fetchStatus() {
		$.get(cfg.ajaxUrl, { action: 'qpat_job_status', nonce: cfg.nonce }).done(function (res) {
			if (res && res.success) {
				render(res.data.jobs);
			}
		});
	}

	// After translations finish, reload the box so the new EN/ZH links appear.
	function refreshBox() {
		var $box = $('#qpat-box .inside');
		if (!$box.length || !window.location.href) {
			return;
		}
		$.get(window.location.href).done(function (html) {
			var $fresh = $('<div>').append($.parseHTML(html)).find('#qpat-box .inside');
			if ($fresh.length) {
				$box.html($fresh.html());
			}
		});
	}

	$(document).on('click', '.qpat-queue-now', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var overwrite = String($btn.data('overwrite')) === '1';
		if (overwrite && !window.confirm(i18n.confirmOverwrite)) {
			return;
		}
		var $status = $btn.closest('.inside').find('.qpat-queue-status');
		$btn.prop('disabled', true);
		$.post(cfg.ajaxUrl, {
			action: 'qpat_queue_now',
			nonce: cfg.nonce,
			post_id: $btn.data('post'),
			overwrite: overwrite ? 1 : 0
		}).done(function (res) {
			var queued = res && res.success ? res.data.queued : 0;
			$status.removeClass('is-err').toggleClass('is-ok', queued > 0).text(queued ? i18n.queued : i18n.nothing);
			if (res && res.success) {
				render(res.data.jobs);
			}
		}).fail(function () {
			$status.removeClass('is-ok').addClass('is-err').text(i18n.error);
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	// Review of hand-edited paragraphs on an EN / ZH copy.
	$(document).on('click', '.qpat-review-do', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $box = $btn.closest('.inside');
		$box.find('.qpat-review-do').prop('disabled', true);
		$.post(cfg.ajaxUrl, {
			action: 'qpat_review',
			nonce: cfg.nonce,
			copy_id: $btn.data('copy'),
			index: $btn.data('index'),
			do: $btn.data('do')
		}).done(function () {
			// "Dùng bản dịch mới" changed the content: reload so the editor shows it.
			if (String($btn.data('do')) === 'accept') {
				window.location.reload();
				return;
			}
			refreshBox();
		}).fail(function (xhr) {
			var msg = xhr && xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data.message : i18n.error;
			window.alert(msg || i18n.error);
			$box.find('.qpat-review-do').prop('disabled', false);
		});
	});

	// Show progress of jobs already running (e.g. right after clicking "Đăng").
	$(fetchStatus);
})(jQuery);
