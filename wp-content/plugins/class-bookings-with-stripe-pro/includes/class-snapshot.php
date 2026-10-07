<?php
/**
 * Clasbpro data snapshot: export / inspect / import (replace).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

abstract class Snapshot {

	public const FORMAT    = 1;
	public const JSON_NAME = 'snapshot.json';

	/**
	 * @return list<string>
	 */
	public static function post_types(): array {
		return [
			CPT::CLASS_PT,
			CPT::PACK_PT,
			CPT::MANUAL_COUPON_PT,
			CPT::BOOKING_PT,
			CPT::PACK_PURCHASE_PT,
		];
	}

	public static function rewrite_site_urls( string $text, string $from, string $to ): string {
		$from = untrailingslashit( trim( $from ) );
		$to   = untrailingslashit( trim( $to ) );
		if ( '' === $from || '' === $to || $from === $to || '' === $text ) {
			return $text;
		}

		$pairs = [];
		foreach ( self::url_variants( $from ) as $variant ) {
			$pairs[ $variant ] = $to;
		}

		uksort(
			$pairs,
			static function ( string $a, string $b ): int {
				return strlen( $b ) <=> strlen( $a );
			}
		);

		return strtr( $text, $pairs );
	}

	/**
	 * @return string|\WP_Error
	 */
	public static function export_to_temp_zip() {
		if ( ! class_exists( \ZipArchive::class ) ) {
			return new \WP_Error( 'clasbpro_zip', __( 'PHP ZipArchive is required to export.', 'class-bookings-with-stripe-pro' ) );
		}

		$dir = trailingslashit( get_temp_dir() );
		$path = wp_unique_filename( $dir, 'clasbpro-export-' . gmdate( 'Ymd-His' ) . '.zip' );
		$full = $dir . $path;

		return self::export_to_path( $full );
	}

	/**
	 * @return string|\WP_Error
	 */
	public static function export_to_path( string $path ) {
		if ( ! class_exists( \ZipArchive::class ) ) {
			return new \WP_Error( 'clasbpro_zip', __( 'PHP ZipArchive is required to export.', 'class-bookings-with-stripe-pro' ) );
		}

		@set_time_limit( 0 );

		$built = self::build();
		$json  = wp_json_encode( $built['snapshot'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) || '' === $json ) {
			return new \WP_Error( 'clasbpro_json', __( 'Could not encode the snapshot.', 'class-bookings-with-stripe-pro' ) );
		}

		$zip = new \ZipArchive();
		$dir = dirname( $path );
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'clasbpro_dir', __( 'Could not create the export directory.', 'class-bookings-with-stripe-pro' ) );
		}

		$opened = $zip->open( $path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
		if ( true !== $opened ) {
			return new \WP_Error( 'clasbpro_zip_open', __( 'Could not create the export zip.', 'class-bookings-with-stripe-pro' ) );
		}

		$zip->addFromString( self::JSON_NAME, $json );
		foreach ( $built['media_files'] as $rel => $abs ) {
			if ( is_readable( $abs ) ) {
				$zip->addFile( $abs, $rel );
			}
		}
		$zip->close();

		return $path;
	}

	/**
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function inspect( string $zip_path ) {
		$loaded = self::load_zip( $zip_path );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		$snap    = $loaded['snapshot'];
		$posts   = is_array( $snap['posts'] ?? null ) ? $snap['posts'] : [];
		$counts  = [
			'classes'         => 0,
			'bookings'        => 0,
			'packs'           => 0,
			'pack_purchases'  => 0,
			'coupons'         => 0,
			'media'           => is_array( $snap['media'] ?? null ) ? count( $snap['media'] ) : 0,
			'queue'           => is_array( $snap['queue'] ?? null ) ? count( $snap['queue'] ) : 0,
			'pending_queue'   => 0,
		];
		foreach ( $posts as $post ) {
			$type = (string) ( $post['post_type'] ?? '' );
			if ( CPT::CLASS_PT === $type ) {
				++$counts['classes'];
			} elseif ( CPT::BOOKING_PT === $type ) {
				++$counts['bookings'];
			} elseif ( CPT::PACK_PT === $type ) {
				++$counts['packs'];
			} elseif ( CPT::PACK_PURCHASE_PT === $type ) {
				++$counts['pack_purchases'];
			} elseif ( CPT::MANUAL_COUPON_PT === $type ) {
				++$counts['coupons'];
			}
		}
		foreach ( is_array( $snap['queue'] ?? null ) ? $snap['queue'] : [] as $row ) {
			if ( Scheduled_Emails::STATUS_PENDING === (string) ( $row['status'] ?? '' ) ) {
				++$counts['pending_queue'];
			}
		}

		$source_ver = (string) ( $snap['plugin_version'] ?? '' );
		$warnings   = [];
		$will_apply = [
			__( 'Classes, bookings, coupon packs, coupon codes, and coupon purchases', 'class-bookings-with-stripe-pro' ),
			__( 'Plugin settings and HTML email bodies (not Stripe/Mailchimp secrets)', 'class-bookings-with-stripe-pro' ),
			__( 'Scheduled email queue', 'class-bookings-with-stripe-pro' ),
			__( 'Result pages (updated in place)', 'class-bookings-with-stripe-pro' ),
			__( 'Bundled media files', 'class-bookings-with-stripe-pro' ),
		];
		$will_skip  = [
			__( 'Stripe and Mailchimp secrets — destination keys are kept', 'class-bookings-with-stripe-pro' ),
			__( 'Form theme files in the WordPress theme directory', 'class-bookings-with-stripe-pro' ),
		];

		$missing_fields = self::missing_extra_field_names( $posts );
		if ( $missing_fields ) {
			$will_skip[] = sprintf(
				/* translators: %s: comma-separated ACF field names */
				__( 'Extra ACF values with no matching field on this site: %s', 'class-bookings-with-stripe-pro' ),
				implode( ', ', $missing_fields )
			);
		}

		if ( $source_ver && version_compare( $source_ver, CLASBOWPRO_VERSION, '>' ) ) {
			$warnings[] = sprintf(
				/* translators: 1: zip plugin version, 2: this site plugin version */
				__( 'This zip was exported from plugin %1$s; this site is %2$s. Import will still run. Newer fields may be stored as raw meta and ignored by this version.', 'class-bookings-with-stripe-pro' ),
				$source_ver,
				CLASBOWPRO_VERSION
			);
			$will_skip[] = __( 'Settings this plugin version does not know about (kept as raw options/meta)', 'class-bookings-with-stripe-pro' );
		}

		$from = untrailingslashit( (string) ( $snap['home_url'] ?? '' ) );
		$to   = untrailingslashit( home_url() );

		$loaded['zip']->close();

		return [
			'format'           => (int) ( $snap['format'] ?? 0 ),
			'plugin_version'   => $source_ver,
			'exported_at'      => (string) ( $snap['exported_at'] ?? '' ),
			'counts'           => $counts,
			'includes_secrets' => false,
			'url_rewrite'      => [
				'from' => $from,
				'to'   => $to,
			],
			'warnings'         => $warnings,
			'will_apply'       => $will_apply,
			'will_skip'        => $will_skip,
			'missing_fields'   => $missing_fields,
		];
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>|\WP_Error
	 */
	public static function import_from_zip( string $zip_path, array $args = [] ) {
		if ( empty( $args['confirm'] ) ) {
			return new \WP_Error( 'clasbpro_confirm', __( 'Import requires confirmation.', 'class-bookings-with-stripe-pro' ) );
		}
		if ( ! class_exists( \ZipArchive::class ) ) {
			return new \WP_Error( 'clasbpro_zip', __( 'PHP ZipArchive is required to import.', 'class-bookings-with-stripe-pro' ) );
		}

		@set_time_limit( 0 );

		$loaded = self::load_zip( $zip_path );
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		$author_id = isset( $args['author_id'] ) ? (int) $args['author_id'] : get_current_user_id();
		if ( $author_id <= 0 ) {
			$author_id = 1;
		}

		$backup = '';
		if ( ! empty( $args['force_backup'] ) ) {
			$backup_path = self::backup_dir() . 'clasbpro-backup-before-import-' . gmdate( 'Ymd-His' ) . '.zip';
			$written     = self::export_to_path( $backup_path );
			if ( is_wp_error( $written ) ) {
				return $written;
			}
			$backup = (string) $written;
		}

		self::wipe();

		$maps = self::restore( $loaded['snapshot'], $loaded['zip'], $author_id );
		if ( is_wp_error( $maps ) ) {
			return $maps;
		}

		$inspect = self::inspect( $zip_path );
		if ( is_wp_error( $inspect ) ) {
			$inspect = [];
		}

		return [
			'backup'  => $backup,
			'maps'    => $maps,
			'inspect' => $inspect,
		];
	}

	public static function backup_dir(): string {
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( (string) ( $uploads['basedir'] ?? '' ) ) . 'clasbpro-backups/';
		wp_mkdir_p( $dir );
		return $dir;
	}

	/**
	 * @return array{snapshot: array<string, mixed>, zip: \ZipArchive}|\WP_Error
	 */
	private static function load_zip( string $zip_path ) {
		if ( ! is_readable( $zip_path ) ) {
			return new \WP_Error( 'clasbpro_zip_missing', __( 'The import file could not be read.', 'class-bookings-with-stripe-pro' ) );
		}

		$zip = new \ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new \WP_Error( 'clasbpro_zip_open', __( 'The import file is not a valid zip.', 'class-bookings-with-stripe-pro' ) );
		}

		$raw = $zip->getFromName( self::JSON_NAME );
		if ( ! is_string( $raw ) || '' === $raw ) {
			$zip->close();
			return new \WP_Error( 'clasbpro_json_missing', __( 'The zip is missing snapshot.json.', 'class-bookings-with-stripe-pro' ) );
		}

		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			$zip->close();
			return new \WP_Error( 'clasbpro_json_bad', __( 'snapshot.json is not valid JSON.', 'class-bookings-with-stripe-pro' ) );
		}

		return [
			'snapshot' => $data,
			'zip'      => $zip,
		];
	}

	/**
	 * @return array{snapshot: array<string, mixed>, media_files: array<string, string>}
	 */
	private static function build(): array {
		$posts = self::collect_posts();
		$pages = self::collect_pages();
		$media = self::collect_media( $posts, $pages );

		$snapshot = [
			'format'         => self::FORMAT,
			'plugin_version' => CLASBOWPRO_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'home_url'       => home_url(),
			'site_url'       => site_url(),
			'posts'          => $posts,
			'options'        => self::collect_options(),
			'queue'          => self::collect_queue(),
			'pages'          => $pages,
			'media'          => $media['items'],
		];

		return [
			'snapshot'    => $snapshot,
			'media_files' => $media['files'],
		];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function collect_posts(): array {
		$out = [];
		$query = new \WP_Query(
			[
				'post_type'              => self::post_types(),
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'suppress_filters'       => true,
			]
		);

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			if ( in_array( $post->post_status, [ 'auto-draft' ], true ) ) {
				continue;
			}
			if ( 'revision' === $post->post_type ) {
				continue;
			}

			$out[] = [
				'ID'           => (int) $post->ID,
				'post_type'    => $post->post_type,
				'post_status'  => $post->post_status,
				'post_title'   => $post->post_title,
				'post_name'    => $post->post_name,
				'post_content' => $post->post_content,
				'post_excerpt' => $post->post_excerpt,
				'post_date'    => $post->post_date,
				'post_date_gmt'=> $post->post_date_gmt,
				'menu_order'   => (int) $post->menu_order,
				'meta'         => self::export_meta( (int) $post->ID ),
			];
		}

		return $out;
	}

	/**
	 * @return array<string, array<int, mixed>>
	 */
	private static function export_meta( int $post_id ): array {
		$all = get_post_meta( $post_id );
		if ( ! is_array( $all ) ) {
			return [];
		}
		unset( $all['_edit_lock'], $all['_edit_last'] );
		$out = [];
		foreach ( $all as $key => $values ) {
			$out[ (string) $key ] = array_map(
				static function ( $value ) {
					return maybe_unserialize( $value );
				},
				is_array( $values ) ? $values : [ $values ]
			);
		}
		return $out;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function collect_options(): array {
		global $wpdb;

		$out  = [];
		$like = $wpdb->esc_like( 'clasbpro_' ) . '%';
		$like_hidden = $wpdb->esc_like( '_clasbpro_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like,
				$like_hidden
			),
			ARRAY_A
		);
		if ( ! is_array( $rows ) ) {
			return [];
		}

		foreach ( $rows as $row ) {
			$name = (string) ( $row['option_name'] ?? '' );
			if ( '' === $name || self::is_skipped_option( $name ) ) {
				continue;
			}
			$out[ $name ] = maybe_unserialize( $row['option_value'] ?? '' );
		}

		return $out;
	}

	private static function is_skipped_option( string $name ): bool {
		if ( 0 === strpos( $name, '_transient_' ) || 0 === strpos( $name, '_site_transient_' ) ) {
			return true;
		}
		foreach ( Secrets::field_names() as $field ) {
			foreach ( [ Constants::OPTIONS_POST_ID . '_' . $field, '_' . Constants::OPTIONS_POST_ID . '_' . $field, 'options_' . $field, '_options_' . $field ] as $key ) {
				if ( $name === $key ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function collect_queue(): array {
		global $wpdb;
		$table = Scheduled_Emails::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return [];
		}
		foreach ( $rows as &$row ) {
			unset( $row['id'] );
		}
		unset( $row );
		return $rows;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private static function collect_pages(): array {
		$out = [];
		foreach ( Result_Pages::definitions() as $key => $cfg ) {
			$page_id = (int) Helpers::get_option( $cfg['option'], 0 );
			if ( $page_id <= 0 ) {
				$page = get_page_by_path( $cfg['slug'], OBJECT, 'page' );
				$page_id = $page instanceof \WP_Post ? (int) $page->ID : 0;
			}
			$post = $page_id ? get_post( $page_id ) : null;
			if ( ! $post instanceof \WP_Post ) {
				$out[ $key ] = [
					'slug'         => $cfg['slug'],
					'title'        => $cfg['title'],
					'content'      => $cfg['content'],
					'status'       => 'publish',
					'meta_key'     => $cfg['meta'],
					'option'       => $cfg['option'],
					'field_key'    => $cfg['field_key'],
				];
				continue;
			}
			$out[ $key ] = [
				'ID'           => (int) $post->ID,
				'slug'         => $post->post_name,
				'title'        => $post->post_title,
				'content'      => $post->post_content,
				'status'       => $post->post_status,
				'meta_key'     => $cfg['meta'],
				'option'       => $cfg['option'],
				'field_key'    => $cfg['field_key'],
			];
		}
		return $out;
	}

	/**
	 * @param list<array<string, mixed>>                 $posts
	 * @param array<string, array<string, mixed>>        $pages
	 * @return array{items: list<array<string, mixed>>, files: array<string, string>}
	 */
	private static function collect_media( array $posts, array $pages ): array {
		$ids = [];
		foreach ( $posts as $post ) {
			self::harvest_attachment_ids( $post['meta'] ?? [], $ids );
			self::harvest_attachment_ids( $post['post_content'] ?? '', $ids );
		}
		foreach ( $pages as $page ) {
			self::harvest_attachment_ids( $page['content'] ?? '', $ids );
		}

		$items = [];
		$files = [];
		foreach ( array_keys( $ids ) as $id ) {
			$id = (int) $id;
			if ( $id <= 0 || 'attachment' !== get_post_type( $id ) ) {
				continue;
			}
			$path = get_attached_file( $id );
			if ( ! is_string( $path ) || ! is_readable( $path ) ) {
				continue;
			}
			$rel = 'media/' . $id . '/' . basename( $path );
			$items[] = [
				'ID'       => $id,
				'file'     => $rel,
				'filename' => basename( $path ),
				'mime'     => (string) get_post_mime_type( $id ),
				'alt'      => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				'title'    => get_the_title( $id ),
			];
			$files[ $rel ] = $path;
		}

		return [
			'items' => $items,
			'files' => $files,
		];
	}

	/**
	 * @param mixed             $value
	 * @param array<int, true> $ids
	 */
	private static function harvest_attachment_ids( $value, array &$ids ): void {
		if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
			$id = (int) $value;
			if ( $id > 0 && 'attachment' === get_post_type( $id ) ) {
				$ids[ $id ] = true;
			}
			return;
		}
		if ( is_array( $value ) ) {
			if ( isset( $value['ID'] ) && is_numeric( $value['ID'] ) ) {
				$id = (int) $value['ID'];
				if ( $id > 0 && 'attachment' === get_post_type( $id ) ) {
					$ids[ $id ] = true;
				}
			}
			foreach ( $value as $item ) {
				self::harvest_attachment_ids( $item, $ids );
			}
		}
	}

	private static function wipe(): void {
		$ids = get_posts(
			[
				'post_type'              => self::post_types(),
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
			]
		);
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}

		global $wpdb;
		$table = Scheduled_Emails::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$table}" );

		$like = $wpdb->esc_like( 'clasbpro_' ) . '%';
		$like_hidden = $wpdb->esc_like( '_clasbpro_' ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like,
				$like_hidden
			)
		);
		if ( is_array( $option_names ) ) {
			foreach ( $option_names as $name ) {
				$name = (string) $name;
				if ( self::is_skipped_option( $name ) ) {
					continue;
				}
				delete_option( $name );
			}
		}
	}

	/**
	 * @param array<string, mixed> $snap
	 * @return array{posts: array<int, int>, attachments: array<int, int>}|\WP_Error
	 */
	private static function restore( array $snap, \ZipArchive $zip, int $author_id ) {
		$from = (string) ( $snap['home_url'] ?? '' );
		$to   = home_url();

		$attachment_map = self::restore_media( is_array( $snap['media'] ?? null ) ? $snap['media'] : [], $zip, $author_id );
		$id_map         = [];

		$posts = is_array( $snap['posts'] ?? null ) ? $snap['posts'] : [];
		$order = array_flip( self::post_types() );
		usort(
			$posts,
			static function ( array $a, array $b ) use ( $order ): int {
				$ta = $order[ (string) ( $a['post_type'] ?? '' ) ] ?? 99;
				$tb = $order[ (string) ( $b['post_type'] ?? '' ) ] ?? 99;
				if ( $ta !== $tb ) {
					return $ta <=> $tb;
				}
				return ( (int) ( $a['ID'] ?? 0 ) ) <=> ( (int) ( $b['ID'] ?? 0 ) );
			}
		);

		foreach ( $posts as $post ) {
			$old_id = (int) ( $post['ID'] ?? 0 );
			$new_id = wp_insert_post(
				[
					'post_type'    => (string) ( $post['post_type'] ?? '' ),
					'post_status'  => (string) ( $post['post_status'] ?? 'publish' ),
					'post_title'   => (string) ( $post['post_title'] ?? '' ),
					'post_name'    => (string) ( $post['post_name'] ?? '' ),
					'post_content' => self::rewrite_site_urls( (string) ( $post['post_content'] ?? '' ), $from, $to ),
					'post_excerpt' => self::rewrite_site_urls( (string) ( $post['post_excerpt'] ?? '' ), $from, $to ),
					'post_date'    => (string) ( $post['post_date'] ?? '' ),
					'post_date_gmt'=> (string) ( $post['post_date_gmt'] ?? '' ),
					'menu_order'   => (int) ( $post['menu_order'] ?? 0 ),
					'post_author'  => $author_id,
				],
				true
			);
			if ( is_wp_error( $new_id ) || (int) $new_id <= 0 ) {
				continue;
			}
			if ( $old_id > 0 ) {
				$id_map[ $old_id ] = (int) $new_id;
			}
		}

		$maps = $id_map + $attachment_map;

		foreach ( $posts as $post ) {
			$old_id = (int) ( $post['ID'] ?? 0 );
			$new_id = $id_map[ $old_id ] ?? 0;
			if ( $new_id <= 0 ) {
				continue;
			}
			$meta = is_array( $post['meta'] ?? null ) ? $post['meta'] : [];
			self::restore_meta( $new_id, $meta, $maps, $from, $to );
		}

		self::restore_options( is_array( $snap['options'] ?? null ) ? $snap['options'] : [], $maps, $from, $to );
		self::restore_result_pages( is_array( $snap['pages'] ?? null ) ? $snap['pages'] : [], $from, $to, $author_id );
		self::restore_queue( is_array( $snap['queue'] ?? null ) ? $snap['queue'] : [], $id_map );
		self::rewrite_site_content( $id_map, $from, $to );

		$zip->close();

		return [
			'posts'       => $id_map,
			'attachments' => $attachment_map,
		];
	}

	/**
	 * @param list<array<string, mixed>> $items
	 * @return array<int, int>
	 */
	private static function restore_media( array $items, \ZipArchive $zip, int $author_id ): array {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$map = [];
		foreach ( $items as $item ) {
			$old_id = (int) ( $item['ID'] ?? 0 );
			$rel    = (string) ( $item['file'] ?? '' );
			if ( $old_id <= 0 || '' === $rel ) {
				continue;
			}
			$blob = $zip->getFromName( $rel );
			if ( ! is_string( $blob ) || '' === $blob ) {
				continue;
			}

			$filename = sanitize_file_name( (string) ( $item['filename'] ?? basename( $rel ) ) );
			$tmp      = wp_tempnam( $filename );
			if ( ! $tmp || false === file_put_contents( $tmp, $blob ) ) {
				continue;
			}

			$file_array = [
				'name'     => $filename,
				'tmp_name' => $tmp,
			];
			$new_id = media_handle_sideload( $file_array, 0, (string) ( $item['title'] ?? '' ) );
			if ( is_wp_error( $new_id ) ) {
				@unlink( $tmp );
				continue;
			}
			if ( ! empty( $item['alt'] ) ) {
				update_post_meta( (int) $new_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $item['alt'] ) );
			}
			wp_update_post(
				[
					'ID'          => (int) $new_id,
					'post_author' => $author_id,
				]
			);
			$map[ $old_id ] = (int) $new_id;
		}

		return $map;
	}

	/**
	 * @param array<string, array<int, mixed>> $meta
	 * @param array<int, int>                  $maps
	 */
	private static function restore_meta( int $post_id, array $meta, array $maps, string $from, string $to ): void {
		foreach ( $meta as $key => $values ) {
			$key = (string) $key;
			if ( in_array( $key, [ '_edit_lock', '_edit_last' ], true ) ) {
				continue;
			}
			if ( ! is_array( $values ) ) {
				$values = [ $values ];
			}
			delete_post_meta( $post_id, $key );
			foreach ( $values as $value ) {
				if ( self::should_remap_key( $key ) ) {
					$value = self::remap_value( $value, $maps );
				}
				$value = self::rewrite_value_urls( $value, $from, $to );
				add_post_meta( $post_id, $key, $value );
			}
		}
	}

	/**
	 * @param array<string, mixed> $options
	 * @param array<int, int>      $maps
	 */
	private static function restore_options( array $options, array $maps, string $from, string $to ): void {
		foreach ( $options as $name => $value ) {
			$name = (string) $name;
			if ( self::is_skipped_option( $name ) ) {
				continue;
			}
			if ( self::should_remap_key( $name ) ) {
				$value = self::remap_value( $value, $maps );
			}
			$value = self::rewrite_value_urls( $value, $from, $to );
			update_option( $name, $value, false );
		}
	}

	/**
	 * @param array<string, array<string, mixed>> $pages
	 */
	private static function restore_result_pages( array $pages, string $from, string $to, int $author_id ): void {
		foreach ( Result_Pages::definitions() as $key => $cfg ) {
			$data = is_array( $pages[ $key ] ?? null ) ? $pages[ $key ] : [];
			$title   = (string) ( $data['title'] ?? $cfg['title'] );
			$slug    = sanitize_title( (string) ( $data['slug'] ?? $cfg['slug'] ) );
			$content = self::rewrite_site_urls( (string) ( $data['content'] ?? $cfg['content'] ), $from, $to );
			$status  = (string) ( $data['status'] ?? 'publish' );

			$page_id = self::find_result_page( $cfg, $slug );
			$payload = [
				'post_type'    => 'page',
				'post_status'  => $status ?: 'publish',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_author'  => $author_id,
			];
			if ( $page_id > 0 ) {
				$payload['ID'] = $page_id;
				wp_update_post( $payload );
			} else {
				$page_id = wp_insert_post( $payload );
			}
			$page_id = (int) $page_id;
			if ( $page_id <= 0 ) {
				continue;
			}
			update_post_meta( $page_id, $cfg['meta'], '1' );
			update_option( Constants::OPTIONS_POST_ID . '_' . $cfg['option'], $page_id, false );
			update_option( '_' . Constants::OPTIONS_POST_ID . '_' . $cfg['option'], $cfg['field_key'], false );
			if ( function_exists( 'update_field' ) ) {
				update_field( $cfg['option'], $page_id, Constants::OPTIONS_POST_ID );
			}
		}
	}

	/**
	 * @param array{slug: string, meta: string} $cfg
	 */
	private static function find_result_page( array $cfg, string $slug ): int {
		$by_meta = get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => $cfg['meta'],
				'meta_value'     => '1',
			]
		);
		if ( ! empty( $by_meta[0] ) ) {
			return (int) $by_meta[0];
		}
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		return $page instanceof \WP_Post ? (int) $page->ID : 0;
	}

	/**
	 * @param list<array<string, mixed>> $rows
	 * @param array<int, int>            $id_map
	 */
	private static function restore_queue( array $rows, array $id_map ): void {
		global $wpdb;
		$table = Scheduled_Emails::table_name();
		foreach ( $rows as $row ) {
			$booking_old = (int) ( $row['booking_id'] ?? 0 );
			$class_old   = (int) ( $row['class_id'] ?? 0 );
			$booking_new = $id_map[ $booking_old ] ?? 0;
			if ( $booking_new <= 0 ) {
				continue;
			}
			unset( $row['id'] );
			$row['booking_id'] = $booking_new;
			$row['class_id']   = $id_map[ $class_old ] ?? 0;
			$row['subject_tpl'] = isset( $row['subject_tpl'] ) ? (string) $row['subject_tpl'] : '';
			$row['body_tpl']    = isset( $row['body_tpl'] ) ? (string) $row['body_tpl'] : '';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( $table, $row );
		}
	}

	/**
	 * @param array<int, int> $id_map
	 */
	private static function rewrite_site_content( array $id_map, string $from, string $to ): void {
		if ( ! $id_map && ( untrailingslashit( $from ) === untrailingslashit( $to ) ) ) {
			// Still rewrite shortcodes even when URLs match.
		}

		$query = new \WP_Query(
			[
				'post_type'              => 'any',
				'post_status'            => 'any',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
			]
		);

		foreach ( $query->posts as $post_id ) {
			$post_id = (int) $post_id;
			$post    = get_post( $post_id );
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}
			if ( in_array( $post->post_type, [ 'revision', 'attachment' ], true ) ) {
				continue;
			}

			$content = self::rewrite_site_urls( (string) $post->post_content, $from, $to );
			$content = self::rewrite_shortcode_ids( $content, $id_map );
			if ( $content !== (string) $post->post_content ) {
				wp_update_post(
					[
						'ID'           => $post_id,
						'post_content' => $content,
					]
				);
			}

			$elementor = get_post_meta( $post_id, '_elementor_data', true );
			if ( is_string( $elementor ) && '' !== $elementor ) {
				$updated = self::rewrite_elementor_data( $elementor, $id_map );
				$updated = self::rewrite_site_urls( $updated, $from, $to );
				if ( $updated !== $elementor ) {
					update_post_meta( $post_id, '_elementor_data', wp_slash( $updated ) );
				}
			}
		}
	}

	/**
	 * @param array<int, int> $id_map
	 */
	public static function rewrite_shortcode_ids( string $content, array $id_map ): string {
		if ( '' === $content || ! $id_map ) {
			return $content;
		}

		$tags = array_merge(
			[
				Constants::SHORTCODE_BOOKING,
				Constants::SHORTCODE_SCHEDULE,
				Constants::SHORTCODE_PACKS,
				Constants::SHORTCODE_PACKS_LEGACY,
				Constants::SHORTCODE_STATUS,
			],
			Constants::LEGACY_SHORTCODES_BOOKING,
			Constants::LEGACY_SHORTCODES_STATUS
		);
		$tag_re = implode( '|', array_map( 'preg_quote', $tags ) );

		return (string) preg_replace_callback(
			'/\[(' . $tag_re . ')([^\]]*)\]/i',
			static function ( array $m ) use ( $id_map ): string {
				$tag  = $m[1];
				$atts = $m[2];
				$atts = preg_replace_callback(
					'/\b(class_id|clasbpro_class_stripe_id|class_stripe_id|id|ids|class_ids)=([\'"]?)([^\'"\s\]]+)\2/i',
					static function ( array $am ) use ( $id_map ): string {
						$attr  = $am[1];
						$quote = $am[2];
						$raw   = $am[3];
						$parts = preg_split( '/\s*,\s*/', $raw ) ?: [];
						$out   = [];
						foreach ( $parts as $part ) {
							$id = (int) ltrim( (string) $part, '#' );
							if ( isset( $id_map[ $id ] ) ) {
								$out[] = (string) $id_map[ $id ];
							} else {
								$out[] = (string) $part;
							}
						}
						return $attr . '=' . $quote . implode( ',', $out ) . $quote;
					},
					$atts
				);
				return '[' . $tag . $atts . ']';
			},
			$content
		);
	}

	/**
	 * @param array<int, int> $id_map
	 */
	private static function rewrite_elementor_data( string $json, array $id_map ): string {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			return $json;
		}
		$walk = static function ( &$nodes ) use ( &$walk, $id_map ): void {
			if ( ! is_array( $nodes ) ) {
				return;
			}
			foreach ( $nodes as &$node ) {
				if ( ! is_array( $node ) ) {
					continue;
				}
				$widget = (string) ( $node['widgetType'] ?? '' );
				if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
					if ( Constants::ELEMENTOR_WIDGET === $widget && isset( $node['settings']['class_id'] ) ) {
						$old = (int) ltrim( (string) $node['settings']['class_id'], '#' );
						if ( isset( $id_map[ $old ] ) ) {
							$node['settings']['class_id'] = (string) $id_map[ $old ];
						}
					}
					if ( Constants::ELEMENTOR_SCHEDULE_WIDGET === $widget && isset( $node['settings']['class_ids'] ) ) {
						$ids = $node['settings']['class_ids'];
						if ( is_array( $ids ) ) {
							$node['settings']['class_ids'] = array_map(
								static function ( $id ) use ( $id_map ) {
									$old = (int) $id;
									return $id_map[ $old ] ?? $id;
								},
								$ids
							);
						}
					}
				}
				if ( isset( $node['elements'] ) ) {
					$walk( $node['elements'] );
				}
			}
			unset( $node );
		};
		$walk( $data );
		$encoded = wp_json_encode( $data );
		return is_string( $encoded ) ? $encoded : $json;
	}

	private static function should_remap_key( string $key ): bool {
		$bare = ltrim( $key, '_' );
		if ( in_array( $bare, [ 'class_image', 'schedule_classes', 'schedule_class_ids' ], true ) ) {
			return true;
		}
		if ( false !== strpos( $bare, 'class_id' ) || false !== strpos( $bare, 'pack_id' ) || false !== strpos( $bare, 'booking_id' ) || false !== strpos( $bare, 'purchase_id' ) ) {
			return true;
		}
		if ( preg_match( '/ids$/', $bare ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @param mixed           $value
	 * @param array<int, int> $maps
	 * @return mixed
	 */
	private static function remap_value( $value, array $maps ) {
		if ( is_int( $value ) ) {
			return $maps[ $value ] ?? $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			$id = (int) $value;
			return isset( $maps[ $id ] ) ? (string) $maps[ $id ] : $value;
		}
		if ( is_array( $value ) ) {
			if ( isset( $value['ID'] ) && is_numeric( $value['ID'] ) ) {
				$old = (int) $value['ID'];
				if ( isset( $maps[ $old ] ) ) {
					$value['ID'] = $maps[ $old ];
					if ( isset( $value['id'] ) ) {
						$value['id'] = $maps[ $old ];
					}
				}
			}
			foreach ( $value as $k => $item ) {
				$value[ $k ] = self::remap_value( $item, $maps );
			}
		}
		return $value;
	}

	/**
	 * @param mixed $value
	 * @return mixed
	 */
	private static function rewrite_value_urls( $value, string $from, string $to ) {
		if ( is_string( $value ) ) {
			return self::rewrite_site_urls( $value, $from, $to );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $k => $item ) {
				$value[ $k ] = self::rewrite_value_urls( $item, $from, $to );
			}
		}
		return $value;
	}

	/**
	 * @return list<string>
	 */
	private static function url_variants( string $url ): array {
		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return [ $url ];
		}
		$host = (string) $parts['host'];
		$path = (string) ( $parts['path'] ?? '' );
		$bare = preg_replace( '/^www\./i', '', $host );
		$hosts = array_unique( [ $host, $bare, 'www.' . $bare ] );
		$out   = [];
		foreach ( [ 'https', 'http' ] as $scheme ) {
			foreach ( $hosts as $h ) {
				$out[] = $scheme . '://' . $h . $path;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param list<array<string, mixed>> $posts
	 * @return list<string>
	 */
	private static function missing_extra_field_names( array $posts ): array {
		if ( ! function_exists( 'acf_get_field' ) ) {
			return [];
		}
		$names = [];
		foreach ( $posts as $post ) {
			if ( CPT::CLASS_PT !== ( $post['post_type'] ?? '' ) ) {
				continue;
			}
			$meta = is_array( $post['meta'] ?? null ) ? $post['meta'] : [];
			foreach ( array_keys( $meta ) as $key ) {
				$key = (string) $key;
				if ( '' === $key || '_' === $key[0] ) {
					continue;
				}
				if ( 0 === strpos( $key, 'clasbpro' ) || 0 === strpos( $key, '_clasbpro' ) ) {
					continue;
				}
				if ( acf_get_field( $key ) ) {
					continue;
				}
				$names[ $key ] = $key;
			}
		}
		return array_values( $names );
	}
}
