'use strict';

var EasyWPSMTP = window.EasyWPSMTP || {};
EasyWPSMTP.Admin = EasyWPSMTP.Admin || {};

/**
 * Easy WP SMTP Admin area AI MCP module.
 *
 * @since 2.15.0
 */
EasyWPSMTP.Admin.AiMcp = EasyWPSMTP.Admin.AiMcp || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 2.15.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine. DOM is not ready yet, use only to init something.
		 *
		 * @since 2.15.0
		 */
		init: function() {

			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 2.15.0
		 */
		ready: function() {

			app.events();
		},

		/**
		 * Bind events.
		 *
		 * @since 2.15.0
		 */
		events: function() {

			$( document )
				.on( 'click', '.easy-wp-smtp-ai-mcp-wpvibe-button[data-action="install"]', app.onInstallClick )
				.on( 'click', '.easy-wp-smtp-ai-mcp-wpvibe-button[data-action="activate"]', app.onActivateClick );
		},

		/**
		 * POST to the Easy WP SMTP admin-ajax dispatcher with the nonce attached.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} extra Task-specific fields merged into the request.
		 *
		 * @returns {object} The jQuery jqXHR promise.
		 */
		post: function( extra ) {

			var data = $.extend( {
				action: 'easy_wp_smtp_ajax',
				nonce: window.easy_wp_smtp.nonce,
			}, extra || {} );

			return $.post( window.easy_wp_smtp.ajax_url, data );
		},

		/**
		 * Install button: install & activate WPVibe, then reload.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} event The click event.
		 */
		onInstallClick: function( event ) {

			event.preventDefault();

			app.runInstallerTask( $( event.currentTarget ), 'about_plugin_install' );
		},

		/**
		 * Activate button: activate WPVibe, then reload.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} event The click event.
		 */
		onActivateClick: function( event ) {

			event.preventDefault();

			app.runInstallerTask( $( event.currentTarget ), 'about_plugin_activate' );
		},

		/**
		 * Run an install/activate task, then reload on success or surface an
		 * inline error on failure. A reload lets PHP re-render the next state's
		 * CTA. WP returns logical failures with HTTP 200 + success:false, so a
		 * failed task lands in .done and must be checked explicitly.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} $button Button element that was clicked.
		 * @param {string} task    AJAX task — install or activate.
		 */
		runInstallerTask: function( $button, task ) {

			app.clearError( $button );
			app.setLoading( $button );

			app.post( { task: task, plugin: $button.data( 'plugin' ) } )
				.done( function( response ) {

					if ( response && response.success ) {
						window.location.reload();
						return;
					}

					// Install partially succeeded (installed but activation failed):
					// the plugin state changed, so reload to render the activate CTA.
					if ( task === 'about_plugin_install' && response && response.data && response.data.basename ) {
						window.location.reload();
						return;
					}

					app.showError( $button );
				} )
				.fail( function() {

					app.showError( $button );
				} );
		},

		/**
		 * Show the loading spinner on the button.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} $button Button element.
		 */
		setLoading: function( $button ) {

			$button
				.addClass( 'easy-wp-smtp-btn--loading' )
				.prop( 'disabled', true );
		},

		/**
		 * Restore the button to its initial state and show the inline error.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} $button Button element.
		 */
		showError: function( $button ) {

			$button
				.removeClass( 'easy-wp-smtp-btn--loading' )
				.prop( 'disabled', false );

			var $row = $button.closest( '.easy-wp-smtp-ai-mcp-cta-row' );
			var $error = $row.siblings( '.easy-wp-smtp-ai-mcp-install-error' );

			if ( ! $error.length ) {
				$error = $( '<p class="easy-wp-smtp-ai-mcp-install-error" role="alert"></p>' );
				$row.after( $error );
			}

			$error.text( window.easy_wp_smtp_ai_mcp.error_text );
		},

		/**
		 * Remove a previously shown inline error before a new attempt.
		 *
		 * @since 2.15.0
		 *
		 * @param {object} $button Button element.
		 */
		clearError: function( $button ) {

			$button
				.closest( '.easy-wp-smtp-ai-mcp-cta-row' )
				.siblings( '.easy-wp-smtp-ai-mcp-install-error' )
				.remove();
		},
	};

	return app;
}( document, window, jQuery ) );

EasyWPSMTP.Admin.AiMcp.init();
