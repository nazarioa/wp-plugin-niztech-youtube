<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:55 PM
 */

class Niztech_Youtube {
	const PLUGIN_PREFIX = 'niztech_youtube_';
	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	public static function init_hooks() {
		self::$initiated = true;
	}

	public static function plugin_activation() {
		self::niztech_youtube_create_table_playlist();
		self::niztech_youtube_create_table_video();
		add_option( PLUGIN_PREFIX . 'db_version', NT_YOUTUBE_DATABASE_VERSION );
	}

	public static function plugin_deactivation() {
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