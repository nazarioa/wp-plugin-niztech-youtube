<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:46 PM
 */


class Niztech_Youtube_Client {

	public static function video_content( $post_id ) {
		global $wpdb;
		if ( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;
		}

		$type               = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'type' );
		$foreign_key_object = Niztech_Youtube::get_video_or_playlist_code_and_foreign_key( $type, $post_id );

		if ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {
			return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS );//.
//			                           " WHERE playlist_id = $foreign_key_object->id" );
		} elseif ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
			                           " WHERE post_id = $foreign_key_object->post_id AND playlist_id = 0" );
		}

	}
}