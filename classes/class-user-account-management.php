<?php
/**
 * The main plugin class
 *
 * @package User_Account_Management
 */

class User_Account_Management {

	/**
	 * The version number for this release of the plugin.
	 * This will later be used for upgrades and enqueuing files
	 *
	 * This should be set to the 'Plugin Version' value defined
	 * in the plugin header.
	 *
	 * @var string A PHP-standardized version number string
	 */
	public $version;

	/**
	 * Filesystem path to the main plugin file
	 * @var string
	 */
	public $file;

	/**
	 * Prefix for plugin options
	 * @var string
	 */
	public $option_prefix;

	/**
	 * Plugin slug
	 * @var string
	 */
	public $slug;

	/**
	* @var object
	* Load the Activate class
	*/
	public $activate;

	/**
	* @var object
	* Load the Transient class
	*/
	public $transient;

	/**
	* @var object
	* User data
	*/
	public $user_data;

	/**
	* @var object
	* Akismet
	*/
	public $akismet;

	/**
	* @var object
	* Login
	*/
	public $login;

	/**
	* @var object
	* Register
	*/
	public $register;

	/**
	* @var object
	* Account management
	*/
	public $account;

	/**
	* @var object
	* REST API
	*/
	public $rest;

	/**
	* @var object
	* Administrative interface
	*/
	public $admin;

	/**
	 * Class constructor
	 *
	 * @param string $version The current plugin version
	 * @param string $file The main plugin file
	 */
	public function __construct( $version, $file ) {

		$this->version       = $version;
		$this->file          = $file;
		$this->option_prefix = 'user_account_management_';
		$this->slug          = 'user-account-management';

		$cache = get_option( $this->option_prefix . 'cache_data', false );
		if ( true === filter_var( $cache, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->cache = true;
		} else {
			$this->cache = false;
		}
		$this->cache_expiration = (int) get_option( $this->option_prefix . 'cache_time', 2592000 );

	}

	public function init() {

		// Activate
		$this->activate = new User_Account_Management_Activate();

		// Transient
		$this->transient = new User_Account_Management_Transient( 'user_account_transients', $this->cache_expiration );

		// User data setup
		$this->user_data = new User_Account_Management_User_Data();

		// Akismet setup
		$this->akismet = new User_Account_Management_Akismet();

		// Login
		$this->login = new User_Account_Management_Login();

		// Register
		$this->register = new User_Account_Management_Register();

		// Account Management
		$this->account = new User_Account_Management_Account();

		// REST API features
		$this->rest = new User_Account_Management_Rest();

		// Admin features
		$this->admin = new User_Account_Management_Admin();

		$this->add_actions();

	}

	private function add_actions() {
		// handle redirects before rendering shortcodes
		add_action( 'wp', array( $this, 'user_status_check' ) );

		// query string params
		add_filter( 'query_vars', array( $this, 'user_query_vars' ), 10, 1 );

		// javascript/css
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_styles' ) );
	}

	/**
	 * Redirect users if necessary
	 *
	 * @return id  $user_id
	 */
	public function user_status_check() {
		$redirect = '';
		if ( '' !== $this->login->redirect_after_login_url ) {
			if ( is_user_logged_in() && is_page( 'user/login' ) ) {
				$redirect = wp_validate_redirect( $this->login->redirect_after_login_url, $redirect );
			}
		}
		if ( ! empty( $redirect ) ) {
			wp_redirect( $redirect );
			exit;
		}
	}

	public function user_query_vars( $query_vars ) {
		$query_vars[] = 'user_id';
		return $query_vars;
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
			$stylesheet_path = apply_filters( $this->option_prefix . 'front_end_stylesheet_path', plugins_url( 'assets/css/' . $this->slug . '.css' ) );
			// example to change the stylesheet path
			/*
			add_filter( 'user_account_management_front_end_stylesheet_path', 'front_end_stylesheet_path', 10, 1 );
			function front_end_stylesheet_path( $front_end_stylesheet_path ) {
				return $front_end_stylesheet_path;
			}
			*/

			if ( '' !== $stylesheet_path ) {
				wp_enqueue_style( $this->slug, plugins_url( 'assets/css/' . $this->slug . '.css', $this->file ), array(), filemtime( plugin_dir_path( $this->file ) . 'assets/css/' . $this->slug . '.css' ), 'all' );
			}
			wp_enqueue_script( 'password-strength-meter' );
			wp_enqueue_script( $this->slug, plugins_url( 'assets/js/' . $this->slug . '.min.js', $this->file ), array( 'jquery', 'password-strength-meter' ), filemtime( plugin_dir_path( $this->file ) . 'assets/js/' . $this->slug . '.js' ), true );
			// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
			wp_localize_script(
				$this->slug,
				$this->option_prefix . 'rest',
				array(
					'site_url'       => site_url( '/' ),
					'rest_namespace' => 'wp-json/' . $this->slug . '/v1',
					'nonce'          => wp_create_nonce( 'wp_rest' ),
				)
			);
		}
	}

	/**
	 * Get the current url, for cases we need to use it as a form submission destination
	 *
	 * @return string         The current url
	 */
	public function get_current_url() {
		if ( is_page() || is_single() ) {
			$current_url = wp_get_canonical_url();
		} else {
			global $wp;
			$current_url = home_url( add_query_arg( array(), $wp->request ) );
		}
		return $current_url;
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

		do_action( $this->option_prefix . 'before_' . $template_name );

		// allow users to put templates into their theme
		if ( file_exists( get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php' ) ) {
			$file = get_theme_file_path() . '/' . $this->slug . '-templates/' . $location . $template_name . '.php';
		} else {
			$file = plugin_dir_path( $this->file ) . 'templates/' . $location . $template_name . '.php';
		}

		require( $file );

		do_action( $this->option_prefix . 'after_' . $template_name );

		$html = ob_get_contents();

		ob_end_clean();

		return $html;
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
		$custom_message = apply_filters( $this->option_prefix . 'custom_error_message', '', $error_code, $data );
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
			case 'spam':
				return __( 'Our system flagged your account information as spam. You can try again.', 'user-account-management' );
			case 'expiredkey':
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

	/**
	 * Get the URL to the plugin admin menu
	 *
	 * @return string          The menu's URL
	 */
	public function get_menu_url() {
		$url = 'options-general.php?page=' . $this->slug;
		return admin_url( $url );
	}

	/**
	 * Get name/ISO2 code for all available countries. This gets used to render the <select> but here is only an array
	 *
	 *
	 * @return array               The countries
	 */
	public function get_countries() {

		$countries_url = 'https://restcountries.eu/rest/v2/all?fields=name;alpha2Code;';

		if ( true === $this->cache ) {
			// check the cache for country data
			$cached = $this->transient->get(
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
				$cached = $this->transient->set(
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
	 * Load up the localization file if we're using WordPress in a different language.
	 *
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'user-account-management', false, dirname( plugin_basename( $this->file ) ) . '/languages/' );
	}

}
