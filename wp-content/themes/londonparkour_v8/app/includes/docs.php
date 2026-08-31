<?php
/**
 * Docs / wiki helpers — support CPT at /docs/{slug}, wiki landing at /docs,
 * FAQ at /docs/frequently-asked-questions.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wiki landing URL.
 */
function lp_docs_url(): string {
	return home_url( '/docs/' );
}

/**
 * Blog index URL.
 */
function lp_docs_blog_url(): string {
	$lp_blog = (string) get_permalink( (int) get_option( 'page_for_posts' ) );
	if ( '' === $lp_blog || '0' === $lp_blog ) {
		$lp_blog = home_url( '/blog/' );
	}
	return $lp_blog;
}

/**
 * Find a published support post by any of the given slugs.
 *
 * @param string[] $lp_slugs Slugs to try.
 * @return WP_Post|null
 */
function lp_docs_find_support( array $lp_slugs ): ?WP_Post {
	foreach ( $lp_slugs as $lp_slug ) {
		$lp_post = get_page_by_path( $lp_slug, OBJECT, 'support' );
		if ( $lp_post instanceof WP_Post ) {
			return $lp_post;
		}
	}
	return null;
}

/**
 * FAQ is the Wiki landing at /docs.
 */
function lp_docs_faq_url(): string {
	return lp_docs_url();
}

/**
 * Whether this support post is the FAQ page.
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_is_faq( ?WP_Post $lp_post ): bool {
	if ( ! $lp_post ) {
		return false;
	}
	return in_array( $lp_post->post_name, array( 'frequently-asked-questions', 'faq' ), true );
}

/**
 * Whether this support post is Class Locations (links out to the Classes map).
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_is_class_locations( ?WP_Post $lp_post ): bool {
	if ( ! $lp_post ) {
		return false;
	}
	return in_array( $lp_post->post_name, array( 'class-locations', 'class-location', 'locations' ), true )
		|| 0 === strcasecmp( get_the_title( $lp_post ), 'Class Locations' );
}

/**
 * Gift Cards wiki URL.
 */
function lp_docs_gift_cards_url(): string {
	$lp_post = lp_docs_find_support( array( 'gift-cards', 'giftcards', 'gift-card' ) );
	if ( $lp_post ) {
		return (string) get_permalink( $lp_post );
	}
	return home_url( '/docs/gift-cards/' );
}

/**
 * Whether this support post is the Gift Cards article.
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_is_gift_cards( ?WP_Post $lp_post ): bool {
	if ( ! $lp_post ) {
		return false;
	}
	return (bool) preg_match( '/gift-?cards?/', $lp_post->post_name );
}

/**
 * Whether this support post is Terms of service.
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_is_terms( ?WP_Post $lp_post ): bool {
	if ( ! $lp_post ) {
		return false;
	}
	$lp_slug  = $lp_post->post_name;
	$lp_title = strtolower( $lp_post->post_title );
	return in_array( $lp_slug, array( 'terms', 'terms-of-service' ), true )
		|| false !== strpos( $lp_title, 'terms of service' );
}

/**
 * Whether this support post is the student waiver.
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_is_waiver( ?WP_Post $lp_post ): bool {
	if ( ! $lp_post ) {
		return false;
	}
	$lp_slug  = $lp_post->post_name;
	$lp_title = strtolower( $lp_post->post_title );
	return in_array( $lp_slug, array( 'student-waiver', 'waiver' ), true )
		|| false !== strpos( $lp_title, 'student waiver' );
}

/**
 * Public waiver URL — live site is /waiver/, not /docs/student-waiver/.
 */
function lp_docs_waiver_url(): string {
	$lp_post = lp_docs_find_support( array( 'student-waiver', 'waiver' ) );
	if ( $lp_post ) {
		return (string) get_permalink( $lp_post );
	}
	return home_url( '/waiver/' );
}

/**
 * Wiki | Legal for a support post.
 *
 * Terms of service and the student waiver use the Legal page (clauses, doc
 * meta). An editor can also set Template = Legal on any support post. Every
 * other support post uses the wiki article chrome.
 *
 * @param int|WP_Post|null $lp_post Post.
 */
function lp_docs_template_mode( $lp_post = null ): string {
	$lp_obj = $lp_post instanceof WP_Post ? $lp_post : null;
	if ( ! $lp_obj ) {
		$lp_id = (int) $lp_post;
		if ( $lp_id < 1 ) {
			$lp_id = (int) get_the_ID();
		}
		$lp_obj = $lp_id > 0 ? get_post( $lp_id ) : null;
	}
	if ( ! $lp_obj instanceof WP_Post ) {
		return 'wiki';
	}
	if ( lp_docs_is_terms( $lp_obj ) || lp_docs_is_waiver( $lp_obj ) ) {
		return 'legal';
	}
	$lp_mode = function_exists( 'get_field' ) ? (string) get_field( 'docs_template', $lp_obj->ID ) : '';
	return 'legal' === $lp_mode ? 'legal' : 'wiki';
}

/**
 * Classes | Company | Website. Unset defaults to classes.
 *
 * @param int|WP_Post|null $lp_post Post.
 */
function lp_docs_index_group( $lp_post = null ): string {
	$lp_id = $lp_post instanceof WP_Post ? (int) $lp_post->ID : (int) $lp_post;
	if ( $lp_id < 1 ) {
		$lp_id = (int) get_the_ID();
	}
	$lp_group = function_exists( 'get_field' ) ? (string) get_field( 'docs_index_group', $lp_id ) : '';
	return in_array( $lp_group, array( 'classes', 'company', 'website' ), true ) ? $lp_group : 'classes';
}

/**
 * Published support count.
 */
function lp_docs_support_count(): int {
	$lp_counts = wp_count_posts( 'support' );
	return ( is_object( $lp_counts ) && isset( $lp_counts->publish ) ) ? (int) $lp_counts->publish : 0;
}

/**
 * Published story count (blog CPT, else native posts).
 */
function lp_docs_story_count(): int {
	$lp_type   = post_type_exists( 'blog' ) ? 'blog' : 'post';
	$lp_counts = wp_count_posts( $lp_type );
	return ( is_object( $lp_counts ) && isset( $lp_counts->publish ) ) ? (int) $lp_counts->publish : 0;
}

/**
 * Wiki / Blog / Gift Cards switcher rows.
 *
 * @param string $lp_active wiki|blog|gift-cards.
 * @return array
 */
function lp_docs_switcher_rows( string $lp_active = 'wiki' ): array {
	$lp_pages   = lp_docs_support_count();
	$lp_stories = lp_docs_story_count();

	return array(
		array(
			'index'   => 'SECTION A',
			'title'   => 'Wiki',
			'meta'    => sprintf( '%d pages', $lp_pages ?: 15 ),
			'icon'    => 'icon-book-open',
			'href'    => lp_docs_url(),
			'current' => 'wiki' === $lp_active,
		),
		array(
			'index'   => 'SECTION B',
			'title'   => 'Blog',
			'meta'    => sprintf( '%d stories', $lp_stories ?: 12 ),
			'icon'    => 'icon-newspaper',
			'href'    => lp_docs_blog_url(),
			'current' => 'blog' === $lp_active,
		),
		array(
			'index'   => 'SECTION C',
			'title'   => 'Gift Cards',
			'meta'    => 'buying, redeeming, expiry',
			'icon'    => 'icon-tag',
			'href'    => lp_docs_gift_cards_url(),
			'current' => 'gift-cards' === $lp_active,
		),
	);
}

/**
 * Docs Index groups from published support posts, A–Z per column.
 *
 * @param string $lp_current_title Title marked CURRENT. FAQ landing marks Frequently Asked Questions.
 * @return array
 */
function lp_docs_index_groups( string $lp_current_title = '' ): array {
	$lp_query = new WP_Query(
		array(
			'post_type'      => 'support',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);

	$lp_buckets = array(
		'classes' => array(),
		'company' => array(),
		'website' => array(),
	);

	$lp_faq_row = null;
	foreach ( $lp_query->posts as $lp_post ) {
		if ( ! $lp_post instanceof WP_Post ) {
			continue;
		}
		$lp_title  = get_the_title( $lp_post );
		$lp_marker = ( '' !== $lp_current_title && $lp_title === $lp_current_title ) ? 'CURRENT' : '↗';
		if ( lp_docs_is_faq( $lp_post ) ) {
			$lp_faq_row = array(
				'title'  => $lp_title,
				'marker' => $lp_marker,
				'href'   => lp_docs_url(),
			);
			continue;
		}
		$lp_href = (string) get_permalink( $lp_post );
		if ( lp_docs_is_class_locations( $lp_post ) ) {
			$lp_href = function_exists( 'lp_classes_page_url' )
				? lp_classes_page_url( 'classes-map' )
				: home_url( '/classes-map/' );
		}
		$lp_group = lp_docs_index_group( $lp_post );
		$lp_buckets[ $lp_group ][] = array(
			'title'  => $lp_title,
			'marker' => $lp_marker,
			'href'   => $lp_href,
		);
	}

	if ( ! $lp_faq_row ) {
		$lp_faq_title = 'Frequently Asked Questions';
		$lp_faq_row   = array(
			'title'  => $lp_faq_title,
			'marker' => ( '' !== $lp_current_title && $lp_current_title === $lp_faq_title ) ? 'CURRENT' : '↗',
			'href'   => lp_docs_url(),
		);
	}
	array_unshift( $lp_buckets['classes'], $lp_faq_row );

	return array(
		array(
			'heading' => 'CLASSES',
			'pages'   => $lp_buckets['classes'],
		),
		array(
			'heading' => 'COMPANY',
			'pages'   => $lp_buckets['company'],
		),
		array(
			'heading' => 'WEBSITE',
			'pages'   => $lp_buckets['website'],
		),
	);
}

/**
 * Resolve a support URL by slug.
 *
 * @param string $lp_slug     Support slug.
 * @param string $lp_fallback Fallback URL.
 */
function lp_docs_wiki_url( string $lp_slug, string $lp_fallback = '' ): string {
	$lp_post = get_page_by_path( $lp_slug, OBJECT, 'support' );
	if ( $lp_post instanceof WP_Post ) {
		return (string) get_permalink( $lp_post );
	}
	return $lp_fallback;
}

/**
 * Echo the Docs Index band.
 *
 * @param string $lp_current_title CURRENT row title.
 */
function lp_docs_render_index( string $lp_current_title = '' ): void {
	$lp_groups = lp_docs_index_groups( $lp_current_title );
	$lp_count  = lp_docs_support_count();
	$lp_label  = sprintf( '%d PAGES', $lp_count );

	$lp_current_page = null;
	foreach ( $lp_groups as $lp_group ) {
		foreach ( $lp_group['pages'] as $lp_page ) {
			if ( 'CURRENT' === $lp_page['marker'] ) {
				$lp_current_page = $lp_page;
				break 2;
			}
		}
	}
	if ( ! $lp_current_page ) {
		$lp_current_page = $lp_groups[0]['pages'][0] ?? array(
			'title' => __( 'Docs', 'londonparkour_v8' ),
		);
	}

	$lp_echo_index_groups = static function ( array $lp_groups ) {
		foreach ( $lp_groups as $lp_group ) :
			?>
			<div class="flex flex-col">
				<span class="font-label text-[10px] font-semibold tracking-[1px] uppercase text-base-content/65 pb-3"><?php echo esc_html( $lp_group['heading'] ); ?></span>
				<div class="divide-y divide-base-300">
					<?php foreach ( $lp_group['pages'] as $lp_page ) : ?>
						<?php
						lp_part(
							'components/list-row',
							array(
								'index'   => '',
								'title'   => $lp_page['title'],
								'meta'    => '',
								'marker'  => $lp_page['marker'],
								'href'    => $lp_page['href'],
								'surface' => 'panel',
							)
						);
						?>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		endforeach;
	};
	?>
	<div class="w-full bg-base-200" data-component="docs-index" id="docs-index">
		<details class="group/docs lg:hidden" data-component="docs-index-picker">
			<summary class="list-none cursor-pointer px-6 py-4 [&::-webkit-details-marker]:hidden" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: current wiki page title */ __( 'Docs index: %s', 'londonparkour_v8' ), $lp_current_page['title'] ) ); ?>">
				<span class="flex flex-col gap-2">
					<span class="flex items-end justify-between gap-4">
						<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-base-content">DOCS INDEX</span>
						<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_label ); ?></span>
					</span>
					<span class="flex items-center justify-between gap-3">
						<span class="font-heading text-[16px] font-medium tracking-[-0.2px] text-base-content min-w-0"><?php echo esc_html( $lp_current_page['title'] ); ?></span>
						<span class="shrink-0" aria-hidden="true"><?php lp_icon( 'icon-chevron-down', 'w-5 h-5 shrink-0 text-accent transition-transform duration-200 group-open/docs:rotate-180' ); ?></span>
					</span>
				</span>
			</summary>
			<div class="px-6 pb-6 flex flex-col gap-10">
				<?php $lp_echo_index_groups( $lp_groups ); ?>
			</div>
		</details>
		<div class="hidden lg:flex px-6 lg:px-16 py-scale-2xl flex-col gap-[28px]">
			<div class="flex items-end justify-between gap-4">
				<span class="font-label text-[11px] font-bold tracking-[1.2px] uppercase text-base-content">DOCS INDEX</span>
				<span class="font-label text-[10px] font-normal uppercase tracking-[0.8px] text-base-content/65"><?php echo esc_html( $lp_label ); ?></span>
			</div>
			<div class="h-px w-full bg-base-content" aria-hidden="true"></div>
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-x-16 gap-y-10">
				<?php $lp_echo_index_groups( $lp_groups ); ?>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Switcher + Docs Index — the wiki block shared by every docs URL, including Legal.
 *
 * @param string $lp_switcher wiki|blog|gift-cards.
 * @param string $lp_current  Index CURRENT title.
 */
function lp_docs_render_wiki_nav( string $lp_switcher, string $lp_current = '' ): void {
	lp_render_block(
		'section-directory',
		array(
			'rows' => lp_docs_switcher_rows( $lp_switcher ),
		)
	);

	lp_docs_render_index( $lp_current );
}

/**
 * Shared docs chrome crumbs + masthead + wiki nav.
 *
 * @param string $lp_third_crumb Third breadcrumb label.
 * @param string $lp_switcher    wiki|blog|gift-cards.
 * @param string $lp_current     Index CURRENT title.
 */
function lp_docs_render_wiki_chrome_start( string $lp_third_crumb, string $lp_switcher, string $lp_current = '' ): void {
	lp_part(
		'components/breadcrumb-rail',
		array(
			'crumbs' => array(
				array(
					'label' => 'HOME',
					'href'  => home_url( '/' ),
				),
				array(
					'label' => 'DOCS',
					'href'  => lp_docs_url(),
				),
				array( 'label' => $lp_third_crumb ),
			),
			'action' => array(
				'label' => 'ALL DOCS ↗',
				'href'  => lp_docs_url(),
			),
		)
	);

	lp_part(
		'components/page-masthead',
		array(
			'title' => 'Questions, answered.',
			'note'  => 'Guides, FAQs and stories from LondonParkour. Start with answers to common questions — or switch to Blog for news and projects.',
		)
	);

	lp_docs_render_wiki_nav( $lp_switcher, $lp_current );
}

/**
 * Passenger enquiries + onward, shared by FAQ landing and wiki articles.
 */
function lp_docs_render_wiki_chrome_end(): void {
	lp_render_block( 'passenger-enquiries', array() );

	lp_part(
		'components/page-onward',
		array(
			'prev' => array(
				'keyword' => '← CONTACT',
				'label'   => 'Send us a message',
				'href'    => home_url( '/contact/' ),
			),
			'next' => array(
				'keyword' => 'BOOK A CLASS →',
				'label'   => 'Or just turn up in trainers',
				'href'    => (string) get_post_type_archive_link( lp_class_post_type() ),
			),
		)
	);
}

/**
 * Render a v7 support post body. Imported posts store markdown in
 * post_content — same path as blog, not `the_content()` which would print
 * `##` headings as paragraphs.
 *
 * @param WP_Post|null $lp_post Post.
 */
function lp_docs_render_markdown_body( ?WP_Post $lp_post ): void {
	if ( ! $lp_post instanceof WP_Post ) {
		return;
	}

	$lp_parsed = lp_blog_parse_markdown( (string) $lp_post->post_content );
	$lp_h      = 'font-heading text-[27px] font-semibold tracking-[-0.5px] text-base-content scroll-mt-[24px]';

	lp_blog_render_blocks(
		(array) ( $lp_parsed['intro'] ?? array() ),
		array(
			'lead'          => true,
			'heading_start' => 'h3',
		)
	);
	foreach ( $lp_parsed['sections'] as $lp_section ) {
		echo '<h2 id="' . esc_attr( $lp_section['id'] ) . '" class="' . esc_attr( $lp_h ) . '">' . esc_html( $lp_section['heading'] ) . '</h2>';
		lp_blog_render_blocks(
			(array) ( $lp_section['blocks'] ?? array() ),
			array(
				'lead'          => false,
				'heading_start' => 'h3',
			)
		);
	}
}

/**
 * Route support singles to wiki or Legal templates.
 *
 * @param string $lp_template Current template.
 * @return string
 */
function lp_docs_template_include( string $lp_template ): string {
	if ( ! is_singular( 'support' ) ) {
		return $lp_template;
	}

	if ( 'legal' === lp_docs_template_mode( get_post() ) ) {
		$lp_legal = get_theme_file_path( 'templates/legal.php' );
		return is_readable( $lp_legal ) ? $lp_legal : $lp_template;
	}

	$lp_wiki = get_theme_file_path( 'templates/docs-wiki.php' );
	return is_readable( $lp_wiki ) ? $lp_wiki : $lp_template;
}
add_filter( 'template_include', 'lp_docs_template_include' );

/**
 * Force support permalinks under /docs/{slug} even before ACF JSON syncs.
 *
 * @param array  $lp_args      register_post_type args.
 * @param string $lp_post_type Post type.
 * @return array
 */
function lp_docs_support_post_type_args( array $lp_args, string $lp_post_type ): array {
	if ( 'support' !== $lp_post_type ) {
		return $lp_args;
	}
	$lp_args['rewrite']     = array(
		'slug'       => 'docs',
		'with_front' => false,
	);
	$lp_args['has_archive'] = false;
	return $lp_args;
}
add_filter( 'register_post_type_args', 'lp_docs_support_post_type_args', 20, 2 );

/**
 * /support/{slug} still resolves after the rewrite slug became docs.
 */
function lp_docs_rewrite(): void {
	add_rewrite_rule( '^support/([^/]+)/?$', 'index.php?support=$matches[1]', 'top' );
	add_rewrite_rule( '^waiver/?$', 'index.php?lp_docs_waiver=1', 'top' );
}
add_action( 'init', 'lp_docs_rewrite', 11 );

/**
 * @param string[] $lp_vars Query vars.
 * @return string[]
 */
function lp_docs_query_vars( array $lp_vars ): array {
	$lp_vars[] = 'lp_docs_waiver';
	return $lp_vars;
}
add_filter( 'query_vars', 'lp_docs_query_vars' );

/**
 * Resolve /waiver/ to whichever support slug exists.
 *
 * @param array $lp_query_vars Request vars.
 * @return array
 */
function lp_docs_waiver_request( array $lp_query_vars ): array {
	if ( empty( $lp_query_vars['lp_docs_waiver'] ) ) {
		return $lp_query_vars;
	}
	unset( $lp_query_vars['lp_docs_waiver'] );
	$lp_post = lp_docs_find_support( array( 'student-waiver', 'waiver' ) );
	if ( $lp_post ) {
		$lp_query_vars['post_type'] = 'support';
		$lp_query_vars['support']   = $lp_post->post_name;
		$lp_query_vars['name']      = $lp_post->post_name;
	}
	return $lp_query_vars;
}
add_filter( 'request', 'lp_docs_waiver_request' );

/**
 * Canonical permalink for the waiver is /waiver/, matching the live site.
 *
 * @param string  $lp_permalink Default permalink.
 * @param WP_Post $lp_post      Post.
 */
function lp_docs_waiver_permalink( string $lp_permalink, $lp_post ): string {
	if ( $lp_post instanceof WP_Post && 'support' === $lp_post->post_type && lp_docs_is_waiver( $lp_post ) ) {
		return home_url( '/waiver/' );
	}
	return $lp_permalink;
}
add_filter( 'post_type_link', 'lp_docs_waiver_permalink', 10, 2 );

/**
 * Flush rewrites once after the docs slug change.
 */
function lp_docs_maybe_flush_rewrites(): void {
	$lp_flag = 'lp_docs_rewrite_v3';
	if ( get_option( $lp_flag ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $lp_flag, '1' );
}
add_action( 'init', 'lp_docs_maybe_flush_rewrites', 99 );

/**
 * Old URL map: /docs-faq, /legal, /docs/faq, /support/{slug}.
 */
function lp_docs_redirects(): void {
	$lp_request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );

	if ( is_page( 'docs-faq' ) || 'docs-faq' === $lp_request ) {
		wp_safe_redirect( lp_docs_url(), 301 );
		exit;
	}

	if ( is_page( 'legal' ) ) {
		$lp_terms = lp_docs_find_support( array( 'terms', 'terms-of-service' ) );
		wp_safe_redirect( $lp_terms ? (string) get_permalink( $lp_terms ) : lp_docs_url(), 301 );
		exit;
	}

	if ( 'docs/faq' === $lp_request || ( is_singular( 'support' ) && lp_docs_is_faq( get_post() ) ) ) {
		wp_safe_redirect( lp_docs_url(), 301 );
		exit;
	}

	if ( is_singular( 'support' ) && lp_docs_is_waiver( get_post() ) && 'waiver' !== $lp_request ) {
		wp_safe_redirect( lp_docs_waiver_url(), 301 );
		exit;
	}

	if ( is_singular( 'support' ) && lp_docs_is_class_locations( get_post() ) ) {
		$lp_map = function_exists( 'lp_classes_page_url' )
			? lp_classes_page_url( 'classes-map' )
			: home_url( '/classes-map/' );
		wp_safe_redirect( $lp_map, 301 );
		exit;
	}

	if ( is_singular( 'support' ) && 0 === strpos( $lp_request, 'support/' ) ) {
		wp_safe_redirect( (string) get_permalink(), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'lp_docs_redirects' );

/**
 * Known column + template for migrated support slugs / titles.
 *
 * @return array<string, array{group:string, template:string}>
 */
function lp_docs_known_support_map(): array {
	return array(
		'student-waiver'         => array( 'group' => 'classes', 'template' => 'legal' ),
		'waiver'                 => array( 'group' => 'classes', 'template' => 'legal' ),
		'youth-class'            => array( 'group' => 'classes', 'template' => 'wiki' ),
		'personal-training'      => array( 'group' => 'classes', 'template' => 'wiki' ),
		'personal-training-pt'   => array( 'group' => 'classes', 'template' => 'wiki' ),
		'hiring-us'              => array( 'group' => 'classes', 'template' => 'wiki' ),
		'frequently-asked-questions' => array( 'group' => 'classes', 'template' => 'faq' ),
		'faq'                    => array( 'group' => 'classes', 'template' => 'faq' ),
		'gift-cards'             => array( 'group' => 'classes', 'template' => 'wiki' ),
		'giftcards'              => array( 'group' => 'classes', 'template' => 'wiki' ),
		'class-locations'        => array( 'group' => 'classes', 'template' => 'wiki' ),
		'beginners-class'        => array( 'group' => 'classes', 'template' => 'wiki' ),
		'privacy'                => array( 'group' => 'company', 'template' => 'wiki' ),
		'privacy-policy'         => array( 'group' => 'company', 'template' => 'wiki' ),
		'pricing'                => array( 'group' => 'company', 'template' => 'wiki' ),
		'photography-video'      => array( 'group' => 'company', 'template' => 'wiki' ),
		'photography-and-video'  => array( 'group' => 'company', 'template' => 'wiki' ),
		'equality'               => array( 'group' => 'company', 'template' => 'wiki' ),
		'equality-policy'        => array( 'group' => 'company', 'template' => 'wiki' ),
		'contacting-us'          => array( 'group' => 'company', 'template' => 'wiki' ),
		'code-of-conduct'        => array( 'group' => 'company', 'template' => 'wiki' ),
		'terms'                  => array( 'group' => 'website', 'template' => 'legal' ),
		'terms-of-service'       => array( 'group' => 'website', 'template' => 'legal' ),
	);
}

/**
 * Apply column + template to support posts. Known slugs get the pen map;
 * everything else stays Classes + Wiki (the field defaults).
 *
 * @return int Posts updated.
 */
function lp_docs_seed_support_fields(): int {
	if ( ! function_exists( 'update_field' ) ) {
		return 0;
	}

	$lp_map   = lp_docs_known_support_map();
	$lp_query = new WP_Query(
		array(
			'post_type'      => 'support',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		)
	);

	$lp_updated = 0;
	foreach ( $lp_query->posts as $lp_post ) {
		if ( ! $lp_post instanceof WP_Post ) {
			continue;
		}
		$lp_known = $lp_map[ $lp_post->post_name ] ?? null;
		$lp_group = $lp_known['group'] ?? 'classes';
		$lp_mode  = $lp_known['template'] ?? 'wiki';
		update_field( 'docs_index_group', $lp_group, $lp_post->ID );
		update_field( 'docs_template', $lp_mode, $lp_post->ID );
		++$lp_updated;
	}

	return $lp_updated;
}

/**
 * Publish a student-waiver support post when v7 import did not leave one.
 *
 * @return int Post ID, or 0 on failure.
 */
function lp_docs_ensure_waiver_post(): int {
	$lp_existing = lp_docs_find_support( array( 'student-waiver', 'waiver' ) );
	if ( $lp_existing ) {
		return (int) $lp_existing->ID;
	}

	$lp_id = wp_insert_post(
		array(
			'post_type'    => 'support',
			'post_status'  => 'publish',
			'post_title'   => 'Student Waiver',
			'post_name'    => 'student-waiver',
			'post_content' => '',
		),
		true
	);

	return is_wp_error( $lp_id ) ? 0 : (int) $lp_id;
}
