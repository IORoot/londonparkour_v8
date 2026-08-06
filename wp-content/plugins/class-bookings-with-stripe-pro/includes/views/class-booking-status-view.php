<?php
/**
 * Booking status view — composable blocks for [clasbpro_booking_status].
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

class Booking_Status_View extends Abstract_View {

	public string $type;

	/** @var 'booking'|'coupon' */
	public string $kind;

	public string $session_id;

	public string $status_token;

	public int $purchase_id;

	public string $reason;

	public string $msg;

	public string $origin;

	/** @var array<string, mixed>|null */
	public ?array $booking;

	/** @var array<string, mixed>|null */
	public ?array $purchase;

	/** @var array<string, string> */
	public array $atts;

	/** @var array<string, string> */
	public array $reason_messages;

	/**
	 * @param array<string, mixed> $args
	 */
	public function __construct( array $args ) {
		$this->type         = (string) ( $args['type'] ?? 'success' );
		$this->kind         = ( 'coupon' === ( $args['kind'] ?? '' ) ) ? 'coupon' : 'booking';
		$this->session_id   = (string) ( $args['session_id'] ?? '' );
		$this->status_token = (string) ( $args['status_token'] ?? '' );
		$this->purchase_id  = (int) ( $args['purchase_id'] ?? 0 );
		$this->reason       = (string) ( $args['reason'] ?? '' );
		$this->msg          = (string) ( $args['msg'] ?? '' );
		$this->origin       = (string) ( $args['origin'] ?? '' );
		$this->booking      = isset( $args['booking'] ) && is_array( $args['booking'] ) ? $args['booking'] : null;
		$this->purchase     = isset( $args['purchase'] ) && is_array( $args['purchase'] ) ? $args['purchase'] : null;
		$this->atts         = (array) ( $args['atts'] ?? [] );

		$this->reason_messages = [
			'capacity_full'   => __( "Sorry — that class just filled up while you were booking. Please try a different date.", CLASBOWPRO_TEXT_DOMAIN ),
			'class_inactive'  => __( 'Bookings for this class are currently unavailable. Please check back soon.', CLASBOWPRO_TEXT_DOMAIN ),
			'date_invalid'    => __( 'That date is no longer available. Please choose another.', CLASBOWPRO_TEXT_DOMAIN ),
			'class_not_found' => __( 'We could not find that class. It may have been removed.', CLASBOWPRO_TEXT_DOMAIN ),
			'stripe_error'    => __( 'We could not connect to our payment provider. Please try again in a moment.', CLASBOWPRO_TEXT_DOMAIN ),
			'validation'      => __( 'Some details were missing or invalid. Please check the form and try again.', CLASBOWPRO_TEXT_DOMAIN ),
			'internal'        => __( 'Something went wrong on our end. Please try again — your card has not been charged.', CLASBOWPRO_TEXT_DOMAIN ),
		];
		$this->reason_messages = (array) apply_filters(
			'clasbpro_status_reason_messages',
			$this->reason_messages,
			$this->type,
			$this->reason,
			$this->booking,
			$this->kind,
			$this->purchase
		);
	}

	protected function get_layout_name(): string {
		return 'booking-status';
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_required_js_markers(): array {
		return [];
	}

	public function is_coupon(): bool {
		return 'coupon' === $this->kind;
	}

	public function get_variant(): string {
		if ( 'cancelled' === $this->type ) {
			return 'cancelled';
		}
		if ( 'error' === $this->type ) {
			return 'error';
		}
		if ( $this->is_coupon() ) {
			if ( $this->purchase && 'paid' === ( $this->purchase['status'] ?? '' ) ) {
				return 'success-paid';
			}
			if ( '' !== $this->session_id || $this->purchase_id > 0 ) {
				return 'success-pending';
			}

			return 'success-fallback';
		}
		if ( $this->booking && 'paid' === ( $this->booking['status'] ?? '' ) ) {
			return 'success-paid';
		}
		if ( '' !== $this->session_id ) {
			return 'success-pending';
		}

		return 'success-fallback';
	}

	public function get_status_class(): string {
		if ( 'success' !== $this->type ) {
			return $this->type;
		}
		if ( $this->is_coupon() ) {
			return (string) ( $this->purchase['status'] ?? 'pending' );
		}

		return (string) ( $this->booking['status'] ?? 'pending' );
	}

	public function get_reason_message(): string {
		$default = $this->is_coupon()
			? __( 'Something went wrong while purchasing your coupon. Please try again.', CLASBOWPRO_TEXT_DOMAIN )
			: __( 'Something went wrong while taking your booking. Please try again.', CLASBOWPRO_TEXT_DOMAIN );

		return (string) ( $this->reason_messages[ $this->reason ] ?? $default );
	}

	public function should_render( string $slug ): bool {
		$variant = $this->get_variant();

		return match ( $slug ) {
			'details-list'      => 'success-paid' === $variant && ( $this->is_coupon() ? null !== $this->purchase : null !== $this->booking ),
			'pending-spinner'   => 'success-pending' === $variant,
			'try-again-button'  => in_array( $variant, [ 'cancelled', 'error' ], true ),
			'error-detail'      => 'error' === $variant && '' !== $this->msg,
			'hint'              => in_array( $variant, [ 'success-paid', 'error' ], true ),
			default             => true,
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	protected function get_layout_vars(): array {
		return [
			'type'             => $this->type,
			'kind'             => $this->kind,
			'session_id'       => $this->session_id,
			'status_token'     => $this->status_token,
			'purchase_id'      => $this->purchase_id,
			'reason'           => $this->reason,
			'msg'              => $this->msg,
			'origin'           => $this->origin,
			'booking'          => $this->booking,
			'purchase'         => $this->purchase,
			'atts'             => $this->atts,
			'reason_messages'  => $this->reason_messages,
		];
	}

	/**
	 * @param array<string, mixed> $template_args
	 */
	public static function render_html( array $template_args ): string {
		$view        = new self( $template_args );
		$layout_path = Template_Loader::locate_layout( $view->get_layout_name(), 'status' );

		ob_start();
		if ( is_readable( $layout_path ) ) {
			do_action( 'clasbpro_before_render_status_template', $template_args, $layout_path );
			$view->include_layout( $layout_path );
			do_action( 'clasbpro_after_render_status_template', $template_args, $layout_path );
		}
		$html = (string) ob_get_clean();

		return (string) apply_filters( 'clasbpro_status_html', $html, $template_args, $layout_path );
	}
}
