/**
 * Class Bookings with Stripe — Reports yearly line chart (Chart.js + zoom).
 *
 * Expects window.clasbproReportsChart from wp_localize_script.
 */
(function () {
	'use strict';

	var cfg = window.clasbproReportsChart;

	var yearSelect = document.getElementById( 'clasbpro-reports-year' );
	if ( yearSelect && yearSelect.form ) {
		yearSelect.addEventListener( 'change', function () {
			yearSelect.form.submit();
		} );
	}

	function init() {
		if ( ! cfg || ! cfg.hasData || typeof Chart === 'undefined' ) {
			return;
		}

		var canvas = document.getElementById( 'clasbpro-reports-year-chart' );
		if ( ! canvas ) {
			return;
		}

		var ctx = canvas.getContext( '2d' );
		if ( ! ctx ) {
			return;
		}

		var datasets = ( cfg.datasets || [] ).map( function ( ds ) {
			return {
				label: ds.label,
				data: ds.data,
				borderColor: ds.borderColor,
				backgroundColor: 'transparent',
				fill: false,
				tension: 0.2,
				pointRadius: 0,
				pointHitRadius: 8,
				pointHoverRadius: 4,
				borderWidth: 2,
			};
		} );

		var chart = new Chart( ctx, {
			type: 'line',
			data: { datasets: datasets },
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: {
					mode: 'index',
					intersect: false,
				},
				plugins: {
					legend: {
						position: 'bottom',
						labels: {
							boxWidth: 14,
							boxHeight: 14,
							padding: 12,
							usePointStyle: true,
						},
					},
					tooltip: {
						mode: 'index',
						intersect: false,
						callbacks: {
							label: function ( context ) {
								var label = context.dataset.label || '';
								if ( label ) {
									label += ': ';
								}
								var y = context.parsed.y;
								if ( y !== null && y !== undefined && Number.isFinite( y ) ) {
									label += String( Math.round( y ) );
								}
								return label;
							},
						},
					},
					zoom: {
						pan: {
							enabled: true,
							mode: 'xy',
						},
						zoom: {
							wheel: { enabled: true },
							pinch: { enabled: true },
							mode: 'xy',
						},
						limits: {
							x: { min: cfg.xMin, max: cfg.xMax },
							y: { min: 0 },
						},
					},
				},
				scales: {
					x: {
						type: 'time',
						min: cfg.xMin,
						max: cfg.xMax,
						time: {
							displayFormats: {
								day: 'd MMM',
								week: 'd MMM',
								month: "MMM ''yy",
							},
						},
						title: {
							display: true,
							text: cfg.i18n.dateAxis,
						},
					},
					y: {
						beginAtZero: true,
						title: {
							display: true,
							text: cfg.i18n.studentsBooked,
						},
						ticks: {
							// Return null for non-integer ticks so Chart.js hides the label *and* grid line
							// (rounding the label alone still drew multiple lines at 0.2, 0.4, … all reading "0").
							callback: function ( tickValue ) {
								var n = Number( tickValue );
								if ( ! Number.isFinite( n ) ) {
									return tickValue;
								}
								if ( Math.abs( n - Math.round( n ) ) > 1e-6 ) {
									return null;
								}
								return Math.round( n );
							},
						},
					},
				},
			},
		} );

		var resetBtn = document.getElementById( 'clasbpro-reports-chart-reset' );
		if ( resetBtn ) {
			resetBtn.addEventListener( 'click', function () {
				if ( typeof chart.resetZoom === 'function' ) {
					chart.resetZoom();
				}
			} );
		}

		canvas.addEventListener( 'dblclick', function () {
			if ( typeof chart.resetZoom === 'function' ) {
				chart.resetZoom();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
