<?php
/**
 * Default admin custom-payment email body (plain text).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;
?>
Custom payment received.

- Customer: {customer_name} <{customer_email}>
- Total: {amount_total}
- Reference: {booking_id}
- Receipt: {stripe_receipt_url}

This is not a class booking.
