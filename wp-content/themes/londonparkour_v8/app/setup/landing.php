<?php
/**
 * Landings — /go/{slug}/ pages for ads.
 *
 * The form and `wp lp landing <file.json>` write the same fields. An
 * incomplete payload is not published. The command publishes immediately,
 * updates the same slug in place, and always forces noindex.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

/**
 * Privacy or terms URL, then the legal page.
 *
 * @param string[] $slugs Support slugs to try first.
 */
function lp_landing_legal_url( array $slugs ): string {
	if ( function_exists( 'lp_docs_find_support' ) ) {
		$support = lp_docs_find_support( $slugs );
		if ( $support instanceof WP_Post ) {
			$link = get_permalink( $support );
			if ( is_string( $link ) && '' !== $link ) {
				return $link;
			}
		}
	}

	$page = get_page_by_path( 'legal' );
	if ( $page instanceof WP_Post ) {
		$link = get_permalink( $page );
		if ( is_string( $link ) && '' !== $link ) {
			return $link;
		}
	}

	return home_url( '/legal/' );
}

/**
 * Glyph ids in the sprite.
 *
 * @return array<string, true>
 */
function lp_landing_glyph_ids(): array {
	static $ids = null;
	if ( is_array( $ids ) ) {
		return $ids;
	}

	$ids  = array();
	$file = get_theme_file_path( 'assets/img/glyphs.svg' );
	$svg  = is_readable( $file ) ? file_get_contents( $file ) : '';
	if ( is_string( $svg ) && preg_match_all( '/id="(glyph-[^"]+)"/', $svg, $matches ) ) {
		foreach ( $matches[1] as $id ) {
			$ids[ $id ] = true;
		}
	}

	return $ids;
}

/**
 * Repeater rows, dropping blanks.
 *
 * @param mixed $rows Raw repeater.
 * @return array<int, array<string, mixed>>
 */
function lp_landing_rows( $rows ): array {
	if ( ! is_array( $rows ) ) {
		return array();
	}

	$out = array();
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$filled = false;
		foreach ( $row as $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				$filled = true;
				break;
			}
		}
		if ( $filled ) {
			$out[] = $row;
		}
	}

	return $out;
}

/**
 * Why this payload cannot be published. Empty means it can.
 *
 * @param array<string, mixed> $data Field values.
 * @return string[]
 */
function lp_landing_errors( array $data ): array {
	$errors = array();
	$need   = array(
		'title'              => __( 'Title', 'londonparkour_v8' ),
		'slug'               => __( 'Slug', 'londonparkour_v8' ),
		'hero_eyebrow'       => __( 'Hero eyebrow', 'londonparkour_v8' ),
		'hero_headline'      => __( 'Hero headline', 'londonparkour_v8' ),
		'hero_lead'          => __( 'Hero lead', 'londonparkour_v8' ),
		'statement_eyebrow'  => __( 'Statement eyebrow', 'londonparkour_v8' ),
		'statement_headline' => __( 'Statement headline', 'londonparkour_v8' ),
		'statement_quote'    => __( 'Statement side quote', 'londonparkour_v8' ),
		'faq_eyebrow'        => __( 'FAQ eyebrow', 'londonparkour_v8' ),
		'cta_headline'       => __( 'Closing headline', 'londonparkour_v8' ),
		'cta_subhead'        => __( 'Closing subhead', 'londonparkour_v8' ),
	);

	foreach ( $need as $key => $label ) {
		$value = $data[ $key ] ?? '';
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			$errors[] = sprintf(
				/* translators: %s: field label. */
				__( '%s is required.', 'londonparkour_v8' ),
				$label
			);
		}
	}

	$slug = isset( $data['slug'] ) && is_string( $data['slug'] ) ? $data['slug'] : '';
	if ( '' !== $slug && sanitize_title( $slug ) !== $slug ) {
		$errors[] = __( 'Slug may only use lowercase letters, numbers, and hyphens.', 'londonparkour_v8' );
	}

	$image = (int) ( $data['hero_image'] ?? 0 );
	if ( $image < 1 || 'attachment' !== get_post_type( $image ) || ! wp_attachment_is_image( $image ) ) {
		$errors[] = __( 'Photograph must be an image already in the media library.', 'londonparkour_v8' );
	}

	foreach (
		array(
			'hero_focus_x' => __( 'Subject, left to right', 'londonparkour_v8' ),
			'hero_focus_y' => __( 'Subject, top to bottom', 'londonparkour_v8' ),
		) as $lp_focus_key => $lp_focus_label
	) {
		if ( ! array_key_exists( $lp_focus_key, $data ) || '' === $data[ $lp_focus_key ] || null === $data[ $lp_focus_key ] ) {
			continue;
		}
		$lp_focus = $data[ $lp_focus_key ];
		if ( ! is_numeric( $lp_focus ) || (float) $lp_focus < 0 || (float) $lp_focus > 100 ) {
			$errors[] = sprintf(
				/* translators: %s: field label. */
				__( '%s must be a number from 0 to 100.', 'londonparkour_v8' ),
				$lp_focus_label
			);
		}
	}

	$glyphs     = lp_landing_glyph_ids();
	$principles = lp_landing_rows( $data['principles'] ?? array() );
	if ( 3 !== count( $principles ) ) {
		$errors[] = __( 'Statement needs exactly three principles.', 'londonparkour_v8' );
	}
	foreach ( $principles as $index => $row ) {
		$n       = $index + 1;
		$icon    = trim( (string) ( $row['icon_id'] ?? '' ) );
		$label   = trim( (string) ( $row['label'] ?? '' ) );
		$body    = trim( (string) ( $row['body'] ?? '' ) );
		if ( '' === $label || '' === $body || '' === $icon ) {
			$errors[] = sprintf(
				/* translators: %d: principle number. */
				__( 'Principle %d needs a glyph, a label, and a body.', 'londonparkour_v8' ),
				$n
			);
			continue;
		}
		if ( ! isset( $glyphs[ $icon ] ) ) {
			$errors[] = sprintf(
				/* translators: 1: principle number, 2: glyph id. */
				__( 'Principle %1$d glyph “%2$s” is not in the sprite.', 'londonparkour_v8' ),
				$n,
				$icon
			);
		}
	}

	$faqs = lp_landing_rows( $data['faq_items'] ?? array() );
	$complete_faqs = 0;
	foreach ( $faqs as $row ) {
		if ( '' !== trim( (string) ( $row['question'] ?? '' ) ) && '' !== trim( (string) ( $row['answer'] ?? '' ) ) ) {
			++$complete_faqs;
		}
	}
	if ( $complete_faqs < 3 ) {
		$errors[] = __( 'FAQ needs at least three questions, each with an answer.', 'londonparkour_v8' );
	}

	return $errors;
}

/**
 * Stored landing fields, plus title and slug.
 *
 * @param int $post_id Landing ID.
 * @return array<string, mixed>
 */
function lp_landing_fields_from_post( int $post_id ): array {
	$post = get_post( $post_id );
	$get  = static function ( string $name ) use ( $post_id ) {
		if ( ! function_exists( 'get_field' ) ) {
			return '';
		}
		$value = get_field( $name, $post_id );
		return is_string( $value ) ? $value : $value;
	};

	$image = $get( 'hero_image' );
	if ( is_array( $image ) ) {
		$image = (int) ( $image['ID'] ?? $image['id'] ?? 0 );
	}

	return array(
		'title'              => $post instanceof WP_Post ? $post->post_title : '',
		'slug'               => $post instanceof WP_Post ? $post->post_name : '',
		'hero_eyebrow'       => (string) $get( 'hero_eyebrow' ),
		'hero_headline'      => (string) $get( 'hero_headline' ),
		'hero_lead'          => (string) $get( 'hero_lead' ),
		'hero_image'         => (int) $image,
		'hero_focus_x'       => $get( 'hero_focus_x' ),
		'hero_focus_y'       => $get( 'hero_focus_y' ),
		'statement_eyebrow'  => (string) $get( 'statement_eyebrow' ),
		'statement_headline' => (string) $get( 'statement_headline' ),
		'statement_quote'    => (string) $get( 'statement_quote' ),
		'principles'         => $get( 'principles' ),
		'faq_eyebrow'        => (string) $get( 'faq_eyebrow' ),
		'faq_items'          => $get( 'faq_items' ),
		'cta_headline'       => (string) $get( 'cta_headline' ),
		'cta_subhead'        => (string) $get( 'cta_subhead' ),
	);
}

/**
 * Name a posted ACF tree so validation can read it.
 *
 * @param mixed $posted Raw $_POST['acf'] fragment.
 * @return mixed
 */
function lp_landing_name_posted( $posted ) {
	if ( ! is_array( $posted ) ) {
		return $posted;
	}

	$named = array();
	foreach ( $posted as $key => $value ) {
		$name = (string) $key;
		if ( is_string( $key ) && str_starts_with( $key, 'field_' ) && function_exists( 'acf_get_field' ) ) {
			$field = acf_get_field( $key );
			if ( is_array( $field ) && ! empty( $field['name'] ) ) {
				$name = (string) $field['name'];
			}
		}
		$named[ $name ] = lp_landing_name_posted( $value );
	}

	return $named;
}

/**
 * Values being saved from the editor.
 *
 * @param int $post_id Post ID.
 * @return array<string, mixed>
 */
function lp_landing_fields_from_request( int $post_id ): array {
	$stored = lp_landing_fields_from_post( $post_id );
	$posted = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? lp_landing_name_posted( wp_unslash( $_POST['acf'] ) ) : array();
	if ( ! is_array( $posted ) ) {
		$posted = array();
	}

	$data = array_merge( $stored, $posted );
	if ( isset( $_POST['post_title'] ) ) {
		$data['title'] = sanitize_text_field( wp_unslash( $_POST['post_title'] ) );
	}
	$slug = isset( $_POST['post_name'] ) ? sanitize_title( wp_unslash( $_POST['post_name'] ) ) : '';
	if ( '' === $slug && isset( $data['title'] ) && is_string( $data['title'] ) ) {
		$slug = sanitize_title( $data['title'] );
	}
	if ( '' !== $slug ) {
		$data['slug'] = $slug;
	}
	if ( isset( $data['hero_image'] ) && is_array( $data['hero_image'] ) ) {
		$data['hero_image'] = (int) ( $data['hero_image']['ID'] ?? $data['hero_image']['id'] ?? 0 );
	}

	return $data;
}

/**
 * Block an incomplete publish. Drafts save.
 *
 * @param array<string, mixed> $data    Slashed post data.
 * @param array<string, mixed> $postarr Raw post array.
 * @return array<string, mixed>
 */
function lp_landing_insert_data( array $data, array $postarr ): array {
	if ( 'lp_landing' !== ( $data['post_type'] ?? '' ) || 'publish' !== ( $data['post_status'] ?? '' ) ) {
		return $data;
	}
	if ( ! empty( $GLOBALS['lp_landing_cli'] ) || ! empty( $_POST['acf'] ) ) {
		return $data;
	}

	$post_id = (int) ( $postarr['ID'] ?? 0 );
	if ( $post_id && ! lp_landing_errors( lp_landing_fields_from_post( $post_id ) ) ) {
		return $data;
	}

	$data['post_status'] = 'draft';
	return $data;
}
add_filter( 'wp_insert_post_data', 'lp_landing_insert_data', 10, 2 );

/**
 * Same completeness check as the command, for the Publish button.
 */
function lp_landing_validate_save_post(): void {
	$post_id = (int) ( $_POST['post_ID'] ?? 0 );
	if ( $post_id < 1 || 'lp_landing' !== get_post_type( $post_id ) ) {
		return;
	}
	if ( 'publish' !== ( $_POST['post_status'] ?? '' ) ) {
		return;
	}
	if ( ! function_exists( 'acf_add_validation_error' ) ) {
		return;
	}

	$errors = lp_landing_errors( lp_landing_fields_from_request( $post_id ) );
	if ( $errors ) {
		acf_add_validation_error( '', implode( ' ', $errors ) );
	}
}
add_action( 'acf/validate_save_post', 'lp_landing_validate_save_post', 20 );

/**
 * New landings start hidden from search engines.
 *
 * @param mixed $value   Stored value.
 * @param mixed $post_id Post ID.
 * @return mixed
 */
function lp_landing_noindex_default( $value, $post_id ) {
	if ( is_string( $post_id ) && str_starts_with( $post_id, 'post_' ) ) {
		$post_id = substr( $post_id, 5 );
	}
	$post_id = (int) $post_id;
	if ( $post_id < 1 || 'lp_landing' !== get_post_type( $post_id ) ) {
		return $value;
	}
	if ( ! metadata_exists( 'post', $post_id, 'seo_noindex' ) ) {
		return 1;
	}
	return $value;
}
add_filter( 'acf/load_value/name=seo_noindex', 'lp_landing_noindex_default', 10, 2 );

/**
 * Hide a landing that has never saved the SEO box.
 *
 * @param bool $noindex Current decision.
 */
function lp_landing_noindex_filter( $noindex ) {
	if ( $noindex || ! is_singular( 'lp_landing' ) ) {
		return $noindex;
	}
	$post_id = (int) get_queried_object_id();
	if ( $post_id && ! metadata_exists( 'post', $post_id, 'seo_noindex' ) ) {
		return true;
	}
	return $noindex;
}
add_filter( 'lp_seo_noindex', 'lp_landing_noindex_filter' );

/**
 * Write the fixed landing sequence.
 *
 * @param int $post_id Landing ID.
 */
function lp_landing_render( int $post_id ): void {
	$fields     = lp_landing_fields_from_post( $post_id );
	$principles = array();
	foreach ( lp_landing_rows( $fields['principles'] ) as $row ) {
		$principles[] = array(
			'icon_id' => (string) ( $row['icon_id'] ?? '' ),
			'label'   => (string) ( $row['label'] ?? '' ),
			'body'    => (string) ( $row['body'] ?? '' ),
		);
	}

	$faqs = array();
	foreach ( lp_landing_rows( $fields['faq_items'] ) as $row ) {
		$faqs[] = array(
			'question' => (string) ( $row['question'] ?? '' ),
			'answer'   => (string) ( $row['answer'] ?? '' ),
		);
	}

	lp_render_block(
		'hero',
		array(
			'eyebrow'     => (string) $fields['hero_eyebrow'],
			'headline'    => (string) $fields['hero_headline'],
			'lead'        => (string) $fields['hero_lead'],
			'media'        => (int) $fields['hero_image'],
			'media_origin' => lp_hero_focal_point( $fields['hero_focus_x'] ?? 50, $fields['hero_focus_y'] ?? 50 ),
			'under_nav'    => false,
			'board_style' => 'next',
		)
	);

	lp_render_block(
		'statement',
		array(
			'eyebrow'    => (string) $fields['statement_eyebrow'],
			'since'      => 'SINCE 2018',
			'statement'  => (string) $fields['statement_headline'],
			'quote'      => (string) $fields['statement_quote'],
			'signature'  => '— Andy Pearson, Head Coach',
			'principles' => $principles,
		)
	);

	lp_render_block(
		'testimonials',
		array(
			'eyebrow'             => '03 — IN THEIR WORDS',
			'quote_source'        => 'latest',
			'quote_limit'         => 3,
			'allow_placeholders'  => false,
			'rotate'              => false,
			'show_see_all'        => false,
			'surface'             => 'band',
		)
	);

	lp_render_block(
		'faq',
		array(
			'meta_left'  => (string) $fields['faq_eyebrow'],
			'meta_right' => '',
			'items'      => $faqs,
			'show_aside' => false,
		)
	);

	lp_render_block(
		'cta',
		array(
			'kicker'         => '05 — START',
			'headline'       => (string) $fields['cta_headline'],
			'subhead'        => (string) $fields['cta_subhead'],
			'book'           => true,
			'primary_action' => array(
				'title' => 'BOOK YOUR FIRST CLASS',
				'url'   => '',
			),
		)
	);
}

/**
 * Find a landing by slug, any status.
 *
 * @param string $slug Post name.
 */
function lp_landing_find( string $slug ): ?WP_Post {
	$posts = get_posts(
		array(
			'post_type'      => 'lp_landing',
			'name'           => $slug,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$post = $posts[0] ?? null;
	return $post instanceof WP_Post ? $post : null;
}

/**
 * Store the writable fields. Noindex is always on.
 *
 * @param int                  $post_id Landing ID.
 * @param array<string, mixed> $data    Validated payload.
 */
function lp_landing_write_fields( int $post_id, array $data ): void {
	if ( ! function_exists( 'update_field' ) ) {
		return;
	}

	$map = array(
		'hero_eyebrow',
		'hero_headline',
		'hero_lead',
		'hero_image',
		'hero_focus_x',
		'hero_focus_y',
		'statement_eyebrow',
		'statement_headline',
		'statement_quote',
		'principles',
		'faq_eyebrow',
		'faq_items',
		'cta_headline',
		'cta_subhead',
	);
	foreach ( $map as $name ) {
		update_field( $name, $data[ $name ] ?? '', $post_id );
	}
	update_field( 'seo_noindex', 1, $post_id );
	update_post_meta( $post_id, 'seo_noindex', '1' );
}

/**
 * Publish a landing from JSON. Incomplete input writes nothing.
 *
 * @param array<string, mixed> $data Payload.
 * @return int Post ID.
 */
function lp_landing_publish( array $data ): int {
	$errors = lp_landing_errors( $data );
	if ( $errors ) {
		throw new InvalidArgumentException( implode( ' ', $errors ) );
	}

	$slug = (string) $data['slug'];
	$title = (string) $data['title'];
	$existing = lp_landing_find( $slug );
	$created  = false;

	$GLOBALS['lp_landing_cli'] = true;
	try {
		if ( $existing instanceof WP_Post ) {
			$post_id = (int) $existing->ID;
		} else {
			$inserted = wp_insert_post(
				array(
					'post_type'   => 'lp_landing',
					'post_status' => 'draft',
					'post_title'  => $title,
					'post_name'   => $slug,
				),
				true
			);
			if ( is_wp_error( $inserted ) ) {
				throw new RuntimeException( $inserted->get_error_message() );
			}
			$post_id = (int) $inserted;
			$created = true;
		}

		lp_landing_write_fields( $post_id, $data );

		$updated = wp_update_post(
			array(
				'ID'          => $post_id,
				'post_title'  => $title,
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $updated ) ) {
			throw new RuntimeException( $updated->get_error_message() );
		}
	} catch ( Throwable $error ) {
		if ( $created && isset( $post_id ) ) {
			wp_delete_post( $post_id, true );
		}
		throw $error;
	} finally {
		unset( $GLOBALS['lp_landing_cli'] );
	}

	return $post_id;
}

/**
 * `wp lp landing <file.json>`
 *
 * @param string[] $args Positional args.
 */
function lp_cli_landing( array $args ): void {
	$file = $args[0] ?? '';
	if ( '' === $file || ! is_readable( $file ) ) {
		WP_CLI::error( 'Pass a readable JSON file.' );
	}

	$raw = file_get_contents( $file );
	$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
	if ( ! is_array( $data ) ) {
		WP_CLI::error( 'The file is not a JSON object. Nothing was written.' );
	}

	try {
		$post_id = lp_landing_publish( $data );
	} catch ( InvalidArgumentException $error ) {
		WP_CLI::error( $error->getMessage() . ' Nothing was written.' );
	} catch ( Throwable $error ) {
		WP_CLI::error( $error->getMessage() );
	}

	$url = get_permalink( $post_id );
	WP_CLI::success( 'Published landing ' . $post_id . ( is_string( $url ) ? ' ' . $url : '' ) );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'lp landing', 'lp_cli_landing' );
}
