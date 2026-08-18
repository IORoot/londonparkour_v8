<?php
/**
 * Collapse byte-identical attachments and leftover size-folder copies.
 *
 * Usage:
 *   bin/wp lp media:dedupe --dry-run
 *   bin/wp lp media:dedupe
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * WP-CLI: merge duplicate image attachments and delete unreferenced ` 2.jpg` files.
 *
 * @param array $args       Positional.
 * @param array $assoc_args Flags.
 */
function lp_cli_media_dedupe( $args, $assoc_args ): void {
	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}
	ob_implicit_flush( true );

	$dry = ! empty( $assoc_args['dry-run'] );
	$GLOBALS['lp_media_dedupe_verbose'] = ! empty( $assoc_args['verbose'] );
	WP_CLI::log( $dry ? 'Media dedupe (dry-run)' : 'Media dedupe' );

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	$groups = lp_media_duplicate_groups();
	$merged = 0;
	$repointed = 0;

	WP_CLI::log( sprintf( 'Duplicate image groups: %d', count( $groups ) ) );

	foreach ( $groups as $md5 => $ids ) {
		$keeper = lp_media_pick_keeper( $ids );
		$dups   = array_values( array_diff( $ids, array( $keeper ) ) );
		WP_CLI::log(
			sprintf(
				'  keep #%d (%s)  drop %s',
				$keeper,
				wp_basename( (string) get_attached_file( $keeper ) ),
				implode( ', ', array_map( static fn( $id ) => '#' . $id, $dups ) )
			)
		);

		foreach ( $dups as $dup ) {
			if ( ! $dry ) {
				$repointed += lp_media_repoint_attachment( (int) $dup, $keeper );
				wp_delete_attachment( (int) $dup, true );
			}
			++$merged;
		}

		if ( ! $dry ) {
			lp_remember_file_md5( $keeper );
		}
	}

	$swept = lp_media_sweep_conflict_copies( $dry );

	WP_CLI::success(
		sprintf(
			'%s duplicate attachment(s) %s, %d reference(s) rewritten, %d conflict-copy file(s) %s%s',
			$merged,
			$dry ? 'would be deleted' : 'deleted',
			$repointed,
			$swept,
			$dry ? 'would be deleted' : 'deleted',
			$dry ? ' (dry-run)' : ''
		)
	);
}

/**
 * Groups of image attachment IDs that share an original-file md5.
 *
 * @return array<string, int[]>
 */
function lp_media_duplicate_groups(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'post_mime_type' => 'image',
		)
	);

	$uploads = wp_get_upload_dir();
	$basedir = trailingslashit( (string) ( $uploads['basedir'] ?? '' ) );

	$ids   = $query->posts;
	$total = count( $ids );
	WP_CLI::log( sprintf( 'Hashing %d image attachments…', $total ) );

	$by_hash = array();
	$done    = 0;
	foreach ( $ids as $id ) {
		$id = (int) $id;
		++$done;
		if ( 0 === $done % 50 || 1 === $done ) {
			WP_CLI::log( sprintf( '  hashed %d / %d', $done, $total ) );
		}

		$rel = (string) get_post_meta( $id, '_wp_attached_file', true );
		$file = ( '' !== $rel && '' !== $basedir ) ? $basedir . ltrim( $rel, '/' ) : get_attached_file( $id );
		if ( ! $file || ! is_readable( $file ) ) {
			continue;
		}
		$md5 = get_post_meta( $id, LP_FILE_MD5_META, true );
		if ( ! is_string( $md5 ) || 32 !== strlen( $md5 ) ) {
			$md5 = md5_file( $file );
			if ( is_string( $md5 ) && 32 === strlen( $md5 ) ) {
				update_post_meta( $id, LP_FILE_MD5_META, $md5 );
			}
		}
		if ( ! is_string( $md5 ) || 32 !== strlen( $md5 ) ) {
			continue;
		}
		$by_hash[ $md5 ][] = $id;
	}

	return array_filter( $by_hash, static fn( $ids ) => count( $ids ) > 1 );
}

/**
 * Prefer a filename that is not a unique-name / iCloud collision, then lowest ID.
 *
 * @param int[] $ids Attachment IDs, same bytes.
 */
function lp_media_pick_keeper( array $ids ): int {
	$scored = array();
	foreach ( $ids as $id ) {
		$name    = pathinfo( (string) get_attached_file( (int) $id ), PATHINFO_FILENAME );
		$score   = 2;
		if ( preg_match( '/ \d+$/', $name ) ) {
			$score = 0;
		} elseif ( preg_match( '/-\d+$/', $name ) ) {
			$score = 1;
		}
		$scored[] = array(
			'score' => $score,
			'id'    => (int) $id,
		);
	}
	usort(
		$scored,
		static function ( $a, $b ) {
			if ( $a['score'] !== $b['score'] ) {
				return $b['score'] <=> $a['score'];
			}
			return $a['id'] <=> $b['id'];
		}
	);
	return $scored[0]['id'];
}

/**
 * Rewrite every stored reference from one attachment ID to another.
 *
 * @param int $from Duplicate ID.
 * @param int $to   Keeper ID.
 * @return int Rows changed.
 */
function lp_media_repoint_attachment( int $from, int $to ): int {
	global $wpdb;

	$changed = 0;
	$changed += lp_media_repoint_table( $wpdb->postmeta, 'meta_id', 'meta_value', $from, $to );
	$changed += lp_media_repoint_table( $wpdb->termmeta, 'meta_id', 'meta_value', $from, $to );
	$changed += lp_media_repoint_table( $wpdb->commentmeta, 'meta_id', 'meta_value', $from, $to );
	$changed += lp_media_repoint_table( $wpdb->options, 'option_id', 'option_value', $from, $to );

	$children = get_children(
		array(
			'post_parent' => $from,
			'post_type'   => 'attachment',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $children as $child ) {
		wp_update_post(
			array(
				'ID'          => (int) $child,
				'post_parent' => $to,
			)
		);
		++$changed;
	}

	return $changed;
}

/**
 * Replace an attachment ID inside one wpdb table column.
 *
 * @param string $table  Table name.
 * @param string $id_col Primary key.
 * @param string $val_col Value column.
 * @param int    $from   Duplicate ID.
 * @param int    $to     Keeper ID.
 */
function lp_media_repoint_table( string $table, string $id_col, string $val_col, int $from, int $to ): int {
	global $wpdb;

	$like_i = '%i:' . $from . ';%';
	$like_s = '%"' . $from . '"%';
	$rows   = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT {$id_col} AS row_id, {$val_col} AS row_value FROM {$table}
			 WHERE {$val_col} = %s
			    OR {$val_col} = %d
			    OR {$val_col} LIKE %s
			    OR {$val_col} LIKE %s",
			(string) $from,
			$from,
			$like_i,
			$like_s
		)
	);

	$changed = 0;
	foreach ( $rows as $row ) {
		$new = lp_media_replace_id_in_value( $row->row_value, $from, $to );
		if ( $new === $row->row_value ) {
			continue;
		}
		$wpdb->update(
			$table,
			array( $val_col => $new ),
			array( $id_col => $row->row_id )
		);
		++$changed;
	}

	return $changed;
}

/**
 * Replace an int ID inside a scalar, PHP-serialized, or nested array value.
 *
 * @param mixed $value Raw DB value.
 * @param int   $from  Duplicate ID.
 * @param int   $to    Keeper ID.
 * @return mixed
 */
function lp_media_replace_id_in_value( $value, int $from, int $to ) {
	if ( is_int( $value ) || ( is_string( $value ) && ctype_digit( $value ) ) ) {
		return ( (int) $value === $from ) ? ( is_int( $value ) ? $to : (string) $to ) : $value;
	}

	if ( ! is_string( $value ) ) {
		return $value;
	}

	$un = maybe_unserialize( $value );
	if ( $un === $value ) {
		return $value;
	}

	$replaced = lp_media_replace_id_recursive( $un, $from, $to );
	if ( $replaced === $un ) {
		return $value;
	}

	return is_serialized( $value ) ? serialize( $replaced ) : $replaced;
}

/**
 * @param mixed $value Nested value.
 * @param int   $from  Duplicate ID.
 * @param int   $to    Keeper ID.
 * @return mixed
 */
function lp_media_replace_id_recursive( $value, int $from, int $to ) {
	if ( is_int( $value ) ) {
		return $from === $value ? $to : $value;
	}
	if ( is_string( $value ) && ctype_digit( $value ) ) {
		return $from === (int) $value ? (string) $to : $value;
	}
	if ( is_array( $value ) ) {
		foreach ( $value as $key => $item ) {
			$value[ $key ] = lp_media_replace_id_recursive( $item, $from, $to );
		}
	}
	return $value;
}

/**
 * Delete Finder/iCloud `name 2.jpg` files that no attachment metadata points at.
 *
 * @param bool $dry Dry run.
 * @return int Files removed (or that would be).
 */
function lp_media_sweep_conflict_copies( bool $dry ): int {
	$uploads = wp_get_upload_dir();
	$basedir = isset( $uploads['basedir'] ) ? (string) $uploads['basedir'] : '';
	if ( '' === $basedir || ! is_dir( $basedir ) ) {
		return 0;
	}

	$referenced = lp_media_referenced_relpaths();
	$skip_dirs  = array( 'Logos', 'backup', 'rank-math', 'redux', 'avatars' );
	$removed    = 0;

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $basedir, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}
		$name = $file->getFilename();
		if ( ! preg_match( '/ \d+\.(jpe?g|png|webp|gif)$/i', $name ) ) {
			continue;
		}

		$abs = $file->getPathname();
		$rel = ltrim( str_replace( '\\', '/', substr( $abs, strlen( $basedir ) ) ), '/' );
		$top = explode( '/', $rel )[0];
		if ( in_array( $top, $skip_dirs, true ) || str_starts_with( $top, '_import' ) ) {
			continue;
		}
		if ( isset( $referenced[ $rel ] ) ) {
			continue;
		}

		if ( ! empty( $GLOBALS['lp_media_dedupe_verbose'] ) ) {
			WP_CLI::log( '  sweep ' . $rel );
		}
		if ( ! $dry ) {
			wp_delete_file( $abs );
		}
		++$removed;
	}

	return $removed;
}

/**
 * Relative upload paths owned by some attachment (original + every size).
 *
 * @return array<string, true>
 */
function lp_media_referenced_relpaths(): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	$refs = array();
	foreach ( $query->posts as $id ) {
		$file = get_post_meta( (int) $id, '_wp_attached_file', true );
		if ( is_string( $file ) && '' !== $file ) {
			$refs[ ltrim( str_replace( '\\', '/', $file ), '/' ) ] = true;
		}
		$meta = wp_get_attachment_metadata( (int) $id, true );
		if ( ! is_array( $meta ) ) {
			continue;
		}
		$dir = is_string( $file ) ? dirname( $file ) : '';
		$dir = ( '.' === $dir ) ? '' : $dir;
		if ( ! empty( $meta['file'] ) && is_string( $meta['file'] ) ) {
			$refs[ ltrim( str_replace( '\\', '/', $meta['file'] ), '/' ) ] = true;
		}
		if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
			continue;
		}
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) || ! is_string( $size['file'] ) ) {
				continue;
			}
			$size_rel = str_contains( $size['file'], '/' )
				? ( $dir ? $dir . '/' . $size['file'] : $size['file'] )
				: ( $dir ? $dir . '/' . $size['file'] : $size['file'] );
			$refs[ ltrim( str_replace( '\\', '/', $size_rel ), '/' ) ] = true;
		}
	}

	return $refs;
}

WP_CLI::add_command( 'lp media:dedupe', 'lp_cli_media_dedupe' );
