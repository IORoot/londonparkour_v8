<?php
/**
 * Template Name: Private 1:1
 *
 * Private 1:1 sales landing. Ported from
 * src/stories/Pages/PrivateCoaching/PrivateCoaching.js (`R1MB21`).
 *
 * Unique layout — not the homepage private-coaching block. Chrome in this
 * template; copy defaults transcribed from the Storybook page (which itself
 * follows the pen after the £65 / £40 and appointment-booking corrections).
 * Coach cards read `lp_coach` records (the pen used placeholder names).
 *
 * BOOK 1:1 opens the shared clasbpro appointment overlay when
 * `appointment_class` is set on this page (group_lp_private_coaching).
 * Opening portrait is the featured image.
 *
 * Landmark contract: nav and footer outside the one <main>, the H1 inside it.
 *
 * Seeded at slug `private-coaching` (`/private-coaching/`).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_page_id = get_the_ID();

$lp_eyebrow   = '04 — PRIVATE 1:1';
$lp_headline  = "One coach.\nJust you.";
$lp_lead      = "Private sessions move at your pace — whether that's a first wall you'd rather not meet in front of a group, a comeback after injury, or one specific line you've been stuck on for months.";
$lp_fare_label = 'FROM';
$lp_amount    = '£65';
$lp_unit      = 'PER SESSION · BLOCKS OF 5 AVAILABLE';
$lp_reassure  = 'Confirmed instantly. Cancellation policy applies — see FAQs below.';
$lp_book_label = 'BOOK 1:1';

$lp_audience = array(
	array(
		'label' => 'FIRST WALL',
		'desc'  => "Haven't trained outdoors before — or not comfortably. A 1:1 session removes the social pressure and lets you move at the pace that makes sense for your body.",
	),
	array(
		'label' => 'COMEBACK',
		'desc'  => 'Returning after injury, time off, or a confidence dip. One coach who knows your history is safer and faster than any group setting.',
	),
	array(
		'label' => 'STUCK LINE',
		'desc'  => "There's a specific movement, spot or technique you can't progress past. We bring it apart, rebuild the sub-skills, and close the gap.",
	),
	array(
		'label' => 'PAIRS',
		'desc'  => 'Two people training together: same session price, split between you. Good if you both want focused coaching without going full group.',
	),
);

$lp_steps = array(
	array(
		'num'   => '01',
		'title' => 'Open the booking panel',
		'desc'  => "Tap Book 1:1 to open the appointments overlay. You'll see all available slots across London locations.",
	),
	array(
		'num'   => '02',
		'title' => 'Pick a date and location',
		'desc'  => 'Each slot shows the venue and the coach running it. Choose what fits your schedule and confirm.',
	),
	array(
		'num'   => '03',
		'title' => 'Session on the day',
		'desc'  => "Your coach arrives with a plan, adapts it live to how you're moving, and debriefs at the end so you leave with a clear next step.",
	),
);

$lp_faqs = array(
	array(
		'q' => 'Who will this help?',
		'a' => "Anyone. You don't need to be fit already — shy, old, overweight, uncoordinated or injured, none of it matters. We give you the coaching to become fit, healthy, mobile and functional.",
	),
	array(
		'q' => 'What benefit is this to you?',
		'a' => "Uninterrupted, face-to-face time with some of the most experienced parkour coaches in the world. We're not the best performers or competitors — we've taught many of those people.",
	),
	array(
		'q' => 'How experienced are the coaches?',
		'a' => "Teaching since 2005: Olympic athletes, special forces, emergency services, people with disabilities, and everyone in between. Practical, functional movement — that's the qualification.",
	),
	array(
		'q' => 'What will you get?',
		'a' => 'Whatever you want to work on — fundamentals, strength, recovery, injury prevention, or how you live. No topic yet? The coach judges what you need and leads.',
	),
);

$lp_media_id = $lp_page_id ? (int) get_post_thumbnail_id( $lp_page_id ) : 0;
$lp_appt_id  = $lp_page_id && function_exists( 'get_field' ) ? absint( get_field( 'appointment_class', $lp_page_id ) ) : 0;

$lp_coach_query = new WP_Query(
	array(
		'post_type'      => 'lp_coach',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'no_found_rows'  => true,
	)
);
$lp_coaches = array();
foreach ( $lp_coach_query->posts as $lp_coach_post ) {
	if ( ! $lp_coach_post instanceof WP_Post ) {
		continue;
	}
	$lp_cid       = (int) $lp_coach_post->ID;
	$lp_coaches[] = array(
		'name'  => get_the_title( $lp_cid ),
		'role'  => function_exists( 'get_field' ) ? (string) get_field( 'role', $lp_cid ) : '',
		'photo' => has_post_thumbnail( $lp_cid ) ? (int) get_post_thumbnail_id( $lp_cid ) : 0,
	);
}

$lp_agenda    = function_exists( 'lp_classes_page_url' ) ? lp_classes_page_url( 'classes' ) : home_url( '/classes/' );
$lp_workshops = function_exists( 'lp_workshops_url' ) ? lp_workshops_url() : home_url( '/workshops/' );

$lp_book_button = static function ( string $variant ) use ( $lp_appt_id, $lp_book_label ): void {
	if ( $lp_appt_id > 0 && function_exists( 'lp_class_book_button_args' ) ) {
		$lp_btn                         = lp_class_book_button_args( $lp_appt_id, '', $lp_book_label, $variant );
		$lp_btn['trailing_icon_id']     = 'icon-arrow-right';
		$lp_btn['data_attrs']['data-lp-list'] = 'private-coaching';
		lp_part( 'elements/button', $lp_btn );
		return;
	}

	lp_part(
		'elements/button',
		array(
			'variant'          => $variant,
			'label'            => $lp_book_label,
			'trailing_icon_id' => 'icon-arrow-right',
			'command'          => 'show-modal',
			'command_for'      => 'lp-booking-drawer',
		)
	);
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
				array(
					'label' => 'CLASSES',
					'href'  => $lp_agenda,
				),
				array( 'label' => 'PRIVATE 1:1' ),
			),
			'action' => array(
				'label' => 'AGENDA ↗',
				'href'  => $lp_agenda,
			),
		)
	);
	if ( function_exists( 'lp_classes_view_tabs' ) ) {
		lp_part(
			'components/view-rail',
			array(
				'tabs' => lp_classes_view_tabs( 'private' ),
			)
		);
	}
	?>

	<section class="w-full bg-neutral" data-component="private-opening">
		<div class="flex flex-col lg:flex-row lg:items-stretch lg:min-h-[860px]">
			<div class="relative w-full aspect-[4/5] lg:aspect-auto lg:w-1/2 min-h-[320px] overflow-hidden bg-neutral">
				<?php
				if ( $lp_media_id ) {
					lp_part(
						'components/media-photo',
						array(
							'image_id'      => $lp_media_id,
							'layout'        => 'fill',
							'size'          => 'lp_portrait_lg',
							'sizes'         => '(min-width: 1024px) 50vw, 100vw',
							'class'         => 'absolute inset-0 h-full w-full object-cover',
							'loading'       => 'eager',
							'fetchpriority' => 'high',
						)
					);
				}
				?>
			</div>
			<div class="w-full lg:w-1/2 flex flex-col justify-end gap-6 px-6 py-scale-2xl lg:px-16 bg-neutral">
				<span class="font-label text-[11px] font-semibold tracking-[1.5px] uppercase text-primary"><?php echo esc_html( $lp_eyebrow ); ?></span>
				<h1 class="font-heading text-step-5 font-bold leading-[0.92] tracking-[-2.4px] text-neutral-content m-0"><?php echo nl2br( esc_html( $lp_headline ), false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html then nl2br. ?></h1>
				<p class="font-body text-[16px] font-normal leading-[1.55] tracking-[0.1px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_lead ); ?></p>
				<div class="flex items-end gap-3 flex-wrap">
					<span class="font-label text-[11px] font-semibold tracking-[1.1px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_fare_label ); ?></span>
					<span class="font-heading text-[56px] font-bold tracking-[-2px] leading-[0.9] text-neutral-content"><?php echo esc_html( $lp_amount ); ?></span>
					<span class="font-label text-[11px] font-normal tracking-[0.8px] uppercase text-neutral-content/50"><?php echo esc_html( $lp_unit ); ?></span>
				</div>
				<div>
					<?php $lp_book_button( 'primary' ); ?>
				</div>
				<p class="font-label text-[11px] font-normal tracking-[0.2px] leading-[1.5] text-neutral-content/50 m-0"><?php echo esc_html( $lp_reassure ); ?></p>
			</div>
		</div>
	</section>

	<section class="w-full bg-base-100" data-component="private-who">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<header class="flex items-end justify-between gap-4 flex-wrap">
				<h2 class="font-heading text-[40px] font-bold leading-[0.92] tracking-[-1.2px] text-base-content m-0">Who it is for</h2>
				<span class="font-label text-[11px] font-semibold tracking-[1.4px] uppercase text-base-content/65">02 — PRIVATE 1:1</span>
			</header>
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
			<ul class="list-none m-0 p-0" role="list">
				<?php foreach ( $lp_audience as $lp_row ) : ?>
					<li class="flex flex-col sm:flex-row gap-4 sm:gap-8 py-7 border-b border-base-300">
						<span class="font-label text-[11px] font-semibold tracking-[1.4px] uppercase text-base-content sm:w-40 shrink-0"><?php echo esc_html( $lp_row['label'] ); ?></span>
						<p class="font-body text-[16px] font-normal leading-[1.55] tracking-[0.1px] text-base-content/65 m-0"><?php echo esc_html( $lp_row['desc'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>

	<section class="w-full bg-neutral" data-component="private-how">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-14">
			<header class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
				<h2 class="font-heading text-[40px] font-bold leading-[0.92] tracking-[-1.2px] text-neutral-content m-0">How a session works</h2>
				<p class="font-body text-[15px] font-normal leading-[1.5] text-neutral-content/50 m-0 lg:max-w-[320px]">No request form. Pick a slot and it confirms instantly.</p>
			</header>
			<ol class="flex flex-col lg:flex-row gap-10 lg:gap-0 list-none m-0 p-0" role="list">
				<?php foreach ( $lp_steps as $lp_step ) : ?>
					<li class="flex-1 min-w-0 flex flex-col gap-4 lg:pr-12">
						<span class="font-label text-[11px] font-semibold tracking-[1.5px] uppercase text-primary"><?php echo esc_html( $lp_step['num'] ); ?></span>
						<h3 class="font-heading text-[22px] font-bold leading-[1.1] tracking-[-0.5px] text-neutral-content m-0"><?php echo esc_html( $lp_step['title'] ); ?></h3>
						<p class="font-body text-[15px] font-normal leading-[1.55] tracking-[0.1px] text-neutral-content/50 m-0"><?php echo esc_html( $lp_step['desc'] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</section>

	<section class="w-full bg-base-100" data-component="private-fare">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<header class="flex items-end justify-between gap-4 flex-wrap">
				<h2 class="font-heading text-[40px] font-bold leading-[0.92] tracking-[-1.2px] text-base-content m-0">Fare</h2>
				<span class="font-label text-[11px] font-semibold tracking-[1.2px] uppercase text-base-content/65">Blocks of 5 sessions available at a reduced rate.</span>
			</header>
			<div class="h-px w-full bg-base-300" aria-hidden="true"></div>
			<div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-0">
				<div class="flex flex-col gap-4 lg:pr-16" data-fare="one-to-one">
					<span class="font-label text-[11px] font-semibold tracking-[1.4px] uppercase text-base-content">ONE-TO-ONE</span>
					<span class="font-heading text-[72px] font-bold leading-[0.85] tracking-[-3px] text-base-content">£65</span>
					<span class="font-label text-[11px] font-normal tracking-[0.8px] uppercase text-base-content/65">per session / 60 min</span>
					<p class="font-body text-[15px] font-normal leading-[1.55] text-base-content/65 m-0">One coach, one athlete, full attention. Outdoors at a location of your choice.</p>
				</div>
				<div class="flex flex-col gap-4 lg:pl-16" data-fare="shared">
					<span class="font-label text-[11px] font-semibold tracking-[1.4px] uppercase text-base-content">SHARED (2 PEOPLE)</span>
					<span class="font-heading text-[72px] font-bold leading-[0.85] tracking-[-3px] text-base-content">£40</span>
					<span class="font-label text-[11px] font-normal tracking-[0.8px] uppercase text-base-content/65">per person / 60 min</span>
					<p class="font-body text-[15px] font-normal leading-[1.55] text-base-content/65 m-0">Two athletes, same session. Split the price, keep the focus. You organise your own pair.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="w-full bg-accent" data-component="private-coaches">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-12">
			<header class="flex items-end">
				<h2 class="font-heading text-[40px] font-bold leading-[0.92] tracking-[-1.2px] text-accent-content m-0">Coaches</h2>
			</header>
			<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
				<?php foreach ( $lp_coaches as $lp_coach ) : ?>
					<article class="flex flex-col gap-3" data-component="private-coach-card">
						<div class="relative w-full h-[444px] overflow-hidden bg-neutral">
							<?php
							if ( ! empty( $lp_coach['photo'] ) ) {
								lp_part(
									'components/media-photo',
									array(
										'image_id' => (int) $lp_coach['photo'],
										'alt'      => (string) $lp_coach['name'],
										'layout'   => 'fill',
										'size'     => 'lp_portrait_lg',
										'sizes'    => '(min-width: 640px) 33vw, 100vw',
										'class'    => 'absolute inset-0 h-full w-full object-cover',
									)
								);
							}
							?>
						</div>
						<h3 class="font-heading text-[20px] font-bold tracking-[-0.4px] leading-[1.1] text-accent-content m-0"><?php echo esc_html( (string) $lp_coach['name'] ); ?></h3>
						<p class="font-label text-[11px] font-semibold tracking-[1.3px] uppercase text-accent-content/70 m-0"><?php echo esc_html( (string) $lp_coach['role'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="w-full" data-component="private-faq-book">
		<div class="flex flex-col lg:flex-row lg:items-stretch lg:min-h-[840px]">
			<div class="w-full lg:w-1/2 bg-neutral px-6 py-scale-2xl lg:pl-16 lg:pr-16 flex flex-col gap-10">
				<h2 class="font-heading text-[32px] font-bold leading-[0.92] tracking-[-0.8px] text-neutral-content m-0">Common questions</h2>
				<div>
					<?php foreach ( $lp_faqs as $lp_faq ) : ?>
						<div class="flex flex-col gap-2 py-6 border-b border-neutral-content/10">
							<h3 class="font-heading text-[18px] font-bold tracking-[-0.3px] leading-[1.1] text-neutral-content m-0"><?php echo esc_html( $lp_faq['q'] ); ?></h3>
							<p class="font-body text-[15px] font-normal leading-[1.55] text-neutral-content/50 m-0"><?php echo esc_html( $lp_faq['a'] ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="w-full lg:w-1/2 bg-primary px-6 py-scale-2xl lg:pl-16 lg:pr-16 flex flex-col justify-end gap-8">
				<span class="font-label text-[11px] font-semibold tracking-[1.5px] uppercase text-primary-content/70">BOOK WHEN YOU'RE READY</span>
				<h2 class="font-heading text-[40px] font-bold leading-[0.92] tracking-[-1.2px] text-primary-content m-0">Book your session</h2>
				<p class="font-body text-[16px] font-normal leading-[1.55] text-primary-content/70 m-0">Choose a date and location in the booking panel. Each slot shows the venue and the coach running it. Confirms instantly.</p>
				<div>
					<?php $lp_book_button( 'inverse' ); ?>
				</div>
				<p class="font-label text-[11px] font-normal tracking-[0.2px] leading-[1.5] text-primary-content/70 m-0">Instant confirmation · Cancellation policy applies · Blocks of 5 available</p>
			</div>
		</div>
	</section>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CLASSES',
				'label'   => 'The weekly board',
				'href'    => $lp_agenda,
			),
			'next' => array(
				'keyword' => 'WORKSHOPS →',
				'label'   => 'The dates still to come',
				'href'    => $lp_workshops,
			),
		)
	);
	?>
</main>

<?php
get_footer();
