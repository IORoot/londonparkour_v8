<?php

namespace EasyWPSMTP\Tasks\Queue;

use EasyWPSMTP\Tasks\Meta;
use EasyWPSMTP\Tasks\Task;

/**
 * Class SendEnqueuedEmailTask.
 *
 * @since 2.6.0
 */
class SendEnqueuedEmailTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 2.6.0
	 */
	const ACTION = 'easy_wp_smtp_send_enqueued_email';

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
	}

	/**
	 * Schedule email sending.
	 *
	 * @since 2.6.0
	 *
	 * @param int $email_id Email id.
	 */
	public function schedule( $email_id ) {

		// Exit if AS function does not exist.
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}

		// Schedule the task.
		$this->async()
		     ->params( $email_id )
		     ->register();
	}

	/**
	 * Perform email sending.
	 *
	 * @since 2.6.0
	 *
	 * @param int $meta_id The Meta ID with the stored task parameters.
	 */
	public function process( $meta_id ) {

		$task_meta = new Meta();
		$meta      = $task_meta->get( (int) $meta_id );

		// We should actually receive the passed parameter.
		if ( empty( $meta ) || empty( $meta->data ) || count( $meta->data ) < 1 ) {
			return;
		}

		$email_id = $meta->data[0];

		easy_wp_smtp()->get_queue()->send_email( $email_id );
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
