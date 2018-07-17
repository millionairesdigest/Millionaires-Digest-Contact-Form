<?php

if ( bp_is_my_profile() ) : ?>
<?php endif; ?>

<form name="bpucf-contact-me-form" class="standard-form bp-user-contact-form" method="post" enctype="multipart/form-data">
	<?php //error//feedback ?>

	<!-- You can either override this function or just create your own form -->
	<?php bpucf_generate_form_fields( bpucf_get_sorted_form_fields() ); ?>
	<?php wp_nonce_field( 'bpucf-send-mail' ); ?>
	<input type="submit" name="bpucf-contact-me-submit-button" value="<?php _e( 'Send Message', 'millionaires-digest-user-contact-form' ); ?>">
</form>
