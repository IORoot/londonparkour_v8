<?php

class AF_Pro_Core_Recaptcha {
  const SITE_KEY_ATTRIBUTE = 'data-recaptcha-site-key';
  const TOKEN_INPUT_NAME = 'g-recaptcha-response';

  const SCRIPT_HANDLE = 'recaptcha';
  const SCRIPT_URL = 'https://www.google.com/recaptcha/api.js';

  function __construct() {
    add_filter( 'af/form/attributes', array( $this, 'add_site_key_to_form' ), 10, 2 );
    add_action( 'af/form/before_submission', array( $this, 'check_captcha' ), 10, 1 );
    add_action( 'af/form/enqueue', array( $this, 'enqueue' ), 10, 1 );
    add_filter( 'script_loader_tag', array( $this, 'make_enqueue_async'), 10, 3 );

    add_filter( 'af/form/valid_form', array( $this, 'valid_form' ), 10, 1 );
    add_filter( 'af/form/from_post', array( $this, 'form_from_post' ), 10, 2 );
    add_filter( 'af/form/to_post', array( $this, 'form_to_post' ), 10, 2 );
  }

  /**
   * Add reCAPTCHA site key to form attributes.
   * Picked up by JavaScript which runs reCAPTCHA.
   *
   * @since 1.7.0
   *
   */
  function add_site_key_to_form( $attributes, $form ) {
    if ( $form['recaptcha'] ) {
      $attributes[ self::SITE_KEY_ATTRIBUTE ] = self::get_site_key();
    }

    return $attributes;
  }

  /**
   * Validate the generated reCAPTCHA token.
   * Normal users shouldn't come this far as the form won't submit unless the reCAPTCHA has been completed.
   * This function catches users without JS enabled or bots.
   * 
   * @since 1.7.0
   *
   */
  function check_captcha( $form ) {
    if ( ! $form['recaptcha'] ) {
      return;
    }

    $token = isset( $_POST[ self::TOKEN_INPUT_NAME ] ) ? $_POST[ self::TOKEN_INPUT_NAME ] : '';
    $is_valid = $this->verify_token( $token, self::get_secret_key() );
    if ( ! $is_valid ) {
      $error_message = __( 'Captcha is required', 'advanced-forms' );
      af_add_submission_error( $error_message );
    }
  }

  function enqueue( $form ) {
    if ( ! $form['recaptcha'] ) {
      return;
    }

    wp_enqueue_script( self::SCRIPT_HANDLE, self::SCRIPT_URL );
  }

  function make_enqueue_async( $tag, $handle, $src ) {
    if ( self::SCRIPT_HANDLE !== $handle ) {
      return $tag;
    }

    return sprintf( '<script src="%s" async defer></script>', $src );
  }

  /**
   * Verify a generated token against the reCAPTCHA API.
   *
   * @since 1.7.0
   *
   */
  private function verify_token( $token, $secret_key ) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $response = wp_remote_post( $url, array(
      'body' => array(
        'secret' => $secret_key,
        'response' => $token,
      ),
    ));

    if ( is_wp_error( $response ) ) {
      return false;
    }

    $body = json_decode( $response['body'], true );
    return $body['success'];
  }

  static function get_site_key() {
    return get_field( 'field_af_recaptcha_site_key', 'options' );
  }

  static function get_secret_key() {
    return get_field( 'field_af_recaptcha_secret_key', 'options' );
  }

  function valid_form( $form ) {
    $form['recaptcha'] = false;
    
    return $form;
  }

  function form_from_post( $form, $post ) {
    $recpatcha_enabled = get_field( 'form_integrations_recaptcha', $post->ID );
  
    if ( $recpatcha_enabled ) {
      $form['recaptcha'] = true;
    }
    
    return $form;
  }
  
  function form_to_post( $form, $post ) {
    update_field( 'field_form_integrations_recaptcha', $form['recaptcha'], $post->ID );
  }

}

return new AF_Pro_Core_Recaptcha();
