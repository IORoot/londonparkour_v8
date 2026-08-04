<?php

namespace EasyWPSMTP;

use EasyWPSMTP\Admin\DebugEvents\DebugEvents;
use EasyWPSMTP\Helpers\Helpers;

/**
 * Class Debug — legacy global "last error" message bag.
 *
 * The plugin no longer reads from this bag. For diagnostic logging, use
 * {@see DebugEvents::add()}. For tracking the current email-sending failure
 * state per connection (the data the EmailSendingErrors banner renders), use
 * {@see EmailSendingDebug}.
 *
 * @since      2.0.0
 * @deprecated {VERSION}
 */
class Debug {

	/**
	 * Key for options table where all messages will be saved to.
	 *
	 * @since 2.0.0
	 */
	const OPTION_KEY = 'easy_wp_smtp_debug';

	/**
	 * Hold the cached error messages.
	 *
	 * @since 2.0.0
	 *
	 * @var array
	 */
	private static $cached_messages;

	/**
	 * Save unique debug message to a debug log.
	 * Adds one more to a list, at the end.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @param mixed $message An array or string error message.
	 *
	 * @return bool|int
	 */
	public static function set( $message ) {

		if ( empty( $message ) ) {
			return false;
		}

		self::clear_cache();

		// Log the error message to the Debug Events.
		$event_id = DebugEvents::add( $message );

		$all = self::get_raw();

		if ( ! empty( $event_id ) ) {
			array_push( $all, $event_id );
		} else {
			if ( ! is_string( $message ) ) {
				$message = wp_json_encode( $message );
			} else {
				$message = wp_strip_all_tags( $message, false );
			}

			array_push( $all, $message );
		}

		update_option( self::OPTION_KEY, array_unique( $all ), false );

		return $event_id;
	}

	/**
	 * Remove all messages for a debug log.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 */
	public static function clear() {

		self::clear_cache();

		update_option( self::OPTION_KEY, [], false );
	}

	/**
	 * Clear cached error messages.
	 *
	 * @since 2.0.0
	 */
	private static function clear_cache() {

		self::$cached_messages = null;
	}

	/**
	 * Get the raw DB debug option values.
	 *
	 * @since 2.0.0
	 */
	private static function get_raw() {

		$all = get_option( self::OPTION_KEY, [] );

		if ( ! is_array( $all ) ) {
			$all = (array) $all;
		}

		return $all;
	}

	/**
	 * Retrieve all messages from a debug log.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @return array
	 */
	public static function get() {

		if ( isset( self::$cached_messages ) ) {
			return self::$cached_messages;
		}

		$all = self::get_raw();

		if ( empty( $all ) ) {
			self::$cached_messages = [];

			return [];
		}

		$event_ids    = [];
		$old_messages = [];

		foreach ( $all as $item ) {
			if ( is_int( $item ) ) {
				$event_ids[] = (int) $item;
			} else {
				$old_messages[] = $item;
			}
		}

		$event_messages        = DebugEvents::get_debug_messages( $event_ids );
		self::$cached_messages = array_unique( array_merge( $old_messages, $event_messages ) );

		return self::$cached_messages;
	}

	/**
	 * Get the last message that was saved to a debug log.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @return string
	 */
	public static function get_last() {

		$all = self::get();

		if ( ! empty( $all ) && is_array( $all ) ) {
			return (string) end( $all );
		}

		return '';
	}

	/**
	 * Get the proper variable content output to debug.
	 *
	 * Moved to {@see Helpers::pvar()}. Kept as a thin passthrough so external
	 * callers don't break.
	 *
	 * @since      2.0.0
	 * @deprecated {VERSION}
	 *
	 * @param mixed $var Variable to output.
	 *
	 * @return string
	 */
	public static function pvar( $var = '' ) {

		return Helpers::pvar( $var );
	}
}
