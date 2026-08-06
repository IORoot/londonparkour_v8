<?php
/**
 * Shared status page body — included by default and themed layouts.
 *
 * @var \IOROOT_STRIPE_BOOKINGS_PRO\Booking_Status_View $view
 */

defined( 'ABSPATH' ) || exit;

$view->render( 'title' );
$view->render( 'lede' );
$view->render( 'details-list' );
$view->render( 'pending-spinner' );
$view->render( 'try-again-button' );
$view->render( 'error-detail' );
$view->render( 'hint' );
