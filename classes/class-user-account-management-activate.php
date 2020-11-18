<?php
/**
 * Class file for the User_Account_Management_Activate class.
 *
 * @file
 */

if ( ! class_exists( 'User_Account_Management' ) ) {
	die();
}

/**
 * Create activate functionality
 */
class User_Account_Management_Activate {

	public $option_prefix;
	public $version;
	public $slug;

	/**
	* Constructor which sets up shortcodes
	*/
	public function __construct() {

		$this->option_prefix = user_account_management()->option_prefix;
		$this->version       = user_account_management()->version;
		$this->slug          = user_account_management()->slug;

		$this->add_actions();

	}

	private function add_actions() {
		register_activation_hook( dirname( __DIR__ ) . '/' . $this->slug . '.php', array( $this, 'plugin_activated' ) );
	}

	/**
	* Check for the minimum required version of php
	*/
	public function php_requirements() {
		if ( version_compare( PHP_VERSION, '5.5', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( '<strong>This plugin requires PHP Version 5.5</strong> <br />Please contact your host to upgrade PHP on your server, and then retry activating the plugin.' );
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
			'user'             => array(
				'title'   =>
					sprintf(
						// translators: placeholder refers to site name
						esc_html__( 'Your %1$s account', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[account-info]',
			),
			'login'            => array(
				'title'   =>
					sprintf(
						// translators: placeholder refers to site name
						esc_html__( 'Log in to %1$s', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[custom-login-form]',
				'parent'  => 'user',
			),
			'register'         => array(
				'title'   =>
					sprintf(
						// translators: placeholder refers to site name
						esc_html__( 'Create your %1$s account', 'user-account-management' ),
						get_bloginfo( 'name' )
					),
				'content' => '[custom-register-form]',
				'parent'  => 'user',
			),
			'password-lost'    => array(
				'title'   => __( 'Forgot Your Password?', 'user-account-management' ),
				'content' => '[custom-password-lost-form]',
				'parent'  => 'user',
			),
			'password-reset'   => array(
				'title'   => __( 'Set a New Password', 'user-account-management' ),
				'content' => '[custom-password-reset-form]',
				'parent'  => 'user',
			),
			'password'         => array(
				'title'   => __( 'Change Your Password', 'user-account-management' ),
				'content' => '[custom-password-change-form]',
				'parent'  => 'user',
			),
			'account-settings' => array(
				'title'   => __( 'Account Settings', 'user-account-management' ),
				'content' => '[custom-account-settings-form]',
				'parent'  => 'user',
			),
		);

		$slug = user_account_management()->slug; // for PHP 7.4 compatibility
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

}
