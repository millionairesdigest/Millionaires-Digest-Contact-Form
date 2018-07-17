<?php
/**
 * Plugin action handle file.
 *
 * @package millionaires-digest-user-contact-form
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BPUCF_Action_Handler
 */
class BPUCF_Action_Handler {

	/**
	 * BPUCF_Action_Handler constructor.
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Callback to handle action.
	 */
	public function setup() {
		add_action( 'bp_template_redirect', array( $this, 'process' ) );
	}

	/**
	 * Call process form function by checking whose form is submitted.
	 */
	public function process() {

		if ( isset( $_POST['bpucf-save-settings-button'] ) ) {
			$this->process_settings_form();
		} elseif ( isset( $_POST['bpucf-contact-me-submit-button'] ) ) {
			$this->process_contact_me_form();
		}
	}

	/**
	 * Process user save settings form.
	 */
	public function process_settings_form() {

		if ( ! bp_is_my_profile() && ! is_super_admin() ) {
			return;
		}

		$referrer = wp_get_referer();
		$slug     = bpucf_get_nav_slug();

		if ( ! $referrer ) {
			$referrer = bp_displayed_user_domain() . bp_get_settings_slug() . '/' . $slug;
		}

		$nonce             = $_POST['_wpnonce'];
		$show_contact_form = $_POST['bpucf-show-contact-form'];
		$bpucf_new_email   = empty( $_POST['bpucf-contact-email'] ) ? '' : $_POST['bpucf-contact-email'];

		if ( ! wp_verify_nonce( $nonce, 'bpucf-save-settings' ) ) {
			bp_core_add_message( __( 'Invalid action.', 'millionaires-digest-user-contact-form' ) );
			bp_core_redirect( $referrer );
		}

		$data = array(
			'show_contact_form' => $show_contact_form,
		);

		if ( ! empty( $bpucf_new_email ) ) {

			if ( ! is_email( $bpucf_new_email ) ) {
				bp_core_add_message( __( 'Please enter a valid email.', 'millionaires-digest-user-contact-form' ), 'error' );
				bp_core_redirect( $referrer );
			}

			$data['contact_email'] = $bpucf_new_email;
		}

		update_user_meta( bp_displayed_user_id(), '_bpucf_settings', $data );

		bp_core_add_message( __( 'Settings updated.', 'millionaires-digest-user-contact-form' ), 'success' );
		bp_core_redirect( $referrer );
	}

	/**
	 * Process contact me form.
	 */
	public function process_contact_me_form() {

		$referrer = bpucf_get_referer();

		$nonce = $_POST['_wpnonce'];

		if ( ! wp_verify_nonce( $nonce, 'bpucf-send-mail' ) ) {
			bp_core_add_message( __( 'Invalid action.', 'millionaires-digest-user-contact-form' ) );
			bp_core_redirect( $referrer );
		}

		if ( ! bpucf_current_user_can_contact( bp_displayed_user_id() ) ) {
			bp_core_add_message( __( 'You are not allowed to contact this user.', 'millionaires-digest-user-contact-form' ), 'error' );
			bp_core_redirect( $referrer );
		}

		$this->validate_contact_form( $referrer );

		$file = '';
		if ( bpucf_is_attachment_allowed() && ! empty( $_FILES['attachment'] ) ) {
			$attachment = $this->process_attachment();
			if ( ! empty( $attachment['file'] ) ) {
				$file = $attachment['file'];
			}
		}

		// the to email address should be set to no-reply@domain.com.
		$to = bpucf_get_user_email( bp_displayed_user_id() );

		$prefix = bpucf_get_email_subject_prefix();
		$sender_email = empty( $_POST['email'] ) ? '' : $_POST['email'];

		$subject = $_POST['subject'];
		$message .= "\r\n". sprintf( __( 'Name: %s', 'millionaires-digest-user-contact-form' ), $_POST['name'] );
		$message .= "\r\n" .sprintf( __( 'Email: %s', 'millionaires-digest-user-contact-form' ), $sender_email );
		$message .= "\r\n" .sprintf( __( 'Subject: %s', 'millionaires-digest-user-contact-form' ), $_POST['subject'] );
		$message .= "\r\n" .sprintf( __( 'Message: %s', 'millionaires-digest-user-contact-form' ), $_POST['message'] );
		$message .="\r\n";

		$headers = array();

		$extra = array();
		$extra[] = sprintf( __( 'Contacted User Name: %s', 'millionaires-digest-user-contact-form' ), bp_get_displayed_user_fullname() );
		$extra[] = sprintf( __( 'Contacted User Link: %s', 'millionaires-digest-user-contact-form' ), bp_displayed_user_domain() );

		$sent = true;

		$extra_message = $message ."\r\n" . join("\r\n", $extra ) ."\r\n";

		// Send an email to the contacted user.
		if ( ! $this->mail( $to, $prefix . "\r\n" . $subject, $message , $headers, $file, 'user' ) ) {
			$sent = false;
		}

		// send mail to admin if needed
		// Should we send an email to admin too?
		if ( bpucf_get_option( 'acknowledge_admin' ) ) {

			$admin_email = bpucf_get_option( 'admin_email' );
			if ( $admin_email && is_email( $admin_email ) ) {
				$this->mail( $admin_email, $prefix . '' . $subject, $extra_message, $headers, $file, 'admin' );
			}
		}

		// Send a copy to the sender as acknowledgement.
		if ( bpucf_get_option( 'allow_acknowledge' ) && ! empty( $sender_email ) ) {
			if ( ! is_email( $sender_email ) ) {
				bp_core_add_message( __( 'Please enter a valid email.', 'millionaires-digest-user-contact-form' ), 'error' );
				bp_core_redirect( $referrer );
			}

			if ( ! $this->mail( $sender_email, __( 'Acknowledgement: ', 'millionaires-digest-user-contact-form' ) . $subject, $extra_message, $headers, $file, 'sender' ) ) {
				$sent = false;
			}
		}

		if ( $file ) {
			@unlink( $file );
		}

		// Check if mails were sent successfully.
		if ( ! $sent ) {
			bp_core_add_message( __( 'Something went wrong. Please try again.', 'millionaires-digest-user-contact-form' ), 'error' );
			return;
		}

		bp_core_add_message( __( 'Message sent successfully.', 'millionaires-digest-user-contact-form' ), 'success' );
	}


	/**
	 * Send email to the given address.
	 *
	 * @param string $to email address.
	 * @param string $subject subject.
	 * @param string $message message.
	 * @param array  $headers headers.
	 * @param string $file file path.
	 * @param string $context content type(admin|sender|user).
	 *
	 * @return bool
	 */
	private function mail( $to, $subject, $message, $headers, $file, $context ) {
		// Developers, use this filter to modify the subject/message.
		$subject = apply_filters( 'bpucf_message_subject', $subject, $context );
		$message = apply_filters( 'bpucf_message_content', $message, $context );
		$headers = apply_filters( 'bpucf_message_headers', $headers, $context );

		return wp_mail( $to, sanitize_text_field( $subject ), sanitize_textarea_field( $message ), $headers, $file );
	}
	/**
	 * Validate contact me form check all required fields are not empty.
	 *
	 * @param string $redirect where to redirect if validation fails.
	 */
	public function validate_contact_form( $redirect ) {

		$fields = bpucf_get_form_fields();

		foreach ( $fields as $field ) {
			if ( ! $field['required'] ) {
				continue;
			}

			// this field is required.
			$name = $field['name'];

			if ( empty( $_POST[ $name ] ) ) {
				bp_core_add_message( $field['label'] . __( ' is required', 'millionaires-digest-user-contact-form' ), 'error' );
				bp_core_redirect( $redirect );
			}
		}
	}

	/**
	 * Process attachment form contact me form
	 *
	 * On failure, returns $overrides['upload_error_handler'](&$file, $message ) or array( 'error'=>$message )
	 *
	 * @return array
	 */
	public function process_attachment() {

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$file = wp_handle_upload( $_FILES['attachment'], array( 'action' => $_POST['action'] ) );

		return $file;
	}
}

new BPUCF_Action_Handler();
