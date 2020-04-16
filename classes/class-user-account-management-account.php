<?php
/**
 * Class file for the User_Account_Management_Account class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * Managing Account Settings
 */
class User_Account_Management_Account {

	public $option_prefix;
	public $version;
	public $slug;
	public $file;
	public $user_data;

	/**
	* Constructor which sets up account management pages
	*/
	public function __construct() {

		$this->option_prefix = user_account_management()->option_prefix;
		$this->version       = user_account_management()->version;
		$this->slug          = user_account_management()->slug;
		$this->transient     = user_account_management()->transient;
		$this->user_data     = user_account_management()->user_data;
		$this->cache         = user_account_management()->cache;

		$this->add_actions();

	}

	public function add_actions() {
		// shortcodes
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) ); // lost password
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) ); // reset password
		add_shortcode( 'custom-password-change-form', array( $this, 'render_password_change_form' ) ); // reset password
		add_shortcode( 'custom-account-settings-form', array( $this, 'render_account_settings_form' ) ); // account settings

		// actions
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) ); // lost password
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) ); // lost password
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) ); // reset password
		add_action( 'init', array( $this, 'do_password_change' ) ); // logged in user change password
		add_action( 'init', array( $this, 'do_account_settings' ) ); // logged in user account settings

		// filters
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 ); // lost password
		add_filter( 'retrieve_password_title', array( $this, 'replace_retrieve_password_title' ), 10, 4 ); // lost password email subject
		add_filter( 'send_email_change_email', array( $this, 'send_email_change_email' ), 10, 3 ); // send email when user changes email
		add_filter( 'send_password_change_email', array( $this, 'send_password_change_email' ), 10, 3 ); // send email when user changes password

		// whether to send email to admins when user changes password
		// we can't use a filter for this, but maybe later we could use an option
		if ( ! function_exists( 'wp_password_change_notification' ) ) {
			function wp_password_change_notification() {}
		}
	}

	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// check if the form data is stored in a transient
		$key       = isset( $_REQUEST['form_key'] ) ? esc_attr( $_REQUEST['form_key'] ) : '';
		$form_data = array();
		if ( '' !== $key ) {
			$form_data = get_transient( 'uam_reset_' . $key );
		}
		$attributes['form_data'] = $form_data;

		// Retrieve possible errors from request parameters
		$attributes['errors'] = array();
		if ( isset( $_REQUEST['errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['errors'] );

			foreach ( $error_codes as $error_code ) {
				$attributes['errors'][] = user_account_management()->get_error_message( $error_code, $form_data );
			}
		}

		// form action for submission
		$attributes['action'] = apply_filters( $this->option_prefix . 'lost_password_form_action', wp_lostpassword_url() );
		// example to change the form action
		/*
		add_filter( 'user_account_management_lost_password_form_action', 'lost_password_form_action', 10, 1 );
		function lost_password_form_action( $lost_password_form_action ) {
			return $lost_password_form_action;
		}
		*/

		// translators: instructions on top of the form. so far no placeholders are necessary
		$attributes['instructions'] = '<p class="a-form-instructions">' . esc_html__( 'Enter your email address and we\'ll send you a link you can use to pick a new password.' ) . '</p>';
		$attributes['instructions'] = apply_filters( $this->option_prefix . 'lost_password_form_instructions', $attributes['instructions'] );
		// example to change the lost password form instructions
		/*
		add_filter( 'user_account_management_lost_password_form_instructions', 'lost_password_form_instructions', 10, 1 );
		function lost_password_form_instructions( $lost_password_form_instructions ) {
			return $lost_password_form_instructions;
		}
		*/

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		} else {
			return user_account_management()->get_template_html( 'password-lost-form', 'front-end', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = rawurldecode( $_REQUEST['login'] );
				$attributes['key']   = rawurldecode( $_REQUEST['key'] );

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['errors'] ) ) {
					$error_codes = explode( ',', $_REQUEST['errors'] );

					foreach ( $error_codes as $code ) {
						$errors[] = user_account_management()->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return user_account_management()->get_template_html( 'password-reset-form', 'front-end', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'user-account-management' );
			}
		}
	}

	/**
	 * A shortcode for rendering the form used to change a logged in user's password.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_change_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/

		$attributes['current_url'] = user_account_management()->get_current_url();
		$attributes['redirect']    = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			$message = sprintf(
				'<p class="a-form-instructions">You are not signed in. You can <a href="%1$s">%2$s</a> if you do not have it.</p>',
				wp_lostpassword_url(),
				esc_html__( 'reset your password', 'user-account-management' )
			);
			return $message;
		} else {

			$can_access = $this->user_data->check_user_permissions();
			if ( false === $can_access ) {
				return __( 'You do not have permission to access this page.', 'user-account-management' );
			}

			//$attributes['login'] = rawurldecode( $_REQUEST['login'] );

			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $code ) {
					$errors[] = user_account_management()->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;
			return user_account_management()->get_template_html( 'password-change-form', 'front-end', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to change a logged in user's basic account info.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_account_settings_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		$can_access = $this->user_data->check_user_permissions();
		if ( false === $can_access ) {
			return __( 'You do not have permission to access this page.', 'user-account-management' );
		}
		if ( '' !== $this->user_data->user_id ) {
			$user_id = $this->user_data->user_id;
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/
		// we should use it for this page as well, unless and until it becomes insufficient

		$attributes['current_url'] = user_account_management()->get_current_url();
		$attributes['redirect']    = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			return __( 'You are not signed in.', 'user-account-management' );
		} else {
			// check if the form data is stored in a transient
			$key       = isset( $_REQUEST['form_key'] ) ? esc_attr( $_REQUEST['form_key'] ) : '';
			$form_data = array();
			if ( '' !== $key ) {
				$form_data = get_transient( 'uam_acct_settings_' . $key );
			}
			$attributes['form_data'] = $form_data;
			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $code ) {
					$errors[] = user_account_management()->get_error_message( $code, $form_data );
				}
			}
			$attributes['errors'] = $errors;
			if ( isset( $user_id ) && '' !== $user_id ) {
				$attributes['user'] = get_userdata( $user_id );
			} else {
				$attributes['user'] = wp_get_current_user();
			}
			$attributes['user_meta'] = get_user_meta( $attributes['user']->ID );
			if ( isset( $attributes['user_meta']['_country'][0] ) && 'United States' === $attributes['user_meta']['_country'][0] ) {
				$attributes['user_meta']['_country'][0] = 'United States of America'; // fix legacy naming
			}

			$attributes['include_city_state'] = get_option( $this->option_prefix . 'include_city_state', false );
			$attributes['hidden_city_state']  = get_option( $this->option_prefix . 'hidden_city_state', false );
			$include_countries                = get_option( $this->option_prefix . 'include_countries', false );
			if ( true === filter_var( $include_countries, FILTER_VALIDATE_BOOLEAN ) ) {
				$attributes['countries'] = user_account_management()->get_countries();
			}

			return user_account_management()->get_template_html( 'account-settings-form', 'front-end', $attributes );
		}
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				user_account_management()->login->redirect_logged_in_user();
				exit;
			}
			wp_redirect( site_url( 'user/password-lost' ) );
			exit;
		}
	}

	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = site_url( 'user/password-lost' );
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				$redirect_url = site_url( 'user/login' );
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
			}
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Changes a logged in user's password
	 */
	public function do_password_change() {
		if ( isset( $_POST[ $this->option_prefix . 'action' ] ) && 'reset-password' === $_POST[ $this->option_prefix . 'action' ] ) {
			$user_id = $this->user_data->user_id;
			if ( 0 === $user_id ) {
				return;
			}

			$redirect_url = $_POST[ $this->option_prefix . 'redirect' ];

			if ( wp_verify_nonce( $_POST[ $this->option_prefix . 'password_nonce' ], 'uam-password-nonce' ) ) {
				if ( '' === $_POST['new_password'] ) {
					$redirect_url = add_query_arg( 'errors', 'new_password_empty', $redirect_url );
				} else {
					$user_data = array(
						'ID'        => $user_id,
						'user_pass' => $_POST['new_password'],
					);
					wp_update_user( $user_data );
					$redirect_url = add_query_arg( 'password-reset', 'true', $redirect_url );
				}

				if ( isset( $redirect_url ) ) {
					$redirect_url = wp_validate_redirect( $redirect_url, $redirect_url );
				}
				if ( ! empty( $redirect_url ) ) {
					wp_redirect( $redirect_url );
					exit;
				}
			}
		}
	}

	/**
	 * Updates a logged in user's account settings
	 */
	public function do_account_settings() {

		if ( isset( $_POST[ $this->option_prefix . 'action' ] ) && 'account-settings-update' === $_POST[ $this->option_prefix . 'action' ] ) {
			$user_id = $this->user_data->user_id;
			if ( 0 === $user_id ) {
				return;
			}

			$redirect_url = sanitize_text_field( $_POST[ $this->option_prefix . 'redirect' ] );

			if ( wp_verify_nonce( sanitize_text_field( $_POST[ $this->option_prefix . 'account_settings_nonce' ] ), 'uam-account-settings-nonce' ) ) {
				if ( empty( $_POST ) ) {
					$redirect_url = add_query_arg( 'errors', 'account_settings_empty', $redirect_url );
				} else {
					$existing_user_data = get_userdata( $user_id );
					$new_user_data      = $this->user_data->setup_user_data( $_POST, $existing_user_data );
					$result             = $this->user_data->register_or_update_user( $new_user_data, 'update' );
				}

				if ( isset( $_POST['rest'] ) && 'true' === sanitize_text_field( $_POST['rest'] ) ) {
					return;
				}

				if ( is_wp_error( $result ) ) {
					// Parse errors into a string and append as parameter to redirect
					$errors       = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'errors', $errors, $redirect_url );
				} else {
					$redirect_url = add_query_arg( 'account-settings-update', 'true', $redirect_url );
				}

				if ( isset( $redirect_url ) ) {
					$redirect_url = wp_validate_redirect( $redirect_url, $redirect_url );
					if ( get_current_user_id() !== $this->user_data->user_id ) {
						$redirect_url = add_query_arg( 'user_id', $this->user_data->user_id, $redirect_url );
					}
				}
				if ( ! empty( $redirect_url ) ) {
					wp_redirect( $redirect_url );
					exit;
				}
			}
		}
	}

	/**
	 * Redirects to the custom password reset page, or the login page
	 * if there are errors.
	 */
	public function redirect_to_custom_password_reset() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo
			$user = check_password_reset_key( rawurldecode( $_REQUEST['key'] ), rawurldecode( $_REQUEST['login'] ) );

			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( site_url( 'user/login?login=expiredkey' ) );
				} else {
					wp_redirect( site_url( 'user/login?login=invalidkey' ) );
				}
				exit;
			}

			$redirect_url = site_url( 'user/password-reset' );
			$redirect_url = add_query_arg( 'login', rawurlencode( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', rawurlencode( $_REQUEST['key'] ), $redirect_url );

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$rp_key   = rawurldecode( $_REQUEST['rp_key'] );
			$rp_login = rawurldecode( $_REQUEST['rp_login'] );

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( site_url( '/user/login?login=expiredkey' ) );
				} else {
					wp_redirect( site_url( '/user/login?login=invalidkey' ) );
				}
				exit;
			}

			if ( isset( $_POST['new_password'] ) ) {

				if ( empty( $_POST['new_password'] ) ) {
					// Password is empty
					$redirect_url = site_url( '/user/password-reset' );

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'errors', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['new_password'] );

				// user has a new password; log them in now
				$user_data = array(
					'user_login'    => $rp_login,
					'user_password' => $_POST['new_password'],
					'remember'      => false,
				);

				$result  = wp_signon( $user_data, is_ssl() );
				$user_id = $result->ID;
				wp_set_current_user( $user_id, $user_data['user_login'] );
				wp_set_auth_cookie( $user_id, true, is_ssl() );

				if ( ! is_wp_error( $result ) ) {
					do_action( 'wp_login', $user_data['user_login'], $result );
					global $current_user;
					$current_user = $result;
					$default_url  = get_option( $this->option_prefix . 'default_login_redirect', '' );
					if ( '' === $default_url ) {
						$default_url = site_url( '/user/' );
					}
					wp_safe_redirect( $default_url );
					exit();
				}
			} else {
				echo 'Invalid request.';
			}

			exit;
		}
	}

	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail message to send.
	 */
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {

		$attributes['message']    = $message; // default mail message
		$attributes['key']        = $key; //activation key
		$attributes['user_login'] = $user_login; // user's email address
		$attributes['reset_url']  = site_url( 'wp-login.php?action=rp&amp;key=' . rawurlencode( $key ) . '&amp;login=' . rawurlencode( $user_login ), 'user-account-management' );
		$attributes['user_data']  = $user_data; // WP_User object

		// if you want to use html as the mime type, use the filter
		// we do not include this here because a theme template would be required anyway
		// add_filter( 'wp_mail_content_type', function() { return 'text/html'; })

		$msg = user_account_management()->get_template_html( 'retrieve-password-message', 'email', $attributes );
		return $msg;
	}

	/**
	 * Returns the email subject for the password reset mail.
	 * Called through the retrieve_password_title filter.
	 *
	 * @param string  $title    Default mail message.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail subject to use
	 */
	public function replace_retrieve_password_title( $title, $user_login, $user_data ) {
		$title = 'Reset your password on ' . get_bloginfo( 'name' );
		return $title;
	}

	/**
	 * Whether to send an email when users change their email address
	 *
	 * By default, we turn this off, but it can be enabled by other users.
	 *
	 * @param array  $send         Whether to send the email
	 * @param array  $user         Original user array
	 * @param array  $userdata     Updated user array
	 *
	 * @return array   The mail parameters
	 */
	public function send_email_change_email( $send, $user, $userdata ) {
		$send = apply_filters( $this->option_prefix . 'send_email_change_email', false, $user, $userdata );
		return $send;
	}

	/**
	 * Whether to send an email when users change their password
	 *
	 * By default, we turn this off, but it can be enabled by other users.
	 *
	 * @param array  $send         Whether to send the email
	 * @param array  $user         Original user array
	 * @param array  $userdata     Updated user array
	 *
	 * @return array   The mail parameters
	 */
	public function send_password_change_email( $send, $user, $userdata ) {
		$send = apply_filters( $this->option_prefix . 'send_password_change_email', false, $user, $userdata );
		return $send;
	}

}
