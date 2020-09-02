<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @link       https://github.com/arielhr1987/leira-cron-jobs
 * @since      1.0.0
 * @package    Leira_Cron_Jobs
 * @subpackage Leira_Cron_Jobs/admin
 * @author     Ariel <arielhr1987@gmail.com>
 */
class Leira_Cron_Jobs_Manager{

	/**
	 * Get all defined cron jobs
	 *
	 * @return array
	 */
	public function get_crons() {

		if ( function_exists( '_get_cron_array' ) ) {
			$items = _get_cron_array();
		} else {
			$cron = get_option( 'cron' );

			$items = ( is_array( $cron ) ? $cron : array() );
		}

		$arr = array();
		foreach ( $items as $time => $tasks ) {

			foreach ( $tasks as $name => $task ) {

				foreach ( $task as $md5 => $details ) {
					$schedule = isset( $details['schedule'] ) && ! empty( $details['schedule'] ) ? $details['schedule'] : '__single_run';

					$options = 0;
					if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
						$options |= JSON_UNESCAPED_SLASHES;
					}
					if ( defined( 'JSON_PRETTY_PRINT' ) ) {
						$options |= JSON_PRETTY_PRINT;
					}

					$arr[] = array(
						'event'    => $name,
						'action'   => $this->get_cron_action( $name ),
						//or use wp_json_encode( $details['args'], $options )
						'args'     => isset( $details['args'] ) && ! empty( $details['args'] ) ? json_encode( $details['args'] ) : '',
						'schedule' => $schedule,
						'time'     => $time,
						'md5'      => $md5,
					);
				}
			}
		}

		return $arr;
	}

	/**
	 * Get a defined cron job
	 *
	 * @return array|bool Returns an array containing all the information of the cron job  or false if it wasn't found
	 */
	public function get_cron() {

		if ( function_exists( '_get_cron_array' ) ) {
			$items = _get_cron_array();
		} else {
			$cron = get_option( 'cron' );

			$items = ( is_array( $cron ) ? $cron : array() );
		}

		foreach ( $items as $time => $tasks ) {

			foreach ( $tasks as $name => $task ) {

				foreach ( $task as $md5 => $details ) {
					$schedule = isset( $details['schedule'] ) && ! empty( $details['schedule'] ) ? $details['schedule'] : '__single_run';

					$options = 0;
					if ( defined( 'JSON_UNESCAPED_SLASHES' ) ) {
						$options |= JSON_UNESCAPED_SLASHES;
					}
					if ( defined( 'JSON_PRETTY_PRINT' ) ) {
						$options |= JSON_PRETTY_PRINT;
					}

					return array(
						'event'    => $name,
						'action'   => $this->get_cron_action( $name ),
						//'args'     => isset( $details['args'] ) && ! empty( $details['args'] ) ? wp_json_encode( $details['args'], $options ) : '',
						'args'     => isset( $details['args'] ) && ! empty( $details['args'] ) ? json_encode( $details['args'] ) : '',
						'schedule' => $schedule,
						'time'     => $time,
						'md5'      => $md5,
					);
				}
			}
		}

		return false;
	}

	/**
	 * Get the action hook that handles the cron job
	 *
	 * @param $name
	 *
	 * @return bool|mixed|string
	 */
	public function get_cron_action( $name ) {
		$action = false;
		if ( has_action( $name ) ) {
			if ( isset( $GLOBALS['wp_filter'][ $name ] ) ) {
				foreach ( $GLOBALS['wp_filter'][ $name ] as $priority => $tasks ) {
					foreach ( $tasks as $functions ) {
						foreach ( $functions as $function ) {
							if ( ! ( $function == 1 ) ) {
								if ( is_array( $function ) ) {
									// probably object and function
									if ( is_object( $function[0] ) ) {
										$info = get_class( $function[0] ) . '::' . $function[1];
									} else {
										$info = print_r( $function, true );
									}
									//$action = '<br/>' . $priority . '&nbsp;' . $info;
									$action = $info;
								} else {
									//$action = '<br/>' . $priority . '&nbsp;' . $function;
									$action = $function;
								}
							}
						}
					}
				}
			}
		}

		return $action;
	}

	/**
	 * Executes a list of cron jobs immediately.
	 *
	 * Executes an event by scheduling a new single event with the same arguments.
	 *
	 * @param array $jobs
	 *
	 * @return bool
	 */
	public function bulk_run( $jobs ) {
		if ( ! is_array( $jobs ) ) {
			$jobs = array();

			return false;
		}
		$crons = _get_cron_array();

		delete_transient( 'doing_cron' );
		foreach ( $jobs as $timestamp => $job ) {
			$job = (array) $job; //Avoid warning "Invalid argument supplied for foreach()"
			foreach ( $job as $event => $md5 ) {
				if ( isset( $crons[ $timestamp ][ $event ][ $md5 ] ) ) {
					$args = $crons[ $timestamp ][ $event ][ $md5 ]['args'];
					/**
					 * Wordpress will not schedule an event if the execution time is withing the next 10 minutes.
					 * We have some other ways to "run" the cron job
					 *
					 * 1- Use do_action_ref_array method to call the action directly.
					 *    The problem with this implementation is that long running actions will slowdown the page load.
					 *    NOT A GOOD IDEA
					 *
					 * 2- Schedule a single event that points to an action actually triggers the cron job.
					 *
					 * 3- Schedule a single event using wp_schedule_single_event. If you want to run an event that
					 *    is about to be executed in the next 10 minutes Wordpress will NOT schedule that event
					 *
					 * 4- If the event is about to be executed in the next 10 minutes, them reschedule the event for now
					 *
					 * 5- schedule a single event without using wp_schedule_single_event function and avoid the 10 minutes error
					 *
					 * 6- Simple reschedule the event
					 */

					//we are using approach 5
					$event = apply_filters( 'schedule_event', $event );

					// A plugin disallowed this event
					if ( ! $event ) {
						continue;
					}

					$crons[ time() - 1 ][ $event ][ $md5 ] = array(
						'schedule' => false, //single run
						'args'     => $args,
					);
				}
			}
		}
		/**
		 * Sort the array
		 */
		uksort( $crons, 'strnatcasecmp' );

		/**
		 * Add new events to the cron schedule array
		 */
		$res = _set_cron_array( $crons );

		/**
		 * Trigger cron jobs
		 */
		spawn_cron();

		return $res;
	}

	/**
	 * Deletes a cron job.
	 *
	 * @param array $jobs
	 *
	 * @return bool
	 */
	public function bulk_delete( $jobs ) {
		$crons = _get_cron_array();

		$jobs = (array) $jobs;
		foreach ( $jobs as $time => $job ) {
			$job = (array) $job; //Avoid warning "Invalid argument supplied for foreach()"
			foreach ( $job as $name => $md5 ) {
				if ( isset( $crons[ $time ][ $name ][ $md5 ] ) ) {
					$args = $crons[ $time ][ $name ][ $md5 ]['args'];
					wp_unschedule_event( $time, $name, $args );
				}
			}
		}

		return true;
	}

	/**
	 * Adds a new cron job.
	 *
	 * @param int    $time     A GMT time that the event should be run at.
	 * @param string $schedule The recurrence of the cron event.
	 * @param string $hookname The name of the hook to execute.
	 * @param array  $args     Arguments to add to the cron event.
	 *
	 * @return bool
	 */
	public function add( $time, $schedule, $hookname, $args ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		if ( '__single_run' === $schedule ) {
			return wp_schedule_single_event( $time, $hookname, $args );
		} else {
			return wp_schedule_event( $time, $schedule, $hookname, $args );
		}
	}

	/**
	 * Deletes a cron job.
	 *
	 * @param string $job  The hook name of the event to delete.
	 * @param string $md5  The cron event signature.
	 * @param string $time The GMT time that the event would be run at.
	 *
	 * @return bool
	 */
	public function delete( $job, $md5, $time ) {
		$crons = _get_cron_array();
		if ( isset( $crons[ $time ][ $job ][ $md5 ] ) ) {
			$args = $crons[ $time ][ $job ][ $md5 ]['args'];
			wp_unschedule_event( $time, $job, $args );

			return true;
		}

		return false;
	}

	/**
	 * Edits a cron job
	 *
	 * @param string $job
	 * @param string $md5
	 * @param int    $time
	 * @param string $new_schedule The new schedule for the cron job
	 * @param int    $new_time     The new execution time
	 * @param array  $new_args     The new arguments to pass to the action
	 *
	 * @return bool
	 */
	public function edit( $job, $md5, $time, $new_schedule, $new_time, $new_args = array() ) {

		$deleted = $this->delete( $job, $md5, $time );
		if ( ! $deleted ) {
			return false;
		}

		return $this->add( $new_time, $new_schedule, $job, $new_args );

	}
}
