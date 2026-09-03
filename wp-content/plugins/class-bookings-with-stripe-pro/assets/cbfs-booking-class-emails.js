/**
 * Class edit: email override sub-tabs, body editors, external-link metabox toggle.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '#acf-group_clasbpro_class_emails .acf-fields';

	function clasbproClassEmailRoot() {
		return document.querySelector( ROOT_SELECTOR );
	}

	function clasbproClassEmailBody() {
		return document.body;
	}

	function clasbproEmailViewClass( section ) {
		return 'clasbpro-email-view-' + section;
	}

	function clasbproSetClassEmailsActive( isActive ) {
		var body = clasbproClassEmailBody();
		if ( body ) {
			body.classList.toggle( 'clasbpro-class-emails-active', !! isActive );
		}
	}

	function clasbproMountScheduledEmailControlRows() {
		var root = clasbproClassEmailRoot();
		if ( ! root ) {
			return;
		}

		[
			{
				section: 'reminders',
				names: [
					'class_email_reminder_enabled',
					'class_email_reminder_offset_amount',
					'class_email_reminder_offset_unit',
					'class_email_reminder_admin_copy',
				],
			},
			{
				section: 'post-class',
				names: [
					'class_email_post_class_enabled',
					'class_email_post_class_offset_amount',
					'class_email_post_class_offset_unit',
					'class_email_post_class_admin_copy',
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

	function clasbproActivateEmailSubtab( section ) {
		if ( ! section ) {
			return;
		}
		var body = clasbproClassEmailBody();
		if ( ! body ) {
			return;
		}
		body.classList.add( 'clasbpro-email-subtabs-ready' );
		[ 'admin', 'customer', 'reminders', 'post-class' ].forEach( function ( slug ) {
			body.classList.remove( clasbproEmailViewClass( slug ) );
		} );
		body.classList.add( clasbproEmailViewClass( section ) );

		document
			.querySelectorAll( '#acf-group_clasbpro_class_emails .clasbpro-email-subtabs__btn' )
			.forEach( function ( btn ) {
				btn.classList.toggle(
					'is-active',
					btn.getAttribute( 'data-clasbpro-email-section' ) === section
				);
			} );

		var description = document.querySelector(
			'#acf-group_clasbpro_class_emails .clasbpro-email-subtabs-description'
		);
		if ( description ) {
			var activeBtn = document.querySelector(
				'#acf-group_clasbpro_class_emails .clasbpro-email-subtabs__btn[data-clasbpro-email-section="' +
					section +
					'"]'
			);
			description.textContent = activeBtn
				? activeBtn.getAttribute( 'data-clasbpro-description' ) || ''
				: '';
		}

		clasbproMountScheduledEmailControlRows();
		clasbproSyncAllBodyEditorToolbars();

		var root = clasbproClassEmailRoot();
		if ( root ) {
			EMAIL_BODY_EDITOR_FIELDS.forEach( function ( cfg ) {
				var modeField = root.querySelector(
					'.acf-field.clasbpro-email-body-mode-field[data-name="' + cfg.mode + '"]'
				);
				if ( modeField ) {
					clasbproApplyEmailBodyEditorMode(
						cfg,
						clasbproGetEmailBodyMode( modeField )
					);
				}
			} );
		}
	}

	function clasbproInitEmailSubtabs() {
		var nav = document.querySelector(
			'#acf-group_clasbpro_class_emails .clasbpro-email-subtabs'
		);
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
				clasbproActivateEmailSubtab( btn.getAttribute( 'data-clasbpro-email-section' ) );
			} );
		}
		clasbproActivateEmailSubtab( 'admin' );
	}

	var EMAIL_BODY_EDITOR_FIELDS = [
		{
			visual: 'class_email_admin_body',
			html: 'class_email_admin_body_html',
			mode: 'class_email_admin_body_editor_mode',
			overrideMode: 'class_email_admin_mode',
			section: 'admin',
		},
		{
			visual: 'class_email_customer_body',
			html: 'class_email_customer_body_html',
			mode: 'class_email_customer_body_editor_mode',
			overrideMode: 'class_email_customer_mode',
			section: 'customer',
		},
		{
			visual: 'class_email_reminder_body',
			html: 'class_email_reminder_body_html',
			mode: 'class_email_reminder_body_editor_mode',
			overrideMode: 'class_email_reminder_mode',
			section: 'reminders',
		},
		{
			visual: 'class_email_post_class_body',
			html: 'class_email_post_class_body_html',
			mode: 'class_email_post_class_body_editor_mode',
			overrideMode: 'class_email_post_class_mode',
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

	function clasbproIsCustomOverrideMode( overrideModeFieldName ) {
		var root = clasbproClassEmailRoot();
		if ( ! root ) {
			return false;
		}
		var field = root.querySelector(
			'.acf-field[data-name="' + overrideModeFieldName + '"] input:checked'
		);
		return !! ( field && field.value === 'custom' );
	}

	function clasbproGetActiveEmailSection() {
		var body = clasbproClassEmailBody();
		if ( ! body ) {
			return 'admin';
		}
		var match = body.className.match( /clasbpro-email-view-([a-z-]+)/ );
		return match ? match[ 1 ] : 'admin';
	}

	function clasbproSyncBodyEditorToolbarVisibility( cfg ) {
		var root = clasbproClassEmailRoot();
		if ( ! root ) {
			return;
		}
		var toolbar = root.querySelector(
			'.clasbpro-email-editor-toolbar[data-clasbpro-email-body="' + cfg.visual + '"]'
		);
		if ( ! toolbar ) {
			return;
		}
		var isCustom = clasbproIsCustomOverrideMode( cfg.overrideMode );
		toolbar.classList.toggle( 'is-custom-active', isCustom );
	}

	function clasbproSyncAllBodyEditorToolbars() {
		EMAIL_BODY_EDITOR_FIELDS.forEach( clasbproSyncBodyEditorToolbarVisibility );
	}

	function clasbproApplyEmailBodyEditorMode( cfg, mode ) {
		var root = clasbproClassEmailRoot();
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

		var isActiveTab = cfg.section === clasbproGetActiveEmailSection();
		var isCustom    = clasbproIsCustomOverrideMode( cfg.overrideMode );

		if ( ! isCustom || ! isActiveTab ) {
			if ( toolbar ) {
				toolbar.classList.remove( 'is-custom-active' );
			}
			visualField.style.display = '';
			htmlField.style.display   = '';
			visualField.classList.remove( 'is-html-mode-hidden' );
			htmlField.classList.remove( 'is-html-mode-visible' );
			return;
		}

		if ( toolbar ) {
			toolbar.classList.add( 'is-custom-active' );
		}

		var isHtmlField = clasbproEmailBodyModeUsesHtmlField( mode );
		visualField.style.display = isHtmlField ? 'none' : 'block';
		htmlField.style.display   = isHtmlField ? 'block' : 'none';
		visualField.classList.toggle( 'is-html-mode-hidden', isHtmlField );
		htmlField.classList.toggle( 'is-html-mode-visible', isHtmlField );

		var copyBar = htmlField.querySelector( '.clasbpro-email-html-copy-bar' );
		if ( copyBar ) {
			copyBar.hidden = mode !== 'raw';
		}

		if ( toolbar ) {
			toolbar.querySelectorAll( '.clasbpro-email-editor-toggle__btn' ).forEach( function ( btn ) {
				btn.classList.toggle( 'is-active', btn.getAttribute( 'data-mode' ) === mode );
				btn.setAttribute(
					'aria-selected',
					btn.getAttribute( 'data-mode' ) === mode ? 'true' : 'false'
				);
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
		var root = clasbproClassEmailRoot();
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
					'clasbpro-email-editor-toolbar clasbpro-email-editor-toolbar--' +
					cfg.section;
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
			clasbproSyncBodyEditorToolbarVisibility( cfg );
		} );
	}

	function clasbproIsOverrideModeFieldName( fieldName ) {
		return /^(class_email_(admin|customer|reminder|post_class)_mode)$/.test( fieldName );
	}

	function clasbproBindOverrideModeWatch() {
		var root = clasbproClassEmailRoot();
		if ( ! root || root.dataset.clasbproOverrideModeBound === '1' ) {
			return;
		}
		root.dataset.clasbproOverrideModeBound = '1';
		root.addEventListener( 'change', function ( event ) {
			var input = event.target;
			var field = input && input.closest ? input.closest( '.acf-field[data-name]' ) : null;
			if ( ! field ) {
				return;
			}
			var fieldName = field.getAttribute( 'data-name' ) || '';
			if ( ! clasbproIsOverrideModeFieldName( fieldName ) ) {
				return;
			}
			window.setTimeout( function () {
				clasbproMountEmailBodyEditors();
				clasbproSyncAllBodyEditorToolbars();
				EMAIL_BODY_EDITOR_FIELDS.forEach( function ( cfg ) {
					var modeField = root.querySelector(
						'.acf-field.clasbpro-email-body-mode-field[data-name="' + cfg.mode + '"]'
					);
					if ( modeField ) {
						clasbproApplyEmailBodyEditorMode(
							cfg,
							clasbproGetEmailBodyMode( modeField )
						);
					}
				} );
			}, 50 );
		} );
	}

	function clasbproGetScheduleType() {
		var field = document.querySelector(
			'.acf-field[data-name="schedule_type"] input:checked, .acf-field[data-name="schedule_type"] select'
		);
		if ( ! field ) {
			return '';
		}
		return field.value || '';
	}

	function clasbproToggleClassEmailsMetabox() {
		var box = document.getElementById( 'acf-group_clasbpro_class_emails' );
		if ( ! box ) {
			return;
		}
		var hide = clasbproGetScheduleType() === 'external_link';
		box.style.display = hide ? 'none' : '';
		clasbproSetClassEmailsActive( ! hide && !! clasbproClassEmailRoot() );
	}

	function clasbproBindResetButtons() {
		document
			.querySelectorAll( '#acf-group_clasbpro_class_emails .clasbpro-class-email-reset-btn' )
			.forEach( function ( btn ) {
				if ( btn.dataset.clasbproResetBound === '1' ) {
					return;
				}
				btn.dataset.clasbproResetBound = '1';
				btn.addEventListener( 'click', function ( event ) {
					var message = btn.getAttribute( 'data-clasbpro-confirm' ) || '';
					if ( message && ! window.confirm( message ) ) {
						event.preventDefault();
					}
				} );
			} );
	}

	function clasbproBootClassEmailsUi() {
		if ( ! clasbproClassEmailRoot() ) {
			return;
		}
		clasbproBindScheduledEmailControlGuards();
		clasbproToggleClassEmailsMetabox();
		clasbproInitEmailSubtabs();
		clasbproMountScheduledEmailControlRows();
		clasbproMountEmailBodyEditors();
		clasbproBindOverrideModeWatch();
		clasbproSyncAllBodyEditorToolbars();
		clasbproBindResetButtons();
	}

	function clasbproBindAcfHooks() {
		if ( typeof acf === 'undefined' || ! acf.addAction ) {
			return;
		}
		acf.addAction( 'ready', clasbproBootClassEmailsUi );
		acf.addAction( 'append', clasbproBootClassEmailsUi );
		acf.addAction( 'show_field', function ( field ) {
			if ( ! field || ! field.get ) {
				return;
			}
			var name = field.get( 'name' ) || '';
			var shouldSync = EMAIL_BODY_EDITOR_FIELDS.some( function ( cfg ) {
				return (
					name === cfg.visual ||
					name === cfg.html ||
					name === cfg.mode ||
					name === cfg.overrideMode
				);
			} );
			if ( ! shouldSync ) {
				return;
			}
			window.setTimeout( function () {
				clasbproMountEmailBodyEditors();
				clasbproSyncAllBodyEditorToolbars();
			}, 0 );
		} );
	}

	function clasbproBindScheduleTypeWatch() {
		document.addEventListener( 'change', function ( event ) {
			var target = event.target;
			if (
				target &&
				target.closest &&
				target.closest( '.acf-field[data-name="schedule_type"]' )
			) {
				clasbproToggleClassEmailsMetabox();
			}
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			clasbproBindScheduleTypeWatch();
			clasbproBindAcfHooks();
			clasbproBootClassEmailsUi();
		} );
	} else {
		clasbproBindScheduleTypeWatch();
		clasbproBindAcfHooks();
		clasbproBootClassEmailsUi();
	}
} )();
