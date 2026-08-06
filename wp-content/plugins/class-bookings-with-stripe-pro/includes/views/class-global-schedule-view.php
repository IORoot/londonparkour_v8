<?php
/**
 * Global schedule calendar view — week time grid for [clasbpro_schedule].
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

class Global_Schedule_View extends Abstract_View {

	/** @var array<int, int> */
	public array $class_ids;

	public int $weeks_ahead;

	public string $week_monday;

	/** @var array<string, string> */
	public array $atts;

	/** @var array<string, string> */
	public array $labels;

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		$this->class_ids   = array_values( array_map( 'absint', (array) ( $args['class_ids'] ?? [] ) ) );
		$this->weeks_ahead = max( 1, (int) ( $args['weeks_ahead'] ?? Schedule_Calendar::weeks_ahead_cap() ) );
		$this->week_monday = Schedule_Calendar::monday_of_week( (string) ( $args['week_monday'] ?? wp_date( 'Y-m-d' ) ) );
		$this->atts        = (array) ( $args['atts'] ?? [] );

		$this->labels = apply_filters(
			'clasbpro_schedule_labels',
			[
				'all_classes'   => __( 'All', 'class-bookings-with-stripe-pro' ),
				'prev_week'     => __( 'Previous week', 'class-bookings-with-stripe-pro' ),
				'next_week'     => __( 'Next week', 'class-bookings-with-stripe-pro' ),
				'loading'       => __( 'Loading schedule…', 'class-bookings-with-stripe-pro' ),
				'empty'         => __( 'No sessions this week.', 'class-bookings-with-stripe-pro' ),
				'empty_filtered' => __( 'No %s classes this week.', 'class-bookings-with-stripe-pro' ),
				'today'         => __( 'Today', 'class-bookings-with-stripe-pro' ),
				'tomorrow'      => __( 'Tomorrow', 'class-bookings-with-stripe-pro' ),
				'cancelled'     => __( 'Cancelled', 'class-bookings-with-stripe-pro' ),
				'full'          => __( 'Full', 'class-bookings-with-stripe-pro' ),
				'class_full'    => __( 'Class full', 'class-bookings-with-stripe-pro' ),
				'spots_left'    => __( '%1$s/%2$s spots left', 'class-bookings-with-stripe-pro' ),
				'book'          => __( 'Book', 'class-bookings-with-stripe-pro' ),
				'close'         => __( 'Close', 'class-bookings-with-stripe-pro' ),
				'seats_left'    => __( '%d seats left', 'class-bookings-with-stripe-pro' ),
				'seat_left'     => __( '1 seat left', 'class-bookings-with-stripe-pro' ),
			],
			$this->class_ids
		);
	}

	protected function get_layout_name(): string {
		return 'global-schedule';
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_required_js_markers(): array {
		return [
			'schedule-filters' => 'data-cbfs-schedule-filters',
			'schedule-grid'    => 'data-cbfs-schedule-grid',
			'schedule-booking-panel' => 'data-cbfs-schedule-panel',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_layout_vars(): array {
		return [
			'class_ids'   => $this->class_ids,
			'weeks_ahead' => $this->weeks_ahead,
			'week_monday' => $this->week_monday,
			'atts'        => $this->atts,
			'labels'      => $this->labels,
		];
	}

	/**
	 * @param array<string, mixed> $template_args
	 */
	public static function render_html( array $template_args ): string {
		$view        = new self( $template_args );
		$layout_path = Template_Loader::locate_layout( $view->get_layout_name(), 'schedule' );

		ob_start();
		if ( is_readable( $layout_path ) ) {
			do_action( 'clasbpro_before_render_schedule_template', $template_args, $layout_path );
			$view->include_layout( $layout_path );
			do_action( 'clasbpro_after_render_schedule_template', $template_args, $layout_path );
		}
		$html = (string) ob_get_clean();

		return (string) apply_filters( 'clasbpro_schedule_html', $html, $template_args, $layout_path );
	}
}
