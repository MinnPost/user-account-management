<?php
/**
 * Plugin Name: User Account Management
 * Description: Replaces the WordPress user account management flow
 * Version: 0.0.8
 * Author: Jonathan Stegall / based on Jarkko Laine's work
 * License: GPL-2.0+
 * Text Domain: user-account-management
 */

class User_Account_Management {

	/**
	* @var string
	* Prefix for plugin options
	*/
	private $option_prefix;

	/**
	* @var string
	* Current version of the plugin
	*/
	private $version;

	/**
	* @var string
	* The plugin's slug so we can include it when necessary
	*/
	private $slug;

	/**
	* @var object
	*/
	private $activate;

	/**
	* @var object
	*/
	private $admin;

	/**
	 * @var object
	 * Static property to hold an instance of the class; this seems to make it reusable
	 *
	 */
	static $instance = null;

	/**
	* Load the static $instance property that holds the instance of the class.
	* This instance makes the class reusable by other plugins
	*
	* @return object
	*
	*/
	static public function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new User_Account_Management();
		}
		return self::$instance;
	}

	/**
	 * This is our constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->option_prefix = 'user_account_management_';
		$this->version       = '0.0.8';
		$this->slug          = 'user-account-management';

		$this->user_id = '';

		// things to do upon activate
		$this->activate = $this->activate( $this->option_prefix, $this->version, $this->slug );

		// load admin
		$this->admin = $this->load_admin();

		$this->redirect_after_login_url = $this->get_redirect_after_login_url();

		$this->add_actions();

	}

	/**
	 * What to do upon activation of the plugin
	 *
	 * @return object
	 *   Instance of User_Account_Management_Activate
	 */
	private function activate() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-' . $this->slug . '-activate.php' );
		$activate = new User_Account_Management_Activate( $this->option_prefix, $this->version, $this->slug );
		return $activate;
	}

	/**
	 * load the admin stuff
	 * creates admin menu to save the config options
	 *
	 * @return object
	 *   Instance of User_Account_Management_Admin
	 */
	private function load_admin() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-' . $this->slug . '-admin.php' );
		$admin = new User_Account_Management_Admin( $this->option_prefix, $this->version, $this->slug );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		return $admin;
	}

	/**
	 * Deal with the possible parameters for redirecting users after they log in
	 *
	 * @return string
	 */
	private function get_redirect_after_login_url() {
		$redirect_url = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$redirect_url = $_REQUEST['redirect_to'];
		} elseif ( isset( $_REQUEST['destination'] ) ) {
			$redirect_url = $_REQUEST['destination'];
		}
		return $redirect_url;
	}

	/**
	 * Display a Settings link on the main Plugins page
	 *
	 * @param array $links
	 * @param string $file
	 * @return array $links
	 * These are the links that go with this plugin's entry
	 */
	public function plugin_action_links( $links, $file ) {
		if ( plugin_basename( __FILE__ ) === $file ) {
			$settings = '<a href="' . get_admin_url() . 'options-general.php?page=' . $this->slug . '">' . __( 'Settings', 'user-account-management' ) . '</a>';
			array_unshift( $links, $settings );
		}
		return $links;
	}

	/**
	 * Add plugin shortcodes, actions, and filters
	 *
	 * @return void
	 */
	private function add_actions() {

		add_action( 'init', array( $this, 'set_user_id' ), 10 );

		// handle redirects before rendering shortcodes
		add_action( 'wp', array( $this, 'user_status_check' ) );

		// shortcodes for pages
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) ); // login
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) ); // register
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) ); // lost password
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) ); // reset password
		add_shortcode( 'custom-password-change-form', array( $this, 'render_password_change_form' ) ); // reset password
		add_shortcode( 'custom-account-settings-form', array( $this, 'render_account_settings_form' ) ); // account settings

		// actions
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_styles' ) ); // javascript/css
		add_action( 'wp_login', array( $this, 'after_successful_login' ), 10, 2 ); // what to do when a user logs in
		if ( ! is_admin() ) {
			add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) ); // login
			add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) ); // logout
			add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) ); // register
		}
		add_action( 'login_form_register', array( $this, 'do_register_user' ) ); // register
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) ); // lost password
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) ); // lost password
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) ); // reset password
		add_action( 'init', array( $this, 'do_password_change' ) ); // logged in user change password
		add_action( 'init', array( $this, 'do_account_settings' ) ); // logged in user account settings

		// filters
		add_filter( 'auth_cookie_expiration', array( $this, 'login_expiration' ) );
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 ); // login
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 ); // login
		add_filter( 'sanitize_user', array( $this, 'allow_email_as_username' ), 10, 3 ); // register
		add_filter( 'pre_user_display_name', array( $this, 'set_default_display_name' ) ); // register
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 ); // lost password
		add_filter( 'retrieve_password_title', array( $this, 'replace_retrieve_password_title' ), 10, 4 ); // lost password email subject
		add_filter( 'wp_new_user_notification_email', array( $this, 'replace_new_user_email' ), 10, 3 ); // email new users receive
		add_filter( 'wp_new_user_notification_email_admin', array( $this, 'replace_new_user_email_admin' ), 10, 3 ); // email admins receive when a user registers (this is disabled by default)
		add_filter( 'send_email_change_email', array( $this, 'send_email_change_email' ), 10, 3 ); // send email when user changes email
		add_filter( 'send_password_change_email', array( $this, 'send_password_change_email' ), 10, 3 ); // send email when user changes password
		add_filter( 'query_vars', array( $this, 'user_query_vars' ), 10, 1 ); // query string params

		// whether to send email to admins when user changes password
		// we can't use a filter for this, but maybe later we could use an option
		if ( ! function_exists( 'wp_password_change_notification' ) ) {
			function wp_password_change_notification() {}
		}

		// api endpoints that can be called by other stuff
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

		$cache = get_option( $this->option_prefix . 'cache_data', false );
		if ( true === filter_var( $cache, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->cache = true;
		} else {
			$this->cache = false;
		}
		if ( true === $this->cache ) {
			$cache_expiration      = (int) get_option( $this->option_prefix . 'cache_time', 2592000 );
			$this->acct_transients = new User_Account_Management_Transient( 'user_account_transients', $cache_expiration );
		}

	}

	/**
	 * Set the currently selected user ID, allowing for the URL query string to override the current logged in user for moderators and such
	 *
	 *
	 * @return id  $user_id
	 */
	public function set_user_id() {
		if ( isset( $_REQUEST['user_id'] ) ) {
			$this->user_id = esc_attr( $_REQUEST['user_id'] );
		} else {
			$this->user_id = get_current_user_id();
		}
		return $this->user_id;
	}

	/**
	 * Redirect users if necessary
	 *
	 * @return id  $user_id
	 */
	public function user_status_check() {
		$redirect = '';
		if ( '' !== $this->redirect_after_login_url ) {
			if ( is_user_logged_in() && is_page( 'user/login' ) ) {
				$redirect = wp_validate_redirect( $this->redirect_after_login_url, $redirect );
			}
		}
		if ( ! empty( $redirect ) ) {
			wp_redirect( $redirect );
			exit;
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
				$attributes['redirect'] = wp_validate_redirect( $this->get_current_url() );
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
				$errors[] = $this->get_error_message( $code, $form_data );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && 'true' === $_REQUEST['logged_out'];

		// allow a custom message to be put into the styled message div
		$attributes['message_info'] = apply_filters( 'user_account_management_login_form_message_info', '', $attributes['source'] );
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
		$attributes['action'] = apply_filters( 'user_account_management_login_form_action', $attributes['action'] );
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
			$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'No account yet?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>.</p>',
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

		$attributes['instructions'] = apply_filters( 'user_account_management_login_form_instructions', $attributes['instructions'], $attributes['source'] );
		// example to change the login form instructions
		/*
		add_filter( 'user_account_management_login_form_instructions', 'login_form_instructions', 10, 2 );
		function login_form_instructions( $login_form_instructions, $source = '' ) {
			return $login_form_instructions;
		}
		*/

		// translators: password help at bottom of the form. placeholders are 1) reset password link; 2) reset password link text
		$attributes['password_help'] = sprintf( '<p class="a-form-instructions a-form-caption"><small><a href="%1$s">%2$s</a></small></p>',
			wp_lostpassword_url(),
			esc_html__( 'Need help or forgot your password?', 'user-account-management' )
		);
		$attributes['password_help'] = apply_filters( 'user_account_management_login_form_password_help', $attributes['password_help'] );
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
		return $this->get_template_html( 'login-form', 'front-end', $attributes );
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
		if ( '' !== $this->redirect_after_login_url ) {
			$attributes['redirect'] = wp_validate_redirect( $this->redirect_after_login_url, $attributes['redirect'] );
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
				$attributes['errors'][] = $this->get_error_message( $error_code, $form_data );
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
			$attributes['countries'] = $this->get_countries();
		}

		$login_url = wp_login_url();
		if ( '' !== $attributes['redirect'] ) {
			$login_url = wp_login_url() . '?redirect_to=' . $attributes['redirect'];
		}

		// translators: instructions on top of the form. placeholders are 1) login link, 2) login link text, 3) help link, 4) help link text
		$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'Already have an account?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>. ' . esc_html__( 'Do you need ', 'user-account-management' ) . '<a href="%3$s">%4$s</a>?</p>',
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
			return $this->get_template_html( 'register-form', 'front-end', $attributes );
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
				$attributes['errors'][] = $this->get_error_message( $error_code, $form_data );
			}
		}

		// form action for submission
		$attributes['action'] = apply_filters( 'user_account_management_lost_password_form_action', wp_lostpassword_url() );
		// example to change the form action
		/*
		add_filter( 'user_account_management_lost_password_form_action', 'lost_password_form_action', 10, 1 );
		function lost_password_form_action( $lost_password_form_action ) {
			return $lost_password_form_action;
		}
		*/

		// translators: instructions on top of the form. so far no placeholders are necessary
		$attributes['instructions'] = '<p class="a-form-instructions">' . esc_html__( 'Enter your email address and we\'ll send you a link you can use to pick a new password.' ) . '</p>';
		$attributes['instructions'] = apply_filters( 'user_account_management_lost_password_form_instructions', $attributes['instructions'] );
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
			return $this->get_template_html( 'password-lost-form', 'front-end', $attributes );
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
						$errors[] = $this->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return $this->get_template_html( 'password-reset-form', 'front-end', $attributes );
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

		$attributes['current_url'] = $this->get_current_url();
		$attributes['redirect']    = $attributes['current_url'];

		if ( ! is_user_logged_in() ) {
			$message = sprintf( '<p class="a-form-instructions">You are not signed in. You can <a href="%1$s">%2$s</a> if you do not have it.</p>',
				wp_lostpassword_url(),
				esc_html__( 'reset your password', 'user-account-management' )
			);
			return $message;
		} else {

			$can_access = $this->check_user_permissions();
			if ( false === $can_access ) {
				return __( 'You do not have permission to access this page.', 'user-account-management' );
			}

			//$attributes['login'] = rawurldecode( $_REQUEST['login'] );

			// Error messages
			$errors = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $code ) {
					$errors[] = $this->get_error_message( $code );
				}
			}
			$attributes['errors'] = $errors;
			return $this->get_template_html( 'password-change-form', 'front-end', $attributes );
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

		$can_access = $this->check_user_permissions();
		if ( false === $can_access ) {
			return __( 'You do not have permission to access this page.', 'user-account-management' );
		}
		if ( '' !== $this->user_id ) {
			$user_id = $this->user_id;
		}

		// this functionality is mostly from https://pippinsplugins.com/change-password-form-short-code/
		// we should use it for this page as well, unless and until it becomes insufficient

		$attributes['current_url'] = $this->get_current_url();
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
					$errors[] = $this->get_error_message( $code, $form_data );
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
				$attributes['countries'] = $this->get_countries();
			}

			return $this->get_template_html( 'account-settings-form', 'front-end', $attributes );
		}
	}

	/**
	 * Add plugin JavaScript
	 *
	 */
	public function add_scripts_styles() {
		$user_page = get_page_by_path( 'user' );
		global $post;
		if ( ! is_object( $user_page ) || ! is_object( $post ) ) {
			return;
		}
		if ( is_page( $user_page->ID ) || $user_page->ID === $post->post_parent ) {

			// stylesheet path
			$stylesheet_path = apply_filters( 'user_account_management_front_end_stylesheet_path', plugins_url( 'assets/css/' . $this->slug . '.min.css' ) );
			// example to change the stylesheet path
			/*
			add_filter( 'user_account_management_front_end_stylesheet_path', 'front_end_stylesheet_path', 10, 1 );
			function front_end_stylesheet_path( $front_end_stylesheet_path ) {
				return $front_end_stylesheet_path;
			}
			*/

			if ( '' !== $stylesheet_path ) {
				wp_enqueue_style( $this->slug, plugins_url( 'assets/css/' . $this->slug . '.min.css', __FILE__ ), array(), $this->version, 'all' );
			}
			wp_enqueue_script( 'password-strength-meter' );
			wp_enqueue_script( $this->slug, plugins_url( 'assets/js/' . $this->slug . '.min.js', __FILE__ ), array( 'jquery', 'password-strength-meter' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/' . $this->slug . '.js' ), true );
			// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
			wp_localize_script(
				$this->slug,
				'user_account_management_rest',
				array(
					'site_url'       => site_url( '/' ),
					'rest_namespace' => 'wp-json/' . $this->slug . '/v1',
				)
			);
		}
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
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		$redirect_url = site_url( 'user/login/?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
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
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {

			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif ( isset( $_POST['rh_name'] ) && ! empty( $_POST['rh_name'] ) ) {
				$redirect_url = add_query_arg( 'register-errors', 'honeypot', $redirect_url );
			} else {
				$user_data = $this->setup_user_data( $_POST );
				$result    = $this->register_or_update_user( $user_data, 'register' );

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
					do_action( 'wp_login', $login_data['user_login'] );

					// user is successfully logged in
					if ( ! is_wp_error( $result ) ) {
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
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
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
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
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
		if ( isset( $_POST['user_account_management_action'] ) && 'reset-password' === $_POST['user_account_management_action'] ) {
			$user_id = $this->user_id;
			if ( 0 === $user_id ) {
				return;
			}

			$redirect_url = $_POST['user_account_management_redirect'];

			if ( wp_verify_nonce( $_POST['user_account_management_password_nonce'], 'uam-password-nonce' ) ) {
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
		if ( isset( $_POST['user_account_management_action'] ) && 'account-settings-update' === $_POST['user_account_management_action'] ) {
			$user_id = $this->user_id;
			if ( 0 === $user_id ) {
				return;
			}

			$redirect_url = $_POST['user_account_management_redirect'];

			if ( wp_verify_nonce( $_POST['user_account_management_account_settings_nonce'], 'uam-account-settings-nonce' ) ) {
				if ( empty( $_POST ) ) {
					$redirect_url = add_query_arg( 'errors', 'account_settings_empty', $redirect_url );
				} else {
					$existing_user_data = get_userdata( $user_id );
					$new_user_data      = $this->setup_user_data( $_POST, $existing_user_data );
					$result             = $this->register_or_update_user( $new_user_data, 'update' );
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
					if ( get_current_user_id() !== $this->user_id ) {
						$redirect_url = add_query_arg( 'user_id', $this->user_id, $redirect_url );
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
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
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
				do_action( 'wp_login', $user_data['user_login'] );

				if ( ! is_wp_error( $result ) ) {
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
	 * Allow users to use email addresses as username, preserving special characters
	 *
	 * @param string           $username           The username that will be stored
	 * @param string           $raw_username       The posted username value
	 * @param bool             $strict             Whether to be strict about special chars
	 *
	 * @return string $username
	 */
	public function allow_email_as_username( $username, $raw_username, $strict ) {
		// This avoids infinite looping! We only re-run if strict was set.
		if ( $strict && false !== is_email( $username ) ) {
			return sanitize_user( $raw_username, false );
		} else {
			return $username;
		}
	}

	/**
	 * New user registrations should have display_name set
	 * to 'firstname lastname'.
	 *
	 * @param string $name
	 *
	 * @return string name
	 *
	 */
	public function set_default_display_name( $name ) {
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		if ( '' !== $first_name && '' !== $last_name ) {
			$name = $first_name . ' ' . $last_name;
		}
		return $name;
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

		$msg = $this->get_template_html( 'retrieve-password-message', 'email', $attributes );
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
		$attributes = apply_filters( 'user_account_management_new_user_email_attributes', $attributes );

		// example to edit the new user email attributes
		/*
		add_filter( 'user_account_management_new_user_email_attributes', 'new_user_email_attributes', 10, 1 );
		function new_user_email_attributes( $new_user_email_attributes ) {
			return $new_user_email_attributes;
		}
		*/

		$attributes['message'] = $this->get_template_html( 'new-user-notification-email', 'email', $attributes );

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

		$admin_notification_allowed = apply_filters( 'user_account_management_allow_new_user_notification_admin', false );

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
		$attributes = apply_filters( 'user_account_management_new_user_email_admin_attributes', $attributes );

		// example to edit the new user email attributes
		/*
		add_filter( 'user_account_management_new_user_email_admin_attributes', 'new_user_email_admin_attributes', 10, 1 );
		function new_user_email_admin_attributes( $new_user_email_admin_attributes ) {
			return $new_user_email_admin_attributes;
		}
		*/

		$attributes['message'] = $this->get_template_html( 'new-user-notification-email-admin', 'email', $attributes );

		$wp_new_user_notification_email['to']      = $attributes['to'];
		$wp_new_user_notification_email['subject'] = $attributes['subject'];
		$wp_new_user_notification_email['message'] = $attributes['message'];
		$wp_new_user_notification_email['headers'] = $attributes['headers'];

		return $wp_new_user_notification_email;
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
		$send = apply_filters( 'user_account_management_send_email_change_email', false, $user, $userdata );
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
		$send = apply_filters( 'user_account_management_send_password_change_email', false, $user, $userdata );
		return $send;
	}

	public function user_query_vars( $query_vars ) {
		$query_vars[] = 'user_id';
		return $query_vars;
	}

	/**
	 * Register API endpoints for dealing with user accounts
	 *
	 */
	public function register_api_endpoints() {
		register_rest_route( $this->slug . '/v1', '/check-zip', array(
			array(
				'methods'  => array( WP_REST_Server::READABLE ),
				'callback' => array( $this, 'check_zip' ),
				'args'     => array(
					'zip_code' => array(
						'sanitize_callback' => 'esc_attr',
					),
					'country'  => array(
						'default'           => 'US',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				//'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );
		register_rest_route( $this->slug . '/v1', '/check-account-exists', array(
			array(
				'methods'  => array( WP_REST_Server::READABLE ),
				'callback' => array( $this, 'check_email' ),
				'args'     => array(
					'email' => array(
						'sanitize_callback' => 'sanitize_email',
					),
				),
				//'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );
		register_rest_route( $this->slug . '/v1', '/create-user', array(
			array(
				'methods'  => WP_REST_Server::CREATABLE,
				'callback' => array( $this, 'api_register_user' ),
				'args'     => array(
					'email'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					),
					'password' => array(
						'required' => true,
					),
				),
				//'permission_callback' => array( $this, 'permissions_check' ),
			),
		) );
		register_rest_route( $this->slug . '/v1', '/update-user', array(
			array(
				'methods'  => WP_REST_Server::EDITABLE,
				'callback' => array( $this, 'api_update_user' ),
				'args'     => array(
					'user_id'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_email',
					),
					'first_name'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'last_name'    => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'permission_callback' => function( $request ) {
					return $this->check_user_permissions( '', 'update' );
				},
			),
		) );
	}

	/**
	 * API endpoint for checking zip/country for city/state
	 *
	 * @param object  $request    The REST request
	 *
	 * @return array   The REST response
	 *
	 */
	public function check_zip( WP_REST_Request $request ) {
		$params    = $request->get_params();
		$zip_code  = $params['zip_code'];
		$country   = $params['country'];
		$citystate = $this->get_city_state( $zip_code, $country );
		return $citystate;
	}

	/**
	 * API endpoint for checking email address for a pre-existing account
	 *
	 * @param object  $request    The REST request
	 *
	 * @return array   The REST response
	 *
	 */
	public function check_email( WP_REST_Request $request ) {
		$params = $request->get_params();
		$email  = $params['email'];
		if ( username_exists( $email ) || email_exists( $email ) ) {
			$user = array(
				'status' => 'success',
				'reason' => 'user exists',
				'uid'    => email_exists( $email ),
			);
			return $user;
		}
		return false;
	}

	/**
	 * Set up user data to be created or updated
	 *
	 * @param object  $posted       The $_POST object
	 * @param array   $user_data    Any already existing data
	 *
	 * @return array   $user_data the data ready to be inserted or updated
	 *
	 */
	private function setup_user_data( $posted, $existing_user_data = array() ) {

		$user_data = array();
		if ( isset( $existing_user_data->ID ) ) {
			$user_data['ID'] = $existing_user_data->ID;
		}

		// email address is used as login in this plugin
		if ( isset( $posted['email'] ) ) {
			$user_data['user_email'] = $posted['email'];
			$user_data['user_login'] = $user_data['user_email'];
		}

		// password is only sent to this method on register
		if ( isset( $posted['password'] ) ) {
			$user_data['user_pass'] = $posted['password'];
		}

		// email and password are sanitized by WordPress already

		// first, last, zip, country should be optional since people might want to remove them
		if ( isset( $posted['first_name'] ) ) {
			$user_data['first_name'] = sanitize_text_field( $posted['first_name'] );
		} else {
			$user_data['first_name'] = '';
		}
		if ( isset( $posted['last_name'] ) ) {
			$user_data['last_name'] = sanitize_text_field( $posted['last_name'] );
		} else {
			$user_data['last_name'] = '';
		}

		// if there is an existing user:
		// add zip/country to the array if they are different than the previous version.
		// otherwise leave them empty; this will keep the update_user_meta method from running needlessly
		if ( isset( $user_data['ID'] ) ) {
			$existing_zip_code = get_user_meta( $user_data['ID'], '_zip_code', true );
			$existing_country  = get_user_meta( $user_data['ID'], '_country', true );
			$existing_city     = get_user_meta( $user_data['ID'], '_city', true );
			$existing_state    = get_user_meta( $user_data['ID'], '_state', true );
		}

		if ( isset( $posted['zip_code'] ) ) {
			$user_data['zip_code'] = esc_attr( $posted['zip_code'] );
			// if there is an existing zip code but it is unchanged, keep it empty
			if ( isset( $existing_zip_code ) && $existing_zip_code === $user_data['zip_code'] ) {
				$user_data['zip_code'] = '';
			}
		} else {
			$user_data['zip_code'] = '';
		}
		if ( isset( $posted['country'] ) && isset( $user_data['country'] ) && $user_data['country'] !== $posted['country'] ) {
			$user_data['country'] = sanitize_text_field( $posted['country'] );
			if ( isset( $existing_country ) && $existing_country === $user_data['country'] ) {
				$user_data['country'] = '';
			}
		} else {
			$user_data['country'] = '';
		}

		if ( isset( $posted['city'] ) ) {
			$user_data['city'] = sanitize_text_field( $posted['city'] );
			// if there is an existing city but it is unchanged, keep it empty
			if ( isset( $existing_city ) && $existing_city === $user_data['city'] ) {
				$user_data['city'] = '';
			}
		} else {
			$user_data['city'] = '';
		}

		if ( isset( $posted['state'] ) ) {
			$user_data['state'] = sanitize_text_field( $posted['state'] );
			// if there is an existing state but it is unchanged, keep it empty
			if ( isset( $existing_state ) && $existing_state === $user_data['state'] ) {
				$user_data['state'] = '';
			}
		} else {
			$user_data['state'] = '';
		}

		$user_data['user_nicename'] = strtolower( $user_data['first_name'] . '-' . $user_data['last_name'] );
		$user_data['display_name']  = $user_data['first_name'] . ' ' . $user_data['last_name'];
		$user_data['nickname']      = $user_data['first_name'];

		// add a filter to allow more $posted data to be added to $user_data
		// users should sanitize data that is being added in this way
		$user_data = apply_filters( 'user_account_management_add_to_user_data', $user_data, $posted, $existing_user_data );
		// example to add a field
		/*
		add_filter( 'user_account_management_add_to_user_data', 'add_to_user_data', 10, 3 );
		function add_to_user_data( $user_data, $posted, $existing_user_data ) {
			if ( isset( $posted['_newsletters'] ) ) {
				$user_data['_newsletters'] = sanitize_text_field( $posted['_newsletters'] );
			}
			return $user_data;
		}
		*/

		return $user_data;
	}

	/**
	 * Use the Geonames API to get the city/state from a postal code/country combination
	 *
	 * @param string  $zip_code    Zip/postal code
	 * @param string  $country     Country
	 * @param bool  $reset         Allows the cache to be skipped
	 *
	 * @return array               The city/state pair, as well as the status success/error
	 *
	 */
	private function get_city_state( $zip_code, $country, $reset = false ) {
		$citystate = '';

		// countries where the space breaks the api
		if ( 'GB' === $country ) {
			$zip_code = explode( ' ', $zip_code );
			$zip_code = $zip_code[0];
		}

		$geonames_api_username = get_option( $this->option_prefix . 'geonames_api_username', '' );
		if ( '' !== $geonames_api_username ) {
			$url = 'http://api.geonames.org/postalCodeLookupJSON?postalcode=' . urlencode( $zip_code ) . '&country=' . urlencode( $country ) . '&username=' . $geonames_api_username;
		} else {
			return $citystate;
		}

		$cached = $this->cache_get(
			array(
				'url' => $url,
			)
		);

		if ( isset( $cached ) && is_array( $cached ) && false === $reset ) {
			// load data from cache if it is available
			$citystate = $cached;
		} else {
			$request  = wp_remote_get( $url );
			$body     = wp_remote_retrieve_body( $request );
			$location = json_decode( $body, true );
			if ( ! is_array( $location['postalcodes'] ) ) {
				return $citystate;
			}
			$city      = $location['postalcodes'][0]['placeName'];
			$state     = $location['postalcodes'][0]['adminName1'];
			$citystate = array(
				'city'  => $city,
				'state' => $state,
			);

			if ( true === $this->cache ) {
				// cache the json response
				$cached = $this->cache_set(
					array(
						'url' => $url,
					),
					$citystate
				);
			}
		}

		if ( '' !== $citystate ) {
			$citystate['status'] = 'success';
		} else {
			$citystate['status'] = 'error';
		}

		return $citystate;

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

	/**
	* Process the REST API request to create a user
	*
	* @param $request
	*
	* @return $result
	*/
	public function api_register_user( WP_REST_Request $request ) {
		$email      = $request->get_param( 'email' );
		$password   = $request->get_param( 'password' );
		$first_name = $request->get_param( 'first_name' );
		$last_name  = $request->get_param( 'last_name' );
		$zip_code   = $request->get_param( 'zip_code' );
		$city       = $request->get_param( 'city' );
		$state      = $request->get_param( 'state' );
		$country    = $request->get_param( 'country' );

		$posted    = array(
			'email'      => $email,
			'password'   => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'zip_code'   => $zip_code,
			'city'       => $city,
			'state'      => $state,
			'country'    => $country,
		);
		$user_data = $this->setup_user_data( $posted );

		$data = $this->register_or_update_user( $user_data, 'register', array() );

		$result = array();
		if ( is_int( $data ) ) {
			$result = array(
				'status' => 'success',
				'reason' => 'new user',
				'uid'    => $data,
			);
		} else {
			$result = array(
				'status' => 'error',
				'reason' => 'user not created',
				'errors' => $data,
			);
		}
		return $result;
	}

	/**
	* Process the REST API request to update a user
	*
	* @param $request
	*
	* @return $result
	*/
	public function api_update_user( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		$posted  = $request->get_params();

		$existing_user_data = get_userdata( $user_id );
		$new_user_data      = $this->setup_user_data( $posted, $existing_user_data );
		$data               = $this->register_or_update_user( $new_user_data, 'update' );
		
		$result = array();
		if ( is_int( $data ) ) {
			$result = array(
				'status' => 'success',
				'reason' => 'updated user',
				'uid'    => $data,
			);
		} else {
			$result = array(
				'status' => 'error',
				'reason' => 'user ' . $user_id . ' not updated',
				'errors' => $data,
			);
		}

		return $result;
	}

	/**
	 * Validates and then completes the new user signup or existing user update process if all went well.
	 *
	 * @param array  $user_data            The prepared array of user data
	 * @param string $action               Register or update
	 * @param array  $existing_user_data   If it's an update, allow comparisons with existing user data
	 * @param bool   $return               If true, only return the user. Don't do any redirecting or echoing stuff.
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function register_or_update_user( $user_data, $action, $existing_user_data = array() ) {

		$country = $user_data['country'];

		$errors = new WP_Error();

		// do pre save action
		do_action( 'user_account_management_pre_user_data_save', $user_data, $existing_user_data );

		// add more data to $result in the event that the pre user data save needs to show something
		$result = apply_filters( 'user_account_management_pre_save_result', $user_data, $existing_user_data );
		/*
		add_filter( 'user_account_management_pre_save_result', 'pre_save_result', 10, 2 );
		function pre_save_errors( $user_data, $existing_user_data ) {
			$result = array( 'errors' => 'here is an error!' );
			return $result;
		}
		*/
		// if errors are needed, they should be returned as wp_error objects
		if ( is_wp_error( $result ) ) {
			// Parse errors into a string and append as parameter to redirect
			$errors = join( ',', $result->get_error_codes() );
			return $errors;
		}

		// add a filter to allow user data to be modified before it is saved
		$user_data = apply_filters( 'user_account_management_modify_user_data', $user_data, $existing_user_data );
		// example to remove a field from the user data that doesn't need to be saved
		/*
		add_filter( 'user_account_management_modify_user_data', 'modify_user_data', 10, 2 );
		function modify_user_data( $user_data, $existing_user_data ) {
			unset( $user_data['_newsletters'] );
		}
		*/

		if ( 'register' === $action ) {
			// Email address is used as both username and email. It is also the only
			// parameter we need to validate
			if ( ! is_email( $user_data['user_email'] ) ) {
				$errors->add( 'email', $this->get_error_message( 'email', $user_data ) );
				return $errors;
			}

			if ( username_exists( $user_data['user_email'] ) || email_exists( $user_data['user_email'] ) ) {
				$errors->add( 'email_exists', $this->get_error_message( 'email_exists', $user_data ) );
				return $errors;
			}
			$user_id = wp_insert_user( $user_data );
		} elseif ( 'update' === $action ) {
			$user_id = $user_data['ID'];
			$result  = wp_update_user( $user_data );
		}

		if ( '' !== $user_data['zip_code'] ) {
			update_user_meta( $user_id, '_zip_code', $user_data['zip_code'] );
		}

		if ( '' !== $user_data['country'] ) {
			update_user_meta( $user_id, '_country', $user_data['country'] );
		}

		// try to get the city/state from the zip code
		if ( '' !== $user_data['zip_code'] ) {
			if ( '' === $user_data['country'] ) {
				$user_data['country'] = 'US';
			}
			if ( '' === $user_data['city'] || '' === $user_data['state'] ) {
				$citystate = $this->get_city_state( $user_data['zip_code'], $user_data['country'] ); // this will return an empty value without the api key, this it will not set the below meta fields if that happens
				if ( '' !== $citystate['city'] ) {
					update_user_meta( $user_id, '_city', $citystate['city'] );
				}
				if ( '' !== $citystate['state'] ) {
					update_user_meta( $user_id, '_state', $citystate['state'] );
				}
			}
		}

		if ( '' !== $user_data['city'] ) {
			update_user_meta( $user_id, '_city', $user_data['city'] );
		}
		if ( '' !== $user_data['state'] ) {
			update_user_meta( $user_id, '_state', $user_data['state'] );
		}

		// do post save action
		do_action( 'user_account_management_post_user_data_save', $user_data, $existing_user_data );

		// add more data to $result in the event that the post user data save needs something
		$result = apply_filters( 'user_account_management_post_save_result', $result );
		/*
		add_filter( 'user_account_management_post_save_result', 'post_save_result', 10, 1 );
		function post_save_result( $result ) {
			return $result;
		}
		*/

		/**
		* We apparently don't automatically get access to core's user_register or profile_update actions, so we should call them here.
		*
		* @param int     $user_id       User ID.
		* @param WP_User $existing_user_data Object containing user's data prior to update.
		*/
		if ( 'update' === $action ) {
			/**
			 * Fires immediately after an existing user is updated.
			 *
			 * @since 2.0.0
			 *
			 * @param int     $user_id       User ID.
			 * @param WP_User $old_user_data Object containing user's data prior to update.
			 */
			do_action( 'profile_update', $user_id, $existing_user_data );
		} else {
			/**
			 * Fires immediately after a new user is registered.
			 *
			 * @since 1.5.0
			 *
			 * @param int $user_id User ID.
			 */
			do_action( 'user_register', $user_id );
		}

		if ( 'update' === $action ) {
			return $result;
		}

		// add a filter to skip the new user notification to the user
		// by default, this is allowed
		$user_notification_allowed = apply_filters( 'user_account_management_allow_new_user_notification', true );

		// example to skip the new user notification
		/*
		add_filter( 'user_account_management_allow_new_user_notification', 'skip_new_user_notification', 10, 1 );
		function skip_new_user_notification( $notification_allowed ) {
			$user_notification_allowed = false;
			return $user_notification_allowed;
		}
		*/

		// add a filter to skip the new user notification to the admin
		// by default, this is not allowed
		$admin_notification_allowed = apply_filters( 'user_account_management_allow_new_user_notification_admin', false );

		// example to skip the new user notification
		/*
		add_filter( 'user_account_management_allow_new_user_notification_admin', 'skip_new_user_notification', 10, 1 );
		function skip_new_user_notification( $notification_allowed ) {
			$user_notification_allowed = false;
			return $user_notification_allowed;
		}
		*/

		if ( true === $user_notification_allowed && true === $admin_notification_allowed ) {
			wp_new_user_notification( $user_id, null, 'both' );
		} elseif ( true === $user_notification_allowed ) {
			wp_new_user_notification( $user_id, null, 'user' );
		} elseif ( true === $admin_notification_allowed ) {
			wp_new_user_notification( $user_id, null, 'admin' );
		}

		return $user_id;
	}

	/**
	 * Get the current url, for cases we need to use it as a form submission destination
	 *
	 * @return string         The current url
	 */
	private function get_current_url() {
		if ( is_page() || is_single() ) {
			$current_url = wp_get_canonical_url();
		} else {
			global $wp;
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
		}
		return $current_url;
	}

	/**
	 * Check whether the current user is allowed to see the current screen
	 *
	 * @param int         The user id to check
	 *
	 * @return bool true or false
	 */
	public function check_user_permissions( $user_id = '', $method = 'create' ) {
		if ( '' === $user_id && '' !== $this->user_id ) {
			$user_id = $this->user_id;
		} elseif ( '' === $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( 'create' !== $method && 0 === $user_id ) {
			return false;
		}
		if ( get_current_user_id() === $user_id || current_user_can( 'edit_user', $user_id ) ) {
			return true;
		} else {
			return false;
		}
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
	public function get_template_html( $template_name, $location = '', $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		if ( '' !== $location ) {
			$location = $location . '/';
		}

		ob_start();

		do_action( 'user_account_management_before_' . $template_name );

		// allow users to put templates into their theme
		if ( file_exists( get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php' ) ) {
			$file = get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php';
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
	 * Get name/ISO2 code for all available countries. This gets used to render the <select> but here is only an array
	 *
	 *
	 * @return array               The countries
	 */
	private function get_countries() {

		$countries_url = 'https://restcountries.eu/rest/v2/all?fields=name;alpha2Code;';

		if ( true === $this->cache ) {
			// check the cache for country data
			$cached = $this->cache_get(
				array(
					'url' => $countries_url,
				)
			);
		}

		if ( isset( $cached ) && is_array( $cached ) ) {
			// load data from cache if it is available
			$countries = $cached;
		} else {
			// call the server to get the list
			$request   = wp_remote_get( $countries_url );
			$body      = wp_remote_retrieve_body( $request );
			$countries = json_decode( $body, true );

			if ( true === $this->cache ) {
				// cache the json response
				$cached = $this->cache_set(
					array(
						'url' => $countries_url,
					),
					$countries
				);
			}
		}
		return $countries;
	}

	/**
	 * Check to see if this API call exists in the cache
	 * if it does, return the transient for that key
	 *
	 * @param mixed $call The API call we'd like to make.
	 * @return $this->form_transients->get $cachekey
	 */
	private function cache_get( $call ) {
		$cachekey = md5( wp_json_encode( $call ) );
		return $this->acct_transients->get( $cachekey );
	}

	/**
	 * Create a cache entry for the current result, with the url and args as the key
	 *
	 * @param mixed $call The API query name.
	 * @return Bool whether or not the value was set
	 * @link https://wordpress.stackexchange.com/questions/174330/transient-storage-location-database-xcache-w3total-cache
	 */
	private function cache_set( $call, $data ) {
		$cachekey = md5( wp_json_encode( $call ) );
		return $this->acct_transients->set( $cachekey, $data );
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 * @param array $data           This should be user data, either provided by a form or a hook
	 *
	 * @return string               An error message.
	 */
	public function get_error_message( $error_code, $data = array() ) {
		$custom_message = apply_filters( 'user_account_management_custom_error_message', '', $error_code, $data );
		if ( '' !== $custom_message ) {
			return $custom_message;
		}
		// example to change the error message
		/*
		add_filter( 'user_account_management_custom_error_message', 'error_message', 10, 3 );
		function login_form_message_info( $message, $error_code, $data ) {
			$message = 'this is my error';
			return $message;
		}
		*/
		switch ( $error_code ) {
			case 'empty_username':
				return __( 'You did not enter an email address.', 'user-account-management' );
			case 'empty_password':
				return __( 'You did not enter a password.', 'user-account-management' );
			case 'invalid_username':
			case 'invalid_email':
			case 'invalidcombo':
				return __(
					"We couldn't find an account with that email address. Maybe you used a different one when signing up?",
					'user-account-management'
				);
			case 'incorrect_password':
				// translators: parameter is the wp_lostpassword_url() url
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
			case 'honeypot':
				return __( 'You filled out a form field that was created to stop spammers. Please try again.', 'user-account-management' );
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'user-account-management' );
			case 'password_reset_empty':
			case 'new_password_empty':
				return __( "Sorry, we don't accept empty passwords.", 'user-account-management' );
			default:
				break;
		}
		return __( 'An unknown error occurred. Please try again later.', 'user-account-management' );
	}

}

/**
 * Class to store all theme/plugin transients as an array in one WordPress transient
 **/
class User_Account_Management_Transient {

	protected $name;
	public $cache_expiration;

	/**
	 * Constructor which sets cache options and the name of the field that lists this plugin's cache keys.
	 *
	 * @param string $name The name of the field that lists all cache keys.
	 */
	public function __construct( $name, $cache_expiration = 2592000 ) {
		$this->name             = $name;
		$this->cache_expiration = $cache_expiration; // cache expiration in seconds
		$this->cache_prefix     = esc_sql( 'acct_mgmt_' );
	}

	/**
	 * Get the transient that lists all the other transients for this plugin.
	 *
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function all_keys() {
		return get_transient( $this->name );
	}

	/**
	 * Set individual transient, and add its key to the list of this plugin's transients.
	 *
	 * @param string $cachekey the key for this cache item
	 * @param mixed $value the value of the cache item
	 * @param int $cache_expiration. How long the plugin key cache, and this individual item cache, should last before expiring.
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function set( $cachekey, $value ) {

		$prefix   = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;

		$keys   = $this->all_keys();
		$keys[] = $cachekey;
		set_transient( $this->name, $keys, $this->cache_expiration );

		return set_transient( $cachekey, $value, $this->cache_expiration );
	}

	/**
	 * Get the individual cache value
	 *
	 * @param string $cachekey the key for this cache item
	 * @return mixed value of transient. False of empty, otherwise array.
	 */
	public function get( $cachekey ) {
		$prefix   = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;
		return get_transient( $cachekey );
	}

	/**
	 * Delete the individual cache value
	 *
	 * @param string $cachekey the key for this cache item
	 * @return bool True if successful, false otherwise.
	 */
	public function delete( $cachekey ) {
		$prefix   = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;
		return delete_transient( $cachekey );
	}

	/**
	 * Delete the entire cache for this plugin
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function flush() {
		$keys   = $this->all_keys();
		$result = true;
		foreach ( $keys as $key ) {
			$result = delete_transient( $key );
		}
		$result = delete_transient( $this->name );
		return $result;
	}

}

// Initialize the plugin
$user_account_management = User_Account_Management::get_instance();
