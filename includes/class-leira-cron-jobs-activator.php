<?php
/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Leira_Cron_Jobs
 * @subpackage Leira_Cron_Jobs/includes
 * @author     Ariel <arielhr1987@gmail.com>
 */
class Leira_Cron_Jobs_Activator{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// If there's never been a cron event, _get_cron_array will return false.
		if ( _get_cron_array() === false ) {
			_set_cron_array( array() );
		}
	}

}
