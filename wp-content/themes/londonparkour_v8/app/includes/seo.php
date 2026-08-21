<?php
/**
 * Site-wide SEO layer — meta, Open Graph, canonical, robots, JSON-LD.
 *
 * Hooks `wp_head` only. No visual markup, no class-string changes, no H1 work.
 * Future pages inherit the same head tags; type-specific JSON-LD attaches when
 * the queried object is a class, tutorial, FAQ surface, coupon pack, or similar.
 *
 * Override points:
 *   lp_seo_description
 *   lp_seo_image
 *   lp_seo_robots
 *   lp_seo_graph
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wire every public response.
 */
function lp_seo_boot(): void {
	if ( is_admin() ) {
		return;
	}

	remove_action( 'wp_head', 'rel_canonical' );

	add_action( 'wp_head', 'lp_seo_print_meta', 1 );
	add_action( 'wp_head', 'lp_seo_print_jsonld', 5 );
	add_filter( 'wp_robots', 'lp_seo_robots' );
	add_filter( 'document_title_separator', 'lp_seo_title_separator' );
	add_filter( 'robots_txt', 'lp_seo_robots_txt', 10, 2 );
}
add_action( 'wp', 'lp_seo_boot' );

/**
 * Title separator matching the site's label voice.
 */
function lp_seo_title_separator( string $sep ): string {
	unset( $sep );
	return '·';
}

/**
 * Current canonical URL: the public permalink, minus tracking query args.
 */
function lp_seo_canonical_url(): string {
	$url = '';

	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_singular() ) {
		$url = (string) wp_get_canonical_url();
	} elseif ( is_home() && get_option( 'page_for_posts' ) ) {
		$url = (string) get_permalink( (int) get_option( 'page_for_posts' ) );
	} elseif ( is_post_type_archive() ) {
		$url = (string) get_post_type_archive_link( get_query_var( 'post_type' ) );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$url = (string) get_term_link( $term );
		}
	} elseif ( is_search() ) {
		$url = (string) get_search_link();
	} elseif ( is_404() ) {
		$url = home_url( '/' );
	}

	if ( is_wp_error( $url ) || '' === $url ) {
		$url = home_url( add_query_arg( array() ) );
	}

	if ( is_paged() && ! is_404() ) {
		$url = html_entity_decode( get_pagenum_link( (int) get_query_var( 'paged' ) ) );
	}

	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return $url;
	}

	$query = array();
	if ( ! empty( $parts['query'] ) ) {
		parse_str( $parts['query'], $query );
		foreach ( array_keys( $query ) as $key ) {
			if ( preg_match( '/^(utm_|mc_|fbclid|gclid|_ga)/i', (string) $key ) ) {
				unset( $query[ $key ] );
			}
		}
	}

	$built = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '' )
		. ( $parts['host'] ?? '' )
		. ( isset( $parts['port'] ) ? ':' . $parts['port'] : '' )
		. ( $parts['path'] ?? '/' );

	if ( $query ) {
		$built .= '?' . http_build_query( $query );
	}

	return $built;
}

/**
 * Plain text, collapsed whitespace, no tags.
 */
function lp_seo_plain( string $html ): string {
	$html = wp_strip_all_tags( $html, true );
	$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$html = preg_replace( '/\s+/u', ' ', $html );

	return trim( (string) $html );
}

/**
 * Trim to ~155 characters on a word boundary.
 */
function lp_seo_clip( string $text, int $max = 155 ): string {
	$text = lp_seo_plain( $text );
	if ( mb_strlen( $text ) <= $max ) {
		return $text;
	}

	$cut = mb_substr( $text, 0, $max - 1 );
	$sp  = mb_strrpos( $cut, ' ' );
	if ( false !== $sp && $sp > (int) ( $max * 0.6 ) ) {
		$cut = mb_substr( $cut, 0, $sp );
	}

	return rtrim( $cut, '.,;:—' ) . '…';
}

/**
 * Whether this response should stay out of the index.
 */
function lp_seo_is_noindex(): bool {
	if ( is_search() || is_404() || is_attachment() ) {
		return true;
	}

	$slugs = array( 'blocks-qa', 'booking-error', 'booking-cancelled', 'booking-confirmed' );
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && in_array( $post->post_name, $slugs, true ) ) {
			return true;
		}
	}

	return (bool) apply_filters( 'lp_seo_noindex', false );
}

/**
 * Core robots meta. Search / 404 / utility pages stay out of the index.
 *
 * @param array<string, mixed> $robots
 * @return array<string, mixed>
 */
function lp_seo_robots( array $robots ): array {
	if ( lp_seo_is_noindex() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['index'], $robots['follow'] );
	} else {
		$robots['index']  = true;
		$robots['follow'] = true;
		unset( $robots['noindex'], $robots['nofollow'] );
	}

	$robots['max-image-preview'] = 'large';
	$robots['max-snippet']       = '-1';
	$robots['max-video-preview'] = '-1';

	return apply_filters( 'lp_seo_robots', $robots );
}

/**
 * Ensure the sitemap is advertised in robots.txt.
 */
function lp_seo_robots_txt( string $output, bool $public ): string {
	if ( ! $public ) {
		return $output;
	}

	$sitemap = home_url( '/wp-sitemap.xml' );
	if ( false === strpos( $output, 'Sitemap:' ) ) {
		$output = rtrim( $output ) . "\n\nSitemap: {$sitemap}\n";
	}

	return $output;
}

/**
 * First usable string from a Flexible Content row — existing copy only.
 *
 * @param array<string, mixed> $row
 */
function lp_seo_row_text( array $row ): string {
	$keys = array( 'lead', 'standfirst', 'note', 'subtitle', 'intro', 'body', 'headline', 'text', 'description' );
	foreach ( $keys as $key ) {
		if ( empty( $row[ $key ] ) || ! is_string( $row[ $key ] ) ) {
			continue;
		}
		$plain = lp_seo_plain( $row[ $key ] );
		if ( '' !== $plain ) {
			return $plain;
		}
	}

	return '';
}

/**
 * Description for the current response.
 */
function lp_seo_description(): string {
	$desc = '';

	if ( is_singular() ) {
		$post_id = (int) get_queried_object_id();
		$excerpt = has_excerpt( $post_id ) ? (string) get_the_excerpt( $post_id ) : '';
		if ( '' !== lp_seo_plain( $excerpt ) ) {
			$desc = $excerpt;
		} else {
			$content = (string) get_post_field( 'post_content', $post_id );
			$desc    = lp_seo_plain( $content );
		}

		if ( '' === $desc && function_exists( 'get_field' ) ) {
			$sections = get_field( 'page_sections', $post_id );
			if ( is_array( $sections ) ) {
				foreach ( $sections as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$desc = lp_seo_row_text( $row );
					if ( '' !== $desc ) {
						break;
					}
				}
			}
		}

		if ( '' === $desc ) {
			foreach ( array( 'acf_subtitle', 'subtitle', 'lead', 'standfirst' ) as $field ) {
				if ( ! function_exists( 'get_field' ) ) {
					break;
				}
				$value = get_field( $field, $post_id );
				if ( is_string( $value ) && '' !== lp_seo_plain( $value ) ) {
					$desc = $value;
					break;
				}
			}
		}
	} elseif ( is_home() ) {
		$blog = (int) get_option( 'page_for_posts' );
		if ( $blog ) {
			$desc = (string) get_the_excerpt( $blog );
			if ( '' === lp_seo_plain( $desc ) ) {
				$desc = (string) get_post_field( 'post_content', $blog );
			}
		}
	} elseif ( is_post_type_archive() ) {
		$pto = get_queried_object();
		if ( $pto instanceof WP_Post_Type && ! empty( $pto->description ) ) {
			$desc = (string) $pto->description;
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$desc = (string) $term->description;
		}
	}

	if ( '' === lp_seo_plain( $desc ) ) {
		$desc = (string) get_bloginfo( 'description', 'display' );
	}

	if ( '' === lp_seo_plain( $desc ) ) {
		$pairs = lp_seo_faq_pairs();
		if ( $pairs ) {
			$desc = $pairs[0]['question'] . ' ' . $pairs[0]['answer'];
		}
	}

	if ( '' === lp_seo_plain( $desc ) ) {
		$desc = wp_get_document_title();
	}

	return apply_filters( 'lp_seo_description', lp_seo_clip( $desc ) );
}

/**
 * Share image: featured → custom logo → site icon.
 *
 * @return array{url:string,width:int,height:int,alt:string}|null
 */
function lp_seo_image(): ?array {
	$attachment_id = 0;

	if ( is_singular() && has_post_thumbnail() ) {
		$attachment_id = (int) get_post_thumbnail_id();
	}

	if ( ! $attachment_id && function_exists( 'get_theme_mod' ) ) {
		$attachment_id = (int) get_theme_mod( 'custom_logo' );
	}

	if ( ! $attachment_id ) {
		$attachment_id = (int) get_option( 'site_icon' );
	}

	if ( ! $attachment_id && function_exists( 'get_field' ) ) {
		$front = (int) get_option( 'page_on_front' );
		if ( $front ) {
			$sections = get_field( 'page_sections', $front );
			if ( is_array( $sections ) ) {
				foreach ( $sections as $row ) {
					if ( ! is_array( $row ) ) {
						continue;
					}
					$layout = str_replace( '_', '-', (string) ( $row['acf_fc_layout'] ?? '' ) );
					if ( 'hero' !== $layout ) {
						continue;
					}
					$media = $row['media'] ?? 0;
					if ( is_array( $media ) ) {
						$media = $media['ID'] ?? $media['id'] ?? 0;
					}
					$attachment_id = absint( $media );
					if ( $attachment_id ) {
						break;
					}
				}
			}
		}
	}

	$attachment_id = (int) apply_filters( 'lp_seo_image_id', $attachment_id );
	if ( $attachment_id < 1 ) {
		return apply_filters( 'lp_seo_image', null );
	}

	$src = wp_get_attachment_image_src( $attachment_id, 'lp_wide' );
	if ( ! is_array( $src ) ) {
		$src = wp_get_attachment_image_src( $attachment_id, 'full' );
	}
	if ( ! is_array( $src ) || empty( $src[0] ) ) {
		return apply_filters( 'lp_seo_image', null );
	}

	$image = array(
		'url'    => (string) $src[0],
		'width'  => (int) ( $src[1] ?? 0 ),
		'height' => (int) ( $src[2] ?? 0 ),
		'alt'    => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
	);

	return apply_filters( 'lp_seo_image', $image );
}

/**
 * Print description, canonical, Open Graph and Twitter tags.
 */
function lp_seo_print_meta(): void {
	$title       = wp_get_document_title();
	$description = lp_seo_description();
	$url         = lp_seo_canonical_url();
	$image       = lp_seo_image();
	$locale      = str_replace( '-', '_', get_locale() );
	$type        = ( is_singular( 'post' ) || is_singular( 'lp_tutorial' ) ) ? 'article' : 'website';

	if ( is_singular( 'clasbpro_class' ) ) {
		$type = 'article';
	}

	if ( '' !== $description ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
	}

	if ( ! is_404() ) {
		printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
	}

	printf( '<meta property="og:locale" content="%s">' . "\n", esc_attr( $locale ) );
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( '' !== $description ) {
		printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $description ) );
	}
	if ( ! is_404() ) {
		printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	}

	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	printf( '<meta name="twitter:title" content="%s">' . "\n", esc_attr( $title ) );
	if ( '' !== $description ) {
		printf( '<meta name="twitter:description" content="%s">' . "\n", esc_attr( $description ) );
	}

	if ( $image ) {
		printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $image['url'] ) );
		if ( $image['width'] > 0 ) {
			printf( '<meta property="og:image:width" content="%d">' . "\n", $image['width'] );
		}
		if ( $image['height'] > 0 ) {
			printf( '<meta property="og:image:height" content="%d">' . "\n", $image['height'] );
		}
		if ( '' !== $image['alt'] ) {
			printf( '<meta property="og:image:alt" content="%s">' . "\n", esc_attr( $image['alt'] ) );
		}
		printf( '<meta name="twitter:image" content="%s">' . "\n", esc_url( $image['url'] ) );
	}
}

/**
 * Stable @id for the organisation node.
 */
function lp_seo_org_id(): string {
	return trailingslashit( home_url( '/' ) ) . '#organization';
}

/**
 * Stable @id for the website node.
 */
function lp_seo_website_id(): string {
	return trailingslashit( home_url( '/' ) ) . '#website';
}

/**
 * Place node for one lp_location site.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_place_node( WP_Post $location ): ?array {
	$id    = (int) $location->ID;
	$name  = get_the_title( $id );
	$lat   = function_exists( 'get_field' ) ? trim( (string) get_field( 'latitude', $id ) ) : '';
	$lon   = function_exists( 'get_field' ) ? trim( (string) get_field( 'longitude', $id ) ) : '';
	$meta  = function_exists( 'get_field' ) ? (string) get_field( 'meta', $id ) : '';
	$node  = array(
		'@type' => 'Place',
		'@id'   => get_permalink( $id ) ? get_permalink( $id ) . '#place' : home_url( '/#place-' . $id ),
		'name'  => $name,
	);

	$address = array(
		'@type'          => 'PostalAddress',
		'addressLocality' => $name,
		'addressRegion'  => 'Greater London',
		'addressCountry' => 'GB',
	);
	$postcode = function_exists( 'lp_location_postcode' ) ? lp_location_postcode( $id ) : '';
	if ( '' !== $postcode ) {
		$address['postalCode'] = $postcode;
	}
	if ( '' !== lp_seo_plain( $meta ) ) {
		$address['streetAddress'] = lp_seo_plain( $meta );
	}
	$node['address'] = $address;

	if ( '' !== $lat && '' !== $lon && is_numeric( $lat ) && is_numeric( $lon ) ) {
		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $lat,
			'longitude' => (float) $lon,
		);
	}

	return $node;
}

/**
 * AggregateRating from imported Google reviews, when we have real scores.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_aggregate_rating(): ?array {
	if ( ! post_type_exists( 'lp_testimonial' ) ) {
		return null;
	}

	$ids = get_posts(
		array(
			'post_type'      => 'lp_testimonial',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$sum = 0;
	$n   = 0;
	foreach ( $ids as $id ) {
		$rating = function_exists( 'get_field' ) ? (int) get_field( 'rating', (int) $id ) : 0;
		if ( $rating < 1 || $rating > 5 ) {
			continue;
		}
		$sum += $rating;
		++$n;
	}

	if ( $n < 1 ) {
		return null;
	}

	return array(
		'@type'       => 'AggregateRating',
		'ratingValue' => round( $sum / $n, 1 ),
		'bestRating'  => 5,
		'worstRating' => 1,
		'reviewCount' => $n,
	);
}

/**
 * Organisation / LocalBusiness node.
 *
 * @return array<string, mixed>
 */
function lp_seo_organization_node(): array {
	$org = array(
		'@type' => array( 'SportsActivityLocation', 'LocalBusiness' ),
		'@id'   => lp_seo_org_id(),
		'name'  => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
		'email' => 'hello@londonparkour.com',
		'areaServed' => array(
			'@type' => 'City',
			'name'  => 'London',
		),
		'sameAs' => array(
			'https://www.instagram.com/london_parkour',
			'https://youtube.com/@londonparkour',
			'https://www.facebook.com/ldnpk',
		),
	);

	$image = lp_seo_image();
	if ( $image ) {
		$org['image']  = $image['url'];
		$org['logo']   = $image['url'];
	}

	$rating = lp_seo_aggregate_rating();
	if ( $rating ) {
		$org['aggregateRating'] = $rating;
	}

	if ( function_exists( 'lp_locations_by_kind' ) ) {
		$places = array();
		foreach ( lp_locations_by_kind( 'site' ) as $location ) {
			$node = lp_seo_place_node( $location );
			if ( $node ) {
				$places[] = $node;
			}
		}
		if ( $places ) {
			$org['location'] = $places;
		}
	}

	return $org;
}

/**
 * WebSite node, with SearchAction when a public search exists.
 *
 * @return array<string, mixed>
 */
function lp_seo_website_node(): array {
	$node = array(
		'@type'     => 'WebSite',
		'@id'       => lp_seo_website_id(),
		'url'       => home_url( '/' ),
		'name'      => get_bloginfo( 'name' ),
		'publisher' => array( '@id' => lp_seo_org_id() ),
		'inLanguage' => get_bloginfo( 'language' ),
	);

	$node['potentialAction'] = array(
		'@type'       => 'SearchAction',
		'target'      => array(
			'@type'       => 'EntryPoint',
			'urlTemplate' => home_url( '/?s={search_term_string}' ),
		),
		'query-input' => 'required name=search_term_string',
	);

	return $node;
}

/**
 * BreadcrumbList from the queried object — works for any future template.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_breadcrumb_node(): ?array {
	$items = array(
		array(
			'name' => get_bloginfo( 'name' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_front_page() ) {
		return null;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post ) {
			if ( 'post' === $post->post_type ) {
				$blog = (int) get_option( 'page_for_posts' );
				if ( $blog ) {
					$items[] = array(
						'name' => get_the_title( $blog ),
						'url'  => (string) get_permalink( $blog ),
					);
				}
			} elseif ( 'page' !== $post->post_type ) {
				$pto = get_post_type_object( $post->post_type );
				if ( $pto && ! empty( $pto->has_archive ) ) {
					$archive = get_post_type_archive_link( $post->post_type );
					if ( $archive ) {
						$items[] = array(
							'name' => $pto->labels->name,
							'url'  => $archive,
						);
					}
				}
			}

			$ancestors = array_reverse( get_post_ancestors( $post ) );
			foreach ( $ancestors as $ancestor_id ) {
				$items[] = array(
					'name' => get_the_title( (int) $ancestor_id ),
					'url'  => (string) get_permalink( (int) $ancestor_id ),
				);
			}

			$items[] = array(
				'name' => get_the_title( $post ),
				'url'  => (string) get_permalink( $post ),
			);
		}
	} elseif ( is_home() ) {
		$blog = (int) get_option( 'page_for_posts' );
		$items[] = array(
			'name' => $blog ? get_the_title( $blog ) : __( 'Blog', 'londonparkour_v8' ),
			'url'  => $blog ? (string) get_permalink( $blog ) : home_url( '/' ),
		);
	} elseif ( is_post_type_archive() ) {
		$pto = get_queried_object();
		if ( $pto instanceof WP_Post_Type ) {
			$items[] = array(
				'name' => $pto->labels->name,
				'url'  => (string) get_post_type_archive_link( $pto->name ),
			);
		}
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$tax = get_taxonomy( $term->taxonomy );
			if ( $tax && ! empty( $tax->object_type[0] ) ) {
				$archive = get_post_type_archive_link( $tax->object_type[0] );
				if ( $archive ) {
					$pto     = get_post_type_object( $tax->object_type[0] );
					$items[] = array(
						'name' => $pto ? $pto->labels->name : $tax->labels->name,
						'url'  => $archive,
					);
				}
			}
			$items[] = array(
				'name' => $term->name,
				'url'  => (string) get_term_link( $term ),
			);
		}
	} elseif ( is_search() ) {
		$items[] = array(
			'name' => __( 'Search', 'londonparkour_v8' ),
			'url'  => (string) get_search_link(),
		);
	} else {
		return null;
	}

	$list = array();
	$pos  = 1;
	foreach ( $items as $item ) {
		if ( empty( $item['url'] ) || is_wp_error( $item['url'] ) ) {
			continue;
		}
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $pos,
			'name'     => lp_seo_plain( (string) $item['name'] ),
			'item'     => $item['url'],
		);
		++$pos;
	}

	if ( count( $list ) < 2 ) {
		return null;
	}

	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);
}

/**
 * Flatten FAQ rows (ACF or defaults) to question/answer pairs.
 *
 * @param array<int, array<string, mixed>> $rows
 * @return array<int, array{question:string,answer:string}>
 */
function lp_seo_faq_pairs_from_rows( array $rows ): array {
	$canonical = array();
	$defaults  = lp_faq_default_items();
	foreach ( lp_faq_default_groups() as $group ) {
		if ( is_array( $group ) && ! empty( $group['items'] ) && is_array( $group['items'] ) ) {
			foreach ( $group['items'] as $item ) {
				$defaults[] = $item;
			}
		}
	}
	foreach ( $defaults as $item ) {
		if ( is_array( $item ) && ! empty( $item['question'] ) ) {
			$canonical[ (string) $item['question'] ] = (string) ( $item['answer'] ?? '' );
		}
	}

	$pairs = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$question = lp_seo_plain( (string) ( $row['question'] ?? '' ) );
		$answer   = (string) ( $row['answer'] ?? '' );
		if ( isset( $canonical[ $question ] ) ) {
			$answer = $canonical[ $question ];
		} elseif ( isset( $canonical[ (string) ( $row['question'] ?? '' ) ] ) ) {
			$answer = $canonical[ (string) $row['question'] ];
		}
		$answer = lp_seo_plain( $answer );
		if ( '' === $question || '' === $answer ) {
			continue;
		}
		$pairs[] = array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	return $pairs;
}

/**
 * FAQ pairs for the current page.
 *
 * @return array<int, array{question:string,answer:string}>
 */
function lp_seo_faq_pairs(): array {
	static $cached = null;
	if ( null !== $cached ) {
		return $cached;
	}

	$rows    = array();
	$post_id = is_singular() ? (int) get_queried_object_id() : 0;

	if ( $post_id && function_exists( 'get_field' ) ) {
		$sections = get_field( 'page_sections', $post_id );
		if ( is_array( $sections ) ) {
			foreach ( $sections as $section ) {
				if ( ! is_array( $section ) ) {
					continue;
				}
				$layout = str_replace( '_', '-', (string) ( $section['acf_fc_layout'] ?? '' ) );
				if ( 'faq' !== $layout ) {
					continue;
				}
				if ( ! empty( $section['items'] ) && is_array( $section['items'] ) ) {
					$rows = array_merge( $rows, $section['items'] );
				}
				if ( ! empty( $section['groups'] ) && is_array( $section['groups'] ) ) {
					foreach ( $section['groups'] as $group ) {
						if ( is_array( $group ) && ! empty( $group['items'] ) && is_array( $group['items'] ) ) {
							$rows = array_merge( $rows, $group['items'] );
						}
					}
				}
			}
		}
	}

	if ( ! $rows ) {
		$template = is_page() ? (string) get_page_template_slug() : '';
		$is_docs  = false !== strpos( $template, 'docs-faq' )
			|| ( is_singular( 'support' ) && function_exists( 'lp_docs_is_faq' ) && lp_docs_is_faq( get_queried_object() ) );
		if ( $is_docs ) {
			foreach ( lp_faq_default_groups() as $group ) {
				if ( ! empty( $group['items'] ) && is_array( $group['items'] ) ) {
					$rows = array_merge( $rows, $group['items'] );
				}
			}
		} elseif ( false !== strpos( $template, 'contact' ) ) {
			$rows = lp_faq_default_items();
		}
	}

	$cached = lp_seo_faq_pairs_from_rows( $rows );
	return $cached;
}

/**
 * FAQPage node when the current view actually shows a FAQ.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_faq_node(): ?array {
	$pairs = lp_seo_faq_pairs();
	if ( ! $pairs ) {
		return null;
	}

	$entities = array();
	foreach ( $pairs as $pair ) {
		$entities[] = array(
			'@type'          => 'Question',
			'name'           => $pair['question'],
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $pair['answer'],
			),
		);
	}

	return array(
		'@type'      => 'FAQPage',
		'mainEntity' => $entities,
	);
}

/**
 * Numeric GBP from a "£15" display string or a raw float.
 */
function lp_seo_gbp_amount( $value ): ?float {
	if ( is_numeric( $value ) ) {
		$amount = (float) $value;
		return $amount > 0 ? $amount : null;
	}
	if ( ! is_string( $value ) ) {
		return null;
	}
	$digits = preg_replace( '/[^0-9.]/', '', $value );
	if ( '' === $digits ) {
		return null;
	}
	$amount = (float) $digits;
	return $amount > 0 ? $amount : null;
}

/**
 * Offer for a class drop-in.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_class_offer( int $class_id, int $remaining = -1 ): ?array {
	$amount = null;
	$raw    = function_exists( 'lp_clasbpro_raw' ) ? lp_clasbpro_raw( $class_id ) : null;
	if ( is_array( $raw ) ) {
		$amount = lp_seo_gbp_amount( $raw['price'] ?? 0 );
	}
	if ( null === $amount && function_exists( 'lp_class_price_display' ) ) {
		$amount = lp_seo_gbp_amount( lp_class_price_display( $class_id ) );
	}
	if ( null === $amount ) {
		return null;
	}

	$offer = array(
		'@type'         => 'Offer',
		'price'         => $amount,
		'priceCurrency' => 'GBP',
		'url'           => get_permalink( $class_id ),
		'seller'        => array( '@id' => lp_seo_org_id() ),
	);

	if ( $remaining === 0 ) {
		$offer['availability'] = 'https://schema.org/SoldOut';
	} elseif ( $remaining > 0 ) {
		$offer['availability']      = 'https://schema.org/LimitedAvailability';
		$offer['inventoryLevel']    = $remaining;
	} else {
		$offer['availability'] = 'https://schema.org/InStock';
	}

	return $offer;
}

/**
 * Course + SportsEvent nodes for a class singular.
 *
 * @return array<int, array<string, mixed>>
 */
function lp_seo_class_nodes( int $class_id ): array {
	$nodes    = array();
	$permalink = (string) get_permalink( $class_id );
	$title     = get_the_title( $class_id );
	$about     = lp_seo_plain( (string) get_post_field( 'post_content', $class_id ) );
	$image     = lp_seo_image();

	$mins = 0;
	$raw  = function_exists( 'lp_clasbpro_raw' ) ? lp_clasbpro_raw( $class_id ) : null;
	if ( is_array( $raw ) ) {
		$mins = (int) ( $raw['duration'] ?? 0 );
	}

	$course = array(
		'@type'       => 'Course',
		'@id'         => $permalink . '#course',
		'name'        => $title,
		'url'         => $permalink,
		'provider'    => array( '@id' => lp_seo_org_id() ),
		'inLanguage'  => 'en-GB',
	);
	if ( '' !== $about ) {
		$course['description'] = lp_seo_clip( $about, 300 );
	}
	if ( $image ) {
		$course['image'] = $image['url'];
	}
	if ( $mins > 0 ) {
		$course['timeRequired'] = 'PT' . $mins . 'M';
	}
	$offer = lp_seo_class_offer( $class_id );
	if ( $offer ) {
		$course['offers'] = $offer;
	}

	$location_id = function_exists( 'lp_class_location_id' ) ? lp_class_location_id( $class_id ) : 0;
	if ( $location_id ) {
		$loc_post = get_post( $location_id );
		if ( $loc_post instanceof WP_Post ) {
			$place = lp_seo_place_node( $loc_post );
			if ( $place ) {
				$course['location'] = $place;
			}
		}
	}

	$nodes[] = $course;

	$sessions = function_exists( 'lp_class_upcoming_sessions' ) ? lp_class_upcoming_sessions( $class_id, 8 ) : array();
	$is_one_off = function_exists( 'lp_class_is_one_off' ) && lp_class_is_one_off( $class_id );

	foreach ( $sessions as $i => $session ) {
		if ( ! is_array( $session ) ) {
			continue;
		}
		$date = (string) ( $session['date'] ?? '' );
		$time = (string) ( $session['time'] ?? '00:00' );
		if ( '' === $date ) {
			continue;
		}
		if ( strlen( $time ) > 5 ) {
			$time = substr( $time, 0, 5 );
		}

		$start = date_create( $date . ' ' . $time, wp_timezone() );
		if ( ! $start ) {
			continue;
		}
		$end = clone $start;
		if ( $mins > 0 ) {
			$end->modify( '+' . $mins . ' minutes' );
		}

		$remaining = isset( $session['remaining'] ) ? (int) $session['remaining'] : -1;
		$event     = array(
			'@type'            => $is_one_off ? 'Event' : 'SportsEvent',
			'@id'              => $permalink . '#session-' . $date,
			'name'             => $title,
			'url'              => $permalink,
			'startDate'        => $start->format( DATE_ATOM ),
			'endDate'          => $end->format( DATE_ATOM ),
			'eventStatus'      => ! empty( $session['sold_out'] )
				? 'https://schema.org/EventScheduled'
				: 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'organizer'        => array( '@id' => lp_seo_org_id() ),
		);
		if ( '' !== $about ) {
			$event['description'] = lp_seo_clip( $about, 300 );
		}
		if ( $image ) {
			$event['image'] = $image['url'];
		}
		if ( ! empty( $course['location'] ) ) {
			$event['location'] = $course['location'];
		}
		$session_offer = lp_seo_class_offer( $class_id, $remaining );
		if ( $session_offer ) {
			if ( ! empty( $session['sold_out'] ) ) {
				$session_offer['availability'] = 'https://schema.org/SoldOut';
			}
			$valid = clone $start;
			$session_offer['validFrom'] = $valid->format( DATE_ATOM );
			$event['offers']            = $session_offer;
		}
		if ( $remaining >= 0 ) {
			$event['remainingAttendeeCapacity'] = $remaining;
		}
		if ( isset( $session['capacity'] ) ) {
			$event['maximumAttendeeCapacity'] = (int) $session['capacity'];
		}

		$nodes[] = $event;

		if ( $is_one_off && 0 === $i ) {
			break;
		}
	}

	$video_url = function_exists( 'get_field' ) ? (string) get_field( 'video_url', $class_id ) : '';
	$video_id  = function_exists( 'lp_youtube_id_from_url' ) ? lp_youtube_id_from_url( $video_url ) : '';
	if ( '' !== $video_id ) {
		$nodes[] = lp_seo_video_node(
			$title,
			$permalink,
			$video_id,
			$about,
			$image['url'] ?? ''
		);
	}

	return $nodes;
}

/**
 * VideoObject for a YouTube id.
 *
 * @return array<string, mixed>
 */
function lp_seo_video_node( string $name, string $url, string $youtube_id, string $description = '', string $thumb = '' ): array {
	$watch = 'https://www.youtube.com/watch?v=' . $youtube_id;
	$node  = array(
		'@type'        => 'VideoObject',
		'name'         => $name,
		'embedUrl'     => 'https://www.youtube.com/embed/' . $youtube_id,
		'contentUrl'   => $watch,
		'thumbnailUrl' => '' !== $thumb ? $thumb : 'https://i.ytimg.com/vi/' . $youtube_id . '/hqdefault.jpg',
		'publisher'    => array( '@id' => lp_seo_org_id() ),
	);
	if ( '' !== $description ) {
		$node['description'] = lp_seo_clip( $description, 300 );
	} else {
		$node['description'] = $name;
	}

	return $node;
}

/**
 * Tutorial VideoObject.
 *
 * @return array<string, mixed>|null
 */
function lp_seo_tutorial_node( int $post_id ): ?array {
	$video_id = '';
	if ( function_exists( 'get_field' ) ) {
		$video_id = lp_youtube_id_from_url( (string) get_field( 'video_id', $post_id ) );
		if ( '' === $video_id ) {
			$video_id = lp_youtube_id_from_url( (string) get_field( 'video_url', $post_id ) );
		}
	}
	if ( '' === $video_id ) {
		return null;
	}

	$desc  = lp_seo_description();
	$image = lp_seo_image();
	$node  = lp_seo_video_node(
		get_the_title( $post_id ),
		(string) get_permalink( $post_id ),
		$video_id,
		$desc,
		$image['url'] ?? ''
	);

	$secs = function_exists( 'lp_tutorial_runtime_seconds' ) ? lp_tutorial_runtime_seconds( $post_id ) : 0;
	if ( $secs > 0 ) {
		$node['duration'] = 'PT' . $secs . 'S';
	}

	$published = get_the_date( DATE_ATOM, $post_id );
	if ( $published ) {
		$node['uploadDate'] = $published;
	}

	return $node;
}

/**
 * Offer catalog from clasbpro packs (coupons / pricing).
 *
 * @return array<int, array<string, mixed>>
 */
function lp_seo_pack_offer_nodes(): array {
	if ( ! post_type_exists( 'clasbpro_pack' ) ) {
		return array();
	}

	$ids = array();
	if ( is_singular() && function_exists( 'get_field' ) ) {
		foreach ( array( 'drop_in_pack', 'five_pack', 'ten_pack' ) as $field ) {
			$id = absint( get_field( $field ) );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
	}

	if ( ! $ids ) {
		$ids = get_posts(
			array(
				'post_type'      => 'clasbpro_pack',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
	}

	$nodes = array();
	foreach ( $ids as $id ) {
		$id   = (int) $id;
		$data = null;
		if ( class_exists( '\IOROOT_STRIPE_BOOKINGS_PRO\Packs' ) ) {
			$data = \IOROOT_STRIPE_BOOKINGS_PRO\Packs::get_pack_data( $id );
		}
		$name  = is_array( $data ) ? (string) ( $data['name'] ?? get_the_title( $id ) ) : get_the_title( $id );
		$price = is_array( $data ) ? lp_seo_gbp_amount( $data['price'] ?? 0 ) : null;
		$desc  = is_array( $data ) ? lp_seo_plain( (string) ( $data['description'] ?? '' ) ) : '';
		if ( null === $price ) {
			continue;
		}

		$offer = array(
			'@type'         => 'Offer',
			'@id'           => home_url( '/#offer-pack-' . $id ),
			'name'          => $name,
			'price'         => $price,
			'priceCurrency' => 'GBP',
			'availability'  => 'https://schema.org/InStock',
			'seller'        => array( '@id' => lp_seo_org_id() ),
			'url'           => lp_seo_canonical_url(),
		);
		if ( '' !== $desc ) {
			$offer['description'] = $desc;
		}
		$uses = is_array( $data ) ? (int) ( $data['uses'] ?? $data['credits'] ?? 0 ) : 0;
		if ( $uses > 0 ) {
			$offer['eligibleQuantity'] = array(
				'@type'    => 'QuantitativeValue',
				'value'    => $uses,
				'unitText' => 'class',
			);
		}
		$nodes[] = $offer;
	}

	return $nodes;
}

/**
 * Whether the current view should emit pack Offers.
 */
function lp_seo_should_emit_packs(): bool {
	if ( is_front_page() ) {
		return true;
	}
	$template = is_page() ? (string) get_page_template_slug() : '';
	if ( false !== strpos( $template, 'coupons' ) ) {
		return true;
	}

	return is_page( 'coupons' );
}

/**
 * WebPage node for the current URL.
 *
 * @return array<string, mixed>
 */
function lp_seo_webpage_node(): array {
	$node = array(
		'@type'      => 'WebPage',
		'@id'        => lp_seo_canonical_url() . '#webpage',
		'url'        => lp_seo_canonical_url(),
		'name'       => wp_get_document_title(),
		'isPartOf'   => array( '@id' => lp_seo_website_id() ),
		'about'      => array( '@id' => lp_seo_org_id() ),
		'inLanguage' => get_bloginfo( 'language' ),
	);
	$desc = lp_seo_description();
	if ( '' !== $desc ) {
		$node['description'] = $desc;
	}
	$image = lp_seo_image();
	if ( $image ) {
		$node['primaryImageOfPage'] = $image['url'];
	}

	return $node;
}

/**
 * Full JSON-LD @graph for this response.
 *
 * @return array<string, mixed>
 */
function lp_seo_graph(): array {
	$graph = array(
		lp_seo_organization_node(),
		lp_seo_website_node(),
		lp_seo_webpage_node(),
	);

	$crumbs = lp_seo_breadcrumb_node();
	if ( $crumbs ) {
		$graph[] = $crumbs;
	}

	$faq = lp_seo_faq_node();
	if ( $faq ) {
		$graph[] = $faq;
	}

	if ( is_singular( 'clasbpro_class' ) ) {
		foreach ( lp_seo_class_nodes( (int) get_queried_object_id() ) as $node ) {
			$graph[] = $node;
		}
	}

	if ( is_singular( 'lp_tutorial' ) ) {
		$video = lp_seo_tutorial_node( (int) get_queried_object_id() );
		if ( $video ) {
			$graph[] = $video;
		}
	}

	if ( lp_seo_should_emit_packs() ) {
		foreach ( lp_seo_pack_offer_nodes() as $offer ) {
			$graph[] = $offer;
		}
	}

	return array(
		'@context' => 'https://schema.org',
		'@graph'   => apply_filters( 'lp_seo_graph', $graph ),
	);
}

/**
 * Print the JSON-LD script.
 */
function lp_seo_print_jsonld(): void {
	if ( is_feed() || is_robots() || wp_is_json_request() ) {
		return;
	}

	$json = wp_json_encode(
		lp_seo_graph(),
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
	);
	if ( ! is_string( $json ) || '' === $json ) {
		return;
	}

	echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded with HEX_TAG.
}
