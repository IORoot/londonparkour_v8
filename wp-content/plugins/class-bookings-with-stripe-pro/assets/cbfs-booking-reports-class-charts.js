/**
 * Class Bookings with Stripe — per-class report charts (Chart.js).
 */
(function () {
	'use strict';

	var cfg = window.clasbproClassCharts;
	if ( ! cfg || typeof Chart === 'undefined' ) {
		return;
	}

	var currency = cfg.currency || {};
	var accent = '#0e7490';
	var accentLight = 'rgba(14, 116, 144, 0.15)';

	function formatMoney( value ) {
		var n = Number( value );
		if ( ! Number.isFinite( n ) ) {
			return '';
		}
		var decimals = Number.isFinite( currency.decimals ) ? currency.decimals : 2;
		var formatted = n.toFixed( decimals );
		var symbol = currency.symbol || '';
		if ( currency.position === 'after' ) {
			return formatted + ' ' + symbol;
		}
		return symbol + formatted;
	}

	function monthLabels( months ) {
		return ( months || [] ).map( function ( ym ) {
			var parts = String( ym ).split( '-' );
			if ( parts.length !== 2 ) {
				return ym;
			}
			var d = new Date( parseInt( parts[ 0 ], 10 ), parseInt( parts[ 1 ], 10 ) - 1, 1 );
			return d.toLocaleDateString( undefined, { month: 'short', year: '2-digit' } );
		} );
	}

	function initBarChart( canvasId, labels, data, yLabel, isMoney ) {
		var canvas = document.getElementById( canvasId );
		if ( ! canvas ) {
			return;
		}
		var ctx = canvas.getContext( '2d' );
		if ( ! ctx ) {
			return;
		}

		new Chart( ctx, {
			type: 'bar',
			data: {
				labels: labels,
				datasets: [
					{
						label: yLabel,
						data: data,
						backgroundColor: accentLight,
						borderColor: accent,
						borderWidth: 1,
						borderRadius: 4,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function ( context ) {
								var val = context.parsed.y;
								if ( isMoney ) {
									return yLabel + ': ' + formatMoney( val );
								}
								return yLabel + ': ' + String( Math.round( val ) );
							},
						},
					},
				},
				scales: {
					y: {
						beginAtZero: true,
						title: { display: true, text: yLabel },
						ticks: {
							callback: function ( tickValue ) {
								if ( isMoney ) {
									return formatMoney( tickValue );
								}
								return tickValue;
							},
						},
					},
				},
			},
		} );
	}

	function initLineChart( canvasId, labels, data, yLabel, isMoney, isPercent ) {
		var canvas = document.getElementById( canvasId );
		if ( ! canvas ) {
			return;
		}
		var ctx = canvas.getContext( '2d' );
		if ( ! ctx ) {
			return;
		}

		new Chart( ctx, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [
					{
						label: yLabel,
						data: data,
						borderColor: accent,
						backgroundColor: accentLight,
						fill: true,
						tension: 0.25,
						pointRadius: 3,
						pointHoverRadius: 5,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function ( context ) {
								var val = context.parsed.y;
								if ( isPercent ) {
									return yLabel + ': ' + String( val ) + '%';
								}
								if ( isMoney ) {
									return yLabel + ': ' + formatMoney( val );
								}
								return yLabel + ': ' + String( val );
							},
						},
					},
				},
				scales: {
					y: {
						beginAtZero: true,
						max: isPercent ? 100 : undefined,
						title: { display: true, text: yLabel },
						ticks: {
							callback: function ( tickValue ) {
								if ( isPercent ) {
									return tickValue + '%';
								}
								if ( isMoney ) {
									return formatMoney( tickValue );
								}
								return tickValue;
							},
						},
					},
				},
			},
		} );
	}

	function initOccupancyChart() {
		var canvas = document.getElementById( 'clasbpro-class-chart-occupancy' );
		if ( ! canvas || ! cfg.occupancy || ! cfg.occupancy.length ) {
			return;
		}
		var ctx = canvas.getContext( '2d' );
		if ( ! ctx ) {
			return;
		}

		var points = cfg.occupancy.map( function ( p ) {
			return { x: p.x, y: p.y };
		} );

		new Chart( ctx, {
			type: 'line',
			data: {
				datasets: [
					{
						label: cfg.i18n.occupancy,
						data: points,
						borderColor: '#14b8a6',
						backgroundColor: 'rgba(20, 184, 166, 0.12)',
						fill: true,
						tension: 0.2,
						pointRadius: 4,
					},
				],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					tooltip: {
						callbacks: {
							label: function ( context ) {
								return cfg.i18n.occupancy + ': ' + context.parsed.y + '%';
							},
						},
					},
				},
				scales: {
					x: {
						type: 'time',
						time: {
							displayFormats: { day: 'd MMM', month: "MMM ''yy" },
						},
						title: { display: true, text: cfg.i18n.dateAxis },
					},
					y: {
						beginAtZero: true,
						max: 100,
						title: { display: true, text: cfg.i18n.occupancy },
						ticks: { callback: function ( v ) { return v + '%'; } },
					},
				},
			},
		} );
	}

	function init() {
		var months = cfg.months || [];
		var labels = monthLabels( months );
		var i18n = cfg.i18n || {};

		initBarChart(
			'clasbpro-class-chart-students',
			labels,
			cfg.studentsMonthly || [],
			i18n.students || 'Students',
			false
		);
		initBarChart(
			'clasbpro-class-chart-revenue',
			labels,
			cfg.revenueMonthly || [],
			i18n.revenue || 'Revenue',
			true
		);
		initLineChart(
			'clasbpro-class-chart-cumulative',
			labels,
			cfg.cumulativeRevenue || [],
			i18n.cumulativeRevenue || 'Cumulative revenue',
			true,
			false
		);
		initOccupancyChart();
		initBarChart(
			'clasbpro-class-chart-dow',
			cfg.dowLabels || [],
			cfg.dowCounts || [],
			i18n.bookings || 'Bookings',
			false
		);

		var classSelect = document.getElementById( 'clasbpro-reports-class-id' );
		if ( classSelect && classSelect.form ) {
			classSelect.addEventListener( 'change', function () {
				classSelect.form.submit();
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
