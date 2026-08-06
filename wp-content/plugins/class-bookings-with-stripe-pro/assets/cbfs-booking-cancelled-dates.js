/**
 * Class edit: append upcoming dates to the cancelled-dates ACF textarea.
 */
( function () {
	'use strict';

	if ( window.__clasbproCancelledDateHelperBound ) {
		return;
	}
	window.__clasbproCancelledDateHelperBound = true;

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '.clasbpro-add-cancelled-date' );
		if ( ! trigger ) {
			return;
		}
		event.preventDefault();

		var fieldKey = trigger.getAttribute( 'data-field-key' );
		var date = trigger.getAttribute( 'data-date' );
		if ( ! fieldKey || ! date ) {
			return;
		}

		var input = document.querySelector(
			'.acf-field[data-key="' + fieldKey + '"] textarea'
		);
		if ( ! input ) {
			return;
		}

		var lines = ( input.value || '' )
			.split( /\r?\n/ )
			.map( function ( line ) {
				return line.trim();
			} )
			.filter( Boolean );
		if ( lines.indexOf( date ) !== -1 ) {
			return;
		}
		lines.push( date );
		input.value = lines.join( '\n' );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
} )();
