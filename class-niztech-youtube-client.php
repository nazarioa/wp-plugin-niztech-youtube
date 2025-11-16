<?php
/**
 * @file
 * Logic for content view of `Niztech_Youtube`
 *
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

		$type               = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'type', $post_id );
		$foreign_key_object = Niztech_Youtube::get_video_or_playlist_code_and_foreign_key( $type, $post_id );

		if ( empty( $foreign_key_object ) || empty( $type ) ) {
			return null;
		}

		if ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {

			return $wpdb->get_results(
				'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
										" WHERE playlist_id = $foreign_key_object->id;"
			);
		} elseif ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			return $wpdb->get_results(
				'SELECT * FROM ' . $wpdb->prefix . Niztech_Youtube::TBL_VIDEOS .
										" WHERE post_id = $foreign_key_object->post_id AND playlist_id = 0;"
			);
		}
	}

	/**
	 * @param string $class
	 * @param string $id
	 * @param string $post_id
	 */
	public static function video_content_html( $post_id, $class = '', $id = '' ) {
		$videos    = Niztech_Youtube_Client::video_content( $post_id );
		$output    = '';
		$hideLabel = __(
			'Hide override',
			Niztech_Youtube::PLUGIN_TEXT_DOMAIN
		);
		if ( ! empty( $videos ) ) {
			$videos_html = '';
			foreach ( $videos as $video ) {
				$video_url      = $video->thumbnail_high_url;
				$video_input_id = sprintf( 'post-%s-video-%s', $post_id, $video->id );

				if ( empty( $video_url ) ) {
					$videos_html .= sprintf(
						'<li class="niztech-youtube-thumbnail"><a href="//www.youtube.com/watch?v=%s" class="niztech-youtube-thumbnail-picture" style="background-image: url(\'%s\')"><span class="niztech-youtube-hidden">%s</span></a><div><input id="%s" name="hidden[%s][]" value="%s" type="checkbox" checked /><label for="%s">%s</label></div></li>',
						$video->youtube_video_code,
						$video_url,
						$video->title,
						$video_input_id,
						$post_id,
						$video->id,
						$video_input_id,
						$hideLabel
					);
				} else {
					$videos_html .= sprintf(
						'<li class="niztech-youtube-thumbnail"><a href="//www.youtube.com/watch?v=%s" class="niztech-youtube-thumbnail-picture" style="background-image: url(\'%s\')"><span class="niztech-youtube-hidden">%s</span></a><div><input id="%s" name="hidden[%s][]" value="%s" type="checkbox" /><label for="%s">%s</label></div></li>',
						$video->youtube_video_code,
						$video_url,
						$video->title,
						$video_input_id,
						$post_id,
						$video->id,
						$video_input_id,
						$hideLabel
					);
				}
			}
			$id_attrib    = ( empty( $id ) ? '' : sprintf( 'id="%s"', $id ) );
			$class_attrib = empty( $class ) ? 'class="niztech-youtube-thumbnails' : sprintf( 'class="niztech-youtube-thumbnails %s"', $class );
			$output       = sprintf( '<ol %s">%s</ol>', implode( ' ', array( $id_attrib, $class_attrib ) ), $videos_html );
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
		$a = shortcode_atts(
			array(
				'class' => '',
				'id'    => '',
				'post'  => '',
			),
			$attr
		);

		Niztech_Youtube_Client::video_content_html( $a['post'], $a['class'], $a['id'] );
	}
}
