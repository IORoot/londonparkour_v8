<?php
/**
 * One-shot: rewrite every lp_tutorial excerpt from description / YouTube /
 * transcript sources. If post_content is empty or the "description"
 * placeholder, write a four-sentence body.
 *
 * Run from the theme:
 *   ./bin/wp eval-file bin/rewrite-tutorial-copy.php
 *
 * @package londonparkour_v8
 */

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "Run via bin/wp eval-file.\n" );
	exit( 1 );
}

/**
 * @param string $text Raw copy.
 */
function lp_copy_is_blank( string $text ): bool {
	$t = strtolower( trim( wp_strip_all_tags( $text ) ) );
	return '' === $t || 'description' === $t;
}

/**
 * @param string $text Raw copy.
 */
function lp_copy_strip_boilerplate( string $text ): string {
	$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	$text = preg_replace( '/\*{1,2}([^*]+)\*{1,2}/', '$1', $text ) ?? $text;
	$text = preg_replace( '/\[(?:Music|Applause|Laughter|Silence)\]/i', ' ', $text ) ?? $text;
	$text = preg_replace( '#https?://\S+#i', ' ', $text ) ?? $text;
	$text = preg_replace( '/#[A-Za-z0-9_]+/', ' ', $text ) ?? $text;
	$lines = preg_split( '/\R/u', $text ) ?: array();
	$keep  = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( '' === $line ) {
			continue;
		}
		if ( preg_match( '/useful links|follow us|hashtags|instagram|subscribe|patreon|londonparkour\.com|get in touch|email us|watch more on youtube|detailed tutorials and articles/i', $line ) ) {
			continue;
		}
		if ( preg_match( '/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{25C0}-\x{25FF}◣◥🔹📌]+/u', $line ) ) {
			continue;
		}
		$keep[] = $line;
	}
	$text = implode( ' ', $keep );
	$text = preg_replace( '/\s+/u', ' ', $text ) ?? $text;
	return trim( $text );
}

/**
 * @param mixed $raw video_transcript_json value.
 */
function lp_copy_transcript_text( $raw ): string {
	if ( is_string( $raw ) && '' !== $raw ) {
		$json = json_decode( $raw, true );
	} elseif ( is_array( $raw ) ) {
		$json = $raw;
	} else {
		return '';
	}
	if ( ! is_array( $json ) ) {
		return '';
	}
	if ( isset( $json['transcript'] ) && is_array( $json['transcript'] ) ) {
		$json = $json['transcript'];
	} elseif ( isset( $json['cues'] ) && is_array( $json['cues'] ) ) {
		$json = $json['cues'];
	}
	$bits = array();
	foreach ( $json as $cue ) {
		if ( is_string( $cue ) && '' !== $cue ) {
			$bits[] = $cue;
			continue;
		}
		if ( ! is_array( $cue ) ) {
			continue;
		}
		$t = $cue['text'] ?? $cue['content'] ?? $cue['transcript'] ?? '';
		if ( is_string( $t ) && '' !== $t ) {
			$bits[] = $t;
		}
	}
	return lp_copy_strip_boilerplate( implode( ' ', $bits ) );
}

/**
 * @param string $text Prose.
 * @return string[]
 */
function lp_copy_split_sentences( string $text ): array {
	$text = trim( $text );
	if ( '' === $text ) {
		return array();
	}
	$text = preg_replace( '/^\s*[-*•]\s+/m', '', $text ) ?? $text;
	$text = str_replace( '...', '…', $text );
	$parts = preg_split( '/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! is_array( $parts ) || count( $parts ) < 2 ) {
		// Spoken transcript often has no stops — chunk on " so " / " okay ".
		$chunked = preg_split( '/\s+(?:so|okay so|alright so|and then)\s+/iu', $text, 8, PREG_SPLIT_NO_EMPTY );
		if ( is_array( $chunked ) && count( $chunked ) > 1 ) {
			$parts = $chunked;
		} else {
			$parts = array( $text );
		}
	}
	$out = array();
	foreach ( $parts as $part ) {
		$part = trim( $part, " \t\"'`“”" );
		$part = preg_replace( '/\s+/u', ' ', $part ) ?? $part;
		if ( mb_strlen( $part ) < 28 ) {
			continue;
		}
		$out[] = $part;
	}
	return $out;
}

/**
 * @param string $sentence Candidate.
 */
function lp_copy_is_filler( string $sentence ): bool {
	return (bool) preg_match(
		'/(if you.?re looking|if you are looking|when it comes to|in this tutorial|this tutorial will|this tutorial introduces|this tutorial is designed|this guide will|to sum up|in wrapping up|first and foremost|have you ever|if you.?ve ever felt|welcome back|if you.?re gearing|you.?ve come to the right place|you.?re not alone|many climbers make|spices up|fun way|new heights|climbing routine|add some excitement|by embracing these|not only spices)/i',
		$sentence
	);
}

/**
 * Prefer lines that name the movement over marketing filler.
 *
 * @param string $sentence Candidate.
 */
function lp_copy_sentence_score( string $sentence ): int {
	$score = 0;
	if ( preg_match( '/\b(wall|rail|bar|cat[- ]?leap|vault|climb|jump|land|grip|foot|feet|arm|hip|knee|shoulder|step|take-?off|kong|lache|tic[- ]tac|cork|crane|overgrip|pull-?up|quad|precision|turn away|top position|speed-?step)\b/i', $sentence ) ) {
		$score += 3;
	}
	if ( preg_match( '/\b(start|from the|then|drive|turn|hold|place|keep your|make your way|position yourself)\b/i', $sentence ) ) {
		$score += 2;
	}
	if ( lp_copy_is_filler( $sentence ) || lp_copy_is_spoken_junk( $sentence ) ) {
		$score -= 8;
	}
	return $score;
}

/**
 * Fix obvious ASR misses using the tutorial title.
 *
 * @param string $text  Source copy.
 * @param string $title Decoded title.
 */
function lp_copy_align_to_title( string $text, string $title ): string {
	if ( preg_match( '/corkscrew/i', $title ) ) {
		$text = preg_replace( '/cook\s*screws?/i', 'corkscrew', $text ) ?? $text;
	}
	return $text;
}

/**
 * @param string $sentence Candidate.
 * @param string $title    Decoded title.
 */
function lp_copy_is_off_topic( string $sentence, string $title ): bool {
	if ( preg_match( '/\b(workout routine|fitness journey|single-leg squat|lunges?|climbing shoes|game changer|level up your workout|hit the climbing wall|strides you.?ll make)\b/i', $sentence )
		&& ! preg_match( '/squat|lunge|shoe/i', $title ) ) {
		return true;
	}
	return false;
}

/**
 * @param string $sentence Candidate.
 */
function lp_copy_is_spoken_junk( string $sentence ): bool {
	return (bool) preg_match(
		'/\b(uh+|please stop|like i said yesterday|i had over whip|they i wanted|something he just have|yeah try it out)\b/i',
		$sentence
	);
}

/**
 * @param string $sentence Spoken line.
 */
function lp_copy_tidy_spoken( string $sentence ): string {
	$sentence = preg_replace( '/\bokay\b[.,]?/i', ' ', $sentence ) ?? $sentence;
	$sentence = preg_replace( '/\bgonna\b/i', 'going to', $sentence ) ?? $sentence;
	$sentence = preg_replace( '/\s+/u', ' ', $sentence ) ?? $sentence;
	return trim( $sentence, " ,;" );
}

/**
 * @param string $sentence Candidate.
 */
function lp_copy_end_sentence( string $sentence ): string {
	$sentence = rtrim( $sentence );
	if ( '' !== $sentence && ! preg_match( '/[.!?]$/u', $sentence ) ) {
		$sentence .= '.';
	}
	// Spoken leftover: capitalise first letter.
	if ( $sentence !== '' ) {
		$sentence = mb_strtoupper( mb_substr( $sentence, 0, 1 ) ) . mb_substr( $sentence, 1 );
	}
	return $sentence;
}

/**
 * @param string[] $sentences Candidates.
 * @param int      $need      How many.
 * @return string[]
 */
function lp_copy_pick_sentences( array $sentences, int $need, bool $rank = false ): array {
	$candidates = array();
	foreach ( $sentences as $i => $sentence ) {
		if ( preg_match( '/essential equipment|don.?t forget to incorporate/i', $sentence ) ) {
			continue;
		}
		$candidates[] = array(
			'text'  => $sentence,
			'score' => lp_copy_sentence_score( $sentence ),
			'i'     => $i,
		);
	}
	if ( $rank ) {
		usort(
			$candidates,
			static function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return $a['i'] <=> $b['i'];
				}
				return $b['score'] <=> $a['score'];
			}
		);
	}

	$good = array();
	foreach ( $candidates as $row ) {
		if ( $row['score'] < 0 ) {
			continue;
		}
		$ended = lp_copy_end_sentence( $row['text'] );
		if ( in_array( $ended, $good, true ) ) {
			continue;
		}
		$good[] = $ended;
		if ( count( $good ) >= $need ) {
			return $good;
		}
	}
	return $good;
}

/**
 * @param string $line Line.
 * @param int    $max  Max chars.
 */
function lp_copy_clip( string $line, int $max = 240 ): string {
	$line = lp_copy_end_sentence( $line );
	if ( mb_strlen( $line ) <= $max ) {
		return $line;
	}
	$line = mb_substr( $line, 0, $max - 1 );
	$line = preg_replace( '/\s+\S*$/u', '', $line ) ?? $line;
	return rtrim( $line, '.,;: ' ) . '.';
}

/**
 * @param WP_Post $post Tutorial.
 * @return array{excerpt:string,body:string,fill_body:bool}
 */
function lp_copy_for_tutorial( WP_Post $post ): array {
	$title   = html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' );
	$content = (string) $post->post_content;
	$fill    = lp_copy_is_blank( $content );

	$yt    = lp_copy_align_to_title( lp_copy_strip_boilerplate( (string) get_post_meta( $post->ID, 'video_youtube_data_description', true ) ), $title );
	$ytf   = lp_copy_align_to_title( lp_copy_strip_boilerplate( (string) get_post_meta( $post->ID, 'video_youtube_data_description_filtered', true ) ), $title );
	$gpt   = lp_copy_align_to_title( lp_copy_strip_boilerplate( (string) get_post_meta( $post->ID, 'video_chatgpt_response_text', true ) ), $title );
	$tgpt  = lp_copy_align_to_title( lp_copy_strip_boilerplate( (string) get_post_meta( $post->ID, 'video_transcript_chatgpt', true ) ), $title );
	$tr    = lp_copy_align_to_title( lp_copy_transcript_text( get_post_meta( $post->ID, 'video_transcript_json', true ) ), $title );

	$content_sents = array();
	if ( ! $fill ) {
		$content_sents = lp_copy_split_sentences( lp_copy_strip_boilerplate( wp_strip_all_tags( $content ) ) );
	}

	$src_sents = array();
	foreach ( array( $ytf, $yt, $tgpt, $gpt, $tr ) as $i => $src ) {
		if ( mb_strlen( $src ) < 40 ) {
			continue;
		}
		$from_transcript = 4 === $i;
		foreach ( lp_copy_split_sentences( $src ) as $sentence ) {
			if ( $from_transcript ) {
				$sentence = lp_copy_tidy_spoken( $sentence );
			}
			if ( mb_strlen( $sentence ) < 28 ) {
				continue;
			}
			if ( lp_copy_is_off_topic( $sentence, $title ) || lp_copy_is_spoken_junk( $sentence ) || lp_copy_is_filler( $sentence ) ) {
				continue;
			}
			$src_sents[] = $sentence;
		}
	}

	$short = preg_replace( '/^How to\s+/i', '', $title ) ?? $title;
	$short = trim( $short, " ." );
	$tags  = get_the_terms( $post, 'tutorial-tag' );
	$tag   = ( is_array( $tags ) && $tags ) ? strtolower( $tags[0]->slug ) : '';
	$cats  = get_the_terms( $post, 'tutorial-category' );
	$move  = '';
	if ( is_array( $cats ) ) {
		foreach ( $cats as $cat ) {
			if ( $cat->parent ) {
				$move = $cat->name;
				break;
			}
		}
		if ( '' === $move && $cats ) {
			$move = $cats[0]->name;
		}
	}

	$short_lc = lcfirst( $short );
	if ( 'demonstration' === $tag ) {
		$fallback_ex = sprintf( 'A demonstration of %s.', $short_lc );
	} elseif ( 'challenge' === $tag ) {
		$fallback_ex = sprintf( 'A challenge on %s — show the shape, not a near miss.', $short_lc );
	} elseif ( '' !== $move ) {
		$fallback_ex = sprintf( '%s, coached: %s.', $move, $short );
	} else {
		$fallback_ex = $short . '.';
	}

	$excerpt_pool  = $fill ? $src_sents : $content_sents;
	$excerpt_bits  = lp_copy_pick_sentences( $excerpt_pool, 1, $fill );
	$excerpt       = lp_copy_clip( $excerpt_bits[0] ?? $fallback_ex );

	$body = '';
	if ( $fill ) {
		$four = lp_copy_pick_sentences( $src_sents, 4, false );
		if ( count( $four ) < 4 ) {
			if ( 'demonstration' === $tag ) {
				$pad = array(
					sprintf( 'This clip shows %s as a demonstration, not a coached breakdown.', $short_lc ),
					'' !== $move ? sprintf( 'Watch it in %s — the line, the contact, and where the weight sits.', $move ) : 'Watch the line, the contact, and where the weight sits.',
					'Match the pace and the shape; pause and copy the details you can see.',
					'Bring the same quiet version to class when you can hold it at speed.',
				);
			} else {
				$pad = array(
					sprintf( 'This lesson is %s.', $short ),
					'' !== $move ? sprintf( 'It sits in %s — watch the contact, the line, and where the weight goes.', $move ) : 'Watch the contact, the line, and where the weight goes.',
					'Pause, copy the shape, then put the same details into your own reps.',
					'Bring it to class when the movement is quiet enough to hold at speed.',
				);
			}
			foreach ( $pad as $extra ) {
				if ( count( $four ) >= 4 ) {
					break;
				}
				$four[] = lp_copy_end_sentence( $extra );
			}
		}
		$body = implode( ' ', array_slice( $four, 0, 4 ) );
	}

	return array(
		'excerpt'   => $excerpt,
		'body'      => $body,
		'fill_body' => $fill,
	);
}

$dry = in_array( '--dry-run', $GLOBALS['argv'] ?? array(), true )
	|| '1' === getenv( 'LP_COPY_DRY' );

$ids = array_values( array_filter( array_map( 'intval', explode( ',', (string) getenv( 'LP_COPY_IDS' ) ) ) ) );

$args = array(
	'post_type'              => 'lp_tutorial',
	'post_status'            => 'publish',
	'posts_per_page'         => $dry ? 18 : -1,
	'orderby'                => 'ID',
	'order'                  => 'ASC',
	'no_found_rows'          => true,
	'update_post_meta_cache' => true,
	'update_post_term_cache' => true,
);
if ( $ids ) {
	$args['post__in']       = $ids;
	$args['orderby']        = 'post__in';
	$args['posts_per_page'] = count( $ids );
}

$query = new WP_Query( $args );

$updated = 0;
$filled  = 0;
$skipped = 0;
global $wpdb;

foreach ( $query->posts as $post ) {
	if ( ! $post instanceof WP_Post ) {
		continue;
	}
	$copy = lp_copy_for_tutorial( $post );
	if ( '' === $copy['excerpt'] ) {
		++$skipped;
		continue;
	}

	if ( $dry ) {
		$kind = $copy['fill_body'] ? 'FILL' : 'KEEP-BODY';
		echo $kind . ' #' . $post->ID . ' ' . get_the_title( $post ) . "\n";
		echo '  EX: ' . $copy['excerpt'] . "\n";
		if ( $copy['fill_body'] ) {
			echo '  BD: ' . $copy['body'] . "\n";
		}
		echo "\n";
		++$updated;
		if ( $copy['fill_body'] ) {
			++$filled;
		}
		continue;
	}

	$row = array(
		'post_excerpt' => $copy['excerpt'],
	);
	if ( $copy['fill_body'] && '' !== $copy['body'] ) {
		$row['post_content'] = $copy['body'];
		++$filled;
	}

	$ok = $wpdb->update(
		$wpdb->posts,
		$row,
		array( 'ID' => (int) $post->ID ),
		array_fill( 0, count( $row ), '%s' ),
		array( '%d' )
	);
	if ( false === $ok ) {
		fwrite( STDERR, 'Fail ' . $post->ID . "\n" );
		continue;
	}
	clean_post_cache( (int) $post->ID );
	++$updated;
}

echo "Updated {$updated} tutorials. Wrote body copy on {$filled} empty posts. Skipped {$skipped}.\n";
