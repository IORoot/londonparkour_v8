<?php
/**
 * Concourse clasbpro theme bootstrap — londonparkour_v8.
 *
 * Loaded when clasbpro theme_source=theme. Keep filters light; prefer style.css.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ensure Concourse CSS prints after packs / calendars / select.
 *
 * Plugin `enqueue_form_select_style` (priority 999) adds clasbpro-theme-pack as a
 * dependency of clasbpro-form-select. If we also make the pack depend on
 * form-select before that runs, WP_Dependencies cycles and OOMs. Run *after*
 * 999, drop the reverse edge, then depend on the component sheets.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! wp_style_is( 'clasbpro-theme-pack', 'registered' ) ) {
			return;
		}

		$styles = wp_styles();
		$handle = 'clasbpro-theme-pack';
		if ( ! isset( $styles->registered[ $handle ] ) ) {
			return;
		}

		if ( isset( $styles->registered['clasbpro-form-select'] ) ) {
			$styles->registered['clasbpro-form-select']->deps = array_values(
				array_filter(
					(array) $styles->registered['clasbpro-form-select']->deps,
					static function ( $dep ) use ( $handle ) {
						return $dep !== $handle;
					}
				)
			);
		}

		$deps = array( 'clasbpro' );
		foreach ( array( 'clasbpro-packs', 'clasbpro-appointment-calendar', 'clasbpro-form-select' ) as $dep ) {
			if ( isset( $styles->registered[ $dep ] ) ) {
				$deps[] = $dep;
			}
		}

		$styles->registered[ $handle ]->deps = array_values( array_unique( $deps ) );
	},
	1000
);

/**
 * Overlay / inline booking CTA — pen hoH6b.
 *
 * @param array<string,string> $labels     Plugin labels.
 * @param array<string,mixed>  $class_data Class payload.
 * @param array<int,mixed>     $dates      Unused.
 * @return array<string,string>
 */
function lp_clasbpro_concourse_booking_labels( array $labels, array $class_data, $dates = array() ): array {
	unset( $dates );
	if ( function_exists( 'lp_booking_form_pay_label' ) ) {
		$labels['book_button'] = lp_booking_form_pay_label( $class_data );
	}

	return $labels;
}
add_filter( 'clasbpro_booking_labels', 'lp_clasbpro_concourse_booking_labels', 10, 3 );
