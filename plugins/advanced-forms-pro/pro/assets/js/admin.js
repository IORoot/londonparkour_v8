(function($, undefined) {
    $(document).ready(function() {

	    if (acf === undefined) {
		    return;
	    }

		acf.addAction( 'af/admin/gutenberg/form_selected', function(data, $block) {
			// Only show post editing settings if post editing is enabled for the selected form
			// If disabled, a message will instead be shown.
			var post_editing_enabled = !!data.form.editing.post;
			$block.find( '.acf-field-af-block-post-editing' ).toggle( post_editing_enabled );
			$block.find( '.acf-field-af-block-post-to-edit' ).toggle( post_editing_enabled );
			$block.find( '.acf-field-af-block-post-editing-disabled-message' ).toggle( !post_editing_enabled );

			// Same for the user editing settings
			var user_editing_enabled = !!data.form.editing.user;
			$block.find( '.acf-field-af-block-user-editing' ).toggle( user_editing_enabled );
			$block.find( '.acf-field-af-block-user-to-edit' ).toggle( user_editing_enabled );
			$block.find( '.acf-field-af-block-user-editing-disabled-message' ).toggle( !user_editing_enabled );
		});
	});
})(jQuery);