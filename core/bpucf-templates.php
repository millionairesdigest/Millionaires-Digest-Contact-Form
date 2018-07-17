<?php

/**
 * Render contact me form.
 */
function bpucf_render_form() {
	add_action( 'bp_template_content', 'bpucf_load_form' );
	bp_core_load_template( array( 'members/single/plugins' ) );
}

/**
 * Load form content
 */
function bpucf_load_form() {

	$located = locate_template( array( 'buddypress/members/single/bpucf-form.php' ), false );
	if ( ! $located ) {
		$located = bpucf()->get_path() . 'templates/buddypress/members/single/bpucf-form.php';
	}

	require $located;
}


/**
 * Render settings form.
 */
function bpucf_settings_form() {
	add_action( 'bp_template_content', 'bpucf_load_settings_form' );
	bp_core_load_template( array( 'members/single/plugins' ) );
}
/**
 * Render settings form
 */
function bpucf_load_settings_form() {

	$settings = get_user_meta( bp_displayed_user_id(), '_bpucf_settings', true );

	$checked = 1;

	if ( ! empty( $settings ) ) {
		$checked = $settings['show_contact_form'];
	}

	?>
	<form method="post" action="" name="bpucf_user_settings_form">
		<table>
			<?php if ( bpucf_get_option( 'allow_user_show_hide_form' ) ) : ?>
				<tr>
					<td><?php _e( 'Show contact form', 'millionaires-digest-user-contact-form' ) ?></td>
					<td>
						<input type="radio" value="1" name="bpucf-show-contact-form" <?php checked( $checked, 1 )?>><?php _e( 'Yes', 'millionaires-digest-user-contact-form' ) ?>
						<input type="radio" value="0" name="bpucf-show-contact-form" <?php checked( $checked, 0 )?>><?php _e( 'No', 'millionaires-digest-user-contact-form' ) ?>
					</td>
				</tr>
			<?php endif; ?>
			<?php if ( bpucf_get_option( 'allow_user_new_email' ) ) : ?>
				<tr>
					<td><?php _e( 'Email', 'millionaires-digest-user-contact-form' ) ?></td>
					<td><input type="text" name="bpucf-contact-email" value="<?php echo esc_attr( bpucf_get_user_email( bp_displayed_user_id() ) ); ?>"></td>
				</tr>
			<?php endif; ?>
		</table>
		<?php wp_nonce_field( 'bpucf-save-settings' ); ?>
		<input type="submit" name="bpucf-save-settings-button" value="<?php _e( 'Save', 'millionaires-digest-user-contact-form' ); ?>">
	</form>
	<?php
}
