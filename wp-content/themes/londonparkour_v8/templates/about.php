<?php
/**
 * Template Name: About
 *
 * Company story. Ported from src/stories/Pages/About/About.js (`PGxI1`).
 *
 * Unique layout — copy stays in this template (Storybook defaults, which
 * follow the pen after the previous-company-name and ADAPT rewrites). The
 * featured image is the hero. The floor section embeds a YouTube video
 * (`raakvpb_q9E`). Team cards read published `lp_coach` records: `is_lead`
 * is first, then everyone else, in one 4-column row. Photo and name link to `single-lp_coach`. No training-since field exists
 * on the CPT, so the grid tag is omitted. Platform 04 always prints
 * the current calendar year. Delivered To keeps its designed copy; client
 * logos sit under it via the shared Clients block (`layout` embed).
 *
 * Marquee and CTA are the shared blocks. Landmark contract: nav and footer
 * outside the one <main>, the H1 inside it.
 *
 * Seeded at slug `about` (`/about/`).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_page_id = get_the_ID();

$lp_hero_chip = 'EST. 2018';
$lp_coords    = 'N 51.5074°  /  W 0.1278°';
$lp_hero_dek  = 'High-quality, well-taught, reasonably priced, accessible and fun coaching across London. That was the brief Andy set when he founded LondonParkour in 2018 — and it still is.';
$lp_hero_alt  = 'Parkour athletes training on a London rooftop.';

$lp_stats = array(
	array(
		'value'  => '6+',
		'label'  => 'DECADES COMBINED COACH EXPERIENCE',
		'signal' => false,
	),
	array(
		'value'  => '11,000+',
		'label'  => 'STUDENT ATTENDANCES SINCE 2018',
		'signal' => true,
	),
	array(
		'value'  => '5,000+',
		'label'  => 'CLASSES TAUGHT',
		'signal' => false,
	),
);

$lp_history = array(
	array(
		'plt'     => '01',
		'year'    => '2005',
		'service' => 'TRAINING',
		'notes'   => 'Started in 2005. Trained every week. Did not stop.',
		'live'    => false,
	),
	array(
		'plt'     => '02',
		'year'    => '2008',
		'service' => 'COACH',
		'notes'   => 'Became a coach. Helped grow the practice. Designed indoor space. Delivered International Coach Qualifications worldwide.',
		'live'    => false,
	),
	array(
		'plt'     => '03',
		'year'    => '2018',
		'service' => 'LONDONPARKOUR',
		'notes'   => 'Founded LondonParkour — quality, access, and fun, pointed at this city.',
		'live'    => false,
	),
	array(
		'plt'     => '04',
		'year'    => (string) wp_date( 'Y' ),
		'service' => 'NOW',
		'notes'   => "London's leading outdoor parkour classes, private 1:1s and workshops, led by some of the most established coaches in the world.",
		'live'    => true,
	),
);

$lp_ethos = array(
	array(
		'idx'   => '01',
		'title' => 'OLD-SCHOOL STANDARDS',
		'body'  => 'The traditional parkour coaching standards — held, not watered down. Be strong to be useful.',
	),
	array(
		'idx'   => '02',
		'title' => 'MODERN TWIST',
		'body'  => 'Modern movements and skills sit alongside the old work. The practice moves. The standard does not.',
	),
	array(
		'idx'   => '03',
		'title' => 'INTENSE, SCALED',
		'body'  => 'Classes are always intense. They are always scaled to the practitioner in front of us. Everyone works. Nobody is left behind.',
	),
);

$lp_attributes = array(
	'POWER',
	'STRENGTH',
	'SPEED',
	'AGILITY',
	'FEAR',
	'GRIT',
	'BALANCE',
	'PROPRIOCEPTION',
	'FLOW',
	'FOCUS',
	'ACCURACY',
	'+ THE REST',
);

$lp_floor_facts = array(
	array(
		'title' => 'ALL LEVELS',
		'body'  => 'From first session to advanced. Nobody is too early, nobody is too far along.',
	),
	array(
		'title' => 'ALL AGES',
		'body'  => 'Kids, adults, people who thought they had left this kind of thing behind.',
	),
	array(
		'title' => 'THE WORK',
		'body'  => 'Everyone works hard. Everyone enjoys it. That is the whole point.',
	),
);

$lp_stat_tones = array(
	'signal' => 'font-heading text-[43px] font-bold tracking-[-1.5px] leading-none text-accent',
	'ink'    => 'font-heading text-[43px] font-bold tracking-[-1.5px] leading-none text-base-content',
);

$lp_year_tones = array(
	'live' => 'font-heading text-[28px] font-bold tracking-[-1px] leading-none text-primary lg:w-[90px] shrink-0',
	'past' => 'font-heading text-[28px] font-bold tracking-[-1px] leading-none text-neutral-content lg:w-[90px] shrink-0',
);

$lp_service_tones = array(
	'live' => 'font-label text-[12px] font-semibold tracking-[0.8px] uppercase text-primary lg:w-[260px] shrink-0',
	'past' => 'font-label text-[12px] font-semibold tracking-[0.8px] uppercase text-neutral-content lg:w-[260px] shrink-0',
);

$lp_attr_tones = array(
	'hot'  => 'font-label text-[12px] font-semibold tracking-[0.9px] uppercase text-primary',
	'cool' => 'font-label text-[12px] font-semibold tracking-[0.9px] uppercase text-neutral-content',
);

$lp_hero_id  = $lp_page_id ? (int) get_post_thumbnail_id( $lp_page_id ) : 0;

$lp_agenda  = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
$lp_contact = home_url( '/contact/' );
$lp_contact_page = get_page_by_path( 'contact' );
if ( $lp_contact_page instanceof WP_Post ) {
	$lp_contact = (string) get_permalink( $lp_contact_page );
}

$lp_coach_query = new WP_Query(
	array(
		'post_type'              => 'lp_coach',
		'post_status'            => 'publish',
		'posts_per_page'         => 24,
		'orderby'                => 'menu_order title',
		'order'                  => 'ASC',
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => false,
	)
);

if ( $lp_coach_query->have_posts() ) {
	update_post_thumbnail_cache( $lp_coach_query );
}

$lp_mapped = array();
$lp_i      = 0;
foreach ( $lp_coach_query->posts as $lp_post ) {
	if ( ! $lp_post instanceof WP_Post ) {
		continue;
	}
	++$lp_i;
	$lp_cid = (int) $lp_post->ID;
	$lp_mapped[] = array(
		'id'      => $lp_cid,
		'index'   => sprintf( '%02d', $lp_i ),
		'name'    => get_the_title( $lp_cid ),
		'role'    => function_exists( 'get_field' ) ? (string) get_field( 'role', $lp_cid ) : '',
		'bio'     => function_exists( 'get_field' ) ? (string) get_field( 'bio', $lp_cid ) : '',
		'photo'   => has_post_thumbnail( $lp_cid ) ? (int) get_post_thumbnail_id( $lp_cid ) : 0,
		'href'    => (string) get_permalink( $lp_cid ),
		'is_lead' => function_exists( 'get_field' ) ? (bool) get_field( 'is_lead', $lp_cid ) : false,
	);
}

$lp_founder = null;
$lp_coaches = array();
foreach ( $lp_mapped as $lp_row ) {
	if ( $lp_row['is_lead'] && null === $lp_founder ) {
		$lp_founder = $lp_row;
		continue;
	}
	$lp_coaches[] = $lp_row;
}
if ( null === $lp_founder && $lp_mapped ) {
	$lp_founder = $lp_mapped[0];
	$lp_coaches = array_slice( $lp_mapped, 1 );
}

$lp_n = 0;
if ( $lp_founder ) {
	++$lp_n;
	$lp_founder['index'] = sprintf( '%02d', $lp_n );
}
foreach ( $lp_coaches as $lp_ci => $lp_row ) {
	++$lp_n;
	$lp_coaches[ $lp_ci ]['index'] = sprintf( '%02d', $lp_n );
}

$lp_team = array();
if ( $lp_founder ) {
	$lp_team[] = $lp_founder;
}
foreach ( $lp_coaches as $lp_row ) {
	$lp_team[] = $lp_row;
}

$lp_heads = array(
	'page'   => array(
		'note' => 'font-label text-[12px] font-normal tracking-[0.5px] uppercase text-base-content/65',
		'rule' => 'h-px w-full bg-base-300',
	),
	'accent' => array(
		'note' => 'font-label text-[12px] font-normal tracking-[0.5px] uppercase text-accent-content/70',
		'rule' => 'h-px w-full bg-accent-content/15',
	),
	'board'  => array(
		'note' => 'font-label text-[12px] font-normal tracking-[0.5px] uppercase text-neutral-content/50',
		'rule' => 'h-px w-full bg-neutral-content/10',
	),
);

$lp_section_head = static function ( string $eyebrow, string $meta, array $tone ): void {
	?>
	<header class="flex flex-col gap-[18px] w-full">
		<div class="flex items-baseline justify-between gap-4">
			<span class="<?php echo esc_attr( $tone['note'] ); ?>"><?php echo esc_html( $eyebrow ); ?></span>
			<span class="<?php echo esc_attr( $tone['note'] ); ?>"><?php echo esc_html( $meta ); ?></span>
		</div>
		<div class="<?php echo esc_attr( $tone['rule'] ); ?>" aria-hidden="true"></div>
	</header>
	<?php
};

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array( 'label' => 'ABOUT' ),
			),
			'action' => array(
				'label' => 'CLASSES ↗',
				'href'  => $lp_agenda,
			),
		)
	);
	?>

	<section class="relative w-full min-h-[640px] lg:min-h-[780px] bg-neutral overflow-hidden" data-component="about-hero">
		<?php
		if ( $lp_hero_id ) {
							lp_part(
								'components/media-photo',
								array(
									'image_id'      => $lp_hero_id,
									'alt'          => $lp_hero_alt,
									'layout'        => 'none',
									'size'          => 'lp_wide_lg',
									'sizes'         => '100vw',
									'class'         => 'absolute inset-0 h-full w-full object-cover',
									'loading'       => 'eager',
									'fetchpriority' => 'high',
								)
							);
		}
		?>
		<div class="absolute inset-0 bg-gradient-to-t from-neutral via-neutral/70 to-transparent" aria-hidden="true"></div>
		<div class="absolute inset-0 z-[1] pointer-events-none opacity-[0.16]" aria-hidden="true" data-slot="grid">
			<div class="absolute inset-0 flex">
				<?php for ( $lp_i = 0; $lp_i < 13; $lp_i++ ) : ?>
					<span class="flex-1 border-l border-neutral-content last:border-r"></span>
				<?php endfor; ?>
			</div>
			<div class="absolute left-0 right-0 top-[12%] h-px bg-neutral-content"></div>
			<div class="absolute left-0 right-0 top-[35%] h-px bg-neutral-content"></div>
			<div class="absolute left-0 right-0 top-[65%] h-px bg-neutral-content"></div>
			<div class="absolute left-0 right-0 top-[88%] h-px bg-neutral-content"></div>
		</div>
		<span class="absolute z-10 top-6 right-6 lg:top-10 lg:right-16 font-label text-[10px] font-semibold tracking-[1px] uppercase text-primary m-0" data-slot="hero-coords"><?php echo esc_html( $lp_coords ); ?></span>
		<div class="relative z-10 flex flex-col justify-end gap-7 min-h-[640px] lg:min-h-[780px] px-6 lg:px-16 pb-16">
			<div class="self-start">
				<?php
				lp_part(
					'elements/chip',
					array(
						'variant' => 'signal',
						'label'   => $lp_hero_chip,
					)
				);
				?>
			</div>
			<div class="flex flex-col gap-8 w-full">
				<h1 class="font-display text-step-5 lg:text-step-7 font-bold tracking-[-0.04em] leading-[0.88] m-0 pb-3">
					<span class="block text-neutral-content">Work hard.</span>
					<span class="block text-primary">Enjoy it.</span>
				</h1>
				<p class="font-body text-[14px] font-normal tracking-[0.1px] leading-[1.55] text-neutral-content/50 m-0 max-w-[560px]"><?php echo esc_html( $lp_hero_dek ); ?></p>
			</div>
			<div class="flex items-center gap-4 flex-wrap">
				<?php
				lp_part(
					'elements/button',
					array(
						'variant'          => 'primary',
						'label'            => 'BOOK A CLASS',
						'href'             => $lp_agenda,
						'trailing_icon_id' => 'icon-arrow-right',
					)
				);
				?>
				<a href="#team" class="font-label text-[12px] font-semibold tracking-[1px] uppercase text-neutral-content hover:text-primary transition-colors duration-150">MEET THE TEAM ↓</a>
			</div>
		</div>
	</section>

	<?php
	lp_render_block(
		'marquee',
		array(
			'items' => array(
				array( 'label' => 'BE STRONG TO BE USEFUL' ),
				array( 'label' => 'OLD-SCHOOL STANDARDS' ),
				array( 'label' => 'MODERN TWIST' ),
				array( 'label' => 'INTENSE BUT SCALED' ),
				array( 'label' => 'ALL AGES' ),
				array( 'label' => 'ALL LEVELS' ),
				array( 'label' => 'WORK HARD' ),
				array( 'label' => 'ENJOY IT' ),
			),
		)
	);
	?>

	<section class="w-full bg-base-100" data-component="about-line">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<?php $lp_section_head( '01 — THE LINE', '2005 → 2018', $lp_heads['page'] ); ?>
			<h2 class="font-heading text-step-3 font-semibold leading-[1.02] tracking-[-1.6px] text-base-content m-0">From the classes that made him to the ones he runs now.</h2>
			<div class="flex flex-col lg:flex-row gap-16 items-start">
				<div class="flex-1 min-w-0 flex flex-col gap-5">
					<p class="font-body text-[16px] font-normal leading-[1.55] text-base-content m-0">Andy started in 2005. He trained consistently, became a coach in 2008, and spent a decade helping grow the practice — teaching, designing indoor space, and delivering coach education around the world.</p>
					<p class="font-body text-[16px] font-normal leading-[1.55] text-base-content/65 m-0">In 2018 he founded LondonParkour: high-quality, well-taught, reasonably priced, accessible and fun coaching, pointed at this city.</p>
					<p class="font-heading text-[22px] font-semibold tracking-[-0.6px] leading-[1.25] text-accent m-0">“High-quality. Well taught. Reasonably priced. Accessible. Fun.”</p>
				</div>
				<div class="w-full lg:w-[360px] shrink-0 border-l border-base-300">
					<?php foreach ( $lp_stats as $lp_stat ) : ?>
						<div class="flex flex-col gap-1 py-5 pl-6 border-b border-base-300" data-component="about-stat">
							<span class="<?php echo esc_attr( $lp_stat['signal'] ? $lp_stat_tones['signal'] : $lp_stat_tones['ink'] ); ?>"><?php echo esc_html( $lp_stat['value'] ); ?></span>
							<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-base-content/65"><?php echo esc_html( $lp_stat['label'] ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="w-full bg-neutral" data-component="about-history">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
			<div class="flex items-end justify-between gap-4">
				<div class="flex flex-col gap-3 min-w-0">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-primary">02 — SERVICE HISTORY</span>
					<h2 class="font-heading text-step-2 font-semibold leading-none tracking-[-1.2px] text-neutral-content m-0">The line before LondonParkour.</h2>
				</div>
				<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-primary shrink-0">LIVE</span>
			</div>
			<div class="h-px w-full bg-neutral-content/10" aria-hidden="true"></div>
			<div class="hidden lg:flex items-center justify-between pb-2">
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50 w-[72px]">PLT</span>
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50 w-[90px]">YEAR</span>
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50 w-[260px]">SERVICE</span>
				<span class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50 flex-1">NOTES</span>
			</div>
			<?php foreach ( $lp_history as $lp_row ) : ?>
				<div class="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-0 py-[22px] border-b border-neutral-content/10" data-component="about-history-row">
					<span class="font-label text-[12px] font-semibold tracking-[1px] uppercase text-primary lg:w-[72px] shrink-0"><?php echo esc_html( $lp_row['plt'] ); ?></span>
					<span class="<?php echo esc_attr( $lp_row['live'] ? $lp_year_tones['live'] : $lp_year_tones['past'] ); ?>"><?php echo esc_html( $lp_row['year'] ); ?></span>
					<span class="<?php echo esc_attr( $lp_row['live'] ? $lp_service_tones['live'] : $lp_service_tones['past'] ); ?>"><?php echo esc_html( $lp_row['service'] ); ?></span>
					<p class="font-body text-[14px] font-normal leading-[1.45] text-neutral-content/50 m-0 min-w-0"><?php echo esc_html( $lp_row['notes'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="w-full bg-accent" data-component="about-delivered">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<?php $lp_section_head( '03 — DELIVERED TO', 'WORKSHOPS + SEMINARS', $lp_heads['accent'] ); ?>
			<div class="flex flex-col gap-4">
				<h2 class="font-heading text-step-3 font-semibold leading-[0.95] tracking-[-1.6px] text-accent-content m-0">Army, palace, paper, punk.</h2>
				<p class="font-body text-[16px] font-normal leading-[1.5] text-accent-content/70 m-0 max-w-[640px]">Workshops and seminars for the British Army, the MOD and Special Forces — and for brands including The Stranglers, Decathlon, The Guardian and Sandringham Palace.</p>
			</div>
			<?php
			lp_render_block(
				'clients',
				array(
					'layout' => 'embed',
				)
			);
			?>
		</div>
	</section>

	<section class="w-full bg-base-100" data-component="about-ethos">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
			<?php $lp_section_head( '04 — THE ETHOS', 'BE STRONG TO BE USEFUL', $lp_heads['page'] ); ?>
			<h2 class="font-heading text-step-4 font-semibold leading-[0.95] tracking-[-2.4px] text-base-content m-0">Work hard. Enjoy it while you do.</h2>
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-10 lg:gap-6">
				<?php foreach ( $lp_ethos as $lp_item ) : ?>
					<div class="flex flex-col gap-4 min-w-0">
						<span class="block w-6 h-0.5 bg-accent" aria-hidden="true"></span>
						<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-accent"><?php echo esc_html( $lp_item['idx'] ); ?></span>
						<h3 class="font-label text-[12px] font-semibold tracking-[0.9px] uppercase text-base-content m-0"><?php echo esc_html( $lp_item['title'] ); ?></h3>
						<p class="font-body text-[14px] font-normal leading-[1.5] text-base-content/65 m-0"><?php echo esc_html( $lp_item['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="w-full bg-neutral" data-component="about-practice">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-10">
			<?php $lp_section_head( '05 — THE PRACTICE', 'USEFUL, NOT FUNCTIONAL', $lp_heads['board'] ); ?>
			<div class="flex flex-col gap-2">
				<h2 class="font-heading text-step-4 font-semibold leading-[0.95] tracking-[-2.4px] text-neutral-content m-0">Not functional.</h2>
				<p class="font-heading text-step-4 font-semibold leading-[0.95] tracking-[-2.4px] text-primary m-0">Useful.</p>
			</div>
			<div class="flex flex-col lg:flex-row gap-16 items-start">
				<div class="flex-1 min-w-0 flex flex-col gap-5">
					<p class="font-body text-[16px] font-normal leading-[1.55] text-neutral-content/50 m-0">We train practical movement. Not a workout that calls itself functional — the actual skills that are useful. Run, climb, vault, land, keep going. The work is to realise what you can already do, then attain it.</p>
					<p class="font-body text-[16px] font-normal leading-[1.55] text-neutral-content/50 m-0">This is not high-flying building jumping. It is not flips. It is a discipline built for anyone who will show up. The city is the playground and the facility. You will see it differently. You will train in a way you have never trained before.</p>
					<p class="font-heading text-[32px] font-semibold tracking-[-1px] text-primary m-0">Become a pathfinder.</p>
				</div>
				<div class="w-full lg:w-[420px] shrink-0 border-l border-neutral-content/10">
					<p class="font-label text-[10px] font-semibold tracking-[1.1px] uppercase text-primary pl-6 pb-4 m-0">ATTRIBUTES + SKILLS</p>
					<?php foreach ( $lp_attributes as $lp_label ) : ?>
						<?php $lp_hot = ( 'FEAR' === $lp_label || 'GRIT' === $lp_label ); ?>
						<div class="flex items-center justify-between py-3 pl-6 border-b border-neutral-content/10">
							<span class="<?php echo esc_attr( $lp_hot ? $lp_attr_tones['hot'] : $lp_attr_tones['cool'] ); ?>"><?php echo esc_html( $lp_label ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	</section>

	<section class="w-full bg-base-100" data-component="about-team" id="team">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<?php $lp_section_head( '06 — THE TEAM', 'LONDON COMMUNITY', $lp_heads['page'] ); ?>
			<div class="flex flex-col gap-4">
				<h2 class="font-heading text-step-3 font-semibold leading-[0.95] tracking-[-1.6px] text-base-content m-0">The people who teach the practice.</h2>
				<p class="font-body text-[16px] font-normal leading-[1.5] text-base-content/65 m-0 max-w-[640px]">Every coach has been part of the London community for decades — and is highly respected as a parkour coach. They remember being the person at the back of the class.</p>
			</div>
			<?php if ( $lp_team ) : ?>
				<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
					<?php foreach ( $lp_team as $lp_coach ) : ?>
						<article class="flex flex-col" data-component="coach-grid-card">
							<a href="<?php echo esc_url( (string) ( $lp_coach['href'] ?? '' ) ); ?>" class="relative aspect-[3/4] overflow-hidden bg-base-300">
								<?php
								if ( ! empty( $lp_coach['photo'] ) ) {
									lp_part(
										'components/media-photo',
										array(
											'image_id' => (int) $lp_coach['photo'],
											'alt'      => (string) $lp_coach['name'],
											'layout'   => 'fill',
											'size'     => 'lp_portrait_lg',
											'sizes'    => '(min-width: 1024px) 25vw, 50vw',
										)
									);
								}
								?>
							</a>
							<div class="flex flex-col gap-1.5 lg:gap-2.5 pt-3 lg:pt-5">
								<div class="flex items-baseline justify-between gap-3">
									<span class="font-label text-[11px] font-semibold tracking-[0.6px] text-base-content/65"><?php echo esc_html( (string) $lp_coach['index'] ); ?></span>
								</div>
								<h3 class="font-heading text-[16px] lg:text-[22px] font-semibold tracking-[-0.4px] leading-tight text-base-content m-0">
									<a href="<?php echo esc_url( (string) ( $lp_coach['href'] ?? '' ) ); ?>" class="hover:text-accent transition-colors duration-150"><?php echo esc_html( (string) $lp_coach['name'] ); ?></a>
								</h3>
								<p class="font-label text-[10px] font-semibold tracking-[0.8px] uppercase text-base-content/65 m-0"><?php echo esc_html( (string) $lp_coach['role'] ); ?></p>
								<?php if ( '' !== (string) $lp_coach['bio'] ) : ?>
									<p class="font-body text-[12px] lg:text-[13px] leading-[1.45] lg:leading-[1.55] text-base-content/70 m-0"><?php echo esc_html( (string) $lp_coach['bio'] ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<section class="w-full bg-neutral" data-component="about-floor">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-9">
			<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-primary">07 — THE FLOOR</span>
			<h2 class="font-heading text-step-3 font-semibold leading-[0.95] tracking-[-1.6px] text-neutral-content m-0">Regulars. All abilities. All ages.</h2>
			<p class="font-body text-[16px] font-normal leading-[1.55] text-neutral-content/50 m-0 max-w-[720px]">A solid group of people who show up, work hard, and love what they do. Beginners next to people who have been on this floor for years. Classes stay intense — and stay scaled to whoever is in them.</p>
			<div class="relative w-full overflow-hidden bg-secondary aspect-video [&>iframe]:absolute [&>iframe]:inset-0 [&>iframe]:size-full m-0" data-component="about-floor-video">
				<iframe src="https://www.youtube-nocookie.com/embed/raakvpb_q9E" title="YouTube video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
			</div>
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
				<?php foreach ( $lp_floor_facts as $lp_fact ) : ?>
					<div class="flex flex-col gap-2.5 pl-6 border-l border-neutral-content/10 min-w-0">
						<h3 class="font-label text-[12px] font-semibold tracking-[0.9px] uppercase text-primary m-0"><?php echo esc_html( $lp_fact['title'] ); ?></h3>
						<p class="font-body text-[14px] font-normal leading-[1.5] text-neutral-content/50 m-0"><?php echo esc_html( $lp_fact['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	lp_render_block(
		'cta',
		array(
			'kicker'          => '10 — START',
			'coordinates'     => 'N 51.5074° / W 0.1278°',
			'headline'        => 'Take your first step.',
			'subhead'         => 'Beginners sessions run Tuesday and Thursday at 18:30 in Vauxhall. Fifteen pounds and no prior experience of any kind.',
			'primary_action'  => array(
				'link' => array(
					'title'  => 'BOOK YOUR FIRST CLASS',
					'url'    => $lp_agenda,
					'target' => '',
				),
			),
			'alt_action'      => array(
				'link' => array(
					'title'  => 'Or send us a question ↗',
					'url'    => $lp_contact,
					'target' => '',
				),
			),
		)
	);

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CONTACT',
				'label'   => 'Questions, workshops, partnerships',
				'href'    => $lp_contact,
			),
			'next' => array(
				'keyword' => 'CLASSES →',
				'label'   => 'Start with the beginners board',
				'href'    => $lp_agenda,
			),
		)
	);
	?>
</main>

<?php
get_footer();
