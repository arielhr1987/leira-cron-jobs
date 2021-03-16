<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/arielhr1987
 * @since             1.0.0
 * @package           Leira_Cron_Jobs
 *
 * @wordpress-plugin
 * Plugin Name:       Cron Jobs
 * Plugin URI:        https://github.com/arielhr1987/leira-cron-jobs
 * Description:       Cron Jobs is a simple, but powerful plugin that will help you to manage your site cron jobs easily.
 * Version:           1.2.8
 * Author:            Ariel
 * Author URI:        https://leira.dev
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       leira-cron-jobs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LEIRA_CRON_JOBS_VERSION', '1.2.8' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-leira-cron-jobs-activator.php
 */
function activate_leira_cron_jobs() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-leira-cron-jobs-activator.php';
	Leira_Cron_Jobs_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-leira-cron-jobs-deactivator.php
 */
function deactivate_leira_cron_jobs() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-leira-cron-jobs-deactivator.php';
	Leira_Cron_Jobs_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_leira_cron_jobs' );
register_deactivation_hook( __FILE__, 'deactivate_leira_cron_jobs' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-leira-cron-jobs.php';

/**
 * Helper method to get the main instance of the plugin
 *
 * @return Leira_Cron_Jobs
 * @since    1.2.0
 * @access   global
 */
function leira_cron_jobs() {
	return Leira_Cron_Jobs::instance();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
leira_cron_jobs()->run();
