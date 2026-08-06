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

	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
