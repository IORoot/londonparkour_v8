<?php
/**
 * Post → partial projections shared by more than one template.
 *
 * DATA shaping only — the markup these feed lives under parts/. A projection
 * with a single caller stays in that caller (search.php's row-meta closure);
 * anything here has at least two.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Flatten a post into the shape parts/components/blog-card.php reads.
 *
 * Everything comes from core except `read_time`, the one value WordPress has
 * no native equivalent for — see the `group_lp_post` note in
 * app/setup/acf-groups.php. The two fallbacks are BlogIndex's own source
 * defaults, kept so a post with no category or no author still renders the
 * design rather than a gap.
 *
 * @param WP_Post $lp_post The post.
 * @return array
 */
function lp_post_card_args( WP_Post $lp_post ): array {
	$lp_author = get_the_author_meta( 'display_name', (int) $lp_post->post_author );

	return array(
		'category'  => lp_post_category_label( $lp_post ),
		'read_time' => lp_post_read_time( $lp_post ),
		'title'     => get_the_title( $lp_post ),
		'excerpt'   => get_the_excerpt( $lp_post ),
		'author'    => $lp_author ?: 'Andy Pearson',
		'date'      => get_the_date( 'M j, Y', $lp_post ),
		'date_meta' => strtoupper( get_the_date( 'j M Y', $lp_post ) ),
		'href'      => get_permalink( $lp_post ),
		'image_id'  => get_post_thumbnail_id( $lp_post ) ?: 0,
	);
}

/**
 * The `blog` CPT (v7 import) uses `blog-category`. Native `post` uses `category`.
 *
 * @param WP_Post $lp_post The post.
 * @return string Uppercased category label.
 */
function lp_post_category_label( WP_Post $lp_post ): string {
	$lp_taxonomy = ( 'blog' === $lp_post->post_type && taxonomy_exists( 'blog-category' ) )
		? 'blog-category'
		: 'category';
	$lp_terms    = get_the_terms( $lp_post->ID, $lp_taxonomy );
	if ( $lp_terms && ! is_wp_error( $lp_terms ) ) {
		return strtoupper( $lp_terms[0]->name );
	}

	return 'PROJECT';
}

/**
 * ACF `read_time` when set; otherwise a word-count estimate so imported
 * `blog` posts still print a meta line.
 *
 * @param WP_Post $lp_post The post.
 * @return string e.g. "3 MIN READ".
 */
function lp_post_read_time( WP_Post $lp_post ): string {
	$lp_acf = function_exists( 'get_field' ) ? (string) get_field( 'read_time', $lp_post->ID ) : '';
	if ( '' !== $lp_acf ) {
		return $lp_acf;
	}

	$lp_words = str_word_count( wp_strip_all_tags( (string) $lp_post->post_content ) );
	$lp_mins  = max( 1, (int) round( $lp_words / 200 ) );

	return $lp_mins . ' MIN READ';
}

/**
 * Published `blog` CPT posts when that type exists, otherwise native `post`.
 *
 * The posts page (`home.php`) uses the main query via lp_filter_blog_home()
 * so it can paginate. This helper is a one-off fetch with no found rows.
 *
 * @param int $lp_count How many to return (lead + recent grid).
 * @return WP_Post[]
 */
function lp_blog_listing_posts( int $lp_count = 4 ): array {
	$lp_type = post_type_exists( 'blog' ) ? 'blog' : 'post';
	$lp_q    = new WP_Query(
		array(
			'post_type'           => $lp_type,
			'post_status'         => 'publish',
			'posts_per_page'      => $lp_count,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	return $lp_q->posts;
}

/**
 * Split a markdown (or plain) article into intro blocks + ## sections.
 *
 * v7 blog bodies are markdown. Structured ACF repeaters, when present, take
 * precedence in the template — this is the imported-content path.
 *
 * Only ATX `##` headings become TOC sections. `###` and below stay inside the
 * current section so a long guide does not flatten every subhead into the rail.
 *
 * @param string $lp_content Raw post_content.
 * @return array{intro: array<int, array>, sections: array<int, array{id: string, heading: string, blocks: array}>}
 */
function lp_blog_parse_markdown( string $lp_content ): array {
	$lp_content = str_replace( array( "\r\n", "\r" ), "\n", trim( $lp_content ) );
	$lp_content = html_entity_decode( $lp_content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$lp_content = (string) preg_replace( '/\A#\s+[^\n]+\n+/', '', $lp_content, 1 );

	$lp_parts = preg_split( '/^##\s+(.+)$/m', $lp_content, -1, PREG_SPLIT_DELIM_CAPTURE );
	if ( ! is_array( $lp_parts ) ) {
		$lp_parts = array( $lp_content );
	}

	$lp_used     = array();
	$lp_intro    = lp_blog_parse_blocks( (string) ( $lp_parts[0] ?? '' ), $lp_used );
	$lp_sections = array();
	$lp_count    = count( $lp_parts );

	for ( $lp_i = 1; $lp_i < $lp_count; $lp_i += 2 ) {
		$lp_heading = trim( (string) $lp_parts[ $lp_i ] );
		if ( '' === $lp_heading ) {
			continue;
		}
		$lp_sections[] = array(
			'id'      => lp_blog_heading_id( $lp_heading, $lp_used ),
			'heading' => $lp_heading,
			'blocks'  => lp_blog_parse_blocks( (string) ( $lp_parts[ $lp_i + 1 ] ?? '' ), $lp_used ),
		);
	}

	return array(
		'intro'    => $lp_intro,
		'sections' => $lp_sections,
	);
}

/**
 * Stable, unique fragment id for a heading.
 *
 * @param string   $lp_heading Heading text (entities already decoded).
 * @param string[] $lp_used    Ids already emitted, keyed by id.
 */
function lp_blog_heading_id( string $lp_heading, array &$lp_used ): string {
	$lp_base = sanitize_title( wp_strip_all_tags( $lp_heading ) );
	if ( '' === $lp_base ) {
		$lp_base = 'section';
	}

	$lp_id = $lp_base;
	$lp_n  = 2;
	while ( isset( $lp_used[ $lp_id ] ) ) {
		$lp_id = $lp_base . '-' . $lp_n;
		++$lp_n;
	}
	$lp_used[ $lp_id ] = true;

	return $lp_id;
}

/**
 * Classify a markdown body into typed blocks (paragraph, heading, video, quote, list, image).
 *
 * @param string   $lp_text Markdown fragment (one section or the intro).
 * @param string[] $lp_used Heading ids already used, for inner ### anchors.
 * @return array<int, array>
 */
function lp_blog_parse_blocks( string $lp_text, array &$lp_used = array() ): array {
	$lp_text = trim( $lp_text );
	if ( '' === $lp_text ) {
		return array();
	}

	$lp_lines  = explode( "\n", $lp_text );
	$lp_n      = count( $lp_lines );
	$lp_i      = 0;
	$lp_blocks = array();

	while ( $lp_i < $lp_n ) {
		$lp_trim = trim( $lp_lines[ $lp_i ] );
		if ( '' === $lp_trim ) {
			++$lp_i;
			continue;
		}

		if ( preg_match( '/^(#{3,6})\s+(.+)$/', $lp_trim, $lp_m ) ) {
			$lp_heading    = trim( $lp_m[2] );
			$lp_blocks[]   = array(
				'type'    => 'heading',
				'level'   => strlen( $lp_m[1] ),
				'text'    => $lp_heading,
				'id'      => lp_blog_heading_id( $lp_heading, $lp_used ),
			);
			++$lp_i;
			continue;
		}

		if ( str_starts_with( $lp_trim, '>' ) ) {
			$lp_quoted = array();
			while ( $lp_i < $lp_n ) {
				$lp_line = trim( $lp_lines[ $lp_i ] );
				if ( '' === $lp_line ) {
					$lp_j = $lp_i + 1;
					while ( $lp_j < $lp_n && '' === trim( $lp_lines[ $lp_j ] ) ) {
						++$lp_j;
					}
					if ( $lp_j < $lp_n && str_starts_with( trim( $lp_lines[ $lp_j ] ), '>' ) ) {
						$lp_quoted[] = '';
						++$lp_i;
						continue;
					}
					break;
				}
				if ( ! str_starts_with( $lp_line, '>' ) ) {
					break;
				}
				$lp_quoted[] = (string) preg_replace( '/^>\s?/', '', $lp_line, 1 );
				++$lp_i;
			}
			$lp_inner = lp_blog_parse_blocks( implode( "\n", $lp_quoted ), $lp_used );
			if ( $lp_inner ) {
				$lp_blocks[] = array(
					'type'   => 'quote',
					'blocks' => $lp_inner,
				);
			}
			continue;
		}

		if ( preg_match( '/^[-*]\s+/', $lp_trim ) ) {
			$lp_items = array();
			while ( $lp_i < $lp_n ) {
				$lp_line = $lp_lines[ $lp_i ];
				$lp_item = trim( $lp_line );
				if ( preg_match( '/^[-*]\s+(.*)$/', $lp_item, $lp_m ) ) {
					$lp_items[] = $lp_m[1];
					++$lp_i;
					continue;
				}
				if ( $lp_items && preg_match( '/^\s{2,}\S/', $lp_line ) && ! preg_match( '/^[-*]\s+/', $lp_item ) && ! preg_match( '/^\d+[.)]\s+/', $lp_item ) ) {
					$lp_items[ count( $lp_items ) - 1 ] .= ' ' . $lp_item;
					++$lp_i;
					continue;
				}
				break;
			}
			$lp_blocks[] = array(
				'type'  => 'ul',
				'items' => $lp_items,
			);
			continue;
		}

		if ( preg_match( '/^\d+[.)]\s+/', $lp_trim ) ) {
			$lp_items = array();
			while ( $lp_i < $lp_n ) {
				$lp_line = $lp_lines[ $lp_i ];
				$lp_item = trim( $lp_line );
				if ( preg_match( '/^\d+[.)]\s+(.*)$/', $lp_item, $lp_m ) ) {
					$lp_items[] = $lp_m[1];
					++$lp_i;
					continue;
				}
				if ( $lp_items && preg_match( '/^\s{2,}\S/', $lp_line ) && ! preg_match( '/^[-*]\s+/', $lp_item ) && ! preg_match( '/^\d+[.)]\s+/', $lp_item ) ) {
					$lp_items[ count( $lp_items ) - 1 ] .= ' ' . $lp_item;
					++$lp_i;
					continue;
				}
				break;
			}
			$lp_blocks[] = array(
				'type'  => 'ol',
				'items' => $lp_items,
			);
			continue;
		}

		if ( preg_match( '/^(?:-{3,}|\*{3,}|_{3,})$/', $lp_trim ) ) {
			$lp_blocks[] = array( 'type' => 'hr' );
			++$lp_i;
			continue;
		}

		$lp_para_lines = array();
		while ( $lp_i < $lp_n ) {
			$lp_line = trim( $lp_lines[ $lp_i ] );
			if ( '' === $lp_line ) {
				break;
			}
			if ( preg_match( '/^#{3,6}\s+/', $lp_line ) || str_starts_with( $lp_line, '>' ) ) {
				break;
			}
			if ( preg_match( '/^[-*]\s+/', $lp_line ) || preg_match( '/^\d+[.)]\s+/', $lp_line ) ) {
				break;
			}
			if ( preg_match( '/^(?:-{3,}|\*{3,}|_{3,})$/', $lp_line ) ) {
				break;
			}
			$lp_para_lines[] = $lp_line;
			++$lp_i;
		}

		$lp_para = trim( implode( "\n", $lp_para_lines ) );
		if ( '' === $lp_para ) {
			continue;
		}

		$lp_video = lp_blog_video_block_from_markdown( $lp_para );
		if ( $lp_video ) {
			$lp_blocks[] = $lp_video;
			continue;
		}

		$lp_image = lp_blog_image_block_from_markdown( $lp_para );
		if ( $lp_image ) {
			$lp_blocks[] = $lp_image;
			continue;
		}

		$lp_blocks[] = array(
			'type' => 'p',
			'text' => $lp_para,
		);
	}

	return $lp_blocks;
}

/**
 * A standalone markdown link (or bare URL) that should render as an embed.
 *
 * @param string $lp_text One paragraph.
 * @return array|null
 */
function lp_blog_video_block_from_markdown( string $lp_text ): ?array {
	$lp_text  = preg_replace( '/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', trim( $lp_text ) ) ?? trim( $lp_text );
	$lp_url   = '';
	$lp_label = '';

	if ( preg_match( '/^\[([^\]]+)\]\((https?:[^)\s]+)(?:\s+"[^"]*")?\)$/', $lp_text, $lp_m ) ) {
		$lp_label = trim( $lp_m[1] );
		$lp_url   = trim( $lp_m[2] );
	} elseif ( preg_match( '/^(https?:\/\/\S+)$/', $lp_text, $lp_m ) ) {
		$lp_url = $lp_m[1];
	} else {
		return null;
	}

	$lp_yt = function_exists( 'lp_youtube_id_from_url' ) ? lp_youtube_id_from_url( $lp_url ) : '';
	if ( '' !== $lp_yt ) {
		return array(
			'type'     => 'video',
			'provider' => 'youtube',
			'id'       => $lp_yt,
			'title'    => $lp_label,
		);
	}

	$lp_list = function_exists( 'lp_youtube_playlist_id_from_url' ) ? lp_youtube_playlist_id_from_url( $lp_url ) : '';
	if ( '' !== $lp_list ) {
		return array(
			'type'     => 'video',
			'provider' => 'youtube-playlist',
			'id'       => $lp_list,
			'title'    => $lp_label,
		);
	}

	$lp_vm = function_exists( 'lp_vimeo_id_from_url' ) ? lp_vimeo_id_from_url( $lp_url ) : '';
	if ( '' !== $lp_vm ) {
		return array(
			'type'     => 'video',
			'provider' => 'vimeo',
			'id'       => $lp_vm,
			'title'    => $lp_label,
		);
	}

	return null;
}

/**
 * A paragraph that is only `![alt](src)`.
 *
 * @param string $lp_text One paragraph.
 * @return array|null
 */
function lp_blog_image_block_from_markdown( string $lp_text ): ?array {
	$lp_text = trim( $lp_text );
	if ( ! preg_match( '/^!\[([^\]]*)\]\((https?:[^)\s]+)(?:\s+"[^"]*")?\)$/', $lp_text, $lp_m ) ) {
		return null;
	}

	return array(
		'type' => 'img',
		'alt'  => $lp_m[1],
		'src'  => $lp_m[2],
	);
}

/**
 * Whole-literal class strings for markdown article blocks.
 *
 * @param string $lp_key Lookup key.
 */
function lp_blog_markdown_class( string $lp_key ): string {
	$lp_map = array(
		'p'           => 'font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content',
		'p_lead'      => 'font-body text-[16px] leading-[1.75] tracking-[0.1px] text-base-content',
		'heading_3'   => 'font-heading text-[18px] font-semibold tracking-[-0.3px] text-base-content scroll-mt-[24px]',
		'heading_4'   => 'font-heading text-[16px] font-semibold tracking-[-0.3px] text-base-content scroll-mt-[24px]',
		'heading_5'   => 'font-heading text-[16px] font-semibold tracking-[-0.3px] text-base-content scroll-mt-[24px]',
		'quote'       => 'border-l-2 border-accent pl-[24px] flex flex-col gap-[12px]',
		'quote_title' => 'font-label text-[10px] font-semibold uppercase tracking-[1.1px] text-accent m-0',
		'quote_p'     => 'font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content m-0',
		'ul'          => 'list-disc pl-5 m-0 flex flex-col gap-[12px] font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content',
		'ol'          => 'list-decimal pl-5 m-0 flex flex-col gap-[12px] font-body text-[14px] leading-[1.75] tracking-[0.1px] text-base-content',
		'embed'       => 'relative w-full overflow-hidden bg-secondary aspect-video [&>iframe]:absolute [&>iframe]:inset-0 [&>iframe]:size-full m-0',
		'caption'     => 'font-label text-[10px] font-normal tracking-[0.8px] text-base-content/65',
		'figure'      => 'm-0 flex flex-col gap-[12px]',
		'img'         => 'w-full h-auto',
		'hr'          => 'w-full h-px bg-base-300 border-0',
	);

	return $lp_map[ $lp_key ] ?? '';
}

/**
 * Class attribute for a markdown block. Trusted literals — `esc_attr` would
 * encode `[&>iframe]` and miss the compiled Tailwind selector.
 *
 * @param string $lp_key Lookup key.
 */
function lp_blog_markdown_class_attr( string $lp_key ): string {
	$lp_class = lp_blog_markdown_class( $lp_key );
	if ( str_contains( $lp_class, '>' ) || str_contains( $lp_class, '<' ) ) {
		return $lp_class;
	}

	return esc_attr( $lp_class );
}

/**
 * Echo typed markdown blocks. `$lp_lead` is consumed by the first paragraph.
 *
 * @param array $lp_blocks Parse tree from lp_blog_parse_blocks().
 * @param array $lp_args   {
 *   @type bool   $lead          First paragraph uses the standfirst size.
 *   @type string $heading_start Inner ### tag: 'h3' (docs) or 'h4' (blog).
 *   @type bool   $in_quote      Nested quote pass — skip lead, use quote type.
 * }
 */
function lp_blog_render_blocks( array $lp_blocks, array $lp_args = array() ): void {
	$lp_lead          = ! empty( $lp_args['lead'] );
	$lp_heading_start = (string) ( $lp_args['heading_start'] ?? 'h4' );
	$lp_in_quote      = ! empty( $lp_args['in_quote'] );
	$lp_tags          = array( 'h3', 'h4', 'h5', 'h6' );
	if ( ! in_array( $lp_heading_start, $lp_tags, true ) ) {
		$lp_heading_start = 'h4';
	}

	foreach ( $lp_blocks as $lp_block ) {
		$lp_type = (string) ( $lp_block['type'] ?? '' );

		if ( 'p' === $lp_type ) {
			$lp_class = ( $lp_lead && ! $lp_in_quote ) ? lp_blog_markdown_class( 'p_lead' ) : ( $lp_in_quote ? lp_blog_markdown_class( 'quote_p' ) : lp_blog_markdown_class( 'p' ) );
			$lp_lead  = false;
			echo '<p class="' . esc_attr( $lp_class ) . '">' . wp_kses_post( lp_blog_inline_markdown( (string) ( $lp_block['text'] ?? '' ) ) ) . '</p>';
			continue;
		}

		if ( 'heading' === $lp_type ) {
			$lp_level = (int) ( $lp_block['level'] ?? 3 );
			$lp_index = max( 0, $lp_level - 3 );
			$lp_tag   = $lp_tags[ array_search( $lp_heading_start, $lp_tags, true ) + $lp_index ] ?? 'h6';
			if ( ! in_array( $lp_tag, $lp_tags, true ) ) {
				$lp_tag = 'h4';
			}
			$lp_text = (string) ( $lp_block['text'] ?? '' );
			$lp_id   = (string) ( $lp_block['id'] ?? '' );

			if ( $lp_in_quote ) {
				echo '<p class="' . lp_blog_markdown_class_attr( 'quote_title' ) . '">' . esc_html( $lp_text ) . '</p>';
				continue;
			}

			$lp_h_class = lp_blog_markdown_class( $lp_level >= 4 ? 'heading_4' : 'heading_3' );
			echo '<' . $lp_tag . ' id="' . esc_attr( $lp_id ) . '" class="' . esc_attr( $lp_h_class ) . '">' . esc_html( $lp_text ) . '</' . $lp_tag . '>';
			continue;
		}

		if ( 'quote' === $lp_type ) {
			echo '<blockquote class="' . lp_blog_markdown_class_attr( 'quote' ) . '" data-component="blog-annotation">';
			lp_blog_render_blocks(
				(array) ( $lp_block['blocks'] ?? array() ),
				array(
					'lead'          => false,
					'heading_start' => $lp_heading_start,
					'in_quote'      => true,
				)
			);
			echo '</blockquote>';
			continue;
		}

		if ( 'ul' === $lp_type || 'ol' === $lp_type ) {
			$lp_tag = 'ul' === $lp_type ? 'ul' : 'ol';
			echo '<' . $lp_tag . ' class="' . lp_blog_markdown_class_attr( $lp_tag ) . '">';
			foreach ( (array) ( $lp_block['items'] ?? array() ) as $lp_item ) {
				echo '<li>' . wp_kses_post( lp_blog_inline_markdown( (string) $lp_item ) ) . '</li>';
			}
			echo '</' . $lp_tag . '>';
			continue;
		}

		if ( 'video' === $lp_type ) {
			lp_blog_render_video_block( $lp_block );
			continue;
		}

		if ( 'img' === $lp_type ) {
			$lp_src = esc_url( (string) ( $lp_block['src'] ?? '' ), array( 'http', 'https' ) );
			if ( '' === $lp_src ) {
				continue;
			}
			echo '<figure class="' . lp_blog_markdown_class_attr( 'figure' ) . '">';
			echo '<img class="' . lp_blog_markdown_class_attr( 'img' ) . '" src="' . esc_url( $lp_src ) . '" alt="' . esc_attr( (string) ( $lp_block['alt'] ?? '' ) ) . '" loading="lazy">';
			echo '</figure>';
			continue;
		}

		if ( 'hr' === $lp_type ) {
			echo '<hr class="' . lp_blog_markdown_class_attr( 'hr' ) . '">';
		}
	}
}

/**
 * Responsive YouTube / Vimeo iframe. Src hosts are hardcoded, ids are encoded.
 *
 * @param array $lp_block Video block from lp_blog_video_block_from_markdown().
 */
function lp_blog_render_video_block( array $lp_block ): void {
	$lp_provider = (string) ( $lp_block['provider'] ?? '' );
	$lp_id       = (string) ( $lp_block['id'] ?? '' );
	$lp_title    = trim( (string) ( $lp_block['title'] ?? '' ) );
	$lp_src      = '';
	$lp_iframe_title = $lp_title;

	if ( 'youtube' === $lp_provider && preg_match( '/^[A-Za-z0-9_-]{11}$/', $lp_id ) ) {
		$lp_src          = 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $lp_id );
		$lp_iframe_title = ( '' === $lp_title || 0 === strcasecmp( $lp_title, 'YouTube' ) ) ? 'YouTube video' : $lp_title;
	} elseif ( 'youtube-playlist' === $lp_provider && preg_match( '/^[\w-]+$/', $lp_id ) ) {
		$lp_src          = 'https://www.youtube-nocookie.com/embed/videoseries?list=' . rawurlencode( $lp_id );
		$lp_iframe_title = ( '' === $lp_title ) ? 'YouTube playlist' : $lp_title;
	} elseif ( 'vimeo' === $lp_provider && preg_match( '/^\d+$/', $lp_id ) ) {
		$lp_src          = 'https://player.vimeo.com/video/' . rawurlencode( $lp_id );
		$lp_iframe_title = ( '' === $lp_title || str_ends_with( strtolower( $lp_title ), 'on vimeo' ) )
			? ( preg_replace( '/\s+on vimeo$/i', '', $lp_title ) ?: 'Vimeo video' )
			: $lp_title;
	}

	if ( '' === $lp_src ) {
		return;
	}

	$lp_show_caption = ( '' !== $lp_title && 0 !== strcasecmp( $lp_title, 'YouTube' ) );

	echo '<figure class="' . lp_blog_markdown_class_attr( 'figure' ) . '" data-component="blog-embed">';
	echo '<div class="' . lp_blog_markdown_class_attr( 'embed' ) . '">';
	echo '<iframe src="' . esc_url( $lp_src ) . '" title="' . esc_attr( $lp_iframe_title ) . '" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>';
	echo '</div>';
	if ( $lp_show_caption ) {
		echo '<figcaption class="' . lp_blog_markdown_class_attr( 'caption' ) . '">' . esc_html( $lp_title ) . '</figcaption>';
	}
	echo '</figure>';
}

/**
 * @param string $lp_text A markdown block.
 * @return string[] Non-empty paragraphs.
 */
function lp_blog_split_paragraphs( string $lp_text ): array {
	$lp_chunks = preg_split( '/\n\s*\n/', trim( $lp_text ) );
	if ( ! is_array( $lp_chunks ) ) {
		return array();
	}

	$lp_out = array();
	foreach ( $lp_chunks as $lp_chunk ) {
		$lp_chunk = trim( (string) $lp_chunk );
		if ( '' !== $lp_chunk ) {
			$lp_out[] = $lp_chunk;
		}
	}

	return $lp_out;
}

/**
 * Inline markdown → allowed HTML for an article paragraph.
 *
 * @param string $lp_text Escaped-then-marked-up paragraph.
 * @return string
 */
function lp_blog_inline_markdown( string $lp_text ): string {
	$lp_text = html_entity_decode( $lp_text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$lp_text = esc_html( $lp_text );
	$lp_text = (string) preg_replace_callback(
		'/!\[([^\]]*)\]\(([^)]+)\)/',
		static function ( array $lp_m ): string {
			$lp_src = esc_url( html_entity_decode( $lp_m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' ), array( 'http', 'https' ) );
			if ( '' === $lp_src ) {
				return $lp_m[0];
			}
			return '<img class="inline-block max-w-full h-auto" src="' . esc_url( $lp_src ) . '" alt="' . $lp_m[1] . '">';
		},
		$lp_text
	);
	$lp_text = (string) preg_replace_callback(
		'/\[([^\]]+)\]\(([^)]+)\)/',
		static function ( array $lp_m ): string {
			$lp_href = html_entity_decode( $lp_m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			return '<a class="text-accent hover:text-accent/70" href="' . esc_url( $lp_href ) . '">' . $lp_m[1] . '</a>';
		},
		$lp_text
	);
	$lp_text = (string) preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $lp_text );
	$lp_text = (string) preg_replace( '/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/s', '<em>$1</em>', $lp_text );
	$lp_text = (string) preg_replace( '/(?<![A-Za-z0-9])_(?!_)(.+?)(?<!_)_(?![A-Za-z0-9])/s', '<em>$1</em>', $lp_text );

	return nl2br( $lp_text, false );
}

/**
 * Project a query's paging state into parts/components/pagination.php's args.
 *
 * Returns an EMPTY array when there is only one page, so a caller can write
 * `$p = lp_pagination_args(); if ( $p ) { lp_part( …, $p ); }` and needs no
 * second condition.
 *
 * Hrefs come from get_pagenum_link(), which reads the current request — so
 * this suits the MAIN query. A secondary WP_Query would need its own link
 * builder; none of the callers has one.
 *
 * @param WP_Query|null $lp_query Defaults to the main query.
 * @param string        $lp_noun  Plural noun for the count line. Default 'RESULTS'.
 * @return array
 */
function lp_pagination_args( ?WP_Query $lp_query = null, string $lp_noun = 'RESULTS' ): array {
	$lp_query = $lp_query ?? $GLOBALS['wp_query'];
	$lp_total = (int) $lp_query->max_num_pages;

	if ( $lp_total < 2 ) {
		return array();
	}

	$lp_current  = max( 1, (int) $lp_query->get( 'paged' ) );
	$lp_per_page = (int) $lp_query->get( 'posts_per_page' );
	$lp_found    = (int) $lp_query->found_posts;
	$lp_from     = ( ( $lp_current - 1 ) * $lp_per_page ) + 1;
	$lp_to       = min( $lp_current * $lp_per_page, $lp_found );

	// The design draws a short run of page boxes with no ellipsis state.
	// When a real archive exceeds that, window around the current page —
	// prev/next + the count line still cover the full range (PORT-FINDINGS §16).
	$lp_window = 7;
	if ( $lp_total <= $lp_window ) {
		$lp_start = 1;
		$lp_end   = $lp_total;
	} else {
		$lp_half  = (int) floor( $lp_window / 2 );
		$lp_start = max( 1, $lp_current - $lp_half );
		$lp_end   = min( $lp_total, $lp_start + $lp_window - 1 );
		$lp_start = max( 1, $lp_end - $lp_window + 1 );
	}

	$lp_pages = array();
	for ( $lp_i = $lp_start; $lp_i <= $lp_end; $lp_i++ ) {
		$lp_pages[] = array(
			'label'   => (string) $lp_i,
			'href'    => get_pagenum_link( $lp_i ),
			'current' => $lp_i === $lp_current,
		);
	}

	return array(
		'pages' => $lp_pages,
		'prev'  => $lp_current > 1
			? array(
				'label' => '← PREVIOUS',
				'href'  => get_pagenum_link( $lp_current - 1 ),
			)
			: array(),
		'next'  => $lp_current < $lp_total
			? array(
				'label' => 'NEXT →',
				'href'  => get_pagenum_link( $lp_current + 1 ),
			)
			: array(),
		// En dash, not a hyphen — SearchResults.js flags this on `h0BaW`.
		'count' => sprintf( 'SHOWING %02d–%02d OF %d %s', $lp_from, $lp_to, $lp_found, $lp_noun ),
	);
}

/**
 * URL of one of the two Classes view PAGES, by slug.
 *
 * Agenda is the default Classes page at `/classes/`. Map is `/classes-map/`.
 * Class singles stay at `/classes/{slug}`; the CPT listings archive is
 * `/all-classes/` so it does not collide with Agenda. See docs/PORT-FINDINGS.md §21.
 *
 * Falls back to a top-level path if the page has not been seeded yet, so the
 * rail still points somewhere rather than at nothing.
 *
 * @param string $lp_slug Page slug (`classes` or `classes-map`).
 * @return string
 */
function lp_classes_page_url( string $lp_slug ): string {
	$lp_page = get_page_by_path( $lp_slug );

	if ( $lp_page ) {
		return (string) get_permalink( $lp_page );
	}

	return home_url( '/' . $lp_slug . '/' );
}

/**
 * Class-types listings archive (`/all-classes/`).
 *
 * Agenda is `/classes/` via `lp_classes_page_url( 'classes' )`. Map is
 * `lp_classes_page_url( 'classes-map' )`. The nav's Classes item and Find a
 * class both go to the agenda; this listings URL is the drop panel's ALL
 * CLASSES foot. See docs/PORT-FINDINGS.md §21.
 *
 * @return string
 */
function lp_classes_listings_url(): string {
	$lp_type = function_exists( 'lp_class_post_type' ) ? lp_class_post_type() : 'clasbpro_class';
	$lp_link = get_post_type_archive_link( $lp_type );

	if ( is_string( $lp_link ) && '' !== $lp_link ) {
		return $lp_link;
	}

	return home_url( '/all-classes/' );
}

/**
 * Whether a location post is a class site or a map-only training spot.
 * Missing/unknown values default to site so existing records stay class sites.
 *
 * @param int $lp_id Location post ID.
 * @return string site|spot
 */
function lp_location_kind( int $lp_id ): string {
	$lp_kind = (string) get_field( 'location_kind', $lp_id );
	return 'spot' === $lp_kind ? 'spot' : 'site';
}

/**
 * Published lp_location posts filtered by kind.
 *
 * @param string $lp_kind site|spot.
 * @return WP_Post[]
 */
function lp_locations_by_kind( string $lp_kind = 'site' ): array {
	$lp_kind  = 'spot' === $lp_kind ? 'spot' : 'site';
	$lp_posts = get_posts(
		array(
			'post_type'      => 'lp_location',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);

	return array_values(
		array_filter(
			$lp_posts,
			static function ( WP_Post $lp_post ) use ( $lp_kind ): bool {
				return lp_location_kind( (int) $lp_post->ID ) === $lp_kind;
			}
		)
	);
}

/**
 * Join a list in English: "A", "A and B", "A, B and C".
 *
 * @param string[] $lp_items
 */
function lp_join_and( array $lp_items ): string {
	$lp_items = array_values(
		array_filter(
			array_map( 'strval', $lp_items ),
			static function ( string $lp_item ): bool {
				return '' !== $lp_item;
			}
		)
	);
	$lp_n     = count( $lp_items );
	if ( 0 === $lp_n ) {
		return '';
	}
	if ( 1 === $lp_n ) {
		return $lp_items[0];
	}
	if ( 2 === $lp_n ) {
		return $lp_items[0] . ' and ' . $lp_items[1];
	}

	return implode( ', ', array_slice( $lp_items, 0, -1 ) ) . ' and ' . $lp_items[ $lp_n - 1 ];
}

/**
 * UK postcode from a location's meta string, or empty.
 *
 * @param int $lp_id Location post ID.
 */
function lp_location_postcode( int $lp_id ): string {
	$lp_meta = (string) get_field( 'meta', $lp_id );
	if ( preg_match( '/\b([A-Z]{1,2}\d[A-Z\d]?\s*\d[A-Z]{2})\b/i', $lp_meta, $lp_m ) ) {
		return strtoupper( preg_replace( '/\s+/', ' ', $lp_m[1] ) );
	}

	return '';
}

/**
 * Class-site lines for Contact, e.g. "Vauxhall — SW8 1SS".
 *
 * @return string[]
 */
function lp_contact_location_lines(): array {
	$lp_lines = array();
	foreach ( lp_locations_by_kind( 'site' ) as $lp_post ) {
		$lp_code    = lp_location_postcode( (int) $lp_post->ID );
		$lp_lines[] = '' !== $lp_code ? $lp_post->post_title . ' — ' . $lp_code : $lp_post->post_title;
	}

	return $lp_lines;
}

/**
 * Multiline Locations block for Contact Other Ways.
 */
function lp_contact_locations_block(): string {
	return implode( "\n", lp_contact_location_lines() );
}

/**
 * Compact Locations value for the Contact reach aside.
 */
function lp_contact_locations_inline(): string {
	$lp_names = array();
	foreach ( lp_locations_by_kind( 'site' ) as $lp_post ) {
		$lp_names[] = $lp_post->post_title;
	}

	return implode( ' · ', $lp_names );
}

/**
 * Number of published class sites.
 */
function lp_published_site_count(): int {
	return count( lp_locations_by_kind( 'site' ) );
}

/**
 * Spoken count of published class sites, e.g. "three".
 *
 * Falls back to "three" when none are published, matching the live site.
 */
function lp_sites_word( bool $capital = false ): string {
	$n = lp_published_site_count();
	if ( $n < 1 ) {
		$n = 3;
	}

	$words = array(
		1  => 'one',
		2  => 'two',
		3  => 'three',
		4  => 'four',
		5  => 'five',
		6  => 'six',
		7  => 'seven',
		8  => 'eight',
		9  => 'nine',
		10 => 'ten',
	);
	$word = $words[ $n ] ?? (string) $n;

	return $capital ? ucfirst( $word ) : $word;
}

/**
 * Contact FAQ answer for "Where exactly do you train?" from live class sites.
 */
function lp_where_we_train_answer(): string {
	$lp_names = array();
	foreach ( lp_locations_by_kind( 'site' ) as $lp_post ) {
		$lp_names[] = $lp_post->post_title;
	}
	$lp_n = count( $lp_names );
	if ( $lp_n < 1 ) {
		return 'We train at sites across London. Every one is next to a tube or overground station.';
	}

	$lp_lead = lp_sites_word( true ) . ' ' . ( 1 === $lp_n ? 'site' : 'sites' ) . ' across London';

	return $lp_lead . ' — ' . lp_join_and( $lp_names ) . '. Every one is next to a tube or overground station.';
}

/**
 * Street View / maps exit URL for a location.
 *
 * @param int $lp_id Location post ID.
 * @return string
 */
function lp_location_streetview_url( int $lp_id ): string {
	$lp_custom = (string) get_field( 'streetview', $lp_id );
	if ( '' !== trim( $lp_custom ) ) {
		return $lp_custom;
	}

	$lp_lat = (string) get_field( 'latitude', $lp_id );
	$lp_lon = (string) get_field( 'longitude', $lp_id );
	if ( '' === $lp_lat || '' === $lp_lon ) {
		return '';
	}

	return sprintf(
		'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint=%s,%s',
		rawurlencode( $lp_lat ),
		rawurlencode( $lp_lon )
	);
}

/**
 * OpenStreetMap embed URL for a lat/lon pin (Class Detail meeting map).
 *
 * @param string $lp_lat Latitude.
 * @param string $lp_lon Longitude.
 * @param float  $lp_delta Half-bbox in degrees.
 * @return string Empty when coords are missing / non-numeric.
 */
function lp_osm_embed_url( string $lp_lat, string $lp_lon, float $lp_delta = 0.01 ): string {
	if ( '' === trim( $lp_lat ) || '' === trim( $lp_lon ) || ! is_numeric( $lp_lat ) || ! is_numeric( $lp_lon ) ) {
		return '';
	}

	$lp_la = (float) $lp_lat;
	$lp_lo = (float) $lp_lon;
	$lp_bbox = sprintf(
		'%F,%F,%F,%F',
		$lp_lo - $lp_delta,
		$lp_la - $lp_delta,
		$lp_lo + $lp_delta,
		$lp_la + $lp_delta
	);

	return sprintf(
		'https://www.openstreetmap.org/export/embed.html?bbox=%s&layer=mapnik&marker=%s',
		rawurlencode( $lp_bbox ),
		rawurlencode( sprintf( '%F,%F', $lp_la, $lp_lo ) )
	);
}

/**
 * OpenStreetMap “open in maps” URL for a lat/lon pin.
 *
 * @param string $lp_lat Latitude.
 * @param string $lp_lon Longitude.
 * @return string Empty when coords are missing / non-numeric.
 */
function lp_osm_maps_url( string $lp_lat, string $lp_lon ): string {
	if ( '' === trim( $lp_lat ) || '' === trim( $lp_lon ) || ! is_numeric( $lp_lat ) || ! is_numeric( $lp_lon ) ) {
		return '';
	}

	return sprintf(
		'https://www.openstreetmap.org/?mlat=%s&mlon=%s#map=17/%s/%s',
		rawurlencode( $lp_lat ),
		rawurlencode( $lp_lon ),
		rawurlencode( $lp_lat ),
		rawurlencode( $lp_lon )
	);
}

/**
 * Google Maps search URL for a lat/lon pin (OPEN IN MAPS exits).
 *
 * @param string $lp_lat Latitude.
 * @param string $lp_lon Longitude.
 * @return string Empty when coords are missing / non-numeric.
 */
function lp_google_maps_url( string $lp_lat, string $lp_lon ): string {
	if ( '' === trim( $lp_lat ) || '' === trim( $lp_lon ) || ! is_numeric( $lp_lat ) || ! is_numeric( $lp_lon ) ) {
		return '';
	}

	return sprintf(
		'https://www.google.com/maps/search/?api=1&query=%s',
		rawurlencode( $lp_lat . ',' . $lp_lon )
	);
}

/**
 * WhatsApp group invite URL — class, then site, then Site Settings.
 *
 * Only chat.whatsapp.com / wa.me / api.whatsapp.com hosts are accepted, so a
 * pasted javascript: or tracking URL never reaches the confirmed page.
 *
 * @param int $class_id    clasbpro_class post ID.
 * @param int $location_id lp_location post ID.
 * @return string Empty when nothing valid is configured.
 */
function lp_whatsapp_invite_url( int $class_id = 0, int $location_id = 0 ): string {
	$candidates = array();
	if ( $class_id > 0 && function_exists( 'get_field' ) ) {
		$candidates[] = (string) get_field( 'whatsapp_url', $class_id );
	}
	if ( $location_id > 0 && function_exists( 'get_field' ) ) {
		$candidates[] = (string) get_field( 'whatsapp_url', $location_id );
	}
	if ( function_exists( 'get_field' ) ) {
		$candidates[] = (string) get_field( 'whatsapp_url', 'option' );
	}

	foreach ( $candidates as $raw ) {
		$url = lp_whatsapp_invite_url_sanitize( $raw );
		if ( '' !== $url ) {
			return $url;
		}
	}

	return '';
}

/**
 * @param string $raw Untrusted field value.
 * @return string Sanitised https invite URL, or empty.
 */
function lp_whatsapp_invite_url_sanitize( string $raw ): string {
	$raw = trim( $raw );
	if ( '' === $raw ) {
		return '';
	}

	$url = esc_url_raw( $raw, array( 'https', 'http' ) );
	if ( '' === $url ) {
		return '';
	}

	$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
	$host = preg_replace( '/^www\./', '', $host ) ?? $host;
	$ok   = array( 'chat.whatsapp.com', 'wa.me', 'api.whatsapp.com', 'whatsapp.com' );

	return in_array( $host, $ok, true ) ? $url : '';
}

/**
 * First N sentences of plain text, whitespace-normalised into one paragraph.
 * Used on Class Detail coach bios so the byline stays short.
 *
 * @param string $lp_text  Source copy.
 * @param int    $lp_count Sentence count (default 2).
 * @return string
 */
function lp_first_sentences( string $lp_text, int $lp_count = 2 ): string {
	$lp_text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $lp_text ) ) ?? '' );
	if ( '' === $lp_text || $lp_count < 1 ) {
		return '';
	}

	if ( ! preg_match_all( '/[^.!?]+(?:[.!?]+(?:\s+|$)|$)/u', $lp_text, $lp_matches ) ) {
		return $lp_text;
	}

	$lp_parts = array_values(
		array_filter(
			array_map( 'trim', $lp_matches[0] ),
			static function ( string $lp_part ): bool {
				return '' !== $lp_part;
			}
		)
	);

	return trim( implode( ' ', array_slice( $lp_parts, 0, $lp_count ) ) );
}

/**
 * Keep class/coach location pickers on sites — spots are map-only.
 *
 * @param array $lp_args WP_Query args for ACF post_object.
 * @return array
 */
function lp_acf_location_post_object_sites_only( array $lp_args ): array {
	$lp_sites = lp_locations_by_kind( 'site' );
	$lp_ids   = array_map(
		static function ( WP_Post $lp_post ): int {
			return (int) $lp_post->ID;
		},
		$lp_sites
	);

	// Empty post__in returns every post; force a no-match when there are no sites.
	$lp_args['post__in'] = $lp_ids ? $lp_ids : array( 0 );
	return $lp_args;
}

add_filter( 'acf/fields/post_object/query/name=acf_location', 'lp_acf_location_post_object_sites_only' );
add_filter( 'acf/fields/post_object/query/name=location', 'lp_acf_location_post_object_sites_only' );

/**
 * The Classes view-rail tabs, shared by Agenda, Map, Private 1:1 and Workshops.
 *
 * Session counts come from clasbpro via lp_class_upcoming_sessions().
 * Site counts are class sites only (not map-only spots).
 *
 * @param string $lp_active agenda|map|private|workshops.
 * @return array Tabs in view-rail.php's shape.
 */
function lp_classes_view_tabs( string $lp_active = 'agenda' ): array {
	$lp_class_ids = get_posts(
		lp_class_query_exclude_one_offs(
			lp_class_active_meta_query(
				array(
					'post_type'      => lp_class_post_type(),
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			)
		)
	);

	$lp_sessions = 0;
	foreach ( $lp_class_ids as $lp_id ) {
		$lp_sessions += count( lp_class_upcoming_sessions( (int) $lp_id, 16 ) );
	}

	$lp_sites = count( lp_locations_by_kind( 'site' ) );

	$lp_workshop_ids = get_posts(
		lp_class_active_meta_query(
			array(
				'post_type'      => lp_class_post_type(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					lp_class_one_off_meta_clause(),
				),
			)
		)
	);
	$lp_workshop_n = count( $lp_workshop_ids );

	$lp_private = home_url( '/private-coaching/' );
	foreach ( array( 'private-coaching', 'private-tuition' ) as $lp_slug ) {
		$lp_page = get_page_by_path( $lp_slug );
		if ( $lp_page instanceof WP_Post ) {
			$lp_private = (string) get_permalink( $lp_page );
			break;
		}
	}

	return array(
		array(
			'label'   => 'AGENDA',
			'meta'    => sprintf( '%d SESSIONS', $lp_sessions ),
			'icon_id' => 'icon-calendar-days',
			'href'    => lp_classes_page_url( 'classes' ),
			'active'  => 'agenda' === $lp_active,
		),
		array(
			'label'   => 'MAP',
			'meta'    => sprintf( '%d SITES', $lp_sites ),
			'icon_id' => 'icon-map-pin',
			'href'    => lp_classes_page_url( 'classes-map' ),
			'active'  => 'map' === $lp_active,
		),
		array(
			'label'   => 'PRIVATE 1:1',
			'meta'    => 'ANY SITE',
			'icon_id' => 'icon-user',
			'href'    => $lp_private,
			'active'  => 'private' === $lp_active,
		),
		array(
			'label'   => 'WORKSHOPS',
			'meta'    => sprintf( '%d DATES', $lp_workshop_n ),
			'icon_id' => 'icon-academic-cap',
			'href'    => lp_workshops_url(),
			'active'  => 'workshops' === $lp_active,
		),
	);
}

/**
 * The three Classes filter cells, in filter-grid.php's shape.
 *
 * Ported from ClassesHeaderCluster.js's `DEFAULT_FILTER_CELLS`. SITE's empty
 * option label counts published `lp_location` posts so it stays accurate when
 * sites are added or removed (the pen's "All six sites" string is not used).
 *
 * Options come from real records: CLASS TYPE from the `lp_level` taxonomy
 * (the design's kickers — ALL LEVELS, LEVEL 2 · IMPROVER, 6–9 AGE — are level
 * terms), SITE from published `lp_location` posts, which is what a class's
 * `acf_location` post_object field points at.
 *
 * Field names match app/setup/queries.php. Nothing here is a taxonomy query
 * var: see that file for why the filter uses its own parameters.
 *
 * @param array $lp_current Current values keyed by field name.
 * @return array
 */
function lp_class_filter_cells( array $lp_current = array() ): array {
	$lp_levels = get_terms(
		array(
			'taxonomy'   => 'lp_level',
			'hide_empty' => false,
		)
	);

	$lp_level_options = array(
		array(
			'value' => '',
			'label' => 'All classes',
		),
	);
	foreach ( is_array( $lp_levels ) ? $lp_levels : array() as $lp_term ) {
		$lp_level_options[] = array(
			'value' => $lp_term->slug,
			'label' => $lp_term->name,
		);
	}

	$lp_sites = lp_locations_by_kind( 'site' );

	$lp_site_n = count( $lp_sites );
	$lp_site_options = array(
		array(
			'value' => '',
			'label' => sprintf(
				/* translators: %d: number of published training sites */
				_n( 'All %d site', 'All %d sites', $lp_site_n, 'londonparkour_v8' ),
				$lp_site_n
			),
		),
	);
	foreach ( $lp_sites as $lp_site ) {
		$lp_site_options[] = array(
			'value' => (string) $lp_site->ID,
			'label' => get_the_title( $lp_site ),
		);
	}

	return array(
		array(
			'type'        => 'search',
			'key'         => 'SEARCH',
			'name'        => 'class_search',
			'placeholder' => 'Class, coach or site',
			'value'       => (string) ( $lp_current['class_search'] ?? '' ),
		),
		array(
			'type'    => 'select',
			'key'     => 'CLASS TYPE',
			'name'    => 'class_level',
			'options' => $lp_level_options,
			'value'   => (string) ( $lp_current['class_level'] ?? '' ),
		),
		array(
			'type'    => 'select',
			'key'     => 'SITE',
			'name'    => 'class_site',
			'options' => $lp_site_options,
			'value'   => (string) ( $lp_current['class_site'] ?? '' ),
		),
	);
}

/**
 * WHEN fact for an agenda card: "18:30 – 20:00" from start time + duration.
 *
 * @param string $lp_time     H:i.
 * @param int    $lp_class_id Class post ID (for duration).
 */
function lp_agenda_when_label( string $lp_time, int $lp_class_id ): string {
	if ( '' === $lp_time ) {
		return '';
	}

	$lp_minutes = (int) filter_var( lp_class_duration( $lp_class_id ), FILTER_SANITIZE_NUMBER_INT );
	if ( ! $lp_minutes ) {
		return $lp_time;
	}

	$lp_start = DateTimeImmutable::createFromFormat( 'H:i', $lp_time );
	if ( ! $lp_start ) {
		return $lp_time;
	}

	return $lp_start->format( 'H:i' ) . ' – ' . $lp_start->modify( sprintf( '+%d minutes', $lp_minutes ) )->format( 'H:i' );
}

/**
 * Attachment ID for a seeded demo-media filename (e.g. DSC01072.jpeg).
 *
 * @param string $lp_filename Basename under bin/demo-media/.
 */
function lp_demo_media_id( string $lp_filename ): int {
	$lp_slug = sanitize_title( pathinfo( $lp_filename, PATHINFO_FILENAME ) );
	if ( '' === $lp_slug ) {
		return 0;
	}

	$lp_ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'name'           => $lp_slug,
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	return (int) ( $lp_ids[0] ?? 0 );
}

/**
 * Every session in one week, grouped by day, for the Agenda board.
 *
 * Sessions come from clasbpro via lp_class_sessions_between() — active classes
 * only, dated occurrences in the requested Monday–Sunday window.
 *
 * @param int $lp_offset Weeks from the current one. Negative is the past.
 * @return array start, end, week number, day groups and a session count.
 */
function lp_agenda_week( int $lp_offset = 0 ): array {
	$lp_start = ( new DateTimeImmutable( 'monday this week' ) )->modify( sprintf( '%+d weeks', $lp_offset ) );
	$lp_end   = $lp_start->modify( '+6 days' );

	// Keyed by Y-m-d so the seven day buckets stay in calendar order.
	$lp_days = array();
	for ( $lp_i = 0; $lp_i < 7; $lp_i++ ) {
		$lp_days[ $lp_start->modify( sprintf( '+%d days', $lp_i ) )->format( 'Y-m-d' ) ] = array();
	}

	$lp_count = 0;
	foreach ( lp_class_sessions_between( $lp_start, $lp_end ) as $lp_row ) {
		$lp_date = (string) ( $lp_row['date'] ?? '' );

		if ( ! isset( $lp_days[ $lp_date ] ) ) {
			continue;
		}

		$lp_class_id = (int) ( $lp_row['id'] ?? 0 );
		$lp_location = (string) ( $lp_row['location'] ?? '' );
		$lp_level    = (string) ( $lp_row['level'] ?? '' );
		$lp_time     = (string) ( $lp_row['time'] ?? '' );

		$lp_days[ $lp_date ][] = array(
			'time'       => $lp_time,
			// BoardRow's own per-row date label is left blank: the day is
			// already announced by the band above it, and repeating it is
			// redundant. The source makes the same call.
			'date_label' => '',
			'title'      => (string) ( $lp_row['title'] ?? '' ),
			'subtitle'   => (string) ( $lp_row['subtitle'] ?? '' ),
			'location'   => $lp_location,
			'level'      => $lp_level,
			'spaces'     => (string) ( $lp_row['spaces'] ?? '' ),
			'sold_out'   => ! empty( $lp_row['sold_out'] ),
			'href'       => (string) ( $lp_row['url'] ?? '' ),
			'class_id'   => $lp_class_id,
			// Cards board fields (O6Fhqs) — kept alongside the row shape.
			'thumb'      => (int) ( $lp_row['thumb'] ?? 0 ),
			'price'      => (string) ( $lp_row['price'] ?? '' ),
			'coaches'    => (string) ( $lp_row['coaches'] ?? '' ),
			'when'       => lp_agenda_when_label( $lp_time, $lp_class_id ),
			'kicker'     => '' !== $lp_location ? strtoupper( $lp_location ) : strtoupper( $lp_level ),
			'past'       => ! lp_class_session_is_future( $lp_row ),
		);
		++$lp_count;
	}

	$lp_groups = array();
	foreach ( $lp_days as $lp_date => $lp_sessions ) {
		usort( $lp_sessions, static fn( $lp_a, $lp_b ): int => strcmp( $lp_a['time'], $lp_b['time'] ) );
		$lp_day = new DateTimeImmutable( $lp_date );

		$lp_groups[] = array(
			'iso'      => $lp_date,
			'day'      => strtoupper( $lp_day->format( 'D' ) ),
			'date'     => strtoupper( $lp_day->format( 'j F Y' ) ),
			'sessions' => $lp_sessions,
		);
	}

	return array(
		'start'  => $lp_start,
		'end'    => $lp_end,
		'week'   => (int) $lp_start->format( 'W' ),
		'days'   => $lp_groups,
		'count'  => $lp_count,
		'offset' => $lp_offset,
	);
}

/**
 * The design's week label: "Week 29 · 13th – 20th July 2026".
 *
 * @param array $lp_week A lp_agenda_week() result.
 * @return string
 */
function lp_agenda_week_label( array $lp_week ): string {
	// The design's example week sits inside one month, so it names the month
	// once. A week that straddles two needs both, or "27th – 2nd August" reads
	// as though the 27th were in August.
	$lp_same_month = $lp_week['start']->format( 'F' ) === $lp_week['end']->format( 'F' );

	return sprintf(
		'Week %d · %s – %s',
		$lp_week['week'],
		$lp_week['start']->format( $lp_same_month ? 'jS' : 'jS F' ),
		$lp_week['end']->format( 'jS F Y' )
	);
}
