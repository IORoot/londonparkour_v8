( function () {
	'use strict';

	const core = window.CLASBOWPRO_CalendarCore;
	if ( ! core ) {
		return;
	}

	async function fetchMonth( classId, year, month ) {
		const url = core.buildRestUrl( 'appointment-calendar', {
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

	async function fetchSlots( classId, date ) {
		const url = core.buildRestUrl( 'appointment-slots', {
			class_id: classId,
			date: date,
		} );
		const res = await fetch( url );
		if ( ! res.ok ) {
			throw new Error( 'slots' );
		}
		return res.json();
	}

	function formatSlotMeta( slot ) {
		const parts = [];
		if ( slot.location ) {
			parts.push( slot.location );
		}
		if ( slot.duration_minutes ) {
			parts.push( slot.duration_minutes + ' min' );
		}
		if ( slot.show_price && slot.price_label ) {
			parts.push( slot.price_label );
		}
		if ( slot.label ) {
			parts.push( slot.label );
		}
		return parts.join( ' · ' );
	}

	function getSlotsUi( cal ) {
		return {
			panel: cal.querySelector( '[data-cbfs-slots-panel]' ),
			heading: cal.querySelector( '[data-cbfs-slots-heading]' ),
			hint: cal.querySelector( '[data-cbfs-slots-hint]' ),
			list: cal.querySelector( '[data-cbfs-slot-list]' ),
			selection: cal.querySelector( '[data-cbfs-selection]' ),
			selectionText: cal.querySelector( '[data-cbfs-selection-text]' ),
		};
	}

	function setSlotsIdle( cal ) {
		const ui = getSlotsUi( cal );
		if ( ui.panel ) {
			ui.panel.classList.remove( 'is-active', 'is-loading' );
		}
		if ( ui.heading ) {
			ui.heading.hidden = true;
			ui.heading.textContent = '';
		}
		if ( ui.hint ) {
			ui.hint.hidden = false;
			ui.hint.textContent = cal.dataset.labelPickDay || 'Pick a highlighted day on the calendar above.';
			ui.hint.classList.remove( 'is-emphasis' );
		}
		if ( ui.list ) {
			ui.list.hidden = true;
			ui.list.innerHTML = '';
		}
		if ( ui.selection ) {
			ui.selection.hidden = true;
		}
		if ( ui.selectionText ) {
			ui.selectionText.textContent = '';
		}
	}

	function setSlotsLoading( cal, date ) {
		const ui = getSlotsUi( cal );
		if ( ui.panel ) {
			ui.panel.classList.add( 'is-active', 'is-loading' );
		}
		if ( ui.heading ) {
			ui.heading.hidden = false;
			ui.heading.textContent = ( cal.dataset.labelTimesHeading || 'Available times' ) + ' — ' + core.formatDateLong( date );
		}
		if ( ui.hint ) {
			ui.hint.hidden = false;
			ui.hint.textContent = 'Loading times…';
			ui.hint.classList.remove( 'is-emphasis' );
		}
		if ( ui.list ) {
			ui.list.hidden = true;
			ui.list.innerHTML = '';
		}
		if ( ui.selection ) {
			ui.selection.hidden = true;
		}
	}

	function setSlotsPickTime( cal, date ) {
		const ui = getSlotsUi( cal );
		if ( ui.panel ) {
			ui.panel.classList.add( 'is-active' );
			ui.panel.classList.remove( 'is-loading' );
		}
		if ( ui.heading ) {
			ui.heading.hidden = false;
			ui.heading.textContent = ( cal.dataset.labelTimesHeading || 'Available times' ) + ' — ' + core.formatDateLong( date );
		}
		if ( ui.hint ) {
			ui.hint.hidden = true;
			ui.hint.classList.remove( 'is-emphasis' );
		}
		if ( ui.list ) {
			ui.list.hidden = false;
		}
	}

	function updateSlotStatusLabels( cal, selectedButton ) {
		const availLabel = cal.dataset.labelAvailable || 'Available';
		const selectedLabel = cal.dataset.labelSlotSelected || 'Selected';
		cal.querySelectorAll( '.cbfs-appointment-calendar__slot:not(.is-booked)' ).forEach( function ( el ) {
			const status = el.querySelector( '.cbfs-appointment-calendar__slot-status' );
			if ( ! status ) {
				return;
			}
			const isSelected = el === selectedButton;
			status.textContent = isSelected ? selectedLabel : availLabel;
			status.classList.toggle( 'is-selected', isSelected );
		} );
	}

	function buildSlotButton( cal, slot, availLabel, bookedLabel ) {
		const btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'cbfs-appointment-calendar__slot';
		btn.setAttribute( 'role', 'radio' );
		btn.setAttribute( 'aria-checked', 'false' );

		const time = document.createElement( 'span' );
		time.className = 'cbfs-appointment-calendar__slot-time';
		time.textContent = slot.time_label || '';

		const meta = document.createElement( 'span' );
		meta.className = 'cbfs-appointment-calendar__slot-meta';
		const metaText = formatSlotMeta( slot );
		meta.textContent = metaText;
		meta.hidden = ! metaText;

		const status = document.createElement( 'span' );
		status.className = 'cbfs-appointment-calendar__slot-status';

		if ( ! slot.selectable ) {
			btn.classList.add( 'is-booked' );
			btn.disabled = true;
			status.textContent = bookedLabel;
			btn.setAttribute( 'aria-label', ( slot.time_label || '' ) + ', ' + bookedLabel );
		} else {
			status.textContent = availLabel;
			btn.setAttribute( 'aria-label', ( slot.time_label || '' ) + ( metaText ? ', ' + metaText : '' ) + ', ' + availLabel );
		}

		btn.appendChild( time );
		btn.appendChild( meta );
		btn.appendChild( status );
		btn.addEventListener( 'click', function () {
			selectSlot( cal, slot, btn );
		} );
		return btn;
	}

	function clearAllDaySelection( cal ) {
		cal.querySelectorAll( '.cbfs-appointment-calendar__day' ).forEach( function ( el ) {
			core.setDayPressed( el, false );
		} );
	}

	function clearSlotSelection( cal ) {
		const dateInput = cal.querySelector( '[name="class_date"]' );
		const ruleInput = cal.querySelector( '[name="slot_rule_id"]' );
		if ( dateInput ) {
			dateInput.value = '';
		}
		if ( ruleInput ) {
			ruleInput.value = '';
			ruleInput.dataset.remaining = '0';
			ruleInput.dataset.unitPrice = '';
		}
		cal.querySelectorAll( '.cbfs-appointment-calendar__slot.is-selected' ).forEach( function ( el ) {
			el.classList.remove( 'is-selected' );
			el.setAttribute( 'aria-checked', 'false' );
		} );
		updateSlotStatusLabels( cal, null );
		const ui = getSlotsUi( cal );
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

	function clearSelection( cal ) {
		clearAllDaySelection( cal );
		clearSlotSelection( cal );
		const ui = getSlotsUi( cal );
		if ( ui.hint ) {
			ui.hint.classList.add( 'is-emphasis' );
		}
	}

	function selectSlot( cal, slot, button ) {
		const dateInput = cal.querySelector( '[name="class_date"]' );
		const ruleInput = cal.querySelector( '[name="slot_rule_id"]' );
		if ( ! dateInput || ! ruleInput || ! slot.selectable ) {
			return;
		}
		cal.querySelectorAll( '.cbfs-appointment-calendar__slot.is-selected' ).forEach( function ( el ) {
			el.classList.remove( 'is-selected' );
			el.setAttribute( 'aria-checked', 'false' );
		} );
		button.classList.add( 'is-selected' );
		button.setAttribute( 'aria-checked', 'true' );
		updateSlotStatusLabels( cal, button );

		const ui = getSlotsUi( cal );
		if ( ui.hint ) {
			ui.hint.hidden = true;
			ui.hint.classList.remove( 'is-emphasis' );
		}
		if ( ui.selection && ui.selectionText ) {
			const summary = core.formatDateLong( slot.date ) + ' at ' + ( slot.time_label || '' );
			ui.selectionText.textContent = summary;
			ui.selection.hidden = false;
		}

		dateInput.value = slot.date;
		ruleInput.value = slot.rule_id;
		ruleInput.dataset.remaining = String( slot.remaining ?? slot.capacity ?? 1 );
		ruleInput.dataset.unitPrice = String( slot.price_gbp || '' );
		dateInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		ruleInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		const form = cal.closest( '.cbfs-form__form' );
		if ( form && window.CLASBOWPRO_updateSeats ) {
			window.CLASBOWPRO_updateSeats( form );
		}
	}

	function readCalendarPreset( cal ) {
		const form = cal.closest( '.cbfs-form__form' );
		const presetDate = ( form && form.dataset.cbfsPresetDate ) || cal.dataset.cbfsPresetDate || '';
		const presetSlot = ( form && form.dataset.cbfsPresetSlotRuleId ) || cal.dataset.cbfsPresetSlotRuleId || '';
		return {
			presetDate: presetDate,
			presetSlot: presetSlot,
		};
	}

	async function renderSlots( cal, date, state ) {
		const ui = getSlotsUi( cal );
		if ( ! ui.list ) {
			return;
		}

		setSlotsLoading( cal, date );
		ui.list.innerHTML = '';

		const classId = cal.dataset.cbfsClassId;
		const availLabel = cal.dataset.labelAvailable || 'Available';
		const bookedLabel = cal.dataset.labelBooked || 'Booked';
		let autoSelect = null;

		try {
			const data = await fetchSlots( classId, date );
			const slots = data.slots || [];
			const available = slots.filter( function ( slot ) {
				return slot.selectable;
			} );

			if ( ! slots.length ) {
				setSlotsPickTime( cal, date );
				if ( ui.hint ) {
					ui.hint.textContent = 'No slots on this day.';
					ui.hint.classList.remove( 'is-emphasis' );
				}
				ui.list.hidden = true;
				clearSlotSelection( cal );
				return;
			}

			setSlotsPickTime( cal, date );
			core.fitCalendarHeight( cal );

			slots.forEach( function ( slot ) {
				const li = document.createElement( 'li' );
				const btn = buildSlotButton( cal, slot, availLabel, bookedLabel );
				li.appendChild( btn );
				ui.list.appendChild( li );
				if ( slot.selectable && available.length === 1 ) {
					autoSelect = { slot: slot, btn: btn };
				}
			} );

			const section = cal.querySelector( '[data-cbfs-slots-section]' );
			if ( section && typeof section.scrollIntoView === 'function' ) {
				section.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
			}

			if ( autoSelect ) {
				selectSlot( cal, autoSelect.slot, autoSelect.btn );
			}

			const presetSlot = ( state && state.presetSlot ) || readCalendarPreset( cal ).presetSlot;
			if ( presetSlot ) {
				slots.forEach( function ( slot, index ) {
					if ( ! slot.selectable || String( slot.rule_id || '' ) !== presetSlot ) {
						return;
					}
					const li = ui.list.children[ index ];
					const btn = li ? li.querySelector( '.cbfs-appointment-calendar__slot' ) : null;
					if ( btn ) {
						selectSlot( cal, slot, btn );
					}
				} );
			}
		} catch ( e ) {
			if ( ui.panel ) {
				ui.panel.classList.remove( 'is-loading' );
			}
			if ( ui.hint ) {
				ui.hint.hidden = false;
				ui.hint.textContent = 'Could not load slots. Please try again.';
				ui.hint.classList.remove( 'is-emphasis' );
			}
			if ( ui.list ) {
				ui.list.hidden = true;
			}
		}
		core.fitCalendarHeight( cal );
	}

	function appendDayDots( btn, info ) {
		const total = parseInt( info.slot_count, 10 ) || 0;
		const avail = parseInt( info.available_count, 10 ) || 0;
		if ( total <= 0 ) {
			return;
		}
		const wrap = document.createElement( 'span' );
		wrap.className = 'cbfs-appointment-calendar__day-dots';
		if ( total > 4 ) {
			wrap.classList.add( 'cbfs-appointment-calendar__day-dots--many' );
		}
		wrap.setAttribute( 'aria-hidden', 'true' );
		let i;
		for ( i = 0; i < avail; i++ ) {
			const dot = document.createElement( 'span' );
			dot.className = 'cbfs-appointment-calendar__day-dot is-available';
			wrap.appendChild( dot );
		}
		for ( i = 0; i < total - avail; i++ ) {
			const dot = document.createElement( 'span' );
			dot.className = 'cbfs-appointment-calendar__day-dot is-booked-only';
			wrap.appendChild( dot );
		}
		btn.appendChild( wrap );
	}

	function dayAriaLabel( date, info ) {
		let label = core.formatDateLong( date );
		if ( info && info.slot_count ) {
			const n = parseInt( info.slot_count, 10 );
			label += ', ' + n + ( n === 1 ? ' slot' : ' slots' );
			const avail = parseInt( info.available_count, 10 ) || 0;
			if ( avail > 0 && avail < n ) {
				label += ', ' + avail + ' available';
			} else if ( avail === 0 ) {
				label += ', fully booked';
			}
		}
		return label;
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

			if ( info && info.has_any ) {
				btn.classList.add( info.has_available ? 'has-available' : 'has-booked-only' );
				appendDayDots( btn, info );
			} else {
				btn.classList.add( 'is-disabled' );
				btn.disabled = true;
			}
			btn.setAttribute( 'aria-label', dayAriaLabel( date, info ) );
			core.markTodayOnDay( cal, btn, date );
			core.setDayPressed( btn, state.selectedDate === date );
			btn.addEventListener( 'click', function () {
				clearAllDaySelection( cal );
				core.setDayPressed( btn, true );
				state.selectedDate = date;
				clearSlotSelection( cal );
				renderSlots( cal, date, state );
			} );
			fragment.appendChild( btn );
		}

		grid.replaceChildren( fragment );
		core.fitCalendarHeight( cal );
	}

	function initCalendar( cal ) {
		if ( cal.dataset.cbfsCalendarInit === '1' ) {
			return;
		}
		cal.dataset.cbfsCalendarInit = '1';

		const preset = readCalendarPreset( cal );
		const presetDate = preset.presetDate;
		const presetSlot = preset.presetSlot;

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
			selectedDate: '',
			presetDate: presetDate,
			presetSlot: presetSlot,
		};

		const syncNav = core.bindMonthNav( cal, state, {
			onMonthChange: function () {
				clearSelection( cal );
				setSlotsIdle( cal );
			},
			onLoadMonth: function ( calEl, st ) {
				return core.loadMonth( calEl, st, 'appointment', fetchMonth, renderGrid );
			},
		} );

		core.loadMonth( cal, state, 'appointment', fetchMonth, renderGrid ).then( function () {
			syncNav();
			applyPresetDay( cal, state );
		} );
	}

	async function applyPresetDay( cal, state ) {
		if ( ! state.presetDate || state.presetApplied ) {
			return;
		}
		const btn = cal.querySelector( '.cbfs-appointment-calendar__day[data-date="' + state.presetDate + '"]' );
		if ( ! btn || btn.disabled ) {
			return;
		}
		state.presetApplied = true;
		clearAllDaySelection( cal );
		core.setDayPressed( btn, true );
		state.selectedDate = state.presetDate;
		clearSlotSelection( cal );
		await renderSlots( cal, state.presetDate, state );
	}

	function initCalendars( root ) {
		const scope = root || document;
		scope.querySelectorAll( '[data-cbfs-appointment-calendar]' ).forEach( initCalendar );
		core.bindResize();
		scope.querySelectorAll( '[data-cbfs-appointment-calendar]' ).forEach( core.fitCalendarHeight );
	}

	function init() {
		initCalendars( document );
	}

	window.CLASBOWPRO_initAppointmentCalendars = initCalendars;

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
