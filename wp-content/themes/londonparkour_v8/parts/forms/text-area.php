<?php
/**
 * TextArea — Concourse design system multi-line input.
 *
 * Ported from src/stories/Forms/TextArea/TextArea.js. Mirrors Forms/Field
 * prop-for-prop (`variant: underline|boxed`, `label`, `name`, `value`,
 * `placeholder`, `required`, `disabled`, `error`, `error_message`) so the two
 * compose into one form without learning a second API. Read from the
 * Contact — Enquiries MESSAGE field: height 120, padding 14/16, 1px border,
 * 13px value, 9px label-row-to-box gap (tighter than Field's 13px gap — this
 * field's own measured instance, not the generic Form Field master).
 *
 * `resize-none` on both variants: a resizable underline (bottom-border-only)
 * box breaks its own affordance mid-resize, and a boxed field resizing
 * outside its design-fixed 120px would fight the page grid it's read from.
 *
 * Colour is the same `surface: page|board` axis as Field, on top of the same
 * `variant` axis — see Field's docblock for the full rationale (a board
 * ground needs the neutral-content family, or base-content text goes
 * invisible on it in both light themes).
 *
 * id/for pairing: the source's module-scoped `nextId()` counter becomes
 * `wp_unique_id( 'textarea-' )` so multiple instances on one page never
 * collide.
 *
 * `on_change` is not carried over — it was a JS callback prop; this is a
 * server-rendered partial with no behaviour layer of its own.
 *
 * @param string $args['id']            Explicit id; auto-generated when omitted.
 * @param string $args['variant']       underline|boxed. Default underline.
 * @param string $args['surface']       page|board. Default page.
 * @param string $args['label']         Default 'Label'.
 * @param string $args['name']
 * @param string $args['value']
 * @param string $args['placeholder']
 * @param bool   $args['required']
 * @param bool   $args['disabled']
 * @param bool   $args['error']
 * @param string $args['error_message']
 * @param int    $args['rows']          Default 5.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Same colour logic as parts/forms/field.php — kept duplicated here rather
// than factored into a third file, per the source's own "lazier diff" note.
$lp_state_underline = array(
	'page'  => array(
		'default'  => 'border-base-content text-base-content',
		'error'    => 'border-error text-base-content',
		'disabled' => 'border-base-300 text-base-content/50',
	),
	'board' => array(
		'default'  => 'border-neutral-content/20 text-neutral-content',
		'error'    => 'border-error text-neutral-content',
		'disabled' => 'border-neutral-content/10 text-neutral-content/50',
	),
);

$lp_state_boxed = array(
	'page'  => array(
		'default'  => 'border-base-300 text-base-content focus:border-base-content',
		'error'    => 'border-error text-error',
		'disabled' => 'border-base-300 text-base-content/50',
	),
	'board' => array(
		// `bg-transparent` kills daisyUI `.textarea`'s default `base-100` fill —
		// on light themes that cream chip + `text-neutral-content` is white-on-white.
		'default'  => 'border-neutral-content/10 text-neutral-content focus:border-neutral-content/20 bg-transparent',
		'error'    => 'border-error text-error bg-transparent',
		'disabled' => 'border-neutral-content/10 text-neutral-content/50 bg-transparent',
	),
);

$lp_label_class        = array(
	'page'  => 'text-base-content',
	'board' => 'text-neutral-content',
);
$lp_meta_muted          = array(
	'page'  => 'text-base-content/65',
	'board' => 'text-neutral-content/50',
);
$lp_meta_muted_disabled = array(
	'page'  => 'text-base-content/50',
	'board' => 'text-neutral-content/50',
);
$lp_placeholder         = array(
	'page'  => 'placeholder:text-base-content/65',
	'board' => 'placeholder:text-neutral-content/50',
);
$lp_disabled_border     = array(
	'page'  => 'disabled:border-base-300',
	'board' => 'disabled:border-neutral-content/10',
);
$lp_disabled_bg_boxed   = array(
	'page'  => 'disabled:bg-base-100',
	'board' => 'disabled:bg-neutral',
);

$lp_variant  = ( 'boxed' === ( $args['variant'] ?? 'underline' ) ) ? 'boxed' : 'underline';
$lp_surface  = ( 'board' === ( $args['surface'] ?? 'page' ) ) ? 'board' : 'page';
$lp_is_boxed = 'boxed' === $lp_variant;

$lp_label           = (string) ( $args['label'] ?? 'Label' );
$lp_name            = (string) ( $args['name'] ?? '' );
$lp_value           = (string) ( $args['value'] ?? '' );
$lp_placeholder_val = (string) ( $args['placeholder'] ?? '' );
$lp_required        = ! empty( $args['required'] );
$lp_disabled        = ! empty( $args['disabled'] );
$lp_error           = ! empty( $args['error'] );
$lp_error_message   = (string) ( $args['error_message'] ?? '' );
$lp_rows            = (int) ( $args['rows'] ?? 5 );

$lp_field_id  = (string) ( $args['id'] ?? wp_unique_id( 'textarea-' ) );
$lp_error_id  = $lp_field_id . '-error';
$lp_state     = $lp_disabled ? 'disabled' : ( $lp_error ? 'error' : 'default' );
$lp_show_hint = '' !== $lp_error_message;

$lp_state_map  = $lp_is_boxed ? $lp_state_boxed : $lp_state_underline;
$lp_on_surface = $lp_state_map[ $lp_surface ] ?? $lp_state_map['page'];

// Pick this surface's value from a shared literal map, page as the fallback.
$lp_pick = static function ( array $map ) use ( $lp_surface ) {
	return $map[ $lp_surface ] ?? $map['page'];
};

$lp_textarea_class = $lp_is_boxed
	? lp_classes(
		'textarea textarea-sm validator w-full rounded-none border resize-none',
		$lp_on_surface[ $lp_state ],
		'min-h-[120px] py-[14px] px-[16px] font-body text-[13px] tracking-[0.2px]',
		$lp_pick( $lp_placeholder ),
		'user-invalid:border-error',
		$lp_pick( $lp_disabled_bg_boxed ),
		$lp_pick( $lp_disabled_border ),
		'disabled:opacity-[.45]'
	)
	: lp_classes(
		'textarea textarea-ghost textarea-sm validator w-full rounded-none border-0 border-b resize-none',
		$lp_on_surface[ $lp_state ],
		'focus:border-b-2 user-invalid:border-error px-0 font-body text-[14px] tracking-[0.1px]',
		$lp_pick( $lp_placeholder ),
		'disabled:bg-transparent',
		$lp_pick( $lp_disabled_border ),
		'disabled:opacity-[.45]'
	);
?>
<div class="<?php echo lp_classes( 'group flex flex-col', $lp_error ? 'gap-[8px]' : 'gap-[9px]' ); ?>" data-component="text-area" data-state="<?php echo esc_attr( $lp_state ); ?>">
	<div class="flex items-center justify-between">
		<label for="<?php echo esc_attr( $lp_field_id ); ?>" class="<?php echo lp_classes( 'font-label text-[10px] font-semibold tracking-[1px] uppercase', $lp_pick( $lp_label_class ) ); ?>"><?php echo esc_html( $lp_label ); ?></label>
		<span class="inline-flex items-center">
			<?php if ( 'disabled' === $lp_state ) : ?>
				<span class="<?php echo lp_classes( 'font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_meta_muted_disabled ) ); ?>">DISABLED</span>
			<?php elseif ( 'error' === $lp_state ) : ?>
				<span class="font-label text-[10px] tracking-[0.9px] uppercase text-error">INVALID</span>
			<?php else : ?>
				<span class="<?php echo lp_classes( 'font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_meta_muted ), 'group-focus-within:hidden' ); ?>"><?php echo $lp_required ? 'REQUIRED' : ''; ?></span>
				<span class="<?php echo lp_classes( 'hidden font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_label_class ), 'group-focus-within:inline' ); ?>">FOCUS</span>
			<?php endif; ?>
		</span>
	</div>
	<textarea
		class="<?php echo esc_attr( $lp_textarea_class ); ?>"
		id="<?php echo esc_attr( $lp_field_id ); ?>"
		<?php if ( '' !== $lp_name ) : ?>name="<?php echo esc_attr( $lp_name ); ?>"<?php endif; ?>
		rows="<?php echo esc_attr( $lp_rows ); ?>"
		<?php if ( '' !== $lp_placeholder_val ) : ?>placeholder="<?php echo esc_attr( $lp_placeholder_val ); ?>"<?php endif; ?>
		<?php echo $lp_required ? 'required' : ''; ?>
		<?php echo $lp_disabled ? 'disabled' : ''; ?>
		<?php echo $lp_error ? 'aria-invalid="true"' : ''; ?>
		<?php if ( $lp_show_hint ) : ?>aria-describedby="<?php echo esc_attr( $lp_error_id ); ?>"<?php endif; ?>
	><?php echo esc_textarea( $lp_value ); ?></textarea>
	<?php if ( $lp_show_hint ) : ?>
		<p id="<?php echo esc_attr( $lp_error_id ); ?>" class="validator-hint font-body text-[10px] text-error m-0"><?php echo esc_html( $lp_error_message ); ?></p>
	<?php endif; ?>
</div>
