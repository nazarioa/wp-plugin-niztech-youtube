<?php
/*
  Plugin Name: Niztech - YouTube
  Plugin URI: https://www.niztech.com
  Description: Simple YouTube data source
  Version: 1.0.0
  Author: Niztech
  Author URI: https://www.niztech.com
 */

if ( file_exists( $file = __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
} elseif ( file_exists( $file = __DIR__ . '/vendor_committed/autoload.php' ) ) {
	require_once __DIR__ . '/vendor_committed/autoload.php';
} else {
	throw new \Exception( 'please run "composer install in "' . __DIR__ . '"' );
}

if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'NT_YOUTUBE_PLUGIN_VERSION', '1.0.0' );
define( 'NT_YOUTUBE_DATABASE_VERSION', 2 );
define( 'NT_YOUTUBE__MINIMUM_WP_VERSION', '4.0' );
define( 'NT_YOUTUBE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

register_activation_hook( __FILE__, array( 'Niztech_Youtube', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Niztech_Youtube', 'plugin_deactivation' ) );

// Add class files who have hooks that are referenced in this file.
require_once( NT_YOUTUBE__PLUGIN_DIR . 'Niztech_Youtube.class.php' );
require_once( NT_YOUTUBE__PLUGIN_DIR . 'Niztech_Youtube_Client.class.php' );
require_once( NT_YOUTUBE__PLUGIN_DIR . 'Niztech_Youtube_Admin.class.php' );

add_action( 'init', array( 'Niztech_Youtube', 'init' ), 1 );

if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require_once( NT_YOUTUBE__PLUGIN_DIR . 'Niztech_Youtube_Admin.class.php' );
	add_action( 'init', array( 'Niztech_Youtube_Admin', 'init' ) );
}


if ( true ) {
	require_once( NT_YOUTUBE__PLUGIN_DIR . 'Niztech_Youtube_Client.class.php' );
	add_action( 'init', array( 'Niztech_Youtube_Client', 'init' ) );
}