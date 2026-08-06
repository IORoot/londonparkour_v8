<?php
defined( 'ABSPATH' ) || exit;

$direction = (string) ( $direction ?? 'prev' );
$path      = 'next' === $direction ? 'M17.5 13.5 24 20l-6.5 6.5' : 'M22.5 13.5 16 20l6.5 6.5';
?>
<svg class="cbfs-schedule__nav-icon" viewBox="0 0 40 40" aria-hidden="true" focusable="false">
	<circle class="cbfs-schedule__nav-icon-ring" cx="20" cy="20" r="19" />
	<path class="cbfs-schedule__nav-icon-arrow" d="<?php echo esc_attr( $path ); ?>" />
</svg>
