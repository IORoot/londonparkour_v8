<?php
/**
 * Abstract base for composable booking views.
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Abstract_View {

	/** @var array<string, bool> */
	protected static array $rendered_required_markers = [];

	abstract protected function get_layout_name(): string;

	/**
	 * Map component slugs to DOM markers used by front-end validation (WP_DEBUG only).
	 *
	 * @return array<string, string>
	 */
	abstract protected function get_required_js_markers(): array;

	protected function get_style_handle_prefix(): string {
		return 'clasbowi';
	}

	public function should_render( string $slug ): bool {
		return true;
	}

	public function get_component_path( string $slug ): string {
		$path = Template_Loader::locate_component( $this->get_layout_name(), $slug );

		return is_readable( $path ) ? $path : '';
	}

	public function render( string $slug ): void {
		if ( ! $this->should_render( $slug ) ) {
			return;
		}

		$path = $this->get_component_path( $slug );
		if ( '' === $path ) {
			return;
		}

		Template_Loader::enqueue_component_style(
			$this->get_layout_name(),
			$slug,
			$this->get_style_handle_prefix(),
			CLASBOWPRO_URL
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$this->track_rendered_marker( $slug );
		}

		$view = $this;
		include $path;
	}

	/**
	 * Variables extracted into layout / component includes alongside $view.
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function get_layout_vars(): array;

	protected function include_layout( string $path ): void {
		foreach ( $this->get_layout_vars() as $key => $value ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Intentional view variable extraction.
			$$key = $value;
		}

		$view = $this;
		include $path;
	}

	protected function track_rendered_marker( string $slug ): void {
		$markers = $this->get_required_js_markers();
		if ( isset( $markers[ $slug ] ) ) {
			self::$rendered_required_markers[ $markers[ $slug ] ] = true;
		}
	}

	/**
	 * @return array<string, bool>
	 */
	public static function get_rendered_required_markers(): array {
		return self::$rendered_required_markers;
	}
}
