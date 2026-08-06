<?php
/**
 * archive.php — category, tag, date and author archives.
 *
 * No Storybook source; the body is template-parts/content/archive-list.php,
 * shared with index.php. Read that file's docblock for what it composes and
 * why. This template exists as its own file so an archive can diverge from
 * the last-resort fallback later without index.php changing.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/content/archive-list' );
get_footer();
