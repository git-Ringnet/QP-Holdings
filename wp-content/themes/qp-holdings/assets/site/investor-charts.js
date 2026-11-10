/**
 * Investor "Thông tin cổ phiếu" page – donut charts.
 *
 * Renders:
 *  - Main shareholders doughnut chart with leader-line percentage labels.
 *  - Small "domestic vs foreign" doughnut chart in the side card.
 *
 * Reads data from `data-qp-chart` / `data-qp-chart-data` attributes set by
 * the PHP template, so percentages, names, and colours stay server-driven.
 */
(function () {
	'use strict';

	function readJSON(el, attr) {
		try {
			return JSON.parse(el.getAttribute(attr) || '[]');
		} catch (err) {
			return [];
		}
	}

	function formatPct(value) {
		return Math.abs(value) < 0.005 ? '0' : value.toFixed(2);
	}

	function init() {
		if (typeof window.Chart === 'undefined') {
			return;
		}

		var shareholdersEl = document.querySelector('[data-qp-chart="shareholders"]');
		if (shareholdersEl) {
			var shData = readJSON(shareholdersEl, 'data-qp-chart-data');
			var shCanvas = shareholdersEl.querySelector('canvas');
			if (shCanvas && shData.length) {
				renderShareholders(shCanvas, shData);
			}
		}

		var typesEl = document.querySelector('[data-qp-chart="investor-types"]');
		if (typesEl) {
			var tData = readJSON(typesEl, 'data-qp-chart-data');
			var tCanvas = typesEl.querySelector('canvas');
			if (tCanvas && tData.length) {
				renderInvestorTypes(tCanvas, tData);
			}
		}
	}

	/**
	 * Custom Chart.js plugin: draws leader lines + percentage labels around the
	 * donut. Splits labels by side (left / right) and de-overlaps vertically.
	 */
	var externalLabelsPlugin = {
		id: 'qpExternalLabels',
		afterDatasetsDraw: function (chart) {
			var meta = chart.getDatasetMeta(0);
			if (!meta || !meta.data || !meta.data.length) return;

			var ctx = chart.ctx;
			var values = chart.data.datasets[0].data;
			var total = values.reduce(function (a, b) { return a + b; }, 0);
			if (total <= 0) return;

			var labels = meta.data.map(function (arc, i) {
				var angle = (arc.startAngle + arc.endAngle) / 2;
				var r = arc.outerRadius;
				var isRight = Math.cos(angle) >= 0;
				return {
					arc: arc,
					angle: angle,
					r: r,
					isRight: isRight,
					pct: (values[i] / total) * 100,
					color: arc.options.backgroundColor,
					cx: arc.x + Math.cos(angle) * r,
					cy: arc.y + Math.sin(angle) * r,
					lx: arc.x + Math.cos(angle) * (r + 22),
					ly: arc.y + Math.sin(angle) * (r + 22)
				};
			});

			var minGap = 22;
			['left', 'right'].forEach(function (side) {
				var group = labels.filter(function (l) {
					return side === 'right' ? l.isRight : !l.isRight;
				});
				group.sort(function (a, b) { return a.ly - b.ly; });
				for (var j = 1; j < group.length; j++) {
					if (group[j].ly - group[j - 1].ly < minGap) {
						group[j].ly = group[j - 1].ly + minGap;
					}
				}
			});

			ctx.save();
			ctx.font = '500 13px "Noto Sans", sans-serif';
			ctx.textBaseline = 'middle';

			labels.forEach(function (l) {
				var endX = l.lx + (l.isRight ? 28 : -28);

				ctx.strokeStyle = l.color;
				ctx.lineWidth = 1.25;
				ctx.beginPath();
				ctx.moveTo(l.cx, l.cy);
				ctx.lineTo(l.lx, l.ly);
				ctx.lineTo(endX, l.ly);
				ctx.stroke();

				ctx.fillStyle = '#212529';
				ctx.textAlign = l.isRight ? 'left' : 'right';
				ctx.fillText(
					l.pct.toFixed(2).replace('.', ',') + '%',
					endX + (l.isRight ? 4 : -4),
					l.ly
				);
			});

			ctx.restore();
		}
	};

	function renderShareholders(canvas, data) {
		new window.Chart(canvas, {
			type: 'doughnut',
			data: {
				labels: data.map(function (d) { return d.name; }),
				datasets: [{
					data: data.map(function (d) { return d.value; }),
					backgroundColor: data.map(function (d) { return d.color; }),
					borderColor: '#ffffff',
					borderWidth: 2,
					hoverOffset: 6
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '62%',
				layout: { padding: { top: 16, bottom: 16, left: 104, right: 104 } },
				animation: { animateRotate: true, duration: 700 },
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function (ctx) {
								var d = data[ctx.dataIndex];
								var shares = d.shares ? ' — ' + d.shares : '';
								return d.name + ': ' + formatPct(d.value) + '%' + shares;
							}
						}
					}
				}
			},
			plugins: [externalLabelsPlugin]
		});
	}

	function renderInvestorTypes(canvas, data) {
		// Inflate sub-1% slices to a visible minimum so a 0.04% segment
		// still renders as a thin coloured wedge. Tooltip + legend keep
		// the real percentage from PHP data.
		var minVisualPct = 4;
		var chartValues = data.map(function (d) {
			return d.value > 0 && d.value < minVisualPct ? minVisualPct : d.value;
		});

		new window.Chart(canvas, {
			type: 'doughnut',
			data: {
				labels: data.map(function (d) { return d.label; }),
				datasets: [{
					data: chartValues,
					backgroundColor: data.map(function (d) { return d.color; }),
					borderColor: '#ffffff',
					borderWidth: 0,
					hoverOffset: 4
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '68%',
				animation: { animateRotate: true, duration: 600 },
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function (ctx) {
								var d = data[ctx.dataIndex];
								return d.label + ': ' + formatPct(d.value) + '%';
							}
						}
					}
				}
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
