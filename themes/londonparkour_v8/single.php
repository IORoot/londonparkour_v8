<?php
/**
 * single.php — BlogDetail. Every native `post`.
 *
 * Ported from src/stories/Pages/BlogDetail/BlogDetail.js. Read that file's
 * docblock in full before touching this one.
 *
 * Section order: breadcrumb → blog-detail-title-block → blog-detail-media →
 * blog-detail-caption → blog-detail-body (TOC + article, incl. the
 * blog-detail-pull-quote) → onward. Nav/footer are get_header()/get_footer(),
 * outside the one <main>. "Where It Happens" / "Closing CTA" / "Gift Rail" /
 * "Pagination" are library-only sections the captured page never composes —
 * per the source's own docblock, not built here either.
 *
 * The body is TWO ACF repeaters on the `post` field group (group_lp_post,
 * app/setup/acf-groups.php) — `body_sections` (before the pull-quote) and
 * `body_sections_after_quote` (after it) — matching BlogDetail.js's own
 * DEFAULT_BODY shape exactly (`sections` / `sectionsAfterQuote`). The sticky
 * "IN THIS ARTICLE" TOC is DERIVED from the two repeaters, in the order they
 * render — never stored a third time. Each section's anchor id is derived
 * from its heading via sanitize_title(), not its own field: unlike Legal's
 * clause numbers, a heading's slug already IS a stable id.
 *
 * `read_time` is shared with home.php's lead-article meta line — both read
 * the same field on the same post. `standfirst`, `author_role`, the
 * caption's `caption_location` / `caption_credit`, `body_intro` and
 * `pull_quote` are the rest of the new fields this page needs — none of them
 * are things WordPress already stores. Title, date, featured image and
 * category are read straight off the native post; the byline's author name
 * is the post's native author (get_the_author()).
 *
 * DIVERGENCE: `meta`'s leading segment is the post's real category
 * (uppercased), not the source's hardcoded literal "NEWS" — the same field
 * home.php's lead article and grid cards already read, so the same article
 * shows the same category in both places instead of two disagreeing labels.
 * Falls back to "NEWS" only when a post has no category.
 *
 * DIVERGENCE: onward prev/next read the chronologically adjacent post
 * (get_previous_post()/get_next_post()) rather than the source's one fixed
 * demo pair ("SkyTaTa…"/"Version 7") — a real multi-post blog needs real
 * neighbours for every article, not one hardcoded pair that only makes sense
 * on a single specific post. Renders the empty side (page-onward.php's own
 * behaviour for a missing item) when there is no neighbour, rather than a
 * label with no working link.
 *
 * The hero photo (1440×540 in the source) is the LCP element. No 1440×540
 * crop family is registered in app/setup/theme.php — reusing `lp_wide_lg`
 * (1920×1080, 16:9) rather than adding a single orphan size; see the port
 * report for why. `scrim` is deliberately omitted (`none`) — the source's
 * plain <img> has no gradient overlay — so `loading`/`fetchpriority` are set
 * explicitly rather than via media-photo's scrim-keyed LCP default.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_post_id = get_the_ID();

	$lp_read_time = function_exists( 'get_field' ) ? (string) get_field( 'read_time', $lp_post_id ) : '';
	$lp_read_time = $lp_read_time ?: '3 MIN READ';

	$lp_cats     = get_the_category();
	$lp_category = $lp_cats ? strtoupper( $lp_cats[0]->name ) : 'NEWS';

	$lp_meta      = sprintf( '%s · %s', $lp_category, $lp_read_time );
	$lp_date_site = strtoupper( get_the_date( 'j M Y' ) ) . ' · LONDONPARKOUR';

	$lp_author = get_the_author();
	$lp_author = $lp_author ?: 'Andy Pearson';

	$lp_author_role = function_exists( 'get_field' ) ? (string) get_field( 'author_role', $lp_post_id ) : '';
	$lp_author_role = $lp_author_role ?: 'HEAD COACH';

	$lp_standfirst = function_exists( 'get_field' ) ? (string) get_field( 'standfirst', $lp_post_id ) : '';
	$lp_standfirst = $lp_standfirst ?: 'A new collaboration bringing weekly parkour classes to stude…';

	$lp_caption_location = function_exists( 'get_field' ) ? (string) get_field( 'caption_location', $lp_post_id ) : '';
	$lp_caption_location = $lp_caption_location ?: 'IMPERIAL COLLEGE LONDON · SOUTH KENSINGTON';

	$lp_caption_credit = function_exists( 'get_field' ) ? (string) get_field( 'caption_credit', $lp_post_id ) : '';
	$lp_caption_credit = $lp_caption_credit ?: 'PHOTO: LONDONPARKOUR';

	$lp_body_intro = function_exists( 'get_field' ) ? (string) get_field( 'body_intro', $lp_post_id ) : '';
	$lp_body_intro = $lp_body_intro ?: 'LondonParkour is excited to announce its new collaboration w…';

	// `B8LI3`'s DEFAULT_BODY, transcribed verbatim — used only when the post
	// has no body_sections/body_sections_after_quote/pull_quote of its own.
	$lp_default_sections       = array(
		array(
			'heading' => 'The Collaboration',
			'body'    => 'Universities are the perfect place to discover parkour: a co…',
		),
		array(
			'heading' => 'Meet the Coach',
			'body'    => 'The sessions are led by Mesh, one of our most experienced co…',
		),
	);
	$lp_default_sections_after = array(
		array(
			'heading' => 'Sessions & Schedule',
			'body'    => 'Classes run every Wednesday during term time and are open to…',
		),
		array(
			'heading' => 'Get Involved',
			'body'    => "If you're an Imperial student, keep an eye on the student un…",
		),
	);
	$lp_default_pull_quote     = array(
		'quote'       => 'Parkour teaches you to see opportunity where others see obst…',
		'attribution' => 'MESH · COACH',
	);

	$lp_project_section = static function ( array $lp_row ): array {
		return array(
			'heading' => (string) ( $lp_row['heading'] ?? '' ),
			'body'    => (string) ( $lp_row['body'] ?? '' ),
		);
	};

	$lp_sections_field = function_exists( 'get_field' ) ? get_field( 'body_sections', $lp_post_id ) : null;
	$lp_sections        = ( is_array( $lp_sections_field ) && $lp_sections_field )
		? array_map( $lp_project_section, $lp_sections_field )
		: $lp_default_sections;

	$lp_sections_after_field = function_exists( 'get_field' ) ? get_field( 'body_sections_after_quote', $lp_post_id ) : null;
	$lp_sections_after        = ( is_array( $lp_sections_after_field ) && $lp_sections_after_field )
		? array_map( $lp_project_section, $lp_sections_after_field )
		: $lp_default_sections_after;

	$lp_pull_quote_field = function_exists( 'get_field' ) ? get_field( 'pull_quote', $lp_post_id ) : null;
	$lp_pull_quote        = ( is_array( $lp_pull_quote_field ) && ! empty( $lp_pull_quote_field['quote'] ) )
		? array(
			'quote'       => (string) $lp_pull_quote_field['quote'],
			'attribution' => (string) ( $lp_pull_quote_field['attribution'] ?? '' ),
		)
		: $lp_default_pull_quote;

	// Anchor ids are derived from each heading, not a separate field.
	foreach ( $lp_sections as &$lp_ref_section ) {
		$lp_ref_section['id'] = sanitize_title( $lp_ref_section['heading'] );
	}
	unset( $lp_ref_section );
	foreach ( $lp_sections_after as &$lp_ref_section ) {
		$lp_ref_section['id'] = sanitize_title( $lp_ref_section['heading'] );
	}
	unset( $lp_ref_section );

	$lp_toc = array();
	foreach ( array_merge( $lp_sections, $lp_sections_after ) as $lp_toc_row ) {
		$lp_toc[] = array(
			'id'    => $lp_toc_row['id'],
			'title' => $lp_toc_row['heading'],
		);
	}

	$lp_blog_page_id = (int) get_option( 'page_for_posts' );
	$lp_blog_crumb    = array( 'label' => 'BLOG' );
	if ( $lp_blog_page_id ) {
		$lp_blog_crumb['href'] = get_permalink( $lp_blog_page_id );
	}

	$lp_crumbs        = array(
		array(
			'label' => 'HOME',
			'href'  => home_url( '/' ),
		),
		array( 'label' => 'DOCS' ), // No /docs page yet — see home.php's docblock.
		$lp_blog_crumb,
		array( 'label' => strtoupper( get_the_title() ) ),
	);
	$lp_crumb_action  = array( 'label' => 'ALL WRITING ↗' ); // Same — no href.

	$lp_prev_post   = get_previous_post();
	$lp_onward_prev = array( 'keyword' => '← PREVIOUS' );
	if ( $lp_prev_post ) {
		$lp_onward_prev['label'] = get_the_title( $lp_prev_post );
		$lp_onward_prev['href']  = get_permalink( $lp_prev_post );
	}

	$lp_next_post   = get_next_post();
	$lp_onward_next = array( 'keyword' => 'NEXT →' );
	if ( $lp_next_post ) {
		$lp_onward_next['label'] = get_the_title( $lp_next_post );
		$lp_onward_next['href']  = get_permalink( $lp_next_post );
	}
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
		?>

		<div class="w-full bg-base-100" data-component="blog-detail-title-block">
			<div class="px-6 lg:px-16 pt-scale-xl pb-scale-l flex flex-col gap-[24px]">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'    => $lp_meta,
						'right'   => $lp_date_site,
						'surface' => 'page',
					)
				);
				lp_part( 'elements/rule', array( 'tone' => 'hairline' ) );
				?>
				<h1 class="font-display font-bold text-[76px] leading-[0.92] tracking-[-3.2px] text-base-content"><?php echo esc_html( get_the_title() ); ?></h1>
				<p class="max-w-[640px] font-body text-[18px] leading-[1.6] tracking-[0.1px] text-base-content/65"><?php echo esc_html( $lp_standfirst ); ?></p>
				<?php
				lp_part(
					'components/byline',
					array(
						'name'      => $lp_author,
						'secondary' => $lp_author_role,
						'size'      => 'md',
						'surface'   => 'page',
					)
				);
				?>
			</div>
		</div>

		<figure class="w-full h-[540px] bg-base-300 m-0 overflow-hidden" data-component="blog-detail-media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php
				lp_part(
					'components/media-photo',
					array(
						'image_id'      => get_post_thumbnail_id(),
						'element'       => 'img',
						'layout'        => 'none',
						'class'         => 'w-full h-full object-cover',
						'size'          => 'lp_wide_lg',
						'sizes'         => '100vw',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
					)
				);
				?>
			<?php endif; ?>
		</figure>

		<div class="w-full bg-neutral" data-component="blog-detail-caption">
			<div class="px-6 lg:px-16 py-3.5">
				<?php
				lp_part(
					'components/meta-row',
					array(
						'left'    => $lp_caption_location,
						'right'   => $lp_caption_credit,
						'surface' => 'board',
					)
				);
				?>
			</div>
		</div>

		<div class="w-full bg-base-200" data-component="blog-detail-body">
			<div class="px-6 lg:px-16 py-scale-2xl flex flex-col lg:flex-row gap-[56px]">
				<aside class="w-full lg:w-[260px] shrink-0 flex flex-col gap-[18px] lg:sticky lg:top-[24px] lg:self-start">
					<p class="font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65 m-0">In this article</p>
					<nav aria-label="In this article">
						<?php foreach ( $lp_toc as $lp_i => $lp_entry ) : ?>
							<?php
							lp_part(
								'components/list-row',
								array(
									'index'   => str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT ),
									'title'   => $lp_entry['title'],
									'meta'    => '',
									'href'    => '#' . $lp_entry['id'],
									'surface' => 'page',
								)
							);
							?>
						<?php endforeach; ?>
					</nav>
					<a href="#" class="font-label text-[11px] font-semibold uppercase tracking-[0.9px] text-accent hover:text-accent/70 transition-colors duration-150">SHARE THIS ↗</a>
				</aside>
				<article class="w-full max-w-[680px] flex flex-col gap-[28px]">
					<p class="font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content"><?php echo esc_html( $lp_body_intro ); ?></p>
					<?php foreach ( $lp_sections as $lp_section ) : ?>
						<h3 id="<?php echo esc_attr( $lp_section['id'] ); ?>" class="font-heading text-[27px] font-semibold tracking-[-0.5px] text-base-content scroll-mt-[24px]"><?php echo esc_html( $lp_section['heading'] ); ?></h3>
						<p class="font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content"><?php echo esc_html( $lp_section['body'] ); ?></p>
					<?php endforeach; ?>
					<blockquote class="border-l-2 border-accent pl-[24px] flex flex-col gap-[12px]" data-component="blog-detail-pull-quote">
						<p class="font-heading text-[31px] font-bold leading-[1.2] tracking-[-0.6px] text-base-content m-0"><?php echo esc_html( $lp_pull_quote['quote'] ); ?></p>
						<cite class="not-italic font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp_pull_quote['attribution'] ); ?></cite>
					</blockquote>
					<?php foreach ( $lp_sections_after as $lp_section ) : ?>
						<h3 id="<?php echo esc_attr( $lp_section['id'] ); ?>" class="font-heading text-[27px] font-semibold tracking-[-0.5px] text-base-content scroll-mt-[24px]"><?php echo esc_html( $lp_section['heading'] ); ?></h3>
						<p class="font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content"><?php echo esc_html( $lp_section['body'] ); ?></p>
					<?php endforeach; ?>
				</article>
			</div>
		</div>

		<?php
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
endwhile;

get_footer();
