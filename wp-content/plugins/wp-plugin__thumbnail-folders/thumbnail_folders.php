<?php
/*
 * @wordpress-plugin
 * Plugin Name:       _ANDYP - Media - Thumbnail Folders
 * Plugin URI:        http://londonparkour.com
 * Description:       <strong>Filter</strong> | Adds a prefix folder to all media thumbnails.
 * Version:           1.1.0
 * Author:            Andy Pearson
 * Author URI:        https://londonparkour.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress 5.3+ builds intermediates with make_subsize() per size, not
 * multi_resize(). GD/Imagick then store 'file' as wp_basename() of the full
 * path, which strips the size folder. We keep the size name on the editor
 * while that subsize is written, then put it back onto the returned metadata.
 */
add_filter( 'wp_image_editors', 'lp_thumbnail_folders_editors' );
function lp_thumbnail_folders_editors( $editors ) {
	array_unshift( $editors, 'WP_Image_Editor_Custom_GD' );
	array_unshift( $editors, 'WP_Image_Editor_Custom_IM' );
	return $editors;
}

require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';

trait LP_Thumbnail_Folders_Editor {

	/**
	 * Registered size slug for the subsize currently being written.
	 *
	 * @var string|null
	 */
	protected $lp_size_folder = null;

	/**
	 * Map requested width/height/crop back to the registered size name.
	 *
	 * @param array $size_data Size array from _wp_make_subsizes().
	 * @return string|null
	 */
	protected static function lp_folder_for_size( $size_data ) {
		$w    = (int) ( $size_data['width'] ?? 0 );
		$h    = (int) ( $size_data['height'] ?? 0 );
		$crop = $size_data['crop'] ?? false;

		$sizes  = wp_get_registered_image_subsizes();
		$by_wh  = null;

		foreach ( $sizes as $name => $size ) {
			if ( (int) $size['width'] !== $w || (int) $size['height'] !== $h ) {
				continue;
			}
			if ( $size['crop'] == $crop ) {
				return $name;
			}
			$by_wh = $name;
		}

		return $by_wh;
	}

	/**
	 * Write this subsize into {size-name}/filename.ext and return metadata
	 * whose 'file' key includes that folder (core would strip it).
	 *
	 * @param array $size_data Size data from wp_get_registered_image_subsizes().
	 * @return array|WP_Error
	 */
	public function make_subsize( $size_data ) {
		$folder               = self::lp_folder_for_size( $size_data );
		$this->lp_size_folder = $folder;
		$saved                = parent::make_subsize( $size_data );
		$this->lp_size_folder = null;

		if ( ! is_wp_error( $saved ) && $folder && ! empty( $saved['file'] ) && ! str_contains( $saved['file'], '/' ) ) {
			$saved['file'] = $folder . '/' . $saved['file'];
		}

		return $saved;
	}

	/**
	 * Put intermediates in a folder named after the registered size, not
	 * filename-300x200.ext in the same directory as the original.
	 *
	 * @param string|null $suffix    Unused; size folder comes from make_subsize().
	 * @param string|null $dest_path Optional destination directory.
	 * @param string|null $extension Optional file extension.
	 * @return string
	 */
	public function generate_filename( $suffix = null, $dest_path = null, $extension = null ) {
		if ( is_string( $this->file ) && str_contains( $this->file, 'uploads/avatars' ) ) {
			return parent::generate_filename( $suffix, $dest_path, $extension );
		}

		$folder = $this->lp_size_folder;
		if ( ! $folder ) {
			return parent::generate_filename( $suffix, $dest_path, $extension );
		}

		$dir = pathinfo( $this->file, PATHINFO_DIRNAME );
		$ext = pathinfo( $this->file, PATHINFO_EXTENSION );

		if ( ! is_null( $dest_path ) && ! wp_is_stream( $dest_path ) ) {
			$real = realpath( $dest_path );
			if ( $real ) {
				$dir = $real;
			}
		} elseif ( ! is_null( $dest_path ) && wp_is_stream( $dest_path ) ) {
			$dir = $dest_path;
		}

		$name    = wp_basename( $this->file, ".{$ext}" );
		$new_ext = strtolower( $extension ? $extension : $ext );

		return trailingslashit( $dir ) . "{$folder}/{$name}.{$new_ext}";
	}
}

class WP_Image_Editor_Custom_GD extends WP_Image_Editor_GD {
	use LP_Thumbnail_Folders_Editor;
}

class WP_Image_Editor_Custom_IM extends WP_Image_Editor_Imagick {
	use LP_Thumbnail_Folders_Editor;
}

/**
 * On read, prefix a size folder only when that file is actually on disk.
 * Blind prefixing made WordPress look for thumbnail/file-150x150.jpg when
 * the file was still file-150x150.jpg (default core naming).
 */
add_filter( 'wp_get_attachment_metadata', 'lp_thumbnail_folders_metadata', 10, 2 );
function lp_thumbnail_folders_metadata( $data, $attachment_id ) {
	if ( ! is_array( $data ) || empty( $data['sizes'] ) || ! is_array( $data['sizes'] ) ) {
		return $data;
	}

	$attached = get_attached_file( $attachment_id, true );
	$dir      = $attached ? dirname( $attached ) : '';

	foreach ( $data['sizes'] as $name => $size ) {
		if ( empty( $size['file'] ) || ! is_string( $size['file'] ) ) {
			continue;
		}
		if ( str_contains( $size['file'], '/' ) ) {
			continue;
		}

		$prefixed = $name . '/' . $size['file'];
		if ( $dir && file_exists( $dir . '/' . $prefixed ) ) {
			$data['sizes'][ $name ]['file'] = $prefixed;
		}
	}

	return $data;
}
