<?php
/**
 * 404 — the service board and the destinations band.
 *
 * Ported from src/stories/Pages/NotFound/NotFound.js.
 *
 * Two sections inside one <main>; nav and footer are get_header()/get_footer()
 * and stay outside it, which is the landmark contract the source asserts.
 *
 * The service board is the FIXED dark band (`bg-neutral`) and the "Where To
 * Next" panel is `bg-secondary`, the recessed board surface — the same two-tier
 * dark treatment BookingDrawer uses. Both keep the `neutral-content` family;
 * `secondary-content` and `neutral-content` are the same value in all four
 * themes. Never `base-content` on either.
 *
 * The search input, the ghost buttons and the submit button are hand-built
 * rather than `forms/field.php` / `elements/button.php` instances, following
 * the precedent the source sets and documents: both of those hardcode
 * `base-content` / `btn-outline` colours that go invisible on this band in the
 * two light themes, and `button.php` emits `type="button"` with no override, so
 * it cannot produce a submit control at all. Giving those parts a `surface`
 * axis is a change to shipped elements — flagged in PORT-FINDINGS §12, not
 * patched locally.
 *
 * ONE DELIBERATE DEPARTURE: the source's form is inert (`preventDefault`)
 * because the Storybook has no results route. WordPress does — `search.php` —
 * so the form gets `method="get"`, `action` on the site root and `name="s"`,
 * and actually works. The source's own comment says it is inert only for want
 * of a route.
 *
 * The H1 uses the .pen MASTER's sentence, not the page instance's literal
 * "404 " override, which reads as an unfinished edit and duplicates the eyebrow
 * above it. That was the repo owner's decision; both strings are recorded in
 * the source's own docblock.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/* Whole literal strings. Tailwind v4 scans source text — never build a class. */
$lp_ghost_btn = 'inline-flex items-center gap-3 px-6 py-[15px] border border-neutral-content/20 font-label text-[12px] font-semibold uppercase tracking-[1px] text-neutral-content hover:border-neutral-content/40 transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';
$lp_cta_btn   = 'flex items-center justify-between gap-3 px-[22px] h-[60px] bg-primary text-primary-content font-label text-[12px] font-semibold uppercase tracking-[1px] hover:bg-primary/85 transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary';

$lp_status_label = 'SERVICE STATUS — 404 NOT FOUND';
$lp_headline     = 'This service does not run.';
$lp_lead         = "The page you asked for isn't on the board. It may have been retired, renamed, or you followed a link that has since expired. Everything else is still running.";

$lp_search = array(
	'label'       => 'SEARCH THE SITE',
	// The curly quotes are literal in the source.
	'hint'        => 'TRY “BEGINNERS” OR “VAUXHALL”',
	'placeholder' => 'What were you looking for?',
	'submit'      => 'SEARCH',
);

$lp_ghost_actions = array(
	array(
		'label' => 'BACK TO HOME',
		'href'  => home_url( '/' ),
	),
	array(
		'label' => 'REPORT A BROKEN LINK',
		'href'  => '/contact',
	),
);

$lp_panel = array(
	'title' => 'WHERE TO NEXT',
	'live'  => 'ALL RUNNING',
	'rows'  => array(
		array(
			'label' => 'TIMETABLE',
			'value' => "This week's board",
		),
		array(
			'label' => 'TUTORIALS',
			'value' => 'Twelve free lessons',
		),
		array(
			'label' => 'PRICING',
			'value' => 'From £8 a session',
		),
		array(
			'label' => 'CONTACT',
			'value' => 'Talk to a human',
		),
	),
	'cta'   => array(
		'label' => "SEE THIS WEEK'S BOARD",
		'href'  => '/classes',
	),
	'foot'  => "Still stuck? Email hello@londonparkour.com and we'll find what you were after.",
);

$lp_destinations = array(
	array(
		'kicker' => 'DESTINATION 01',
		'title'  => "This week's board",
		'meta'   => '40+ sessions · six sites · drop in from £8',
		'href'   => '/classes',
	),
	array(
		'kicker' => 'DESTINATION 02',
		'title'  => 'Free tutorials',
		'meta'   => 'Twelve lessons · watch before you turn up',
		'href'   => '/tutorials',
	),
	array(
		'kicker' => 'DESTINATION 03',
		'title'  => 'Talk to a human',
		'meta'   => "We'll find whatever you were after",
		'href'   => '/contact',
	),
);

get_header();
?>

<main id="main">
	<div class="w-full bg-neutral" data-component="not-found-board">
		<div class="px-6 lg:px-16 pt-[44px] pb-[96px] border-t border-neutral-content/20">
			<div class="flex flex-col lg:flex-row gap-12 lg:gap-20">

				<div class="flex-1 min-w-0 flex flex-col">
					<div>
						<?php
						lp_part(
							'elements/status',
							array(
								'variant' => 'live',
								'surface' => 'board',
								'label'   => $lp_status_label,
							)
						);
						?>
					</div>
					<h1 class="font-display text-[clamp(2.75rem,7vw,4.5rem)] font-bold tracking-[-3.2px] leading-[0.94] text-neutral-content m-0 mt-[28px]"><?php echo esc_html( $lp_headline ); ?></h1>
					<p class="font-body text-[14px] font-normal tracking-[0.1px] leading-[1.7] text-neutral-content/50 m-0 mt-[26px] max-w-[62ch]"><?php echo esc_html( $lp_lead ); ?></p>

					<div class="mt-[40px] flex flex-col gap-[9px]">
						<div class="flex items-center justify-between gap-4 flex-wrap">
							<span class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-neutral-content/50"><?php echo esc_html( $lp_search['label'] ); ?></span>
							<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/40"><?php echo esc_html( $lp_search['hint'] ); ?></span>
						</div>
						<form class="flex items-stretch gap-3 flex-wrap" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
							<label class="sr-only" for="not-found-search"><?php echo esc_html( $lp_search['label'] ); ?></label>
							<div class="flex-1 min-w-[220px] flex items-center gap-3 h-[52px] px-4 bg-neutral border border-neutral-content/[.14]">
								<span class="text-neutral-content/40 shrink-0" aria-hidden="true"><?php lp_icon( 'icon-magnifying-glass', 'w-3.5 h-3.5' ); ?></span>
								<input id="not-found-search" name="s" type="search"
									class="w-full bg-transparent border-0 p-0 font-body text-[13px] tracking-[0.2px] text-neutral-content placeholder:text-neutral-content/40 focus:outline-none"
									placeholder="<?php echo esc_attr( $lp_search['placeholder'] ); ?>" />
							</div>
							<button type="submit"
								class="inline-flex items-center gap-3 h-[52px] px-6 bg-primary text-primary-content font-label text-[12px] font-semibold uppercase tracking-[1px] hover:bg-primary/85 transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-primary">
								<span><?php echo esc_html( $lp_search['submit'] ); ?></span>
								<?php lp_icon( 'icon-arrow-right', 'w-3.5 h-3.5' ); ?>
							</button>
						</form>
					</div>

					<div class="mt-[26px] flex items-center gap-[14px] flex-wrap">
						<?php foreach ( $lp_ghost_actions as $lp_action_row ) : ?>
							<a href="<?php echo esc_url( $lp_action_row['href'] ); ?>" class="<?php echo esc_attr( $lp_ghost_btn ); ?>">
								<span><?php echo esc_html( $lp_action_row['label'] ); ?></span>
								<?php lp_icon( 'icon-arrow-right', 'w-3.5 h-3.5' ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<aside class="w-full lg:w-[380px] lg:shrink-0 bg-secondary border border-neutral-content/10 flex flex-col self-start">
					<div class="flex items-center justify-between gap-4 px-[22px] py-[16px] border-b border-neutral-content/20">
						<h2 class="font-label text-[12px] font-semibold uppercase tracking-[1px] text-primary m-0"><?php echo esc_html( $lp_panel['title'] ); ?></h2>
						<?php
						lp_part(
							'elements/status',
							array(
								'variant' => 'live',
								'surface' => 'board',
								'label'   => $lp_panel['live'],
							)
						);
						?>
					</div>
					<?php foreach ( $lp_panel['rows'] as $lp_row ) : ?>
						<div class="flex items-center justify-between gap-5 px-[22px] py-[14px] border-b border-neutral-content/10">
							<span class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-neutral-content/50"><?php echo esc_html( $lp_row['label'] ); ?></span>
							<span class="font-body text-[15px] font-medium tracking-[-0.2px] text-neutral-content text-right"><?php echo esc_html( $lp_row['value'] ); ?></span>
						</div>
					<?php endforeach; ?>
					<a href="<?php echo esc_url( $lp_panel['cta']['href'] ); ?>" class="<?php echo esc_attr( $lp_cta_btn ); ?>">
						<span><?php echo esc_html( $lp_panel['cta']['label'] ); ?></span>
						<?php lp_icon( 'icon-arrow-right', 'w-3.5 h-3.5' ); ?>
					</a>
					<p class="px-[22px] py-[14px] font-body text-[10px] font-normal tracking-[0.3px] leading-[1.6] text-neutral-content/50 m-0"><?php echo esc_html( $lp_panel['foot'] ); ?></p>
				</aside>

			</div>
		</div>
	</div>

	<div class="w-full bg-base-100 border-t border-base-300" data-component="not-found-destinations">
		<div class="px-6 lg:px-16">
			<ul role="list" class="grid grid-cols-1 md:grid-cols-3 m-0 p-0 list-none">
				<?php foreach ( $lp_destinations as $lp_i => $lp_dest ) : ?>
					<li class="<?php echo $lp_i > 0 ? 'md:border-l border-base-300' : ''; ?>">
						<a href="<?php echo esc_url( $lp_dest['href'] ); ?>" class="<?php echo lp_classes( 'flex flex-col gap-0 py-[34px]', $lp_i > 0 ? 'md:pl-11' : '', 'md:pr-11 group' ); ?>">
							<span>
								<?php
								lp_part(
									'elements/glyph-label',
									array(
										'label'   => $lp_dest['kicker'],
										'icon_id' => 'icon-arrow-right',
										'surface' => 'page',
										'tone'    => 'muted',
									)
								);
								?>
							</span>
							<span class="font-heading text-[24px] font-medium tracking-[-0.6px] text-base-content mt-[24px] group-hover:text-accent transition-colors duration-150"><?php echo esc_html( $lp_dest['title'] ); ?></span>
							<span class="font-body text-[11px] font-normal tracking-[0.15px] leading-[1.55] text-base-content/65 mt-[14px]"><?php echo esc_html( $lp_dest['meta'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</main>

<?php
get_footer();
