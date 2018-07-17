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
 * Get default settings
 *
 * @return array
 */
function bpucf_get_default_options() {

	return array(
		'nav_slug'                  => 'contact-me',
		'allowed_contactable_roles' => array( 'all' => 'all' ),
		'allow_user_show_hide_form' => 0,
		'allow_user_new_email'      => 1,
		'acknowledge_admin'         => 1,
		'allow_acknowledge'         => 1,
		'email_subject_prefix'      => __( 'New Message:', 'millionaires-digest-user-contact-form' ),
		'who_can_contact'           => array( 'all' => 'all' ),
		'allow_attachment'          => 0,
		'admin_email'               => get_option( 'admin_email' ),
	);
}

/**
 * Get option value if not set pick from default settings
 *
 * @param string $key option value key.
 * @param mixed  $default fallback value.
 *
 * @return mixed
 */
function bpucf_get_option( $key, $default = null ) {

	if ( is_multisite() && bpucf()->is_network_active() ) {
		$settings = get_site_option( 'bpucf-settings', bpucf_get_default_options() );
	} else {
		$settings = get_option( 'bpucf-settings', bpucf_get_default_options() );
	}

	if ( isset( $settings[ $key ] ) ) {
		return $settings[ $key ];
	}

	return $default;
}

/**
 * Get allowed roles which can be contacted.
 *
 * @return array
 */
function bpucf_get_allowed_contactable_roles() {
	return bpucf_get_option( 'allowed_contactable_roles' );
}

/**
 * Email subject prefix
 *
 * @return string
 */
function bpucf_get_email_subject_prefix() {
	return bpucf_get_option( 'email_subject_prefix' );
}

/**
 * Who can contact user.
 *
 * @return string
 */
function bpucf_get_who_can_contact() {
	return bpucf_get_option( 'who_can_contact', array( 'all' ) );
}

/**
 * Get user saved email
 *
 * @param int $user_id user id.
 *
 * @return string
 */
function bpucf_get_user_email( $user_id ) {

	$settings = get_user_meta( $user_id, '_bpucf_settings', true );

	if ( ! empty( $settings['contact_email'] ) && is_email( $settings['contact_email'] ) ) {
		return $settings['contact_email'];
	}

	$user = get_userdata( $user_id );

	return $user->user_email;
}

/**
 * Get form fields for user contact form.
 *
 * @return array Fields
 */
function bpucf_get_form_fields() {

	$fields = array(
		'name'       => array(
			'name'     => 'name',
			'type'     => 'text',
			'label'    => __( 'Name', 'millionaires-digest-user-contact-form' ),
			'required' => true,
			'position' => 0,
		),
		'email'      => array(
			'name'     => 'email',
			'type'     => 'text',
			'label'    => __( 'Email', 'millionaires-digest-user-contact-form' ),
			'required' => true,
			'position' => 1,
		),
		'subject'    => array(
			'name'     => 'subject',
			'type'     => 'text',
			'label'    => __( 'Subject', 'millionaires-digest-user-contact-form' ),
			'required' => true,
			'position' => 2,
		),
		'message'    => array(
			'name'     => 'message',
			'type'     => 'textarea',
			'label'    => __( 'Message ', 'millionaires-digest-user-contact-form' ),
			'required' => true,
			'position' => 3,
		),
	);

	if ( bpucf_is_attachment_allowed() ) {
		$fields['attachment'] = array(
			'name'     => 'attachment',
			'type'     => 'file',
			'label'    => __( 'Attach File', 'millionaires-digest-user-contact-form' ),
			'required' => false,
			'position' => 4,
		);
	}


	/**
	 * Use this filter to add extra fields.
	 */
	return apply_filters( 'bpucf_form_fields', $fields );
}

/**
 * Get sorted form fields.
 *
 * @return array
 */
function bpucf_get_sorted_form_fields() {

	$fields = bpucf_get_form_fields();
	uasort( $fields, 'bpucf_compare_field_positions' );

	return $fields;
}

/**
 * Compare function for comparing fields by position
 *
 * @param array $field1 field.
 * @param array $field2 another field.
 *
 * @return int
 */
function bpucf_compare_field_positions( $field1, $field2 ) {

	if ( $field1['position'] == $field2['position'] ) {
		return 0;
	} elseif ( $field1['position'] < $field2['position'] ) {
		return - 1;
	} else {
		return 1;
	}
}
/**
 * Check if current screen is contact me screen or not.
 *
 * @return bool
 */
function bpucf_is_contact_me_screen() {

	$slug = bpucf_get_nav_slug();
	if (  bp_is_user() && ! bp_is_settings_component() && bp_is_current_action( $slug ) ) {
		return true;
	}

	return false;
}

/**
 * Get Required fields array
 *
 * @return array Required fields
 */
function bpucf_get_required_fields() {
	$fields = bpucf_get_form_fields();
	$required_fields = array();

	foreach ( $fields as $field ) {
		if ( $field['required'] ) {
			$required_fields[] = $field['name'];
		}
	}
	return $required_fields;
}

/**
 * Is form visible for the visiting/visited user context?
 *
 * @param int $user_id displayed user id.
 * @return bool
 */
function bpucf_is_form_visible( $user_id ) {
	 return  bpucf_is_contact_form_enabled_for_user( $user_id ) && bpucf_current_user_can_contact( $user_id );
}


/**
 * Is the form enabled by admin/user and should it be available.
 *
 * @param int $user_id user id.
 *
 * @return bool
 */
function bpucf_is_contact_form_enabled_for_user( $user_id ) {

	// If user role is not contactable, no need to proceed further.
	if ( ! bpucf_check_user_has_contactable_role( $user_id ) ) {
		return false;
	}

	// Check if the admin has allowed user to override settings?
	$user_can_override = bpucf_get_option( 'allow_user_show_hide_form' );

	if ( ! $user_can_override ) {
		return true;
	} elseif ( bpucf_has_user_enabled_contact_form( $user_id ) ) {
		return true;
	}

	return false;
}

/**
 * Check if the given user has contactable role.
 *
 * @param int $user_id user id.
 *
 * @return bool
 */
function bpucf_check_user_has_contactable_role( $user_id ) {
	$contactable_roles      = bpucf_get_option( 'allowed_contactable_roles', array() );

	if ( empty( $contactable_roles ) ) {
		return false;
	}
	// if all is allowed then always return true.
	if ( in_array( 'all', $contactable_roles ) ) {
		return true;
	}

	$user   = get_user_by( 'id', $user_id );
	if ( empty( $user ) ) {
		return false;
	}

	$user_roles = $user->roles;

	if ( empty( $user_roles ) ) {
		return false;
	}

	// check if the displayed user has contactable roles?
	if ( ! array_intersect( $contactable_roles, $user_roles ) ) {
		return false;
	}

	return true;
}

/**
 * Has  user enabled contact form?
 *
 * @param int $user_id user id.
 *
 * @return bool
 */
function bpucf_has_user_enabled_contact_form( $user_id ) {
	// Is form enabled for displayed user?.
	$user_settings      = get_user_meta( $user_id, '_bpucf_settings', true );
	$is_form_enabled    = is_array( $user_settings ) && isset( $user_settings['show_contact_form'] ) ? $user_settings['show_contact_form'] : true;
	return $is_form_enabled;
}

/**
 * Check if attachment is allowed.
 *
 * @return bool
 */
function bpucf_is_attachment_allowed() {
	return bpucf_get_option( 'allow_attachment', 0 );
}

/**
 * Get the nav slug.
 *
 * @return mixed
 */
function bpucf_get_nav_slug() {
	return bpucf_get_option( 'nav_slug', 'contact-me' );
}

/**
 * Return referrer url.
 *
 * @return bool|false|string
 */
function bpucf_get_referer() {

	$referrer = wp_get_referer();

	if ( empty( $referrer ) ) {

		$displayed_user_domain = bp_displayed_user_domain();

		$slug = bpucf_get_nav_slug();
		if ( bpucf_is_contact_me_screen() ) {
			$referrer = trailingslashit( $displayed_user_domain . $slug );
		} elseif ( bp_is_active( 'settings' ) ) {
			$referrer = trailingslashit( $displayed_user_domain . bp_get_settings_slug() . '/' . $slug );
		}
	}

	return $referrer;
}

/**
 * Generate form fields.
 *
 * @param array $fields fields array.
 */
function bpucf_generate_form_fields( $fields ) {

	$renderer = apply_filters( 'bpucf_generate_form_fields_renderer', 'bpucf_generate_html_form_field' );

	if ( ! $renderer || ! is_callable( $renderer ) ) {
		_e( 'The registered form renderer is invalid!', 'millionaires-digest-user-contact-form' );

		return;
	}

	foreach ( $fields as $field ) {
		echo call_user_func( $renderer, $field );
	}
}


/**
 * Generate html for the form field.
 *
 * @param array $field field array.
 *
 * @return string
 */
function bpucf_generate_html_form_field( $field ) {

	$type = $field['type'];

	$label = esc_html( $field['label'] );
	$name  = esc_attr( $field['name'] );
	$required = $field['required'] ? '<span class="bpucf-field-required">*</span>' : '';
	switch ( $type ) {

		case 'text':
			$html = "<label> {$label} {$required}<input type='text' name='{$name}'></label>";
			break;

		case 'textarea':
			$html = "<label> {$label}{$required}<textarea name='{$name}'></textarea></label>";
			break;

		case 'file':
			$html = "<label> {$label}{$required}<input type='file' name='{$name}'></label>";
			$html .="<input type='hidden' name='action' value='bpucf-attachment-upload' />";
			break;

		default:
			$html = '';
			break;
	}


	$html = apply_filters( 'bpucf_field_html', $html , $field, $type );

	return $html;
}
