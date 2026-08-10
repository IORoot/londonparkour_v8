<?php
/**
 * TrainInPerson — "04 Train These In Person".
 *
 * REFERENCE IMPLEMENTATION. Every block's markup file follows this shape:
 *   - Read $args, defaulting to the Storybook's own DEFAULT_* values.
 *   - Resolve any list through lp_resolve_source(), then project it locally —
 *     the same entity is shaped differently in each block.
 *   - Compose parts via lp_part(); never retype an element's markup.
 *   - Copy Tailwind class strings from the Storybook byte-for-byte, as whole
 *     literals. Never build one by concatenation.
 *
 * Ported from src/stories/Blocks/TrainInPerson/TrainInPerson.js.
 *
 * Accent band (bg-accent + the accent-content family, never bg-neutral or
 * bg-primary). The floor on this band is accent-content/70 — 4.57:1, the
 * tightest passing pairing in the system. Nothing here goes below it.
 *
 * Two departures from the Storybook source, both deliberate:
 *   1. Its hand-built hairline is replaced by parts/elements/rule.php with
 *      tone="accent". Its JSDoc says Rule had no accent tone; Rule has since
 *      gained one, and the markup is byte-identical bar data-component. This
 *      is the consolidation the port exists to do.
 *   2. The head row stays hand-built. It is MetaRow-shaped but its two sides
 *      differ in weight (left 11px/600, right 10px/normal) where MetaRow uses
 *      one class for both — the Storybook calls this out explicitly and
 *      Locations and Classes hand-roll the same row for the same reason.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_eyebrow = (string) ( $args['eyebrow'] ?? 'TRAIN THESE IN PERSON' );
$lp_stamp   = (string) ( $args['stamp'] ?? 'FIVE SITES ACROSS LONDON' );
$lp_note    = (string) ( $args['note'] ?? 'Every site is a ten-minute walk from a tube or overground station. We checked.' );
$lp_action  = lp_action( $args['primary_action'] ?? null );

// One query layer; the projection is this block's own. A location record gives
// tag/title/meta; a manual row supplies the same three names.
$lp_sites = array_map(
	static function ( array $item ): array {
		return array(
			'tag'    => (string) ( $item['tag'] ?? '' ),
			'title'  => (string) ( $item['title'] ?? '' ),
			'detail' => (string) ( $item['meta'] ?? '' ),
		);
	},
	lp_resolve_source( $args, 'lp_location', array( 'require_kind' => 'site' ) )
);

$lp_spacing = lp_section_spacing( $args );
?>
<section class="w-full bg-accent" data-component="train-in-person"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="<?php echo lp_classes( 'px-6 lg:px-16 pt-scale-xl pb-scale-2xl flex flex-col gap-[32px]', $lp_spacing ); ?>">

		<div class="flex items-center justify-between gap-4 flex-wrap">
			<span class="font-label text-[11px] font-semibold uppercase tracking-[1px] text-accent-content"><?php echo esc_html( $lp_eyebrow ); ?></span>
			<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-accent-content/70"><?php echo esc_html( $lp_stamp ); ?></span>
		</div>

		<?php lp_part( 'elements/rule', array( 'tone' => 'accent' ) ); ?>

		<?php if ( $lp_sites ) : ?>
			<div class="grid sm:grid-cols-3 gap-[32px]">
				<?php foreach ( $lp_sites as $lp_site ) : ?>
					<div class="flex flex-col gap-[10px]">
						<?php
						lp_part(
							'elements/glyph-label',
							array(
								'label'   => $lp_site['tag'],
								'surface' => 'accent',
								'tone'    => 'muted',
							)
						);
						?>
						<p class="font-heading text-[20px] font-medium tracking-[-0.3px] text-accent-content"><?php echo esc_html( $lp_site['title'] ); ?></p>
						<p class="font-body text-[11px] leading-[1.5] text-accent-content/70"><?php echo esc_html( $lp_site['detail'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( '' !== $lp_note || $lp_action ) : ?>
			<div class="flex items-center justify-between gap-4 flex-wrap pt-2 border-t border-accent-content/15">
				<p class="font-body text-[11px] leading-[1.5] text-accent-content/70 m-0 max-w-[420px]"><?php echo esc_html( $lp_note ); ?></p>
				<?php if ( $lp_action ) : ?>
					<a href="<?php echo esc_url( $lp_action['href'] ); ?>" class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-accent-content hover:text-accent-content/70 transition-colors duration-150 whitespace-nowrap"><?php echo esc_html( $lp_action['label'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

	</div>
</section>
