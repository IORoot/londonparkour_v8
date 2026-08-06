<?php
/**
 * index.php — WordPress's last-resort template.
 *
 * Required by every theme, and reached only when nothing more specific
 * matches. No Storybook source; the body is
 * template-parts/content/archive-list.php, shared with archive.php.
 *
 * @package londonparkour_v8
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part( 'template-parts/content/archive-list' );
get_footer();
