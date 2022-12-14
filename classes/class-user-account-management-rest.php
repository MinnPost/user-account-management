<?php
/**
 * Class file for the User_Account_Management_Rest class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * REST API methods
 */
class User_Account_Management_Rest {

	public $option_prefix;
	public $version;
	public $slug;
	public $file;
	public $user_data;

	/**
	 * Constructor which sets up REST API endpoints
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

	/**
	 * API endpoints that can be called by other stuff.
	 */
	public function add_actions() {
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
	}

	/**
	 * Register API endpoints for dealing with user accounts
	 */
	public function register_api_endpoints() {
		register_rest_route(
			$this->slug . '/v1',
			'/check-zip',
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( $this, 'check_zip' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'zip_code' => array(
							'sanitize_callback' => 'esc_attr',
						),
						'country'  => array(
							'default'           => 'US',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/check-account-exists',
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( $this, 'check_account' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'email' => array(
							'sanitize_callback' => 'sanitize_email',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/check-account',
			array(
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( $this, 'check_account' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'email'      => array(
							'sanitize_callback' => 'sanitize_email',
						),
						'first_name' => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'last_name'  => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'city'       => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'state'      => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'country'    => array(
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/create-user',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'api_register_user' ),
					'permission_callback' => array( $this, 'can_process' ),
					'args'                => array(
						'email'    => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_email',
						),
						'password' => array(
							'required' => true,
						),
					),
				),
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/update-user',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'api_update_user' ),
					'args'                => array(
						'user_id'    => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'email'      => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_email',
						),
						'first_name' => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'last_name'  => array(
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
					'permission_callback' => function( $request ) {
						return $this->user_data->check_user_permissions( '', 'update' );
					},
				),
			)
		);
	}

	/**
	 * Process the REST API request to create a user
	 *
	 * @param WP_REST_Request $request the WP_REST_Request object.
	 * @return array $result
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

		$posted = array(
			'email'      => $email,
			'password'   => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'zip_code'   => $zip_code,
			'city'       => $city,
			'state'      => $state,
			'country'    => $country,
		);

		$result = array();

		// check for spam here.
		$spam = apply_filters( $this->option_prefix . 'check_spam', false, $posted );
		if ( ! get_option( 'users_can_register' ) ) {
			// Registration closed, display error.
			$result = array(
				'status' => 'closed',
				'reason' => 'registration not allowed',
				'errors' => array(),
			);
		} elseif ( true === $spam ) {
			// is spam.
			$result = array(
				'status' => 'spam',
				'reason' => 'submission flagged as spam',
				'errors' => array(),
			);
		} else {
			// create user.
			$user_data = $this->user_data->setup_user_data( $posted );
			$data      = $this->user_data->register_or_update_user( $user_data, 'register', array() );
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
		}
		return $result;
	}

	/**
	 * Process the REST API request to update a user
	 *
	 * @param WP_REST_Request $request the REST API request.
	 * @return $result
	 */
	public function api_update_user( WP_REST_Request $request ) {
		$user_id = $request->get_param( 'user_id' );
		$posted  = $request->get_params();

		$existing_user_data = get_userdata( $user_id );
		$new_user_data      = $this->user_data->setup_user_data( $posted, $existing_user_data );
		$data               = $this->user_data->register_or_update_user( $new_user_data, 'update' );

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
	 * API endpoint for checking zip/country for city/state
	 *
	 * @param WP_REST_Request $request    The REST request.
	 * @return array   The REST response
	 */
	public function check_zip( WP_REST_Request $request ) {
		$params    = $request->get_params();
		$zip_code  = $params['zip_code'];
		$country   = $params['country'];
		$citystate = $this->user_data->get_city_state( $zip_code, $country );
		return $citystate;
	}

	/**
	 * API endpoint for checking email address for a pre-existing account
	 *
	 * @param WP_REST_Request $request    The REST request.
	 * @return array   The REST response
	 */
	public function check_account( WP_REST_Request $request ) {
		$result     = array();
		$params     = $request->get_params();
		$email      = $request->get_param( 'email' );
		$password   = $request->get_param( 'password' );
		$first_name = $request->get_param( 'first_name' );
		$last_name  = $request->get_param( 'last_name' );
		$zip_code   = $request->get_param( 'zip_code' );
		$city       = $request->get_param( 'city' );
		$state      = $request->get_param( 'state' );
		$country    = $request->get_param( 'country' );

		$posted = array(
			'email'      => $email,
			'password'   => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'zip_code'   => $zip_code,
			'city'       => $city,
			'state'      => $state,
			'country'    => $country,
		);
		if ( username_exists( $email ) || email_exists( $email ) ) {
			$result = array(
				'status' => 'success',
				'reason' => 'user exists',
				'uid'    => email_exists( $email ),
			);
			return $result;
		}
		// check for spam here.
		$spam = apply_filters( $this->option_prefix . 'check_spam', false, $posted );
		// is spam.
		if ( true === $spam ) {
			$result = array(
				'status' => 'spam',
				'reason' => 'email flagged as spam',
				'errors' => array(),
			);
		}
		return $result;
	}

	/**
	 * Check to see if the user has permission to do this
	 *
	 * @param WP_REST_Request $request the request object sent to the API.
	 */
	public function can_process( WP_REST_Request $request ) {
		// unless we specify otherwise, the method should return true.
		$http_method = $request->get_method();
		return true;
	}
}
