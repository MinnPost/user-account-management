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

		$this->option_prefix = 'user_account_management_';
		$this->version = '0.0.1';
		$this->slug = 'user-account-management';

		// load admin
		$this->admin = $this->load_admin();

		$this->add_actions();

	}

	/**
	* load the admin stuff
	* creates admin menu to save the config options
	*
	* @throws \Exception
	*/
	private function load_admin() {
		require_once( plugin_dir_path( __FILE__ ) . 'classes/class-' . $this->slug . '-admin.php' );
		$admin = new User_Account_Management_Admin( $this->option_prefix, $this->version, $this->slug );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
		return $admin;
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
		// shortcodes for pages
		add_shortcode( 'custom-login-form', array( $this, 'render_login_form' ) ); // login
		add_shortcode( 'custom-register-form', array( $this, 'render_register_form' ) ); // register
		add_shortcode( 'custom-password-lost-form', array( $this, 'render_password_lost_form' ) ); // lost password
		add_shortcode( 'custom-password-reset-form', array( $this, 'render_password_reset_form' ) ); // reset password

		// actions
		add_action( 'wp_enqueue_scripts', array( $this, 'add_scripts_styles' ) ); // javascript/css
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) ); // login
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) ); // logout
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) ); // register
		add_action( 'login_form_register', array( $this, 'do_register_user' ) ); // register
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) ); // lost password
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) ); // lost password
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) ); // reset password
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) ); // reset password
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) ); // reset password

		// filters
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 ); // login
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 ); // login
		add_filter( 'sanitize_user', array( $this, 'allow_email_as_username' ), 10, 3 ); // register
		add_filter( 'pre_user_display_name', array( $this, 'set_default_display_name' ) ); // register
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 ); // lost password

		// api endpoints that can be called by other stuff
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

		$cache = get_option( $this->option_prefix . 'cache_data', false );
		if ( '1' === $cache ) {
			$this->cache = true;
		} else {
			$this->cache = false;
		}
		if ( true === $this->cache ) {
			$this->acct_transients = new User_Account_Management_Transient( 'user_account_transients' );
		}

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
			'password-lost' => array(
				'title' => __( 'Forgot Your Password?', 'user-account-management' ),
				'content' => '[custom-password-lost-form]',
				'parent' => 'user',
			),
			'password-reset' => array(
				'title' => __( 'Set a New Password', 'user-account-management' ),
				'content' => '[custom-password-reset-form]',
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

		// if the user is already signed in, this lets us not leave them stranded
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

		// Check if the user just requested a new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && 'confirm' === $_REQUEST['checkemail'];

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
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && 'true' === $_REQUEST['logged_out'];

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
		// example to change the login form instructions
		/*
		add_filter( 'user_account_management_login_form_instructions', 'login_form_instructions', 10, 1 );
		function login_form_instructions( $login_form_instructions ) {
			return $login_form_instructions;
		}
		*/

		// translators: password help at bottom of the form. placeholders are 1) reset password link; 2) reset password link text
		$attributes['password_help'] = sprintf( '<p class="a-form-instructions a-form-caption"><a href="%1$s">%2$s</a></p>',
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

		$attributes['include_city_state'] = get_option( $this->option_prefix . 'include_city_state', false );
		$attributes['hidden_city_state'] = get_option( $this->option_prefix . 'hidden_city_state', false );
		$include_countries = get_option( $this->option_prefix . 'include_countries', false );
		if ( '1' === $include_countries ) {
			$attributes['countries'] = $this->get_countries();
		}

		// translators: instructions on top of the form. placeholders are 1) login link, 2) login link text, 3) help link, 4) help link text
		$attributes['instructions'] = sprintf( '<p class="a-form-instructions">' . esc_html__( 'Already have an account?', 'user-account-management' ) . ' <a href="%1$s">%2$s</a>. ' . esc_html__( 'Do you need ', 'user-account-management' ) . '<a href="%3$s">%4$s</a>?</p>',
			wp_login_url(),
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

		// Retrieve possible errors from request parameters
		$attributes['errors'] = array();
		if ( isset( $_REQUEST['errors'] ) ) {
			$error_codes = explode( ',', $_REQUEST['errors'] );

			foreach ( $error_codes as $error_code ) {
				$attributes['errors'][] = $this->get_error_message( $error_code );
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

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'user-account-management' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = rawurldecode( $_REQUEST['login'] );
				$attributes['key'] = rawurldecode( $_REQUEST['key'] );

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );

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
	 * Add plugin JavaScript
	 *
	 */
	public function add_scripts_styles() {
		$user_page = get_page_by_path( 'user' );
		global $post;
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
			wp_enqueue_script( $this->slug, plugins_url( 'assets/js/' . $this->slug . '.min.js', __FILE__ ), array( 'jquery', 'password-strength-meter' ), $this->version, true );
			// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
			wp_localize_script(
				$this->slug,
				'user_account_management_rest',
				array(
					'site_url' => site_url( '/' ),
					'rest_namespace' => 'wp-json/' . $this->slug . '/v1',
				)
			);
		}
	}

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {
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
						$default_url = get_option( 'user_account_management_default_login_redirect', site_url( '/user/' ) );
						wp_safe_redirect( $default_url );
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
			$rp_key = rawurldecode( $_REQUEST['rp_key'] );
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
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['new_password'] );

				// user has a new password; log them in now
				$user_data = array(
					'user_login' => $rp_login,
					'user_password' => $_POST['new_password'],
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
			} else {
				echo 'Invalid request.';
			}

			exit;
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
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {
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
		$last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
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

		$attributes['message'] = $message; // default mail message
		$attributes['key'] = $key; //activation key
		$attributes['user_login'] = $user_login; // user's email address
		$attributes['reset_url'] = site_url( 'wp-login.php?action=rp&key=' . rawurlencode( $key ) . '&login=' . rawurlencode( $user_login ), 'user-account-management' );
		$attributes['user_data'] = $user_data; // WP_User object

		$msg = $this->get_template_html( 'retrieve-password-message', 'email', $attributes );
		return $msg;
	}

	/**
	 * Register API endpoints for dealing with user accounts
	 *
	 */
	public function register_api_endpoints() {
		register_rest_route( $this->slug . '/v1', '/check-zip', array(
			array(
				'methods'         => WP_REST_Server::READABLE,
				'callback'        => array( $this, 'check_zip' ),
				'args'            => array(
					'zip_code' => array(
						'sanitize_callback' => 'esc_attr',
					),
					'country' => array(
						'default' => 'US',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				//'permission_callback' => array( $this, 'permissions_check' ),
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
		$params = $request->get_params();
		$zip_code = $params['zip_code'];
		$country = $params['country'];
		$citystate = $this->get_city_state( $zip_code, $country );
		return $citystate;
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
			$request = wp_remote_get( $url );
			$body = wp_remote_retrieve_body( $request );
			$location = json_decode( $body, true );
			$city = $location['postalcodes'][0]['placeName'];
			$state = $location['postalcodes'][0]['adminName1'];
			$citystate = array(
				'city' => $city,
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

		// try to get the city/state from the zip code, if we can
		if ( '' !== $zip_code ) {
			if ( '' === $country ) {
				$country = 'US';
			}
			$citystate = $this->get_city_state( $zip_code, $country ); // this will return an empty value without the api key, this it will not set the below meta fields if that happens
			if ( '' !== $citystate['city'] ) {
				update_user_meta( $user_id, '_city', $citystate['city'] );
			}
			if ( '' !== $citystate['state'] ) {
				update_user_meta( $user_id, '_state', $citystate['state'] );
			}
		}

		return $user_id;
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
			$request = wp_remote_get( $countries_url );
			$body = wp_remote_retrieve_body( $request );
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
			case 'invalidcombo':
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
			case 'honeypot':
				return __( 'You filled out a form field that was created to stop spammers. Please try again.', 'user-account-management' );
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'user-account-management' );
			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'user-account-management' );
			default:
				break;
		}
		error_log( 'unknown error code is ' . $error_code );
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
	public function __construct( $name ) {
		$this->name = $name;
		$this->cache_expiration = 2592000; // cache it for a month
		$this->cache_prefix = esc_sql( 'acct_mgmt_' );
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

		$prefix = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;

		$keys = $this->all_keys();
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
		$prefix = $this->cache_prefix;
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
		$prefix = $this->cache_prefix;
		$cachekey = $prefix . $cachekey;
		return delete_transient( $cachekey );
	}

	/**
	 * Delete the entire cache for this plugin
	 *
	 * @return bool True if successful, false otherwise.
	 */
	public function flush() {
		$keys = $this->all_keys();
		$result = true;
		foreach ( $keys as $key ) {
			$result = delete_transient( $key );
		}
		$result = delete_transient( $this->name );
		return $result;
	}

}


register_activation_hook( __FILE__, array( 'User_Account_Management', 'plugin_activated' ) );

// Initialize the plugin
$user_account_management = new User_Account_Management();
