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

	private static $initiated = false;
	private static $notices = array();

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
	}

	public static function plugin_activation() {
		self::niztech_youtube_create_table_playlist();
		self::niztech_youtube_create_table_video();
		add_option( self::PLUGIN_PREFIX . 'db_version', NT_YOUTUBE_DATABASE_VERSION );
	}

	public static function plugin_deactivation() {
	}

	public static function enter_api_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die( __( 'Cheatin&#8217; uh?', self::PLUGIN_TEXT_DOMAIN ) );
		}

		if ( ! wp_verify_nonce( $_POST['_wpnonce'], Niztech_Youtube_Admin::NONCE ) ) {
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

	public static function niztech_youtube_create_table_playlist() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'niztech_youtube_playlists';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			youtube_playlist_url varchar(100) DEFAULT '' NOT NULL,
			title tinytext NOT NULL,
			last_refresh datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}

	public static function niztech_youtube_create_table_video() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'niztech_youtube_videos';
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			youtube_video_id varchar(55) DEFAULT '' NOT NULL,
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

}