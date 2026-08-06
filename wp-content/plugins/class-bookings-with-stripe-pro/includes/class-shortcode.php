<?php
/**
 * Shortcodes: [clasbpro_booking], [clasbpro_booking_status], [clasbpro_schedule].
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Shortcode {

	public static function init(): void {
		add_shortcode( Constants::SHORTCODE_BOOKING, [ self::class, 'render_booking' ] );
		add_shortcode( Constants::SHORTCODE_STATUS, [ self::class, 'render_status' ] );
		add_shortcode( Constants::SHORTCODE_SCHEDULE, [ self::class, 'render_schedule' ] );
		add_shortcode( Constants::SHORTCODE_PACKS, [ self::class, 'render_packs' ] );
		add_shortcode( Constants::SHORTCODE_PACKS_LEGACY, [ self::class, 'render_packs' ] );

		foreach ( Constants::LEGACY_SHORTCODES_BOOKING as $legacy_tag ) {
			add_shortcode( $legacy_tag, [ self::class, 'render_booking' ] );
		}
		foreach ( Constants::LEGACY_SHORTCODES_STATUS as $legacy_tag ) {
			add_shortcode( $legacy_tag, [ self::class, 'render_status' ] );
		}
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public static function render_packs( $atts = [] ): string {
		$atts = shortcode_atts(
			[
				'heading' => '1',
				'id'      => '',
				'ids'     => '',
			],
			(array) $atts,
			Constants::SHORTCODE_PACKS
		);

		$ids = self::parse_pack_ids_attr( (string) $atts['id'] );
		if ( empty( $ids ) ) {
			$ids = self::parse_pack_ids_attr( (string) $atts['ids'] );
		}

		$packs = Packs::get_active_packs( $ids );
		wp_enqueue_style( 'clasbpro' );
		wp_enqueue_style( 'clasbpro-packs' );
		Theme_Loader::enqueue_theme_style();
		wp_enqueue_script( 'clasbpro' );
		wp_enqueue_script( 'clasbpro-packs' );

		$show_heading = '1' === (string) $atts['heading'];
		ob_start();
		$template = Template_Loader::locate_layout( 'packs-list.php', 'packs' );
		include $template;
		return (string) ob_get_clean();
	}

	/**
	 * @return array<int, int>
	 */
	private static function parse_pack_ids_attr( string $raw ): array {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return [];
		}
		$parts = preg_split( '/[\s,]+/', $raw ) ?: [];
		return array_values( array_unique( array_filter( array_map( 'absint', $parts ) ) ) );
	}

	/**
	 * @param array<string, string> $atts
	 */
	public static function render_booking( $atts = [] ): string {
		return self::render_booking_html( (array) $atts );
	}

	/**
	 * @param array<string, string> $atts
	 */
	public static function render_booking_html( array $raw_atts ): string {
		$atts = shortcode_atts(
			[
				'class_id'                 => '0',
				'class_slug'               => '',
				'clasbpro_class_stripe_id' => '',
				'class_stripe_id'          => '',
				'heading'                  => '1',
				'preset_date'              => '',
				'preset_slot_rule_id'      => '',
			],
			$raw_atts,
			Constants::SHORTCODE_BOOKING
		);
		$atts = apply_filters( 'clasbpro_shortcode_booking_atts', $atts );

		$class_id = (int) $atts['class_id'];
		if ( ! $class_id && '' !== (string) $atts['clasbpro_class_stripe_id'] ) {
			$class_id = absint( ltrim( (string) $atts['clasbpro_class_stripe_id'], '#' ) );
		}
		if ( ! $class_id && '' !== (string) $atts['class_stripe_id'] ) {
			$class_id = absint( ltrim( (string) $atts['class_stripe_id'], '#' ) );
		}
		foreach ( [ 'yoga_class_stripe_id', 'yoga_booking_id', 'stripe_booking_id' ] as $legacy_key ) {
			if ( ! $class_id && ! empty( $raw_atts[ $legacy_key ] ) ) {
				$class_id = absint( ltrim( (string) $raw_atts[ $legacy_key ], '#' ) );
				break;
			}
		}
		if ( ! $class_id && $atts['class_slug'] ) {
			$post = get_page_by_path( sanitize_title( $atts['class_slug'] ), OBJECT, CPT::CLASS_PT );
			if ( $post ) {
				$class_id = (int) $post->ID;
			}
		}

		$class_data = $class_id ? Helpers::get_class_data( $class_id ) : null;
		if ( ! $class_data ) {
			return '<div class="cbfs-form cbfs-form--error">' . esc_html__( 'No class selected.', 'class-bookings-with-stripe-pro' ) . '</div>';
		}
		$class_data = apply_filters( 'clasbpro_booking_class_data', $class_data, $atts );

		wp_enqueue_style( 'clasbpro' );
		wp_enqueue_style( 'clasbpro-packs' );
		wp_enqueue_script( 'clasbpro' );
		if ( ! empty( $class_data['is_appointments'] ) ) {
			wp_enqueue_style( 'clasbpro-appointment-calendar' );
			wp_enqueue_script( 'clasbpro-calendar-core' );
			wp_enqueue_script( 'clasbpro-appointment-calendar' );
		} elseif ( ! empty( $class_data['uses_date_calendar'] ) ) {
			wp_enqueue_style( 'clasbpro-appointment-calendar' );
			wp_enqueue_script( 'clasbpro-calendar-core' );
			wp_enqueue_script( 'clasbpro-class-date-calendar' );
		}

		$show_heading = '1' === (string) $atts['heading'];
		if ( ! empty( $class_data['is_appointments'] ) ) {
			$dates           = [];
			$max_seats_today = max( 1, (int) ( $class_data['capacity'] ?? 1 ) );
		} elseif ( ! empty( $class_data['uses_date_calendar'] ) ) {
			$dates           = [];
			$max_seats_today = 0;
		} else {
			$dates_count     = ! empty( $class_data['is_one_off_event'] )
				? 1
				: Helpers::class_upcoming_dates_count( $class_data );
			$dates           = Bookings::next_available_dates( $class_data, $dates_count );
			$max_seats_today = $dates ? max( 0, (int) $dates[0]['remaining'] ) : 0;
		}

		$preset_date = Helpers::normalise_date_string( (string) $atts['preset_date'] );
		if ( $preset_date && empty( $class_data['is_appointments'] ) && empty( $class_data['uses_date_calendar'] ) ) {
			$dates = self::ensure_preset_date_in_list( $dates, $class_data, $preset_date );
			foreach ( $dates as $d ) {
				if ( $d['date'] === $preset_date && empty( $d['cancelled'] ) ) {
					$max_seats_today = max( 0, (int) $d['remaining'] );
					break;
				}
			}
		}

		$template_args = apply_filters(
			'clasbpro_booking_template_args',
			[
				'class_data'      => $class_data,
				'dates'           => $dates,
				'show_heading'    => $show_heading,
				'max_seats_today' => $max_seats_today,
				'atts'            => $atts,
			],
			$atts
		);

		return Booking_Form_View::render_html( $template_args );
	}

	/**
	 * @param array<int, array<string, mixed>> $dates
	 * @param array<string, mixed>             $class_data
	 * @return array<int, array<string, mixed>>
	 */
	private static function ensure_preset_date_in_list( array $dates, array $class_data, string $preset_date ): array {
		foreach ( $dates as $d ) {
			if ( (string) ( $d['date'] ?? '' ) === $preset_date ) {
				return $dates;
			}
		}
		$cancelled = in_array( $preset_date, (array) ( $class_data['cancelled_dates'] ?? [] ), true );
		$mode      = Helpers::cancelled_dates_display( $class_data );
		if ( $cancelled && 'show' !== $mode ) {
			return $dates;
		}
		$remaining = $cancelled ? 0 : Bookings::seats_remaining( $class_data, $preset_date );
		$dates[]   = [
			'date'       => $preset_date,
			'label'      => Helpers::format_date( $preset_date ),
			'remaining'  => $remaining,
			'cancelled'  => $cancelled,
			'selectable' => ! $cancelled && $remaining > 0,
		];
		usort(
			$dates,
			static function ( array $a, array $b ): int {
				return strcmp( (string) $a['date'], (string) $b['date'] );
			}
		);
		return $dates;
	}

	/**
	 * @param array<string, string>|string $atts
	 */
	public static function render_schedule( $atts = [] ): string {
		$raw_atts = (array) $atts;
		$atts     = shortcode_atts(
			[
				'class_ids' => '',
			],
			$raw_atts,
			Constants::SHORTCODE_SCHEDULE
		);
		$atts = apply_filters( 'clasbpro_shortcode_schedule_atts', $atts );

		$override = [];
		if ( '' !== trim( (string) $atts['class_ids'] ) ) {
			$override = REST::sanitize_class_ids_param( (string) $atts['class_ids'] );
		}
		$class_ids = Schedule_Calendar::resolve_class_ids( $override ?: null );
		if ( empty( $class_ids ) ) {
			return '<div class="cbfs-schedule cbfs-schedule--error">' . esc_html__( 'No classes configured for the schedule calendar. Choose classes in Settings → Result pages.', 'class-bookings-with-stripe-pro' ) . '</div>';
		}

		wp_enqueue_style( 'clasbpro-global-schedule' );
		wp_enqueue_script( 'clasbpro-global-schedule' );
		Theme_Loader::enqueue_theme_style();
		wp_enqueue_style( 'clasbpro-appointment-calendar' );
		wp_enqueue_script( 'clasbpro-calendar-core' );
		wp_enqueue_script( 'clasbpro-appointment-calendar' );
		wp_enqueue_script( 'clasbpro-class-date-calendar' );

		$template_args = apply_filters(
			'clasbpro_schedule_template_args',
			[
				'class_ids'    => $class_ids,
				'weeks_ahead'  => Schedule_Calendar::weeks_ahead_cap(),
				'week_monday'  => Schedule_Calendar::monday_of_week( wp_date( 'Y-m-d' ) ),
				'atts'         => $atts,
			],
			$atts
		);

		return Global_Schedule_View::render_html( $template_args );
	}

	/**
	 * @param array<string, string> $atts
	 */
	public static function render_status( $atts = [] ): string {
		$atts = shortcode_atts( [
			'type' => 'success',
		], (array) $atts, 'clasbpro_booking_status' );
		$atts = apply_filters( 'clasbpro_shortcode_status_atts', $atts );

		$type = in_array( $atts['type'], [ 'success', 'cancelled', 'error' ], true ) ? $atts['type'] : 'success';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$session_id   = isset( $_GET['booking'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['booking'] ) ) : '';
		$status_token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['token'] ) ) : '';
		$purchase_id  = isset( $_GET['clasbpro_pack_purchase'] ) ? absint( $_GET['clasbpro_pack_purchase'] ) : 0;
		$reason       = isset( $_GET['reason'] ) ? sanitize_key( wp_unslash( (string) $_GET['reason'] ) ) : '';
		$msg          = isset( $_GET['msg'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['msg'] ) ) : '';
		$origin_raw   = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['from'] ) ) : '';
		$origin       = '' !== $origin_raw ? Helpers::sanitise_internal_url( $origin_raw, home_url( '/' ) ) : home_url( '/' );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		wp_enqueue_style( 'clasbpro' );
		if ( 'success' === $type ) {
			wp_enqueue_script( 'clasbpro' );
		}

		$kind     = 'booking';
		$booking  = null;
		$purchase = null;

		if ( 'success' === $type && $purchase_id > 0 ) {
			$kind = 'coupon';
			if ( Packs::can_view_purchase_status( $purchase_id, $session_id, $status_token ) ) {
				if ( Packs::STATUS_PENDING === Packs::get_purchase_status( $purchase_id ) ) {
					Packs::reconcile_purchase_from_stripe( $purchase_id );
				}
				$purchase = Packs::get_purchase_display( $purchase_id );
				if ( $purchase && Packs::STATUS_PAID === ( $purchase['status'] ?? '' ) ) {
					$promo_id = (string) ( $purchase['promo_id'] ?? '' );
					$email    = (string) ( $purchase['customer_email'] ?? '' );
					$expires  = (int) get_post_meta( $purchase_id, '_clasbpro_pack_expires_at', true );
					if ( '' !== $promo_id && is_email( $email ) ) {
						Packs::set_active_cookie( $promo_id, $email, $expires );
					}
				}
				// Do not load _clasbpro_status_token from meta into the page.
				// Only echo session/token query args the visitor already presented (or session for polling).
				if ( ! $session_id ) {
					$session_id = (string) get_post_meta( $purchase_id, '_clasbpro_stripe_session_id', true );
				}
			}
		} elseif ( 'success' === $type && $session_id ) {
			$booking_id = Bookings::find_by_stripe_session( $session_id );
			$can_view   = $booking_id && (
				current_user_can( 'manage_options' )
				|| Bookings::verify_status_token( $booking_id, $status_token )
			);
			if ( $can_view ) {
				$meta       = Bookings::get_meta( $booking_id );
				$class_data = Helpers::get_class_data( $meta['class_id'] );
				$display    = Bookings::get_booking_display_context( $booking_id, $class_data );
				$booking    = [
					'status'        => $meta['status'],
					'booking_id'    => $booking_id,
					'class_name'    => $class_data['name'] ?? '',
					'class_date'    => Helpers::format_date( $meta['class_date'] ),
					'class_time'    => Helpers::format_time( $display['start_time'] ),
					'location'      => $display['location'],
					'duration'      => (int) $display['duration'],
					'slot_label'    => $display['label'],
					'seats'         => $meta['seats'],
					'amount_total'  => Helpers::format_stripe_amount( (int) $meta['amount_total_pence'] ),
					'customer_name' => $meta['customer_name'],
					'extra_fields'  => Extra_Fields::display_rows( (int) $meta['class_id'], (string) ( $meta['extra_fields_json'] ?? '' ) ),
				];
			}
		}

		$template_args = apply_filters(
			'clasbpro_status_template_args',
			[
				'type'         => $type,
				'kind'         => $kind,
				'session_id'   => $session_id,
				'status_token' => $status_token,
				'purchase_id'  => $purchase_id,
				'reason'       => $reason,
				'msg'          => $msg,
				'origin'       => $origin,
				'booking'      => $booking,
				'purchase'     => $purchase,
				'atts'         => $atts,
			],
			$atts
		);

		return Booking_Status_View::render_html( $template_args );
	}
}
