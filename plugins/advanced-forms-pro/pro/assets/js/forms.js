(function($) {

  af.calculated = {
    initialize: function( form ) {
      // Find all calculated fields and set them up
      const $calculated_fields = form.$el.find( '.acf-field-calculated' );

      if (!$calculated_fields.length) {
        return;
      }

      const refreshHandler = function() {
        af.calculated.refresh( form, $calculated_fields );
      };
      refreshHandler();

      // Listen for form changes and refresh the field
      form.$el.change( refreshHandler );

      // Allow triggering of a refresh through an action
      acf.addAction( 'af/field/calculated/update_value', refreshHandler );

      // Having actions for each calculated field is unnecessary now since
      // they all update at the same time with a single request but we need
      // to keep it for backwards compatibility
      $calculated_fields.each(function( _, field ) {
        const $field = $(field);
        const name = $field.attr( 'data-name' );
        const key = $field.attr( 'data-key' );

        acf.addAction( 'af/field/calculated/update_value/name=' + name, refreshHandler );
        acf.addAction( 'af/field/calculated/update_value/key=' + key, refreshHandler );
      })
    },

    refresh: function( form, $fields ) {
      // Prepare AJAX request with field key and serialized form data
      let data = acf.serialize( form.$el );

      data.action = 'af_calculated_field';

      // Pass along all the calculated fields in the form as a comma-separated
      // list of field keys
      data.calculated_fields = $fields.map(function( _, field ) {
        return $(field).attr( 'data-key' );
      }).get().join();

      data = acf.prepare_for_ajax( data );

      // Lock fields to indicate loading
      $fields.each(function( _, field ) {
        af.calculated.lockField( $(field) );
      });

      // Fetch updated field values through AJAX
      $.ajax({
        url: acf.get('ajaxurl'),
        data: data,
        type: 'post',
        success: function( data ){
          // Update field contents
          const values = JSON.parse( data );

          $fields.each(function( _, field ) {
            const $field = $(field);
            const key = $field.attr( 'data-key' );
            const value = values[ key ];
            af.calculated.updateField( form, $field, value );
          });
        },
        complete: function(){
          // Unlock fields again once the request has finished (successfully or not)
          $fields.each(function( _, field ) {
            af.calculated.unlockField( $(field) );
          });
        }
      });
    },

    updateField: function( form, $field, value ) {
      $field.find( 'input.af-calculated-value' ).val( value );
      $field.find( '.af-calculated-content' ).html( value );

      const name = $field.attr( 'data-name' );
      const key = $field.attr( 'data-key' );

      acf.doAction( 'af/field/calculated/value_updated', value, $field, form );
      acf.doAction( 'af/field/calculated/value_updated/name=' + name, value, $field, form );
      acf.doAction( 'af/field/calculated/value_updated/key=' + key, value, $field, form );
    },

    lockField: function( $field ) {
      $field.find( '.af-input' ).css( 'opacity', 0.5 );
    },

    unlockField: function( $field ) {
      $field.find( '.af-input' ).css( 'opacity', 1.0 );
    },
  };

  af.recaptcha = {
    initialize: function( form ) {
      var site_key = af.recaptcha.getSiteKey( form );
      if (site_key === null) {
        return;
      }

      var $container = $('<div class="af-recaptcha-container">').attr('data-size', 'invisible');
      var $submit_wrapper = form.$el.find('.af-submit');
      $submit_wrapper.append( $container );

      // Add a submission step to perform a reCAPTCHA check.
      // A low priority is used to ensure reCAPTCHA runs early (before AJAX)
      af.addSubmissionStep( form, 5, function( callback ) {
        // There is no way of detecting when reCAPTCHA has been closed.
        // Instead we unlock the form after a two seconds in case the user tries again.
        unlockFormTimeout = setTimeout(function() {
          af.unlock( form );
        }, 2000);

        // Triggered after a successful captcha check.
        // Adds the token to a hidden field and continues the submission process.
        var captchaCallback = function(token) {
          // Ensure the form is locked after the captcha succeeds to avoid duplicate submissions
          clearTimeout( unlockFormTimeout );
          af.lock( form );

          var $token_input = $( '<input type="hidden" name="g-recaptcha-response" />' ).val( token );
          form.$el.find( '.acf-hidden' ).append( $token_input );
          callback();
        };

        var recaptcha_widget_id = grecaptcha.render(
          $container.get(0),
          {
            'sitekey': site_key,
            callback: captchaCallback,
          }
        );

        grecaptcha.execute( recaptcha_widget_id );
      });
    },

    getSiteKey: function( form ) {
      var site_key = form.$el.attr( 'data-recaptcha-site-key' );
      if (typeof site_key !== typeof undefined && site_key !== false) {
        return site_key;
      } else {
        return null;
      }
    },
  };

  acf.addAction( 'af/form/setup', af.calculated.initialize );
  acf.addAction( 'af/form/setup', af.recaptcha.initialize );


  // Add post ID to ACF AJAX requests when editing a post
  af.addPostID = function( data ) {
    // Check if data has field key
    if ( ! data.hasOwnProperty( 'field_key' ) ) {
      return data;
    }

    // Find field with key
    var key = data.field_key;
    var $field = $('.af-field[data-key="' + key + '"]');
    if ( ! $field.length ) {
      return data;
    }

    var $post_id_input = $field.siblings( '.acf-hidden' ).find( 'input[name="post_id"]' );
    if ( $post_id_input.length ) {
      data.post_id = $post_id_input.val();
    }

    return data;
  };

  acf.addFilter( 'prepare_for_ajax', af.addPostID );

})(jQuery);