<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Leira_Cron_Jobs
 * @subpackage Leira_Cron_Jobs/includes
 * @author     Ariel <arielhr1987@gmail.com>
 *
 * @property Leira_Cron_Jobs_Manager       manager
 * @property Leira_Cron_Jobs_Notifications notify
 */
class Leira_Cron_Jobs{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Leira_Cron_Jobs_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * Singleton instance
	 *
	 * @var null
	 */
	protected static $instance = null;

	/**
	 * The Singleton method
	 *
	 * @return Leira_Cron_Jobs|null
	 */
	public static function instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	protected function __construct() {
		if ( defined( 'LEIRA_CRON_JOBS_VERSION' ) ) {
			$this->version = LEIRA_CRON_JOBS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'leira-cron-jobs';

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Leira_Cron_Jobs_Loader. Orchestrates the hooks of the plugin.
	 * - Leira_Cron_Jobs_i18n. Defines internationalization functionality.
	 * - Leira_Cron_Jobs_Admin. Defines all hooks for the admin area.
	 * - Leira_Cron_Jobs_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-leira-cron-jobs-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-leira-cron-jobs-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		if ( is_admin() ) {
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-leira-cron-jobs-admin.php';

			/**
			 * Class to handle all notifications
			 */
			require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-leira-cron-jobs-notifications.php';
		}

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-leira-cron-jobs-public.php';

		$this->loader = new Leira_Cron_Jobs_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Leira_Cron_Jobs_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function set_locale() {

		$plugin_i18n = new Leira_Cron_Jobs_i18n();
		$this->get_loader()->set( 'i18n', $plugin_i18n );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function define_admin_hooks() {

		if ( is_admin() ) {

			$plugin_admin = new Leira_Cron_Jobs_Admin( $this->get_plugin_name(), $this->get_version() );
			$this->get_loader()->set( 'admin', $plugin_admin );

			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
			$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
			$this->loader->add_action( 'admin_menu', $plugin_admin, 'admin_menu' );
			$this->loader->add_action( 'wp_ajax_inline-save-cron-job', $plugin_admin, 'ajax_save' );

			$this->loader->add_filter( 'set-screen-option', $plugin_admin, 'filter_set_screen_option', 10, 3 );

			/**
			 * Rate us
			 */
			$this->loader->add_filter( 'admin_footer_text', $plugin_admin, 'admin_footer_text', 1000 );
			$this->loader->add_action( 'wp_ajax_leira-cron-jobs-footer-rated', $plugin_admin, 'footer_rated' );

			$plugin_notifications = new Leira_Cron_Jobs_Notifications();
			$this->get_loader()->set( 'notify', $plugin_notifications );

		}

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function define_public_hooks() {

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @return    string    The name of the plugin.
	 * @since     1.0.0
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    Leira_Cron_Jobs_Loader    Orchestrates the hooks of the plugin.
	 * @since     1.0.0
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string    The version number of the plugin.
	 * @since     1.0.0
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Gets an instance from the loader
	 *
	 * @param string $key
	 *
	 * @return mixed|null The instance
	 *
	 * @since     1.2.0
	 * @access    public
	 *
	 */
	public function __get( $key ) {
		return $this->get_loader()->get( $key );
	}

	/**
	 * Sets an instance in the loader
	 *
	 * @param string $key
	 * @param mixed  $value
	 *
	 * @since     1.2.0
	 * @access    public
	 *
	 */
	public function __set( $key, $value ) {
		$this->get_loader()->set( $key, $value );
	}

}
