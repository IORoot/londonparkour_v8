( function () {
	'use strict';

	const core = window.CLASBOWPRO_CalendarCore;
	if ( ! core ) {
		return;
	}

	async function fetchMonth( classId, year, month ) {
		const url = core.buildRestUrl( 'class-calendar', {
			class_id: classId,
			year: year,
			month: month,
		} );
		const res = await fetch( url );
		if ( ! res.ok ) {
			throw new Error( 'calendar' );
		}
		return res.json();
	}

	function getSelectionUi( cal ) {
		return {
			panel: cal.querySelector( '[data-cbfs-selection-panel]' ),
			hint: cal.querySelector( '[data-cbfs-month-hint]' ),
			selection: cal.querySelector( '[data-cbfs-selection]' ),
			selectionText: cal.querySelector( '[data-cbfs-selection-text]' ),
		};
	}

	function seatsLabel( remaining ) {
		const n = parseInt( remaining, 10 ) || 0;
		return n === 1 ? '1 seat left' : ( n + ' seats left' );
	}

	function buildSummary( cal, info, date ) {
		const time = info.time_label || '';
		let summary = core.formatDateLong( date );
		if ( time ) {
			summary += ' · ' + time;
		}
		if ( cal.dataset.cbfsShowSeatsRemaining === '1' && ! info.cancelled ) {
			summary += ' · ' + seatsLabel( info.remaining );
		}
		return summary;
	}

	function clearSelection( cal, state ) {
		state.selectedDate = '';
		cal.querySelectorAll( '.cbfs-appointment-calendar__day' ).forEach( function ( el ) {
			core.setDayPressed( el, false );
		} );
		const dateInput = cal.querySelector( '[name="class_date"]' );
		if ( dateInput ) {
			dateInput.value = '';
			dateInput.dataset.remaining = '0';
		}
		const ui = getSelectionUi( cal );
		if ( ui.selection ) {
			ui.selection.hidden = true;
		}
		if ( ui.selectionText ) {
			ui.selectionText.textContent = '';
		}
		const form = cal.closest( '.cbfs-form__form' );
		if ( form && window.CLASBOWPRO_updateSeats ) {
			window.CLASBOWPRO_updateSeats( form );
		}
	}

	function updateMonthHint( cal, state ) {
		const ui = getSelectionUi( cal );
		if ( ! ui.hint ) {
			return;
		}
		const meta = state.monthMeta || {};
		const days = state.days || {};
		const hasClassDays = Object.keys( days ).length > 0;
		const emptyLabel = cal.dataset.labelMonthEmpty || 'No bookable dates this month.';

		if ( ! hasClassDays || meta.has_selectable_in_month === false ) {
			ui.hint.hidden = false;
			ui.hint.textContent = emptyLabel;
		} else if ( ! state.selectedDate ) {
			ui.hint.hidden = false;
			ui.hint.textContent = cal.dataset.labelPickDay || 'Pick a highlighted day on the calendar above.';
		} else {
			ui.hint.hidden = true;
		}
	}

	function selectClassDate( cal, state, date, info, btn ) {
		if ( ! info || ! info.selectable ) {
			return;
		}

		clearSelection( cal, state );
		core.setDayPressed( btn, true );
		state.selectedDate = date;

		const dateInput = cal.querySelector( '[name="class_date"]' );
		if ( dateInput ) {
			dateInput.value = date;
			dateInput.dataset.remaining = String( info.remaining ?? 0 );
			dateInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}

		const ui = getSelectionUi( cal );
		if ( ui.hint ) {
			ui.hint.hidden = true;
		}
		if ( ui.selection && ui.selectionText ) {
			ui.selectionText.textContent = buildSummary( cal, info, date );
			ui.selection.hidden = false;
		}

		const form = cal.closest( '.cbfs-form__form' );
		if ( form && window.CLASBOWPRO_updateSeats ) {
			window.CLASBOWPRO_updateSeats( form );
		}
	}

	function dayAriaLabel( cal, date, info ) {
		let label = core.formatDateLong( date );
		if ( ! info || ! info.has_class ) {
			return label;
		}
		if ( info.cancelled ) {
			label += ', cancelled';
		} else if ( info.full ) {
			label += ', full';
		} else if ( info.selectable ) {
			label += ', available';
		}
		return label;
	}

	function appendDayStatus( btn, info, fullLabel, cancelledLabel ) {
		if ( ! info || ! info.has_class ) {
			return;
		}
		if ( info.cancelled ) {
			const status = document.createElement( 'span' );
			status.className = 'cbfs-appointment-calendar__day-status';
			status.textContent = cancelledLabel;
			btn.appendChild( status );
			return;
		}
		if ( info.full ) {
			const status = document.createElement( 'span' );
			status.className = 'cbfs-appointment-calendar__day-status';
			status.textContent = fullLabel;
			btn.appendChild( status );
		}
	}

	function appendClassDayDot( btn, info ) {
		if ( ! info || ! info.has_class ) {
			return;
		}

		const wrap = document.createElement( 'span' );
		wrap.className = 'cbfs-appointment-calendar__day-dots';
		wrap.setAttribute( 'aria-hidden', 'true' );

		const dot = document.createElement( 'span' );
		dot.className = 'cbfs-appointment-calendar__day-dot';
		if ( info.cancelled ) {
			dot.classList.add( 'is-cancelled' );
		} else if ( info.full ) {
			dot.classList.add( 'is-booked-only' );
		} else {
			dot.classList.add( 'is-available' );
		}

		wrap.appendChild( dot );
		btn.appendChild( wrap );
	}

	function renderGrid( cal, state ) {
		const grid = cal.querySelector( '[data-cbfs-cal-grid]' );
		const title = cal.querySelector( '[data-cbfs-cal-title]' );
		if ( ! grid || ! title ) {
			return;
		}

		title.textContent = core.MONTHS[ state.month - 1 ] + ' ' + state.year;

		const offset = core.monthStartOffset( state.year, state.month );
		const totalDays = core.daysInMonth( state.year, state.month );
		const days = state.days || {};
		const fragment = document.createDocumentFragment();
		const fullLabel = cal.dataset.labelFull || 'Full';
		const cancelledLabel = cal.dataset.labelCancelled || 'Cancelled';

		for ( let i = 0; i < offset; i++ ) {
			const cell = document.createElement( 'span' );
			cell.className = 'cbfs-appointment-calendar__day cbfs-appointment-calendar__day--empty';
			cell.setAttribute( 'aria-hidden', 'true' );
			fragment.appendChild( cell );
		}

		for ( let d = 1; d <= totalDays; d++ ) {
			const date = core.ymd( state.year, state.month, d );
			const info = days[ date ];
			const btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'cbfs-appointment-calendar__day';
			btn.dataset.date = date;

			const num = document.createElement( 'span' );
			num.className = 'cbfs-appointment-calendar__day-num';
			num.textContent = String( d );
			btn.appendChild( num );

			if ( info && info.has_class ) {
				if ( info.cancelled ) {
					btn.classList.add( 'is-cancelled' );
					btn.disabled = true;
				} else if ( info.full ) {
					btn.classList.add( 'has-booked-only', 'is-full' );
					btn.disabled = true;
				} else if ( info.selectable ) {
					btn.classList.add( 'has-available' );
				}
				appendClassDayDot( btn, info );
				appendDayStatus( btn, info, fullLabel, cancelledLabel );
			} else {
				btn.classList.add( 'is-disabled' );
				btn.disabled = true;
			}

			btn.setAttribute( 'aria-label', dayAriaLabel( cal, date, info ) );
			core.markTodayOnDay( cal, btn, date );
			core.setDayPressed( btn, state.selectedDate === date );

			if ( info && info.selectable ) {
				btn.addEventListener( 'click', function () {
					selectClassDate( cal, state, date, info, btn );
				} );
			}

			fragment.appendChild( btn );
		}

		grid.replaceChildren( fragment );
		updateMonthHint( cal, state );
		core.fitCalendarHeight( cal );
	}

	function readCalendarPreset( cal ) {
		const form = cal.closest( '.cbfs-form__form' );
		return ( form && form.dataset.cbfsPresetDate ) || cal.dataset.cbfsPresetDate || '';
	}

	function initCalendar( cal ) {
		if ( cal.dataset.cbfsCalendarInit === '1' ) {
			return;
		}
		cal.dataset.cbfsCalendarInit = '1';

		const presetDate = readCalendarPreset( cal );

		const now = new Date();
		let year = now.getFullYear();
		let month = now.getMonth() + 1;
		if ( presetDate && /^\d{4}-\d{2}-\d{2}$/.test( presetDate ) ) {
			const parts = presetDate.split( '-' );
			year = parseInt( parts[0], 10 );
			month = parseInt( parts[1], 10 );
		}

		const state = {
			year: year,
			month: month,
			days: {},
			monthMeta: {},
			selectedDate: '',
			presetDate: presetDate,
			presetApplied: false,
		};

		const syncNav = core.bindMonthNav( cal, state, {
			onMonthChange: function () {
				clearSelection( cal, state );
			},
			onLoadMonth: function ( calEl, st ) {
				return core.loadMonth( calEl, st, 'class-date', fetchMonth, renderGrid );
			},
		} );

		core.loadMonth( cal, state, 'class-date', fetchMonth, renderGrid ).then( function () {
			syncNav();
			applyPresetDay( cal, state );
		} );
	}

	function applyPresetDay( cal, state ) {
		if ( ! state.presetDate || state.presetApplied ) {
			return;
		}
		const info = ( state.days || {} )[ state.presetDate ];
		const btn = cal.querySelector( '.cbfs-appointment-calendar__day[data-date="' + state.presetDate + '"]' );
		if ( ! info || ! info.selectable || ! btn ) {
			return;
		}
		state.presetApplied = true;
		selectClassDate( cal, state, state.presetDate, info, btn );
	}

	function initCalendars( root ) {
		const scope = root || document;
		scope.querySelectorAll( '[data-cbfs-class-date-calendar]' ).forEach( initCalendar );
		core.bindResize();
		scope.querySelectorAll( '[data-cbfs-class-date-calendar]' ).forEach( core.fitCalendarHeight );
	}

	function init() {
		initCalendars( document );
	}

	window.CLASBOWPRO_initClassDateCalendars = initCalendars;

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
