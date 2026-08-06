<?php
/**
 * Default customer email body (plain text).
 *
 * @package IOROOT_STRIPE_BOOKINGS
 */

defined( 'ABSPATH' ) || exit;
?>
Hi {customer_name},

Thanks for booking — we can't wait to see you on the mat!

Your booking
- Class: {class_name}
- When: {class_date} at {class_time}
- Where: {location}
- Duration: {duration} minutes
- Seats: {seats}
- Total paid: {amount_total}

Reference: {booking_id}

If you need to cancel or change anything, just reply to this email.

See you soon!
