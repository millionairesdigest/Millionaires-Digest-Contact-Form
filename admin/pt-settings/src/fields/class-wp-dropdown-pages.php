<?php
/**
 * WordPress DropDown Pages Field class
 *
 * @package Press_Themes\PT_Settings
 */

namespace Press_Themes\PT_Settings\Fields;

// Exit if the file is accessed directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use Press_Themes\PT_Settings\Field;

/**
 * Class WP_DropDown_pages
 *
 * @package Press_Themes\PT_Settings\Fields.
 */
class WP_DropDown_pages extends Field {

	/**
	 * WP_DropDown_pages constructor.
	 *
	 * @param array $field settings.
	 */
	public function __construct( $field ) {
		parent::__construct( $field );
	}

	/**
	 * Displays wordpress dropdown pages for a settings field
	 *
	 * @param array $args settings field args.
	 */
	public function render( $args ) {

		$args['name']     = $args['option_key'];
		$args['selected'] = $args['value'];
		$args['echo']     = 0;

		$action = '';

		if ( false !== strpos( $args['name'], 'blogin_login_page' ) ) {
			$action = 'blogin_create_login_page';
		} elseif ( false !== strpos( $args['name'], 'blogin_logout_page' ) ) {
			$action = 'blogin_create_logout_page';
		} elseif ( false !== strpos( $args['name'], 'blogin_forgot_password_page' ) ) {
			$action = 'blogin_create_forgot_password_page';
		} elseif ( false !== strpos( $args['name'], 'blogin_reset_password_page' ) ) {
			$action = 'blogin_create_reset_password_page';
		}

		$dropdown = wp_dropdown_pages( $args );

		if ( empty( $args['selected'] ) ) {
			$dropdown .= '<a class="blogin-page-create-btn" href="#" data-nonce="' . wp_create_nonce( 'blogin-create-page' ) . '" data-action="' . esc_attr( $action ) . '">' . __( 'Create Page', 'branded-login' ) . '</a><div id="' . $action . '"></div>';
		}

		echo $dropdown;
	}
}
