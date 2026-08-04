<?php

namespace EasyWPSMTP\Tasks;

use Exception;

/**
 * Class NotificationsUpdateTask.
 *
 * @since 2.8.0
 */
class NotificationsUpdateTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 2.8.0
	 */
	const ACTION = 'easy_wp_smtp_admin_notifications_update';

	/**
	 * Class constructor.
	 *
	 * @since 2.8.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 2.8.0
	 */
	public function init() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		// Register the action handler.
		add_action( self::ACTION, [ $this, 'process' ] );

		// Exit if notifications are disabled
		// or this task is already scheduled.
		if (
			! easy_wp_smtp()->get_notifications()->is_enabled() ||
			Tasks::is_scheduled( self::ACTION ) !== false
		) {
			return;
		}

		// Schedule the task.
		$this->recurring(
			strtotime( '+1 minute' ),
			easy_wp_smtp()->get_notifications()->get_notification_update_task_interval()
		)
		     ->unique()
		     ->register();
	}

	/**
	 * Update the notification feed.
	 *
	 * @since 2.8.0
	 */
	public function process() {

		// Delete task duplicates.
		try {
			$this->remove_pending( 1000 );
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Do nothing.
		}

		easy_wp_smtp()->get_notifications()->update();
	}
}
