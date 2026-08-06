<?php
/**
 * Elementor widget: Class Booking with Stripe form.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Shortcode;

defined( 'ABSPATH' ) || exit;

class Widget_Stripe_Booking extends Widget_Base {

	public function get_name() {
		return \IOROOT_STRIPE_BOOKINGS_PRO\Constants::ELEMENTOR_WIDGET;
	}

	public function get_title() {
		return esc_html__( 'Class Booking with Stripe (Pro)', 'class-bookings-with-stripe-pro' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'booking', 'stripe', 'class', 'payment' ];
	}

	public function get_style_depends() {
		return [ \IOROOT_STRIPE_BOOKINGS_PRO\Constants::SCRIPT_FRONTEND ];
	}

	public function get_script_depends() {
		return [ \IOROOT_STRIPE_BOOKINGS_PRO\Constants::SCRIPT_FRONTEND ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Class Booking with Stripe', 'class-bookings-with-stripe-pro' ),
			]
		);

		$this->add_control(
			'source',
			[
				'label'   => esc_html__( 'Class source', 'class-bookings-with-stripe-pro' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => [
					'manual'        => esc_html__( 'Manual: pick a Class', 'class-bookings-with-stripe-pro' ),
					'current_field' => esc_html__( 'Current post field: stripe-booking-id', 'class-bookings-with-stripe-pro' ),
				],
				'description' => esc_html__( 'Use "Current post field" inside loop/off-canvas templates where the source post has an ACF field named stripe-booking-id (internally clasbpro_class_stripe_id).', 'class-bookings-with-stripe-pro' ),
			]
		);

		$this->add_control(
			'class_id',
			[
				'label'       => esc_html__( 'Stripe class', 'class-bookings-with-stripe-pro' ),
				'type'        => Controls_Manager::SELECT,
				'options'     => $this->get_class_options(),
				'description' => esc_html__( 'Choose which class this booking form should sell.', 'class-bookings-with-stripe-pro' ),
				'condition'   => [
					'source' => 'manual',
				],
			]
		);

		$this->add_control(
			'current_field_key',
			[
				'label'       => esc_html__( 'Field key on current post', 'class-bookings-with-stripe-pro' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'clasbpro_class_stripe_id',
				'description' => esc_html__( 'ACF/meta field that stores the Class ID (for example: 1209).', 'class-bookings-with-stripe-pro' ),
				'condition'   => [
					'source' => 'current_field',
				],
			]
		);

		$this->add_control(
			'show_heading',
			[
				'label'        => esc_html__( 'Show class heading', 'class-bookings-with-stripe-pro' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Show', 'class-bookings-with-stripe-pro' ),
				'label_off'    => esc_html__( 'Hide', 'class-bookings-with-stripe-pro' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'class-bookings-with-stripe-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'form_max_width',
			[
				'label'      => esc_html__( 'Form max width', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [
						'min' => 280,
						'max' => 1200,
					],
					'%'  => [
						'min' => 30,
						'max' => 100,
					],
					'vw' => [
						'min' => 20,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form' => 'max-width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .cbfs-status' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_padding',
			[
				'label'      => esc_html__( 'Form padding', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					/* Outer wrapper only — card chrome lives on .cbfs-form__surface */
					'{{WRAPPER}} .cbfs-form.cbfs-form--layout-modern' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .cbfs-form:not(.cbfs-form--layout-modern), {{WRAPPER}} .cbfs-status' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_fields_gap',
			[
				'label'      => esc_html__( 'Fields spacing', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form__form' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_row_internal_gap',
			[
				'label'      => esc_html__( 'Label/input spacing', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 30,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form__row' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'form_background_color',
			[
				'label'     => esc_html__( 'Form background', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					/* Paint the card, not the outer shell (modern layout stays visible) */
					'{{WRAPPER}} .cbfs-form__surface' => 'background: {{VALUE}};',
					'{{WRAPPER}} .cbfs-form:not(.cbfs-form--layout-modern), {{WRAPPER}} .cbfs-status' => 'background: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'form_title_color',
			[
				'label'     => esc_html__( 'Title color', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cbfs-form__title, {{WRAPPER}} .cbfs-status__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'stripe_button_style_heading',
			[
				'label'     => esc_html__( 'Book & pay button', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'stripe_button_typography',
				'label'    => esc_html__( 'Typography', 'class-bookings-with-stripe-pro' ),
				'selector' => '{{WRAPPER}} .cbfs-form .cbfs-form__button',
			]
		);

		$this->add_control(
			'stripe_button_text_color',
			[
				'label'     => esc_html__( 'Text color', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'stripe_button_background_color',
			[
				'label'     => esc_html__( 'Background color', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'stripe_button_border',
				'label'    => esc_html__( 'Border', 'class-bookings-with-stripe-pro' ),
				'selector' => '{{WRAPPER}} .cbfs-form .cbfs-form__button',
			]
		);

		$this->add_responsive_control(
			'stripe_button_border_radius',
			[
				'label'      => esc_html__( 'Border radius', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'stripe_button_padding',
			[
				'label'      => esc_html__( 'Padding', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'external_button_style_heading',
			[
				'label'     => esc_html__( 'External link button', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'external_button_typography',
				'label'    => esc_html__( 'Typography', 'class-bookings-with-stripe-pro' ),
				'selector' => '{{WRAPPER}} .cbfs-form .cbfs-form__button--link',
			]
		);

		$this->add_control(
			'external_button_text_color',
			[
				'label'     => esc_html__( 'Text color', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button--link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'external_button_background_color',
			[
				'label'     => esc_html__( 'Background color', 'class-bookings-with-stripe-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button--link' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'external_button_border',
				'label'    => esc_html__( 'Border', 'class-bookings-with-stripe-pro' ),
				'selector' => '{{WRAPPER}} .cbfs-form .cbfs-form__button--link',
			]
		);

		$this->add_responsive_control(
			'external_button_border_radius',
			[
				'label'      => esc_html__( 'Border radius', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button--link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'external_button_padding',
			[
				'label'      => esc_html__( 'Padding', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .cbfs-form .cbfs-form__button--link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	private function get_class_options(): array {
		$options = [ '' => esc_html__( '— Select a class —', 'class-bookings-with-stripe-pro' ) ];
		$posts   = get_posts( [
			'post_type'      => CPT::CLASS_PT,
			'post_status'    => [ 'publish', 'draft', 'private' ],
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		foreach ( $posts as $post ) {
			$options[ (string) $post->ID ] = $post->post_title;
		}
		return $options;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = (string) ( $settings['source'] ?? 'manual' );
		$class_id = 0;

		if ( 'current_field' === $source ) {
			$field_key = sanitize_key( (string) ( $settings['current_field_key'] ?? 'clasbpro_class_stripe_id' ) );
			$post_id   = get_the_ID();
			if ( $post_id && $field_key ) {
				$value = function_exists( 'get_field' ) ? get_field( $field_key, $post_id ) : get_post_meta( $post_id, $field_key, true );
				if ( null === $value || '' === $value ) {
					foreach ( [ 'yoga_class_stripe_id', 'clasbpro_booking_id', 'yoga_booking_id' ] as $legacy_key ) {
						$legacy_value = function_exists( 'get_field' ) ? get_field( $legacy_key, $post_id ) : get_post_meta( $post_id, $legacy_key, true );
						if ( null !== $legacy_value && '' !== $legacy_value ) {
							$value = $legacy_value;
							break;
						}
					}
				}
				if ( is_scalar( $value ) ) {
					$class_id = absint( ltrim( (string) $value, '#' ) );
				}
			}
		} else {
			$class_id = (int) ( $settings['class_id'] ?? 0 );
		}

		if ( ! $class_id ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="cbfs-form cbfs-form--error">' . esc_html__( 'No Class ID found. Pick a class manually or ensure the current post has clasbpro_class_stripe_id (or legacy yoga_class_stripe_id) set.', 'class-bookings-with-stripe-pro' ) . '</div>';
			}
			return;
		}

		$booking_html = Shortcode::render_booking( [
			'class_id' => (string) absint( $class_id ),
			'heading'  => ( ( $settings['show_heading'] ?? 'yes' ) === 'yes' ) ? '1' : '0',
		] );
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Shortcode::render_booking() templates.
		echo $booking_html;
	}
}
