<?php
/**
 * Tutorial series helpers — runtime, shelves, overview URL, rewrite.
 *
 * Runtime is never the ACF `duration` field on an lp_tutorial (that is a
 * challenge-hold timer). Order:
 *   1. video_youtube_data → contentDetails.duration_secs (or ISO 8601)
 *   2. last video_transcript_json cue: start + duration
 *   3. 0
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public URL for the series overview page (`/tutorials/series/`).
 */
function lp_tutorials_series_url(): string {
	return home_url( '/tutorials/series/' );
}

/**
 * Public URL for the category board page (`/tutorials/category/`).
 */
function lp_tutorials_category_url(): string {
	return home_url( '/tutorials/category/' );
}

/**
 * Tutorial-tag slugs the category board can toggle.
 *
 * @return string[]
 */
function lp_tutorial_kind_slugs(): array {
	return array( 'tutorial', 'challenge', 'demonstration' );
}

/**
 * Default category-board kinds: tutorials and challenges. Demos stay off.
 *
 * @return string[]
 */
function lp_tutorial_kind_defaults(): array {
	return array( 'tutorial', 'challenge' );
}

/**
 * Kinds currently shown on the category board.
 *
 * No `tutorial_kinds` in the URL means the default (demos off). An empty
 * param means every toggle is off.
 *
 * @return string[]
 */
function lp_tutorial_kind_filter_values(): array {
	$allowed = lp_tutorial_kind_slugs();

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filter.
	if ( ! isset( $_GET['tutorial_kinds'] ) ) {
		return lp_tutorial_kind_defaults();
	}

	$raw = sanitize_text_field( wp_unslash( $_GET['tutorial_kinds'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( '' === $raw ) {
		return array();
	}

	$out = array();
	foreach ( explode( ',', $raw ) as $slug ) {
		$slug = sanitize_title( $slug );
		if ( in_array( $slug, $allowed, true ) && ! in_array( $slug, $out, true ) ) {
			$out[] = $slug;
		}
	}

	return $out;
}

/**
 * Category board URL with category + kind toggles.
 *
 * Default kinds (tutorial + challenge) are omitted from the query string
 * so `/tutorials/category/` is the demos-off view.
 *
 * @param string   $category Category slug, or empty for all.
 * @param string[] $kinds    On slugs.
 */
function lp_tutorials_category_filter_url( string $category = '', array $kinds = array() ): string {
	$url   = lp_tutorials_category_url();
	$args  = array();
	$canon = array_values( array_intersect( lp_tutorial_kind_slugs(), $kinds ) );

	if ( '' !== $category ) {
		$args['tutorial_category'] = $category;
	}

	if ( $canon !== lp_tutorial_kind_defaults() ) {
		$args['tutorial_kinds'] = implode( ',', $canon );
	}

	return $args ? (string) add_query_arg( $args, $url ) : $url;
}

/**
 * Toggle one kind on or off, keeping the others.
 *
 * @param string   $slug  Kind to flip.
 * @param string[] $kinds Currently on.
 * @return string[]
 */
function lp_tutorial_kind_toggle( string $slug, array $kinds ): array {
	if ( in_array( $slug, $kinds, true ) ) {
		$kinds = array_values( array_diff( $kinds, array( $slug ) ) );
	} else {
		$kinds[] = $slug;
	}

	return array_values( array_intersect( lp_tutorial_kind_slugs(), $kinds ) );
}

/**
 * Non-empty lp_series terms, newest name first (2019… → 2024…). hide_empty so unused series stay off the rails.
 *
 * @return WP_Term[]
 */
function lp_series_terms_nonempty(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'lp_series',
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'DESC',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * Published lp_tutorial count.
 */
function lp_tutorials_published_count(): int {
	return (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );
}

/**
 * Parent movement families with at least one tutorial.
 *
 * Child moves (Step-Vault, Precisions, …) are shelves inside a family,
 * not extra categories.
 */
function lp_tutorials_category_count(): int {
	$terms = get_terms(
		array(
			'taxonomy'   => 'tutorial-category',
			'parent'     => 0,
			'hide_empty' => true,
		)
	);

	return is_array( $terms ) ? count( $terms ) : 0;
}

/**
 * View-rail tabs shared by the tutorial archive, series overview, series
 * detail, and category board.
 *
 * @param string $active series|category|tutorial.
 * @return array<int,array<string,mixed>>
 */
function lp_tutorials_view_tabs( string $active = 'tutorial' ): array {
	$series_count   = count( lp_series_terms_nonempty() );
	$category_count = lp_tutorials_category_count();
	$tutorial_count = lp_tutorials_published_count();
	$archive_url    = (string) get_post_type_archive_link( 'lp_tutorial' );

	return array(
		array(
			'label'   => 'By series',
			'meta'    => sprintf( '%d series', $series_count ),
			'icon_id' => 'icon-square-3-stack-3d',
			'href'    => lp_tutorials_series_url(),
			'active'  => 'series' === $active,
		),
		array(
			'label'   => 'By category',
			'meta'    => sprintf( '%d categories', $category_count ),
			'icon_id' => 'icon-tag',
			'href'    => lp_tutorials_category_url(),
			'active'  => 'category' === $active,
		),
		array(
			'label'   => 'By tutorial',
			'meta'    => sprintf( '%d videos', $tutorial_count ),
			'icon_id' => 'icon-play-circle',
			'href'    => $archive_url,
			'active'  => 'tutorial' === $active,
		),
	);
}

/**
 * Published-tutorial count for one series term.
 *
 * @param int $term_id Series term ID.
 * @return int
 */
function lp_series_published_count( int $term_id ): int {
	static $cache = array();

	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'lp_tutorial',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'lp_series',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		)
	);

	$cache[ $term_id ] = (int) $q->found_posts;

	return $cache[ $term_id ];
}

/**
 * Series poster attachment ID: ACF `poster`, else the first lesson thumbnail.
 *
 * @param int                  $term_id       Series term ID.
 * @param array<string,mixed>|null $fields    Preloaded ACF fields, or null to load.
 * @param WP_Post|int|null     $fallback_post Already-loaded first lesson, if any.
 */
function lp_series_poster_id( int $term_id, $fields = null, $fallback_post = null ): int {
	static $cache = array();

	if ( isset( $cache[ $term_id ] ) ) {
		return $cache[ $term_id ];
	}

	if ( ! is_array( $fields ) && function_exists( 'get_fields' ) ) {
		$fields = get_fields( 'term_' . $term_id );
	}
	$fields = is_array( $fields ) ? $fields : array();

	$raw = $fields['poster'] ?? 0;
	$id  = is_array( $raw )
		? (int) ( $raw['ID'] ?? $raw['id'] ?? 0 )
		: (int) $raw;

	if ( $id ) {
		$cache[ $term_id ] = $id;
		return $id;
	}

	$post_id = 0;
	if ( $fallback_post instanceof WP_Post ) {
		$post_id = (int) $fallback_post->ID;
	} elseif ( is_numeric( $fallback_post ) ) {
		$post_id = (int) $fallback_post;
	}

	if ( ! $post_id ) {
				$q = new WP_Query(
					array(
						'post_type'              => 'lp_tutorial',
						'post_status'            => 'publish',
						'posts_per_page'         => 1,
						'lp_natural_order'       => true,
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'tax_query'              => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'lp_series',
								'field'    => 'term_id',
								'terms'    => $term_id,
							),
						),
					)
				);
		$post_id = $q->posts ? (int) $q->posts[0] : 0;
	}

	$thumb                 = $post_id ? (int) get_post_thumbnail_id( $post_id ) : 0;
	$cache[ $term_id ]     = $thumb;

	return $thumb;
}

/**
 * Published tutorials in a series, category then curriculum order.
 *
 * Bound high enough for Demonstrations (~228) without going unbounded.
 *
 * @param int $term_id Series term ID.
 * @param int $limit   Max posts. Default 300.
 * @return WP_Post[]
 */
function lp_tutorials_in_series( int $term_id, int $limit = 300 ): array {
	$q = new WP_Query(
		array(
			'post_type'              => 'lp_tutorial',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'lp_natural_order'       => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'tax_query'              => array( 
				array(
					'taxonomy' => 'lp_series',
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		)
	);

	return $q->posts;
}

/**
 * Term ID for the 2020 Demonstration Library series, or 0.
 */
function lp_tutorial_demonstration_series_id(): int {
	$term = get_term_by( 'slug', '2020-demonstrations', 'lp_series' );

	return $term instanceof WP_Term ? (int) $term->term_id : 0;
}

/**
 * Published demonstrations in the 2020 library, optionally in one category.
 *
 * @param int   $series_id   lp_series term ID.
 * @param int   $category_id tutorial-category term ID. 0 = whole library.
 * @param int[] $exclude     Post IDs to skip (the current tutorial).
 * @param int   $limit       Max posts.
 * @return WP_Post[]
 */
function lp_tutorial_demonstrations_in_library( int $series_id, int $category_id = 0, array $exclude = array(), int $limit = 80 ): array {
	if ( $series_id <= 0 ) {
		return array();
	}

	$tax = array(
		array(
			'taxonomy' => 'lp_series',
			'field'    => 'term_id',
			'terms'    => $series_id,
		),
		array(
			'taxonomy' => 'tutorial-tag',
			'field'    => 'slug',
			'terms'    => 'demonstration',
		),
	);
	if ( $category_id > 0 ) {
		$tax[] = array(
			'taxonomy'         => 'tutorial-category',
			'field'            => 'term_id',
			'terms'            => $category_id,
			'include_children' => false,
		);
	}

	$args = array(
		'post_type'              => 'lp_tutorial',
		'post_status'            => 'publish',
		'posts_per_page'         => $limit,
		'lp_natural_order'       => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'tax_query'              => $tax,
	);
	$exclude = array_values( array_filter( array_map( 'intval', $exclude ) ) );
	if ( $exclude ) {
		$args['post__not_in'] = $exclude;
	}

	$q = new WP_Query( $args );

	return $q->posts;
}

/**
 * Strip view suffixes so "Box Jump – Front View" matches "Box Jump".
 */
function lp_tutorial_title_stem( string $title ): string {
	$title = strtolower( $title );
	$title = (string) preg_replace( '/\s*[–—-]\s*(front view|side view|front|side).*$/iu', '', $title );
	$title = (string) preg_replace( '/[^a-z0-9]+/i', ' ', $title );

	return trim( $title );
}

/**
 * Rank demonstration titles against the current tutorial's topic.
 *
 * @param WP_Post[] $posts
 * @return WP_Post[]
 */
function lp_tutorial_rank_demonstrations( array $posts, string $needle ): array {
	$needle = lp_tutorial_title_stem( $needle );
	usort(
		$posts,
		static function ( WP_Post $a, WP_Post $b ) use ( $needle ): int {
			$sa = lp_tutorial_demonstration_score( $needle, lp_tutorial_title_stem( $a->post_title ) );
			$sb = lp_tutorial_demonstration_score( $needle, lp_tutorial_title_stem( $b->post_title ) );
			if ( $sa === $sb ) {
				return strcasecmp( $a->post_title, $b->post_title );
			}

			return $sb <=> $sa;
		}
	);

	return $posts;
}

/**
 * Overlap + similar_text score. Higher is closer.
 */
function lp_tutorial_demonstration_score( string $needle, string $candidate ): int {
	if ( '' === $needle || '' === $candidate ) {
		return 0;
	}

	similar_text( $needle, $candidate, $percent );
	$needle_tokens     = array_values( array_filter( explode( ' ', $needle ) ) );
	$candidate_tokens  = array_values( array_filter( explode( ' ', $candidate ) ) );
	$overlap           = count( array_intersect( $needle_tokens, $candidate_tokens ) );
	$contained         = ( str_contains( $needle, $candidate ) || str_contains( $candidate, $needle ) ) ? 80 : 0;

	return (int) round( (float) $percent + ( $overlap * 40 ) + $contained );
}

/**
 * Two demonstrations from the 2020 library closest to this tutorial's topic.
 *
 * Prefers the same child category (Box Jump with Box Jump), then the parent
 * family (Jumping), then the rest of the library. Ranked by title against
 * the tutorial title plus its category names.
 *
 * @return WP_Post[]
 */
function lp_tutorial_related_demonstrations( int $post_id, int $limit = 2 ): array {
	$series_id = lp_tutorial_demonstration_series_id();
	if ( $series_id <= 0 || $post_id <= 0 ) {
		return array();
	}

	list( $parent, $child ) = lp_tutorial_category_pair( $post_id );
	$needle                 = trim(
		get_the_title( $post_id ) . ' '
		. ( $child instanceof WP_Term ? $child->name : '' ) . ' '
		. ( $parent instanceof WP_Term ? $parent->name : '' )
	);

	$pools = array();
	if ( $child instanceof WP_Term ) {
		$pools[] = lp_tutorial_demonstrations_in_library( $series_id, (int) $child->term_id, array( $post_id ), 40 );
	}
	if ( $parent instanceof WP_Term ) {
		$pools[] = lp_tutorial_demonstrations_in_library( $series_id, (int) $parent->term_id, array( $post_id ), 80 );
	}
	$pools[] = lp_tutorial_demonstrations_in_library( $series_id, 0, array( $post_id ), 120 );

	$picked = array();
	$seen   = array();
	foreach ( $pools as $pool ) {
		foreach ( lp_tutorial_rank_demonstrations( $pool, $needle ) as $post ) {
			if ( isset( $seen[ $post->ID ] ) ) {
				continue;
			}
			$seen[ $post->ID ] = true;
			$picked[]          = $post;
			if ( count( $picked ) >= $limit ) {
				return $picked;
			}
		}
	}

	return $picked;
}

/**
 * YouTube id for a tutorial: ACF `video_id`, then `video_url`.
 *
 * @param int $post_id Tutorial post ID.
 */
function lp_tutorial_youtube_id( int $post_id ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$id = lp_youtube_id_from_url( (string) get_field( 'video_id', $post_id ) );
	if ( '' === $id ) {
		$id = lp_youtube_id_from_url( (string) get_field( 'video_url', $post_id ) );
	}

	return $id;
}

/**
 * Parent and child tutorial-category terms for a tutorial.
 *
 * @param WP_Post|int $post Tutorial.
 * @return array{0:?WP_Term,1:?WP_Term} Parent, child.
 */
function lp_tutorial_category_pair( $post ): array {
	$id    = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	$terms = get_the_terms( $id, 'tutorial-category' );
	$terms = is_array( $terms ) ? $terms : array();

	$child  = null;
	$parent = null;
	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			$child = $term;
			break;
		}
	}
	foreach ( $terms as $term ) {
		if ( ! $term->parent ) {
			$parent = $term;
			break;
		}
	}
	if ( $child instanceof WP_Term && ! $parent instanceof WP_Term ) {
		$maybe  = get_term( (int) $child->parent, 'tutorial-category' );
		$parent = $maybe instanceof WP_Term ? $maybe : null;
	}

	return array( $parent, $child );
}

/**
 * Published tutorials in one tutorial-category term, curriculum order.
 *
 * `include_children` is off so a child move (Box Jump) does not pull in
 * the rest of the parent family. Pass `$series_id` to keep only tutorials
 * that also belong to that series (the detail-page board).
 *
 * @param int $term_id   tutorial-category term ID.
 * @param int $limit     Max posts. Default 300.
 * @param int $series_id Optional lp_series term ID to AND with the category.
 * @return WP_Post[]
 */
function lp_tutorials_in_category( int $term_id, int $limit = 300, int $series_id = 0 ): array {
	if ( $term_id <= 0 ) {
		return array();
	}

	$tax = array(
		array(
			'taxonomy'         => 'tutorial-category',
			'field'            => 'term_id',
			'terms'            => $term_id,
			'include_children' => false,
		),
	);
	if ( $series_id > 0 ) {
		$tax[] = array(
			'taxonomy' => 'lp_series',
			'field'    => 'term_id',
			'terms'    => $series_id,
		);
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'lp_tutorial',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'lp_natural_order'       => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'tax_query'              => $tax,
		)
	);

	return $q->posts;
}

/**
 * Raw transcript payload for download. Empty when the field is unset.
 *
 * @param int    $post_id Tutorial post ID.
 * @param string $kind    json|srt.
 */
function lp_tutorial_transcript_body( int $post_id, string $kind ): string {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$field = 'json' === $kind ? 'video_transcript_json' : 'video_transcript_srt';
	$raw   = get_field( $field, $post_id );
	if ( is_array( $raw ) ) {
		$encoded = wp_json_encode( $raw );
		$raw     = is_string( $encoded ) ? $encoded : '';
	}

	return is_string( $raw ) ? $raw : '';
}

/**
 * mm:ss clock for a transcript cue, matching the detail transcript rail.
 */
function lp_tutorial_cue_clock( float $seconds ): string {
	$total = max( 0, (int) floor( $seconds ) );

	return sprintf( '%02d:%02d', intdiv( $total, 60 ), $total % 60 );
}

/**
 * Timed cues for on-page reading. JSON is the caption array; SRT is parsed
 * into the same shape. Empty when the payload cannot be read as cues.
 *
 * @return array<int, array{clock: string, text: string}>
 */
function lp_tutorial_transcript_cues( string $raw, string $kind ): array {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return array();
	}

	if ( 'srt' === $kind ) {
		return lp_tutorial_parse_srt_cues( $raw );
	}

	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) ) {
		return array();
	}

	$cues = $data;
	foreach ( array( 'cues', 'transcript', 'events', 'items' ) as $key ) {
		if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
			$cues = $data[ $key ];
			break;
		}
	}

	$out = array();
	foreach ( $cues as $cue ) {
		if ( is_string( $cue ) && '' !== trim( $cue ) ) {
			$out[] = array(
				'clock' => '',
				'text'  => trim( $cue ),
			);
			continue;
		}
		if ( ! is_array( $cue ) ) {
			continue;
		}
		$text = $cue['text'] ?? $cue['content'] ?? $cue['transcript'] ?? '';
		if ( ! is_string( $text ) || '' === trim( $text ) ) {
			continue;
		}
		$start = 0.0;
		if ( isset( $cue['tStartMs'] ) ) {
			$start = (float) $cue['tStartMs'] / 1000;
		} elseif ( isset( $cue['startMs'] ) ) {
			$start = (float) $cue['startMs'] / 1000;
		} else {
			$start_raw = $cue['start'] ?? $cue['start_time'] ?? $cue['offset'] ?? 0;
			if ( is_string( $start_raw ) && preg_match( '/(\d{1,2}):(\d{2}):(\d{2})/', $start_raw, $time ) ) {
				$start = ( (int) $time[1] * 3600 ) + ( (int) $time[2] * 60 ) + (int) $time[3];
			} else {
				$start = (float) $start_raw;
			}
		}
		$out[] = array(
			'clock' => lp_tutorial_cue_clock( $start ),
			'text'  => trim( $text ),
		);
	}

	return $out;
}

/**
 * SRT blocks → clock + text rows.
 *
 * @return array<int, array{clock: string, text: string}>
 */
function lp_tutorial_parse_srt_cues( string $raw ): array {
	$raw    = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	$blocks = preg_split( '/\n\s*\n/', trim( $raw ) ) ?: array();
	$out    = array();

	foreach ( $blocks as $block ) {
		$lines = preg_split( '/\n/', trim( (string) $block ) ) ?: array();
		if ( count( $lines ) < 2 ) {
			continue;
		}
		if ( isset( $lines[0] ) && preg_match( '/^\d+$/', $lines[0] ) ) {
			array_shift( $lines );
		}
		$stamp = array_shift( $lines );
		if ( ! is_string( $stamp ) || ! preg_match( '/(\d{2}):(\d{2}):(\d{2})[,.](\d+)/', $stamp, $match ) ) {
			continue;
		}
		$secs = ( (int) $match[1] * 3600 ) + ( (int) $match[2] * 60 ) + (int) $match[3];
		$text = trim( implode( ' ', $lines ) );
		$text = wp_strip_all_tags( $text );
		if ( '' === $text ) {
			continue;
		}
		$out[] = array(
			'clock' => lp_tutorial_cue_clock( (float) $secs ),
			'text'  => $text,
		);
	}

	return $out;
}

/**
 * Pretty-print a transcript file for the raw accordion fallback.
 */
function lp_tutorial_transcript_pretty( string $raw, string $kind ): string {
	$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	if ( 'json' !== $kind ) {
		return $raw;
	}

	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) {
		return $raw;
	}

	$pretty = wp_json_encode( $decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

	return is_string( $pretty ) ? $pretty : $raw;
}

/**
 * Split a ChatGPT video summary into body paragraphs and key points.
 *
 * The stored field is a multi-paragraph essay. Many entries end with a
 * "Key Points" / "Highlights" / "Key Takeaways" list (markdown bold,
 * numbered, or dashed). Those bullets belong in KEY TAKEAWAYS, not in
 * the summary body. A trailing dash-list with no heading is treated
 * the same way.
 *
 * @return array{paragraphs: string[], takeaways: string[]}
 */
function lp_tutorial_parse_chatgpt_summary( string $raw ): array {
	$empty = array(
		'paragraphs' => array(),
		'takeaways'  => array(),
	);

	$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
	if ( ! str_contains( $raw, "\n" ) && str_contains( $raw, '\\n' ) ) {
		$raw = str_replace( '\\n', "\n", $raw );
	}
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return $empty;
	}

	$body      = $raw;
	$takeaways = array();

	$heading = '/(?:^|\n)\s*(?:\*\*)?\s*(?:Key\s*Points|Key\s*Takeaways(?:\s+include)?|Highlights|Takeaways)\s*(?:\*\*)?\s*:?\s*(?:\*\*)?\s*(?:\n|$)/iu';
	if ( preg_match( $heading, $raw, $match, PREG_OFFSET_CAPTURE ) ) {
		$body      = trim( substr( $raw, 0, (int) $match[0][1] ) );
		$takeaways = lp_tutorial_parse_chatgpt_list( trim( substr( $raw, (int) $match[0][1] + strlen( $match[0][0] ) ) ) );
	} elseif ( preg_match( '/\n[ \t]*\n((?:[ \t]*(?:[-•*]|\d+[.)])[ \t]+\S.*(?:\n|$))+)\s*$/u', $raw, $match ) ) {
		$maybe = lp_tutorial_parse_chatgpt_list( trim( $match[1] ) );
		if ( count( $maybe ) >= 2 ) {
			$takeaways = $maybe;
			$body      = trim( substr( $raw, 0, -strlen( $match[0] ) ) );
		}
	}

	$chunks     = preg_split( '/\n{2,}/', $body ) ?: array();
	$paragraphs = array();
	foreach ( $chunks as $chunk ) {
		$chunk = trim( (string) preg_replace( '/[ \t]*\n[ \t]*/', ' ', $chunk ) );
		$chunk = trim( (string) preg_replace( '/^\*{1,2}|\*{1,2}$/', '', $chunk ) );
		if ( '' !== $chunk ) {
			$paragraphs[] = $chunk;
		}
	}

	return array(
		'paragraphs' => $paragraphs,
		'takeaways'  => $takeaways,
	);
}

/**
 * Turn a ChatGPT list block into plain takeaway strings.
 *
 * @return string[]
 */
function lp_tutorial_parse_chatgpt_list( string $block ): array {
	$items   = array();
	$current = '';

	foreach ( preg_split( '/\n/', $block ) ?: array() as $line ) {
		$line = trim( (string) $line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/^(?:[-•*]|\d+[.)])\s+(.*)$/u', $line, $match ) ) {
			if ( '' !== $current ) {
				$items[] = $current;
			}
			$current = trim( $match[1] );
			continue;
		}
		if ( '' !== $current ) {
			$current .= ' ' . $line;
			continue;
		}
		$items[] = $line;
	}
	if ( '' !== $current ) {
		$items[] = $current;
	}

	$out = array();
	foreach ( $items as $item ) {
		$item = trim( str_replace( array( '**', '__' ), '', $item ) );
		$item = trim( (string) preg_replace( '/^[\-*•]+\s*/u', '', $item ) );
		if ( '' !== $item ) {
			$out[] = $item;
		}
	}

	return $out;
}

/**
 * Serve JSON / SRT transcript downloads from the tutorial permalink.
 */
function lp_tutorial_serve_transcript(): void {
	if ( ! is_singular( 'lp_tutorial' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public file download.
	$kind = isset( $_GET['lp_transcript'] ) ? sanitize_key( wp_unslash( $_GET['lp_transcript'] ) ) : '';
	if ( ! in_array( $kind, array( 'json', 'srt' ), true ) ) {
		return;
	}

	$post_id = get_queried_object_id();
	$body    = lp_tutorial_transcript_body( $post_id, $kind );
	if ( '' === trim( $body ) ) {
		status_header( 404 );
		nocache_headers();
		exit;
	}

	$slug = sanitize_file_name( (string) get_post_field( 'post_name', $post_id ) );
	if ( '' === $slug ) {
		$slug = 'transcript';
	}

	nocache_headers();
	if ( 'json' === $kind ) {
		header( 'Content-Type: application/json; charset=utf-8' );
	} else {
		header( 'Content-Type: application/x-subrip; charset=utf-8' );
	}
	header( 'Content-Disposition: attachment; filename="' . $slug . '.' . $kind . '"' );
	echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- file download body.
	exit;
}
add_action( 'template_redirect', 'lp_tutorial_serve_transcript' );

/**
 * Parse YouTube ISO-8601 duration (PT1H2M3S) to seconds.
 *
 * @param string $iso Duration string.
 * @return int
 */
function lp_parse_iso8601_duration( string $iso ): int {
	if ( ! preg_match( '/^PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+(?:\.\d+)?)S)?$/i', $iso, $m ) ) {
		return 0;
	}

	$hours   = isset( $m[1] ) && '' !== $m[1] ? (int) $m[1] : 0;
	$minutes = isset( $m[2] ) && '' !== $m[2] ? (int) $m[2] : 0;
	$seconds = isset( $m[3] ) && '' !== $m[3] ? (float) $m[3] : 0;

	return (int) round( ( $hours * 3600 ) + ( $minutes * 60 ) + $seconds );
}

/**
 * Last-cue runtime from a transcript JSON payload.
 *
 * @param mixed $raw JSON string or decoded array.
 * @return int
 */
function lp_transcript_runtime_seconds( $raw ): int {
	$data = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
	if ( ! is_array( $data ) ) {
		return 0;
	}

	$cues = $data;
	foreach ( array( 'cues', 'transcript', 'events', 'items' ) as $key ) {
		if ( isset( $data[ $key ] ) && is_array( $data[ $key ] ) ) {
			$cues = $data[ $key ];
			break;
		}
	}

	$last = null;
	foreach ( $cues as $cue ) {
		if ( is_array( $cue ) ) {
			$last = $cue;
		}
	}

	if ( ! is_array( $last ) ) {
		return 0;
	}

	$start = (float) ( $last['start'] ?? $last['start_time'] ?? $last['offset'] ?? 0 );

	if ( isset( $last['end'] ) && is_numeric( $last['end'] ) && ! isset( $last['duration'] ) && ! isset( $last['dur'] ) ) {
		return (int) ceil( (float) $last['end'] );
	}

	$dur = (float) ( $last['duration'] ?? $last['dur'] ?? 0 );

	return (int) ceil( $start + $dur );
}

/**
 * Runtime of one tutorial in seconds. Request-cached.
 *
 * @param int $post_id Tutorial post ID.
 * @return int
 */
function lp_tutorial_runtime_seconds( int $post_id ): int {
	static $cache = array();

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$secs = 0;

	if ( function_exists( 'get_field' ) ) {
		$raw = get_field( 'video_youtube_data', $post_id );
		if ( is_string( $raw ) && '' !== $raw ) {
			$data = json_decode( $raw, true );
			if ( is_array( $data ) ) {
				$details = isset( $data['contentDetails'] ) && is_array( $data['contentDetails'] )
					? $data['contentDetails']
					: $data;

				if ( isset( $details['duration_secs'] ) && is_numeric( $details['duration_secs'] ) ) {
					$secs = (int) $details['duration_secs'];
				} elseif ( isset( $data['duration_secs'] ) && is_numeric( $data['duration_secs'] ) ) {
					$secs = (int) $data['duration_secs'];
				} elseif ( ! empty( $details['duration'] ) && is_string( $details['duration'] ) ) {
					$secs = lp_parse_iso8601_duration( $details['duration'] );
				}
			}
		}

		if ( $secs <= 0 ) {
			$secs = lp_transcript_runtime_seconds( get_field( 'video_transcript_json', $post_id ) );
		}
	}

	$cache[ $post_id ] = max( 0, $secs );

	return $cache[ $post_id ];
}

/**
 * Card clock: M:SS or H:MM:SS. Empty when runtime is 0.
 *
 * @param int $secs Seconds.
 * @return string
 */
function lp_format_runtime_clock( int $secs ): string {
	if ( $secs <= 0 ) {
		return '';
	}

	$hours   = intdiv( $secs, 3600 );
	$minutes = intdiv( $secs % 3600, 60 );
	$remain  = $secs % 60;

	if ( $hours > 0 ) {
		return sprintf( '%d:%02d:%02d', $hours, $minutes, $remain );
	}

	return sprintf( '%d:%02d', $minutes, $remain );
}

/**
 * Series total: "N MIN" (floor). Empty when under a minute.
 *
 * @param int $secs Seconds.
 * @return string
 */
function lp_format_runtime_minutes( int $secs ): string {
	$minutes = intdiv( max( 0, $secs ), 60 );

	if ( $minutes <= 0 ) {
		return '';
	}

	return sprintf( '%d MIN', $minutes );
}

/**
 * Split `series_label` ("SERIES 01 · BEGINNER") into number + level.
 *
 * @param string $series_label ACF series_label.
 * @param int    $index        1-based list order fallback.
 * @return array{0:string,1:string} Series number, level (level may be '').
 */
function lp_series_label_parts( string $series_label, int $index ): array {
	$series_no = sprintf( 'SERIES %02d', $index );
	$level     = '';
	$raw       = trim( $series_label );

	if ( '' === $raw ) {
		return array( $series_no, $level );
	}

	if ( str_contains( $raw, '·' ) ) {
		$bits      = array_map( 'trim', explode( '·', $raw, 2 ) );
		$series_no = '' !== $bits[0] ? $bits[0] : $series_no;
		$level     = $bits[1] ?? '';
	} elseif ( preg_match( '/^SERIES\s+\d+/i', $raw ) ) {
		$series_no = $raw;
	} else {
		$level = $raw;
	}

	return array( $series_no, $level );
}

/**
 * Comma-separated series tags → uppercase labels.
 *
 * @param string $raw ACF tags value.
 * @return string[]
 */
function lp_series_tag_list( string $raw ): array {
	$out = array();
	foreach ( explode( ',', $raw ) as $tag ) {
		$tag = strtoupper( trim( $tag ) );
		if ( '' !== $tag ) {
			$out[] = $tag;
		}
	}

	return $out;
}

/**
 * First `tutorial-tag` slug: tutorial, challenge, or demonstration.
 *
 * @param WP_Post|int $post Tutorial.
 */
function lp_tutorial_tag_slug( $post ): string {
	$id    = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	$terms = get_the_terms( $id, 'tutorial-tag' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return '';
	}

	return strtolower( (string) $terms[0]->slug );
}

/**
 * Curriculum order number for a tutorial or challenge.
 *
 * Challenges use ACF `award_level`. How-to tutorials use `order_position`.
 * Demonstrations have no order — returns null.
 *
 * @param WP_Post|int $post Tutorial.
 */
function lp_tutorial_order_number( $post ): ?int {
	$id   = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	$slug = lp_tutorial_tag_slug( $id );

	if ( 'demonstration' === $slug ) {
		return null;
	}

	$key = 'challenge' === $slug ? 'award_level' : 'order_position';
	$raw = get_post_meta( $id, $key, true );
	if ( '' === $raw || false === $raw || ! is_numeric( $raw ) ) {
		return null;
	}

	return (int) $raw;
}

/**
 * Zero-padded order label, or empty when the video has no order.
 *
 * @param WP_Post|int $post Tutorial.
 */
function lp_tutorial_order_label( $post ): string {
	$number = lp_tutorial_order_number( $post );

	return null === $number ? '' : sprintf( '%02d', $number );
}

/**
 * Child tutorial-category name, else parent, for natural sort.
 *
 * @param WP_Post $post Tutorial.
 */
function lp_tutorial_sort_category_name( WP_Post $post ): string {
	$terms = get_the_terms( $post, 'tutorial-category' );
	$terms = is_array( $terms ) ? $terms : array();

	$child  = '';
	$parent = '';
	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			if ( '' === $child || strcasecmp( $term->name, $child ) < 0 ) {
				$child = $term->name;
			}
		} elseif ( '' === $parent || strcasecmp( $term->name, $parent ) < 0 ) {
			$parent = $term->name;
		}
	}

	return '' !== $child ? $child : $parent;
}

/**
 * First lp_series term name, for natural sort.
 *
 * @param WP_Post $post Tutorial.
 */
function lp_tutorial_sort_series_name( WP_Post $post ): string {
	$terms = get_the_terms( $post, 'lp_series' );
	if ( ! is_array( $terms ) || ! $terms ) {
		return '';
	}

	$names = wp_list_pluck( $terms, 'name' );
	natcasesort( $names );

	return (string) reset( $names );
}

/**
 * Category → series → order number → title.
 *
 * @param WP_Post $a Tutorial.
 * @param WP_Post $b Tutorial.
 */
function lp_tutorial_natural_compare( WP_Post $a, WP_Post $b ): int {
	$cmp = strcasecmp( lp_tutorial_sort_category_name( $a ), lp_tutorial_sort_category_name( $b ) );
	if ( 0 !== $cmp ) {
		return $cmp;
	}

	$cmp = strcasecmp( lp_tutorial_sort_series_name( $a ), lp_tutorial_sort_series_name( $b ) );
	if ( 0 !== $cmp ) {
		return $cmp;
	}

	$na = lp_tutorial_order_number( $a );
	$nb = lp_tutorial_order_number( $b );
	$ia = null === $na ? 10000 : $na;
	$ib = null === $nb ? 10000 : $nb;
	if ( $ia !== $ib ) {
		return $ia <=> $ib;
	}

	return strcasecmp( get_the_title( $a ), get_the_title( $b ) );
}

/**
 * Flatten one tutorial into video-card args.
 *
 * @param WP_Post $post    Tutorial.
 * @param string  $variant full|compact|lesson.
 * @param int     $index   Unused; order comes from award_level / order_position.
 * @return array<string,mixed>
 */
function lp_video_card_args_from_tutorial( WP_Post $post, string $variant = 'full', int $index = 0 ): array {
	$secs  = lp_tutorial_runtime_seconds( (int) $post->ID );
	$clock = lp_format_runtime_clock( $secs );
	$terms = get_the_terms( $post, 'tutorial-category' );
	$terms = is_array( $terms ) ? $terms : array();

	$child  = null;
	$parent = null;
	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			$child = $term;
			break;
		}
	}
	foreach ( $terms as $term ) {
		if ( ! $term->parent ) {
			$parent = $term;
			break;
		}
	}

	$thumb         = get_post_thumbnail_id( $post ) ?: 0;
	$is_new        = ( time() - get_post_time( 'U', true, $post ) ) <= ( 30 * DAY_IN_SECONDS );
	$kicker_child  = $child ? strtoupper( $child->name ) : '';
	$kicker_parent = $parent ? strtoupper( $parent->name ) : '';
	$move          = '' !== $kicker_child ? $kicker_child : $kicker_parent;
	$order_label   = lp_tutorial_order_label( $post );

	$glyph_id = lp_tutorial_category_glyph( $child ?: $parent );

	if ( 'lesson' === $variant ) {
		return array(
			'variant'  => 'lesson',
			'image_id' => $thumb,
			'index'    => $order_label,
			'kicker'   => $move,
			'glyph_id' => $glyph_id,
			'title'    => get_the_title( $post ),
			'duration' => $clock,
			'status'   => 'WATCH',
			'href'     => (string) get_permalink( $post ),
		);
	}

	if ( 'compact' === $variant ) {
		$foot = 'Lesson';
		if ( '' !== $clock ) {
			$foot .= ' · ' . $clock;
		}

        return array(
			'variant'    => 'compact',
			'image_id'   => $thumb,
			'kicker'     => '' !== $kicker_child ? $kicker_child : $kicker_parent,
			'glyph_id'   => $glyph_id,
			'meta'       => $clock,
			'title'      => get_the_title( $post ),
			'foot_label' => $foot,
			'href'       => (string) get_permalink( $post ),
		);
	}

	$move = $child ? $child->name : ( $parent ? $parent->name : '' );
	$meta = strtoupper( $move );
	if ( '' !== $order_label ) {
		$meta = '' !== $meta ? $order_label . ' · ' . $meta : $order_label;
	}

	return array(
		'variant'     => 'full',
		'image_id'    => $thumb,
		'kicker'      => $kicker_parent,
		'glyph_id'    => $glyph_id,
		'meta'        => $meta,
		'title'       => get_the_title( $post ),
		'note'        => get_the_excerpt( $post ),
		'duration'    => $clock,
		'badge_label' => 'Lesson',
		'flag'        => $is_new ? 'NEW' : '',
		'cta_label'   => 'Watch lesson',
		'cta_href'    => (string) get_permalink( $post ),
	);
}

/**
 * Parent tutorial-category terms for the archive Category dropdown.
 *
 * @return WP_Term[]
 */
function lp_tutorial_parent_categories(): array {
	$terms = get_terms(
		array(
			'taxonomy'   => 'tutorial-category',
			'parent'     => 0,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	return is_array( $terms ) ? $terms : array();
}

/**
 * Select options for the tutorials Category filter.
 *
 * Parents first, each followed by its children. A child is selectable on
 * its own (the archive tax_query matches the term slug; parents still
 * roll up via include_children).
 *
 * @return array<int,array{value:string,label:string}>
 */
function lp_tutorial_category_filter_options(): array {
	$out = array(
		array(
			'value' => '',
			'label' => __( 'All categories', 'londonparkour_v8' ),
		),
	);

	$terms = get_terms(
		array(
			'taxonomy'   => 'tutorial-category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);
	if ( ! is_array( $terms ) ) {
		return $out;
	}

	$parents            = array();
	$children_by_parent = array();
	foreach ( $terms as $term ) {
		if ( $term->parent ) {
			$children_by_parent[ (int) $term->parent ][] = $term;
		} else {
			$parents[] = $term;
		}
	}

	foreach ( $parents as $parent ) {
		$out[] = array(
			'value' => $parent->slug,
			'label' => $parent->name,
		);
		foreach ( $children_by_parent[ (int) $parent->term_id ] ?? array() as $child ) {
			$out[] = array(
				'value' => $child->slug,
				'label' => '— ' . $child->name,
			);
		}
	}

	return $out;
}

/**
 * Select options for the tutorials Series filter.
 *
 * @return array<int,array{value:string,label:string}>
 */
function lp_tutorial_series_filter_options(): array {
	$out = array(
		array(
			'value' => '',
			'label' => __( 'All series', 'londonparkour_v8' ),
		),
	);
	foreach ( lp_series_terms_nonempty() as $term ) {
		$out[] = array(
			'value' => $term->slug,
			'label' => $term->name,
		);
	}

	return $out;
}

/**
 * Select options for the tutorials Tag filter.
 *
 * Challenge, Demonstration, Tutorial — the three `tutorial-tag` terms.
 *
 * @return array<int,array{value:string,label:string}>
 */
function lp_tutorial_tag_filter_options(): array {
	$out = array(
		array(
			'value' => '',
			'label' => __( 'All tags', 'londonparkour_v8' ),
		),
	);

	foreach ( array( 'challenge', 'demonstration', 'tutorial' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'tutorial-tag' );
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$out[] = array(
			'value' => $term->slug,
			'label' => $term->name,
		);
	}

	return $out;
}

/**
 * Sprite id for a tutorial-category term (`svg_glyph` ACF), or empty.
 *
 * Only `glyph-*` ids are returned so a bad term meta value cannot point
 * the sprite `<use>` at an arbitrary fragment. A child term with no glyph
 * inherits the parent's.
 *
 * @param WP_Term|null $term Tutorial-category term.
 * @return string
 */
function lp_tutorial_category_glyph( ?WP_Term $term ): string {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}

	$id = '';
	if ( function_exists( 'get_field' ) ) {
		$id = (string) get_field( 'svg_glyph', $term );
	}
	if ( '' === $id ) {
		$id = (string) get_term_meta( (int) $term->term_id, 'svg_glyph', true );
	}

	$id = sanitize_key( $id );
	if ( str_starts_with( $id, 'glyph-' ) ) {
		return $id;
	}

	if ( $term->parent ) {
		$parent = get_term( (int) $term->parent, 'tutorial-category' );
		return lp_tutorial_category_glyph( $parent instanceof WP_Term ? $parent : null );
	}

	return '';
}

/**
 * Group tutorials into category shelves (series-detail and the category board).
 *
 * One shelf per child tutorial-category. Tutorials tagged parent+child (the
 * usual case) only go on the child shelf. Then leftover parent shelves for
 * tutorials with a parent and no child. Then Uncategorised.
 *
 * A tutorial in two children appears on two shelves.
 *
 * @param WP_Post[] $posts Tutorials to group.
 * @return array<int,array{title:string,glyph_id:string,meta:string,posts:WP_Post[]}>
 */
function lp_series_category_shelves( array $posts ): array {
	$child_shelves  = array();
	$parent_shelves = array();
	$uncategorised  = array();

	foreach ( $posts as $post ) {
		$terms = get_the_terms( $post, 'tutorial-category' );
		$terms = is_array( $terms ) ? $terms : array();

		$children = array();
		$parents  = array();
		foreach ( $terms as $term ) {
			if ( $term->parent ) {
				$children[] = $term;
			} else {
				$parents[] = $term;
			}
		}

		if ( $children ) {
			foreach ( $children as $child ) {
				$id = (int) $child->term_id;
				if ( ! isset( $child_shelves[ $id ] ) ) {
					$child_shelves[ $id ] = array(
						'term'  => $child,
						'posts' => array(),
					);
				}
				$child_shelves[ $id ]['posts'][] = $post;
			}
			continue;
		}

		if ( $parents ) {
			foreach ( $parents as $parent ) {
				$id = (int) $parent->term_id;
				if ( ! isset( $parent_shelves[ $id ] ) ) {
					$parent_shelves[ $id ] = array(
						'term'  => $parent,
						'posts' => array(),
					);
				}
				$parent_shelves[ $id ]['posts'][] = $post;
			}
			continue;
		}

		$uncategorised[] = $post;
	}

	$sort_by_name = static function ( array $a, array $b ): int {
		return strcasecmp( $a['term']->name, $b['term']->name );
	};
	uasort( $child_shelves, $sort_by_name );
	uasort( $parent_shelves, $sort_by_name );

	$buckets = array_merge( array_values( $child_shelves ), array_values( $parent_shelves ) );
	if ( $uncategorised ) {
		$buckets[] = array(
			'term'  => null,
			'posts' => $uncategorised,
		);
	}

	$shelves = array();
	foreach ( $buckets as $bucket ) {
		$shelf_posts = $bucket['posts'];
		usort( $shelf_posts, 'lp_tutorial_natural_compare' );
		$total_secs  = 0;
		foreach ( $shelf_posts as $post ) {
			$total_secs += lp_tutorial_runtime_seconds( (int) $post->ID );
		}

		$count = count( $shelf_posts );
		$mins  = lp_format_runtime_minutes( $total_secs );
		$meta  = sprintf( '%02d LESSONS', $count );
		if ( '' !== $mins ) {
			$meta .= ' · ' . $mins;
		}

		$title = $bucket['term'] instanceof WP_Term
			? $bucket['term']->name
			: __( 'Uncategorised', 'londonparkour_v8' );

		$shelves[] = array(
			'title'    => $title,
			'glyph_id' => lp_tutorial_category_glyph( $bucket['term'] instanceof WP_Term ? $bucket['term'] : null ),
			'meta'     => $meta,
			'posts'    => $shelf_posts,
		);
	}

	return $shelves;
}

/**
 * One horizontal shelf per child tutorial-category, every published tutorial.
 *
 * Same grouping as lp_series_category_shelves(): child move first, parent
 * family only when a tutorial has no child term. Empty shelves omitted.
 * `$category_slug` limits the board to one parent (rolling up children) or
 * one child term. `$kind_slugs` limits by `tutorial-tag` (tutorial /
 * challenge / demonstration). Empty kinds returns no shelves.
 *
 * @param string     $category_slug `tutorial-category` slug, or empty for all.
 * @param string[]|null $kind_slugs `tutorial-tag` slugs. Null = every kind.
 *                                  Empty array returns no shelves.
 * @return array<int,array{title:string,glyph_id:string,meta:string,posts:WP_Post[]}>
 */
function lp_category_board_shelves( string $category_slug = '', ?array $kind_slugs = null ): array {
	$filter_kinds = null !== $kind_slugs;
	if ( $filter_kinds ) {
		$kind_slugs = array_values( array_intersect( lp_tutorial_kind_slugs(), $kind_slugs ) );
		if ( array() === $kind_slugs ) {
			return array();
		}
	}

	$query_args = array(
		'post_type'              => 'lp_tutorial',
		'post_status'            => 'publish',
		'posts_per_page'         => -1,
		'lp_natural_order'       => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	$tax = array();

	if ( '' !== $category_slug ) {
		$tax[] = array(
			'taxonomy'         => 'tutorial-category',
			'field'            => 'slug',
			'terms'            => $category_slug,
			'include_children' => true,
		);
	}

	if ( $filter_kinds ) {
		$tax[] = array(
			'taxonomy' => 'tutorial-tag',
			'field'    => 'slug',
			'terms'    => $kind_slugs,
		);
	}

	if ( $tax ) {
		if ( count( $tax ) > 1 ) {
			$tax = array_merge( array( 'relation' => 'AND' ), $tax );
		}
		$query_args['tax_query'] = $tax; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
	}

	$query = new WP_Query( $query_args );

	$posts = $query->posts;
	if ( $posts ) {
		update_post_thumbnail_cache( $query );
	}

	return lp_series_category_shelves( $posts );
}

/**
 * Sum runtime across a list of tutorials.
 *
 * @param WP_Post[] $posts Tutorials.
 * @return int
 */
function lp_tutorials_total_seconds( array $posts ): int {
	$total = 0;
	foreach ( $posts as $post ) {
		$total += lp_tutorial_runtime_seconds( (int) $post->ID );
	}

	return $total;
}

/**
 * Coerce an ACF taxonomy value (id, WP_Term, or array) to a term ID.
 *
 * @param mixed $raw Field value.
 * @return int
 */
function lp_tutorials_term_id( $raw ): int {
	if ( $raw instanceof WP_Term ) {
		return (int) $raw->term_id;
	}
	if ( is_array( $raw ) ) {
		if ( isset( $raw['term_id'] ) ) {
			return (int) $raw['term_id'];
		}
		if ( isset( $raw[0] ) ) {
			return lp_tutorials_term_id( $raw[0] );
		}
	}

	return (int) $raw;
}

/**
 * Series to feature on the homepage board: explicit pick, else tagged
 * START HERE, else the first nonempty series.
 *
 * @param int $preferred Preferred lp_series term ID.
 * @return int Term ID, or 0.
 */
function lp_tutorials_featured_series_id( int $preferred = 0 ): int {
	if ( $preferred ) {
		$term = get_term( $preferred, 'lp_series' );
		if ( $term instanceof WP_Term ) {
			return (int) $term->term_id;
		}
	}

	foreach ( lp_series_terms_nonempty() as $term ) {
		$tag = strtoupper( trim( (string) get_field( 'tag', 'term_' . $term->term_id ) ) );
		if ( 'START HERE' === $tag ) {
			return (int) $term->term_id;
		}
	}

	$terms = lp_series_terms_nonempty();

	return $terms ? (int) $terms[0]->term_id : 0;
}

/**
 * Board meta line from live CPT counts, e.g. "12 SERIES · 840 EPISODES".
 */
function lp_tutorials_board_meta(): string {
	$series   = count( lp_series_terms_nonempty() );
	$episodes = (int) ( wp_count_posts( 'lp_tutorial' )->publish ?? 0 );

	return sprintf( '%d SERIES · %d EPISODES', $series, $episodes );
}

/**
 * Shared series fields + live tutorial aggregates for homepage projection.
 *
 * @param int $term_id lp_series term ID.
 * @param int $index   1-based list order, used when series_label has no number.
 * @return array<string,mixed>|null
 */
function lp_series_homepage_record( int $term_id, int $index = 1 ): ?array {
	$term = get_term( $term_id, 'lp_series' );
	if ( ! ( $term instanceof WP_Term ) ) {
		return null;
	}

	$fields = function_exists( 'get_fields' ) ? get_fields( 'term_' . $term_id ) : array();
	$fields = is_array( $fields ) ? $fields : array();

	$lessons = lp_tutorials_in_series( $term_id );
	$count   = count( $lessons );
	if ( $count <= 0 && isset( $fields['episode_count'] ) && '' !== (string) $fields['episode_count'] ) {
		$count = (int) $fields['episode_count'];
	}

	$mins = lp_format_runtime_minutes( lp_tutorials_total_seconds( $lessons ) );
	if ( '' === $mins && ! empty( $fields['duration'] ) ) {
		$mins = strtoupper( trim( (string) $fields['duration'] ) );
	}

	$link = get_term_link( $term );
	$url  = is_wp_error( $link ) ? '' : (string) $link;

	list( $series_no, $level ) = lp_series_label_parts( (string) ( $fields['series_label'] ?? '' ), $index );

	return array(
		'id'         => $term_id,
		'tag'        => (string) ( $fields['tag'] ?? '' ),
		'series'     => '' !== $level ? $series_no . ' · ' . $level : $series_no,
		'title'      => $term->name,
		'logline'    => (string) ( $fields['logline'] ?? '' ),
		'count'      => $count,
		'mins'       => $mins,
		'coach'      => strtoupper( trim( (string) ( $fields['coach_label'] ?? '' ) ) ),
		'cta_label'  => (string) ( $fields['cta_label'] ?? '' ),
		'href'       => $url,
		'poster'     => lp_series_poster_id( $term_id, $fields, $lessons[0] ?? null ),
		'poster_alt' => $term->name,
	);
}

/**
 * Project an lp_series term into the homepage featured panel.
 *
 * Episode count, runtime and poster come from the tutorials in the series.
 *
 * @param int $term_id lp_series term ID.
 * @param int $index   1-based list order fallback for SERIES NN.
 * @return array<string,mixed>|null
 */
function lp_series_project_featured( int $term_id, int $index = 1 ): ?array {
	$record = lp_series_homepage_record( $term_id, $index );
	if ( ! $record ) {
		return null;
	}

	$meta = array();
	if ( $record['count'] > 0 ) {
		$meta[] = sprintf( '%d EPISODES', $record['count'] );
	}
	if ( '' !== $record['mins'] ) {
		$meta[] = $record['mins'];
	}
	if ( '' !== $record['coach'] ) {
		$meta[] = $record['coach'];
	}

	$cta = '' !== $record['cta_label'] ? $record['cta_label'] : 'WATCH SERIES';

	return array(
		'tag'        => $record['tag'],
		'series'     => $record['series'],
		'title'      => $record['title'],
		'logline'    => $record['logline'],
		'meta'       => implode( ' · ', $meta ),
		'cta_label'  => $cta,
		'href'       => $record['href'] ? $record['href'] : '#',
		'poster'     => $record['poster'],
		'poster_alt' => $record['poster_alt'],
	);
}

/**
 * Yellow eyebrow for a homepage shelf tile.
 *
 * Promotional `tag` first (NEW, LIBRARY), then the level from `series_label`
 * (BEGINNER), then the first genre tag. Last resort is SERIES so the slot
 * never collapses — the pen puts a tag above every shelf title.
 *
 * @param array<string,mixed> $fields ACF fields for the series term.
 */
function lp_series_shelf_eyebrow( array $fields ): string {
	$tag = strtoupper( trim( (string) ( $fields['tag'] ?? '' ) ) );
	if ( '' !== $tag ) {
		return $tag;
	}

	$label = strtoupper( trim( (string) ( $fields['series_label'] ?? '' ) ) );
	if ( '' !== $label ) {
		if ( str_contains( $label, '·' ) ) {
			$bits = array_map( 'trim', explode( '·', $label, 2 ) );
			if ( '' !== ( $bits[1] ?? '' ) ) {
				return strtoupper( $bits[1] );
			}
			return strtoupper( $bits[0] );
		}
		return $label;
	}

	$tags = lp_series_tag_list( (string) ( $fields['tags'] ?? '' ) );
	if ( $tags ) {
		return $tags[0];
	}

	return 'SERIES';
}

/**
 * Project an lp_series term into a homepage shelf tile.
 *
 * Count and poster come from the tutorials in the series without loading
 * every lesson (Demonstrations is ~228).
 *
 * @param int $term_id lp_series term ID.
 * @return array<string,mixed>|null
 */
function lp_series_project_shelf( int $term_id ): ?array {
	$term = get_term( $term_id, 'lp_series' );
	if ( ! ( $term instanceof WP_Term ) ) {
		return null;
	}

	$fields = function_exists( 'get_fields' ) ? get_fields( 'term_' . $term_id ) : array();
	$fields = is_array( $fields ) ? $fields : array();

	$count = lp_series_published_count( $term_id );
	if ( $count <= 0 && isset( $fields['episode_count'] ) && '' !== (string) $fields['episode_count'] ) {
		$count = (int) $fields['episode_count'];
	}

	$link = get_term_link( $term );
	$url  = is_wp_error( $link ) ? '' : (string) $link;

	return array(
		'tag'        => lp_series_shelf_eyebrow( $fields ),
		'title'      => $term->name,
		'episodes'   => $count > 0 ? $count . ' EPS' : '',
		'href'       => $url ? $url : '#',
		'poster'     => lp_series_poster_id( $term_id, $fields ),
		'poster_alt' => $term->name,
	);
}

/**
 * Map `/tutorials/series` and `/tutorials/category` onto the seeded pages.
 *
 * The tutorial CPT archive owns `/tutorials/`, so without a top rewrite a
 * tutorial slug of `series` or `category` (or the archive's rewrite) would
 * 404 those paths.
 */
function lp_tutorials_series_rewrite(): void {
	add_rewrite_rule( '^tutorials/series/?$', 'index.php?pagename=tutorials-series', 'top' );
	add_rewrite_rule( '^tutorials/category/?$', 'index.php?pagename=tutorials-category', 'top' );
}
add_action( 'init', 'lp_tutorials_series_rewrite', 10 );

/**
 * Flush rewrites once after the series/category view rules are registered.
 */
function lp_tutorials_series_maybe_flush(): void {
	$flag = 'lp_tutorials_view_rewrite_v1';
	if ( get_option( $flag ) ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( $flag, 1, true );
}
add_action( 'init', 'lp_tutorials_series_maybe_flush', 99 );
