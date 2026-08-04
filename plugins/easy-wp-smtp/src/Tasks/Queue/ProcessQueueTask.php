<?php

namespace EasyWPSMTP\Tasks\Queue;

use EasyWPSMTP\Tasks\Task;
use EasyWPSMTP\Tasks\Tasks;

/**
 * Class ProcessQueueTask.
 *
 * @since 2.6.0
 */
class ProcessQueueTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 2.6.0
	 */
	const ACTION = 'easy_wp_smtp_queue_process';

	/**
	 * Class constructor.
	 *
	 * @since 2.6.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task.
	 *
	 * @since 2.6.0
	 */
	public function init() { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		// Register the action handler.
		add_action( self::ACTION, [ $this, 'process' ] );

		// Cleanup completed task occurrences.
		add_action( 'action_scheduler_after_process_queue', [ $this, 'cleanup' ] );

		// Exit if this task the queue is disabled, or it's already scheduled.
		if (
			! easy_wp_smtp()->get_queue()->is_enabled() ||
			Tasks::is_scheduled( self::ACTION ) !== false
		) {
			return;
		}

		// Schedule the task.
		$this->recurring( strtotime( 'now' ), MINUTE_IN_SECONDS )
		     ->unique()
		     ->register();
	}

	/**
	 * Perform email sending.
	 *
	 * @since 2.6.0
	 */
	public function process() {

		$queue = easy_wp_smtp()->get_queue();

		$queue->process();

		if ( ! $queue->is_enabled() ) {
			$this->cancel_force();
		}
	}

	/**
	 * Cleanup completed tasks.
	 *
	 * @since 2.6.0
	 */
	public function cleanup() {

		$this->remove_completed( 10 );
	}
}
