<?php
/**
 * Field — Concourse design system text input.
 *
 * Ported from src/stories/Forms/Field/Field.js. One partial for all 4 design
 * states (default/focus/error/disabled) across three shapes,
 * `variant: underline|boxed|filled`, and two grounds, `surface: page|board` — see
 * the source docblock for the full colour rationale (boxed's `page` palette
 * intentionally matches Forms/Select's box, not the raw Figma numbers).
 *
 * Focus is a real CSS `:focus` state, not a prop. The label-row meta word
 * ("REQUIRED" -> "FOCUS") swaps live via `:focus-within` on the `.group`
 * wrapper — both spans are always in the DOM, toggled with
 * `group-focus-within:` utilities, so none of it needs JS.
 *
 * Native HTML5 validation (required/pattern/type) drives the same visuals
 * via daisyUI's `validator` class + `user-invalid:` variant, with zero JS.
 * `error`/`error_message` cover server-side errors HTML validation can't
 * know about — `aria-invalid="true"` is also one of `.validator`'s own
 * invalid triggers, so setting it reuses the identical CSS path.
 *
 * id/for pairing: the source's module-scoped `nextId()` counter becomes
 * `wp_unique_id( 'field-' )` so multiple instances on one page never collide.
 *
 * `on_change` is not carried over — it was a JS callback prop; this is a
 * server-rendered partial with no behaviour layer of its own.
 *
 * @param string $args['id']            Explicit id; auto-generated when omitted.
 * @param string $args['variant']       underline|boxed|filled. Default underline.
 * @param string $args['surface']       page|board. Default page.
 * @param string $args['label']         Default 'Label'.
 * @param string $args['name']
 * @param string $args['type']          Default 'text'.
 * @param string $args['value']
 * @param string $args['placeholder']
 * @param bool   $args['required']
 * @param bool   $args['disabled']
 * @param bool   $args['error']
 * @param string $args['error_message']
 * @param string $args['pattern']
 * @param string $args['autocomplete']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

// Full literal strings per surface x state — Tailwind v4 scans source text,
// so these can never be assembled by interpolating a colour name into a
// class. `page` values are byte-identical to the pre-surface build.
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

// Boxed variant — `page` copied 1:1 from Forms/Select's STATE map so the two
// boxes match; `board` copied from docs/phase7/surface-axis.md's matrix.
$lp_state_boxed = array(
	'page'  => array(
		'default'  => 'border-base-300 text-base-content focus:border-base-content',
		'error'    => 'border-error text-error',
		'disabled' => 'border-base-300 text-base-content/50',
	),
	'board' => array(
		// `bg-transparent` kills daisyUI `.input`'s default `base-100` fill —
		// on light themes that cream chip + `text-neutral-content` is white-on-white.
		'default'  => 'border-neutral-content/10 text-neutral-content focus:border-neutral-content/20 bg-transparent',
		'error'    => 'border-error text-error bg-transparent',
		'disabled' => 'border-neutral-content/10 text-neutral-content/50 bg-transparent',
	),
);

// Shared surface-only classes (label, meta words, placeholder, disabled fill).
$lp_label_class          = array(
	'page'  => 'text-base-content',
	'board' => 'text-neutral-content',
);
$lp_meta_muted            = array(
	'page'  => 'text-base-content/65',
	'board' => 'text-neutral-content/50',
);
$lp_meta_muted_disabled   = array(
	'page'  => 'text-base-content/50',
	'board' => 'text-neutral-content/50',
);
$lp_placeholder           = array(
	'page'  => 'placeholder:text-base-content/65',
	'board' => 'placeholder:text-neutral-content/50',
);
$lp_disabled_border       = array(
	'page'  => 'disabled:border-base-300',
	'board' => 'disabled:border-neutral-content/10',
);
$lp_disabled_bg_boxed     = array(
	'page'  => 'disabled:bg-base-100',
	'board' => 'disabled:bg-neutral',
);

// Filled — always a white chip with dark ink. `neutral` is the fixed near-black
// in every theme, so typed text stays readable on `bg-white` on both grounds.
$lp_state_filled = array(
	'page'  => array(
		'default'  => 'border-base-300 text-neutral focus:border-base-content bg-white',
		'error'    => 'border-error text-neutral bg-white',
		'disabled' => 'border-base-300 text-neutral/50 bg-white',
	),
	'board' => array(
		'default'  => 'border-neutral-content/10 text-neutral focus:border-neutral-content/20 bg-white',
		'error'    => 'border-error text-neutral bg-white',
		'disabled' => 'border-neutral-content/10 text-neutral/50 bg-white',
	),
);

$lp_variant_in = (string) ( $args['variant'] ?? 'underline' );
$lp_variant    = in_array( $lp_variant_in, array( 'boxed', 'filled' ), true ) ? $lp_variant_in : 'underline';
$lp_surface    = ( 'board' === ( $args['surface'] ?? 'page' ) ) ? 'board' : 'page';
$lp_is_boxed   = 'boxed' === $lp_variant;
$lp_is_filled  = 'filled' === $lp_variant;

$lp_label         = (string) ( $args['label'] ?? 'Label' );
$lp_name           = (string) ( $args['name'] ?? '' );
$lp_type           = (string) ( $args['type'] ?? 'text' );
$lp_value          = (string) ( $args['value'] ?? '' );
$lp_placeholder_val = (string) ( $args['placeholder'] ?? '' );
$lp_required        = ! empty( $args['required'] );
$lp_disabled        = ! empty( $args['disabled'] );
$lp_error           = ! empty( $args['error'] );
$lp_error_message   = (string) ( $args['error_message'] ?? '' );
$lp_pattern         = (string) ( $args['pattern'] ?? '' );
$lp_autocomplete    = (string) ( $args['autocomplete'] ?? '' );

$lp_field_id = (string) ( $args['id'] ?? wp_unique_id( 'field-' ) );
$lp_error_id = $lp_field_id . '-error';
$lp_state    = $lp_disabled ? 'disabled' : ( $lp_error ? 'error' : 'default' );
$lp_show_hint = '' !== $lp_error_message;

$lp_state_map  = $lp_is_filled ? $lp_state_filled : ( $lp_is_boxed ? $lp_state_boxed : $lp_state_underline );
$lp_on_surface = $lp_state_map[ $lp_surface ] ?? $lp_state_map['page'];

// Pick this surface's value from a shared literal map, page as the fallback.
$lp_pick = static function ( array $map ) use ( $lp_surface ) {
	return $map[ $lp_surface ] ?? $map['page'];
};

$lp_input_class = $lp_is_filled
	? lp_classes(
		'input validator w-full rounded-none border',
		$lp_on_surface[ $lp_state ],
		'h-[48px] px-4 font-body text-[16px] tracking-[0.1px] placeholder:text-neutral/50',
		'user-invalid:border-error',
		$lp_pick( $lp_disabled_border ),
		'disabled:opacity-[.45]'
	)
	: ( $lp_is_boxed
	? lp_classes(
		'input input-sm validator w-full rounded-none border',
		$lp_on_surface[ $lp_state ],
		'h-[42px] px-[14px] font-body text-[11px] tracking-[0.4px]',
		$lp_pick( $lp_placeholder ),
		'user-invalid:border-error',
		$lp_pick( $lp_disabled_bg_boxed ),
		$lp_pick( $lp_disabled_border ),
		'disabled:opacity-[.45]'
	)
	: lp_classes(
		'input input-ghost input-sm validator w-full rounded-none border-0 border-b',
		$lp_on_surface[ $lp_state ],
		'focus:border-b-2 user-invalid:border-error px-0 font-body text-[14px] tracking-[0.1px]',
		$lp_pick( $lp_placeholder ),
		'disabled:bg-transparent',
		$lp_pick( $lp_disabled_border ),
		'disabled:opacity-[.45]'
	) );
?>
<div class="<?php echo lp_classes( 'group flex flex-col', $lp_error ? 'gap-[8px]' : 'gap-[13px]' ); ?>" data-component="field" data-state="<?php echo esc_attr( $lp_state ); ?>">
	<div class="flex items-center justify-between">
		<label for="<?php echo esc_attr( $lp_field_id ); ?>" class="<?php echo lp_classes( 'font-label text-[10px] font-semibold tracking-[1px] uppercase', $lp_pick( $lp_label_class ) ); ?>"><?php echo esc_html( $lp_label ); ?></label>
		<span class="inline-flex items-center">
			<?php if ( 'disabled' === $lp_state ) : ?>
				<span class="<?php echo lp_classes( 'font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_meta_muted_disabled ) ); ?>">DISABLED</span>
			<?php elseif ( 'error' === $lp_state ) : ?>
				<span class="font-label text-[10px] tracking-[0.9px] uppercase text-error">INVALID</span>
			<?php else : ?>
				<span class="<?php echo $lp_is_filled ? lp_classes( 'font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_meta_muted ) ) : lp_classes( 'font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_meta_muted ), 'group-focus-within:hidden' ); ?>"><?php echo $lp_required ? 'REQUIRED' : ''; ?></span>
				<?php if ( ! $lp_is_filled ) : ?>
					<span class="<?php echo lp_classes( 'hidden font-label text-[10px] tracking-[0.9px] uppercase', $lp_pick( $lp_label_class ), 'group-focus-within:inline' ); ?>">FOCUS</span>
				<?php endif; ?>
			<?php endif; ?>
		</span>
	</div>
	<input
		class="<?php echo esc_attr( $lp_input_class ); ?>"
		id="<?php echo esc_attr( $lp_field_id ); ?>"
		type="<?php echo esc_attr( $lp_type ); ?>"
		<?php if ( '' !== $lp_name ) : ?>name="<?php echo esc_attr( $lp_name ); ?>"<?php endif; ?>
		value="<?php echo esc_attr( $lp_value ); ?>"
		<?php if ( '' !== $lp_placeholder_val ) : ?>placeholder="<?php echo esc_attr( $lp_placeholder_val ); ?>"<?php endif; ?>
		<?php echo $lp_required ? 'required' : ''; ?>
		<?php echo $lp_disabled ? 'disabled' : ''; ?>
		<?php if ( '' !== $lp_pattern ) : ?>pattern="<?php echo esc_attr( $lp_pattern ); ?>"<?php endif; ?>
		<?php if ( '' !== $lp_autocomplete ) : ?>autocomplete="<?php echo esc_attr( $lp_autocomplete ); ?>"<?php endif; ?>
		<?php echo $lp_error ? 'aria-invalid="true"' : ''; ?>
		<?php if ( $lp_show_hint ) : ?>aria-describedby="<?php echo esc_attr( $lp_error_id ); ?>"<?php endif; ?>
	/>
	<?php if ( $lp_show_hint ) : ?>
		<p id="<?php echo esc_attr( $lp_error_id ); ?>" class="validator-hint font-body text-[10px] text-error m-0"><?php echo esc_html( $lp_error_message ); ?></p>
	<?php endif; ?>
</div>
