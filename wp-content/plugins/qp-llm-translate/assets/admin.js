(function ($) {
	var pollTimer = null;
	var hideTimer = null;
	var lastBusy = false;

	function cfg() {
		return window.qpLlmTranslate || {};
	}

	function toast() {
		var $el = $('#qp-llm-toast');
		if ($el.length) {
			return $el;
		}
		$el = $(
			'<div id="qp-llm-toast" class="qp-llm-toast" hidden>' +
				'<div class="qp-llm-toast__bar"></div>' +
				'<div class="qp-llm-toast__body">' +
					'<span class="qp-llm-toast__spin" aria-hidden="true"></span>' +
					'<div class="qp-llm-toast__copy">' +
						'<strong class="qp-llm-toast__title"></strong>' +
						'<ul class="qp-llm-toast__jobs"></ul>' +
					'</div>' +
					'<button type="button" class="qp-llm-toast__close" aria-label="Close">&times;</button>' +
				'</div>' +
			'</div>'
		);
		$('body').append($el);
		$el.on('click', '.qp-llm-toast__close', function () {
			hideToast(true);
		});
		return $el;
	}

	function statusLabel(status) {
		var i18n = cfg().i18n || {};
		if (status === 'running') return i18n.running || 'Đang dịch';
		if (status === 'queued') return i18n.queuedJob || 'Chờ';
		if (status === 'done') return i18n.doneJob || 'Xong';
		if (status === 'error') return i18n.errorJob || 'Lỗi';
		return status;
	}

	function render(jobs, forceShow) {
		jobs = jobs || [];
		var busy = jobs.some(function (j) {
			return j.status === 'queued' || j.status === 'running';
		});
		var failed = jobs.some(function (j) {
			return j.status === 'error';
		});
		if (!jobs.length && !forceShow) {
			if (lastBusy) {
				finish('done');
			}
			return;
		}
		if (!jobs.length) {
			return;
		}

		var i18n = cfg().i18n || {};
		var $el = toast();
		var title = busy ? (i18n.title || 'Đang dịch') : failed ? (i18n.failed || 'Dịch lỗi') : (i18n.done || 'Đã dịch xong');
		$el.removeAttr('hidden').toggleClass('is-busy', busy).toggleClass('is-done', !busy && !failed).toggleClass('is-err', !busy && failed);
		$el.find('.qp-llm-toast__title').text(title);

		var html = jobs.map(function (job) {
			var err = job.status === 'error' && job.error_message ? ' · ' + job.error_message : '';
			return '<li><span class="qp-llm-toast__lang">' + escapeHtml((job.target_lang || '').toUpperCase()) +
				'</span> <span class="qp-llm-toast__post">' + escapeHtml(job.title || '') +
				'</span> — ' + escapeHtml(statusLabel(job.status)) + escapeHtml(err) + '</li>';
		}).join('');
		$el.find('.qp-llm-toast__jobs').html(html);

		lastBusy = busy;
		if (busy) {
			clearTimeout(hideTimer);
			startPoll();
		} else {
			stopPoll();
			scheduleHide();
		}
	}

	function finish(kind) {
		var i18n = cfg().i18n || {};
		var $el = toast();
		$el.removeAttr('hidden').removeClass('is-busy').toggleClass('is-done', kind !== 'error').toggleClass('is-err', kind === 'error');
		$el.find('.qp-llm-toast__title').text(kind === 'error' ? (i18n.failed || 'Dịch lỗi') : (i18n.done || 'Đã dịch xong'));
		stopPoll();
		scheduleHide();
		lastBusy = false;
	}

	function scheduleHide() {
		clearTimeout(hideTimer);
		hideTimer = setTimeout(function () {
			hideToast(false);
		}, 6000);
	}

	function hideToast(immediate) {
		clearTimeout(hideTimer);
		var $el = $('#qp-llm-toast');
		if (immediate) {
			$el.attr('hidden', true);
		} else {
			$el.addClass('is-out');
			setTimeout(function () {
				$el.attr('hidden', true).removeClass('is-out');
			}, 280);
		}
	}

	function escapeHtml(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
		});
	}

	function startPoll() {
		if (pollTimer) return;
		pollTimer = setInterval(fetchStatus, 2500);
	}

	function stopPoll() {
		if (pollTimer) {
			clearInterval(pollTimer);
			pollTimer = null;
		}
	}

	function fetchStatus(forceShow) {
		var c = cfg();
		if (!c.ajaxUrl) return;
		$.get(c.ajaxUrl, {
			action: 'qp_llm_job_status',
			nonce: c.nonce,
			post_id: 0
		}).done(function (res) {
			if (res && res.success) {
				render((res.data && res.data.jobs) || [], !!forceShow);
			}
		});
	}

	$(document).on('click', '.qp-llm-queue-now', function (e) {
		e.preventDefault();
		var $btn = $(this);
		var $status = $btn.closest('.inside, .qp-llm-settings, body').find('.qp-llm-queue-status').first();
		if (!$status.length) {
			$status = $btn.siblings('.qp-llm-queue-status');
		}
		$btn.prop('disabled', true);
		render([{
			target_lang: '…',
			title: '',
			status: 'running'
		}], true);
		$.post(cfg().ajaxUrl, {
			action: 'qp_llm_queue_now',
			nonce: cfg().nonce,
			post_id: $btn.data('post')
		}).done(function (res) {
			$status.removeClass('is-err').addClass('is-ok').text((cfg().i18n || {}).queued || '');
			render((res && res.data && res.data.jobs) || [], true);
			startPoll();
		}).fail(function () {
			$status.removeClass('is-ok').addClass('is-err').text((cfg().i18n || {}).error || '');
			finish('error');
		}).always(function () {
			$btn.prop('disabled', false);
		});
	});

	$(function () {
		fetchStatus(false);
	});
})(jQuery);
