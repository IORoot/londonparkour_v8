<?php
/**
 * Yoga-themed decorative credit card for example 5.
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="cbfs-ex5__card-art" viewBox="0 0 360 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php echo esc_attr__( 'Illustration of a credit card', 'class-bookings-with-stripe-pro' ); ?>">
	<defs>
		<linearGradient id="cbfs-ex5-card-bg" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" stop-color="#3d5c4a"/>
			<stop offset="100%" stop-color="#2a4035"/>
		</linearGradient>
		<linearGradient id="cbfs-ex5-chip" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" stop-color="#e8d5b5"/>
			<stop offset="100%" stop-color="#c4a882"/>
		</linearGradient>
	</defs>
	<rect x="8" y="8" width="344" height="204" rx="18" fill="url(#cbfs-ex5-card-bg)" stroke="rgba(255,255,255,0.15)" stroke-width="2"/>
	<rect x="36" y="52" width="52" height="40" rx="6" fill="url(#cbfs-ex5-chip)" opacity="0.95"/>
	<rect x="42" y="58" width="40" height="28" rx="4" fill="none" stroke="rgba(0,0,0,0.2)" stroke-width="1"/>
	<g fill="#f5ebe0" opacity="0.95" font-family="ui-monospace, monospace" font-size="18" letter-spacing="3">
		<text x="36" y="138">••••</text>
		<text x="120" y="138">••••</text>
		<text x="204" y="138">••••</text>
		<text x="288" y="138">4242</text>
	</g>
	<text x="36" y="178" fill="rgba(245,235,224,0.55)" font-family="system-ui, sans-serif" font-size="11" letter-spacing="0.08em">NAMASTE YOGA</text>
	<text x="36" y="196" fill="#f5ebe0" font-family="system-ui, sans-serif" font-size="14" font-weight="600">STUDIO MEMBER</text>
	<text x="280" y="178" fill="rgba(245,235,224,0.55)" font-family="system-ui, sans-serif" font-size="11" letter-spacing="0.08em">VALID</text>
	<text x="280" y="196" fill="#f5ebe0" font-family="system-ui, sans-serif" font-size="14" font-weight="600">12/28</text>
	<circle cx="300" cy="56" r="18" fill="#7cb88a" opacity="0.9"/>
	<circle cx="322" cy="56" r="18" fill="#5a9a6a" opacity="0.85"/>
</svg>
