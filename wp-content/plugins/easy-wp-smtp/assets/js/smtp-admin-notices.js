/* global easy_wp_smtp_admin_notices, ajaxurl */

/**
 * Easy WP SMTP Admin Notices.
 *
 * @since 2.10.0
 */

'use strict';

var EasyWPSMTPAdminNotices = window.EasyWPSMTPAdminNotices || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 2.10.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 2.10.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 2.10.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 2.10.0
		 */
		events: function() {

			$( '.easy-wp-smtp-notice.is-dismissible' )
				.on( 'click', '.notice-dismiss', app.dismiss );

			$( '.easy-wp-smtp-notice__copy-btn' ).on( 'click', app.copyErrorCode );
		},

		/**
		 * Copy error code to clipboard.
		 *
		 * @since 2.14.0
		 */
		copyErrorCode: function() {

			var $btn = $( this );
			var code = $btn.siblings( 'code' ).text();

			if ( navigator.clipboard ) {
				navigator.clipboard.writeText( code );
			}

			$btn.find( '.easy-wp-smtp-notice__icon-copy' ).hide();
			$btn.find( '.easy-wp-smtp-notice__icon-check' ).show();

			setTimeout( function() {
				$btn.find( '.easy-wp-smtp-notice__icon-check' ).hide();
				$btn.find( '.easy-wp-smtp-notice__icon-copy' ).show();
			}, 2000 );
		},

		/**
		 * Click on the dismiss notice button.
		 *
		 * @since 2.10.0
		 *
		 * @param {object} event Event object.
		 */
		dismiss: function( event ) {

			var $notice = $( this ).closest( '.easy-wp-smtp-notice' );

			// If notice key is not defined, we can't dismiss it permanently.
			if ( $notice.data( 'notice' ) === undefined ) {
				return;
			}

			var $button = $( this );

			$.ajax( {
				url: ajaxurl,
				dataType: 'json',
				type: 'POST',
				data: {
					action: 'easy_wp_smtp_ajax',
					nonce: easy_wp_smtp_admin_notices.nonce,
					task: 'notice_dismiss',
					notice: $notice.data( 'notice' ),
				},
				beforeSend: function() {
					$button.prop( 'disabled', true );
				},
			} );
		},
	};

	return app;

}( document, window, jQuery ) );

// Initialize.
EasyWPSMTPAdminNotices.init();
