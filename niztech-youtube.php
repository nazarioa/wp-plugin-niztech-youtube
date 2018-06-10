<?php
/*
  Plugin Name: Niztech - YouTube
  Plugin URI: https://www.niztech.com
  Description: Simple YouTube data source
  Version: 1.0.0
  Author: EmbedPlus Team
  Author URI: https://www.embedplus.com
 */

if ( ! file_exists( $file = __DIR__ . '/vendor/autoload.php' ) ) {
	throw new \Exception( 'please run "composer install in "' . __DIR__ . '"' );
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();

if ( ! function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

define( 'NT_YOUTUBE_VERSION', '1.0.0' );
define( 'NT_YOUTUBE__MINIMUM_WP_VERSION', '4.0' );
define( 'NT_YOUTUBE__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
