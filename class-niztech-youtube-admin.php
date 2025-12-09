<?php
/**
 * @file
 * Logic for administrative view of `Niztech_Youtube`
 *
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:45 PM
 */


class Niztech_Youtube_Admin {
	const NONCE_CONTENT_BEHAVIOR   = Niztech_Youtube::PLUGIN_PREFIX . '_content_behavior';
	const NONCE_SAVE_PLAYLIST_DATA = Niztech_Youtube::PLUGIN_PREFIX . '_admin_save_playlist_data';
	const NONCE_UPDATE_KEY         = Niztech_Youtube::PLUGIN_PREFIX . '_update_key';

	public static function init() {
		add_action( 'admin_menu', array( 'Niztech_Youtube_Admin', 'admin_menu' ), 3 );
		add_action( 'admin_enqueue_scripts', array( 'Niztech_Youtube_Admin', 'load_resources' ) );

		add_action( 'save_post', array( 'Niztech_Youtube_Admin', 'video_source_save' ) );
		add_action( 'admin_notices', array( 'Niztech_Youtube_Admin', 'admin_notices' ) );

		add_action( 'load-post.php', array( 'Niztech_Youtube_Admin', 'metabox_video_source_setup' ) );
		add_action( 'load-post-new.php', array( 'Niztech_Youtube_Admin', 'metabox_video_source_setup' ) );

		add_action( 'wp_ajax_niztech_youtube_admin_hide_video', array( 'Niztech_Youtube_Admin', 'video_mark_hidden' ) );
		add_action(
			'wp_ajax_nopriv_niztech_youtube_admin_hide_video',
			array( 'Niztech_Youtube_Admin', 'video_mark_hidden_NOOP' )
		);
	}

	public static function load_resources(): void {
		global $hook_suffix;
		if ( in_array(
			$hook_suffix,
			apply_filters(
				'niztech_youtube_admin_page_hook_suffixes',
				array(
					'index.php', // dashboard
					'post.php',
					'settings_page_niztech-youtube-config',
					'plugins.php',
				)
			)
		) ) {
			wp_register_style(
				'niztech_youtube.css',
				plugin_dir_url( __FILE__ ) . '_inc/niztech_youtube.css',
				array(),
				NT_YOUTUBE_PLUGIN_VERSION
			);
			wp_enqueue_style( 'niztech_youtube.css' );

			wp_enqueue_script(
				'niztech_youtube_admin.js',
				plugin_dir_url( __FILE__ ) . '_inc/niztech_youtube_admin.js',
				null,
				null,
				true
			);
			wp_localize_script(
				'niztech_youtube_admin.js',
				'myAjax',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'niztech-youtube-ajax-nonce' ),
				)
			);
		}
	}

	public static function get_page_url( $page = 'config' ): string {

		$args = array( 'page' => 'niztech-youtube-config' );

		if ( $page == 'delete_key' ) {
			$args = array(
				'page'     => 'niztech-youtube-config',
				'view'     => 'config',
				'action'   => 'delete-key',
				'_wpnonce' => wp_create_nonce( Niztech_Youtube_Admin::NONCE_UPDATE_KEY ),
			);
		}

		return add_query_arg( $args, admin_url( 'options-general.php' ) );
	}

	/**
	 * Adds the route and menu under "Settings"
	 * wp-admin/options-general.php
	 *
	 * @return void
	 */
	public static function admin_menu(): void {
		add_options_page(
			__( 'Niztech YouTube', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ),
			__( 'Niztech YouTube', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ),
			'manage_options',
			'niztech-youtube-config',
			array( 'Niztech_Youtube_Admin', 'config_page_render' )
		);
	}

	public static function config_page_render(): void {
		include NT_YOUTUBE__PLUGIN_DIR . '/views/config.php';
	}

	/**
	 * Adds the UI that is used to update and manage the video and playlist within admin view.
	 *
	 * @return void
	 */
	public static function metabox_video_source_setup(): void {
		add_action( 'add_meta_boxes', array( 'Niztech_Youtube_Admin', 'metabox_video_source_playlist' ) );
	}

	public static function metabox_video_source_playlist(): void {
		add_meta_box(
			'metabox-source-playlist-code',
			esc_html__( 'Playlist ID (code)', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ),  // Title
			array( 'Niztech_Youtube_Admin', 'metabox_video_source_playlist_html' ),   // Callback function
			'video_series',         // Admin page (or post type)
			'normal',               // Context
			'default'               // Priority
		);
	}

	/**
	 * This function gets called from the admin page, specifically a metabox screen.
	 *
	 * @param $post_id
	 *
	 * @throws Exception
	 */
	public static function video_source_save( $post_id ): void {
		$youtube_url             = esc_attr( $_POST['niztech_youtube_url'] ?? '' );
		$youtube_type            = esc_attr( $_POST['niztech_youtube_type'] ?? '' );
		$youtube_use_as_featured = esc_attr( $_POST['niztech_youtube_use_youtube_featured'] ?? false );
		$youtube_nonce           = esc_attr( $_POST['niztech_youtube_source_nonce'] ?? '' );
		$youtube_foreign_key     = esc_attr( $_POST['niztech_youtube_foreign_key'] ?? '' );

		// Only save changes if the user clicked save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Validate that the request came from the user via admin screen.
		if ( ! isset( $_POST['niztech_youtube_source_nonce'] ) || ! wp_verify_nonce(
			$youtube_nonce,
			Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA
		) ) {
			return;
		}

		// Validate that the user has permission to make changes.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_permission_denied', true, 30 );

			return;
		}

		if ( empty( $youtube_url ) ) {
			// Delete post data because $youtube_code is empty.
			Niztech_Youtube::delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::delete_video_by_post_playlist( $post_id, null );

			// TODO: Supply a message stating that all data was removed.
			// or instead have an explicit delete button. Leave what is in the database behind
			set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_deleted', true, 30 );

			return;
		}

		if ( ! Niztech_Youtube::is_youtube_url( $youtube_url ) ) {
			set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_invalid_url', true, 30 );

			return;
		}

		try {
			$youtube_code = Niztech_Youtube::extract_youtube_code( $youtube_url, $youtube_type );
		} catch ( \Exception $e ) {
			// TODO: Should show error if no valid code found for type
			set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_no_youtube_code_extracted', true, 30 );

			return;
		}

		$saved_data = null;

		if ( $youtube_type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			Niztech_Youtube::delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::delete_video_by_post_playlist( $post_id, null );
			try {
				$saved_data = Niztech_Youtube::get_video_info_for( $post_id, $youtube_code, true );
				set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_video_saved', true, 30 );
			} catch ( \Exception $e ) {
				// Instead of above do:
				// get video data from youtube (see get_video_info_for)
				// if not null save to wp
				// update post metadata
				// if null report error
				update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'type', $youtube_type );
				set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_video_error', true, 30 );

				return;
			}
		} elseif ( $youtube_type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {
			Niztech_Youtube::delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::delete_video_by_post_playlist( $post_id, null );
			try {
				$saved_data = Niztech_Youtube::get_playlist_info_for( $post_id, $youtube_code, true );
				set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_playlist_saved', true, 30 );
			} catch ( \Exception $e ) {

				// Instead of above do:
				// get playlist data from youtube (see get_playlist_info_for)
				// if not null save to wp,
				// update post metadata
				// if null report error
				update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'type', $youtube_type );
				set_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_playlist_error', true, 30 );

				return;
			}
		}

		update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'use_yt_thumbnail', $youtube_use_as_featured );
		update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'use_yt_url', $youtube_url );
		$filePath = $saved_data->thumbnail_maxres_url ?? $saved_data->thumbnail_standard_url ?? $saved_data->thumbnail_default_url ?? null;
		if ( $youtube_use_as_featured === 'on' && $filePath ) {
			Niztech_Youtube_Admin::generate_featured_image( $filePath, $post_id, $saved_data->description );
		}
	}

	public static function metabox_video_source_playlist_html( $post ): void {
		wp_nonce_field( Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA, Niztech_Youtube::PLUGIN_PREFIX . 'source_nonce' );
		$type                = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'type' );
		$use_yt_as_thumbnail = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'use_yt_thumbnail' );
		$youtube_data        = Niztech_Youtube::get_video_or_playlist_code_and_foreign_key( $type, $post->ID );
		$youtube_url         = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'use_yt_url' );
		?>

		<p>
			<label for="niztech_youtube_url"><?php _e( 'Youtube URL', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?></label><br>
			<input type="text" name="niztech_youtube_url" id="niztech_youtube_url" style="width: 80%;"
					value="<?php echo $youtube_url; ?>">
			<input type="hidden" name="niztech_youtube_foreign_key" id="niztech_youtube_foreign_key"
					value="<?php echo $youtube_data->id ?? ''; ?>">
		</p>
		<p>
			<label for="niztech_youtube_type"><?php _e( 'Type', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?></label><br>
			<select name="niztech_youtube_type" id="niztech_youtube_type">
				<option value="Playlist" <?php echo ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) ? 'selected' : ''; ?>>
					Playlist
				</option>
				<option value="Video" <?php echo ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) ? 'selected' : ''; ?>>
					Single Video
				</option>
			</select>
		</p>
		<p>
			<label for="niztech_youtube_use_youtube_featured">
				<?php
				_e(
					'Use Youtube Featured Image',
					Niztech_Youtube::PLUGIN_TEXT_DOMAIN
				);
				?>
			</label><br>
			<input id="niztech_youtube_use_youtube_featured"
					name="niztech_youtube_use_youtube_featured"
				<?php echo $use_yt_as_thumbnail ? ' checked ' : ''; ?>
					type="checkbox">
		</p>
		<p>
			<?php Niztech_Youtube_Admin::video_content_admin_html( $post->ID ); ?>
		</p>

		<?php
	}

	public static function video_mark_hidden(): void {
		// Verify nonce for security
		if ( ! check_ajax_referer( 'niztech-youtube-ajax-nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce' );
			wp_die();
		}

		$is_hidden = filter_var( $_POST['is_hidden'], FILTER_VALIDATE_BOOLEAN );
		$video_id  = filter_var( $_POST['video_id'], FILTER_VALIDATE_INT );
		$post_id   = filter_var( $_POST['post_id'], FILTER_VALIDATE_INT );

		// Validate that the user has permission to make changes.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( "You don't have edit privs" );
			wp_die();
		}

		// Perform operations (e.g., database interactions)
		try {
			Niztech_Youtube::hide_video_by_id( $post_id, $video_id, $is_hidden );
			wp_send_json_success();
		} catch ( Exception $e ) {
			wp_send_json_error( $e->getMessage() );
		}

		// Send a JSON response back to JavaScript
		wp_die();
	}

	public static function video_mark_hidden_NOOP(): void {
		wp_send_json_error( 'you must be authenticated' );
		wp_die();
	}

	/**
	 * Downloads an image from the specified URL and attaches it to a post as a post thumbnail.
	 *
	 * @param string $file The URL of the image to download.
	 * @param int    $post_id The post ID the post thumbnail is to be associated with.
	 * @param string $desc Optional. Description of the image.
	 *
	 * @return bool|int|WP_Error Attachment ID, WP_Error object otherwise.
	 */
	public static function generate_featured_image( string $file, int $post_id, string $desc = '' ): bool|int|WP_Error {
		// Set variables for storage, fix file filename for query strings.
		preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
		if ( ! $matches ) {
			return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
		}

		$file_array         = array();
		$file_array['name'] = basename( $matches[0] );

		// Download file to temp location.
		$file_array['tmp_name'] = download_url( $file );

		// If error storing temporarily, return the error.
		if ( is_wp_error( $file_array['tmp_name'] ) ) {
			return $file_array['tmp_name'];
		}

		// Do the validation and storage stuff.
		$id = media_handle_sideload( $file_array, $post_id, $desc );

		// If error storing permanently, unlink.
		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );

			return $id;
		}

		return set_post_thumbnail( $post_id, $id );
	}

	public static function admin_notices(): void {
		$notice = '';
		$level  = '';
		if ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_permission_denied' ) ) {
			$level  = 'notice-error';
			$notice = __( 'You do not have permission to save this change.' );
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_permission_denied' );
		} elseif ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_deleted' ) ) {
			$level  = 'notice-warning';
			$notice = __( 'Video source removed from WP site' );
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_deleted' );
		} elseif ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_invalid_url' ) ) {
			$level  = 'notice-error';
			$notice = __( 'The video URL used does not appear to be a Youtube video or playlist URL' );
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_invalid_url' );
		} elseif ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_no_youtube_code_extracted' ) ) {
			$level  = 'notice-error';
			$notice = __( 'The video URL appears to be a Youtube URL but a valid playlist or video code could not be extracted.' );
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_no_youtube_code_extracted' );
		} elseif ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_video_saved' ) ) {
			$notice = __( 'Your Youtube video was added to your website' );
			$level  = 'notice-success';
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_video_saved' );
		} elseif ( get_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_playlist_saved' ) ) {
			$notice = __( 'Your Youtube playlist was added to your website' );
			$level  = 'notice-success';
			delete_transient( Niztech_Youtube::PLUGIN_PREFIX . 'video_source_save_playlist_saved' );
		}

		if ( $notice ) {
			?>
			<div class="notice <?php echo $level; ?> is-dismissible">
				<p><strong>Niztech Youtube Plugin:</strong> <?php echo $notice; ?></p>
			</div>
			<?php
		}
	}

	/**
	 * @param string $post_id
	 *
	 *
	 * Returns an array of video content intended for an admin managing the site.
	 *
	 * @return array|object|stdClass[]|void|null
	 */
	public static function video_content_admin( int $post_id ) {
		global $wpdb;
		if ( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;
		}

		$type = Niztech_Youtube::video_source_get_meta(
			Niztech_Youtube::PLUGIN_PREFIX . 'type',
			$post_id
		);

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
	 *
	 *
	 * Returns an HTML ordered list of video content intended for an admin managing the site.
	 */
	public static function video_content_admin_html( $post_id, $class = '', $id = '' ) {
		$videos    = Niztech_Youtube_Admin::video_content_admin( $post_id );
		$output    = '';
		$hideLabel = __(
			'Hide this video',
			Niztech_Youtube::PLUGIN_TEXT_DOMAIN
		);
		if ( ! empty( $videos ) ) {
			$videos_html = '';
			foreach ( $videos as $video ) {
				$video_url      = $video->thumbnail_high_url;
				$video_input_id = sprintf( 'post-%s-video-%s', $post_id, $video->id );

				$is_checked   = ( empty( $video_url ) || $video->hidden == 1 ) ? 'checked' : '';
				$videos_html .= sprintf(
					'<li class="niztech-youtube-thumbnail"><a href="//www.youtube.com/watch?v=%s" class="niztech-youtube-thumbnail-picture" style="background-image: url(\'%s\')"><span class="niztech-youtube-hidden">%s</span></a><div><input id="%s" name="%s" value="%s" type="checkbox" %s /><label for="%s">%s</label></div></li>',
					$video->youtube_video_code,
					$video_url,
					$video->title,
					$video_input_id,
					$post_id,
					$video->id,
					$is_checked,
					$video_input_id,
					$hideLabel
				);
			}
			$id_attrib    = ( empty( $id ) ? '' : sprintf( 'id="%s"', $id ) );
			$class_attrib = empty( $class ) ? 'class="niztech-youtube-thumbnails' : sprintf(
				'class="niztech-youtube-thumbnails %s"',
				$class
			);
			$output       = sprintf(
				'<ol %s">%s</ol>',
				implode( ' ', array( $id_attrib, $class_attrib ) ),
				$videos_html
			);
		}

		echo $output;
	}
}
