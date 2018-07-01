<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:46 PM
 */


class Niztech_Youtube_Client {

	public static function init() {
		add_shortcode( 'ntyt_videos', array( 'Niztech_Youtube_Client', 'video_content_shortcode' ) );
	}

	public static function video_content( $post_id ) {
		global $wpdb;
		if ( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;
		}

		$type               = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'type' );
		$foreign_key_object = Niztech_Youtube::get_video_or_playlist_code_and_foreign_key( $type, $post_id );

		if ( empty( $foreign_key_object ) || empty( $type ) ) {
			return null;
		}

		if ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {

			return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
			                           " WHERE playlist_id = $foreign_key_object->id;" );
		} elseif ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			return $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
			                           " WHERE post_id = $foreign_key_object->post_id AND playlist_id = 0;" );
		}

	}

	/**
	 * @param string $class
	 * @param string $id
	 * @param string $post_id
	 */
	public static function video_content_html( $post_id, $class = '', $id = '' ) {
		$videos = Niztech_Youtube_Client::video_content( $post_id );
		$output = '';
		if ( ! empty( $videos ) ) {
			$videos_html     = '';
			foreach ( $videos as $video ) {
				$videos_html .= sprintf( '<a href="//www.youtube.com/watch?v=%s" class="niztech-youtube-thumbnail-picture" style="background-image: url(\'%s\')" title="%s"></a>',
					$video->youtube_video_code , $video->thumbnail_high_url,  $video->title);
			}
			$id_attrib = (empty($id) ? '' : sprintf('id="%s"'));
			$class_attrib =  empty($class) ? 'class="niztech-youtube-thumbnails' : sprintf('class="niztech-youtube-thumbnails %s"', $class);
			$output = sprintf( '<div %s">%s</div>', implode(' ', array($id_attrib,$class_attrib )), $videos_html );
		}

		echo $output;
	}

	/**
	 * Short code implementation for video content
	 *
	 * @param $attr
	 *
	 * @return string
	 */
	public static function video_content_shortcode( $attr ) {
		$a = shortcode_atts( array(
			'class'  => '',
			'id'     => '',
			'number' => 5,
		), $attr );

		return Niztech_Youtube_Client::video_content_html( $a['class'], $a['id'], $a['number'] );
	}

}