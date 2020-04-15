<?php
/**
 * Class file for the User_Account_Management_Register class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * Registering users
 */
class User_Account_Management_Register {

	public $option_prefix;
	public $version;
	public $slug;
	public $file;
	public $user_data;

	/**
	* Constructor which sets up registration pages
	*/
	public function __construct() {

		$this->option_prefix = user_account_management()->option_prefix;
		$this->version       = user_account_management()->version;
		$this->slug          = user_account_management()->slug;
		$this->transient     = user_account_management()->transient;
		$this->user_data     = user_account_management()->user_data;
		$this->login         = user_account_management()->login;
		$this->cache         = user_account_management()->cache;

		$this->add_actions();

	}

	public function add_actions() {
		// shortcodes
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) ); // register

		// actions
		if ( ! is_admin() ) {
			add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) ); // register
		}
		add_action( 'login_form_register', array( $this, 'do_register_user' ) ); // register

		// filters
		add_filter( 'sanitize_user', array( $this->user_data, 'allow_email_as_username' ), 10, 3 ); // used for register and login
		add_filter( 'pre_user_display_name', array( $this->user_data, 'set_default_display_name' ) ); // used for register and login
		add_filter( 'wp_new_user_notification_email', array( $this, 'replace_new_user_email' ), 10, 3 ); // email new users receive
		add_filter( 'wp_new_user_notification_email_admin', array( $this, 'replace_new_user_email_admin' ), 10, 3 ); // email admins receive when a user registers (this is disabled by default)
	}

	/**
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_register_form( $attributes, $content = null ) {

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( '' !== $this->login->redirect_after_login_url ) {
			$attributes['redirect'] = wp_validate_redirect( $this->login->redirect_after_login_url, $attributes['redirect'] );
		}

		// check if the form data is stored in a transient
		$key       = isset( $_REQUEST['form_key'] ) ? esc_attr( $_REQUEST['form_key'] ) : '';
		$form_data = array();
		if ( '' !== $key ) {
			$form_data = get_transient( 'uam_register_' . $key );
		}

		// allow $_GET data to override form data if it is present
		if ( isset( $_GET['user_email'] ) ) {
			$form_data['user_email'] = rawurldecode( $_GET['user_email'] );
		}

		$attributes['form_data'] = $form_data;

		// Retrieve possible errors from request parameters
		$attributes['errors'] = array();
		if ( isset( $_REQUEST['register-errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['register-errors'] );

			foreach ( $error_codes as $error_code ) {
				$attributes['errors'][] = user_account_management()->get_error_message( $error_code, $form_data );
			}
		}

		// form action for submission
		$attributes['action'] = apply_filters( 'user_account_management_register_form_action', wp_registration_url() );
		// example to change the form action
		/*
		add_filter( 'user_account_management_register_form_action', 'register_form_action', 10, 1 );
		function register_form_action( $register_form_action ) {
			return $register_form_action;
		}
		*/

		$attributes['include_city_state'] = get_option( $this->option_prefix . 'include_city_state', false );
		$attributes['hidden_city_state']  = get_option( $this->option_prefix . 'hidden_city_state', false );
		$include_countries                = get_option( $this->option_prefix . 'include_countries', false );
		if ( true === filter_var( $include_countries, FILTER_VALIDATE_BOOLEAN ) ) {
			$attributes['countries'] = user_account_management()->get_countries();
		}

		$login_url = wp_login_url();
		if ( '' !== $attributes['redirect'] ) {
			$login_url = wp_login_url() . '?redirect_to=' . $attributes['redirect'];
		}
		
		$attributes['instructions'] = sprintf(
			// translators: instructions on top of the form. placeholders are 1) login link, 2) login link text, 3) help link, 4) help link text
			'<p class="a-form-instructions">' . esc_html__( 'Already have an account?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>. ' . esc_html__( 'Do you need ', 'user-account-management' ) . '<a href="%3$s">%4$s</a>?</p>',
			$login_url,
			esc_html__( 'Log in now', 'user-account-management' ),
			wp_lostpassword_url(),
			esc_html__( 'account help', 'user-account-management' )
		);
		$attributes['instructions'] = apply_filters( 'user_account_management_login_form_instructions', $attributes['instructions'] );
		// example to change the register form instructions
		/*
		add_filter( 'user_account_management_register_form_instructions', 'register_form_instructions', 10, 1 );
		function register_form_instructions( $register_form_instructions ) {
			return $register_form_instructions;
		}
		*/

		// translators: terms & conditions at bottom of the form. placeholders are 1) name of site, 2) terms of use link, 3) terms link text, 4) privacy link, 5) privacy link text
		$attributes['privacy_terms'] = sprintf( '<p class="a-form-instructions"><small>' . esc_html__( 'By proceeding, you agree to ', 'user-account-management' ) . ' %1$s' . esc_html__( "'s", 'user-account-management' ) . ' <a href="%2$s">%3$s</a> ' . esc_html__( ' and ', 'user-account-management' ) . '<a href="%4$s">%5$s</a>.</small></p>',
			get_bloginfo( 'name' ),
			site_url( 'privacy' ),
			esc_html__( 'Privacy Policy', 'user-account-management' ),
			site_url( 'terms-of-use' ),
			esc_html__( 'Terms of Use', 'user-account-management' )
		);
		$attributes['privacy_terms'] = apply_filters( 'user_account_management_register_form_privacy_terms', $attributes['privacy_terms'] );
		// example to change the register form terms
		/*
		add_filter( 'user_account_management_register_form_privacy_terms', 'register_form_privacy_terms', 10, 1 );
		function register_form_privacy_terms( $register_form_privacy_terms ) {
			return $register_form_privacy_terms;
		}
		*/

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'user-account-management' );
		} else {
			return user_account_management()->get_template_html( 'register-form', 'front-end', $attributes );
		}
	}

	/**
	 * Redirects the user to the custom registration page instead
	 * of wp-login.php?action=register.
	 */
	public function redirect_to_custom_register() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
			} else {
				wp_redirect( site_url( 'user/register' ) );
			}
			exit;
		}
	}

		/**
	 * Handles the registration of a new user.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 *
	 * This method logs in the user if they are successfully registered, and then it redirects them.
	 *
	 */
	public function do_register_user() {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			// check for spam here
			$spam = apply_filters( $this->option_prefix . 'check_spam', false, $_POST );
			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = site_url( 'user/register' );
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif ( true === $spam ) {
				$redirect_url = site_url( 'user/register' );
				$redirect_url = add_query_arg( 'register-errors', 'spam', $redirect_url );
			} else {
				$user_data = $this->user_data->setup_user_data( $_POST );
				$result    = $this->user_data->register_or_update_user( $user_data, 'register' );

				if ( is_wp_error( $result ) ) {
					// Errors found
					$redirect_url = site_url( 'user/register' );
					// Parse errors into a string and append as parameter to redirect
					$errors       = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );

					$key  = md5( microtime() . rand() );
					$data = $user_data;
					unset( $data['user_pass'] );
					set_transient( 'uam_register_' . $key, $data, 120 );
					$redirect_url = add_query_arg( 'form_key', $key, $redirect_url );

				} else {
					// user has been registered; log them in now
					$login_data = array(
						'user_login'    => $user_data['user_email'],
						'user_password' => $user_data['user_pass'],
						'remember'      => false,
					);

					$result  = wp_signon( $login_data, is_ssl() );
					$user_id = $result->ID;
					wp_set_current_user( $user_id, $login_data['user_login'] );
					wp_set_auth_cookie( $user_id, true, is_ssl() );

					// user is successfully logged in
					if ( ! is_wp_error( $result ) ) {
						do_action( 'wp_login', $login_data['user_login'], $result );
						global $current_user;
						$current_user = $result;
						$redirect_url = get_option( $this->option_prefix . 'default_login_redirect', '' );
						if ( '' === $redirect_url ) {
							$redirect_url = site_url( '/user/' );
						}
						// check for hidden redirect field
						if ( isset( $_POST['redirect_to'] ) && ! empty( $_POST['redirect_to'] ) ) {
							$redirect_url = $_POST['redirect_to'];
						}
						wp_safe_redirect( $redirect_url );
						exit();
					}
				}
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Returns the message body for the new user notification email.
	 * Called through the wp_new_user_notification_email filter.
	 *
	 * @param array  $wp_new_user_notification_email         Used to build the wp_mail contents
	 * @param object  $user                                  The WP_User object for the new user
	 * @param string  $blogname                              The site title
	 *
	 * @return array   The mail parameters
	 */
	public function replace_new_user_email( $wp_new_user_notification_email, $user, $blogname ) {

		$attributes['to']          = $wp_new_user_notification_email['to']; // default recipient
		$attributes['subject']     = $wp_new_user_notification_email['subject']; // default subject
		$attributes['message']     = $wp_new_user_notification_email['message']; // default mail message
		$attributes['headers']     = $wp_new_user_notification_email['headers']; // default mail headers
		$attributes['blogname']    = $blogname; // site name
		$attributes['user_data']   = $user->data; // WP_User object
		$attributes['login_url']   = site_url( '/user/login' ); // login url
		$attributes['account_url'] = site_url( '/user/' ); // user account url

		// if you want to use html as the mime type, use the filter
		// we do not include this here because a theme template would be required anyway
		// add_filter( 'wp_mail_content_type', function() { return 'text/html'; })

		// add a filter to change all of the attributes
		$attributes = apply_filters( $this->option_prefix . 'new_user_email_attributes', $attributes );

		// example to edit the new user email attributes
		/*
		add_filter( 'user_account_management_new_user_email_attributes', 'new_user_email_attributes', 10, 1 );
		function new_user_email_attributes( $new_user_email_attributes ) {
			return $new_user_email_attributes;
		}
		*/

		$attributes['message'] = user_account_management()->get_template_html( 'new-user-notification-email', 'email', $attributes );

		$wp_new_user_notification_email['to']      = $attributes['to'];
		$wp_new_user_notification_email['subject'] = $attributes['subject'];
		$wp_new_user_notification_email['message'] = $attributes['message'];
		$wp_new_user_notification_email['headers'] = $attributes['headers'];

		return $wp_new_user_notification_email;
	}

	/**
	 * Returns the message body for the new user notification email that goes to admins.
	 * Called through the wp_new_user_notification_email_admin filter.
	 *
	 * By default, this email is not sent by this plugin, but can be enabled with the
	 * user_account_management_allow_new_user_notification_admin filter
	 *
	 * @param array  $wp_new_user_notification_email         Used to build the wp_mail contents
	 * @param object  $user                                  The WP_User object for the new user
	 * @param string  $blogname                              The site title
	 *
	 * @return array   The mail parameters
	 */
	public function replace_new_user_email_admin( $wp_new_user_notification_email, $user, $blogname ) {

		$admin_notification_allowed = apply_filters( $this->option_prefix . 'allow_new_user_notification_admin', false );

		// if this keeps going unnecessarily, it can cause ISSUES with other plugins
		if ( false === $admin_notification_allowed ) {
			return;
		}

		$attributes['to']        = $wp_new_user_notification_email['to']; // default recipient - the site admin
		$attributes['subject']   = $wp_new_user_notification_email['subject']; // default subject
		$attributes['message']   = $wp_new_user_notification_email['message']; // default mail message
		$attributes['headers']   = $wp_new_user_notification_email['headers']; // default mail headers
		$attributes['blogname']  = $blogname; // site name
		$attributes['user_data'] = $user->data; // WP_User object
		$attributes['login_url'] = site_url( '/user/login' ); // login url

		// if you want to use html as the mime type, use the filter
		// we do not include this here because a theme template would be required anyway
		// add_filter( 'wp_mail_content_type', function() { return 'text/html'; })

		// add a filter to change all of the attributes
		$attributes = apply_filters( $this->option_prefix . 'new_user_email_admin_attributes', $attributes );

		// example to edit the new user email attributes
		/*
		add_filter( 'user_account_management_new_user_email_admin_attributes', 'new_user_email_admin_attributes', 10, 1 );
		function new_user_email_admin_attributes( $new_user_email_admin_attributes ) {
			return $new_user_email_admin_attributes;
		}
		*/

		$attributes['message'] = user_account_management()->get_template_html( 'new-user-notification-email-admin', 'email', $attributes );

		$wp_new_user_notification_email['to']      = $attributes['to'];
		$wp_new_user_notification_email['subject'] = $attributes['subject'];
		$wp_new_user_notification_email['message'] = $attributes['message'];
		$wp_new_user_notification_email['headers'] = $attributes['headers'];

		return $wp_new_user_notification_email;
	}
}
