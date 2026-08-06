<?php
/**
 * FilterGrid — a row of filter cells divided by vertical hairlines.
 *
 * Ported from src/stories/Components/FilterGrid/FilterGrid.js.
 *
 * The 3-cell "Concourse" and 4-cell "Tutorials" grids are the same shape with
 * a different cell count — one component driven by a `cells` array, not two.
 *
 * Every cell is a key label above a value row, exactly the shape forms/field
 * and forms/select already own, so each cell mounts one of the two rather than
 * hand-drawing its own key/value text. Both already render the key at the
 * spec's 10px/600/+1 uppercase treatment, so only the grid frame lives here.
 *
 * DELIBERATE DEPARTURE: the source cells carry an `onChange` callback, which
 * has no PHP equivalent. `name` replaces it — a server-rendered filter needs a
 * form field name to submit under. Class strings are unchanged.
 *
 * Given an `action` the grid wraps itself in a real GET form. The design draws
 * NO submit control, so two things carry that job and neither is visible: a
 * `sr-only` submit button (keyboard- and screen-reader-reachable, and the only
 * mechanism when JS is off), and `data-filter-form`, which
 * assets/js/elements/FilterForm.js reads to submit on a select change. Text
 * inputs are deliberately not auto-submitted — that would reload mid-word.
 * Without `action` the grid renders exactly as before, form-less.
 *
 * @param string $args['action'] Form target. Omit for the plain, inert grid.
 * @param string $args['submit'] sr-only submit label. Default 'Apply filters'.
 * @param array  $args['hidden'] name => value pairs carried through the form.
 * @param array $args['cells'] Ordered cells, each:
 *                             array(
 *                               'type'        => 'search'|'select',
 *                               'key'         => label,
 *                               'name'        => form field name,
 *                               'value'       => current value,
 *                               'placeholder' => search only,
 *                               'options'     => select only, array of value/label,
 *                             )
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_cells  = is_array( $args['cells'] ?? null ) ? $args['cells'] : array();
$lp_action = (string) ( $args['action'] ?? '' );
$lp_submit = (string) ( $args['submit'] ?? 'Apply filters' );
$lp_hidden = is_array( $args['hidden'] ?? null ) ? $args['hidden'] : array();
$lp_is_form = '' !== $lp_action;

if ( ! $lp_cells ) {
	return;
}
?>
<?php if ( $lp_is_form ) : ?>
<form method="get" action="<?php echo esc_url( $lp_action ); ?>" data-filter-form class="flex flex-wrap bg-base-100 border-b border-base-300" data-component="filter-grid">
	<?php foreach ( $lp_hidden as $lp_key => $lp_value ) : ?>
		<input type="hidden" name="<?php echo esc_attr( (string) $lp_key ); ?>" value="<?php echo esc_attr( (string) $lp_value ); ?>" />
	<?php endforeach; ?>
<?php else : ?>
<div class="flex flex-wrap bg-base-100 border-b border-base-300" data-component="filter-grid">
<?php endif; ?>
	<?php foreach ( $lp_cells as $lp_cell ) : ?>
		<div class="flex-1 min-w-[220px] px-6 py-4 border-l border-base-300 first:border-l-0">
			<?php
			if ( 'search' === ( $lp_cell['type'] ?? '' ) ) {
				lp_part(
					'forms/field',
					array(
						'label'       => $lp_cell['key'] ?? '',
						'name'        => $lp_cell['name'] ?? '',
						'placeholder' => $lp_cell['placeholder'] ?? '',
						'value'       => $lp_cell['value'] ?? '',
					)
				);
			} else {
				lp_part(
					'forms/select',
					array(
						'label'   => $lp_cell['key'] ?? '',
						'name'    => $lp_cell['name'] ?? '',
						'options' => is_array( $lp_cell['options'] ?? null ) ? $lp_cell['options'] : array(),
						'value'   => $lp_cell['value'] ?? '',
					)
				);
			}
			?>
		</div>
	<?php endforeach; ?>
	<?php
	if ( $lp_is_form ) {
		lp_part(
			'elements/button',
			array(
				'type'  => 'submit',
				'label' => $lp_submit,
				'class' => 'sr-only',
			)
		);
	}
	?>
<?php echo $lp_is_form ? '</form>' : '</div>'; ?>
