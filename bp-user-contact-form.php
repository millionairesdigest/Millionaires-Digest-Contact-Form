<?php
/**
 * Main plugin file.
 *
 * @package millionaires-digest-user-contact-form
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Name: BuddyPress User Contact Form
 * Version: 1.0.3
 * Plugin URI: https://buddydev.com/plugins/millionaires-digest-user-contact-form/
 * Author: BuddyDev
 * Author URI: https://buddydev.com
 * Description: Plugins allow site admin to set role which have their contact me form.
 */

/**
 * Helper class.
 */
class BPUCF_User_Contact_Form {

	/**
	 * Singleton instance
	 *
	 * @var BPUCF_User_Contact_Form
	 */
	public static $instance = null;


	/**
	 * Plugin basename
	 *
	 * @var string
	 */
	private $basename;
	/**
	 * Holds plugins directory absolute path
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Holds plugins directory url.
	 *
	 * @var string
	 */
	private $url;


	/**
	 * BPUCF_User_Contact_Form constructor.
	 */
	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );

		$this->setup();
	}

	/**
	 * Get singleton instance
	 *
	 * @return BPUCF_User_Contact_Form
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Provide callbacks on various hooks.
	 */
	public function setup() {
		$this->basename = plugin_basename( __FILE__ );

		add_action( 'bp_loaded', array( $this, 'load' ) );
		add_action( 'plugins_loaded', array( $this, 'load_admin' ), 9998 );// pt-settings 1.0.2.
		add_action( 'bp_init', array( $this, 'load_textdomain' ), 2 );
		add_action( 'bp_enqueue_scripts', array( $this, 'load_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
	}

	/**
	 * Loading core
	 */
	public function load() {

		$files = array(
			'core/bpucf-functions.php',
			'core/bpucf-templates.php',
			'core/bpucf-permissions.php',
			'core/bpucf-hooks.php',
			'core/class-bpucf-action-handler.php',
		);

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$files[] = 'admin/pt-settings/pt-settings-loader.php';
			$files[] = 'admin/class-bpucf-admin.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Loading admin files.
	 */
	public function load_admin() {

		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		$files = array();

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$files[] = 'admin/pt-settings/pt-settings-loader.php';
			$files[] = 'admin/class-bpucf-admin.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}
	/**
	 * Load translations
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'millionaires-digest-user-contact-form', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Load assets
	 */
	public function load_assets() {

		if ( ! bpucf_is_contact_me_screen() ) {
			return '';
		}

		wp_register_style( 'bpucf_css', $this->url . 'assets/css/bpucf.css' );

		wp_register_script( 'bpucf_js', $this->url . 'assets/js/bpucf.js' );
	}

	/**
	 * Load admin assets
	 */
	public function load_admin_assets() {

	}

	/**
	 * Update settings on plugin activation.
	 */
	public function activate() {
		require_once $this->path . 'core/bpucf-functions.php';
		$settings = bpucf_get_default_options();

		$setting_name = 'bpucf-settings';

		if ( ! get_option( $setting_name ) ) {
			update_option( $setting_name, $settings );
		}

		// in case of multisite, let us save the default settings in site meta
		// (in case site admin plans to use it as network active).
		if ( is_multisite() && ! get_site_option( $setting_name ) ) {
			update_site_option( $setting_name, $settings );
		}
	}

	/**
	 * Check if the plugin is network active.
	 *
	 * @return bool
	 */
	public function is_network_active() {

		if ( ! is_multisite() ) {
			return false;
		}

		// Check the sitewide plugins array.
		$base    = $this->basename;
		$plugins = get_site_option( 'active_sitewide_plugins' );
		if ( ! is_array( $plugins ) || ! isset( $plugins[ $base ] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get plugin directory absolute path.
	 *
	 * @return string
	 */
	public function get_path() {
		return $this->path;
	}

	/**
	 * Get plugin directory url
	 *
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}
}

/**
 * Initialize application.
 *
 * @return BPUCF_User_Contact_Form
 */
function bpucf() {
	return BPUCF_User_Contact_Form::get_instance();
}

bpucf();
