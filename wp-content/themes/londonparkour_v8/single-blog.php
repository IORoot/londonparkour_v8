<?php
/**
 * single-blog.php — BlogDetail for the `blog` CPT.
 *
 * Same composition as single.php. The v7 import uses this post type; the
 * template hierarchy would otherwise still fall through to single.php, but
 * keeping the named file makes the CPT routing explicit.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/single.php';
