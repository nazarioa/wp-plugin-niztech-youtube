<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:45 PM
 */


class Niztech_Youtube_Admin {
	const NONCE_UPDATE_KEY = Niztech_Youtube::PLUGIN_PREFIX . '_update_key';
	const NONCE_SAVE_PLAYLIST_DATA = Niztech_Youtube::PLUGIN_PREFIX . '_admin_save_playlist_data';

	public static function init() {
		add_action( 'admin_menu', array( 'Niztech_Youtube_Admin', 'admin_menu' ), 3 );
		add_action( 'admin_enqueue_scripts', array( 'Niztech_Youtube_Admin', 'load_resources' ) );

		add_action( 'save_post', array( 'Niztech_Youtube_Admin', 'video_source_save' ) );

		add_action( 'load-post.php', array( 'Niztech_Youtube_Admin', 'metabox_video_source_setup' ) );
		add_action( 'load-post-new.php', array( 'Niztech_Youtube_Admin', 'metabox_video_source_setup' ) );

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

	public static function get_page_url( $page = 'config' ) {

		$args = array( 'page' => 'niztech-youtube-config' );

		if ( $page == 'delete_key' ) {
			$args = array(
				'page'     => 'niztech-youtube-config',
				'view'     => 'config',
				'action'   => 'delete-key',
				'_wpnonce' => wp_create_nonce( Niztech_Youtube_Admin::NONCE_UPDATE_KEY )
			);
		}

		$url = add_query_arg( $args, admin_url( 'options-general.php' ) );

		return $url;
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

	public static function metabox_video_source_setup() {
		add_action( 'add_meta_boxes', array( 'Niztech_Youtube_Admin', 'metabox_video_source_playlist' ) );
	}

	public static function metabox_video_source_playlist() {
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
	 */
	public static function video_source_save( $post_id ) {
		// TODO: Make the sanitize_url_extract_code return an object with keys on for each type.
		$youtube_code        = esc_attr( Niztech_Youtube::sanitize_url_extract_code( $_POST['niztech_video_youtube_url'] ?? '' ) );
		$youtube_type        = esc_attr( $_POST['niztech_video_youtube_type'] ?? '' );
		$youtube_nonce       = esc_attr( $_POST['niztech_video_source_nonce'] ?? '' );
		$youtube_foreign_key = esc_attr( $_POST['niztech_video_foreign_key'] ?? '' );

		// Only save changes if the user clicked save.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Validate that the request came from the user via admin screen.
		if ( ! isset( $_POST['niztech_video_source_nonce'] ) || ! wp_verify_nonce( $youtube_nonce,
				Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA ) ) {
			return;
		}

		// Validate that the user has permission to make changes.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $youtube_code ) ) {
			// Delete post data because $youtube_code is empty.
			Niztech_Youtube::v2_delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::v2_delete_video_by_post_playlist( $post_id, null );

			// TODO: Supply a message stating that all data was removed.
			return;

		} elseif ( $youtube_type == Niztech_Youtube::TYPE_OPTION_VIDEO ) {
			Niztech_Youtube::v2_delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::v2_delete_video_by_post_playlist( $post_id, null );
			Niztech_Youtube::get_video_info_for( $youtube_code, $post_id, true );
			// Instead of above do:
			// get video data from youtube (see get_video_info_for)
			// if not null save to wp
			// update post metadata
			// if null report error
			update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'type', $youtube_type );

		} elseif ( $youtube_type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) {
			Niztech_Youtube::v2_delete_playlist_by_post_id( $post_id );
			Niztech_Youtube::v2_delete_video_by_post_playlist( $post_id, null );
			Niztech_Youtube::get_playlist_info_for( $youtube_code, $post_id, true );
			// Instead of above do:
			// get playlist data from youtube (see get_playlist_info_for)
			// if not null save to wp,
			// update post metadata
			// if null report error
			update_post_meta( $post_id, Niztech_Youtube::PLUGIN_PREFIX . 'type', $youtube_type );
		}
	}

	public static function metabox_video_source_playlist_html( $post ) {
		wp_nonce_field( Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA, 'niztech_video_source_nonce' );
		$type         = Niztech_Youtube::video_source_get_meta( Niztech_Youtube::PLUGIN_PREFIX . 'type' );
		$youtube_data = Niztech_Youtube::get_video_or_playlist_code_and_foreign_key( $type, $post->ID );
		?>

        <p>
            <label for="niztech_video_youtube_url"><?php _e( 'Youtube URL', 'video_source' ); ?></label><br>
            <input type="text" name="niztech_video_youtube_url" id="niztech_video_youtube_url"
                   value="<?php echo $youtube_data->youtube_code ?? ''; ?>">
            <input type="hidden" name="niztech_video_foreign_key" id="niztech_video_foreign_key"
                   value="<?php echo $youtube_data->id ?? ''; ?>">
        </p>
        <p>
            <label for="niztech_video_youtube_type"><?php _e( 'Type', 'video_source' ); ?></label><br>
            <select name="niztech_video_youtube_type" id="niztech_video_youtube_type">
                <option <?php echo ( $type == Niztech_Youtube::TYPE_OPTION_PLAYLIST ) ? 'selected' : '' ?>>
                    Playlist
                </option>
                <option <?php echo ( $type == Niztech_Youtube::TYPE_OPTION_VIDEO ) ? 'selected' : '' ?>>
                    Single Video
                </option>
            </select>
        </p>
        <p>
			<?php Niztech_Youtube_Client::video_content_html( $post->ID ); ?>
        </p>

	<?php }
}