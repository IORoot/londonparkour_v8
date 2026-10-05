<?php
/**
 * Default customer custom-payment email body (plain text).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;
?>
Hi {customer_name},

We received your payment of {amount_total}. A Stripe receipt is on its way to this address.

This is not a class booking. Standard sessions are on the agenda.

Reference: {booking_id}

London Parkour
