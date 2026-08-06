<?php
/**
 * Default customer coupon email body (plain text).
 *
 * @package IOROOT_STRIPE_BOOKINGS_PRO
 */

defined( 'ABSPATH' ) || exit;
?>
Hi {customer_name},

Thanks for purchasing {pack_name}.

Your coupon code: {pack_code}
Uses included: {pack_uses}
Total paid: {amount_total}

Restore this coupon on a device:
{restore_url}

On a class booking page, choose “Use coupon” (1 seat). Enter the code if this browser doesn’t recognise you yet.

Reference: {purchase_id}
