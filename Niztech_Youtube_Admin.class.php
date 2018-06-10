<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:45 PM
 */

namespace Niztech\Youtube;


class Niztech_Youtube_Admin {


	public function __construct() {
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
}

/*
NiztechYoutubeAdmin::playlistItemsListByPlaylistId( $service, 'snippet,contentDetails',
	array( 'maxResults' => '', 'playlistId' => 'PLhTSRIqkFBz5go_OZP63nw-NXEnSKELHk' ) );
*/