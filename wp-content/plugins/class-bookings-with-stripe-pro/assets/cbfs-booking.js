/* Class Bookings with Stripe — frontend behaviour */
( function () {
	'use strict';

	const cfg = window.CLASBOWPRO || {
		rest_url: window.location.origin.replace( /\/$/, '' ) + '/wp-json/clasbpro/v1/',
		nonce: '',
		currency: { code: 'gbp', symbol: '£', decimals: 2, position: 'before' },
	};

	const currencyFormat = cfg.currency || { symbol: '£', decimals: 2, position: 'before' };

	/**
	 * Build a plugin REST URL that works with plain (?rest_route=) and pretty (/wp-json/) permalinks.
	 *
	 * @param {string} endpoint
	 * @param {Record<string, string|number|boolean>|undefined} params
	 * @return {string}
	 */
	function buildRestUrl( endpoint, params ) {
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

	window.CLASBOWPRO_buildRestUrl = buildRestUrl;

	function $$( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	function formatPrice( amount ) {
		const n = Number( amount );
		if ( isNaN( n ) ) {
			return '';
		}
		const decimals = typeof currencyFormat.decimals === 'number' ? currencyFormat.decimals : 2;
		const formatted = n.toFixed( decimals );
		if ( currencyFormat.position === 'after' ) {
			return formatted + ' ' + currencyFormat.symbol;
		}
		return currencyFormat.symbol + formatted;
	}

	window.CLASBOWPRO_formatPrice = formatPrice;

	function showError( form, message, fieldName ) {
		const errEl = form.querySelector( '.cbfs-form__error' );
		if ( errEl ) {
			errEl.hidden = false;
			errEl.textContent = message;
		}
		$$( form, '.cbfs-form__input--error' ).forEach( function ( el ) {
			el.classList.remove( 'cbfs-form__input--error' );
		} );
		if ( fieldName ) {
			const f = form.querySelector( '[name="' + fieldName + '"]' );
			if ( f ) {
				f.classList.add( 'cbfs-form__input--error' );
				f.focus();
			}
		}
	}

	function clearError( form ) {
		const errEl = form.querySelector( '.cbfs-form__error' );
		if ( errEl ) {
			errEl.hidden = true;
			errEl.textContent = '';
		}
		$$( form, '.cbfs-form__input--error' ).forEach( function ( el ) {
			el.classList.remove( 'cbfs-form__input--error' );
		} );
	}

	function setLoading( button, on ) {
		if ( ! button ) return;
		button.classList.toggle( 'is-loading', !! on );
		button.disabled = !! on;
		button.setAttribute( 'aria-busy', on ? 'true' : 'false' );
	}

	function isAppointmentForm( form ) {
		return !! (
			form && (
				form.dataset.cbfsAppointments === '1'
				|| form.querySelector( '[data-cbfs-appointment-calendar]' )
			)
		);
	}

	function isClassDateCalendarForm( form ) {
		return !! (
			form && form.querySelector( '[data-cbfs-class-date-calendar]' )
		);
	}

	function getAppointmentRemaining( form ) {
		const ruleInput = form.querySelector( '[name="slot_rule_id"]' );
		const dateInput = form.querySelector( '[name="class_date"]' );
		const cal = form.querySelector( '[data-cbfs-appointment-calendar]' );

		if ( ruleInput && dateInput && ruleInput.value && dateInput.value ) {
			return Math.max( 0, parseInt( ruleInput.dataset.remaining || '0', 10 ) );
		}

		// Before a slot is chosen, keep party size selectable up to class capacity.
		if ( cal && cal.dataset.cbfsCapacity ) {
			return Math.max( 1, parseInt( cal.dataset.cbfsCapacity, 10 ) || 1 );
		}

		return 0;
	}

	function getSelectableDateOption( dateField ) {
		if ( ! dateField || dateField.tagName !== 'SELECT' ) {
			return null;
		}
		const selected = dateField.options[ dateField.selectedIndex ];
		if ( selected && ! selected.disabled ) {
			return selected;
		}
		const firstSelectable = Array.prototype.findIndex.call( dateField.options, function ( option ) {
			return ! option.disabled;
		} );
		if ( firstSelectable < 0 ) {
			return null;
		}
		dateField.selectedIndex = firstSelectable;
		return dateField.options[ firstSelectable ];
	}

	function getDateRemaining( dateField, form ) {
		if ( form && isAppointmentForm( form ) ) {
			return getAppointmentRemaining( form );
		}
		if ( form && isClassDateCalendarForm( form ) && ( ! dateField || ! dateField.value ) ) {
			return 0;
		}
		if ( ! dateField ) {
			return 0;
		}
		if ( dateField.tagName === 'SELECT' ) {
			const opt = getSelectableDateOption( dateField );
			return opt ? Math.max( 0, parseInt( opt.dataset.remaining || '0', 10 ) ) : 0;
		}
		return Math.max( 0, parseInt( dateField.dataset.remaining || '0', 10 ) );
	}

	function updateSeatsOptions( form ) {
		const dateField = form.querySelector( '[name="class_date"]' );
		const seatsSel = form.querySelector( '[name="seats"]' );
		const totalEl = form.querySelector( '.cbfs-form__total' );
		if ( ! dateField || ! seatsSel ) return;

		const remaining = getDateRemaining( dateField, form );
		const max = Math.max( 1, remaining );

		const previous = parseInt( seatsSel.value, 10 ) || 1;
		seatsSel.innerHTML = '';
		for ( let i = 1; i <= max; i++ ) {
			const o = document.createElement( 'option' );
			o.value = String( i );
			o.textContent = String( i );
			seatsSel.appendChild( o );
		}
		seatsSel.value = String( Math.min( previous, max ) );
		updateTotal( form );

		if ( remaining === 0 ) {
			seatsSel.disabled = true;
		} else {
			seatsSel.disabled = false;
		}
	}

	function updateTotal( form ) {
		const seatsSel = form.querySelector( '[name="seats"]' );
		const totalEl = form.querySelector( '.cbfs-form__total' );
		if ( ! seatsSel || ! totalEl ) return;
		const seats = parseInt( seatsSel.value, 10 ) || 1;
		let unit = parseFloat( totalEl.dataset.cbfsUnitPrice || '0' );
		if ( isAppointmentForm( form ) ) {
			const ruleInput = form.querySelector( '[name="slot_rule_id"]' );
			if ( ruleInput && ruleInput.dataset.unitPrice ) {
				const slotUnit = parseFloat( ruleInput.dataset.unitPrice );
				if ( ! isNaN( slotUnit ) && slotUnit >= 0 ) {
					unit = slotUnit;
				}
			}
		}
		totalEl.textContent = formatPrice( unit * seats );
	}

	function formatDateOptionLabel( d, showSeatsRemaining ) {
		if ( d.cancelled ) {
			return 'Cancelled - ' + d.label;
		}
		let text = d.label;
		if ( showSeatsRemaining ) {
			const seatsLabel = ( d.remaining === 1 ) ? '1 seat left' : ( d.remaining + ' seats left' );
			text += ' · ' + seatsLabel;
		}
		return text;
	}

	async function refreshAvailability( wrapper ) {
		const classId = wrapper.dataset.cbfsClassId;
		if ( ! classId ) return;
		try {
			const res = await fetch( buildRestUrl( 'availability', { class_id: classId } ) );
			if ( ! res.ok ) return;
			const data = await res.json();
			const form = wrapper.querySelector( '.cbfs-form__form' ) || wrapper;
			const dateField = wrapper.querySelector( '[name="class_date"]' );
			if ( ! dateField || ! ( data.dates || [] ).length ) return;

			const showSeatsRemaining = form.dataset.cbfsShowSeatsRemaining === '1';
			const isOneOff = form.dataset.cbfsOneOffDate === '1';

			if ( isOneOff && dateField.tagName !== 'SELECT' ) {
				const d = data.dates[ 0 ];
				const display = wrapper.querySelector( '[data-cbfs-date-display]' );
				dateField.value = d.date;
				dateField.dataset.remaining = String( d.remaining );
				dateField.dataset.cancelled = d.cancelled ? '1' : '0';
				if ( display ) {
					display.textContent = formatDateOptionLabel( d, showSeatsRemaining );
					display.classList.toggle( 'cbfs-form__date-fixed--cancelled', !! d.cancelled );
				}
				updateSeatsOptions( form );
				return;
			}

			if ( dateField.tagName !== 'SELECT' ) {
				return;
			}

			dateField.innerHTML = '';
			( data.dates || [] ).forEach( function ( d ) {
				const o = document.createElement( 'option' );
				o.value = d.date;
				o.dataset.remaining = String( d.remaining );
				o.dataset.cancelled = d.cancelled ? '1' : '0';
				if ( d.cancelled ) {
					o.disabled = true;
					o.className = 'cbfs-form__option--cancelled';
				}
				o.textContent = formatDateOptionLabel( d, showSeatsRemaining );
				dateField.appendChild( o );
			} );
			updateSeatsOptions( form );
		} catch ( e ) { /* ignore */ }
	}

	function getWrapperForForm( form ) {
		if ( ! form ) return null;
		return form.closest( '.cbfs-form[data-cbfs-class-id]' );
	}

	async function handleFormSubmit( form, ev ) {
		ev.preventDefault();
		clearError( form );

		const wrapper = getWrapperForForm( form );
		if ( ! wrapper ) {
			return;
		}

		const button = form.querySelector( '.cbfs-form__button' );
		const fd = new FormData( form );
		const extraFields = {};
		form.querySelectorAll( '[name^="extra_fields["]' ).forEach( function ( el ) {
			const match = el.name.match( /^extra_fields\[([^\]]+)\]$/ );
			if ( ! match ) return;
			const key = match[1];
			if ( el.type === 'checkbox' ) {
				extraFields[ key ] = el.checked ? 1 : 0;
			} else {
				extraFields[ key ] = ( el.value || '' ).toString().trim();
			}
		} );
		const slotRuleId = ( fd.get( 'slot_rule_id' ) || '' ).toString().trim();
		if ( isAppointmentForm( form ) ) {
			if ( ! fd.get( 'class_date' ) || ! slotRuleId ) {
				showError( form, 'Please choose an appointment date and time.', 'class_date' );
				return;
			}
		}
		if ( isClassDateCalendarForm( form ) && ! fd.get( 'class_date' ) ) {
			showError( form, 'Please choose a class date.', 'class_date' );
			return;
		}

		const usePack = shouldUsePack( form );
		const payload = {
			class_id: parseInt( wrapper.dataset.cbfsClassId, 10 ) || 0,
			class_date: fd.get( 'class_date' ),
			seats: usePack ? 1 : ( parseInt( fd.get( 'seats' ), 10 ) || 1 ),
			customer_name: ( fd.get( 'customer_name' ) || '' ).toString().trim(),
			customer_email: ( fd.get( 'customer_email' ) || '' ).toString().trim(),
			waiver_accepted: fd.has( 'waiver_accepted' ),
			mailchimp_opt_in: fd.has( 'mailchimp_opt_in' ),
			extra_fields: extraFields,
			origin_url: window.location.href,
			use_pack: usePack,
		};
		if ( slotRuleId ) {
			payload.slot_rule_id = slotRuleId;
		}

		if ( ! payload.customer_name ) {
			showError( form, 'Please enter your name.', 'customer_name' );
			return;
		}
		if ( ! payload.customer_email ) {
			showError( form, 'Please enter your email address.', 'customer_email' );
			return;
		}
		const missingRequiredExtra = Array.prototype.find.call(
			form.querySelectorAll( '[name^="extra_fields["][data-cbfs-required="1"]' ),
			function ( el ) {
				return ( el.type === 'checkbox' ) ? !el.checked : !( ( el.value || '' ).toString().trim() );
			}
		);
		if ( missingRequiredExtra ) {
			showError( form, 'Please complete all required fields.', missingRequiredExtra.name );
			return;
		}
		const waiverInput = form.querySelector( '[name="waiver_accepted"]' );
		if ( waiverInput && ! payload.waiver_accepted ) {
			showError( form, 'Please accept the waiver before continuing to payment.', 'waiver_accepted' );
			return;
		}

		setLoading( button, true );

		try {
			const res = await fetch( buildRestUrl( 'checkout' ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce,
				},
				body: JSON.stringify( payload ),
			} );

			const data = await res.json().catch( function () { return {}; } );

			if ( ! res.ok || data.error ) {
				const message = data.message || 'Something went wrong. Please try again.';
				showError( form, message, data.field || null );
				setLoading( button, false );

				if ( data.reason === 'capacity_full' || data.reason === 'date_invalid' || data.reason === 'class_inactive' ) {
					refreshAvailability( wrapper );
				}
				return;
			}

			if ( data.url ) {
				window.location.href = data.url;
				return;
			}

			showError( form, 'No payment URL returned. Please try again.' );
			setLoading( button, false );
		} catch ( e ) {
			showError( form, 'Network error. Please check your connection and try again.' );
			setLoading( button, false );
		}
	}

	function attachStatusPolling() {
		document.querySelectorAll( '.cbfs-status[data-cbfs-session], .cbfs-status[data-cbfs-purchase]' ).forEach( function ( el ) {
			const sessionId = el.dataset.cbfsSession || '';
			const statusToken = el.dataset.cbfsToken || '';
			const purchaseId = el.dataset.cbfsPurchase || '';
			const kind = el.dataset.cbfsKind || 'booking';
			if ( ! el.classList.contains( 'cbfs-status--pending' ) ) return;
			if ( kind === 'coupon' ) {
				if ( ! sessionId && ! purchaseId ) return;
			} else if ( ! sessionId || ! statusToken ) {
				return;
			}

			let attempts = 0;
			const max = 90; // ~3+ minutes with adaptive interval
			const tick = async function () {
				attempts++;
				try {
					const params = {
						_t: String( Date.now() ),
					};
					if ( sessionId ) {
						params.session = sessionId;
					}
					if ( statusToken ) {
						params.token = statusToken;
					}
					if ( purchaseId ) {
						params.purchase = purchaseId;
					}
					const endpoint = kind === 'coupon' ? 'pack-purchase-status' : 'booking-status';
					const url = buildRestUrl( endpoint, params );
					const res = await fetch( url, {
						credentials: 'same-origin',
						cache: 'no-store',
						headers: {
							'X-WP-Nonce': cfg.nonce,
							'Cache-Control': 'no-cache',
						},
					} );
					if ( res.ok ) {
						const data = await res.json();
						if ( data.status === 'paid' ) {
							window.location.reload();
							return;
						}
					}
				} catch ( e ) { /* ignore */ }
				if ( attempts < max ) {
					const wait = attempts < 10 ? 2000 : ( attempts < 30 ? 3000 : 5000 );
					setTimeout( tick, wait );
				} else {
					const text = el.querySelector( '.cbfs-status__pending-text' );
					if ( text ) {
						text.textContent = 'Still confirming with Stripe — please refresh this page in a moment.';
					}
				}
			};
			setTimeout( tick, 2000 );
		} );
	}

	function attachWaiverRichLabels() {
		document.querySelectorAll( '[data-cbfs-waiver-group]' ).forEach( function ( group ) {
			const input = group.querySelector( '[name="waiver_accepted"]' );
			const labelArea = group.querySelector( '[data-cbfs-waiver-label]' );
			if ( ! input || ! labelArea ) {
				return;
			}
			labelArea.addEventListener( 'click', function ( ev ) {
				if ( ev.target.closest && ev.target.closest( 'a' ) ) {
					return;
				}
				ev.preventDefault();
				input.checked = ! input.checked;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		} );
	}

	function applyPresetDate( form ) {
		if ( ! form ) {
			return;
		}
		const presetDate = form.dataset.cbfsPresetDate || '';
		if ( ! presetDate ) {
			return;
		}
		const dateField = form.querySelector( '[name="class_date"]' );
		if ( dateField && dateField.tagName === 'SELECT' ) {
			const options = Array.prototype.slice.call( dateField.options );
			const match = options.find( function ( opt ) {
				return opt.value === presetDate && ! opt.disabled;
			} );
			if ( match ) {
				dateField.value = presetDate;
				dateField.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				updateSeatsOptions( form );
				updateTotal( form );
			}
		}
	}

	function shouldUsePack( form ) {
		const choice = form.querySelector( '[data-cbfs-pack-choice-pack]' );
		return !!( choice && choice.checked && ! choice.disabled );
	}

	const PACK_RESTORE_KEY = 'clasbpro_pack_restore';

	function savePackRestoreToken( token ) {
		if ( ! token || ! window.localStorage ) {
			return;
		}
		try {
			window.localStorage.setItem( PACK_RESTORE_KEY, String( token ) );
		} catch ( e ) { /* ignore quota / private mode */ }
	}

	function readPackRestoreToken() {
		if ( ! window.localStorage ) {
			return '';
		}
		try {
			return String( window.localStorage.getItem( PACK_RESTORE_KEY ) || '' );
		} catch ( e ) {
			return '';
		}
	}

	function clearPackRestoreToken() {
		if ( ! window.localStorage ) {
			return;
		}
		try {
			window.localStorage.removeItem( PACK_RESTORE_KEY );
		} catch ( e ) { /* ignore */ }
	}

	function rememberPackStatus( form, status ) {
		if ( form && status && status.recognised ) {
			form._cbfsPackStatus = status;
			if ( status.restore_token ) {
				savePackRestoreToken( status.restore_token );
			}
		}
	}

	function getRememberedPackStatus( form ) {
		return ( form && form._cbfsPackStatus && form._cbfsPackStatus.recognised )
			? form._cbfsPackStatus
			: null;
	}

	function applyPackUiState( form, status ) {
		const panel = form.querySelector( '[data-cbfs-pack-panel]' );
		if ( ! panel ) {
			return;
		}
		panel.hidden = false;
		panel.dataset.cbfsPackMode = '';
		rememberPackStatus( form, status );
		const statusEl = panel.querySelector( '[data-cbfs-pack-status]' );
		const attachEl = panel.querySelector( '[data-cbfs-pack-attach]' );
		const cancelBtn = panel.querySelector( '[data-cbfs-pack-cancel]' );
		const summary = panel.querySelector( '[data-cbfs-pack-summary]' );
		const message = panel.querySelector( '[data-cbfs-pack-message]' );
		const packChoice = panel.querySelector( '[data-cbfs-pack-choice-pack]' );
		const payChoice = panel.querySelector( '[data-cbfs-pack-choice-pay]' );
		const choiceLabel = panel.querySelector( '[data-cbfs-pack-choice-label]' );
		const seatsField = form.querySelector( '[name="seats"]' );
		const buttonLabel = form.querySelector( '.cbfs-form__button-label' );

		if ( cancelBtn ) {
			cancelBtn.hidden = true;
		}

		if ( status && status.recognised ) {
			if ( statusEl ) statusEl.hidden = false;
			if ( attachEl ) attachEl.hidden = true;
			if ( summary ) {
				const used = typeof status.uses_used === 'number'
					? status.uses_used
					: Math.max( 0, ( status.uses_total || 0 ) - ( status.uses_remaining || 0 ) );
				summary.textContent = ( status.pack_name || 'Coupon' )
					+ ': ' + used + ' used, ' + ( status.uses_remaining || 0 ) + ' left'
					+ ( status.uses_total ? ' of ' + status.uses_total : '' )
					+ '.';
			}
			if ( message ) {
				if ( status.message && ! status.eligible ) {
					message.hidden = false;
					message.textContent = status.message;
				} else {
					message.hidden = true;
					message.textContent = '';
				}
			}
			if ( packChoice ) {
				packChoice.disabled = ! status.eligible;
				if ( ! status.eligible && packChoice.checked && payChoice ) {
					payChoice.checked = true;
				}
			}
			if ( choiceLabel ) {
				choiceLabel.textContent = status.eligible
					? 'Use coupon (1 seat)'
					: 'Use coupon (unavailable)';
			}
		} else {
			if ( statusEl ) statusEl.hidden = true;
			if ( attachEl ) attachEl.hidden = false;
			if ( packChoice ) {
				packChoice.checked = false;
				packChoice.disabled = true;
			}
			if ( payChoice ) payChoice.checked = true;
		}

		const usingPack = shouldUsePack( form );
		if ( seatsField ) {
			seatsField.disabled = usingPack;
			if ( usingPack ) {
				seatsField.value = '1';
			}
		}
		if ( buttonLabel ) {
			buttonLabel.textContent = usingPack
				? 'Book with coupon'
				: 'Book & pay with Stripe';
		}
		updateTotal( form );
	}

	function showPackCodeEntry( form ) {
		const panel = form.querySelector( '[data-cbfs-pack-panel]' );
		if ( ! panel ) {
			return;
		}
		const statusEl = panel.querySelector( '[data-cbfs-pack-status]' );
		const attachEl = panel.querySelector( '[data-cbfs-pack-attach]' );
		const cancelBtn = panel.querySelector( '[data-cbfs-pack-cancel]' );
		const errEl = panel.querySelector( '[data-cbfs-pack-attach-error]' );
		const codeInput = panel.querySelector( '[data-cbfs-pack-code]' );
		const canGoBack = !!( getRememberedPackStatus( form ) || ( statusEl && ! statusEl.hidden ) );

		panel.dataset.cbfsPackMode = 'enter-code';
		panel.dataset.cbfsPackCanBack = canGoBack ? '1' : '';
		if ( statusEl ) statusEl.hidden = true;
		if ( attachEl ) attachEl.hidden = false;
		if ( cancelBtn ) cancelBtn.hidden = ! canGoBack;
		if ( errEl ) {
			errEl.hidden = true;
			errEl.textContent = '';
		}
		if ( codeInput ) {
			codeInput.value = '';
			codeInput.focus();
		}
	}

	function cancelPackCodeEntry( form ) {
		const panel = form.querySelector( '[data-cbfs-pack-panel]' );
		if ( ! panel ) {
			return;
		}
		panel.dataset.cbfsPackMode = '';
		panel.dataset.cbfsPackCanBack = '';
		const cached = getRememberedPackStatus( form );
		if ( cached ) {
			applyPackUiState( form, cached );
			return;
		}
		const statusEl = panel.querySelector( '[data-cbfs-pack-status]' );
		const attachEl = panel.querySelector( '[data-cbfs-pack-attach]' );
		const cancelBtn = panel.querySelector( '[data-cbfs-pack-cancel]' );
		const summaryEl = statusEl ? statusEl.querySelector( '[data-cbfs-pack-summary]' ) : null;
		// Fallback: flip panels even if cache/cookie is missing.
		if ( statusEl && summaryEl && summaryEl.textContent ) {
			statusEl.hidden = false;
			if ( attachEl ) attachEl.hidden = true;
			if ( cancelBtn ) cancelBtn.hidden = true;
			return;
		}
		refreshPackStatus( form );
	}

	async function restorePackFromStorage( form ) {
		const token = readPackRestoreToken();
		if ( ! token ) {
			return null;
		}
		const wrapper = getWrapperForForm( form );
		if ( ! wrapper ) {
			return null;
		}
		const classId = parseInt( wrapper.dataset.cbfsClassId, 10 ) || 0;
		const email = ( form.querySelector( '[name="customer_email"]' ) || {} ).value || '';
		try {
			const res = await fetch( buildRestUrl( 'pack-restore' ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce,
				},
				body: JSON.stringify( {
					token: token,
					class_id: classId,
					customer_email: email,
				} ),
			} );
			const data = await res.json().catch( function () { return {}; } );
			if ( ! res.ok || ! data.recognised ) {
				clearPackRestoreToken();
				return null;
			}
			return data;
		} catch ( e ) {
			return null;
		}
	}

	async function refreshPackStatus( form ) {
		const wrapper = getWrapperForForm( form );
		const panel = form.querySelector( '[data-cbfs-pack-panel]' );
		if ( ! wrapper || ! panel ) {
			return;
		}
		if ( panel.dataset.cbfsPackMode === 'enter-code' ) {
			return;
		}
		const classId = parseInt( wrapper.dataset.cbfsClassId, 10 ) || 0;
		const email = ( form.querySelector( '[name="customer_email"]' ) || {} ).value || '';
		try {
			const res = await fetch( buildRestUrl( 'pack-status', {
				class_id: classId,
				customer_email: email,
			} ), {
				credentials: 'same-origin',
				headers: { 'X-WP-Nonce': cfg.nonce },
			} );
			let data = await res.json().catch( function () { return {}; } );
			if ( ! data.recognised ) {
				const restored = await restorePackFromStorage( form );
				if ( restored ) {
					data = restored;
				}
			}
			applyPackUiState( form, data );
		} catch ( e ) {
			panel.hidden = false;
		}
	}

	function seedPackRestoreFromStatusPage() {
		const el = document.querySelector( '[data-cbfs-pack-restore-token]' );
		if ( ! el ) {
			return;
		}
		const token = el.getAttribute( 'data-cbfs-pack-restore-token' ) || '';
		if ( token ) {
			savePackRestoreToken( token );
		}
	}

	async function attachPackCode( form ) {
		const codeInput = form.querySelector( '[data-cbfs-pack-code]' );
		const errEl = form.querySelector( '[data-cbfs-pack-attach-error]' );
		const wrapper = getWrapperForForm( form );
		if ( ! codeInput || ! wrapper ) {
			return;
		}
		const code = ( codeInput.value || '' ).toString().trim();
		const email = ( form.querySelector( '[name="customer_email"]' ) || {} ).value || '';
		if ( errEl ) {
			errEl.hidden = true;
			errEl.textContent = '';
		}
		if ( ! code ) {
			if ( errEl ) {
				errEl.hidden = false;
				errEl.textContent = 'Please enter a coupon code.';
			}
			return;
		}
		try {
			const res = await fetch( buildRestUrl( 'pack-attach' ), {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': cfg.nonce,
				},
				body: JSON.stringify( {
					code: code,
					customer_email: email,
					class_id: parseInt( wrapper.dataset.cbfsClassId, 10 ) || 0,
				} ),
			} );
			const data = await res.json().catch( function () { return {}; } );
			if ( ! res.ok || data.error ) {
				if ( errEl ) {
					errEl.hidden = false;
					errEl.textContent = data.message || 'Could not apply that coupon code.';
				}
				return;
			}
			if ( data.email && ! email ) {
				const emailField = form.querySelector( '[name="customer_email"]' );
				if ( emailField ) {
					emailField.value = data.email;
				}
			}
			applyPackUiState( form, data );
			const packChoice = form.querySelector( '[data-cbfs-pack-choice-pack]' );
			if ( packChoice && data.eligible ) {
				packChoice.checked = true;
				applyPackUiState( form, data );
			}
		} catch ( e ) {
			if ( errEl ) {
				errEl.hidden = false;
				errEl.textContent = 'Network error. Please try again.';
			}
		}
	}

	function initBookingForms( root ) {
		const scope = root || document;
		scope.querySelectorAll( '.cbfs-form__form' ).forEach( function ( form ) {
			updateSeatsOptions( form );
			applyPresetDate( form );
			refreshPackStatus( form );
		} );
		scope.querySelectorAll( '[data-cbfs-waiver-group]' ).forEach( function ( group ) {
			const input = group.querySelector( '[name="waiver_accepted"]' );
			const labelArea = group.querySelector( '[data-cbfs-waiver-label]' );
			if ( ! input || ! labelArea || group.dataset.cbfsWaiverBound === '1' ) {
				return;
			}
			group.dataset.cbfsWaiverBound = '1';
			labelArea.addEventListener( 'click', function ( ev ) {
				if ( ev.target.closest && ev.target.closest( 'a' ) ) {
					return;
				}
				ev.preventDefault();
				input.checked = ! input.checked;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
			} );
		} );
	}

	function init() {
		window.CLASBOWPRO_updateSeats = updateSeatsOptions;
		window.CLASBOWPRO_initBookingForms = initBookingForms;
		seedPackRestoreFromStatusPage();
		initBookingForms( document );
		document.addEventListener( 'change', function ( ev ) {
			const target = ev.target;
			if ( ! target || ! target.closest ) return;
			const form = target.closest( '.cbfs-form__form' );
			if ( ! form ) return;
			if ( target.matches( '[name="class_date"]' ) || target.matches( '[name="slot_rule_id"]' ) ) {
				updateSeatsOptions( form );
				updateTotal( form );
			}
			if ( target.matches( '[name="seats"]' ) ) {
				updateTotal( form );
			}
			if ( target.matches( '[name="customer_email"]' ) ) {
				refreshPackStatus( form );
			}
			if ( target.matches( '[name="cbfs_pack_choice"]' ) ) {
				const seatsField = form.querySelector( '[name="seats"]' );
				const buttonLabel = form.querySelector( '.cbfs-form__button-label' );
				const usingPack = shouldUsePack( form );
				if ( seatsField ) {
					seatsField.disabled = usingPack;
					if ( usingPack ) {
						seatsField.value = '1';
					}
				}
				if ( buttonLabel ) {
					buttonLabel.textContent = usingPack
						? 'Book with coupon'
						: 'Book & pay with Stripe';
				}
				updateTotal( form );
			}
		} );
		document.addEventListener( 'click', function ( ev ) {
			const target = ev.target;
			if ( ! target || ! target.closest ) return;
			const form = target.closest( '.cbfs-form__form' );
			if ( ! form ) return;
			if ( target.closest( '[data-cbfs-pack-attach-btn]' ) ) {
				ev.preventDefault();
				attachPackCode( form );
			}
			if ( target.closest( '[data-cbfs-pack-switch]' ) ) {
				ev.preventDefault();
				// Keep the current coupon cookie until a new code is applied.
				showPackCodeEntry( form );
			}
			if ( target.closest( '[data-cbfs-pack-cancel]' ) ) {
				ev.preventDefault();
				cancelPackCodeEntry( form );
			}
		} );
		document.addEventListener( 'submit', function ( ev ) {
			const form = ev.target && ev.target.closest ? ev.target.closest( '.cbfs-form__form' ) : null;
			if ( ! form ) return;
			handleFormSubmit( form, ev );
		} );
		attachStatusPolling();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
