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
	public $login;
	public $register;
	public $account;

	/**
	* Constructor which sets up REST API endpoints
	*/
	public function __construct() {

		$this->option_prefix = user_account_management()->option_prefix;
		$this->version       = user_account_management()->version;
		$this->slug          = user_account_management()->slug;
		$this->transient     = user_account_management()->transient;
		$this->user_data     = user_account_management()->user_data;
		$this->login         = user_account_management()->login;
		$this->register      = user_account_management()->register;
		$this->account       = user_account_management()->account;
		$this->cache         = user_account_management()->cache;

		$this->add_actions();

	}

	public function add_actions() {
		// api endpoints that can be called by other stuff
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
	}

	/**
	 * Register API endpoints for dealing with user accounts
	 *
	 */
	public function register_api_endpoints() {
		register_rest_route(
			$this->slug . '/v1',
			'/check-zip',
			array(
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
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/check-account-exists',
			array(
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
			)
		);
		register_rest_route(
			$this->slug . '/v1',
			'/create-user',
			array(
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
		$user_data = $this->user_data->setup_user_data( $posted );

		$data = $this->user_data->register_or_update_user( $user_data, 'register', array() );

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
	 * @param object  $request    The REST request
	 *
	 * @return array   The REST response
	 *
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
}
