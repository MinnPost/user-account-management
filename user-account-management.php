<?php
/**
 * Plugin Name: User Account Management
 * Description: Replaces the WordPress user account management flow
 * Version: 0.0.18
 * Author: Jonathan Stegall / based on Jarkko Laine's work
 * License: GPL-2.0+
 * Text Domain: user-account-management
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * The full path to the main file of this plugin
 *
 * This can later be passed to functions such as
 * plugin_dir_path(), plugins_url() and plugin_basename()
 * to retrieve information about plugin paths
 *
 * @since 0.0.13
 * @var string
 */
define( 'USER_ACCOUNT_MANAGEMENT_FILE', __FILE__ );

/**
 * The plugin's current version
 *
 * @since 0.0.13
 * @var string
 */
define( 'USER_ACCOUNT_MANAGEMENT_VERSION', '0.0.18' );

// Load the autoloader.
require_once( 'lib/autoloader.php' );

/**
 * Retrieve the instance of the main plugin class
 *
 * @since 0.0.13
 * @return User_Account_Management
 */
function user_account_management() {
	static $plugin;

	if ( is_null( $plugin ) ) {
		$plugin = new User_Account_Management( USER_ACCOUNT_MANAGEMENT_VERSION, __FILE__ );
	}

	return $plugin;
}

user_account_management()->init();
