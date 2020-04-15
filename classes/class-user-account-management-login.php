<?php
/**
 * Class file for the User_Account_Management_Login class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * Logging in users
 */
class User_Account_Management_Login {

	public $option_prefix;
	public $version;
	public $slug;
	public $file;
	public $user_data;

	public $redirect_after_login_url;

	/**
	* Constructor which sets up login pages
	*/
	public function __construct() {

		$this->option_prefix            = user_account_management()->option_prefix;
		$this->version                  = user_account_management()->version;
		$this->slug                     = user_account_management()->slug;
		$this->transient                = user_account_management()->transient;
		$this->user_data                = user_account_management()->user_data;
		$this->cache                    = user_account_management()->cache;
		$this->redirect_after_login_url = $this->get_redirect_after_login_url();

		$this->add_actions();

	}

	/**
	 * Deal with the possible parameters for redirecting users after they log in
	 *
	 * @return string
	 */
	public function get_redirect_after_login_url() {
		$redirect_url = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirect_url = $_REQUEST['redirect_to'];
		} elseif ( isset( $_REQUEST['destination'] ) ) {
			$redirect_url = $_REQUEST['destination'];
		}
		return $redirect_url;
	}

	public function add_actions() {

		// shortcodes
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) ); // login

		// actions
		add_action( 'wp_login', array( $this, 'after_successful_login' ), 10, 2 ); // what to do when a user logs in
		if ( ! is_admin() ) {
			add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) ); // login
			add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) ); // logout
		}

		// filters
		add_filter( 'auth_cookie_expiration', array( $this, 'login_expiration' ) );
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 ); // login
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 ); // login
		add_filter( 'sanitize_user', array( $this->user_data, 'allow_email_as_username' ), 10, 3 ); // used for register and login
		add_filter( 'pre_user_display_name', array( $this->user_data, 'set_default_display_name' ) ); // used for register and login
	}

	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_login_form( $attributes, $content = null ) {

		$attributes = shortcode_atts(
			array(
				'source'           => '',
				'action'           => '',
				'redirect'         => '',
				'redirect_here'    => false,
				'instructions'     => '',
				'instructions_tag' => '',
			),
			$attributes,
			'login_form'
		);

		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		if ( '' === $attributes['source'] ) {
			$attributes['source'] = isset( $_GET['source'] ) ? esc_attr( $_GET['source'] ) : '';
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( '' !== $this->redirect_after_login_url ) {
			$attributes['redirect'] = wp_validate_redirect( $this->redirect_after_login_url, $attributes['redirect'] );
		} else {
			if ( true === filter_var( $attributes['redirect_here'], FILTER_VALIDATE_BOOLEAN ) ) {
				$attributes['redirect'] = wp_validate_redirect( user_account_management()->get_current_url() );
			}
		}

		// Check if the user just requested a new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];

		// check if the form data is stored in a transient
		$key       = isset( $_REQUEST['form_key'] ) ? esc_attr( $_REQUEST['form_key'] ) : '';
		$form_data = array();
		if ( '' !== $key ) {
			$form_data = get_transient( 'uam_login_' . $key );
		}
		$attributes['form_data'] = $form_data;

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );
			foreach ( $error_codes as $code ) {
				$errors[] = user_account_management()->get_error_message( $code, $form_data );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && 'true' === $_REQUEST['logged_out'];

		// allow a custom message to be put into the styled message div
		$attributes['message_info'] = apply_filters( $this->option_prefix . 'login_form_message_info', '', $attributes['source'] );
		// example to change the login form message info
		/*
		add_filter( 'user_account_management_login_form_message_info', 'login_form_message_info', 10, 2 );
		function login_form_message_info( $login_form_message_info, $source = '' ) {
			return $login_form_message_info;
		}
		*/

		// form action for submission
		if ( '' === $attributes['action'] ) {
			$attributes['action'] = wp_login_url();
		}
		$attributes['action'] = apply_filters( $this->option_prefix . 'login_form_action', $attributes['action'] );
		// example to change the form action
		/*
		add_filter( 'user_account_management_login_form_action', 'login_form_action', 10, 1 );
		function login_form_action( $login_form_action ) {
			return $login_form_action;
		}
		*/

		$registration_url = wp_registration_url();
		if ( '' !== $attributes['redirect'] ) {
			$registration_url = wp_registration_url() . '?redirect_to=' . $attributes['redirect'];
		}

		// translators: instructions on top of the form. placeholders are 1) registration link; 2) registration link text
		if ( '' === $attributes['instructions'] ) {
			$attributes['instructions'] = sprintf(
				'<p class="a-form-instructions">' . esc_html__( 'No account yet?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>.</p>',
				$registration_url,
				esc_html__( 'Register now', 'user-account-management' )
			);
		} else {
			if ( '' !== $attributes['instructions_tag'] ) {
				$tag = $attributes['instructions_tag'];
			} else {
				$tag = 'p';
			}
			$attributes['instructions'] = '<' . $tag . ' class="a-form-instructions">' . $attributes['instructions'] . '</' . $tag . '>';
		}

		$attributes['instructions'] = apply_filters( $this->option_prefix . 'login_form_instructions', $attributes['instructions'], $attributes['source'] );
		// example to change the login form instructions
		/*
		add_filter( 'user_account_management_login_form_instructions', 'login_form_instructions', 10, 2 );
		function login_form_instructions( $login_form_instructions, $source = '' ) {
			return $login_form_instructions;
		}
		*/

		// translators: password help at bottom of the form. placeholders are 1) reset password link; 2) reset password link text
		$attributes['password_help'] = sprintf(
			'<p class="a-form-instructions a-form-caption"><small><a href="%1$s">%2$s</a></small></p>',
			wp_lostpassword_url(),
			esc_html__( 'Need help or forgot your password?', 'user-account-management' )
		);
		$attributes['password_help'] = apply_filters( $this->option_prefix . 'login_form_password_help', $attributes['password_help'] );
		// example to change the password help
		/*
		add_filter( 'user_account_management_login_form_password_help', 'login_form_password_help', 10, 1 );
		function login_form_password_help( $login_form_password_help ) {
			return $login_form_password_help;
		}
		*/

		// if the user is already signed in, this lets us not leave them stranded
		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		}

		// Render the login form using an external template
		return user_account_management()->get_template_html( 'login-form', 'front-end', $attributes );
	}

	/**
	 * Notably, make sure the auth cookie is set when a user logs in
	 *
	 * @param  string  $login   The user's username
	 * @param  object  $user    The logged in user object
	 *
	 */
	public function after_successful_login( $login, $user = '' ) {
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}
		/**
		 * Log in a user by setting authentication cookies.
		 *
		 * @param  int   $user_id
		 * @param  bool  $remember
		 * @param  mixed $secure
		 *
		 */
		$remember = filter_var( get_option( $this->option_prefix . 'remember_user_login', false ), FILTER_VALIDATE_BOOLEAN );
		wp_set_auth_cookie( $user->ID, $remember, is_ssl() );
	}

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {
		$skip_login_redirect = apply_filters( $this->option_prefix . 'skip_login_redirect', false );
		if ( true === $skip_login_redirect ) {
			return;
		}

		if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			if ( '' !== $this->redirect_after_login_url ) {
				$redirect_to = $this->redirect_after_login_url;
			} else {
				$redirect_to = null;
			}
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user( $redirect_to );
				exit;
			}

			// The rest are redirected to the login page
			$login_url = site_url( 'user/login' );
			if ( ! empty( $redirect_to ) ) {
				$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Set the login expiration time for auth cookies
	 *
	 * @param  int    $expire_in
	 * @return int    $new_expire
	 */
	public function login_expiration( $expire_in ) {
		$new_expire = get_option( $this->option_prefix . 'auth_cookie_expiration', '' );
		if ( '' !== $new_expire ) {
			$expire_in = (int) $new_expire;
		}
		return $expire_in;
	}

	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
	 * @param string            $username   The user name used to log in.
	 * @param string            $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {

			if ( is_wp_error( $user ) ) {
				$key  = md5( microtime() . rand() );
				$data = array(
					'user_email' => $username,
				);
				set_transient( 'uam_login_' . $key, $data, 120 );
				$error_codes = join( ',', $user->get_error_codes() );
				$login_url   = site_url( 'user/login' );
				$login_url   = add_query_arg( 'login', $error_codes, $login_url );
				$login_url   = add_query_arg( 'form_key', $key, $login_url );
				wp_redirect( $login_url );
				exit;
			}
		}
		return $user;
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		$redirect_url = site_url( 'user/login/?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL
	 */
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		$redirect_url = site_url();

		if ( ! isset( $user->ID ) ) {
			return $redirect_url;
		}

		if ( ! empty( $user->roles[0] ) && in_array( $user->roles[0], array( 'administrator' ) ) ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( '' === $requested_redirect_to ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $requested_redirect_to;
			}
		} else {
			// Non-admin users go to their account page after login, unless another url is supplied
			if ( '' === $requested_redirect_to ) {
				$redirect_url = site_url( 'user' );
			} else {
				$redirect_url = $requested_redirect_to;
			}
		}

		return wp_validate_redirect( $redirect_url, site_url() );
	}

	/**
	 * Redirects the user to the correct page depending on whether they are an admin or not.
	 *
	 * @param string $redirect_to   An optional redirect_to URL for admin users
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( ! empty( $user->roles[0] ) && in_array( $user->roles[0], array( 'administrator' ) ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( site_url( 'user' ) );
			}
		}
	}

}
