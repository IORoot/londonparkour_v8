( function () {
	'use strict';

	const MONTHS = [
		'January', 'February', 'March', 'April', 'May', 'June',
		'July', 'August', 'September', 'October', 'November', 'December',
	];

	const GRID_HEIGHT_MIN = 256;
	const GRID_HEIGHT_MAX = 352;
	const VIEWPORT_BOTTOM_PAD = 16;
	const monthCache = Object.create( null );

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function ymd( y, m, d ) {
		return y + '-' + pad( m ) + '-' + pad( d );
	}

	function parseYmd( str ) {
		const p = ( str || '' ).split( '-' );
		if ( p.length !== 3 ) {
			return null;
		}
		return { y: parseInt( p[0], 10 ), m: parseInt( p[1], 10 ), d: parseInt( p[2], 10 ) };
	}

	function monthStartOffset( year, month ) {
		const jsDay = new Date( year, month - 1, 1 ).getDay();
		return jsDay === 0 ? 6 : jsDay - 1;
	}

	function daysInMonth( year, month ) {
		return new Date( year, month, 0 ).getDate();
	}

	function addMonths( year, month, delta ) {
		let m = month + delta;
		let y = year;
		while ( m < 1 ) {
			m += 12;
			y -= 1;
		}
		while ( m > 12 ) {
			m -= 12;
			y += 1;
		}
		return { year: y, month: m };
	}

	function buildRestUrl( endpoint, params ) {
		if ( typeof window.CLASBOWPRO_buildRestUrl === 'function' ) {
			return window.CLASBOWPRO_buildRestUrl( endpoint, params );
		}
		const cfg = window.CLASBOWPRO || {
			rest_url: window.location.origin.replace( /\/$/, '' ) + '/wp-json/clasbpro/v1/',
		};
		const base = ( cfg.rest_url || '' ).replace( /\/?$/, '/' );
		const path = String( endpoint || '' ).replace( /^\//, '' );
		const search = new URLSearchParams();
		if ( params && typeof params === 'object' ) {
			Object.keys( params ).forEach( function ( key ) {
				const val = params[ key ];
				if ( val !== undefined && val !== null && '' !== val ) {
					search.set( key, String( val ) );
				}
			} );
		}
		const qs = search.toString();
		if ( base.indexOf( 'rest_route=' ) >= 0 ) {
			return base + path + ( qs ? '&' + qs : '' );
		}
		return base + path + ( qs ? '?' + qs : '' );
	}

	function monthCacheKey( classId, year, month ) {
		return String( classId ) + ':' + year + '-' + pad( month );
	}

	function setCalendarLoading( cal, loading ) {
		cal.classList.toggle( 'is-loading', !! loading );
		cal.setAttribute( 'aria-busy', loading ? 'true' : 'false' );
	}

	function withinWindow( cal, year, month ) {
		const maxMonths = parseInt( cal.dataset.cbfsMonthsAhead || '3', 10 ) || 3;
		const now = new Date();
		const start = new Date( now.getFullYear(), now.getMonth(), 1 );
		const end = new Date( now.getFullYear(), now.getMonth() + maxMonths, 1 );
		const view = new Date( year, month - 1, 1 );
		return view >= start && view <= end;
	}

	function todayYmd( cal ) {
		const fromAttr = cal && cal.dataset ? cal.dataset.cbfsToday : '';
		if ( fromAttr && /^\d{4}-\d{2}-\d{2}$/.test( fromAttr ) ) {
			return fromAttr;
		}
		const now = new Date();
		return ymd( now.getFullYear(), now.getMonth() + 1, now.getDate() );
	}

	function markTodayOnDay( cal, btn, date ) {
		if ( ! btn || date !== todayYmd( cal ) ) {
			return;
		}
		btn.classList.add( 'is-today' );
		btn.setAttribute( 'aria-current', 'date' );
		const label = btn.getAttribute( 'aria-label' );
		if ( label && label.toLowerCase().indexOf( 'today' ) < 0 ) {
			btn.setAttribute( 'aria-label', label + ', today' );
		}
	}

	function formatDateLong( dateStr ) {
		const p = parseYmd( dateStr );
		if ( ! p ) {
			return dateStr;
		}
		const d = new Date( p.y, p.m - 1, p.d );
		return d.toLocaleDateString( undefined, {
			weekday: 'long',
			day: 'numeric',
			month: 'long',
		} );
	}

	function setDayPressed( dayBtn, pressed ) {
		if ( ! dayBtn ) {
			return;
		}
		dayBtn.classList.toggle( 'is-selected', !! pressed );
		dayBtn.setAttribute( 'aria-pressed', pressed ? 'true' : 'false' );
	}

	function fitCalendarHeight( cal, selector ) {
		const form = cal.closest( '.cbfs-form' );
		const grid = cal.querySelector( selector || '[data-cbfs-cal-grid]' );
		if ( ! form || ! grid ) {
			return;
		}

		const viewport = window.visualViewport;
		const viewportBottom = ( viewport ? viewport.height + viewport.offsetTop : window.innerHeight ) - VIEWPORT_BOTTOM_PAD;
		const budget = viewportBottom - form.getBoundingClientRect().top;
		const formHeight = form.getBoundingClientRect().height;
		const gridHeight = grid.getBoundingClientRect().height;

		if ( formHeight <= budget || gridHeight <= GRID_HEIGHT_MIN ) {
			cal.style.removeProperty( '--cbfs-cal-grid-height' );
			return;
		}

		const excess = formHeight - budget;
		const target = Math.max(
			GRID_HEIGHT_MIN,
			Math.min( GRID_HEIGHT_MAX, Math.round( gridHeight - excess ) )
		);
		cal.style.setProperty( '--cbfs-cal-grid-height', target + 'px' );
	}

	function renderSkeletonGrid( cal, state, dayClassName ) {
		const grid = cal.querySelector( '[data-cbfs-cal-grid]' );
		const title = cal.querySelector( '[data-cbfs-cal-title]' );
		if ( ! grid || ! title ) {
			return;
		}

		title.textContent = MONTHS[ state.month - 1 ] + ' ' + state.year;
		grid.replaceChildren();

		const offset = monthStartOffset( state.year, state.month );
		const totalDays = daysInMonth( state.year, state.month );
		const fragment = document.createDocumentFragment();
		const dayClass = dayClassName || 'cbfs-appointment-calendar__day';

		for ( let i = 0; i < offset; i++ ) {
			const cell = document.createElement( 'span' );
			cell.className = dayClass + ' cbfs-appointment-calendar__day--empty';
			cell.setAttribute( 'aria-hidden', 'true' );
			fragment.appendChild( cell );
		}

		for ( let d = 1; d <= totalDays; d++ ) {
			const btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = dayClass + ' is-skeleton';
			btn.disabled = true;
			btn.setAttribute( 'tabindex', '-1' );
			btn.setAttribute( 'aria-hidden', 'true' );

			const num = document.createElement( 'span' );
			num.className = 'cbfs-appointment-calendar__day-num';
			num.textContent = String( d );
			btn.appendChild( num );
			fragment.appendChild( btn );
		}

		grid.appendChild( fragment );
		fitCalendarHeight( cal );
	}

	function bindResize( calSelector ) {
		if ( window.CLASBOWPRO_calendarResizeBound ) {
			return;
		}
		window.CLASBOWPRO_calendarResizeBound = true;
		let resizeTimer = 0;
		const selector = calSelector || '[data-cbfs-appointment-calendar], [data-cbfs-class-date-calendar]';
		function scheduleFitAllCalendars() {
			window.clearTimeout( resizeTimer );
			resizeTimer = window.setTimeout( function () {
				document.querySelectorAll( selector ).forEach( function ( cal ) {
					fitCalendarHeight( cal );
				} );
			}, 80 );
		}
		window.addEventListener( 'resize', scheduleFitAllCalendars );
		if ( window.visualViewport ) {
			window.visualViewport.addEventListener( 'resize', scheduleFitAllCalendars );
		}
	}

	/**
	 * @param {HTMLElement} cal
	 * @param {{ year: number, month: number, days?: object, selectedDate?: string }} state
	 * @param {string} cacheNamespace
	 * @param {function} fetchMonth
	 * @param {function} renderGrid
	 */
	async function loadMonth( cal, state, cacheNamespace, fetchMonth, renderGrid ) {
		const classId = cal.dataset.cbfsClassId;
		const cacheKey = cacheNamespace + ':' + monthCacheKey( classId, state.year, state.month );
		const cachedDays = monthCache[ cacheKey ];

		if ( cachedDays ) {
			state.days = cachedDays;
			setCalendarLoading( cal, false );
			renderGrid( cal, state );
			return;
		}

		setCalendarLoading( cal, true );
		renderSkeletonGrid( cal, state );

		try {
			const data = await fetchMonth( classId, state.year, state.month );
			state.days = data.days || {};
			state.monthMeta = data;
			monthCache[ cacheKey ] = state.days;
			setCalendarLoading( cal, false );
			renderGrid( cal, state );
		} catch ( e ) {
			setCalendarLoading( cal, false );
			const grid = cal.querySelector( '[data-cbfs-cal-grid]' );
			if ( grid ) {
				grid.innerHTML = '<p class="cbfs-appointment-calendar__error">Could not load calendar.</p>';
			}
		}
	}

	/**
	 * @param {HTMLElement} cal
	 * @param {{ year: number, month: number, days?: object, selectedDate?: string }} state
	 * @param {{ onMonthChange?: function, onLoadMonth?: function }} hooks
	 */
	function bindMonthNav( cal, state, hooks ) {
		const prev = cal.querySelector( '.cbfs-appointment-calendar__prev' );
		const next = cal.querySelector( '.cbfs-appointment-calendar__next' );

		function syncNav() {
			const prevMonth = addMonths( state.year, state.month, -1 );
			const nextMonth = addMonths( state.year, state.month, 1 );
			if ( prev ) {
				prev.disabled = ! withinWindow( cal, prevMonth.year, prevMonth.month );
			}
			if ( next ) {
				next.disabled = ! withinWindow( cal, nextMonth.year, nextMonth.month );
			}
		}

		function changeMonth( delta ) {
			const m = addMonths( state.year, state.month, delta );
			if ( ! withinWindow( cal, m.year, m.month ) ) {
				return;
			}
			state.year = m.year;
			state.month = m.month;
			state.selectedDate = '';
			if ( hooks.onMonthChange ) {
				hooks.onMonthChange( cal, state );
			}
			if ( hooks.onLoadMonth ) {
				hooks.onLoadMonth( cal, state ).then( syncNav );
			} else {
				syncNav();
			}
		}

		if ( prev ) {
			prev.addEventListener( 'click', function () {
				changeMonth( -1 );
			} );
		}
		if ( next ) {
			next.addEventListener( 'click', function () {
				changeMonth( 1 );
			} );
		}

		syncNav();
		return syncNav;
	}

	window.CLASBOWPRO_CalendarCore = {
		MONTHS: MONTHS,
		pad: pad,
		ymd: ymd,
		parseYmd: parseYmd,
		monthStartOffset: monthStartOffset,
		daysInMonth: daysInMonth,
		addMonths: addMonths,
		buildRestUrl: buildRestUrl,
		monthCache: monthCache,
		monthCacheKey: monthCacheKey,
		setCalendarLoading: setCalendarLoading,
		withinWindow: withinWindow,
		formatDateLong: formatDateLong,
		todayYmd: todayYmd,
		markTodayOnDay: markTodayOnDay,
		setDayPressed: setDayPressed,
		fitCalendarHeight: fitCalendarHeight,
		renderSkeletonGrid: renderSkeletonGrid,
		bindResize: bindResize,
		loadMonth: loadMonth,
		bindMonthNav: bindMonthNav,
	};
} )();
