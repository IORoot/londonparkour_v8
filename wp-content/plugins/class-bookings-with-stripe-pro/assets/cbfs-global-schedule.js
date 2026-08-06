( function () {
	'use strict';

	const cfg = window.CLASBOWPRO || {};
	const buildRestUrl = window.CLASBOWPRO_buildRestUrl || function ( endpoint, params ) {
		const base = ( cfg.rest_url || '' ).replace( /\/?$/, '/' );
		const path = String( endpoint || '' ).replace( /^\//, '' );
		const search = new URLSearchParams();
		if ( params ) {
			Object.keys( params ).forEach( function ( key ) {
				if ( params[ key ] !== undefined && params[ key ] !== null && params[ key ] !== '' ) {
					search.set( key, String( params[ key ] ) );
				}
			} );
		}
		const qs = search.toString();
		return base + path + ( qs ? ( base.indexOf( '?' ) >= 0 ? '&' : '?' ) + qs : '' );
	};

	const PX_PER_MIN = 2.5;
	const SLOT_MINUTES = 30;
	const EMPTY_SLOT_MINUTES = 60;
	const EMPTY_SLOT_GAP = 8;
	const DAY_LABELS = [ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ];
	const WEEKDAY_NAMES = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
	const MONTH_NAMES = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
	const MONTH_NAMES_SHORT = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
	const MOBILE_MQ = window.matchMedia( '(max-width: 767px)' );
	const TABLET_MQ = window.matchMedia( '(min-width: 768px) and (max-width: 1023px)' );

	const ICON_APPOINTMENT = '<svg class="cbfs-schedule__event-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3.1-6 7-6s7 2.5 7 6"/></svg>';
	const ICON_CLASS = '<svg class="cbfs-schedule__event-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path d="M6.5 8.5 10 5l3.5 3.5"/><path d="M10 5v11"/><path d="M4 20h16"/></svg>';
	const ICON_PEOPLE = '<svg class="cbfs-schedule__event-meta-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><circle cx="9" cy="8" r="2.5"/><circle cx="16.5" cy="9.5" r="2"/><path d="M4.5 18c0-2.5 2-4.5 4.5-4.5S13.5 15.5 13.5 18"/><path d="M13.5 14.5c1.8.3 3 1.8 3 3.5"/></svg>';

	function defaultEventIcon( event ) {
		return event.is_appointments ? ICON_APPOINTMENT : ICON_CLASS;
	}

	function normalizeCalendarSvg( svg ) {
		if ( ! svg ) {
			return;
		}
		svg.setAttribute( 'width', '100%' );
		svg.setAttribute( 'height', '100%' );
		svg.setAttribute( 'preserveAspectRatio', 'xMidYMid meet' );
		svg.style.display = 'block';

		const applyViewBox = function () {
			if ( svg.getAttribute( 'viewBox' ) || svg.getAttribute( 'viewbox' ) ) {
				return;
			}
			try {
				const box = svg.getBBox();
				if ( box.width > 0 && box.height > 0 ) {
					svg.setAttribute( 'viewBox', box.x + ' ' + box.y + ' ' + box.width + ' ' + box.height );
				}
			} catch ( e ) {
				// getBBox can fail before the SVG is painted.
			}
		};

		applyViewBox();
		if ( ! svg.getAttribute( 'viewBox' ) && ! svg.getAttribute( 'viewbox' ) ) {
			requestAnimationFrame( applyViewBox );
		}
	}

	function renderEventIcon( iconWrap, event ) {
		if ( event.icon ) {
			iconWrap.innerHTML = event.icon;
			const svg = iconWrap.querySelector( 'svg' );
			if ( svg ) {
				svg.classList.add( 'cbfs-schedule__event-icon' );
				if ( ! svg.getAttribute( 'aria-hidden' ) ) {
					svg.setAttribute( 'aria-hidden', 'true' );
				}
				normalizeCalendarSvg( svg );
			}
			return;
		}
		iconWrap.innerHTML = defaultEventIcon( event );
	}

	function appendEventIcon( card, event, imageWrap ) {
		const iconWrap = document.createElement( 'span' );
		iconWrap.className = 'cbfs-schedule__event-icon-wrap';
		if ( imageWrap ) {
			imageWrap.appendChild( iconWrap );
		} else {
			card.appendChild( iconWrap );
		}
		renderEventIcon( iconWrap, event );
	}

	function localTodayYmd() {
		const now = new Date();
		return now.getFullYear() + '-' + String( now.getMonth() + 1 ).padStart( 2, '0' ) + '-' + String( now.getDate() ).padStart( 2, '0' );
	}

	function formatDayHead( index, dayNumber ) {
		return DAY_LABELS[ index ] + ', ' + dayNumber;
	}

	function spotsLabel( root, remaining, capacity ) {
		const template = root.dataset.labelSpots || '%1$s/%2$s spots left';
		return template.replace( '%1$s', String( remaining ) ).replace( '%2$s', String( capacity ) );
	}

	function parseYmd( ymd ) {
		const parts = String( ymd || '' ).split( '-' );
		return {
			y: parseInt( parts[0], 10 ) || 0,
			m: parseInt( parts[1], 10 ) || 1,
			d: parseInt( parts[2], 10 ) || 1,
		};
	}

	function addDays( ymd, days ) {
		const p = parseYmd( ymd );
		const dt = new Date( Date.UTC( p.y, p.m - 1, p.d + days ) );
		return dt.toISOString().slice( 0, 10 );
	}

	function mondayOfWeek( ymd ) {
		const p = parseYmd( ymd );
		const dt = new Date( Date.UTC( p.y, p.m - 1, p.d ) );
		const day = dt.getUTCDay();
		const offset = day === 0 ? -6 : 1 - day;
		return addDays( ymd, offset );
	}

	function timeToMinutes( hhmm ) {
		const parts = String( hhmm || '00:00' ).split( ':' );
		return ( parseInt( parts[0], 10 ) || 0 ) * 60 + ( parseInt( parts[1], 10 ) || 0 );
	}

	function formatTime( hhmm ) {
		const parts = String( hhmm || '00:00' ).split( ':' );
		const h = parseInt( parts[0], 10 ) || 0;
		const m = parseInt( parts[1], 10 ) || 0;
		const suffix = h >= 12 ? 'pm' : 'am';
		const hour12 = ( ( h + 11 ) % 12 ) + 1;
		return hour12 + ':' + String( m ).padStart( 2, '0' ) + suffix;
	}

	function minutesLabel( minutes ) {
		const h = Math.floor( minutes / 60 );
		const m = minutes % 60;
		return String( h ).padStart( 2, '0' ) + ':' + String( m ).padStart( 2, '0' );
	}

	function dateFromYmd( ymd ) {
		const p = parseYmd( ymd );
		return new Date( p.y, p.m - 1, p.d );
	}

	function formatFullDate( ymd ) {
		const dt = dateFromYmd( ymd );
		return WEEKDAY_NAMES[ dt.getDay() ] + ' ' + dt.getDate() + ' ' + MONTH_NAMES[ dt.getMonth() ];
	}

	function formatAgendaDayHeading( root, ymd ) {
		const today = localTodayYmd();
		const tomorrow = addDays( today, 1 );
		const full = formatFullDate( ymd );
		if ( ymd === today ) {
			return ( root.dataset.labelToday || 'Today' ) + ' · ' + full;
		}
		if ( ymd === tomorrow ) {
			return ( root.dataset.labelTomorrow || 'Tomorrow' ) + ' · ' + full;
		}
		return full;
	}

	function formatWeekRange( weekMonday ) {
		const weekEnd = addDays( weekMonday, 6 );
		const start = parseYmd( weekMonday );
		const end = parseYmd( weekEnd );
		const currentYear = new Date().getFullYear();
		const sm = MONTH_NAMES_SHORT[ start.m - 1 ];
		const em = MONTH_NAMES_SHORT[ end.m - 1 ];
		let label;

		if ( start.y !== end.y ) {
			label = start.d + ' ' + sm + ' ' + start.y + '–' + end.d + ' ' + em + ' ' + end.y;
		} else if ( start.m === end.m ) {
			label = start.d + '–' + end.d + ' ' + em;
			if ( end.y !== currentYear ) {
				label += ' ' + end.y;
			}
		} else {
			label = start.d + ' ' + sm + '–' + end.d + ' ' + em;
			if ( end.y !== currentYear ) {
				label += ' ' + end.y;
			}
		}
		return label;
	}

	function formatMonthYearLabel( weekMonday ) {
		const weekEnd = addDays( weekMonday, 6 );
		const start = parseYmd( weekMonday );
		const end = parseYmd( weekEnd );

		if ( start.m === end.m && start.y === end.y ) {
			return MONTH_NAMES[ start.m - 1 ] + ' ' + end.y;
		}
		if ( start.y === end.y ) {
			return MONTH_NAMES[ start.m - 1 ] + ' – ' + MONTH_NAMES[ end.m - 1 ] + ' ' + end.y;
		}
		return MONTH_NAMES[ start.m - 1 ] + ' ' + start.y + ' – ' + MONTH_NAMES[ end.m - 1 ] + ' ' + end.y;
	}

	function compareEventsByTime( a, b ) {
		const dateCmp = String( a.date ).localeCompare( String( b.date ) );
		if ( dateCmp !== 0 ) {
			return dateCmp;
		}
		return timeToMinutes( a.start_time ) - timeToMinutes( b.start_time );
	}

	function isMobileView() {
		return MOBILE_MQ.matches;
	}

	function isTabletView() {
		return TABLET_MQ.matches;
	}

	function buildDayHeadElement( index, dayNumber, isToday ) {
		const head = document.createElement( 'div' );
		head.className = 'cbfs-schedule__day-head';
		if ( isToday ) {
			head.classList.add( 'is-today' );
		}
		if ( isTabletView() ) {
			head.classList.add( 'is-stacked' );
			const weekday = document.createElement( 'span' );
			weekday.className = 'cbfs-schedule__day-head-weekday';
			weekday.textContent = DAY_LABELS[ index ];
			const dateNum = document.createElement( 'span' );
			dateNum.className = 'cbfs-schedule__day-head-date';
			dateNum.textContent = String( dayNumber );
			head.appendChild( weekday );
			head.appendChild( dateNum );
		} else {
			head.textContent = formatDayHead( index, dayNumber );
		}
		return head;
	}

	function groupEventsForStacking( events ) {
		const buckets = {};
		events.forEach( function ( event ) {
			const key = event.date + '|' + event.start_time;
			if ( ! buckets[ key ] ) {
				buckets[ key ] = [];
			}
			buckets[ key ].push( event );
		} );
		return buckets;
	}

	function eventOverlapsMinutes( event, slotStart, slotDuration ) {
		const evStart = timeToMinutes( event.start_time );
		const evDuration = Math.max( 15, parseInt( event.duration_minutes, 10 ) || 45 );
		const evEnd = evStart + evDuration;
		const slotEnd = slotStart + slotDuration;
		return evStart < slotEnd && evEnd > slotStart;
	}

	function appendEmptySlots( body, dayEvents, rangeStart, rangeEnd ) {
		for ( let m = rangeStart; m < rangeEnd; m += EMPTY_SLOT_MINUTES ) {
			const occupied = dayEvents.some( function ( event ) {
				return eventOverlapsMinutes( event, m, EMPTY_SLOT_MINUTES );
			} );
			if ( occupied ) {
				continue;
			}
			const slot = document.createElement( 'div' );
			slot.className = 'cbfs-schedule__slot-empty';
			const slotTop = ( m - rangeStart ) * PX_PER_MIN + ( EMPTY_SLOT_GAP / 2 );
			const slotHeight = ( EMPTY_SLOT_MINUTES * PX_PER_MIN ) - EMPTY_SLOT_GAP;
			slot.style.top = slotTop + 'px';
			slot.style.height = Math.max( slotHeight, 12 ) + 'px';
			slot.setAttribute( 'aria-hidden', 'true' );
			body.appendChild( slot );
		}
	}

	function initSchedule( root ) {
		const state = {
			week: root.dataset.cbfsWeek || mondayOfWeek( new Date().toISOString().slice( 0, 10 ) ),
			classIds: ( root.dataset.cbfsClassIds || '' ).split( ',' ).filter( Boolean ).map( Number ),
			weeksAhead: parseInt( root.dataset.cbfsWeeksAhead || '8', 10 ) || 8,
			filterClassId: 'all',
			classes: [],
			events: [],
			range: { start_minutes: 8 * 60, end_minutes: 18 * 60 },
			minWeek: mondayOfWeek( new Date().toISOString().slice( 0, 10 ) ),
			maxWeek: '',
			loading: false,
		};
		state.maxWeek = addDays( state.minWeek, ( state.weeksAhead - 1 ) * 7 );

		const els = {
			prev: root.querySelectorAll( '[data-cbfs-schedule-prev]' ),
			next: root.querySelectorAll( '[data-cbfs-schedule-next]' ),
			filters: root.querySelector( '[data-cbfs-schedule-filters]' ),
			wrap: root.querySelector( '[data-cbfs-schedule-grid-wrap]' ),
			status: root.querySelector( '[data-cbfs-schedule-status]' ),
			weekLabel: root.querySelector( '[data-cbfs-schedule-week-label]' ),
			agenda: root.querySelector( '[data-cbfs-schedule-agenda]' ),
			calendarFrame: root.querySelector( '[data-cbfs-schedule-calendar-frame]' ),
			dayHeads: root.querySelector( '[data-cbfs-schedule-day-heads]' ),
			timeAxis: root.querySelector( '[data-cbfs-schedule-time-axis]' ),
			days: root.querySelector( '[data-cbfs-schedule-days]' ),
			panel: root.querySelector( '[data-cbfs-schedule-panel]' ),
			panelTitle: root.querySelector( '[data-cbfs-schedule-panel-title]' ),
			panelBody: root.querySelector( '[data-cbfs-schedule-panel-body]' ),
			panelLoading: root.querySelector( '[data-cbfs-schedule-panel-loading]' ),
		};

		function setLoading( on ) {
			state.loading = on;
			root.setAttribute( 'aria-busy', on ? 'true' : 'false' );
			if ( els.wrap ) {
				els.wrap.classList.toggle( 'is-loading', on );
			}
			if ( els.status && on ) {
				els.status.textContent = root.dataset.labelLoading || 'Loading schedule…';
			}
		}

		function updateNavButtons() {
			els.prev.forEach( function ( btn ) {
				btn.disabled = state.week <= state.minWeek;
			} );
			els.next.forEach( function ( btn ) {
				btn.disabled = state.week >= state.maxWeek;
			} );
		}

		function updateWeekLabel() {
			if ( ! els.weekLabel ) {
				return;
			}
			els.weekLabel.textContent = isMobileView()
				? formatWeekRange( state.week )
				: formatMonthYearLabel( state.week );
		}

		function syncLayoutMode() {
			root.classList.toggle( 'cbfs-schedule--mobile', isMobileView() );
			root.classList.toggle( 'cbfs-schedule--tablet', isTabletView() );
		}

		function activeFilterClassName() {
			if ( state.filterClassId === 'all' ) {
				return '';
			}
			const id = parseInt( state.filterClassId, 10 );
			const match = state.classes.find( function ( cls ) {
				return cls.id === id;
			} );
			return match ? ( match.name || '' ) : '';
		}

		function emptyScheduleMessage() {
			const className = activeFilterClassName();
			if ( className ) {
				const template = root.dataset.labelEmptyFiltered || 'No %s classes this week.';
				return template.replace( '%s', className );
			}
			return root.dataset.labelEmpty || 'No sessions this week.';
		}

		function renderFilters() {
			if ( ! els.filters ) {
				return;
			}
			els.filters.querySelectorAll( '[data-cbfs-schedule-filter]:not([data-cbfs-schedule-filter="all"])' ).forEach( function ( btn ) {
				btn.remove();
			} );
			state.classes.forEach( function ( cls ) {
				const btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.className = 'cbfs-schedule__filter';
				btn.dataset.cbfsScheduleFilter = String( cls.id );
				btn.setAttribute( 'role', 'tab' );
				btn.setAttribute( 'aria-selected', 'false' );
				btn.style.setProperty( '--cbfs-schedule-color', cls.color || '#dbeafe' );
				btn.textContent = cls.name;
				els.filters.appendChild( btn );
			} );
			els.filters.querySelectorAll( '.cbfs-schedule__filter' ).forEach( function ( el ) {
				const id = el.dataset.cbfsScheduleFilter || 'all';
				const active = id === state.filterClassId;
				el.classList.toggle( 'is-active', active );
				el.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
		}

		function filteredEvents() {
			if ( state.filterClassId === 'all' ) {
				return state.events.slice();
			}
			const id = parseInt( state.filterClassId, 10 );
			return state.events.filter( function ( event ) {
				return event.class_id === id;
			} );
		}

		function buildEventCard( event, stackIndex, stackTotal, agendaMode ) {
			const card = document.createElement( 'button' );
			card.type = 'button';
			card.className = 'cbfs-schedule__event';
			if ( agendaMode ) {
				card.classList.add( 'cbfs-schedule__event--agenda' );
			}
			card.style.setProperty( '--cbfs-schedule-color', event.color || '#e8f0fe' );

			const disabled = ! event.selectable && ! event.external;
			if ( event.cancelled ) {
				card.classList.add( 'is-cancelled' );
			}
			if ( event.full ) {
				card.classList.add( 'is-full' );
			}
			if ( disabled ) {
				card.classList.add( 'is-disabled' );
				card.disabled = true;
			}

			if ( stackTotal > 1 && ! agendaMode ) {
				card.style.setProperty( '--cbfs-stack-index', String( stackIndex ) );
				card.style.setProperty( '--cbfs-stack-total', String( stackTotal ) );
				card.classList.add( 'is-stacked' );
			}

			let imageWrap = null;

			if ( event.show_image && event.image_url ) {
				card.classList.add( 'has-image' );
				imageWrap = document.createElement( 'span' );
				imageWrap.className = 'cbfs-schedule__event-image-wrap';
				const image = document.createElement( 'img' );
				image.className = 'cbfs-schedule__event-image';
				image.src = event.image_url;
				image.alt = '';
				image.setAttribute( 'aria-hidden', 'true' );
				image.loading = 'lazy';
				image.decoding = 'async';
				imageWrap.appendChild( image );
				card.appendChild( imageWrap );
			}

			appendEventIcon( card, event, imageWrap );

			const body = document.createElement( 'span' );
			body.className = 'cbfs-schedule__event-body';

			const title = document.createElement( 'span' );
			title.className = 'cbfs-schedule__event-title';
			title.textContent = event.class_name || '';

			const time = document.createElement( 'span' );
			time.className = 'cbfs-schedule__event-time';
			time.textContent = formatTime( event.start_time );

			body.appendChild( title );
			body.appendChild( time );

			if ( event.label ) {
				const label = document.createElement( 'span' );
				label.className = 'cbfs-schedule__event-label';
				label.textContent = event.label;
				body.appendChild( label );
			}

			if ( event.location ) {
				const location = document.createElement( 'span' );
				location.className = 'cbfs-schedule__event-location';
				location.textContent = event.location;
				body.appendChild( location );
			}

			const meta = document.createElement( 'span' );
			meta.className = 'cbfs-schedule__event-meta';

			if ( event.cancelled ) {
				meta.innerHTML = ICON_PEOPLE + '<span class="cbfs-schedule__event-meta-text">' + ( root.dataset.labelCancelled || 'Cancelled' ) + '</span>';
			} else if ( event.full ) {
				meta.innerHTML = ICON_PEOPLE + '<span class="cbfs-schedule__event-meta-text">' + ( root.dataset.labelClassFull || 'Class full' ) + '</span>';
			} else if ( event.show_seats && event.capacity > 0 ) {
				meta.innerHTML = ICON_PEOPLE + '<span class="cbfs-schedule__event-meta-text">' + spotsLabel( root, event.remaining, event.capacity ) + '</span>';
			} else if ( ! event.is_appointments && ! disabled ) {
				meta.innerHTML = ICON_PEOPLE + '<span class="cbfs-schedule__event-meta-text">' + ( root.dataset.labelBook || 'Book' ) + '</span>';
			}

			card.appendChild( body );
			if ( meta.childNodes.length ) {
				card.appendChild( meta );
			}

			if ( ! disabled || event.external ) {
				card.addEventListener( 'click', function () {
					handleEventClick( event );
				} );
			}

			return card;
		}

		function positionEvent( card, event, rangeStart, minHeight ) {
			const start = timeToMinutes( event.start_time );
			const duration = Math.max( 15, parseInt( event.duration_minutes, 10 ) || 45 );
			const top = ( start - rangeStart ) * PX_PER_MIN;
			const height = duration * PX_PER_MIN;
			card.style.top = top + 'px';
			card.style.height = Math.max( height, minHeight || 72 ) + 'px';
		}

		function renderGrid() {
			if ( ! els.days || ! els.timeAxis || ! els.dayHeads ) {
				return;
			}

			const events = filteredEvents();
			const weekDays = state.weekDays || [];
			const rangeStart = state.range.start_minutes;
			const rangeEnd = state.range.end_minutes;
			const totalMinutes = Math.max( SLOT_MINUTES, rangeEnd - rangeStart );
			const gridHeight = totalMinutes * PX_PER_MIN;
			const todayYmd = localTodayYmd();
			const minEventHeight = isTabletView() ? 64 : 72;

			els.timeAxis.innerHTML = '';
			els.dayHeads.innerHTML = '';
			els.days.innerHTML = '';

			for ( let m = rangeStart; m < rangeEnd; m += SLOT_MINUTES ) {
				const label = document.createElement( 'div' );
				label.className = 'cbfs-schedule__time-label';
				if ( m % 60 === 0 ) {
					label.classList.add( 'is-hour' );
					label.textContent = minutesLabel( m );
				}
				label.style.height = ( SLOT_MINUTES * PX_PER_MIN ) + 'px';
				els.timeAxis.appendChild( label );
			}

			const buckets = groupEventsForStacking( events );

			weekDays.forEach( function ( date, index ) {
				const p = parseYmd( date );
				const isToday = date === todayYmd;

				const head = buildDayHeadElement( index, p.d, isToday );
				els.dayHeads.appendChild( head );

				const col = document.createElement( 'div' );
				col.className = 'cbfs-schedule__day-col';
				if ( isToday ) {
					col.classList.add( 'is-today' );
				}
				col.dataset.date = date;

				const body = document.createElement( 'div' );
				body.className = 'cbfs-schedule__day-body';
				body.style.height = gridHeight + 'px';

				const dayEvents = events.filter( function ( event ) {
					return event.date === date;
				} );
				appendEmptySlots( body, dayEvents, rangeStart, rangeEnd );

				Object.keys( buckets ).forEach( function ( key ) {
					if ( key.indexOf( date + '|' ) !== 0 ) {
						return;
					}
					const stack = buckets[ key ];
					stack.forEach( function ( event, stackIndex ) {
						const card = buildEventCard( event, stackIndex, stack.length );
						positionEvent( card, event, rangeStart, minEventHeight );
						if ( stack.length > 1 ) {
							const widthPct = 100 / stack.length;
							card.style.width = 'calc(' + widthPct + '% - 10px)';
							card.style.left = 'calc(' + ( stackIndex * widthPct ) + '% + 5px)';
						}
						body.appendChild( card );
					} );
				} );

				col.appendChild( body );
				els.days.appendChild( col );
			} );

			if ( els.status ) {
				els.status.textContent = events.length ? '' : emptyScheduleMessage();
			}
			updateWeekLabel();
		}

		function renderAgenda() {
			if ( ! els.agenda ) {
				return;
			}

			const events = filteredEvents().slice().sort( compareEventsByTime );
			const weekDays = state.weekDays || [];

			els.agenda.innerHTML = '';
			updateWeekLabel();

			if ( ! events.length ) {
				const empty = document.createElement( 'p' );
				empty.className = 'cbfs-schedule__agenda-empty';
				empty.textContent = emptyScheduleMessage();
				els.agenda.appendChild( empty );
				if ( els.status ) {
					els.status.textContent = '';
				}
				return;
			}

			weekDays.forEach( function ( date ) {
				const dayEvents = events.filter( function ( event ) {
					return event.date === date;
				} );
				if ( ! dayEvents.length ) {
					return;
				}

				const section = document.createElement( 'section' );
				section.className = 'cbfs-schedule__agenda-day';
				section.setAttribute( 'role', 'listitem' );

				const heading = document.createElement( 'h3' );
				heading.className = 'cbfs-schedule__agenda-day-title';
				heading.textContent = formatAgendaDayHeading( root, date );
				section.appendChild( heading );

				const list = document.createElement( 'div' );
				list.className = 'cbfs-schedule__agenda-events';

				dayEvents.forEach( function ( event ) {
					list.appendChild( buildEventCard( event, 0, 1, true ) );
				} );

				section.appendChild( list );
				els.agenda.appendChild( section );
			} );

			if ( els.status ) {
				els.status.textContent = '';
			}
		}

		function renderSchedule() {
			syncLayoutMode();
			updateWeekLabel();
			if ( isMobileView() ) {
				renderAgenda();
				return;
			}
			renderGrid();
		}

		function closePanel() {
			if ( ! els.panel ) {
				return;
			}
			els.panel.hidden = true;
			document.body.classList.remove( 'cbfs-schedule-panel-open' );
			if ( els.panelBody ) {
				els.panelBody.querySelectorAll( '.cbfs-form' ).forEach( function ( node ) {
					node.remove();
				} );
			}
		}

		function openPanel( title ) {
			if ( ! els.panel ) {
				return;
			}
			els.panel.hidden = false;
			document.body.classList.add( 'cbfs-schedule-panel-open' );
			if ( els.panelTitle ) {
				els.panelTitle.textContent = title || '';
			}
		}

		async function loadBookingForm( event ) {
			if ( ! els.panelBody ) {
				return;
			}
			els.panelBody.querySelectorAll( '.cbfs-form' ).forEach( function ( node ) {
				node.remove();
			} );
			if ( els.panelLoading ) {
				els.panelLoading.hidden = false;
			}

			const params = {
				class_id: event.class_id,
				preset_date: event.date,
			};
			if ( event.slot_rule_id ) {
				params.preset_slot_rule_id = event.slot_rule_id;
			}

			try {
				const res = await fetch( buildRestUrl( 'schedule-booking-form', params ) );
				const data = await res.json();
				if ( ! res.ok || ! data.html ) {
					throw new Error( 'form' );
				}
				if ( els.panelLoading ) {
					els.panelLoading.hidden = true;
				}
				const wrap = document.createElement( 'div' );
				wrap.innerHTML = data.html;
				const formRoot = wrap.firstElementChild;
				if ( formRoot ) {
					els.panelBody.appendChild( formRoot );
				}
				const form = els.panelBody.querySelector( '.cbfs-form__form' );
				if ( form && event.date ) {
					form.dataset.cbfsPresetDate = event.date;
					if ( event.slot_rule_id ) {
						form.dataset.cbfsPresetSlotRuleId = event.slot_rule_id;
					} else {
						delete form.dataset.cbfsPresetSlotRuleId;
					}
				}
				if ( window.CLASBOWPRO_initBookingForms ) {
					window.CLASBOWPRO_initBookingForms( els.panelBody );
				}
				if ( window.CLASBOWPRO_initAppointmentCalendars ) {
					window.CLASBOWPRO_initAppointmentCalendars( els.panelBody );
				}
				if ( window.CLASBOWPRO_initClassDateCalendars ) {
					window.CLASBOWPRO_initClassDateCalendars( els.panelBody );
				}
			} catch ( e ) {
				if ( els.panelLoading ) {
					els.panelLoading.hidden = true;
				}
				const err = document.createElement( 'p' );
				err.className = 'cbfs-schedule__panel-error';
				err.textContent = 'Could not load booking form.';
				els.panelBody.appendChild( err );
			}
		}

		function handleEventClick( event ) {
			if ( event.external && event.external_url ) {
				window.open( event.external_url, '_blank', 'noopener,noreferrer' );
				return;
			}
			if ( ! event.selectable ) {
				return;
			}
			openPanel( event.class_name || '' );
			loadBookingForm( event );
		}

		async function loadWeek() {
			setLoading( true );
			updateNavButtons();
			try {
				const res = await fetch( buildRestUrl( 'schedule-calendar', {
					week: state.week,
					class_ids: state.classIds.join( ',' ),
				} ) );
				const data = await res.json();
				if ( ! res.ok ) {
					throw new Error( 'week' );
				}
				state.classes = data.classes || [];
				state.events = data.events || [];
				state.range = data.range || state.range;
				state.weekDays = ( data.week && data.week.days ) ? data.week.days : [];
				if ( data.weeks_ahead ) {
					state.weeksAhead = data.weeks_ahead;
					state.maxWeek = addDays( state.minWeek, ( state.weeksAhead - 1 ) * 7 );
				}
				root.dataset.cbfsWeek = state.week;
				renderFilters();
				renderSchedule();
			} catch ( e ) {
				if ( els.status ) {
					els.status.textContent = 'Could not load schedule.';
				}
			} finally {
				setLoading( false );
				updateNavButtons();
			}
		}

		if ( els.filters ) {
			els.filters.addEventListener( 'click', function ( ev ) {
				const btn = ev.target && ev.target.closest ? ev.target.closest( '[data-cbfs-schedule-filter]' ) : null;
				if ( ! btn || ! els.filters.contains( btn ) ) {
					return;
				}
				state.filterClassId = btn.dataset.cbfsScheduleFilter || 'all';
				els.filters.querySelectorAll( '.cbfs-schedule__filter' ).forEach( function ( el ) {
					const active = ( el.dataset.cbfsScheduleFilter || 'all' ) === state.filterClassId;
					el.classList.toggle( 'is-active', active );
					el.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				} );
				renderSchedule();
			} );
		}

		els.prev.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const prev = addDays( state.week, -7 );
				if ( prev < state.minWeek ) {
					return;
				}
				state.week = prev;
				loadWeek();
			} );
		} );
		els.next.forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				const next = addDays( state.week, 7 );
				if ( next > state.maxWeek ) {
					return;
				}
				state.week = next;
				loadWeek();
			} );
		} );
		root.querySelectorAll( '[data-cbfs-schedule-close]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', closePanel );
		} );
		document.addEventListener( 'keydown', function ( ev ) {
			if ( ev.key === 'Escape' && els.panel && ! els.panel.hidden ) {
				closePanel();
			}
		} );

		let resizeTimer = null;
		function onViewportChange() {
			window.clearTimeout( resizeTimer );
			resizeTimer = window.setTimeout( function () {
				renderSchedule();
			}, 120 );
		}
		if ( typeof MOBILE_MQ.addEventListener === 'function' ) {
			MOBILE_MQ.addEventListener( 'change', onViewportChange );
			TABLET_MQ.addEventListener( 'change', onViewportChange );
		} else if ( typeof MOBILE_MQ.addListener === 'function' ) {
			MOBILE_MQ.addListener( onViewportChange );
			TABLET_MQ.addListener( onViewportChange );
		}
		window.addEventListener( 'resize', onViewportChange );

		loadWeek();
	}

	function init() {
		document.querySelectorAll( '[data-cbfs-global-schedule]' ).forEach( initSchedule );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
