/* QP Holdings — dashboard charts (Chart.js 4.x).
 * Reads data from the localized `qphDashboardData` global. */
(function () {
	'use strict';

	if (typeof window.Chart === 'undefined' || !window.qphDashboardData) {
		return;
	}

	var data = window.qphDashboardData;
	var palette = data.palette || {};

	Chart.defaults.font.family =
		'"Noto Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
	Chart.defaults.color = palette.text || '#667085';

	function hexToRgba(hex, alpha) {
		var h = (hex || '#000000').replace('#', '');
		if (h.length === 3) {
			h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
		}
		var r = parseInt(h.substring(0, 2), 16);
		var g = parseInt(h.substring(2, 4), 16);
		var b = parseInt(h.substring(4, 6), 16);
		return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
	}

	var seriesColors = [
		palette.gold || '#D3A049',
		palette.navyMid || '#265D90',
		palette.teal || '#3E7C8C',
		palette.goldSoft || '#FFDDA4'
	];

	var legendBottom = {
		legend: {
			position: 'bottom',
			labels: { boxWidth: 12, boxHeight: 12, padding: 16, usePointStyle: true }
		}
	};

	function gridScales(stacked) {
		return {
			x: {
				stacked: !!stacked,
				grid: { display: false },
				ticks: { maxRotation: 0, autoSkipPadding: 12 }
			},
			y: {
				stacked: !!stacked,
				beginAtZero: true,
				ticks: { precision: 0 },
				grid: { color: palette.grid || 'rgba(25,54,96,0.08)' }
			}
		};
	}

	/* Chart 1: submissions over time (stacked area lines). */
	var subEl = document.getElementById('qphChartSubmissions');
	if (subEl && data.submissions && data.submissions.series && data.submissions.series.length) {
		new Chart(subEl, {
			type: 'line',
			data: {
				labels: data.submissions.labels,
				datasets: data.submissions.series.map(function (s, i) {
					var color = seriesColors[i % seriesColors.length];
					return {
						label: s.label,
						data: s.data,
						borderColor: color,
						backgroundColor: hexToRgba(color, 0.18),
						borderWidth: 2,
						fill: true,
						tension: 0.35,
						pointRadius: 2,
						pointHoverRadius: 4
					};
				})
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				plugins: legendBottom,
				scales: gridScales(true)
			}
		});
	}

	/* Chart 2: content publishing cadence (stacked bar). */
	var pubEl = document.getElementById('qphChartPublishing');
	if (pubEl && data.publishing && data.publishing.series) {
		new Chart(pubEl, {
			type: 'bar',
			data: {
				labels: data.publishing.labels,
				datasets: data.publishing.series.map(function (s, i) {
					var color = seriesColors[i % seriesColors.length];
					return {
						label: s.label,
						data: s.data,
						backgroundColor: color,
						borderRadius: 4,
						maxBarThickness: 26
					};
				})
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: legendBottom,
				scales: gridScales(true)
			}
		});
	}

	/* Chart 3: jobs by department (horizontal bar). */
	var jobEl = document.getElementById('qphChartJobs');
	if (jobEl && data.jobsByDept && data.jobsByDept.labels) {
		new Chart(jobEl, {
			type: 'bar',
			data: {
				labels: data.jobsByDept.labels,
				datasets: [
					{
						label: 'Vị trí',
						data: data.jobsByDept.data,
						backgroundColor: palette.gold || '#D3A049',
						borderRadius: 4,
						maxBarThickness: 22
					}
				]
			},
			options: {
				indexAxis: 'y',
				responsive: true,
				maintainAspectRatio: false,
				plugins: { legend: { display: false } },
				scales: {
					x: {
						beginAtZero: true,
						ticks: { precision: 0 },
						grid: { color: palette.grid || 'rgba(25,54,96,0.08)' }
					},
					y: { grid: { display: false } }
				}
			}
		});
	}
})();
