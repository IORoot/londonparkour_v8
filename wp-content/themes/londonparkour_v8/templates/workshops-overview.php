<?php
/**
 * Template Name: Workshops
 *
 * Workshops overview. Ported from
 * src/stories/Pages/WorkshopsOverview/WorkshopsOverview.js (`UXwUT`).
 *
 * One-off clasbpro_class posts only. Lead is the next sitting; remaining
 * upcoming sit on the dark series board; past sit on cream. Cards go to
 * `/classes/{slug}`. View rail with WORKSHOPS current, no filter grid.
 *
 * Seeded at slug `workshops` (`/workshops/`).
 *
 * The overview row markup is duplicated for upcoming and past — reported,
 * not promoted (PORT-BRIEF 3a).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_split = lp_class_workshops_split();
$lp_lead  = $lp_split['lead'];
$lp_rest  = $lp_split['rest'];
$lp_past  = $lp_split['past'];

$lp_private = home_url( '/#private-coaching' );
foreach ( array( 'private-coaching', 'private-tuition' ) as $lp_slug ) {
	$lp_page = get_page_by_path( $lp_slug );
	if ( $lp_page instanceof WP_Post ) {
		$lp_private = (string) get_permalink( $lp_page );
		break;
	}
}

$lp_agenda = lp_classes_page_url( 'classes' );

$lp_row = static function ( WP_Post $lp_post ): void {
	$lp_id       = (int) $lp_post->ID;
	$lp_levels   = get_the_terms( $lp_post, 'lp_level' );
	$lp_level    = ( is_array( $lp_levels ) && $lp_levels ) ? strtoupper( $lp_levels[0]->name ) : '';
	$lp_location = function_exists( 'lp_class_location_id' ) ? lp_class_location_id( $lp_id ) : 0;
	$lp_venue    = $lp_location ? strtoupper( get_the_title( $lp_location ) ) : '';
	$lp_price    = lp_class_price_display( $lp_id );
	$lp_duration = lp_class_workshop_duration_label( $lp_id );
	$lp_coaches  = lp_class_coach_ids( $lp_id );
	$lp_coach    = $lp_coaches ? strtoupper( get_the_title( (int) $lp_coaches[0] ) ) : '';
	$lp_logline  = function_exists( 'get_field' ) ? (string) get_field( 'acf_logline', $lp_id ) : '';
	$lp_chips    = function_exists( 'get_field' ) ? get_field( 'acf_chips', $lp_id ) : array();
	$lp_chips    = is_array( $lp_chips ) ? $lp_chips : array();
	$lp_image    = lp_class_image_id( $lp_id );
	$lp_meta     = array_filter( array( $lp_duration, $lp_venue, $lp_price, $lp_coach ) );
	?>
	<a
		href="<?php echo esc_url( get_permalink( $lp_post ) ); ?>"
		class="group flex flex-col lg:flex-row bg-secondary no-underline overflow-hidden text-left border border-neutral-content/10 hover:bg-primary"
		data-component="workshop-overview-row"
	>
		<div class="relative w-full lg:w-[460px] shrink-0 min-h-[180px] lg:h-[272px] bg-neutral overflow-hidden">
			<?php if ( $lp_image ) : ?>
				<?php
				lp_part(
					'components/media-photo',
					array(
						'image_id' => $lp_image,
						'layout'   => 'fill',
						'size'     => 'lp_wide',
						'sizes'    => '(min-width: 1024px) 460px, 100vw',
						'class'    => 'absolute inset-0 w-full h-full object-cover',
					)
				);
				?>
			<?php endif; ?>
		</div>
		<div class="flex-1 min-w-0 flex flex-col gap-[14px] py-7 px-8 lg:pl-9 justify-between">
			<div class="flex flex-col gap-[14px]">
				<div class="flex items-center gap-2.5 flex-wrap">
					<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( lp_class_workshop_date_label( $lp_id ) ); ?></span>
					<?php if ( '' !== $lp_level ) : ?>
						<span class="font-label text-[10px] font-semibold tracking-[0.9px] uppercase text-neutral-content/50 group-hover:text-neutral/70"><?php echo esc_html( $lp_level ); ?></span>
					<?php endif; ?>
				</div>
				<h2 class="font-heading text-[34px] font-semibold tracking-[-0.9px] leading-[1.02] text-neutral-content m-0 [text-box:normal] group-hover:text-neutral"><?php echo esc_html( get_the_title( $lp_post ) ); ?></h2>
				<?php if ( '' !== $lp_logline ) : ?>
					<p class="font-label text-[13px] font-normal leading-[1.45] tracking-[0.1px] text-neutral-content/70 m-0 group-hover:text-neutral/80"><?php echo esc_html( $lp_logline ); ?></p>
				<?php endif; ?>
				<?php if ( $lp_chips ) : ?>
					<div class="flex flex-wrap gap-2">
						<?php foreach ( $lp_chips as $lp_chip ) : ?>
							<?php $lp_chip_label = (string) ( $lp_chip['label'] ?? '' ); ?>
							<?php if ( '' !== $lp_chip_label ) : ?>
								<span class="inline-flex items-center py-[5px] px-2.5 border border-neutral-content/20 font-label text-[9px] font-bold tracking-[0.8px] uppercase text-neutral-content/80 group-hover:border-neutral/40 group-hover:text-neutral"><?php echo esc_html( $lp_chip_label ); ?></span>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="flex items-center justify-between gap-4 flex-wrap pt-[6px]">
				<span class="flex items-center gap-3 font-label text-[10px] font-semibold uppercase tracking-[0.8px] text-neutral-content/50 group-hover:text-neutral/70">
					<?php foreach ( array_values( $lp_meta ) as $lp_i => $lp_bit ) : ?>
						<?php if ( $lp_i ) : ?>
							<span class="w-[3px] h-[3px] bg-neutral-content/25 group-hover:bg-neutral/40" aria-hidden="true"></span>
						<?php endif; ?>
						<span><?php echo esc_html( $lp_bit ); ?></span>
					<?php endforeach; ?>
				</span>
				<span class="inline-flex items-center gap-2 py-[11px] px-4 bg-neutral-content text-neutral font-label text-[10px] font-bold uppercase tracking-[1px] group-hover:bg-neutral group-hover:text-primary">THE DETAILS</span>
			</div>
		</div>
	</a>
	<?php
};

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/classes-header-cluster',
		array(
			'crumbs'      => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'CLASSES',
					'href'  => $lp_agenda,
				),
				array( 'label' => 'WORKSHOPS' ),
			),
			'action'      => array(
				'label' => 'PRIVATE COACHING ↗',
				'href'  => $lp_private,
			),
			'masthead'    => array(
				'title' => 'The workshops.',
				'note'  => 'Three dates still to come, plus the ones already run. Open a workshop for the full details — image, summary, and a clear way in.',
			),
			'active'      => 'workshops',
			'show_filter' => false,
		)
	);
	?>

	<?php if ( $lp_lead instanceof WP_Post ) : ?>
		<?php
		$lp_lead_id     = (int) $lp_lead->ID;
		$lp_lead_loc    = function_exists( 'lp_class_location_id' ) ? lp_class_location_id( $lp_lead_id ) : 0;
		$lp_lead_venue  = $lp_lead_loc ? strtoupper( get_the_title( $lp_lead_loc ) ) : '';
		$lp_lead_dur    = lp_class_workshop_duration_label( $lp_lead_id );
		$lp_lead_price  = lp_class_price_display( $lp_lead_id );
		$lp_lead_meta   = implode( ' · ', array_filter( array( lp_class_workshop_date_label( $lp_lead_id ), $lp_lead_dur, $lp_lead_venue, $lp_lead_price ) ) );
		$lp_lead_levels = get_the_terms( $lp_lead, 'lp_level' );
		$lp_lead_cat    = ( is_array( $lp_lead_levels ) && $lp_lead_levels ) ? strtoupper( $lp_lead_levels[0]->name ) : 'ALL LEVELS';
		$lp_lead_coaches = lp_class_coach_ids( $lp_lead_id );
		$lp_lead_author  = $lp_lead_coaches ? get_the_title( (int) $lp_lead_coaches[0] ) : '';
		$lp_lead_excerpt = function_exists( 'get_field' ) ? (string) get_field( 'acf_logline', $lp_lead_id ) : '';
		if ( '' === $lp_lead_excerpt ) {
			$lp_lead_excerpt = (string) get_the_excerpt( $lp_lead );
		}
		$lp_lead_dt = null;
		$lp_lead_raw = lp_clasbpro_raw( $lp_lead_id );
		if ( $lp_lead_raw && ! empty( $lp_lead_raw['start_date'] ) ) {
			$lp_lead_dt = DateTimeImmutable::createFromFormat( 'Y-m-d', (string) $lp_lead_raw['start_date'] );
		}
		?>
		<div class="w-full bg-accent" data-component="workshop-overview-lead">
			<div class="px-6 lg:px-16 py-scale-xl flex flex-col gap-[22px]">
				<div class="flex flex-wrap items-center gap-4">
					<?php
					lp_part(
						'elements/status',
						array(
							'variant' => 'live',
							'label'   => 'NEXT UP',
							'surface' => 'accent',
						)
					);
					?>
				</div>
				<?php if ( '' !== $lp_lead_meta ) : ?>
					<p class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-accent-content/70"><?php echo esc_html( $lp_lead_meta ); ?></p>
				<?php endif; ?>
				<?php lp_part( 'elements/rule', array( 'tone' => 'accent' ) ); ?>
				<?php
				lp_part(
					'components/blog-card',
					array(
						'variant'    => 'lead',
						'title'      => get_the_title( $lp_lead ),
						'excerpt'    => $lp_lead_excerpt,
						'category'   => $lp_lead_cat,
						'author'     => $lp_lead_author,
						'date'       => $lp_lead_dt ? $lp_lead_dt->format( 'j M Y' ) : '',
						'href'       => (string) get_permalink( $lp_lead ),
						'cta_label'  => 'THE DETAILS',
						'image_id'   => lp_class_image_id( $lp_lead_id ),
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $lp_rest ) : ?>
		<section class="w-full bg-neutral" data-component="workshop-overview-upcoming">
			<div class="px-6 lg:px-16 pt-16 pb-20 flex flex-col gap-9">
				<div>
					<div class="flex items-end justify-between gap-4 pb-[14px]">
						<div class="flex flex-col gap-2">
							<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-primary">ALSO COMING</span>
							<h2 class="font-heading text-[32px] font-semibold tracking-[-0.8px] text-neutral-content m-0 [text-box:normal]">More dates on the board.</h2>
						</div>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-neutral-content/50"><?php echo esc_html( sprintf( '%02d DATES', count( $lp_rest ) ) ); ?></span>
					</div>
					<div class="h-px w-full bg-neutral-content/20" aria-hidden="true"></div>
				</div>
				<div class="flex flex-col gap-6">
					<?php foreach ( $lp_rest as $lp_item ) : ?>
						<?php $lp_row( $lp_item ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $lp_past ) : ?>
		<section class="w-full bg-base-100" data-component="workshop-overview-past">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-9">
				<div>
					<div class="flex items-end justify-between gap-4 pb-[14px]">
						<div class="flex flex-col gap-2">
							<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-base-content/65">ALREADY RUN</span>
							<h2 class="font-heading text-[32px] font-semibold tracking-[-0.8px] text-base-content m-0 [text-box:normal]">Dates that have been.</h2>
						</div>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65"><?php echo esc_html( sprintf( '%02d DATES', count( $lp_past ) ) ); ?></span>
					</div>
					<div class="h-px w-full bg-base-content/20" aria-hidden="true"></div>
				</div>
				<div class="flex flex-col gap-6">
					<?php foreach ( $lp_past as $lp_item ) : ?>
						<?php $lp_row( $lp_item ); ?>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CLASS TIMETABLE',
				'label'   => 'This week, hour by hour',
				'href'    => $lp_agenda,
			),
			'next' => array(
				'keyword' => 'PRIVATE COACHING →',
				'label'   => 'One coach, any of six sites',
				'href'    => $lp_private,
			),
		)
	);
	?>
</main>

<?php
get_footer();
