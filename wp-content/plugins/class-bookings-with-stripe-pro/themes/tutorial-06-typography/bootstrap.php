<?php
/**
 * Tutorial 06 — load a web font for the form.
 *
 * Change the Google Fonts URL below to use a different family.
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		// ↓↓↓ CHANGE THIS — Google Fonts URL ↓↓↓
		wp_enqueue_style(
			'clasbpro-tut06-fonts',
			'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,600&family=Source+Sans+3:wght@400;600&display=swap',
			[],
			null
		);
		// ↑↑↑ CHANGE THIS ↑↑↑
	},
	15
);
