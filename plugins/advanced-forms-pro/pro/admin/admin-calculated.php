<?php
  
  
class AF_Pro_Admin_Calculated {
  
  function __construct() {
    add_filter( 'af/form/settings_fields', array( $this, 'add_form_settings_fields' ), 10, 1 );
  }
  
  function add_form_settings_fields( $field_group ) {
    
    $field_group['fields'][] = array(
      'key' => 'field_form_calculated_tab',
      'label' => '<span class="dashicons dashicons-hammer"></span>Calculated',
      'name' => '',
      'type' => 'tab',
      'instructions' => '',
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'placement' => 'left',
      'endpoint' => 0,
    );

    $field_group['fields'][] = array(
      'key' => 'field_form_calculated_fields',
      'label' => __( 'Calculated fields', 'advanced-forms' ),
      'name' => 'form_calculated_fields',
      'type' => 'af_calculated_admin',
      'instructions' => sprintf(
	      wp_kses(
		      __( 'Set the content for each calculated form here or for more flexibility, you may define computations using <a href="%s" target="_blank">hooked PHP functions</a>. <br> Change the order in which calculated fields are computed by dragging & dropping.', 'advanced-forms' ),
		      [ 'a' => [ 'href' => [], 'target' => [] ] ]
	      ),
	      esc_url( 'https://advancedforms.github.io/guides/using-calculated-fields/#customizing-the-calculated-value' )
      ),
      'required' => 0,
      'conditional_logic' => 0,
      'wrapper' => array (
        'width' => '',
        'class' => '',
        'id' => '',
      ),
      'placement' => 'left',
      'endpoint' => 0,
    );
    
    return $field_group;
    
  }
  
}

return new AF_Pro_Admin_Calculated();