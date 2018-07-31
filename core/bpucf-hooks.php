<?php
/**
 * Adds navigation menus.
 *
 * @package millionaires-digest-user-contact-form
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add new nav menu on user profile.
 */
function bpucf_add_nav_item() {

	$is_visible = bpucf_is_form_visible( bp_displayed_user_id() );

	if ( ! bp_is_user() || ! $is_visible ) {
		return;
	}

	$slug = bpucf_get_nav_slug();

	bp_core_new_nav_item( array(
			'name'		                => __( 'Contact Form', 'millionaires-digest-user-contact-form' ),
			'slug'		                => $slug,
			'screen_function'	        => 'bpucf_render_form',
			'default_subnav_slug'       => $slug,
			'show_for_displayed_user'   => $is_visible,
		)
	);
}

add_action( 'bp_setup_nav', 'bpucf_add_nav_item' );

/**
 * Add new settings tab if admin allowed to override settings.
 */
function bpucf_add_settings_menu() {

	if ( bpucf_get_option( 'allow_user_show_hide_form' ) || bpucf_get_option( 'allow_user_new_email' ) ) {

		$settings_slug = bp_get_settings_slug();
		$slug = bpucf_get_nav_slug();
		$show = is_super_admin() || bp_is_my_profile();

		bp_core_new_subnav_item( array(
			'name'            => __( 'Contact Form', 'millionaires-digest-user-contact-form' ),
			'slug'            => $slug,
			'parent_slug'     => $settings_slug,
			'position'        => 16,
			'parent_url'      => trailingslashit( bp_displayed_user_domain() . $settings_slug ),
			'screen_function' => 'bpucf_settings_form',
			'user_has_access' => $show,
		) );
	}
}

add_action( 'bp_settings_setup_nav', 'bpucf_add_settings_menu' );


/**
 * Add new contact button to member header.
 */
function bp_add_contact_button( $button ) {
	global $button;
	$profile_menu_label = $button->profile_menu_label;
	$profile_menu_slug  = $button->profile_menu_slug;
	$user_id = bp_displayed_user_id();
	$is_visible = bpucf_is_form_visible( bp_displayed_user_id() );
	
	if ( bp_is_my_profile() ) {
		return false;
	}

	if( ! bp_has_member_type( $user_id, 'brand' ) && ( ! bp_has_member_type( $user_id, 'famous-person' ) && ( ! bp_has_member_type( $user_id, 'organization' ) && ( ! bp_has_member_type( $user_id, 'millionaires-digest' ) && ( ! bp_has_member_type( $user_id, 'government' ) ) ) ) ) ) {
		return;
	}
	
	if ( $is_visible && ! bp_has_member_type( $user_id, 'brand' ) && ( ! bp_has_member_type( $user_id, 'organization' ) && ( ! bp_has_member_type( $user_id, 'government' ) ) ) ) {
				$contact_button_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . 'contact';
			?>
	<div id="bp-add-contact-me-btn" class="generic-button">
		<a href="<?php echo esc_attr( $contact_button_url ); ?>" class="contact-us-btn"><?php echo esc_html( 'Contact' . $profile_menu_label, 'contact-us-button' );
			?>	
		</a>
	</div>
	<?php
	}
	elseif ( $is_visible && ! bp_has_member_type( $user_id, 'famous-person' ) && ( ! bp_has_member_type( $user_id, 'millionaires-digest' ) ) ) {
		$contact_button_url = bp_core_get_userlink( bp_displayed_user_id(), false, true ) . $profile_menu_slug . 'contact';
			?>
	<div id="bp-add-contact-me-btn" class="generic-button">
		<a href="<?php echo esc_attr( $contact_button_url ); ?>" class="contact-me-btn"><?php echo esc_html( 'Contact Us' . $profile_menu_label, 'contact-me-button' );
			?>
		</a>
	</div>
	<?php
	}
}
add_action( 'bp_member_header_actions', 'bp_add_contact_button', 30 );
