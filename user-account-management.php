<?php
/**
 * Plugin Name: User Account Management
 * Description: Replaces the WordPress user account management flow
 * Version: 0.0.1
 * Author: Jonathan Stegall / based on Jarkko Laine's work
 * License: GPL-2.0+
 * Text Domain: user-account-management
 */

class User_Account_Management {

	/**
	 * This is our constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->slug = 'user-account-management';

		$this->version = '0.0.1';
		$this->add_actions();

	}

	private function add_actions() {
		// shortcodes for pages
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) );
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) );

		// actions
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
		add_action( 'login_form_register', array( $this, 'do_register_user' ) );

		// filters
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_filter( 'sanitize_user', array( $this, 'allow_email_as_username' ), 10, 3 );
		add_filter( 'pre_user_display_name', array( $this, 'set_default_display_name' ) );
	}

	/**
	 * New user registrations should have display_name set
	 * to 'firstname lastname'.
	 *
	 * @param string $name
	 */
	public function set_default_display_name( $name ) {
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		if ( '' !== $first_name && '' !== $last_name ) {
			$name = $first_name . ' ' . $last_name;
		}
		return $name;
	}

	/**
	 * Plugin activation hook.
	 *
	 * Creates all WordPress pages needed by the plugin.
	 */
	public static function plugin_activated() {
		// Information needed for creating the plugin's pages
		$page_definitions = array(
			'user' => array(
				'title' => // translators: placeholder refers to site name
					sprintf( esc_html__( 'Your %1$s account', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[account-info]',
			),
			'login' => array(
				'title' => // translators: placeholder refers to site name
					sprintf( esc_html__( 'Log in to %1$s', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[custom-login-form]',
				'parent' => 'user',
			),
			'register' => array(
				'title' => // translators: placeholder refers to site name
					sprintf( esc_html__( 'Create your %1$s account', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[custom-register-form]',
				'parent' => 'user',
			),
		);

		foreach ( $page_definitions as $slug => $page ) {
			if ( ! isset( $page['parent'] ) ) {
				// Check that the page doesn't exist already
				$query = new WP_Query( 'pagename=' . $slug );
				if ( ! $query->have_posts() ) {
					// Add the page using the data from the array above
					wp_insert_post(
						array(
							'post_content'   => $page['content'],
							'post_name'      => $slug,
							'post_title'     => $page['title'],
							'post_status'    => 'publish',
							'post_type'      => 'page',
							'ping_status'    => 'closed',
							'comment_status' => 'closed',
						)
					);
				}
			}
		}

		foreach ( $page_definitions as $slug => $page ) {
			if ( isset( $page['parent'] ) ) {
				$parent_result = get_page_by_path( $page['parent'] );
				if ( null !== $parent_result ) {
					$parent = $parent_result->ID;
				} else {
					$parent = 0;
				}

				// Check that the page doesn't exist already
				$query = new WP_Query( 'pagename=' . $slug );
				if ( ! $query->have_posts() ) {
					// Add the page using the data from the array above
					wp_insert_post(
						array(
							'post_content'   => $page['content'],
							'post_name'      => $slug,
							'post_title'     => $page['title'],
							'post_status'    => 'publish',
							'post_type'      => 'page',
							'ping_status'    => 'closed',
							'comment_status' => 'closed',
							'post_parent'    => $parent,
						)
					);
				}
			}
		}

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
		// Parse shortcode attributes
		$default_attributes = array(
			'show_title' => false,
		);
		$attributes = shortcode_atts( $default_attributes, $attributes );
		$show_title = $attributes['show_title'];

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );
			foreach ( $error_codes as $code ) {
				$errors[] = $this->get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && true === $_REQUEST['logged_out'];

		// form action for submission
		$attributes['action'] = apply_filters( 'user_account_management_login_form_action', wp_login_url() );
		// example to change the form action
		/*
		add_filter( 'user_account_management_login_form_action', 'login_form_action', 10, 1 );
		function login_form_action( $login_form_action ) {
			return $login_form_action;
		}
		*/

		// translators: instructions on top of the form. placeholders are 1) registration link; 2) registration link text
		$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'No account yet?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>.</p>',
			wp_registration_url(),
			esc_html__( 'Register now', 'user-account-management' )
		);
		$attributes['instructions'] = apply_filters( 'user_account_management_login_form_instructions', $attributes['instructions'] );
		// example to change the form action
		/*
		add_filter( 'user_account_management_login_form_instructions', 'login_form_instructions', 10, 1 );
		function login_form_instructions( $login_form_instructions ) {
			return $login_form_instructions;
		}
		*/

		// translators: password help at bottom of the form. placeholders are 1) reset password link; 2) reset password link text
		$attributes['password_help'] = sprintf( '<p class="a-form-instructions a-form-caption"><a href="%1$s">%2$s</a>.</p>',
			wp_registration_url(),
			esc_html__( 'Need help or forgot your password?', 'user-account-management' )
		);
		$attributes['password_help'] = apply_filters( 'user_account_management_login_form_password_help', $attributes['password_help'] );
		// example to change the form action
		/*
		add_filter( 'user_account_management_login_form_password_help', 'login_form_password_help', 10, 1 );
		function login_form_password_help( $login_form_password_help ) {
			return $login_form_password_help;
		}
		*/

		// Render the login form using an external template
		return $this->get_template_html( 'login-form', 'front-end', $attributes );
	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param string $location      Folder location for the template (ie front-end or admin)
	 * @param array  $attributes    The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	private function get_template_html( $template_name, $location = '', $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		if ( '' !== $location ) {
			$location = $location . '/';
		}

		ob_start();

		do_action( 'user_account_management_before_' . $template_name );

		// allow users to put templates into their theme
		if ( file_exists( get_theme_file_path() . '/' . $this->slug . '-templates/' . $template_name . '.php' ) ) {
			$file = get_theme_file_path() . '/' . $this->slug . '-templates/' . $template_name . '.php';
		} else {
			$file = plugin_dir_path( __FILE__ ) . 'templates/' . $location . $template_name . '.php';
		}

		require( $file );

		do_action( 'user_account_management_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	function redirect_to_custom_login() {
		if ( 'GET' === $_SERVER['REQUEST_METHOD'] ) {
			$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
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
	 * Redirects the user to the correct page depending on whether he / she
	 * is an admin or not.
	 *
	 * @param string $redirect_to   An optional redirect_to URL for admin users
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( in_array( $user->roles[0], array( 'administrator' ) ) ) {
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

	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
	 * @param string            $username   The user name used to log in.
	 * @param string            $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			if ( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );
				$login_url = site_url( 'user/login' );
				$login_url = add_query_arg( 'login', $error_codes, $login_url );
				wp_redirect( $login_url );
				exit;
			}
		}
		return $user;
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	private function get_error_message( $error_code ) {
		switch ( $error_code ) {
			case 'empty_username':
				return __( 'You did not enter an email address.', 'user-account-management' );
			case 'empty_password':
				return __( 'You did not enter a password.', 'user-account-management' );
			case 'invalid_username':
			case 'invalid_email':
				return __(
					"We couldn't find an account with that email address. Maybe you used a different one when signing up?",
					'user-account-management'
				);
			case 'incorrect_password':
				$err = __(
					"The password you entered wasn't right. We can help you <a href='%s'>reset your password</a>.",
					'user-account-management'
				);
				return sprintf( $err, wp_lostpassword_url() );
			case 'email':
				return __( 'The email address you entered is not valid.', 'user-account-management' );
			case 'email_exists':
				return __( 'An account already exists with this email address. Is it yours?', 'user-account-management' );
			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'user-account-management' );
			default:
				break;
		}
		return __( 'An unknown error occurred. Please try again later.', 'user-account-management' );
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

		if ( in_array( $user->roles[0], array( 'administrator' ) ) ) {
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
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array(
			'show_title' => false,
		);
		$attributes = shortcode_atts( $default_attributes, $attributes );

		// Retrieve possible errors from request parameters
		$attributes['errors'] = array();
		if ( isset( $_REQUEST['register-errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['register-errors'] );

			foreach ( $error_codes as $error_code ) {
				$attributes['errors'][] = $this->get_error_message( $error_code );
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

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'user-account-management' );
		} else {
			return $this->get_template_html( 'register-form', 'front-end', $attributes );
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
	 * Validates and then completes the new user signup process if all went well.
	 *
	 * @param string $email         The new user's email address
	 * @param string $password      The new user's password
	 * @param string $first_name    The new user's first name
	 * @param string $last_name     The new user's last name
	 * @param string $zip_code      The new user's zip code
	 * @param string $country       The new user's country
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function register_user( $email, $password, $first_name, $last_name, $zip_code, $country = '' ) {
		$errors = new WP_Error();

		// Email address is used as both username and email. It is also the only
		// parameter we need to validate
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists' ) );
			return $errors;
		}

		// Generate the password so that the subscriber will have to check email...
		//$password = wp_generate_password( 12, false );

		$user_data = array(
			'user_login'    => $email,
			'user_email'    => $email,
			'user_pass'     => $password,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'display_name'  => $first_name . ' ' . $last_name,
			'nickname'      => $first_name,
		);

		$user_id = wp_insert_user( $user_data );

		if ( '' !== $zip_code ) {
			update_user_meta( $user_id, '_zip_code', $zip_code );
		}

		if ( '' !== $country ) {
			update_user_meta( $user_id, '_country', $country );
		}

		//wp_new_user_notification( $user_id, $password );

		return $user_id;
	}

	/**
	 * Handles the registration of a new user.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 */
	public function do_register_user() {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {

			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} else {
				$email = $_POST['email'];
				$password = $_POST['password'];
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );
				$zip_code = esc_attr( $_POST['zip_code'] );
				if ( isset( $_POST['country'] ) ) {
					$country = sanitize_text_field( $_POST['country'] );
				} else {
					$country = '';
				}

				$result = $this->register_user( $email, $password, $first_name, $last_name, $zip_code, $country );

				if ( is_wp_error( $result ) ) {
					// Parse errors into a string and append as parameter to redirect
					$errors = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
				} else {
					// user has been registered; log them in now
					$user_data = array(
						'user_login' => $email,
						'user_password' => $password,
						'remember' => false,
					);

					$result = wp_signon( $user_data );

					if ( ! is_wp_error( $result ) ) {
						global $current_user;
						$current_user = $result;
						$default_url = get_option( 'user_account_management_default_login_redirect', site_url( 'user' ) );
						wp_safe_redirect( $default_url );
						exit();
					}
				}
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	public function allow_email_as_username( $username, $raw_username, $strict ) {
		// This avoids infinite looping! We only re-run if strict was set.
		if ( $strict && false !== is_email( $username ) ) {
			return sanitize_user( $raw_username, false );
		} else {
			return $username;
		}
	}

}

register_activation_hook( __FILE__, array( 'User_Account_Management', 'plugin_activated' ) );

// Initialize the plugin
$user_account_management = new User_Account_Management();
