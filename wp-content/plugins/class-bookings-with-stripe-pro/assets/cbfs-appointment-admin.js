( function () {
	'use strict';

	function closest( el, sel ) {
		return el && el.closest ? el.closest( sel ) : null;
	}

	function updateRowType( row ) {
		const type = row.querySelector( '.clasbpro-slot-rule__type' );
		const val = type ? type.value : 'recurring';
		row.dataset.type = val;
	}

	function bindRow( row ) {
		const typeSelect = row.querySelector( '.clasbpro-slot-rule__type' );
		if ( typeSelect ) {
			typeSelect.addEventListener( 'change', function () {
				updateRowType( row );
			} );
		}
		const removeBtn = row.querySelector( '.clasbpro-slot-rule__remove' );
		if ( removeBtn ) {
			removeBtn.addEventListener( 'click', function () {
				const container = closest( row, '.clasbpro-slot-rules__rows' );
				const rows = container ? container.querySelectorAll( '.clasbpro-slot-rule' ) : [];
				if ( rows.length <= 1 ) {
					row.querySelectorAll( 'input:not([type="hidden"]), select, textarea' ).forEach( function ( el ) {
						if ( el.type === 'number' ) {
							el.value = el.name.indexOf( 'duration' ) >= 0 ? '60' : '';
						} else if ( el.tagName === 'SELECT' ) {
							el.selectedIndex = 0;
						} else {
							el.value = '';
						}
					} );
					updateRowType( row );
					return;
				}
				row.remove();
			} );
		}
		updateRowType( row );
	}

	function reindexRows( container ) {
		const rows = container.querySelectorAll( '.clasbpro-slot-rule' );
		rows.forEach( function ( row, index ) {
			row.querySelectorAll( '[name^="clasbpro_slot_rules["]' ).forEach( function ( el ) {
				el.name = el.name.replace( /clasbpro_slot_rules\[[^\]]+\]/, 'clasbpro_slot_rules[' + index + ']' );
			} );
		} );
	}

	function init() {
		const root = document.getElementById( 'clasbpro-slot-rules' );
		if ( ! root ) {
			return;
		}

		const rowsContainer = root.querySelector( '.clasbpro-slot-rules__rows' );
		const template = document.getElementById( 'clasbpro-slot-rule-template' );
		const addBtn = root.querySelector( '.clasbpro-slot-rules__add' );

		if ( rowsContainer ) {
			rowsContainer.querySelectorAll( '.clasbpro-slot-rule' ).forEach( bindRow );
		}

		if ( addBtn && template && rowsContainer ) {
			addBtn.addEventListener( 'click', function () {
				const index = rowsContainer.querySelectorAll( '.clasbpro-slot-rule' ).length;
				const html = template.innerHTML.replace( /__INDEX__/g, String( index ) );
				const wrap = document.createElement( 'div' );
				wrap.innerHTML = html.trim();
				const row = wrap.firstElementChild;
				if ( row ) {
					rowsContainer.appendChild( row );
					bindRow( row );
					reindexRows( rowsContainer );
				}
			} );
		}

		initPartyPrices();
	}

	function formatPartyTotal( seats, rate, symbol, step ) {
		if ( isNaN( rate ) ) {
			return '—';
		}
		const total = seats * rate;
		const decimals = step === '1' ? 0 : 2;
		return symbol + total.toFixed( decimals );
	}

	function updatePartyRow( row, symbol, step ) {
		const input = row.querySelector( '.clasbpro-party-prices__input' );
		const total = row.querySelector( '.clasbpro-party-prices__total' );
		if ( ! input || ! total ) {
			return;
		}
		const seats = parseInt( row.getAttribute( 'data-seats' ), 10 ) || 0;
		const raw = String( input.value || '' ).trim();
		if ( raw === '' ) {
			total.textContent = '—';
			return;
		}
		total.textContent = formatPartyTotal( seats, parseFloat( raw ), symbol, step );
	}

	function bindPartyRow( row, symbol, step ) {
		const input = row.querySelector( '.clasbpro-party-prices__input' );
		if ( input ) {
			input.addEventListener( 'input', function () {
				updatePartyRow( row, symbol, step );
			} );
		}
		updatePartyRow( row, symbol, step );
	}

	function buildPartyRow( template, seats, symbol, step ) {
		const html = template.innerHTML.replace( /__SEATS__/g, String( seats ) );
		const wrap = document.createElement( 'div' );
		wrap.innerHTML = html.trim();
		const row = wrap.firstElementChild;
		if ( ! row ) {
			return null;
		}
		row.setAttribute( 'data-seats', String( seats ) );
		bindPartyRow( row, symbol, step );
		return row;
	}

	function capacityInput() {
		return document.querySelector( '.acf-field[data-key="field_clasbpro_capacity"] input' );
	}

	function syncPartyCapacity( root ) {
		const rowsWrap = root.querySelector( '.clasbpro-party-prices__rows' );
		const template = document.getElementById( 'clasbpro-party-price-row-template' );
		const capEl = capacityInput();
		if ( ! rowsWrap || ! template || ! capEl ) {
			return;
		}
		const symbol = root.getAttribute( 'data-symbol' ) || '£';
		const step = root.getAttribute( 'data-step' ) || '0.01';
		let capacity = parseInt( capEl.value, 10 ) || 1;
		if ( capacity < 1 ) {
			capacity = 1;
		}
		const existing = {};
		rowsWrap.querySelectorAll( '.clasbpro-party-prices__row' ).forEach( function ( row ) {
			existing[ row.getAttribute( 'data-seats' ) ] = row;
		} );
		for ( let seats = 1; seats <= capacity; seats++ ) {
			if ( existing[ String( seats ) ] ) {
				continue;
			}
			const row = buildPartyRow( template, seats, symbol, step );
			if ( row ) {
				rowsWrap.appendChild( row );
			}
		}
		rowsWrap.querySelectorAll( '.clasbpro-party-prices__row' ).forEach( function ( row ) {
			const seats = parseInt( row.getAttribute( 'data-seats' ), 10 ) || 0;
			if ( seats > capacity ) {
				row.remove();
			}
		} );
	}

	function initPartyPrices() {
		const root = document.getElementById( 'clasbpro-party-prices' );
		if ( ! root ) {
			return;
		}
		const symbol = root.getAttribute( 'data-symbol' ) || '£';
		const step = root.getAttribute( 'data-step' ) || '0.01';
		root.querySelectorAll( '.clasbpro-party-prices__row' ).forEach( function ( row ) {
			bindPartyRow( row, symbol, step );
		} );
		const capEl = capacityInput();
		if ( capEl ) {
			capEl.addEventListener( 'input', function () {
				syncPartyCapacity( root );
			} );
			capEl.addEventListener( 'change', function () {
				syncPartyCapacity( root );
			} );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
