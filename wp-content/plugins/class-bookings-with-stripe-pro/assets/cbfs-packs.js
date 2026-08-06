/* Coupons purchase list */
( function () {
	'use strict';

	const cfg = window.CLASBOWPRO || {
		rest_url: '',
		nonce: '',
	};

	function buildRestUrl( endpoint ) {
		if ( typeof window.CLASBOWPRO_buildRestUrl === 'function' ) {
			return window.CLASBOWPRO_buildRestUrl( endpoint );
		}
		const base = ( cfg.rest_url || '' ).replace( /\/?$/, '/' );
		return base + String( endpoint || '' ).replace( /^\//, '' );
	}

	function showError( form, message ) {
		const err = form.querySelector( '.cbfs-packs__error' );
		if ( ! err ) return;
		err.hidden = false;
		err.textContent = message || 'Something went wrong.';
	}

	function clearError( form ) {
		const err = form.querySelector( '.cbfs-packs__error' );
		if ( ! err ) return;
		err.hidden = true;
		err.textContent = '';
	}

	function setLoading( button, loading ) {
		if ( ! button ) return;
		button.disabled = !! loading;
		button.classList.toggle( 'is-loading', !! loading );

		let spinner = button.querySelector( '.cbfs-packs__spinner' );
		if ( loading ) {
			if ( ! spinner ) {
				spinner = document.createElement( 'span' );
				spinner.className = 'cbfs-packs__spinner';
				spinner.setAttribute( 'aria-hidden', 'true' );
				button.appendChild( spinner );
			}
		} else if ( spinner ) {
			spinner.remove();
		}
	}

	async function handleSubmit( form, ev ) {
		ev.preventDefault();
		clearError( form );
		const item = form.closest( '[data-cbfs-pack-id]' );
		const button = form.querySelector( '.cbfs-packs__button' );
		if ( ! item ) return;

		const packId = parseInt( item.dataset.cbfsPackId, 10 ) || 0;
		const name = ( form.querySelector( '[name="customer_name"]' ) || {} ).value || '';
		const email = ( form.querySelector( '[name="customer_email"]' ) || {} ).value || '';

		if ( ! name.trim() ) {
			showError( form, 'Please enter your name.' );
			return;
		}
		if ( ! email.trim() ) {
			showError( form, 'Please enter your email address.' );
			return;
		}

		setLoading( button, true );
		try {
			const res = await fetch( buildRestUrl( 'pack-checkout' ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce,
				},
				body: JSON.stringify( {
					pack_id: packId,
					customer_name: name.trim(),
					customer_email: email.trim(),
					origin_url: window.location.href,
				} ),
			} );
			const data = await res.json().catch( function () { return {}; } );
			if ( ! res.ok || data.error ) {
				showError( form, data.message || 'Could not start checkout.' );
				setLoading( button, false );
				return;
			}
			if ( data.url ) {
				window.location.href = data.url;
				return;
			}
			showError( form, 'No payment URL returned.' );
			setLoading( button, false );
		} catch ( e ) {
			showError( form, 'Network error. Please try again.' );
			setLoading( button, false );
		}
	}

	function init() {
		document.addEventListener( 'submit', function ( ev ) {
			const form = ev.target && ev.target.closest ? ev.target.closest( '.cbfs-packs__form' ) : null;
			if ( ! form ) return;
			handleSubmit( form, ev );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
