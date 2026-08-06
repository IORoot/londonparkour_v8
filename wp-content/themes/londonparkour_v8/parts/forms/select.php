<?php
/**
 * Select — Concourse design system dropdown.
 *
 * Ported from src/stories/Forms/Select/Select.js. One partial for all 4
 * design states (default/focus/error/disabled). Unlike Field this IS boxed:
 * built on daisyUI's `select` (`select-sm`), with the spec's exact 42px
 * height / 14px padding / 1px border layered on as literal Tailwind
 * utilities. A plain `<select>` — no custom dropdown component.
 *
 * The source's Select Field carries no label-row meta word (unlike Field) —
 * only geometry + colour states for the box itself, so none is rendered here.
 * Focus (border -> base-content) is only wired for the default state: an
 * errored select keeps its red border while focused, rather than losing the
 * error affordance exactly when the user is interacting with it.
 *
 * Native HTML5 `required` drives error visuals via daisyUI's `validator`
 * class + `user-invalid:` variant, zero JS. `error`/`error_message` cover
 * server-side errors: `aria-invalid="true"` is also a `.validator` invalid
 * trigger, reusing the same CSS path.
 *
 * id/for pairing: the source's module-scoped `nextId()` counter becomes
 * `wp_unique_id( 'select-' )` so multiple instances on one page never collide.
 *
 * `on_change` is not carried over — it was a JS callback prop; this is a
 * server-rendered partial with no behaviour layer of its own.
 *
 * @param string $args['id']            Explicit id; auto-generated when omitted.
 * @param string $args['label']         Default 'Label'.
 * @param string $args['name']
 * @param array  $args['options']       array of array( 'value' => …, 'label' => … ).
 * @param string $args['value']         Currently selected option value.
 * @param string $args['placeholder']   Renders as a disabled first <option>.
 * @param bool   $args['required']
 * @param bool   $args['disabled']
 * @param bool   $args['error']
 * @param string $args['error_message']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per state — Tailwind v4 scans source text.
$lp_state = array(
	'default'  => 'border-base-300 text-base-content focus:border-base-content',
	'error'    => 'border-error text-error',
	'disabled' => 'border-base-300 text-base-content/50',
);

$lp_label          = (string) ( $args['label'] ?? 'Label' );
$lp_name            = (string) ( $args['name'] ?? '' );
$lp_options         = is_array( $args['options'] ?? null ) ? $args['options'] : array();
$lp_value           = (string) ( $args['value'] ?? '' );
$lp_placeholder_val = (string) ( $args['placeholder'] ?? '' );
$lp_required        = ! empty( $args['required'] );
$lp_disabled        = ! empty( $args['disabled'] );
$lp_error           = ! empty( $args['error'] );
$lp_error_message   = (string) ( $args['error_message'] ?? '' );

$lp_field_id  = (string) ( $args['id'] ?? wp_unique_id( 'select-' ) );
$lp_error_id  = $lp_field_id . '-error';
$lp_state_key = $lp_disabled ? 'disabled' : ( $lp_error ? 'error' : 'default' );
$lp_show_hint = '' !== $lp_error_message;

$lp_select_class = lp_classes(
	'select select-sm validator w-full rounded-none border',
	$lp_state[ $lp_state_key ],
	'h-[42px] px-[14px] font-body text-[11px] tracking-[0.4px] user-invalid:border-error disabled:bg-base-100 disabled:border-base-300 disabled:opacity-[.45]'
);
?>
<div class="<?php echo lp_classes( 'flex flex-col', $lp_error ? 'gap-[8px]' : 'gap-[13px]' ); ?>" data-component="select" data-state="<?php echo esc_attr( $lp_state_key ); ?>">
	<label for="<?php echo esc_attr( $lp_field_id ); ?>" class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-base-content"><?php echo esc_html( $lp_label ); ?></label>
	<select
		class="<?php echo esc_attr( $lp_select_class ); ?>"
		id="<?php echo esc_attr( $lp_field_id ); ?>"
		<?php if ( '' !== $lp_name ) : ?>name="<?php echo esc_attr( $lp_name ); ?>"<?php endif; ?>
		<?php echo $lp_required ? 'required' : ''; ?>
		<?php echo $lp_disabled ? 'disabled' : ''; ?>
		<?php echo $lp_error ? 'aria-invalid="true"' : ''; ?>
		<?php if ( $lp_show_hint ) : ?>aria-describedby="<?php echo esc_attr( $lp_error_id ); ?>"<?php endif; ?>
	>
		<?php if ( '' !== $lp_placeholder_val ) : ?>
			<option value="" disabled <?php echo ( '' === $lp_value ) ? 'selected' : ''; ?>><?php echo esc_html( $lp_placeholder_val ); ?></option>
		<?php endif; ?>
		<?php foreach ( $lp_options as $lp_option ) : ?>
			<?php $lp_option_value = (string) ( $lp_option['value'] ?? '' ); ?>
			<option value="<?php echo esc_attr( $lp_option_value ); ?>" <?php echo ( $lp_option_value === $lp_value ) ? 'selected' : ''; ?>><?php echo esc_html( (string) ( $lp_option['label'] ?? '' ) ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php if ( $lp_show_hint ) : ?>
		<p id="<?php echo esc_attr( $lp_error_id ); ?>" class="validator-hint font-body text-[10px] text-error m-0"><?php echo esc_html( $lp_error_message ); ?></p>
	<?php endif; ?>
</div>
