<?php
/**
 * Plugin functions file.
 *
 * @package millionaires-digest-user-contact-form
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Press_Themes\PT_Settings\Page;

/**
 * Class BPUCF_Admin
 */
class BPUCF_Admin {

	/**
	 * Settings name.
	 *
	 * @var string
	 */
	private $option_name = 'bpucf-settings';
	/**
	 * Menu slug
	 *
	 * @var string
	 */
	private $menu_slug;

	/**
	 * Used to keep a reference of the Page, It will be used in rendering the view.
	 *
	 * @var \Press_Themes\PT_Settings\Page
	 */
	private $page;

	/**
	 * BPUCF_Admin constructor.
	 */
	public function __construct() {

		$this->menu_slug = 'millionaires-digest-user-contact-form-settings';

		add_action( 'admin_init', array( $this, 'init' ) );

		if ( is_multisite() && bpucf()->is_network_active() ) {
			add_action( 'network_admin_menu', array( $this, 'add_network_menu' ) );
			// WP setting api does not save in site meta, we will sync.
			add_action( 'pre_update_option_' . $this->option_name, array( $this, 'sync_options' ), 10, 2 );
		} else {
			// for non multisite, non network active.
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
		}
	}


	/**
	 * Add Menu(Dashboard->Settings->BuddyPress User Contact Form)
	 */
	public function add_menu() {

		add_options_page(
			_x( 'BuddyPress User Contact Form', 'Admin settings page title', 'millionaires-digest-user-contact-form' ),
			_x( 'BuddyPress User Contact Form', 'Admin settings menu label', 'millionaires-digest-user-contact-form' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render' )
		);
	}

	/**
	 * Add Option page for network.
	 */
	public function add_network_menu() {

		add_submenu_page(
			'settings.php',
			_x( 'BuddyPress User Contact Form', 'Admin settings page title', 'millionaires-digest-user-contact-form' ),
			_x( 'BuddyPress User Contact Form', 'Admin settings menu label', 'millionaires-digest-user-contact-form' ),
			'delete_users',
			$this->menu_slug,
			array( $this, 'render' )
		);
	}


	/**
	 * Show/render the setting page
	 */
	public function render() {
		$this->page->render();
	}

	/**
	 * Initialize the admin settings panel and fields
	 */
	public function init() {
		if ( $this->is_network_admin() || $this->is_options_page() || $this->is_setting_page() ) {
			$this->register_settings();
		}
	}

	/**
	 * Initialize the admin settings panel and fields
	 */
	public function register_settings() {

		$page = new Page( $this->option_name );

		if ( is_multisite() && bpucf()->is_network_active() ) {
			$page->set_network_mode();
		}

		$panel = $page->add_panel( 'general', _x( 'General', 'Admin settings panel title', 'millionaires-digest-user-contact-form' ) );

		$section = $panel->add_section( 'settings', _x( 'Settings', 'Admin settings section title', 'millionaires-digest-user-contact-form' ) );

		$roles = get_editable_roles();

		$user_roles = array();
		$user_roles['all'] = __( 'All Members', 'millionaires-digest-user-contact-form' );

		foreach ( $roles as $role => $detail ) {
			$user_roles[ $role ] = $detail['name'];
		}

		$who_can_contact_roles = $user_roles;
		unset( $who_can_contact_roles['all'] );

		$who_can_contact_roles = array_merge( array(
			'all'   => __( 'Anyone', 'millionaires-digest-user-contact-form' ),
			'logged_in' => __( 'Logged In Members', 'millionaires-digest-user-contact-form' ),
		), $who_can_contact_roles );

		$defaults = bpucf_get_default_options();

		$section->add_fields( array(
			array(
				'name'    => 'nav_slug',
				'label'   => _x( 'Contact form slug', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'type'    => 'text',
				'default' => $defaults['nav_slug'],
				'desc'  => __( 'It is the slug used for user contact page. Please do not use space.', 'millionaires-digest-user-contact-form' ),
			),
			array(
				'name'    => 'allowed_contactable_roles',
				'label'   => _x( 'Who Can be contacted?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'type'    => 'multicheck',
				'default' => $defaults['allowed_contactable_roles'],
				'options' => $user_roles,
				'desc'  => __( "If you select 'All Members' all members will have the ability to receive message", 'millionaires-digest-user-contact-form' ),
			),
			array(
				'name'    => 'who_can_contact',
				'label'   => _x( 'Who can contact?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['who_can_contact'],
				'options' => $who_can_contact_roles,
				'type'    => 'multicheck',
			),
			array(
				'name' => 'allow_user_show_hide_form',
				'label' => _x( 'Allow user to show hide contact form?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['allow_user_show_hide_form'],
				'type' => 'radio',
				'options' => array(
					1 => __( 'Yes', 'millionaires-digest-user-contact-form' ),
					0 => __( 'No', 'millionaires-digest-user-contact-form' ),
				),
				'desc'    => __( ' If you enable it,  your members will be able to show/hide contact form on their profile', 'millionaires-digest-user-contact-form' ),
			),
			array(
				'name' => 'allow_user_new_email',
				'label' => _x( 'Allow user to use another email for contact messages?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['allow_user_new_email'],
				'type' => 'radio',
				'options' => array(
					1 => __( 'Yes', 'millionaires-digest-user-contact-form' ),
					0 => __( 'No', 'millionaires-digest-user-contact-form' ),
				),
				'desc' => __( "By default, user's registered email is used to send message. If enabled, users can specify a new email to recieve message.", 'millionaires-digest-user-contact-form' ),
			),
			array(
				'name' => 'allow_acknowledge',
				'label' => _x( 'Allow sender to receive a copy of email?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['allow_acknowledge'],
				'type' => 'radio',
				'options' => array(
					1 => __( 'Yes', 'millionaires-digest-user-contact-form' ),
					0 => __( 'No', 'millionaires-digest-user-contact-form' ),
				),
			),
			array(
				'name' => 'acknowledge_admin',
				'label' => _x( 'Send a copy to admin?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['acknowledge_admin'],
				'type' => 'radio',
				'options' => array(
					1 => __( 'Yes', 'millionaires-digest-user-contact-form' ),
					0 => __( 'No', 'millionaires-digest-user-contact-form' ),
				),
			),
			array(
				'name' => 'admin_email',
				'label' => _x( 'Admin email for receiving the message?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['admin_email'],
				'type' => 'text',
			),
			array(
				'name'    => 'email_subject_prefix',
				'label'   => _x( 'Email subject prefix', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'type'    => 'text',
				'default' => $defaults['email_subject_prefix'],
			),

			array(
				'name'    => 'allow_attachment',
				'label'   => _x( 'Allow file attachment?', 'Admin settings', 'millionaires-digest-user-contact-form' ),
				'default' => $defaults['allow_attachment'],
				'options' => array(
					1 => __( 'Yes', 'millionaires-digest-user-contact-form' ),
					0 => __( 'No', 'millionaires-digest-user-contact-form' ),
				),
				'type'    => 'radio',
				'desc'    => __( 'If you enable it, users will be able to send files as attachment with the message.', 'millionaires-digest-user-contact-form' ),
			),
		) );

		// Save page for future reference.
		$this->page = $page;

		do_action( 'bpucf_register_settings', $page );

		// allow enabling options.
		$page->init();
	}

	/**
	 * Sync option to the site meta.
	 *
	 * @param mixed $value value of the meta.
	 * @param mixed $old_value old value.
	 *
	 * @return mixed
	 */
	public function sync_options( $value, $old_value ) {
		update_site_option( $this->option_name, $value );

		return $value;
	}

	/**
	 * Is it the options.php page that saves settings?
	 *
	 * @return bool
	 */
	private function is_options_page() {
		global $pagenow;

		// We need to load on options.php otherwise settings won't be reistered.
		if ( 'options.php' === $pagenow ) {
			return true;
		}

		return false;
	}


	/**
	 * Is it non multisite settings page?
	 *
	 * @return bool
	 */
	public function is_setting_page() {
		return isset( $_GET['page'] ) && ( $this->menu_slug === $_GET['page'] );
	}

	/**
	 * Is it multisite settings page?.
	 *
	 * @return bool
	 */
	public function is_network_admin() {
		return is_network_admin() && isset( $_GET['page'] ) && ( $this->menu_slug === $_GET['page'] ) && bpucf()->is_network_active();
	}

}

new BPUCF_Admin();
