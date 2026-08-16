<?php
/**
 * single.php — BlogDetail. Native `post` and (via single-blog.php) the `blog` CPT.
 *
 * Ported from src/stories/Pages/BlogDetail/BlogDetail.js. Read that file's
 * docblock in full before touching this one.
 *
 * Section order: breadcrumb → blog-detail-title-block → blog-detail-media →
 * blog-detail-caption → blog-detail-body (TOC + article, incl. an optional
 * blog-detail-pull-quote) → onward. Nav/footer are get_header()/get_footer(),
 * outside the one <main>. "Where It Happens" / "Closing CTA" / "Gift Rail" /
 * "Pagination" are library-only sections the captured page never composes —
 * per the source's own docblock, not built here either.
 *
 * Imported v7 `blog` posts store markdown in `post_content` and have empty
 * ACF repeaters. Those render through lp_blog_parse_markdown(). Structured
 * ACF (`body_sections` / `pull_quote` / `body_sections_after_quote`) wins
 * when present, matching BlogDetail.js's DEFAULT_BODY shape. The TOC is
 * derived from whichever path ran — never stored a third time.
 *
 * Do not fall back to Imperial College copy on an unrelated article.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$lp_post_id = get_the_ID();
	$lp_post    = get_post( $lp_post_id );

	$lp_read_time = $lp_post ? lp_post_read_time( $lp_post ) : '3 MIN READ';
	$lp_category  = $lp_post ? lp_post_category_label( $lp_post ) : 'PROJECT';

	$lp_meta      = sprintf( '%s · %s', $lp_category, $lp_read_time );
	$lp_date_site = strtoupper( get_the_date( 'j M Y' ) ) . ' · LONDONPARKOUR';

	$lp_author = get_the_author();
	$lp_author = $lp_author ?: 'Andy Pearson';

	$lp_author_role = function_exists( 'get_field' ) ? (string) get_field( 'author_role', $lp_post_id ) : '';
	$lp_author_role = $lp_author_role ?: 'HEAD COACH';

	$lp_standfirst = function_exists( 'get_field' ) ? (string) get_field( 'standfirst', $lp_post_id ) : '';
	if ( '' === $lp_standfirst ) {
		$lp_standfirst = wp_strip_all_tags( get_the_excerpt() );
	}

	$lp_caption_location = function_exists( 'get_field' ) ? (string) get_field( 'caption_location', $lp_post_id ) : '';
	if ( '' === $lp_caption_location ) {
		$lp_caption_location = strtoupper( get_the_title() );
	}

	$lp_caption_credit = function_exists( 'get_field' ) ? (string) get_field( 'caption_credit', $lp_post_id ) : '';
	$lp_caption_credit = $lp_caption_credit ?: 'PHOTO: LONDONPARKOUR';

	$lp_intro_paras     = array();
	$lp_sections        = array();
	$lp_sections_after  = array();
	$lp_pull_quote      = null;

	$lp_project_section = static function ( array $lp_row ): array {
		$lp_heading = (string) ( $lp_row['heading'] ?? '' );
		$lp_body    = (string) ( $lp_row['body'] ?? '' );
		return array(
			'id'         => sanitize_title( $lp_heading ),
			'heading'    => $lp_heading,
			'paragraphs' => '' === $lp_body ? array() : array( $lp_body ),
		);
	};

	$lp_sections_field = function_exists( 'get_field' ) ? get_field( 'body_sections', $lp_post_id ) : null;
	$lp_has_acf_body   = is_array( $lp_sections_field ) && $lp_sections_field;

	if ( $lp_has_acf_body ) {
		$lp_body_intro = function_exists( 'get_field' ) ? (string) get_field( 'body_intro', $lp_post_id ) : '';
		if ( '' !== $lp_body_intro ) {
			$lp_intro_paras[] = $lp_body_intro;
		}
		$lp_sections = array_map( $lp_project_section, $lp_sections_field );

		$lp_sections_after_field = function_exists( 'get_field' ) ? get_field( 'body_sections_after_quote', $lp_post_id ) : null;
		$lp_sections_after        = ( is_array( $lp_sections_after_field ) && $lp_sections_after_field )
			? array_map( $lp_project_section, $lp_sections_after_field )
			: array();

		$lp_pull_quote_field = function_exists( 'get_field' ) ? get_field( 'pull_quote', $lp_post_id ) : null;
		if ( is_array( $lp_pull_quote_field ) && ! empty( $lp_pull_quote_field['quote'] ) ) {
			$lp_pull_quote = array(
				'quote'       => (string) $lp_pull_quote_field['quote'],
				'attribution' => (string) ( $lp_pull_quote_field['attribution'] ?? '' ),
			);
		}
	} elseif ( $lp_post ) {
		$lp_parsed      = lp_blog_parse_markdown( (string) $lp_post->post_content );
		$lp_intro_paras = $lp_parsed['intro'];
		$lp_sections    = $lp_parsed['sections'];
	}

	$lp_toc = array();
	foreach ( array_merge( $lp_sections, $lp_sections_after ) as $lp_toc_row ) {
		if ( '' === ( $lp_toc_row['id'] ?? '' ) || '' === ( $lp_toc_row['heading'] ?? '' ) ) {
			continue;
		}
		$lp_toc[] = array(
			'id'    => $lp_toc_row['id'],
			'title' => $lp_toc_row['heading'],
		);
	}

	$lp_blog_page_id = (int) get_option( 'page_for_posts' );
	$lp_blog_url     = $lp_blog_page_id ? get_permalink( $lp_blog_page_id ) : home_url( '/blog/' );
	$lp_docs_url     = lp_docs_url();

	$lp_crumbs       = array(
		array(
			'label' => 'HOME',
			'href'  => home_url( '/' ),
		),
		array(
			'label' => 'DOCS',
			'href'  => $lp_docs_url,
		),
		array(
			'label' => 'BLOG',
			'href'  => $lp_blog_url,
		),
		array( 'label' => strtoupper( get_the_title() ) ),
	);
	$lp_crumb_action = array(
		'label' => 'ALL WRITING ↗',
		'href'  => $lp_blog_url,
	);

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
				<p class="max-w-[640px] font-body text-[15px] leading-[1.6] tracking-[0.1px] text-base-content/65"><?php echo esc_html( $lp_standfirst ); ?></p>
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

		<figure class="relative w-full aspect-[16/9] bg-base-300 m-0 overflow-hidden" data-component="blog-detail-media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php
				lp_part(
					'components/media-photo',
					array(
						'image_id'      => get_post_thumbnail_id(),
						'element'       => 'img',
						'layout'        => 'fill',
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
				<aside class="w-full lg:w-[360px] shrink-0 lg:sticky lg:top-[24px] lg:self-start" data-component="blog-detail-toc">
					<?php if ( $lp_toc ) : ?>
						<p class="font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-base-content m-0">IN THIS ARTICLE</p>
						<div class="h-3.5" aria-hidden="true"></div>
						<div class="h-px w-full bg-base-content" aria-hidden="true"></div>
						<nav aria-label="In this article">
							<?php foreach ( $lp_toc as $lp_i => $lp_entry ) : ?>
								<?php
								$lp_n           = str_pad( (string) ( $lp_i + 1 ), 2, '0', STR_PAD_LEFT );
								$lp_index_class = 0 === $lp_i
									? 'font-label text-[10px] font-normal tracking-[0.8px] shrink-0 w-[20px] text-accent'
									: 'font-label text-[10px] font-normal tracking-[0.8px] shrink-0 w-[20px] text-base-content/65';
								?>
								<a href="#<?php echo esc_attr( $lp_entry['id'] ); ?>" class="flex items-start gap-[14px] w-full py-[13px] border-b border-base-300 no-underline text-left">
									<span class="<?php echo esc_attr( $lp_index_class ); ?>"><?php echo esc_html( $lp_n ); ?></span>
									<span class="font-body text-[12px] font-normal tracking-[0.2px] leading-[1.4] text-base-content min-w-0 flex-1"><?php echo esc_html( $lp_entry['title'] ); ?></span>
								</a>
							<?php endforeach; ?>
						</nav>
						<div class="h-[26px]" aria-hidden="true"></div>
					<?php endif; ?>
					<a href="#" class="font-label text-[11px] font-semibold uppercase tracking-[1px] text-base-content hover:text-base-content/70 transition-colors duration-150">SHARE THIS ↗</a>
				</aside>
				<article class="w-full max-w-[720px] flex flex-col gap-[28px]">
					<?php foreach ( $lp_intro_paras as $lp_i => $lp_para ) : ?>
						<p class="<?php echo esc_attr( 0 === $lp_i ? 'font-body text-[16px] leading-[1.75] tracking-[0.1px] text-base-content' : 'font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content' ); ?>"><?php echo wp_kses_post( lp_blog_inline_markdown( $lp_para ) ); ?></p>
					<?php endforeach; ?>
					<?php foreach ( $lp_sections as $lp_section ) : ?>
						<h3 id="<?php echo esc_attr( $lp_section['id'] ); ?>" class="font-heading text-[27px] font-semibold tracking-[-0.5px] text-base-content scroll-mt-[24px]"><?php echo esc_html( $lp_section['heading'] ); ?></h3>
						<?php foreach ( $lp_section['paragraphs'] as $lp_para ) : ?>
							<p class="font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content"><?php echo wp_kses_post( lp_blog_inline_markdown( $lp_para ) ); ?></p>
						<?php endforeach; ?>
					<?php endforeach; ?>
					<?php if ( $lp_pull_quote ) : ?>
						<blockquote class="border-l-2 border-accent pl-[24px] flex flex-col gap-[12px]" data-component="blog-detail-pull-quote">
							<p class="font-display font-bold text-[31px] leading-[1.2] tracking-[-0.6px] text-base-content m-0"><?php echo esc_html( $lp_pull_quote['quote'] ); ?></p>
							<cite class="not-italic font-label text-[10px] font-semibold uppercase tracking-[1px] text-base-content/65"><?php echo esc_html( $lp_pull_quote['attribution'] ); ?></cite>
						</blockquote>
					<?php endif; ?>
					<?php foreach ( $lp_sections_after as $lp_section ) : ?>
						<h3 id="<?php echo esc_attr( $lp_section['id'] ); ?>" class="font-heading text-[27px] font-semibold tracking-[-0.5px] text-base-content scroll-mt-[24px]"><?php echo esc_html( $lp_section['heading'] ); ?></h3>
						<?php foreach ( $lp_section['paragraphs'] as $lp_para ) : ?>
							<p class="font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content"><?php echo wp_kses_post( lp_blog_inline_markdown( $lp_para ) ); ?></p>
						<?php endforeach; ?>
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
