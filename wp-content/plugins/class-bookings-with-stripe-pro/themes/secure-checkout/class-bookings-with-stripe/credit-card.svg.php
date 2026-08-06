<?php
/**
 * Decorative credit-card SVG for example 4 (not a real card).
 */

defined( 'ABSPATH' ) || exit;
?>
<svg class="cbfs-ex4__card-art" viewBox="0 0 360 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="<?php echo esc_attr__( 'Illustration of a credit card', 'class-bookings-with-stripe-pro' ); ?>">
	<defs>
		<linearGradient id="cbfs-ex4-card-bg" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" stop-color="#1e3a5f"/>
			<stop offset="100%" stop-color="#0f172a"/>
		</linearGradient>
		<linearGradient id="cbfs-ex4-chip" x1="0%" y1="0%" x2="100%" y2="100%">
			<stop offset="0%" stop-color="#d4af37"/>
			<stop offset="100%" stop-color="#b8860b"/>
		</linearGradient>
	</defs>
	<rect x="8" y="8" width="344" height="204" rx="18" fill="url(#cbfs-ex4-card-bg)" stroke="rgba(255,255,255,0.12)" stroke-width="2"/>
	<rect x="36" y="52" width="52" height="40" rx="6" fill="url(#cbfs-ex4-chip)" opacity="0.95"/>
	<rect x="42" y="58" width="40" height="28" rx="4" fill="none" stroke="rgba(0,0,0,0.25)" stroke-width="1"/>
	<g fill="#ffffff" opacity="0.92" font-family="ui-monospace, monospace" font-size="18" letter-spacing="3">
		<text x="36" y="138">••••</text>
		<text x="120" y="138">••••</text>
		<text x="204" y="138">••••</text>
		<text x="288" y="138">4242</text>
	</g>
	<text x="36" y="178" fill="rgba(255,255,255,0.55)" font-family="system-ui, sans-serif" font-size="11" letter-spacing="0.08em">CARDHOLDER</text>
	<text x="36" y="196" fill="#ffffff" font-family="system-ui, sans-serif" font-size="14" font-weight="600">J. SMITH</text>
	<text x="280" y="178" fill="rgba(255,255,255,0.55)" font-family="system-ui, sans-serif" font-size="11" letter-spacing="0.08em">VALID</text>
	<text x="280" y="196" fill="#ffffff" font-family="system-ui, sans-serif" font-size="14" font-weight="600">12/28</text>
	<circle cx="300" cy="56" r="18" fill="#eb001b" opacity="0.9"/>
	<circle cx="322" cy="56" r="18" fill="#f79e1b" opacity="0.85"/>
</svg>
