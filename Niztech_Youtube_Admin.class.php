<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:45 PM
 */


class Niztech_Youtube_Admin {


	public function __construct() {
	}

	public function init() {
		add_action( 'admin_menu', array( 'Niztech_Youtube_Admin', 'admin_menu' ), 3 );
		add_action( 'admin_enqueue_scripts', array( 'Niztech_Youtube_Admin', 'load_resources' ) );
	}

	public static function load_resources() {
		global $hook_suffix;
		if ( in_array( $hook_suffix, apply_filters( 'niztech_youtube_admin_page_hook_suffixes', array(
			'index.php', # dashboard
			'post.php',
			'settings_page_niztech-youtube-config',
			'plugins.php',
		) ) ) ) {
			wp_register_style( 'niztech_youtube.css', plugin_dir_url( __FILE__ ) . '_inc/niztech_youtube.css', array(),
				NT_YOUTUBE_PLUGIN_VERSION );
			wp_enqueue_style( 'niztech_youtube.css' );
		}
	}

	public static function initOptions() {
	}

	public static function hasApiKey() {
	}

	public static function apiKeyFromDb() {
	}

	public static function ntYoutubePluginMenu() {
	}

	public static function safeGet( $array, $key, $default = null ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}

	public static function playlistItemsListByPlaylistId( $service, $part, $params ) {
		$params   = array_filter( $params );
		$response = $service->playlistItems->listPlaylistItems(
			$part,
			$params
		);

		return ( $response );
	}

	public static function admin_menu() {
		add_options_page(
			__( 'Niztech YouTube', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ),
			__( 'Niztech YouTube', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ),
			'manage_options',
			'niztech-youtube-config',
			array( 'Niztech_Youtube_Admin', 'config_page_render' )
		);
	}

	public static function config_page_render() {
		include( NT_YOUTUBE__PLUGIN_DIR . '/views/config.php' );
	}
}

/*
NiztechYoutubeAdmin::playlistItemsListByPlaylistId( $service, 'snippet,contentDetails',
	array( 'maxResults' => '', 'playlistId' => 'PLhTSRIqkFBz5go_OZP63nw-NXEnSKELHk' ) );
*/