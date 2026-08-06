<?php
/**
 * Coaches — "06 — THE COACHES": a lead portrait beside the roster.
 *
 * Ported from src/stories/Blocks/Coaches/Coaches.js.
 *
 * Takes the CPT source control for the roster; the lead coach is separate
 * content, since it is a different projection of the same entity (portrait,
 * name, meta and a pull quote, not a thumbnail row).
 *
 * The lead portrait is media-photo's `fill` layout. The source writes
 * `h-full w-full` here where the scrim family writes `w-full h-full` — same
 * utilities, same compiled CSS, and media-photo already documents emitting one
 * form for both (docs/CONSOLIDATION.md §4c, photo-fill-plain).
 *
 * The roster thumbnail sits in a fixed 62x76 box, so it uses layout='none' and
 * carries `w-full h-full object-cover` as its class.
 *
 * The closing link is elements/text-link.php variant `page_accent`; its
 * positional utilities stay a call-site modifier, per §4a.
 *
 * @param string $args['eyebrow']
 * @param string $args['headline']
 * @param string $args['note']
 * @param string $args['intro_text']
 * @param array  $args['lead_coach']  image / image_alt / name / meta / quote.
 * @param array  $args['link_action']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_default_roster = array(
	array(
		'name'      => 'Kie Piccio',
		'specialty' => 'Precision & balance',
		'location'  => 'PECKHAM',
	),
	array(
		'name'      => 'Leon Lawrence',
		'specialty' => 'Kids & families',
		'location'  => 'VAUXHALL',
	),
	array(
		'name'      => 'Nirosh Ganeshalingam',
		'specialty' => 'Strength & conditioning',
		'location'  => 'SOUTHBANK',
	),
	array(
		'name'      => 'Sofia Reyes',
		'specialty' => "Women's sessions",
		'location'  => 'VAUXHALL',
	),
	array(
		'name'      => 'Tomas Vrba',
		'specialty' => 'Competition & film',
		'location'  => 'ALL SITES',
	),
);

$lp_eyebrow    = (string) ( $args['eyebrow'] ?? '06 — THE COACHES' );
$lp_headline   = (string) ( $args['headline'] ?? 'Twelve people who started exactly where you are.' );
$lp_note       = (string) ( $args['note'] ?? 'ALL UKC LEVEL 2 · DBS CHECKED' );
$lp_intro_text = (string) ( $args['intro_text'] ?? 'Nine of our twelve coaches came up through our own beginner classes. They remember being the nervous one at the back, which is most of the qualification.' );

$lp_lead_in    = is_array( $args['lead_coach'] ?? null ) ? $args['lead_coach'] : array();
$lp_lead_name  = (string) ( $lp_lead_in['name'] ?? 'Andy Pearson' );
$lp_lead_meta  = (string) ( $lp_lead_in['meta'] ?? 'HEAD COACH / 11 YRS' );
$lp_lead_quote = (string) ( $lp_lead_in['quote'] ?? "“The job isn't to make you brave. It's to break the thing you're scared of into six pieces small enough that you're not.”" );
$lp_lead_image = ! empty( $lp_lead_in['image'] ) ? (int) $lp_lead_in['image'] : 0;

$lp_link = lp_action( $args['link_action'] ?? null );

// One query layer; the projection is this block's own. A coach record gives
// thumb/name/specialty/location; a manual row supplies the same names.
$lp_roster = array_map(
	static function ( array $item ): array {
		return array(
			'thumb'     => ! empty( $item['thumb'] ) ? (int) $item['thumb'] : 0,
			'thumb_alt' => (string) ( $item['thumb_alt'] ?? '' ),
			'name'      => (string) ( $item['name'] ?? $item['title'] ?? '' ),
			'specialty' => (string) ( $item['specialty'] ?? '' ),
			'location'  => (string) ( $item['location'] ?? '' ),
		);
	},
	lp_resolve_source( $args, 'lp_coach', array( 'exclude_flag' => 'is_lead' ) )
);

if ( ! $lp_roster ) {
	$lp_roster = $lp_default_roster;
}

$lp_spacing = lp_section_spacing( $args );
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100 px-6 py-[56px] lg:pt-[100px] lg:px-16 lg:pb-[104px]', $lp_spacing ); ?>" data-component="coaches"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div>
		<div class="flex flex-wrap items-end justify-between gap-6">
			<div class="flex flex-col gap-[20px] max-w-[700px]">
				<span class="font-label text-step--2 font-normal tracking-[0.5px] uppercase text-base-content/60"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<h2 class="font-heading text-step-3 font-semibold leading-[1.02] tracking-[-1.6px] text-base-content"><?php echo esc_html( $lp_headline ); ?></h2>
			</div>
			<?php if ( '' !== $lp_note ) : ?>
				<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-base-content/60 whitespace-nowrap"><?php echo esc_html( $lp_note ); ?></span>
			<?php endif; ?>
		</div>

		<div class="mt-[64px] flex flex-col lg:flex-row gap-[72px] items-start">
			<div class="w-full lg:w-[556px] lg:shrink-0 flex flex-col">
				<div class="relative w-full aspect-[556/600] lg:h-[600px] lg:aspect-auto overflow-hidden bg-base-300">
					<?php
					$lp_lead_photo = array(
						'image_id' => $lp_lead_image,
						'size'     => 'lp_portrait_lg',
						'sizes'    => '(min-width: 1024px) 556px, 100vw',
					);
					if ( array_key_exists( 'image_alt', $lp_lead_in ) ) {
						$lp_lead_photo['alt'] = (string) $lp_lead_in['image_alt'];
					}
					lp_part( 'components/media-photo', $lp_lead_photo );
					?>
				</div>
				<div class="mt-[26px] flex flex-col gap-[14px]">
					<div class="flex items-center justify-between gap-4">
						<p class="font-heading text-[28px] font-semibold tracking-[-0.8px] text-base-content"><?php echo esc_html( $lp_lead_name ); ?></p>
						<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-base-content/60 whitespace-nowrap"><?php echo esc_html( $lp_lead_meta ); ?></span>
					</div>
					<p class="font-body text-step--1 font-normal tracking-[0.2px] leading-[1.6] text-base-content/70"><?php echo esc_html( $lp_lead_quote ); ?></p>
				</div>
			</div>

			<div class="flex-1 min-w-0 flex flex-col">
				<p class="font-body text-step--1 font-normal tracking-[0.2px] leading-[1.65] text-base-content/70"><?php echo esc_html( $lp_intro_text ); ?></p>

				<div class="mt-[40px] border-t border-base-300 divide-y divide-base-300">
					<?php foreach ( $lp_roster as $lp_coach ) : ?>
						<div class="flex items-center gap-[20px] py-[18px]" data-component="coach-roster-row">
							<div class="w-[62px] h-[76px] shrink-0 overflow-hidden bg-base-300">
								<?php
								lp_part(
									'components/media-photo',
									array(
										'image_id' => $lp_coach['thumb'],
										'alt'      => '' !== $lp_coach['thumb_alt'] ? $lp_coach['thumb_alt'] : $lp_coach['name'],
										'element'  => 'img',
										'layout'   => 'none',
										'class'    => 'w-full h-full object-cover',
										'size'     => 'lp_portrait_sm',
										'sizes'    => '62px',
									)
								);
								?>
							</div>
							<div class="flex-1 min-w-0 flex flex-col gap-[7px]">
								<p class="font-heading text-[19px] font-medium tracking-[-0.4px] text-base-content truncate"><?php echo esc_html( $lp_coach['name'] ); ?></p>
								<p class="font-body text-[11px] font-normal tracking-[0.3px] text-base-content/60 truncate"><?php echo esc_html( $lp_coach['specialty'] ); ?></p>
							</div>
							<span class="font-label text-[10px] font-normal tracking-[0.6px] uppercase text-base-content/60 text-right shrink-0 whitespace-nowrap"><?php echo esc_html( $lp_coach['location'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>

				<?php
				if ( $lp_link ) {
					lp_part(
						'elements/text-link',
						array(
							'label'   => $lp_link['label'],
							'href'    => $lp_link['href'],
							'variant' => 'page_accent',
							'class'   => 'mt-[28px] inline-block self-start',
						)
					);
				}
				?>
			</div>
		</div>
	</div>
</section>
