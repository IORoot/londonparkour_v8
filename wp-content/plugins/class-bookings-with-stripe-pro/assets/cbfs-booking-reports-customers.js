/**
 * Class Bookings with Stripe — customer report table (sort, search, CSV).
 */
(function () {
	'use strict';

	var cfg = window.clasbproCustomerReport;
	if ( ! cfg || ! Array.isArray( cfg.rows ) ) {
		return;
	}

	var rows = cfg.rows.slice();
	var sortKey = 'total_spent';
	var sortDir = -1;
	var searchInput = null;
	var tbody = null;

	function rowSortValue( row, key ) {
		if ( key === 'customer_since' ) {
			return String( row.first_booking || '' );
		}
		if ( key === 'last_booking' ) {
			return String( row.last_booking || '' );
		}
		if ( key === 'tenure' ) {
			return String( row.first_booking || '' );
		}
		return row[ key ];
	}

	function compare( a, b, key ) {
		var av = rowSortValue( a, key );
		var bv = rowSortValue( b, key );
		if ( key === 'total_spent' || key === 'sessions' ) {
			av = Number( av ) || 0;
			bv = Number( bv ) || 0;
		} else {
			av = String( av || '' ).toLowerCase();
			bv = String( bv || '' ).toLowerCase();
		}
		if ( av < bv ) {
			return -1;
		}
		if ( av > bv ) {
			return 1;
		}
		return 0;
	}

	function filteredRows() {
		var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
		if ( ! q ) {
			return rows.slice();
		}
		return rows.filter( function ( row ) {
			return (
				String( row.email || '' ).toLowerCase().indexOf( q ) !== -1 ||
				String( row.name || '' ).toLowerCase().indexOf( q ) !== -1
			);
		} );
	}

	function sortedRows( list ) {
		var sorted = list.slice();
		sorted.sort( function ( a, b ) {
			return compare( a, b, sortKey ) * sortDir;
		} );
		return sorted;
	}

	function render() {
		if ( ! tbody ) {
			return;
		}
		var display = sortedRows( filteredRows() );
		tbody.innerHTML = '';

		if ( ! display.length ) {
			var empty = document.createElement( 'tr' );
			var td = document.createElement( 'td' );
			td.colSpan = 8;
			td.textContent = cfg.i18n.noResults || 'No customers match your search.';
			empty.appendChild( td );
			tbody.appendChild( empty );
			return;
		}

		display.forEach( function ( row ) {
			var tr = document.createElement( 'tr' );
			var bookingsUrl = bookingsUrlForRow( row );
			tr.innerHTML =
				'<td data-sort="email">' + escapeHtml( row.email ) + '</td>' +
				'<td data-sort="name">' + escapeHtml( row.name ) + '</td>' +
				'<td data-sort="sessions">' + escapeHtml( String( row.sessions ) ) + '</td>' +
				'<td data-sort="total_spent">' + escapeHtml( row.total_spent_display ) + '</td>' +
				'<td data-sort="customer_since">' + escapeHtml( row.customer_since_display ) + '</td>' +
				'<td data-sort="tenure">' + escapeHtml( row.tenure ) + '</td>' +
				'<td data-sort="last_booking">' + escapeHtml( row.last_booking_display ) + '</td>' +
				'<td class="clasbpro-reports-customers-table__actions"></td>';

			var emailCell = tr.querySelector( 'td[data-sort="email"]' );
			if ( emailCell && row.email ) {
				emailCell.innerHTML = '';
				var link = document.createElement( 'a' );
				link.href = 'mailto:' + row.email;
				link.textContent = row.email;
				emailCell.appendChild( link );
			}

			var actionsCell = tr.querySelector( '.clasbpro-reports-customers-table__actions' );
			if ( actionsCell && bookingsUrl ) {
				var bookingsLink = document.createElement( 'a' );
				bookingsLink.className = 'clasbpro-reports-customers-view-link';
				bookingsLink.href = bookingsUrl;
				bookingsLink.textContent = cfg.i18n.viewBookings || 'View bookings';
				actionsCell.appendChild( bookingsLink );
			}

			tbody.appendChild( tr );
		} );
	}

	function bookingsUrlForRow( row ) {
		var base = cfg.bookingsListUrl || '';
		var email = String( row.email || '' ).trim();
		if ( ! base || ! email ) {
			return '';
		}
		var join = base.indexOf( '?' ) === -1 ? '?' : '&';
		return base + join + 'clasbpro_customer_email=' + encodeURIComponent( email );
	}

	function escapeHtml( str ) {
		return String( str )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' );
	}

	function csvEscape( value ) {
		var s = String( value == null ? '' : value );
		if ( s.indexOf( '"' ) !== -1 || s.indexOf( ',' ) !== -1 || s.indexOf( '\n' ) !== -1 ) {
			return '"' + s.replace( /"/g, '""' ) + '"';
		}
		return s;
	}

	function downloadCsv() {
		var headers = cfg.csvHeaders || [];
		var keys = [ 'email', 'name', 'sessions', 'total_spent_display', 'customer_since_display', 'tenure', 'last_booking_display' ];
		var lines = [ headers.map( csvEscape ).join( ',' ) ];

		sortedRows( rows ).forEach( function ( row ) {
			lines.push(
				keys
					.map( function ( key ) {
						return csvEscape( row[ key ] );
					} )
					.join( ',' )
			);
		} );

		var blob = new Blob( [ lines.join( '\n' ) ], { type: 'text/csv;charset=utf-8;' } );
		var url = URL.createObjectURL( blob );
		var link = document.createElement( 'a' );
		link.href = url;
		link.download = cfg.csvFilename || 'customers.csv';
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
		URL.revokeObjectURL( url );
	}

	function updateSortIndicators() {
		document.querySelectorAll( '.clasbpro-reports-customers-table th[data-sort-key]' ).forEach( function ( th ) {
			var key = th.getAttribute( 'data-sort-key' );
			th.classList.remove( 'is-sorted-asc', 'is-sorted-desc' );
			if ( key === sortKey ) {
				th.classList.add( sortDir === 1 ? 'is-sorted-asc' : 'is-sorted-desc' );
			}
		} );
	}

	function init() {
		tbody = document.getElementById( 'clasbpro-reports-customers-body' );
		searchInput = document.getElementById( 'clasbpro-reports-customers-search' );

		if ( searchInput ) {
			searchInput.addEventListener( 'input', render );
		}

		document.querySelectorAll( '.clasbpro-reports-customers-table th[data-sort-key]' ).forEach( function ( th ) {
			th.addEventListener( 'click', function () {
				var key = th.getAttribute( 'data-sort-key' );
				if ( ! key ) {
					return;
				}
				if ( sortKey === key ) {
					sortDir = sortDir * -1;
				} else {
					sortKey = key;
					sortDir = key === 'email' || key === 'name' ? 1 : -1;
				}
				updateSortIndicators();
				render();
			} );
		} );

		var exportBtn = document.getElementById( 'clasbpro-reports-customers-csv' );
		if ( exportBtn ) {
			exportBtn.addEventListener( 'click', downloadCsv );
		}

		updateSortIndicators();
		render();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
})();
