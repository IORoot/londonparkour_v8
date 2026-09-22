( function () {
	'use strict';

	const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

	function generateCode() {
		let code = 'MC-';
		for ( let i = 0; i < 8; i++ ) {
			code += alphabet.charAt( Math.floor( Math.random() * alphabet.length ) );
		}
		return code;
	}

	document.addEventListener( 'click', function ( ev ) {
		const btn = ev.target && ev.target.closest ? ev.target.closest( '[data-clasbpro-generate-code]' ) : null;
		if ( ! btn ) {
			return;
		}
		ev.preventDefault();
		const wrap = document.querySelector( '.acf-field[data-key="field_clasbpro_mc_code"]' );
		const input = wrap ? wrap.querySelector( 'input' ) : null;
		if ( ! input || input.disabled ) {
			return;
		}
		input.value = generateCode();
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	} );
} )();
