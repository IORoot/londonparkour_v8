<?php
/**
 * home.php — BlogIndex. The posts-page template (routes at "/blog/").
 *
 * Ported from src/stories/Pages/BlogIndex/BlogIndex.js. Read that file's
 * docblock in full before touching this one — it records the settled Lead
 * Article ground divergence (bg-accent, with the accent-content family;
 * never bg-neutral, never bg-primary; copy floor accent-content/70) and the
 * Recent grid's real per-card copy.
 *
 * Section order: breadcrumb → masthead → Wiki/Blog/FAQ switcher →
 * lead (page 1) → recent grid → pagination → onward. Nav/footer are
 * get_header()/get_footer(), outside the one <main>.
 *
 * The listing is the posts-page main query (lp_filter_blog_home): `blog`
 * CPT, 24 per page. Page 1 puts the newest in the lead band and the
 * remaining 23 in the grid; later pages are grid-only. Native `post` is
 * only the fallback when that CPT is not registered. DEFAULT_LEAD /
 * DEFAULT_CARDS are transcribed verbatim from the source and are used
 * ONLY when there are no published articles at all. Category comes from
 * `blog-category`; `read_time` is ACF or a word-count estimate. See
 * single.php for the article template.
 *
 * The lead's hairline divider (`x4FPMU` in the source) is composed via
 * elements/rule.php's `accent` tone rather than hand-rolled inline: the
 * source's own docblock flags "Elements/Rule has no accent tone" as a gap,
 * but that gap has since been closed here (rule.php now ships one) — the
 * same resolution BlogDetail's docblock records for MetaRow's `board`
 * surface. The resulting DOM is not byte-identical to the source's single
 * `<span role="separator" aria-hidden="true">` — rule.php wraps a decorative
 * inner line in its own separator div — but it renders the same 1px
 * accent-content/15 line and is the shared part, not a retype.
 *
 * DOCS crumb and ALL DOCS ↗ point at `/docs`.
 *
 * Onward: 'prev' ("FAQ — Passenger enquiries") points at the Docs FAQ
 * passenger-enquiries block. 'next' points at the first Recent-grid article.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_crumbs        = array(
	array(
		'label' => 'HOME',
		'href'  => home_url( '/' ),
	),
	array(
		'label' => 'DOCS',
		'href'  => lp_docs_url(),
	),
	array( 'label' => 'NEWS & STORIES' ),
);
$lp_crumb_action = array(
	'label' => 'ALL DOCS ↗',
	'href'  => lp_docs_url(),
);

$lp_masthead_title = "Everything we've written.";
$lp_masthead_note  = 'Projects, press and the odd long read from the coaching floor. Newest first — or switch to Support for answers to common questions.';

$lp_live_label = 'NOW ON THE BOARD — FEATURED';

// `pVBvW`/`nn0Nr` defaults, transcribed verbatim — used only when the site
// has no published posts at all.
$lp_default_lead  = array(
	'category'  => 'PROJECT',
	'read_time' => '3 MIN READ',
	'title'     => 'Imperial College London',
	'excerpt'   => 'LondonParkour is teaming up with Imperial College London to bring parkour classes to students every Wednesday, led by experienced coach Mesh.',
	'author'    => 'Andy Pearson',
	'date'      => 'Nov 19, 2024',
	'date_meta' => '19 NOV 2024',
	'href'      => '#',
);
$lp_default_cards = array(
	array(
		'read_time' => '4 MIN READ',
		'category'  => 'UPDATE',
		'title'     => 'Version 7',
		'excerpt'   => 'A complete overhaul — fresh design, a new booking system, a hotspot map, 800+ tutorials, challenges and this blog.',
		'author'    => 'Andy Pearson',
		'date'      => 'Nov 9, 2024',
		'href'      => '#',
	),
	array(
		'read_time' => '3 MIN READ',
		'category'  => 'PROJECT',
		'title'     => 'SkyTaTa Prison Break Advert',
		'excerpt'   => "The record-breaking Tata Sky+ HD ad, shot in a real Hungarian prison, with parkour stunts by Andy and Yamakasi's Charles Perrière.",
		'author'    => 'Andy Pearson',
		'date'      => 'Dec 7, 2023',
		'href'      => '#',
	),
	array(
		'read_time' => '4 MIN READ',
		'category'  => 'PROJECT',
		'title'     => 'The Guardian – Fit in my 40s',
		'excerpt'   => "Guardian reporter Zoe Williams tried parkour under Andy's guidance — proving it's for anyone willing to push their boundaries.",
		'author'    => 'Andy Pearson',
		'date'      => 'Dec 7, 2023',
		'href'      => '#',
	),
);

$lp_recent_eyebrow = 'RECENT FILINGS';
$lp_recent_heading = 'Three off the top.';
$lp_recent_note    = 'Projects, press and the occasional note from the coaching floor. The full archive is on the board below.';

/*
 * The WP_Post → blog-card projection moved to lp_post_card_args() in
 * app/includes/content.php when archive-list.php became its second caller.
 * Behaviour is unchanged, including both source-default fallbacks.
 */

$lp_query_posts = $GLOBALS['wp_query']->posts;
$lp_paged       = max( 1, (int) get_query_var( 'paged' ) );

$lp_lead  = null;
$lp_cards = array();

if ( ! $lp_query_posts ) {
	$lp_lead  = $lp_default_lead;
	$lp_cards = $lp_default_cards;
} else {
	if ( 1 === $lp_paged ) {
		$lp_lead       = lp_post_card_args( $lp_query_posts[0] );
		$lp_grid_posts = array_slice( $lp_query_posts, 1 );
	} else {
		$lp_grid_posts = $lp_query_posts;
	}

	foreach ( $lp_grid_posts as $lp_p ) {
		$lp_cards[] = lp_post_card_args( $lp_p );
	}
}

$lp_lead_meta = $lp_lead
	? sprintf( '%s · %s · %s', $lp_lead['category'], $lp_lead['read_time'], $lp_lead['date_meta'] ?? '' )
	: '';

$lp_onward_prev = array(
	'keyword' => '← FAQ',
	'label'   => 'Passenger enquiries',
	'href'    => lp_docs_url(),
);
$lp_onward_next = array(
	'keyword' => 'LATEST ARTICLE →',
	'label'   => 'Version 7 — the overhaul',
);
if ( ! empty( $lp_cards[0]['href'] ) && ! empty( $lp_cards[0]['title'] ) ) {
	$lp_onward_next['label'] = $lp_cards[0]['title'];
	$lp_onward_next['href']  = $lp_cards[0]['href'];
}

get_header();
?>

<main id="main">
	<?php
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => $lp_crumbs,
			'action' => $lp_crumb_action,
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => $lp_masthead_title,
			'note'  => $lp_masthead_note,
		)
	);

	lp_render_block(
		'section-directory',
		array(
			'rows' => lp_docs_switcher_rows( 'blog' ),
		)
	);
	?>

	<?php if ( $lp_lead ) : ?>
		<div class="w-full bg-accent" data-component="blog-index-lead">
			<div class="px-6 lg:px-16 py-scale-xl flex flex-col gap-[22px]">
				<div class="flex flex-wrap items-center gap-4">
					<?php
					lp_part(
						'elements/status',
						array(
							'variant' => 'live',
							'label'   => $lp_live_label,
							'surface' => 'accent',
						)
					);
					?>
				</div>
				<p class="font-label text-[10px] font-normal uppercase tracking-[0.9px] text-accent-content/70"><?php echo esc_html( $lp_lead_meta ); ?></p>
				<?php lp_part( 'elements/rule', array( 'tone' => 'accent' ) ); ?>
				<?php
				lp_part(
					'components/blog-card',
					array(
						'variant'  => 'lead',
						'image_id' => $lp_lead['image_id'] ?? 0,
						'category' => $lp_lead['category'],
						'title'    => $lp_lead['title'],
						'excerpt'  => $lp_lead['excerpt'],
						'author'   => $lp_lead['author'],
						'date'     => $lp_lead['date'],
						'href'     => $lp_lead['href'],
					)
				);
				?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $lp_cards ) : ?>
	<div class="w-full bg-base-100" data-component="blog-index-recent">
		<div class="px-6 lg:px-16 py-scale-2xl flex flex-col gap-[36px]">
			<?php
			lp_part(
				'components/section-head',
				array(
					'eyebrow' => $lp_recent_eyebrow,
					'heading' => $lp_recent_heading,
					'note'    => $lp_recent_note,
					'surface' => 'page',
				)
			);
			?>
			<div class="grid grid-cols-1 md:grid-cols-3 gap-[24px]">
				<?php foreach ( $lp_cards as $lp_card ) : ?>
					<?php
					lp_part(
						'components/blog-card',
						array(
							'variant'   => 'grid',
							'image_id'  => $lp_card['image_id'] ?? 0,
							'category'  => $lp_card['category'],
							'read_time' => $lp_card['read_time'],
							'title'     => $lp_card['title'],
							'excerpt'   => $lp_card['excerpt'],
							'author'    => $lp_card['author'],
							'date'      => $lp_card['date'],
							'href'      => $lp_card['href'],
						)
					);
					?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php
	$lp_pagination = lp_pagination_args( null, 'STORIES' );
	if ( $lp_pagination ) {
		$lp_pagination['aria_label'] = 'Blog pages';
		lp_part( 'components/pagination', $lp_pagination );
	}

	lp_part(
		'components/page-onward',
		array(
			'prev' => $lp_onward_prev,
			'next' => $lp_onward_next,
		)
	);
	?>
</main>

<?php
get_footer();
