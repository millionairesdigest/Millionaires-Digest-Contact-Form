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


/**
 * Check if current user can contact the given user or not
 *
 * @param int $user_id id of user who is being contacted.
 *
 * @return bool
 */
function bpucf_current_user_can_contact( $user_id ) {

	$can = false;

	$who_can_contact = bpucf_get_option( 'who_can_contact' );
	if ( ! $who_can_contact ) {
		$can = false;
	} elseif ( in_array( 'all', $who_can_contact ) ) {
		$can = true;
	} elseif ( in_array( 'loggedin', $who_can_contact ) ) {
		$can = is_user_logged_in();
	} elseif ( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$user_roles = $user->roles;
		if ( $user_roles && array_intersect( $who_can_contact, $user_roles ) ) {
			$can = true;
		}
	}

	// if can not contact but it is own profile or is super admin, set contact.
	if ( ! $can && ( is_super_admin() || bp_is_my_profile() ) ) {
		$can = true;
	}

	return apply_filters( 'bpucf_current_user_can_contact', $can, $user_id );
}
/**
 * Check if user can contact or send mail to the user.
 *
 * @param null|int $user_id id of the user.
 *
 * @return bool
 */
function bpucf_user_can_contact( $user_id = null ) {

	$who_can_contact = bpucf_get_who_can_contact();

	if ( 'all' === $who_can_contact ) {
		return true;
	} elseif ( 'logged_in' == $who_can_contact ) {
		return is_user_logged_in();
	}

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}





	return false;
}

/**
 * Check if user can be contacted or not.
 *
 * @param null|int $user_id id of the user.
 *
 * @return bool
 */
function bpucf_user_can_be_contacted( $user_id = null ) {

	$allowed_roles = bpucf_get_option( 'allowed_contact_roles' );

	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	if ( empty( $user_id ) ) {
		return false;
	}

	$user_roles = new WP_User( $user_id );
	$user_roles = $user_roles->roles;

	$common = array_intersect( $allowed_roles, $user_roles );

	if ( empty( $common ) ) {
		return false;
	}

	return true;
}
