<?php
/**
 * Elementor widget: global schedule calendar.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use IOROOT_STRIPE_BOOKINGS_PRO\CPT;
use IOROOT_STRIPE_BOOKINGS_PRO\Shortcode;

defined( 'ABSPATH' ) || exit;

class Widget_Stripe_Schedule extends Widget_Base {

	public function get_name() {
		return \IOROOT_STRIPE_BOOKINGS_PRO\Constants::ELEMENTOR_SCHEDULE_WIDGET;
	}

	public function get_title() {
		return esc_html__( 'Class Schedule Calendar (Pro)', 'class-bookings-with-stripe-pro' );
	}

	public function get_icon() {
		return 'eicon-calendar';
	}

	public function get_categories() {
		return [ 'basic' ];
	}

	public function get_keywords() {
		return [ 'schedule', 'calendar', 'class', 'booking', 'stripe' ];
	}

	public function get_style_depends() {
		return [
			\IOROOT_STRIPE_BOOKINGS_PRO\Constants::SCRIPT_FRONTEND,
			'clasbpro-global-schedule',
			'clasbpro-appointment-calendar',
		];
	}

	public function get_script_depends() {
		return [
			\IOROOT_STRIPE_BOOKINGS_PRO\Constants::SCRIPT_FRONTEND,
			'clasbpro-calendar-core',
			'clasbpro-appointment-calendar',
			'clasbpro-class-date-calendar',
			'clasbpro-global-schedule',
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'Schedule calendar', 'class-bookings-with-stripe-pro' ),
			]
		);

		$this->add_control(
			'class_source',
			[
				'label'       => esc_html__( 'Classes shown', 'class-bookings-with-stripe-pro' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'settings',
				'options'     => [
					'settings' => esc_html__( 'Use plugin settings (Result pages)', 'class-bookings-with-stripe-pro' ),
					'manual'   => esc_html__( 'Choose classes for this widget', 'class-bookings-with-stripe-pro' ),
				],
				'description' => esc_html__( 'Plugin settings are configured under Classes → Settings → Result pages. Pick manual when this page needs a different class list.', 'class-bookings-with-stripe-pro' ),
			]
		);

		$this->add_control(
			'class_ids',
			[
				'label'       => esc_html__( 'Classes', 'class-bookings-with-stripe-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_class_options(),
				'label_block' => true,
				'description' => esc_html__( 'Inactive classes are hidden on the calendar front end.', 'class-bookings-with-stripe-pro' ),
				'condition'   => [
					'class_source' => 'manual',
				],
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
			'schedule_max_width',
			[
				'label'      => esc_html__( 'Calendar max width', 'class-bookings-with-stripe-pro' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vw' ],
				'range'      => [
					'px' => [
						'min' => 320,
						'max' => 1400,
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
					'{{WRAPPER}} .cbfs-schedule' => 'max-width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * @return array<string, string>
	 */
	private function get_class_options(): array {
		$options = [];
		$posts   = get_posts( [
			'post_type'      => CPT::CLASS_PT,
			'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		foreach ( $posts as $post ) {
			$label = $post->post_title;
			$active = function_exists( 'get_field' ) ? (bool) get_field( 'class_active', $post->ID ) : true;
			if ( ! $active ) {
				$label .= ' (' . __( 'inactive', 'class-bookings-with-stripe-pro' ) . ')';
			}
			$options[ (string) $post->ID ] = $label;
		}

		return $options;
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$source   = (string) ( $settings['class_source'] ?? 'settings' );
		$atts     = [];

		if ( 'manual' === $source ) {
			$raw_ids = $settings['class_ids'] ?? [];
			if ( ! is_array( $raw_ids ) ) {
				$raw_ids = [] !== $raw_ids && null !== $raw_ids ? [ $raw_ids ] : [];
			}
			$ids = array_values( array_filter( array_map( 'absint', $raw_ids ) ) );
			if ( empty( $ids ) ) {
				if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
					echo '<div class="cbfs-schedule cbfs-schedule--error">' . esc_html__( 'Choose at least one class, or switch to plugin settings.', 'class-bookings-with-stripe-pro' ) . '</div>';
				}
				return;
			}
			$atts['class_ids'] = implode( ',', $ids );
		}

		$html = Shortcode::render_schedule( $atts );
		if (
			\Elementor\Plugin::$instance->editor->is_edit_mode()
			&& str_contains( $html, 'cbfs-schedule--error' )
		) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Shortcode::render_schedule().
			echo $html;
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Shortcode::render_schedule().
		echo $html;
	}
}
