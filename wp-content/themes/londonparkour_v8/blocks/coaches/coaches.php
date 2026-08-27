<?php
/**
 * Coaches — dual layout: Homepage grid cards, or lead portrait + roster.
 *
 * Ported from src/stories/Blocks/Coaches/Coaches.js.
 *
 * Layouts:
 *   - `grid` (default) — Homepage V8 frame `t6nJQZ`: four-up coach cards with bio.
 *   - `lead` — node `DUPX7`: head-coach profile + roster list.
 *
 * Takes the CPT source control for the list. Lead layout excludes `is_lead`
 * coaches from the roster (they are featured separately via `lead_coach`).
 * Grid layout includes every coach — the homepage cards are the full team set.
 *
 * The lead portrait is media-photo's `fill` layout. The source writes
 * `h-full w-full` here where the scrim family writes `w-full h-full` — same
 * utilities, same compiled CSS, and media-photo already documents emitting one
 * form for both (docs/CONSOLIDATION.md §4c, photo-fill-plain).
 *
 * The roster thumbnail sits in a fixed 62x76 box, so it uses layout='none' and
 * carries `w-full h-full object-cover` as its class.
 *
 * Lead closing link is elements/text-link.php variant `page_accent`. Grid foot
 * link classes (`text-[11px]` + `uppercase`, no `duration-150`) do not match any
 * text-link variant — ported inline; report as a promotion candidate.
 *
 * @param string $args['layout']       grid|lead. Default grid.
 * @param string $args['eyebrow']
 * @param string $args['meta']         Grid header count, e.g. "(04)".
 * @param string $args['headline']
 * @param string $args['lead']         Grid standfirst.
 * @param string $args['footnote']     Grid foot note.
 * @param string $args['note']         Lead note.
 * @param string $args['intro_text']   Lead roster intro.
 * @param array  $args['lead_coach']   image / image_alt / name / meta / quote.
 * @param array  $args['link_action']
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_layout = ( 'lead' === ( $args['layout'] ?? 'grid' ) ) ? 'lead' : 'grid';

$lp_default_grid_coaches = array(
	array(
		'index' => '01',
		'tag'   => 'TRAINING SINCE 2005',
		'name'  => 'Andy Pearson',
		'role'  => 'DIRECTOR, OWNER & COACH',
		'bio'   => "On London's streets since 2005 — helped develop ParkourUK and has coached weekly as a senior coach for nearly two decades.",
	),
	array(
		'index' => '02',
		'tag'   => 'TRAINING SINCE 2017',
		'name'  => 'Kie Piccio',
		'role'  => 'VAUXHALL YOUTH COACH',
		'bio'   => "A former competitive swimmer who coaches adults and brings a creative, playful style to children's classes.",
	),
	array(
		'index' => '03',
		'tag'   => 'TRAINING SINCE 2007',
		'name'  => 'Leon Lawrence',
		'role'  => 'ARCHWAY ADULT COACH',
		'bio'   => 'Renowned London coach and certified PT — grounded in strength, confidence and practical movement.',
	),
	array(
		'index' => '04',
		'tag'   => 'TRAINING SINCE 2007',
		'name'  => 'Mesh Ganeshlaingam',
		'role'  => 'PARKOUR COACH · UCL',
		'bio'   => 'Blends a dance background into his parkour for a unique flow, with a holistic mind-body focus.',
	),
);

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

$lp_link    = lp_action( $args['link_action'] ?? null );
$lp_spacing = lp_section_spacing( $args );

if ( 'grid' === $lp_layout ) {
	$lp_eyebrow  = lp_section_label( (string) ( $args['eyebrow'] ?? '09 — COACHES / THE TEAM' ), $args['_section_number'] ?? null );
	$lp_meta     = (string) ( $args['meta'] ?? '(04)' );
	$lp_headline = (string) ( $args['headline'] ?? 'The people who teach the practice.' );
	$lp_lead     = (string) ( $args['lead'] ?? 'London-based coaches with decades of experience — teaching across the city since 2005.' );
	$lp_footnote = (string) ( $args['footnote'] ?? 'LONDON-BASED COACHES — TEACHING ACROSS THE CITY SINCE 2005' );

	if ( ! $lp_link ) {
		$lp_link = array(
			'label' => 'TRAIN WITH US →',
			'href'  => '/classes',
		);
	}

	// Latest = published date, not menu_order (the default resolve sort).
	$lp_resolved = lp_resolve_source(
		$args,
		'lp_coach',
		array(
			'orderby' => array(
				'date'       => 'DESC',
				'menu_order' => 'ASC',
			),
		)
	);
	$lp_coaches  = array();
	$lp_i        = 0;

	foreach ( $lp_resolved as $lp_item ) {
		if ( ! is_array( $lp_item ) ) {
			continue;
		}
		++$lp_i;
		$lp_coaches[] = array(
			'index' => (string) ( $lp_item['index'] ?? sprintf( '%02d', $lp_i ) ),
			'tag'   => (string) ( $lp_item['tag'] ?? '' ),
			'name'  => (string) ( $lp_item['name'] ?? $lp_item['title'] ?? '' ),
			'role'  => (string) ( $lp_item['role'] ?? '' ),
			'bio'   => (string) ( $lp_item['bio'] ?? '' ),
			'photo' => ! empty( $lp_item['photo'] ) ? (int) $lp_item['photo'] : ( ! empty( $lp_item['thumb'] ) ? (int) $lp_item['thumb'] : 0 ),
		);
	}

	if ( ! $lp_coaches ) {
		$lp_coaches = $lp_default_grid_coaches;
	}
	?>
<section class="<?php echo lp_classes( 'w-full bg-base-100 px-6 py-16 lg:py-[120px] lg:px-[72px]', $lp_spacing ); ?>" data-component="coaches" data-layout="grid"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
	<div class="flex flex-col gap-8 lg:gap-12">
		<header class="flex flex-col gap-[18px]">
			<div class="flex items-baseline justify-between gap-4">
				<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<?php if ( '' !== $lp_meta ) : ?>
					<span class="font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65"><?php echo esc_html( $lp_meta ); ?></span>
				<?php endif; ?>
			</div>
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
			<h2 class="font-heading text-step-3 font-semibold leading-[1.02] tracking-[-1.6px] text-base-content m-0 max-w-[700px]"><?php echo esc_html( $lp_headline ); ?></h2>
			<p class="font-body text-[15px] leading-[1.6] text-base-content/65 m-0 max-w-[560px]"><?php echo esc_html( $lp_lead ); ?></p>
		</header>

		<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
			<?php foreach ( $lp_coaches as $lp_coach ) : ?>
				<article class="flex flex-col" data-component="coach-grid-card">
					<div class="relative aspect-[3/4] overflow-hidden bg-base-300">
						<?php
						lp_part(
							'components/media-photo',
							array(
								'image_id' => $lp_coach['photo'] ?? 0,
								'alt'      => (string) ( $lp_coach['name'] ?? '' ),
								'size'     => 'lp_portrait_lg',
								'sizes'    => '(min-width: 1024px) 25vw, 50vw',
							)
						);
						?>
					</div>
					<div class="flex flex-col gap-1.5 lg:gap-2.5 pt-3 lg:pt-5">
						<div class="flex items-baseline justify-between gap-3">
							<span class="font-label text-[11px] font-semibold tracking-[0.6px] text-base-content/65"><?php echo esc_html( (string) ( $lp_coach['index'] ?? '' ) ); ?></span>
							<span class="hidden sm:inline font-label text-[10px] font-normal tracking-[0.6px] uppercase text-base-content/65"><?php echo esc_html( (string) ( $lp_coach['tag'] ?? '' ) ); ?></span>
						</div>
						<h3 class="font-heading text-[16px] lg:text-[22px] font-semibold tracking-[-0.4px] leading-tight text-base-content m-0"><?php echo esc_html( (string) ( $lp_coach['name'] ?? '' ) ); ?></h3>
						<p class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-base-content/65 m-0"><?php echo esc_html( (string) ( $lp_coach['role'] ?? '' ) ); ?></p>
						<p class="font-body text-[12px] lg:text-[13px] leading-[1.45] lg:leading-[1.55] text-base-content/70 m-0"><?php echo esc_html( (string) ( $lp_coach['bio'] ?? '' ) ); ?></p>
					</div>
				</article>
			<?php endforeach; ?>
		</div>

		<footer class="flex flex-col gap-4">
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
			<div class="flex items-baseline justify-between gap-4 flex-wrap">
				<span class="font-label text-[10px] font-normal tracking-[0.8px] uppercase text-base-content/65"><?php echo esc_html( $lp_footnote ); ?></span>
				<?php if ( ! empty( $lp_link['label'] ) ) : ?>
					<a href="<?php echo esc_url( (string) ( $lp_link['href'] ?? '#' ) ); ?>" class="font-label text-[11px] font-semibold tracking-[0.5px] uppercase text-accent hover:text-accent/70 transition-colors"><?php echo esc_html( (string) $lp_link['label'] ); ?></a>
				<?php endif; ?>
			</div>
		</footer>
	</div>
</section>
	<?php
	return;
}

/* ── Lead layout ─────────────────────────────────────────────────────────── */

$lp_eyebrow    = lp_section_label( (string) ( $args['eyebrow'] ?? '06 — THE COACHES' ), $args['_section_number'] ?? null );
$lp_headline   = (string) ( $args['headline'] ?? 'Twelve people who started exactly where you are.' );
$lp_note       = (string) ( $args['note'] ?? 'ALL UKC LEVEL 2 · DBS CHECKED' );
$lp_intro_text = (string) ( $args['intro_text'] ?? 'Nine of our twelve coaches came up through our own beginner classes. They remember being the nervous one at the back, which is most of the qualification.' );

$lp_lead_in    = is_array( $args['lead_coach'] ?? null ) ? $args['lead_coach'] : array();
$lp_lead_name  = (string) ( $lp_lead_in['name'] ?? 'Andy Pearson' );
$lp_lead_meta  = (string) ( $lp_lead_in['meta'] ?? 'HEAD COACH / 11 YRS' );
$lp_lead_quote = (string) ( $lp_lead_in['quote'] ?? "“The job isn't to make you brave. It's to break the thing you're scared of into six pieces small enough that you're not.”" );
$lp_lead_image = ! empty( $lp_lead_in['image'] ) ? (int) $lp_lead_in['image'] : 0;

if ( ! $lp_link ) {
	$lp_link = array(
		'label' => 'Meet all twelve →',
		'href'  => '#',
	);
}

// One query layer; the projection is this block's own. A coach record gives
// thumb/name/specialty/location; a manual row supplies the same names.
$lp_roster = array_map(
	static function ( array $item ): array {
		return array(
			'thumb'     => ! empty( $item['thumb'] ) ? (int) $item['thumb'] : ( ! empty( $item['photo'] ) ? (int) $item['photo'] : 0 ),
			'thumb_alt' => (string) ( $item['thumb_alt'] ?? '' ),
			'name'      => (string) ( $item['name'] ?? $item['title'] ?? '' ),
			'specialty' => (string) ( $item['specialty'] ?? '' ),
			'location'  => (string) ( $item['location'] ?? '' ),
		);
	},
	lp_resolve_source(
		$args,
		'lp_coach',
		array(
			'exclude_flag' => 'is_lead',
			'orderby'      => array(
				'date'       => 'DESC',
				'menu_order' => 'ASC',
			),
		)
	)
);

if ( ! $lp_roster ) {
	$lp_roster = $lp_default_roster;
}
?>
<section class="<?php echo lp_classes( 'w-full bg-base-100 px-6 py-[80px] lg:pt-[124px] lg:px-16 lg:pb-[128px]', $lp_spacing ); ?>" data-component="coaches" data-layout="lead"<?php echo lp_section_anchor( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>>
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
				if ( $lp_link && '' !== (string) ( $lp_link['label'] ?? '' ) ) {
					lp_part(
						'elements/text-link',
						array(
							'label'   => $lp_link['label'],
							'href'    => $lp_link['href'] ?: '#',
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
