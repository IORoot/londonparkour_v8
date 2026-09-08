<?php
/**
 * Default SEO titles and descriptions for template pages.
 *
 * These are ACF `seo_title` / `seo_description` values, not on-page copy.
 * Body copy is taken from each page's masthead note (the design file). Titles
 * add the search terms the signed-off H1s deliberately omit.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Slug => [title, description] for public pages.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function lp_seo_page_defaults(): array {
	return array(
		''                   => array(
			'London Parkour | Practical Movement Training & Classes',
			'Practical movement is the practice of getting where you want to go. Taught across three London sites, to every age and every body.',
		),
		'classes'            => array(
			'Parkour Classes in London | Weekly Timetable',
			'Every session on the board for the week ahead. Coach-led, capped at twelve, £15 to drop in. Spaces update live.',
		),
		'classes-map'        => array(
			'Parkour Class Map | London Training Sites',
			'Find your class on the map, then scroll for meeting points and travel details. Every site is a ten-minute walk from a station.',
		),
		'coupons'            => array(
			'Parkour Class Packs in London | No Contract',
			'Buy a class, a pack of five, or ten. Use them at any site — Vauxhall, Old Street or Kilburn Park. No membership. No lock-in.',
		),
		'private-coaching'   => array(
			'Private Parkour Coaching in London | 1:1',
			'Private sessions move at your pace — a first wall, a comeback after injury, or one specific line. From £65 a session.',
		),
		'about'              => array(
			'About London Parkour | Outdoor Classes Since 2018',
			'Andy founded LondonParkour in 2018: high-quality, well-taught, reasonably priced outdoor parkour classes, private 1:1s and workshops across London.',
		),
		'workshops'          => array(
			'Parkour Workshops in London',
			'One-off parkour workshops and teacher training. Open a date for the full details — image, summary, and a clear way in.',
		),
		'contact'            => array(
			'Contact London Parkour',
			'Tell us what you\'re training for and we\'ll point you in the right direction. Email is faster than the phone — coaches are on the floor during sessions.',
		),
		'docs'               => array(
			'Parkour FAQ & Docs | London Parkour',
			'Guides, FAQs and stories from London Parkour. Start with answers to common questions — or switch to Blog for news and projects.',
		),
		'legal'              => array(
			'Terms of Service | London Parkour',
			'The rules that apply when you book and train with us, written in plain English. Ten clauses, no small print.',
		),
		'blog'               => array(
			'Parkour News & Stories | London Parkour',
			'Projects, press and the odd long read from the coaching floor. Newest first — or switch to Support for answers to common questions.',
		),
		'tutorials-series'   => array(
			'Parkour Tutorial Series | London Parkour',
			'Coached video series, filed by movement. Open a line for the full lesson board — image, summary, and a way into the videos.',
		),
		'tutorials-category' => array(
			'Parkour Tutorial Categories | London Parkour',
			'Coached parkour videos filed by category. Filter the board, or open a category for every lesson in that movement.',
		),
	);
}

/**
 * Slug => [title, description] for support (docs) posts whose body starts
 * with a heading, so the auto-clip would otherwise be junk.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function lp_seo_support_defaults(): array {
	return array(
		'terms-of-service'            => array(
			'Terms of Service | London Parkour',
			'The rules that apply when you book and train with us, written in plain English. Ten clauses, no small print.',
		),
		'privacy-policy'              => array(
			'Privacy Policy | London Parkour',
			'How London Parkour collects, uses and stores your information when you book a class, buy a pack, or write to us.',
		),
		'youth-class'                 => array(
			'Youth Parkour Classes in London | Ages 10–14',
			'Outdoor parkour for 10–14s. Jump, climb, vault and land, scaled so first-timers and already-active kids train in the same class.',
		),
		'beginners-class'             => array(
			'Beginners Parkour Classes in London',
			'No fitness, strength or experience required. Outdoor parkour for adults of every ability — vault, jump, climb and balance on real architecture.',
		),
		'pricing'                     => array(
			'Parkour Class Prices | London Parkour',
			'Drop in for £15, or buy a 5-pack or 10-pack. Use them at any London site. No membership and no contract.',
		),
		'personal-training-pt'        => array(
			'Parkour Personal Training in London',
			'One coach, just you. Private outdoor sessions built around a first wall, a stuck line, a comeback, or a child who wants to train.',
		),
		'hiring-us'                   => array(
			'Hire London Parkour | Workshops & Events',
			'Hire London Parkour for workshops, events and film. Tell us the date, the group, and what you need the session to do.',
		),
		'frequently-asked-questions'  => array(
			'Parkour FAQ | London Parkour',
			'Do I need experience? What does a first class cost? What should I bring? Answers to the questions we hear every week.',
		),
		'contacting-us'               => array(
			'Contacting London Parkour',
			'Coaches are on the floor during sessions, so email gets a faster answer than the phone. We reply within 36 hours.',
		),
		'class-locations'             => array(
			'Parkour Class Locations in London',
			'Three outdoor sites — Old Street, Vauxhall and Kilburn Park. Every one is a ten-minute walk from a tube or overground station.',
		),
		'gift-cards'                  => array(
			'Parkour Gift Cards | London Parkour',
			'Give a class, a pack, or a 1:1 session. Gift cards work at any London Parkour site, with no expiry on the booking itself.',
		),
		'code-of-conduct'             => array(
			'Code of Conduct | London Parkour',
			'How we train together: look after the group, look after the site, and leave the architecture as you found it.',
		),
		'equality-policy'             => array(
			'Equality Policy | London Parkour',
			'London Parkour classes are for every age and every body. This is how we keep that true in the booking, on the site, and in the session.',
		),
		'photography-video'           => array(
			'Photography & Video | London Parkour',
			'When we film or photograph a class, what we use it for, and how to opt out.',
		),
		'student-waiver'              => array(
			'Student Waiver | London Parkour',
			'The waiver you agree to when you book. Read it once; ask if anything is unclear.',
		),
	);
}

/**
 * Tutorials CPT archive (`/tutorials/`) — stored on Site Settings.
 *
 * @return array{0: string, 1: string}
 */
function lp_seo_tutorials_archive_defaults(): array {
	$count = function_exists( 'lp_tutorials_published_count' )
		? (int) lp_tutorials_published_count()
		: (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );

	return array(
		'Parkour Tutorials | London Parkour',
		sprintf(
			'%d coached videos, filed by movement. Filter by category, series or tag — or browse the whole board.',
			$count
		),
	);
}

/**
 * Write title + description onto an ACF object (post ID or term string).
 *
 * @param int|string $acf_id ACF post ID, options, or taxonomy_{term_id}.
 */
function lp_seo_write_fields( $acf_id, string $title, string $description ): void {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}

	if ( mb_strlen( $title ) > 70 ) {
		$title = rtrim( mb_substr( $title, 0, 70 ) );
	}
	if ( mb_strlen( $description ) > 200 ) {
		$description = lp_seo_clip( $description, 200 );
	}

	if ( '' !== $title ) {
		update_field( 'seo_title', $title, $acf_id );
	}
	if ( '' !== $description ) {
		update_field( 'seo_description', $description, $acf_id );
	}
}
