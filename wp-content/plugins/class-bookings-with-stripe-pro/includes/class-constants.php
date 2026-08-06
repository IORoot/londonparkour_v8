<?php
/**
 * Prefixed identifiers for Class Bookings with Stripe (clasbpro).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

namespace IOROOT_STRIPE_BOOKINGS_PRO;

defined( 'ABSPATH' ) || exit;

/**
 * Central prefix and slug constants (min. 4-character prefix per WP plugin guidelines).
 */
abstract class Constants {

	public const PREFIX = 'clasbpro';

	public const CPT_CLASS         = 'clasbpro_class';
	public const CPT_BOOKING       = 'clasbpro_booking';
	public const CPT_PACK          = 'clasbpro_pack';
	/** Must stay ≤ 20 chars (wp_posts.post_type). */
	public const CPT_PACK_PURCHASE = 'clasbpro_pack_ord';

	public const OPTIONS_POST_ID = 'clasbpro_options';

	/** Canonical WP option for schedule calendar class IDs (relationship field syncs here on save). */
	public const SCHEDULE_CLASS_IDS_OPTION = 'clasbpro_schedule_class_ids';

	/** Canonical WP option for Stripe currency (lowercase ISO 4217). */
	public const STRIPE_CURRENCY_OPTION = 'clasbpro_stripe_currency';

	public const MENU_SETTINGS = 'clasbpro-settings';
	public const MENU_REPORTS  = 'clasbpro-reports';

	public const REST_NAMESPACE = 'clasbpro/v1';

	public const CRON_HOOK            = 'clasbpro_expire_holds';
	public const CRON_SCHEDULE        = 'clasbpro_five_minutes';
	public const CRON_INTERVAL_OPTION = 'clasbpro_cron_interval_registered';

	public const SHORTCODE_BOOKING  = 'clasbpro_booking';
	public const SHORTCODE_STATUS   = 'clasbpro_booking_status';
	public const SHORTCODE_SCHEDULE = 'clasbpro_schedule';
	public const SHORTCODE_PACKS    = 'clasbpro_coupons';
	/** @deprecated Use SHORTCODE_PACKS (`clasbpro_coupons`). */
	public const SHORTCODE_PACKS_LEGACY = 'clasbpro_packs';

	public const SCRIPT_FRONTEND        = 'clasbpro';
	public const SCRIPT_ADMIN_SETTINGS  = 'clasbpro-admin-settings';
	public const SCRIPT_CANCELLED_DATES = 'clasbpro-cancelled-dates';
	public const SCRIPT_CLASS_METABOX   = 'clasbpro-class-metabox';
	public const SCRIPT_REPORTS_CHART   = 'clasbpro-reports-chart';
	public const STYLE_ADMIN_SETTINGS   = 'clasbpro-admin-settings';
	public const STYLE_REPORTS          = 'clasbpro-reports';

	public const META_PREFIX = '_clasbpro_';

	public const ELEMENTOR_WIDGET          = 'clasbpro-booking';
	public const ELEMENTOR_SCHEDULE_WIDGET = 'clasbpro-schedule';

	/** @var string[] Pro-only legacy shortcodes (distinct from the free plugin). */
	public const LEGACY_SHORTCODES_BOOKING = [ 'stripe_booking_pro', 'clasbpro_booking_legacy' ];

	/** @var string[] Pro-only legacy shortcodes (distinct from the free plugin). */
	public const LEGACY_SHORTCODES_STATUS = [ 'stripe_booking_status_pro', 'clasbpro_booking_status_legacy' ];
}
