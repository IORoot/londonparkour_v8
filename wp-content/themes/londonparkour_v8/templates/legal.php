<?php
/**
 * Template Name: Legal
 *
 * Legal — Terms of service and the student waiver. Breadcrumb rail → page
 * masthead → doc meta → body (index rail + clauses) → onward.
 *
 * Ported from src/stories/Pages/Legal/Legal.js. Read that file's docblock in
 * full before touching this one — it records binding decisions (the pager's
 * hrefs are deliberately omitted until an editor sets them).
 *
 * Clauses, doc facts and doc actions are the `clauses`, `doc_facts` and
 * `doc_actions` ACF repeaters (group_lp_legal in app/setup/acf-groups.php) —
 * the index rail is keyed to clause numbers and jumps to per-clause ids, so
 * they have to be structured data, not the_content(). Copy defaults below
 * (Terms) and in app/includes/waiver-copy.php (waiver) are used only when an
 * editor has not populated the fields — never duplicated into ACF
 * `default_value`s.
 *
 * Doc meta and the body (index rail + clauses) are Legal-only shapes that
 * occur once, so per Port Brief rule 3a they stay inline here rather than
 * being promoted to parts/.
 *
 * Landmark contract: nav and footer outside the one <main>, the H1 inside it
 * (in page-masthead.php).
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

$lp_support   = is_singular( 'support' ) ? get_post() : null;
$lp_is_waiver = $lp_support instanceof WP_Post && function_exists( 'lp_docs_is_waiver' ) && lp_docs_is_waiver( $lp_support );

$lp_title = 'Terms of service.';
$lp_note  = 'The rules that apply when you book and train with us, written in plain English. Ten clauses, no small print — if anything here is unclear, ask us and we will explain it.';
$lp_clause_prefix = 'terms-clause-';
$lp_index_title   = $lp_support instanceof WP_Post ? get_the_title( $lp_support ) : 'Terms of service';

$lp_crumbs       = array(
	array(
		'label' => 'HOME',
		'href'  => home_url( '/' ),
	),
	array(
		'label' => 'DOCS',
		'href'  => lp_docs_url(),
	),
	array( 'label' => 'TERMS OF SERVICE' ),
);
$lp_crumb_action = array(
	'label' => 'ALL DOCS ↗',
	'href'  => lp_docs_url(),
);

// `b6u0Ph` Doc Meta defaults.
$lp_default_doc_facts   = array(
	array(
		'label' => 'EFFECTIVE',
		'value' => '1 April 2026',
	),
	array(
		'label' => 'VERSION',
		'value' => '3.1',
	),
	array(
		'label' => 'LAST REVIEWED',
		'value' => '12 January 2026',
	),
);
$lp_default_doc_actions = array(
	array( 'label' => 'DOWNLOAD PDF ↓' ),
	array( 'label' => 'PRINT THIS PAGE' ),
);

// `wIud8` Body — the ten clauses, each a heading plus exactly two paragraphs.
$lp_default_clauses = array(
	array(
		'n'     => '01',
		'title' => 'Who we are',
		'paras' => array(
			'London Parkour Ltd is a company registered in England and Wales (number 09482231), with a registered office at 24 Britannia Walk, London N1 7LU. Where these terms say “we”, “us” or “our”, they mean that company.',
			'In these terms, “you” means the person booking a session, and where the participant is under 18, the parent or guardian who books on their behalf.',
		),
	),
	array(
		'n'     => '02',
		'title' => 'Booking and payment',
		'paras' => array(
			'A booking is confirmed when payment clears and you receive a confirmation email. Drop-in sessions are paid in full at the point of booking; memberships are collected by direct debit on the same date each month.',
			'Prices shown at the time of booking are the prices you pay. We hold advertised prices until the date stated on our pricing page.',
		),
	),
	array(
		'n'     => '03',
		'title' => 'Cancellations and refunds',
		'paras' => array(
			'You can cancel a booked session free of charge up to twelve hours before it starts, and the credit returns to your account immediately. Inside twelve hours we cannot release the space to anyone else, so the session is charged in full.',
			'If your first session with us is not right for you, tell the coach on the day and we will refund it in full. This applies once, to your first session only.',
		),
	),
	array(
		'n'     => '04',
		'title' => 'Memberships',
		'paras' => array(
			'Monthly membership runs on a rolling basis with no minimum term. Cancel from your account at any time and the membership runs to the end of the paid month.',
			'Annual membership is paid up front and covers twelve months from the start date. It can be paused once for up to two months for injury or travel.',
		),
	),
	array(
		'n'     => '05',
		'title' => 'Taking part, and risk',
		'paras' => array(
			'Parkour is a physical activity carried out on real terrain, and it carries a risk of injury that cannot be removed entirely. Our coaches scale every session, but by taking part you accept that risk.',
			'You must tell your coach about any injury, medical condition or medication that could affect your training, before the session starts.',
		),
	),
	array(
		'n'     => '06',
		'title' => 'Participants under 18',
		'paras' => array(
			'Anyone under 18 needs consent from a parent or guardian, given at the point of booking. Under-14s must be collected from the meeting point by a named adult.',
			'Parents are welcome to watch any junior session. We ask that coaching is left to the coach.',
		),
	),
	array(
		'n'     => '07',
		'title' => 'Conduct at our sites',
		'paras' => array(
			'We train in public and shared spaces. Treat the site, the people around you and the coach with respect, and follow coaching instructions on safety without argument.',
			'We may end a session for anyone who puts themselves or others at risk, or who trains under the influence of alcohol or drugs. No refund is due in that case.',
		),
	),
	array(
		'n'     => '08',
		'title' => 'Sites and weather',
		'paras' => array(
			'Most of our sessions run outdoors and go ahead in rain. We cancel only for conditions that make surfaces genuinely unsafe — ice, high wind or lightning.',
			'If we cancel, you are credited in full and told by text and email as early as we can manage.',
		),
	),
	array(
		'n'     => '09',
		'title' => 'Our liability',
		'paras' => array(
			'We are liable for injury or loss caused by our negligence, and nothing in these terms limits liability for death or personal injury caused by that negligence, or for fraud.',
			'We are not liable for personal property brought to a session, or for loss that was not reasonably foreseeable when you booked.',
		),
	),
	array(
		'n'     => '10',
		'title' => 'Changes to these terms',
		'paras' => array(
			"We may update these terms. If a change materially affects your rights, we will give at least 30 days' notice by email before it takes effect.",
			'The version and effective date at the top of this page always tell you which terms apply.',
		),
	),
);

if ( $lp_is_waiver ) {
	$lp_title         = lp_waiver_masthead_title();
	$lp_note          = lp_waiver_masthead_note();
	$lp_crumbs[2]     = array( 'label' => 'STUDENT WAIVER' );
	$lp_clause_prefix = 'waiver-clause-';
	$lp_index_title   = $lp_support instanceof WP_Post ? get_the_title( $lp_support ) : 'Student Waiver';
	$lp_default_doc_facts   = lp_waiver_default_doc_facts();
	$lp_default_doc_actions = lp_waiver_default_doc_actions();
	$lp_default_clauses     = lp_waiver_default_clauses();
}

/* -- Resolve ACF, falling back to the source's own defaults. -------------- */

$lp_doc_facts_field = function_exists( 'get_field' ) ? get_field( 'doc_facts' ) : null;
$lp_doc_facts        = ( is_array( $lp_doc_facts_field ) && $lp_doc_facts_field )
	? array_map(
		static function ( array $lp_row ): array {
			return array(
				'label' => (string) ( $lp_row['label'] ?? '' ),
				'value' => (string) ( $lp_row['value'] ?? '' ),
			);
		},
		$lp_doc_facts_field
	)
	: $lp_default_doc_facts;

$lp_doc_actions_field = function_exists( 'get_field' ) ? get_field( 'doc_actions' ) : null;
$lp_doc_actions        = ( is_array( $lp_doc_actions_field ) && $lp_doc_actions_field )
	? array_map(
		static function ( array $lp_row ): array {
			return array( 'label' => (string) ( $lp_row['label'] ?? '' ) );
		},
		$lp_doc_actions_field
	)
	: $lp_default_doc_actions;

$lp_clauses_field = function_exists( 'get_field' ) ? get_field( 'clauses' ) : null;
$lp_clauses        = ( is_array( $lp_clauses_field ) && $lp_clauses_field )
	? array_map(
		static function ( array $lp_row ): array {
			$lp_paras = array();
			foreach ( (array) ( $lp_row['paragraphs'] ?? array() ) as $lp_para_row ) {
				$lp_paras[] = is_array( $lp_para_row ) ? (string) ( $lp_para_row['text'] ?? '' ) : '';
			}
			return array(
				'n'     => (string) ( $lp_row['number'] ?? '' ),
				'title' => (string) ( $lp_row['title'] ?? '' ),
				'paras' => $lp_paras,
			);
		},
		$lp_clauses_field
	)
	: $lp_default_clauses;

// `QnMmH` — the source's own pager points at a Privacy and a Cookie policy
// that exist neither in the .pen file nor this repo. Labels are ported;
// hrefs are left unset so page-onward.php's own '#' fallback applies, never
// an invented URL.
$lp_prev_link = lp_action( function_exists( 'get_field' ) ? get_field( 'prev' ) : null );
$lp_next_link = lp_action( function_exists( 'get_field' ) ? get_field( 'next' ) : null );

$lp_prev = array(
	'keyword' => '← PREVIOUS',
	'label'   => $lp_prev_link['label'] ?? 'Privacy policy',
);
if ( ! empty( $lp_prev_link['href'] ) ) {
	$lp_prev['href'] = $lp_prev_link['href'];
}

$lp_next = array(
	'keyword' => 'NEXT →',
	'label'   => $lp_next_link['label'] ?? 'Cookie policy',
);
if ( ! empty( $lp_next_link['href'] ) ) {
	$lp_next['href'] = $lp_next_link['href'];
}

if ( $lp_is_waiver ) {
	if ( ! is_array( $lp_prev_link ) ) {
		$lp_terms_post = lp_docs_find_support( array( 'terms', 'terms-of-service' ) );
		$lp_prev       = array(
			'keyword' => '← PREVIOUS',
			'label'   => 'Terms of service',
		);
		if ( $lp_terms_post ) {
			$lp_prev['href'] = (string) get_permalink( $lp_terms_post );
		}
	}
	if ( ! is_array( $lp_next_link ) ) {
		$lp_privacy_post = lp_docs_find_support( array( 'privacy', 'privacy-policy' ) );
		$lp_next         = array(
			'keyword' => 'NEXT →',
			'label'   => 'Privacy policy',
		);
		if ( $lp_privacy_post ) {
			$lp_next['href'] = (string) get_permalink( $lp_privacy_post );
		}
	}
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
			'title' => $lp_title,
			'note'  => $lp_note,
		)
	);

	lp_docs_render_wiki_nav(
		'wiki',
		$lp_index_title
	);
	?>

	<div class="w-full bg-base-100" data-component="legal-doc-meta">
		<div class="px-6 lg:px-16">
			<div class="flex items-center justify-between gap-6 flex-wrap py-[21px] border-b border-base-content">
				<div class="flex items-center gap-x-10 gap-y-3 flex-wrap">
					<?php
					foreach ( $lp_doc_facts as $lp_fact ) {
						lp_part(
							'components/fact-row',
							array(
								'label'   => $lp_fact['label'],
								'value'   => $lp_fact['value'],
								'surface' => 'page',
							)
						);
					}
					?>
				</div>
				<div class="flex items-center gap-6 flex-wrap">
					<?php
					foreach ( $lp_doc_actions as $lp_action_row ) {
						lp_part(
							'elements/text-link',
							array(
								'label'   => $lp_action_row['label'],
								'href'    => '#',
								'variant' => 'board_compact_accent',
							)
						);
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="w-full bg-base-200" data-component="legal-body">
		<div class="px-6 lg:px-16 py-scale-xl flex flex-col lg:flex-row gap-10 lg:gap-[104px]">
			<nav aria-label="On this page" class="lg:w-[280px] lg:shrink-0">
				<p class="font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-base-content/65 m-0 mb-4">ON THIS PAGE</p>
				<ul role="list" class="flex flex-col m-0 p-0 list-none">
					<?php foreach ( $lp_clauses as $lp_i => $lp_clause ) : ?>
						<li>
							<a href="#<?php echo esc_attr( $lp_clause_prefix . $lp_clause['n'] ); ?>" class="flex items-center gap-[14px] py-[13px] group">
								<span class="font-label text-[10px] font-normal tracking-[0.6px] text-base-content/65 w-[22px] shrink-0"><?php echo esc_html( $lp_clause['n'] ); ?></span>
								<span class="<?php echo lp_classes( 'font-label text-[11px] font-normal tracking-[0.2px]', 0 === $lp_i ? 'text-base-content' : 'text-base-content/65', 'group-hover:text-base-content transition-colors duration-150' ); ?>"><?php echo esc_html( $lp_clause['title'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
			<div class="flex-1 min-w-0 flex flex-col gap-[46px]">
				<?php foreach ( $lp_clauses as $lp_clause ) : ?>
					<section id="<?php echo esc_attr( $lp_clause_prefix . $lp_clause['n'] ); ?>" class="flex flex-col gap-4">
						<div class="flex items-center gap-4">
							<span class="font-label text-[11px] font-semibold tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_clause['n'] ); ?></span>
							<h2 class="font-heading text-[23px] font-medium tracking-[-0.5px] text-base-content m-0"><?php echo esc_html( $lp_clause['title'] ); ?></h2>
						</div>
						<?php foreach ( $lp_clause['paras'] as $lp_para ) : ?>
							<p class="font-body text-[13.5px] font-normal tracking-[0.1px] leading-[1.75] text-base-content/75 m-0"><?php echo esc_html( $lp_para ); ?></p>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>
			</div>
		</div>
	</div>

	<?php
	lp_part(
		'components/page-onward',
		array(
			'prev' => $lp_prev,
			'next' => $lp_next,
		)
	);
	?>
</main>

<?php
get_footer();
