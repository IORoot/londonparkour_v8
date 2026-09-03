/**
 * Settings screen: welcome panel collapse + deep-link ACF tabs from URL hash.
 */
( function () {
	'use strict';

	var LS_KEY = 'clasbpro_welcome_intro_collapsed';
	var root = document.getElementById( 'clasbpro-welcome-panel' );
	var toggle = document.getElementById( 'clasbpro-welcome-toggle' );
	var expandable = document.getElementById( 'clasbpro-welcome-expandable' );

	if ( root && toggle && expandable ) {
		function setCollapsed( collapsed ) {
			root.classList.toggle( 'is-collapsed', collapsed );
			toggle.setAttribute( 'aria-expanded', collapsed ? 'false' : 'true' );
			var ax = collapsed
				? toggle.getAttribute( 'data-clasbpro-aria-collapsed' )
				: toggle.getAttribute( 'data-clasbpro-aria-expanded' );
			if ( ax ) {
				toggle.setAttribute( 'aria-label', ax );
			}
			expandable.setAttribute( 'aria-hidden', collapsed ? 'true' : 'false' );
			try {
				localStorage.setItem( LS_KEY, collapsed ? '1' : '0' );
			} catch ( e ) {
				// Ignore storage errors (private mode, quota, etc.).
			}
		}

		try {
			if ( localStorage.getItem( LS_KEY ) === '1' ) {
				setCollapsed( true );
			}
		} catch ( e ) {
			// Ignore storage errors.
		}

		toggle.addEventListener( 'click', function () {
			setCollapsed( ! root.classList.contains( 'is-collapsed' ) );
		} );
	}

	var EMAIL_SUBTAB_MAP = {
		field_clasbpro_email_subtab_admin: 'admin',
		field_clasbpro_email_subtab_customer: 'customer',
		field_clasbpro_email_subtab_admin_coupon: 'admin-coupon',
		field_clasbpro_email_subtab_customer_coupon: 'customer-coupon',
		field_clasbpro_email_subtab_reminders: 'reminders',
		field_clasbpro_email_subtab_post_class: 'post-class',
		field_clasbpro_email_subtab_extras: 'extras',
	};

	var HELP_SUBTAB_MAP = {
		field_clasbpro_help_subtab_setup: 'setup',
		field_clasbpro_help_subtab_shortcodes: 'shortcodes',
		field_clasbpro_help_subtab_developer: 'developer',
	};

	function clasbproEmailSubtabBody() {
		return document.body;
	}

	function clasbproEmailViewClass( section ) {
		return 'clasbpro-email-view-' + section;
	}

	var STRIPE_KEY_FIELD_NAMES = [
		'stripe_pub_key_test',
		'stripe_secret_key_test',
		'stripe_pub_key_live',
		'stripe_secret_key_live',
	];

	var STRIPE_KEY_PANEL_CFG = [
		{
			section: 'test',
			bannerKey: 'field_clasbpro_stripe_test_keys_banner',
			names: [ 'stripe_pub_key_test', 'stripe_secret_key_test' ],
		},
		{
			section: 'live',
			bannerKey: 'field_clasbpro_stripe_live_keys_banner',
			names: [ 'stripe_pub_key_live', 'stripe_secret_key_live' ],
		},
	];

	function clasbproIsStripeKeyFieldName( name ) {
		return STRIPE_KEY_FIELD_NAMES.indexOf( name ) !== -1;
	}

	function clasbproIsStripeKeyFieldElement( el ) {
		if ( ! el || ! el.classList || ! el.classList.contains( 'acf-field' ) ) {
			return false;
		}
		var name = el.getAttribute( 'data-name' ) || '';
		if ( clasbproIsStripeKeyFieldName( name ) ) {
			return true;
		}
		var key = el.getAttribute( 'data-key' ) || '';
		return (
			key === 'field_clasbpro_stripe_test_keys_banner' ||
			key === 'field_clasbpro_stripe_live_keys_banner' ||
			!! el.closest( '.clasbpro-stripe-keys-panel' )
		);
	}

	function clasbproScheduleStripeSettingsUi() {
		[ 0, 50, 150, 400, 800, 1200, 2000, 3000 ].forEach( function ( delay ) {
			window.setTimeout( clasbproInitStripeSettingsUi, delay );
		} );
	}

	function clasbproRevealStripeKeyField( field ) {
		if ( ! field ) {
			return;
		}
		field.classList.remove( 'acf-hidden' );
		field.removeAttribute( 'hidden' );
		if ( typeof acf !== 'undefined' && acf.getField ) {
			var model = acf.getField( field );
			if ( model && typeof model.show === 'function' ) {
				model.show();
			}
		}
	}

	function clasbproUnhideStripeKeyPanel( panel ) {
		if ( ! panel ) {
			return;
		}
		panel.classList.remove( 'acf-hidden' );
		panel.removeAttribute( 'hidden' );
		panel.querySelectorAll( '.acf-field' ).forEach( clasbproRevealStripeKeyField );
	}

	function clasbproRepairStripeKeyPanel( panel, cfg, root ) {
		var banner = root.querySelector(
			'.acf-field[data-key="' + cfg.bannerKey + '"]'
		);
		var fields = cfg.names
			.map( function ( name ) {
				return root.querySelector( '.acf-field[data-name="' + name + '"]' );
			} )
			.filter( Boolean );
		if ( ! banner || fields.length !== cfg.names.length ) {
			return;
		}

		if ( banner.parentElement !== panel ) {
			panel.appendChild( banner );
		}
		fields.forEach( function ( field ) {
			if ( field.parentElement !== panel ) {
				panel.appendChild( field );
			}
		} );
		clasbproUnhideStripeKeyPanel( panel );
	}

	function clasbproMountStripeKeyPanels() {
		var root = document.querySelector( '.clasbpro-booking-settings .acf-fields' );
		if ( ! root ) {
			return;
		}

		[
			STRIPE_KEY_PANEL_CFG[ 0 ],
			STRIPE_KEY_PANEL_CFG[ 1 ],
		].forEach( function ( cfg ) {
			var panelSelector = '.clasbpro-stripe-keys-panel--' + cfg.section;
			var existingPanel = root.querySelector( panelSelector );
			if ( existingPanel ) {
				clasbproRepairStripeKeyPanel( existingPanel, cfg, root );
				return;
			}

			var banner = root.querySelector(
				'.acf-field[data-key="' + cfg.bannerKey + '"]'
			);
			var fields = cfg.names
				.map( function ( name ) {
					return root.querySelector( '.acf-field[data-name="' + name + '"]' );
				} )
				.filter( Boolean );
			if ( ! banner || fields.length !== cfg.names.length ) {
				return;
			}

			var panel = document.createElement( 'div' );
			panel.className =
				'clasbpro-stripe-keys-panel clasbpro-stripe-keys-panel--' + cfg.section;
			root.insertBefore( panel, banner );
			panel.appendChild( banner );
			fields.forEach( function ( field ) {
				panel.appendChild( field );
			} );
			clasbproUnhideStripeKeyPanel( panel );
		} );
	}

	function clasbproObserveStripeKeyFields() {
		var root = document.querySelector( '.clasbpro-booking-settings .acf-fields' );
		if ( ! root || root.dataset.clasbproStripeObserve === '1' ) {
			return;
		}
		root.dataset.clasbproStripeObserve = '1';

		var observer = new MutationObserver( function ( mutations ) {
			var needsRepair = false;
			mutations.forEach( function ( mutation ) {
				if ( mutation.type !== 'attributes' ) {
					return;
				}
				var target = mutation.target;
				if ( ! clasbproIsStripeKeyFieldElement( target ) ) {
					return;
				}
				if (
					mutation.attributeName === 'hidden' &&
					target.hasAttribute( 'hidden' ) &&
					clasbproIsStripeTabActive()
				) {
					clasbproRevealStripeKeyField( target );
					needsRepair = true;
					return;
				}
				if (
					mutation.attributeName === 'class' &&
					target.classList.contains( 'acf-hidden' )
				) {
					needsRepair = true;
				}
			} );
			if ( needsRepair && clasbproIsStripeTabActive() ) {
				clasbproScheduleStripeSettingsUi();
			}
		} );
		observer.observe( root, {
			subtree: true,
			attributes: true,
			attributeFilter: [ 'class', 'hidden' ],
		} );
	}

	function clasbproBindSettingsFormStripeSave() {
		var form = document.querySelector( '.clasbpro-settings-form' );
		if ( ! form || form.dataset.clasbproStripeSubmitBound === '1' ) {
			return;
		}
		form.dataset.clasbproStripeSubmitBound = '1';
		form.addEventListener(
			'submit',
			function () {
				try {
					sessionStorage.setItem(
						'clasbproRestoreStripeTab',
						clasbproIsStripeTabActive() ? '1' : '0'
					);
				} catch ( error ) {
					// Ignore private browsing storage errors.
				}
			},
			true
		);
	}

	function clasbproRestoreStripeTabAfterSave() {
		var shouldRestore = false;
		try {
			shouldRestore = sessionStorage.getItem( 'clasbproRestoreStripeTab' ) === '1';
			if ( shouldRestore ) {
				sessionStorage.removeItem( 'clasbproRestoreStripeTab' );
			}
		} catch ( error ) {
			shouldRestore = false;
		}

		if ( ! shouldRestore ) {
			return;
		}

		clasbproSetStripeTabActive( true );
		var btn = document.querySelector(
			'.clasbpro-booking-settings .acf-tab-button[data-key="field_clasbpro_tab_stripe"]'
		);
		if ( btn ) {
			btn.click();
		}
		clasbproScheduleStripeSettingsUi();
	}

	function clasbproBindStripeKeyPanelGuards() {
		if ( document.body.dataset.clasbproStripeKeyGuards === '1' ) {
			return;
		}
		document.body.dataset.clasbproStripeKeyGuards = '1';

		clasbproBindSettingsFormStripeSave();
		clasbproObserveStripeKeyFields();

		if ( typeof acf === 'undefined' || ! acf.addAction ) {
			return;
		}

		acf.addAction( 'hide_field', function ( field ) {
			if ( ! clasbproIsStripeTabActive() ) {
				return;
			}
			var name = field && field.get ? field.get( 'name' ) : '';
			var el = field && field.$el ? field.$el[ 0 ] : null;
			if (
				! clasbproIsStripeKeyFieldName( name ) &&
				! ( el && clasbproIsStripeKeyFieldElement( el ) )
			) {
				return;
			}
			clasbproScheduleStripeSettingsUi();
		} );

		acf.addAction( 'show_field', function ( field ) {
			if ( ! field || ! field.get ) {
				return;
			}
			if ( ! clasbproIsStripeKeyFieldName( field.get( 'name' ) || '' ) ) {
				return;
			}
			window.setTimeout( clasbproInitStripeSettingsUi, 0 );
		} );

		acf.addAction( 'ready', function () {
			clasbproRestoreStripeTabAfterSave();
			clasbproScheduleStripeSettingsUi();
		} );
	}

	function clasbproSyncStripeModeClass() {
		var body = document.body;
		if ( ! body || ! body.classList.contains( 'clasbpro-booking-settings' ) ) {
			return;
		}

		var select = document.querySelector(
			'.clasbpro-booking-settings .acf-field[data-name="stripe_mode"] select'
		);
		body.classList.remove( 'clasbpro-stripe-mode-test', 'clasbpro-stripe-mode-live' );
		if ( ! select ) {
			return;
		}
		body.classList.add(
			select.value === 'live' ? 'clasbpro-stripe-mode-live' : 'clasbpro-stripe-mode-test'
		);
	}

	function clasbproBindStripeModeSync() {
		var select = document.querySelector(
			'.clasbpro-booking-settings .acf-field[data-name="stripe_mode"] select'
		);
		if ( ! select || select.dataset.clasbproModeBound === '1' ) {
			return;
		}
		select.dataset.clasbproModeBound = '1';
		select.addEventListener( 'change', clasbproSyncStripeModeClass );
		clasbproSyncStripeModeClass();
	}

	function clasbproInitStripeSettingsUi() {
		clasbproSyncMainSettingsTabState();
		clasbproMountStripeKeyPanels();
		clasbproBindStripeModeSync();
	}

	function clasbproMountScheduledEmailControlRows() {
		var root = document.querySelector( '.clasbpro-booking-settings .acf-fields' );
		if ( ! root ) {
			return;
		}

		[
			{
				section: 'reminders',
				names: [
					'enable_reminder_emails',
					'reminder_offset_amount',
					'reminder_offset_unit',
					'reminder_admin_copy',
				],
			},
			{
				section: 'post-class',
				names: [
					'enable_post_class_emails',
					'post_class_offset_amount',
					'post_class_offset_unit',
					'post_class_admin_copy',
				],
			},
		].forEach( function ( cfg ) {
			var rowSelector =
				'.clasbpro-scheduled-email-controls-row.clasbpro-email-section-' + cfg.section;
			var existingRow = root.querySelector( rowSelector );
			if ( existingRow ) {
				clasbproUnhideScheduledEmailControlRow( existingRow );
				return;
			}

			var fields = cfg.names
				.map( function ( name ) {
					return root.querySelector( '.acf-field[data-name="' + name + '"]' );
				} )
				.filter( Boolean );
			if ( fields.length !== cfg.names.length ) {
				return;
			}

			var row = document.createElement( 'div' );
			row.className =
				'clasbpro-scheduled-email-controls-row clasbpro-email-section clasbpro-email-section-' +
				cfg.section;
			root.insertBefore( row, fields[ 0 ] );
			fields.forEach( function ( field ) {
				field.classList.remove( 'acf-hidden' );
				row.appendChild( field );
			} );
			clasbproUnhideScheduledEmailControlRow( row );
		} );
	}

	function clasbproUnhideScheduledEmailControlRow( row ) {
		if ( ! row ) {
			return;
		}
		row.classList.remove( 'acf-hidden' );
		row.querySelectorAll( '.acf-field' ).forEach( function ( field ) {
			field.classList.remove( 'acf-hidden' );
			if ( typeof acf !== 'undefined' && acf.getField ) {
				var model = acf.getField( field );
				if ( model && typeof model.show === 'function' ) {
					model.show();
				}
			}
		} );
	}

	function clasbproBindScheduledEmailControlGuards() {
		if ( document.body.dataset.clasbproScheduledEmailGuards === '1' ) {
			return;
		}
		document.body.dataset.clasbproScheduledEmailGuards = '1';

		if ( typeof acf === 'undefined' || ! acf.addAction ) {
			return;
		}

		acf.addAction( 'hide_field', function ( field ) {
			var el = field && field.$el ? field.$el[ 0 ] : null;
			if ( ! el || ! el.closest( '.clasbpro-scheduled-email-controls-row' ) ) {
				return;
			}
			window.requestAnimationFrame( function () {
				clasbproUnhideScheduledEmailControlRow(
					el.closest( '.clasbpro-scheduled-email-controls-row' )
				);
			} );
		} );

		acf.addAction( 'ready', function () {
			window.setTimeout( function () {
				document
					.querySelectorAll( '.clasbpro-scheduled-email-controls-row' )
					.forEach( clasbproUnhideScheduledEmailControlRow );
			}, 0 );
		} );
	}

	function clasbproSetEmailsTabActive( isActive ) {
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return;
		}
		body.classList.toggle( 'clasbpro-emails-tab-active', !! isActive );
	}

	function clasbproSetStripeTabActive( isActive ) {
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return;
		}
		body.classList.toggle( 'clasbpro-stripe-tab-active', !! isActive );
	}

	function clasbproSetHelpTabActive( isActive ) {
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return;
		}
		body.classList.toggle( 'clasbpro-help-tab-active', !! isActive );
	}

	function clasbproHelpViewClass( section ) {
		return 'clasbpro-help-view-' + section;
	}

	function clasbproIsAcfTabActive( tabKey ) {
		var btn = document.querySelector(
			'.acf-tab-button[data-key="' + tabKey + '"]'
		);
		if ( ! btn ) {
			return false;
		}
		if ( btn.classList.contains( 'active' ) ) {
			return true;
		}
		var li = btn.closest( 'li' );
		return !! ( li && li.classList.contains( 'active' ) );
	}

	function clasbproIsEmailsTabActive() {
		return clasbproIsAcfTabActive( 'field_clasbpro_tab_emails' );
	}

	function clasbproIsStripeTabActive() {
		return clasbproIsAcfTabActive( 'field_clasbpro_tab_stripe' );
	}

	function clasbproIsHelpTabActive() {
		return clasbproIsAcfTabActive( 'field_clasbpro_tab_help' );
	}

	function clasbproSyncMainSettingsTabState() {
		clasbproSetEmailsTabActive( clasbproIsEmailsTabActive() );
		clasbproSetStripeTabActive( clasbproIsStripeTabActive() );
		clasbproSetHelpTabActive( clasbproIsHelpTabActive() );
	}

	function clasbproBindMainSettingsTabHooks() {
		var tabWrap = document.querySelector(
			'.clasbpro-booking-settings .acf-tab-wrap'
		);
		if ( ! tabWrap || tabWrap.dataset.clasbproMainTabsBound === '1' ) {
			return;
		}
		tabWrap.dataset.clasbproMainTabsBound = '1';
		tabWrap.addEventListener( 'click', function ( event ) {
			var btn = event.target.closest( '.acf-tab-button' );
			if ( ! btn ) {
				return;
			}
			var tabKey = btn.getAttribute( 'data-key' ) || '';
			var isEmails = tabKey === 'field_clasbpro_tab_emails';
			var isStripe = tabKey === 'field_clasbpro_tab_stripe';
			var isHelp = tabKey === 'field_clasbpro_tab_help';
			clasbproSetEmailsTabActive( isEmails );
			clasbproSetStripeTabActive( isStripe );
			clasbproSetHelpTabActive( isHelp );
			if ( isStripe ) {
				clasbproScheduleStripeSettingsUi();
			}
			if ( isEmails ) {
				window.setTimeout( function () {
					clasbproInitEmailSubtabs();
					clasbproMountScheduledEmailControlRows();
					clasbproMountEmailBodyEditors();
				}, 0 );
			}
			if ( isHelp ) {
				window.setTimeout( clasbproInitHelpSubtabs, 0 );
			}
		} );
	}

	function clasbproActivateEmailSubtab( section ) {
		if ( ! section ) {
			return;
		}
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return;
		}
		body.classList.add( 'clasbpro-email-subtabs-ready' );
		Object.keys( EMAIL_SUBTAB_MAP ).forEach( function ( key ) {
			body.classList.remove( clasbproEmailViewClass( EMAIL_SUBTAB_MAP[ key ] ) );
		} );
		body.classList.add( clasbproEmailViewClass( section ) );

		document.querySelectorAll( '.clasbpro-email-subtabs__btn' ).forEach( function ( btn ) {
			btn.classList.toggle(
				'is-active',
				btn.getAttribute( 'data-clasbpro-email-section' ) === section
			);
		} );

		var description = document.querySelector( '.clasbpro-email-subtabs-description' );
		if ( description ) {
			var activeBtn = document.querySelector(
				'.clasbpro-email-subtabs__btn[data-clasbpro-email-section="' + section + '"]'
			);
			description.textContent = activeBtn
				? activeBtn.getAttribute( 'data-clasbpro-description' ) || ''
				: '';
		}

		clasbproMountScheduledEmailControlRows();
	}

	function clasbproCurrentEmailSubtab() {
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return 'admin';
		}
		var match = body.className.match( /clasbpro-email-view-([a-z-]+)/ );
		return match ? match[ 1 ] : 'admin';
	}

	function clasbproInitEmailSubtabs( forceSection ) {
		var nav = document.querySelector( '.clasbpro-email-subtabs' );
		if ( ! nav ) {
			return;
		}
		if ( nav.dataset.bound !== '1' ) {
			nav.dataset.bound = '1';
			nav.addEventListener( 'click', function ( event ) {
				var btn = event.target.closest( '.clasbpro-email-subtabs__btn' );
				if ( ! btn ) {
					return;
				}
				event.preventDefault();
				var section = btn.getAttribute( 'data-clasbpro-email-section' );
				var hashKey = btn.getAttribute( 'data-clasbpro-hash' );
				clasbproActivateEmailSubtab( section );
				if ( hashKey ) {
					window.location.hash = 'clasbpro-tab-' + hashKey;
				}
			} );
		}

		var hash = window.location.hash || '';
		var initial = forceSection || clasbproCurrentEmailSubtab() || 'admin';
		if ( ! forceSection && hash.indexOf( '#clasbpro-tab-field_clasbpro_email_subtab_' ) === 0 ) {
			var key = hash.slice( '#clasbpro-tab-'.length );
			if ( EMAIL_SUBTAB_MAP[ key ] ) {
				initial = EMAIL_SUBTAB_MAP[ key ];
			}
		}
		clasbproActivateEmailSubtab( initial );
	}

	function clasbproActivateHelpSubtab( section ) {
		if ( ! section ) {
			return;
		}
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return;
		}
		body.classList.add( 'clasbpro-help-subtabs-ready' );
		[ 'setup', 'shortcodes', 'developer' ].forEach( function ( slug ) {
			body.classList.remove( clasbproHelpViewClass( slug ) );
		} );
		body.classList.add( clasbproHelpViewClass( section ) );

		document.querySelectorAll( '.clasbpro-help-subtabs__btn' ).forEach( function ( btn ) {
			btn.classList.toggle(
				'is-active',
				btn.getAttribute( 'data-clasbpro-help-section' ) === section
			);
		} );

		var description = document.querySelector( '.clasbpro-help-subtabs-description' );
		if ( description ) {
			var activeBtn = document.querySelector(
				'.clasbpro-help-subtabs__btn[data-clasbpro-help-section="' + section + '"]'
			);
			description.textContent = activeBtn
				? activeBtn.getAttribute( 'data-clasbpro-description' ) || ''
				: '';
		}
	}

	function clasbproCurrentHelpSubtab() {
		var body = clasbproEmailSubtabBody();
		if ( ! body ) {
			return 'setup';
		}
		var match = body.className.match( /clasbpro-help-view-([a-z]+)/ );
		return match ? match[ 1 ] : 'setup';
	}

	function clasbproInitHelpSubtabs( forceSection ) {
		var nav = document.querySelector( '.clasbpro-help-subtabs' );
		if ( ! nav ) {
			return;
		}
		if ( nav.dataset.bound !== '1' ) {
			nav.dataset.bound = '1';
			nav.addEventListener( 'click', function ( event ) {
				var btn = event.target.closest( '.clasbpro-help-subtabs__btn' );
				if ( ! btn ) {
					return;
				}
				event.preventDefault();
				var section = btn.getAttribute( 'data-clasbpro-help-section' );
				var hashKey = btn.getAttribute( 'data-clasbpro-hash' );
				clasbproActivateHelpSubtab( section );
				if ( hashKey ) {
					window.location.hash = 'clasbpro-tab-' + hashKey;
				}
			} );
		}

		var hash = window.location.hash || '';
		var initial = forceSection || clasbproCurrentHelpSubtab() || 'setup';
		if ( ! forceSection && hash.indexOf( '#clasbpro-tab-field_clasbpro_help_subtab_' ) === 0 ) {
			var key = hash.slice( '#clasbpro-tab-'.length );
			if ( HELP_SUBTAB_MAP[ key ] ) {
				initial = HELP_SUBTAB_MAP[ key ];
			}
		}
		clasbproActivateHelpSubtab( initial );
	}

	function clasbproBindEmailTabHooks() {
		if ( typeof acf === 'undefined' || ! acf.addAction ) {
			return;
		}
		acf.addAction( 'ready', function () {
			clasbproBindMainSettingsTabHooks();
			clasbproSyncMainSettingsTabState();
			clasbproRestoreStripeTabAfterSave();
			clasbproScheduleStripeSettingsUi();
			clasbproMountScheduledEmailControlRows();
			if ( clasbproIsEmailsTabActive() ) {
				clasbproInitEmailSubtabs();
				clasbproMountEmailBodyEditors();
			}
			if ( clasbproIsHelpTabActive() ) {
				clasbproInitHelpSubtabs();
			}
		} );
		acf.addAction( 'show_field/type=tab', function ( field ) {
			if ( ! field ) {
				return;
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_stripe' ) {
				clasbproSetStripeTabActive( true );
				clasbproSetEmailsTabActive( false );
				clasbproSetHelpTabActive( false );
				clasbproScheduleStripeSettingsUi();
				return;
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_emails' ) {
				clasbproSetEmailsTabActive( true );
				clasbproSetStripeTabActive( false );
				clasbproSetHelpTabActive( false );
				window.setTimeout( function () {
					clasbproInitEmailSubtabs();
					clasbproMountScheduledEmailControlRows();
					clasbproMountEmailBodyEditors();
				}, 0 );
				return;
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_help' ) {
				clasbproSetHelpTabActive( true );
				clasbproSetEmailsTabActive( false );
				clasbproSetStripeTabActive( false );
				window.setTimeout( clasbproInitHelpSubtabs, 0 );
				return;
			}
			if (
				0 === String( field.get( 'key' ) || '' ).indexOf( 'field_clasbpro_tab_' )
			) {
				clasbproSetEmailsTabActive( false );
				clasbproSetStripeTabActive( false );
				clasbproSetHelpTabActive( false );
			}
		} );
		acf.addAction( 'hide_field/type=tab', function ( field ) {
			if ( ! field ) {
				return;
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_emails' ) {
				clasbproSetEmailsTabActive( false );
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_stripe' ) {
				clasbproSetStripeTabActive( false );
			}
			if ( field.get( 'key' ) === 'field_clasbpro_tab_help' ) {
				clasbproSetHelpTabActive( false );
			}
		} );
	}

	var EMAIL_BODY_EDITOR_FIELDS = [
		{
			visual: 'admin_email_body',
			html: 'admin_email_body_html',
			mode: 'admin_email_body_editor_mode',
			section: 'admin',
		},
		{
			visual: 'customer_email_body',
			html: 'customer_email_body_html',
			mode: 'customer_email_body_editor_mode',
			section: 'customer',
		},
		{
			visual: 'admin_coupon_email_body',
			html: 'admin_coupon_email_body_html',
			mode: 'admin_coupon_email_body_editor_mode',
			section: 'admin-coupon',
		},
		{
			visual: 'customer_coupon_email_body',
			html: 'customer_coupon_email_body_html',
			mode: 'customer_coupon_email_body_editor_mode',
			section: 'customer-coupon',
		},
		{
			visual: 'reminder_email_body',
			html: 'reminder_email_body_html',
			mode: 'reminder_email_body_editor_mode',
			section: 'reminders',
		},
		{
			visual: 'post_class_email_body',
			html: 'post_class_email_body_html',
			mode: 'post_class_email_body_editor_mode',
			section: 'post-class',
		},
	];

	var clasbproEmailCodeEditors = {};

	function clasbproGetEmailBodyModeSelect( modeField ) {
		if ( ! modeField ) {
			return null;
		}
		return modeField.querySelector( 'select' );
	}

	function clasbproNormalizeEmailBodyMode( mode ) {
		return mode === 'html' || mode === 'raw' ? mode : 'visual';
	}

	function clasbproEmailBodyModeUsesHtmlField( mode ) {
		return mode === 'html' || mode === 'raw';
	}

	function clasbproGetEmailBodyMode( modeField ) {
		var select = clasbproGetEmailBodyModeSelect( modeField );
		return clasbproNormalizeEmailBodyMode( select ? select.value : 'visual' );
	}

	function clasbproSetEmailBodyMode( modeField, mode ) {
		var select = clasbproGetEmailBodyModeSelect( modeField );
		if ( ! select ) {
			return;
		}
		select.value = clasbproNormalizeEmailBodyMode( mode );
		select.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function clasbproCopyTextToClipboard( text ) {
		function fallback() {
			return new Promise( function ( resolve, reject ) {
				var ta = document.createElement( 'textarea' );
				ta.value = text;
				ta.setAttribute( 'readonly', '' );
				ta.style.position = 'fixed';
				ta.style.left = '-9999px';
				document.body.appendChild( ta );
				ta.select();
				try {
					if ( document.execCommand( 'copy' ) ) {
						resolve();
					} else {
						reject( new Error( 'copy failed' ) );
					}
				} catch ( err ) {
					reject( err );
				}
				document.body.removeChild( ta );
			} );
		}

		if ( navigator.clipboard && window.isSecureContext && navigator.clipboard.writeText ) {
			return navigator.clipboard.writeText( text ).catch( function () {
				return fallback();
			} );
		}

		return fallback();
	}

	function clasbproGetHtmlFieldValue( htmlField, key ) {
		var editor = clasbproEmailCodeEditors[ key ];
		if ( editor && editor.codemirror ) {
			return editor.codemirror.getValue();
		}
		var cm = htmlField ? htmlField.querySelector( '.CodeMirror' ) : null;
		if ( cm && cm.CodeMirror ) {
			return cm.CodeMirror.getValue();
		}
		var textarea = htmlField ? htmlField.querySelector( 'textarea' ) : null;
		return textarea ? textarea.value : '';
	}

	function clasbproMountHtmlCopyButton( htmlField, key ) {
		if ( ! htmlField || htmlField.dataset.clasbproCopyMounted === '1' ) {
			return;
		}
		var input = htmlField.querySelector( '.acf-input' );
		if ( ! input ) {
			return;
		}

		var bar = document.createElement( 'div' );
		bar.className = 'clasbpro-email-html-copy-bar';
		bar.hidden = true;

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'button clasbpro-email-html-copy';
		btn.textContent = 'Copy to clipboard';
		bar.appendChild( btn );
		input.insertBefore( bar, input.firstChild );

		btn.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			var text = clasbproGetHtmlFieldValue( htmlField, key );
			if ( ! text ) {
				return;
			}
			var idle = 'Copy to clipboard';
			clasbproCopyTextToClipboard( text ).then( function () {
				btn.textContent = 'Copied';
				window.setTimeout( function () {
					btn.textContent = idle;
				}, 1500 );
			} );
		} );

		htmlField.dataset.clasbproCopyMounted = '1';
	}

	function clasbproInitHtmlCodeEditor( htmlField, key ) {
		if ( ! htmlField || htmlField.dataset.clasbproCodeEditorInit === '1' ) {
			return;
		}

		var textarea = htmlField.querySelector( 'textarea' );
		if ( ! textarea ) {
			return;
		}

		if (
			typeof wp !== 'undefined' &&
			wp.codeEditor &&
			window.clasbproEmailCodeEditor &&
			clasbproEmailCodeEditor.cmSettings
		) {
			var editor = wp.codeEditor.initialize( textarea, clasbproEmailCodeEditor.cmSettings );
			if ( editor && editor.codemirror ) {
				clasbproEmailCodeEditors[ key ] = editor;
				window.setTimeout( function () {
					editor.codemirror.refresh();
				}, 0 );
			}
		}

		htmlField.dataset.clasbproCodeEditorInit = '1';
	}

	function clasbproApplyEmailBodyEditorMode( cfg, mode ) {
		var root = document.querySelector( '.clasbpro-booking-settings .acf-fields' );
		if ( ! root ) {
			return;
		}

		var visualField = root.querySelector(
			'.acf-field.clasbpro-email-body-visual-field[data-name="' + cfg.visual + '"]'
		);
		var htmlField = root.querySelector(
			'.acf-field.clasbpro-email-body-html-field[data-name="' + cfg.html + '"]'
		);
		var toolbar = root.querySelector(
			'.clasbpro-email-editor-toolbar[data-clasbpro-email-body="' + cfg.visual + '"]'
		);

		if ( ! visualField || ! htmlField ) {
			return;
		}

		var isHtmlField = clasbproEmailBodyModeUsesHtmlField( mode );
		visualField.style.display = isHtmlField ? 'none' : '';
		htmlField.style.display = isHtmlField ? '' : 'none';

		var copyBar = htmlField.querySelector( '.clasbpro-email-html-copy-bar' );
		if ( copyBar ) {
			copyBar.hidden = mode !== 'raw';
		}

		if ( toolbar ) {
			toolbar.querySelectorAll( '.clasbpro-email-editor-toggle__btn' ).forEach( function ( btn ) {
				btn.classList.toggle(
					'is-active',
					btn.getAttribute( 'data-mode' ) === mode
				);
				btn.setAttribute( 'aria-selected', btn.getAttribute( 'data-mode' ) === mode ? 'true' : 'false' );
			} );
		}

		if ( isHtmlField ) {
			clasbproInitHtmlCodeEditor( htmlField, cfg.html );
			var editor = clasbproEmailCodeEditors[ cfg.html ];
			if ( editor && editor.codemirror ) {
				window.setTimeout( function () {
					editor.codemirror.refresh();
				}, 0 );
			}
		}
	}

	function clasbproMountEmailBodyEditors() {
		var root = document.querySelector( '.clasbpro-booking-settings .acf-fields' );
		if ( ! root ) {
			return;
		}

		EMAIL_BODY_EDITOR_FIELDS.forEach( function ( cfg ) {
			var visualField = root.querySelector(
				'.acf-field.clasbpro-email-body-visual-field[data-name="' + cfg.visual + '"]'
			);
			var htmlField = root.querySelector(
				'.acf-field.clasbpro-email-body-html-field[data-name="' + cfg.html + '"]'
			);
			var modeField = root.querySelector(
				'.acf-field.clasbpro-email-body-mode-field[data-name="' + cfg.mode + '"]'
			);

			if ( ! visualField || ! htmlField || ! modeField ) {
				return;
			}

			clasbproMountHtmlCopyButton( htmlField, cfg.html );

			if ( visualField.dataset.clasbproEmailBodyMounted !== '1' ) {
				var toolbar = document.createElement( 'div' );
				toolbar.className =
					'clasbpro-email-editor-toolbar clasbpro-email-editor-toolbar--' + cfg.section;
				toolbar.setAttribute( 'data-clasbpro-email-body', cfg.visual );
				toolbar.setAttribute( 'data-clasbpro-email-section', cfg.section );

				var label = document.createElement( 'span' );
				label.className = 'clasbpro-email-editor-toolbar__label';
				label.textContent = 'Body editor';
				toolbar.appendChild( label );

				var toggle = document.createElement( 'div' );
				toggle.className = 'clasbpro-email-editor-toggle';
				toggle.setAttribute( 'role', 'tablist' );
				toggle.setAttribute( 'aria-label', 'Email body editor mode' );

				[ 'visual', 'html', 'raw' ].forEach( function ( mode ) {
					var btn = document.createElement( 'button' );
					btn.type = 'button';
					btn.className = 'clasbpro-email-editor-toggle__btn';
					btn.setAttribute( 'data-mode', mode );
					btn.setAttribute( 'role', 'tab' );
					btn.textContent =
						mode === 'raw' ? 'Raw HTML' : mode === 'html' ? 'HTML' : 'Visual';
					toggle.appendChild( btn );
				} );

				toolbar.appendChild( toggle );

				var note = document.createElement( 'p' );
				note.className = 'description clasbpro-email-editor-toolbar__note';
				note.textContent =
					'Visual and HTML are saved separately. HTML is wrapped in the plugin layout. Raw HTML is sent as-is after merge tags.';
				toolbar.appendChild( note );

				visualField.parentNode.insertBefore( toolbar, visualField );

				toggle.addEventListener( 'click', function ( event ) {
					var btn = event.target.closest( '.clasbpro-email-editor-toggle__btn' );
					if ( ! btn ) {
						return;
					}
					event.preventDefault();
					var nextMode = btn.getAttribute( 'data-mode' ) || 'visual';
					clasbproSetEmailBodyMode( modeField, nextMode );
					clasbproApplyEmailBodyEditorMode( cfg, nextMode );
				} );

				visualField.dataset.clasbproEmailBodyMounted = '1';
			}

			clasbproApplyEmailBodyEditorMode( cfg, clasbproGetEmailBodyMode( modeField ) );
		} );
	}

	function clasbproOpenSettingsTabFromHash() {
		var h = window.location.hash || '';
		if ( h.indexOf( '#clasbpro-tab-' ) !== 0 ) {
			return;
		}
		var key = h.slice( '#clasbpro-tab-'.length );
		var isEmailSubtab = key.indexOf( 'field_clasbpro_email_subtab_' ) === 0;
		var isHelpSubtab = key.indexOf( 'field_clasbpro_help_subtab_' ) === 0;
		if (
			! /^field_clasbpro_tab_[a-z0-9_]+$/i.test( key ) &&
			! isEmailSubtab &&
			! isHelpSubtab
		) {
			return;
		}
		if ( isEmailSubtab ) {
			var emailsTab = document.querySelector(
				'.acf-tab-button[data-key="field_clasbpro_tab_emails"]'
			);
			if ( emailsTab && typeof emailsTab.click === 'function' ) {
				emailsTab.click();
			}
			clasbproInitEmailSubtabs();
			if ( EMAIL_SUBTAB_MAP[ key ] ) {
				clasbproActivateEmailSubtab( EMAIL_SUBTAB_MAP[ key ] );
			}
			return;
		}
		if ( isHelpSubtab ) {
			var helpTab = document.querySelector(
				'.acf-tab-button[data-key="field_clasbpro_tab_help"]'
			);
			if ( helpTab && typeof helpTab.click === 'function' ) {
				helpTab.click();
			}
			clasbproInitHelpSubtabs();
			if ( HELP_SUBTAB_MAP[ key ] ) {
				clasbproActivateHelpSubtab( HELP_SUBTAB_MAP[ key ] );
			}
			return;
		}
		if ( key === 'field_clasbpro_tab_developer' ) {
			var legacyHelpTab = document.querySelector(
				'.acf-tab-button[data-key="field_clasbpro_tab_help"]'
			);
			if ( legacyHelpTab && typeof legacyHelpTab.click === 'function' ) {
				legacyHelpTab.click();
			}
			clasbproInitHelpSubtabs( 'developer' );
			window.location.hash = 'clasbpro-tab-field_clasbpro_help_subtab_developer';
			return;
		}
		var btn = document.querySelector( '.acf-tab-button[data-key="' + key + '"]' );
		if ( btn && typeof btn.click === 'function' ) {
			btn.click();
		}
		if ( key === 'field_clasbpro_tab_emails' ) {
			clasbproInitEmailSubtabs();
		}
		if ( key === 'field_clasbpro_tab_help' ) {
			clasbproInitHelpSubtabs();
		}
	}

	function clasbproScheduleTabFromHash() {
		clasbproOpenSettingsTabFromHash();
		clasbproInitEmailSubtabs();
		clasbproInitHelpSubtabs();
		window.setTimeout( clasbproOpenSettingsTabFromHash, 0 );
		window.setTimeout( function () {
			clasbproOpenSettingsTabFromHash();
			clasbproSyncMainSettingsTabState();
			clasbproInitStripeSettingsUi();
			clasbproInitEmailSubtabs();
			clasbproInitHelpSubtabs();
			clasbproMountEmailBodyEditors();
			clasbproMountEmailTestRuleSelects();
			clasbproBindEmailTestSendButtons();
		}, 250 );
	}

	function clasbproMountEmailTestRuleSelects() {
		document.querySelectorAll( '.clasbpro-email-test-rule-picker[data-rules]' ).forEach( function ( picker ) {
			if ( picker.dataset.clasbproRuleSelectMounted === '1' ) {
				return;
			}

			var rules = [];
			try {
				rules = JSON.parse( picker.getAttribute( 'data-rules' ) || '[]' );
			} catch ( error ) {
				rules = [];
			}

			var selectId = picker.getAttribute( 'data-rule-select-id' ) || '';
			var mount = picker.querySelector( '.clasbpro-email-test-rule-select-mount' );
			if ( ! mount || ! selectId ) {
				return;
			}

			var select = document.createElement( 'select' );
			select.id = selectId;

			var blank = document.createElement( 'option' );
			blank.value = '';
			blank.textContent =
				picker.getAttribute( 'data-select-placeholder' ) || '— Select —';
			select.appendChild( blank );

			rules.forEach( function ( rule ) {
				if ( ! rule || ! rule.value ) {
					return;
				}
				var option = document.createElement( 'option' );
				option.value = rule.value;
				option.textContent = rule.label || rule.value;
				select.appendChild( option );
			} );

			mount.appendChild( select );
			picker.dataset.clasbproRuleSelectMounted = '1';
		} );
	}

	function clasbproBindEmailTestSendButtons() {
		document.querySelectorAll( '.clasbpro-email-test-send-btn' ).forEach( function ( btn ) {
			if ( btn.dataset.clasbproTestSendBound === '1' ) {
				return;
			}
			btn.dataset.clasbproTestSendBound = '1';
			btn.addEventListener( 'click', function () {
				var select = document.querySelector( btn.getAttribute( 'data-rule-select' ) || '' );
				var ruleKey = select && select.value ? select.value : '';
				if ( ! ruleKey ) {
					window.alert(
						btn.getAttribute( 'data-select-rule-msg' ) || 'Select a rule before sending a test.'
					);
					return;
				}
				var baseUrl = btn.getAttribute( 'data-base-url' );
				if ( ! baseUrl ) {
					return;
				}
				var url = new URL( baseUrl, window.location.href );
				url.searchParams.set( 'rule_key', ruleKey );
				window.location.href = url.toString();
			} );
		} );
	}

	function clasbproBootSettingsUi() {
		clasbproBindScheduledEmailControlGuards();
		clasbproBindStripeKeyPanelGuards();
		clasbproBindMainSettingsTabHooks();
		clasbproSyncMainSettingsTabState();
		clasbproInitStripeSettingsUi();
		clasbproScheduleStripeSettingsUi();
		clasbproMountScheduledEmailControlRows();
		clasbproScheduleTabFromHash();
		clasbproBindEmailTabHooks();
		if ( clasbproIsEmailsTabActive() ) {
			clasbproMountEmailBodyEditors();
		}
		if ( clasbproIsHelpTabActive() ) {
			clasbproInitHelpSubtabs();
		}
		clasbproMountEmailTestRuleSelects();
		clasbproBindEmailTestSendButtons();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', clasbproBootSettingsUi );
	} else {
		clasbproBootSettingsUi();
	}
	window.addEventListener( 'hashchange', clasbproScheduleTabFromHash );
	window.addEventListener( 'pageshow', function ( event ) {
		if ( ! document.body.classList.contains( 'clasbpro-booking-settings' ) ) {
			return;
		}
		clasbproRestoreStripeTabAfterSave();
		clasbproScheduleStripeSettingsUi();
	} );
} )();
