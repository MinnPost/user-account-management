<?php
/**
 * Class file for the User_Account_Management_User_Data class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * User data
 */
class User_Account_Management_User_Data {

	public $option_prefix;
	public $version;
	public $slug;
	public $file;

	public $user_id;

	/**
	* Constructor which sets up user data
	*/
	public function __construct() {

		$this->option_prefix = user_account_management()->option_prefix;
		$this->version       = user_account_management()->version;
		$this->slug          = user_account_management()->slug;

		$this->user_id = 0;

		add_action( 'plugins_loaded', array( $this, 'add_actions' ) );

	}

	public function add_actions() {

		add_action( 'init', array( $this, 'set_user_id' ), 10 );

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
	 * Set up user data to be created or updated
	 *
	 * @param object  $posted       The $_POST object
	 * @param array   $user_data    Any already existing data
	 *
	 * @return array   $user_data the data ready to be inserted or updated
	 *
	 */
	public function setup_user_data( $posted, $existing_user_data = array() ) {

		$user_data = array();
		if ( isset( $existing_user_data->ID ) ) {
			$user_data['ID'] = $existing_user_data->ID;
		}

		// email address is used as login in this plugin
		if ( array_key_exists( 'email', $posted ) ) {
			if ( ! empty( $posted['email'] ) ) {
				$user_data['user_email'] = $posted['email'];
				$user_data['user_login'] = $user_data['user_email'];
			}
		}

		// password is only sent to this method on register
		if ( isset( $posted['password'] ) ) {
			$user_data['user_pass'] = $posted['password'];
		}

		// email and password are sanitized by WordPress already

		// first, last, zip, country should be optional since people might want to remove them
		if ( array_key_exists( 'first_name', $posted ) ) {
			if ( ! empty( $posted['first_name'] ) ) {
				$user_data['first_name'] = sanitize_text_field( $posted['first_name'] );
			} else {
				$user_data['first_name'] = '';
			}
		}
		if ( array_key_exists( 'last_name', $posted ) ) {
			if ( ! empty( $posted['last_name'] ) ) {
				$user_data['last_name'] = sanitize_text_field( $posted['last_name'] );
			} else {
				$user_data['last_name'] = '';
			}
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

		if ( array_key_exists( 'zip_code', $posted ) ) {
			if ( ! empty( $posted['zip_code'] ) ) {
				$user_data['zip_code'] = esc_attr( $posted['zip_code'] );
				// if there is an existing zip code but it is unchanged, remove it
				if ( isset( $existing_zip_code ) && $existing_zip_code === $user_data['zip_code'] ) {
					unset( $user_data['zip_code'] );
				}
			} else {
				$user_data['zip_code'] = '';
			}
		}

		if ( array_key_exists( 'country', $posted ) ) {
			if ( ! empty( $posted['country'] ) ) {
				$user_data['country'] = sanitize_text_field( $posted['country'] );
				// if there is an existing country but it is unchanged, remove it
				if ( isset( $existing_country ) && $existing_country === $user_data['country'] ) {
					unset( $user_data['country'] );
				}
			} else {
				$user_data['country'] = '';
			}
		}

		if ( array_key_exists( 'city', $posted ) ) {
			if ( ! empty( $posted['city'] ) ) {
				$user_data['city'] = sanitize_text_field( $posted['city'] );
				// if there is an existing city but it is unchanged, remove it
				if ( isset( $existing_city ) && $existing_city === $user_data['city'] ) {
					unset( $user_data['city'] );
				}
			} else {
				$user_data['city'] = '';
			}
		}

		if ( array_key_exists( 'state', $posted ) ) {
			if ( ! empty( $posted['state'] ) ) {
				$user_data['state'] = sanitize_text_field( $posted['state'] );
				// if there is an existing state but it is unchanged, remove it
				if ( isset( $existing_state ) && $existing_state === $user_data['state'] ) {
					unset( $user_data['state'] );
				}
			} else {
				$user_data['state'] = '';
			}
		}

		if ( array_key_exists( 'user_login', $user_data ) ) {
			$user_data['user_nicename'] = sanitize_title( $user_data['user_login'] );
		}
		if ( array_key_exists( 'first_name', $user_data ) || array_key_exists( 'last_name', $user_data ) ) {
			$user_data['display_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
		}
		if ( array_key_exists( 'first_name', $user_data ) ) {
			$user_data['nickname'] = $user_data['first_name'];
		}

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
	 * Validates and then completes the new user signup or existing user update process if all went well.
	 *
	 * @param array  $user_data            The prepared array of user data
	 * @param string $action               Register or update
	 * @param array  $existing_user_data   If it's an update, allow comparisons with existing user data
	 * @param bool   $return               If true, only return the user. Don't do any redirecting or echoing stuff.
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	public function register_or_update_user( $user_data, $action, $existing_user_data = array() ) {

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

		if ( array_key_exists( 'zip_code', $user_data ) && '' !== $user_data['zip_code'] ) {
			update_user_meta( $user_id, '_zip_code', $user_data['zip_code'] );
		}

		if ( array_key_exists( 'country', $user_data ) && '' !== $user_data['country'] ) {
			update_user_meta( $user_id, '_country', $user_data['country'] );
		}

		// try to get the city/state from the zip code
		if ( array_key_exists( 'zip_code', $user_data ) && '' !== $user_data['zip_code'] ) {
			if ( array_key_exists( 'country', $user_data ) && '' === $user_data['country'] ) {
				$user_data['country'] = 'US';
			}
			if ( ( array_key_exists( 'city', $user_data ) && '' === $user_data['city'] ) || ( array_key_exists( 'state', $user_data ) && '' === $user_data['state'] ) ) {
				$citystate = $this->get_city_state( $user_data['zip_code'], $user_data['country'] ); // this will return an empty value without the api key, this it will not set the below meta fields if that happens
				if ( array_key_exists( 'city', $user_data ) && '' !== $citystate['city'] ) {
					update_user_meta( $user_id, '_city', $citystate['city'] );
				}
				if ( array_key_exists( 'state', $user_data ) && '' !== $citystate['state'] ) {
					update_user_meta( $user_id, '_state', $citystate['state'] );
				}
			}
		}

		if ( array_key_exists( 'city', $user_data ) && '' !== $user_data['city'] ) {
			update_user_meta( $user_id, '_city', $user_data['city'] );
		}
		if ( array_key_exists( 'state', $user_data ) && '' !== $user_data['state'] ) {
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
	
}