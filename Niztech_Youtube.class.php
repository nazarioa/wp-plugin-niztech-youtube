<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:55 PM
 */

class Niztech_Youtube {
	const PLUGIN_PREFIX = 'niztech_youtube_';
	const PLUGIN_TEXT_DOMAIN = 'niztech_youtube';
	const TBL_PLAYLIST = 'niztech_youtube_playlists';
	const TBL_VIDEOS = 'niztech_youtube_videos';
	const TYPE_OPTION_PLAYLIST = 'Playlist';
	const TYPE_OPTION_VIDEO = 'Single Video';

	public static $google_service = null;

	private static $initiated = false;
	private static $notices = array();
	private static $video_stale_limit_days = 3;

	private static $youtube_v3_api_key = '';

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'enter-key' ) {
			self::enter_api_key();
		}
	}

	public static function init_hooks() {
		self::$initiated = true;
		try {
			self::setup_youtube_google_client();
		} catch ( \Exception $e ) {
			self::$notices[] = 'Could not setup connection to google, No API Key configured';
		}
	}

	public static function plugin_activation() {
		self::create_table_playlist();
		self::create_table_video();
		add_option( self::PLUGIN_PREFIX . 'db_version', NT_YOUTUBE_DATABASE_VERSION );
	}

	public static function plugin_deactivation() {
	}

	public static function enter_api_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( __( 'Cheatin&#8217; uh?', self::PLUGIN_TEXT_DOMAIN ) );
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], Niztech_Youtube_Admin::NONCE_UPDATE_KEY ) ) {
			return false;
		}

		$new_key = trim( $_POST['niztech-youtube-api'] );
		$old_key = self::get_youtube_api_key();

		if ( empty( $new_key ) ) {
			if ( ! empty( $old_key ) ) {
				delete_option( self::PLUGIN_PREFIX . '_youtube_v3_api_key' );
				self::$notices[] = 'new-key-empty';
			}
		} elseif ( $new_key != $old_key ) {
			self::set_youtube_api_key( $new_key );
		}

		return true;
	}

	public static function get_youtube_api_key() {
		if ( ! empty( self::$youtube_v3_api_key ) ) {
			return self::$youtube_v3_api_key;
		}

		return get_option( self::PLUGIN_PREFIX . '_youtube_v3_api_key', self::$youtube_v3_api_key . '' );
	}

	public static function set_youtube_api_key( $api_key ) {
		$key_status = self::verify_key( $api_key );
		if ( $key_status === 'valid' ) {
			self::$notices['status']  = 'key-valid';
			self::$youtube_v3_api_key = $api_key;
			update_option( self::PLUGIN_PREFIX . '_youtube_v3_api_key', $api_key );
		} else {
			self::$notices['status'] = 'key-not-valid';
		}
	}

	public static function verify_key( $api_key ) {
		// TODO: Improve on this code.
		if ( ! empty( $api_key ) ) {
			return 'valid';
		}

		return 'not-valid';
	}

	public static function create_table_playlist() {
		global $wpdb;
		$table_name      = $wpdb->prefix . TBL_PLAYLIST;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			youtube_playlist_code varchar(255) NOT NULL,
			youtube_playlist_url varchar(100) DEFAULT '' NOT NULL,
			title tinytext NOT NULL,
			last_refresh datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			thumbnail_default_url varchar(255),
			thumbnail_default_width int(4),
			thumbnail_default_height int(4),
			thumbnail_medium_url varchar(255),
			thumbnail_medium_width int(4),
			thumbnail_medium_height int(4),
			thumbnail_high_url varchar(255),
			thumbnail_high_width int(4),
			thumbnail_high_height int(4),
			thumbnail_standard_url varchar(255),
			thumbnail_standard_width int(4),
			thumbnail_standard_height int(4),
			thumbnail_maxres_url varchar(255),
			thumbnail_maxres_width int(4),
			thumbnail_maxres_height int(4),
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function create_table_video() {
		global $wpdb;
		$table_name      = $wpdb->prefix . TBL_VIDEOS;
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			playlist_id mediumint(9) DEFAULT '' NOT NULL,
			post_id bigint(20),
			youtube_video_code varchar(255) DEFAULT '' NOT NULL,
			title tinytext NOT NULL,
			last_update datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			thumbnail_default_url varchar(255),
			thumbnail_default_width int(4),
			thumbnail_default_height int(4),
			thumbnail_medium_url varchar(255),
			thumbnail_medium_width int(4),
			thumbnail_medium_height int(4),
			thumbnail_high_url varchar(255),
			thumbnail_high_width int(4),
			thumbnail_high_height int(4),
			thumbnail_standard_url varchar(255),
			thumbnail_standard_width int(4),
			thumbnail_standard_height int(4),
			thumbnail_maxres_url varchar(255),
			thumbnail_maxres_width int(4),
			thumbnail_maxres_height int(4),
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	/**
	 * @param $youtube_playlist_code
	 *
	 * @return mixed
	 */
	public static function query_playlist_data_from_youtube( $youtube_playlist_code ) {
		$response = self::$google_service->playlistItems->listPlaylistItems(
			'snippet,contentDetails',
			array_filter( array( 'maxResults' => '', 'playlistId' => $youtube_playlist_code ) )
		);

		return ( $response );
	}

	/**
	 * @param $youtube_video_code
	 *
	 * @return mixed
	 */
	public static function query_video_data_from_youtube( $youtube_video_code ) {
		return Niztech_Youtube::$google_service->videos->listVideos( 'snippet,localizations',
			array( 'id' => $youtube_video_code ) )[0];
	}

	/**
	 * ref: https://codex.wordpress.org/Creating_Tables_with_Plugins
	 *
	 * @param $playlist_id
	 * @param $data
	 */
	public static function commit_playlist_data_to_wp( $playlist_id, array $data ) {
		global $wpdb;
		$to_commit = array();

		// Remove existing data
		$wpdb->delete( $wpdb->prefix . self::TBL_VIDEOS, array( 'playlist_id' => $playlist_id ) );

		// Take $data and stores it into database
		foreach ( $data as $datum ) {
			$generic_video_data = array(
				'playlist_id'        => $playlist_id,
				'title'              => $datum->snippet->title,
				'youtube_video_code' => $datum->snippet->resourceId->videoId,
			);

			$thumbnails  = self::process_Google_Service_YouTube_ThumbnailDetails( $datum->snippet->thumbnails );
			$to_commit[] = array_merge( $generic_video_data, $thumbnails );
		}

		// TODO: Not clobber video titles if they have been set to display differently.
		foreach ( $to_commit as $row ) {
			$wpdb->insert( $wpdb->prefix . self::TBL_VIDEOS, $row );
		}

		$today = new DateTime();

		$wpdb->update( $wpdb->prefix . self::TBL_PLAYLIST,
			array( 'last_refresh' => $today->format( 'Y-m-d H:i:s' ) ), //2018-06-14 23:08:15
			array( 'id' => $playlist_id )
		);
	}

	/**
	 * @param $post_id
	 * @param $data
	 * @param $video_code
	 */
	public static function commit_video_data_to_wp( $post_id, $data, $video_code ) {
		global $wpdb;

		// Remove existing data
		$wpdb->delete( $wpdb->prefix . self::TBL_VIDEOS,
			array( 'post_id' => $post_id, 'playlist_id' => 0 ) );

		$today = new DateTime();

		$generic_video_data = array(
			'post_id'            => $post_id,
			'playlist_id'        => 0,
			'youtube_video_code' => $video_code,
			'title'              => $data->snippet->title,
			'last_update'        => $today->format( 'Y-m-d H:i:s' ),
		);

		$thumbnails = self::process_Google_Service_YouTube_ThumbnailDetails( $data->snippet->thumbnails );

		$to_commit = array_merge( $generic_video_data, $thumbnails );

		// TODO: Not clobber video titles if they have been set to display differently.
		$wpdb->insert( $wpdb->prefix . self::TBL_VIDEOS, $to_commit );
	}


	/**
	 * Queries the local database for playlist data. If the data is older than a few days
	 * make the request back to google
	 *
	 * @param string $youtube_playlist_code
	 * @param bool $bypass_cached_data
	 *
	 * @return array of objects
	 */
	public static function get_playlist_info_for( $youtube_playlist_code = '', $bypass_cached_data = false ) {
		global $wpdb;

		// Query cached data
		$playlist_entry = $wpdb->get_row( 'SELECT id, last_refresh ' .
		                                  'FROM ' . $wpdb->prefix . self::TBL_PLAYLIST . ' ' .
		                                  'WHERE youtube_playlist_code = "' . $youtube_playlist_code . '";', 'OBJECT' );

		// if days since $last_refresh > $video_stale_limit_days, set $bypass_cached_data = true
		$last_refresh = new DateTime( $playlist_entry->last_refresh );
		$today        = new DateTime();
		if ( empty( $last_refresh ) || $today->diff( $last_refresh )->days > self::$video_stale_limit_days ) {
			$bypass_cached_data = true;
		}

		if ( $bypass_cached_data ) {
			$raw_data = self::query_playlist_data_from_youtube( $youtube_playlist_code );
			// TODO: Maybe have a cleanup function for that takes $raw_data->items.
			self::commit_playlist_data_to_wp( $playlist_entry->id, $raw_data->items );
		}

		$playlist_data = array();
		// Query video data.
		if ( ! empty( $playlist_entry->id ) ) {
			$playlist_data = $wpdb->get_results( 'SELECT * ' .
			                                     'FROM ' . $wpdb->prefix . self::TBL_VIDEOS . ' ' .
			                                     'WHERE playlist_id = "' . $playlist_entry->id . '";' );
		}

		// returns an array of objects
		return $playlist_data;
	}

	public static function get_video_info_for( $youtube_video_code = '', $post_id, $bypass_cached_data = false ) {
		if ( empty( $youtube_video_code ) ) {
			return null;
		}

		if ( $bypass_cached_data ) {
			$raw_data = self::query_video_data_from_youtube( $youtube_video_code );
			// TODO: Maybe have a cleanup function for that takes $raw_data->items.
			if ( ! empty( $raw_data ) ) {
				Niztech_Youtube::commit_video_data_to_wp( $post_id, $raw_data, $youtube_video_code );
			}
		}

		// query local database for info
		global $wpdb;
		$query = 'SELECT * ' .
		         'FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
		         " WHERE youtube_video_code = post_id = \"$post_id\"";

		return $wpdb->get_row( $query );
	}

	public static function process_Google_Service_YouTube_ThumbnailDetails( $thumbnail_details ) {
		$results         = array();
		$thumbnail_types = array( 'default', 'medium', 'high', 'standard', 'maxres' );
		foreach ( $thumbnail_types as $type ) {
			$results[ 'thumbnail_' . $type . '_url' ]    = $thumbnail_details->$type->url;
			$results[ 'thumbnail_' . $type . '_width' ]  = $thumbnail_details->$type->width;
			$results[ 'thumbnail_' . $type . '_height' ] = $thumbnail_details->$type->height;
		}

		return $results;
	}

	public static function setup_youtube_google_client() {
		$api = self::get_youtube_api_key();
		if ( empty( $api ) ) {
			throw new Exception( __( 'Niztech Youtube could not configured.', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ) );
		}

		$client = new Google_Client();
		$client->setApplicationName( 'Niztech Youtube' );
		$client->setDeveloperKey( $api );
		self::$google_service = new Google_Service_YouTube( $client );
	}

	public static function remove_playlists_for_post( $post_id ) {
		global $wpdb;
		$playlist_tbl_name = $wpdb->prefix . self::TBL_PLAYLIST;
		$playlist_id       = $wpdb->get_row( 'SELECT id FROM ' . $playlist_tbl_name . ' WHERE post_id => ' . $post_id,
			'OBJECT' )->id;
		$wpdb->delete( $wpdb->prefix . self::TBL_VIDEOS, array( 'playlist_id' => $playlist_id ) );
		$wpdb->delete( $playlist_tbl_name, array( 'post_id' => $post_id ) );
	}

	public static function remove_videos_for_post( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . self::TBL_VIDEOS;
		$wpdb->delete( $table_name, array( 'post_id' => $post_id ) );
	}

	public static function get_video_or_playlist_code_and_foreign_key( $type, $post_id ) {
		global $wpdb;
		$query = '';

		if ( empty( $type ) || empty( $post_id ) ) {
			return '';
		} elseif ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {
			$video_tbl_name = $wpdb->prefix . Niztech_Youtube::TBL_PLAYLIST;
			$query          = "SELECT id, post_id, youtube_playlist_code as youtube_code FROM $video_tbl_name WHERE post_id = $post_id";

		} elseif ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			$video_tbl_name = $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS;
			$query          = "SELECT id, post_id, youtube_video_code as youtube_code FROM $video_tbl_name WHERE post_id = $post_id";
		}

		return $wpdb->get_row( $query, 'OBJECT' );
	}

	/**
	 * Method to extract and cleanup the youtube code from a string.s
	 *
	 * @param string $youtube_url_string
	 *
	 * @return string
	 */
	public static function sanitize_url_extract_code( $youtube_url_string = '' ) {
		return $youtube_url_string;
	}

}