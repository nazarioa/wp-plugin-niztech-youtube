<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 3:45 PM
 */


class Niztech_Youtube_Admin {

	const TYPE_OPTION_PLAYLIST = 'Playlist';
	const TYPE_OPTION_VIDEO = 'Single Video';
	const NONCE_UPDATE_KEY = Niztech_Youtube::PLUGIN_PREFIX . '_update_key';
	const NONCE_SAVE_PLAYLIST_DATA = Niztech_Youtube::PLUGIN_PREFIX . '_admin_save_playlist_data';

	public function __construct() {
	}

	public function init() {
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
//		print_r(self::playlist_items_list_by_playlist_Id( Niztech_Youtube::$google_service, 'snippet,contentDetails',
//			array( 'maxResults' => '', 'playlistId' => 'PLhTSRIqkFBz5go_OZP63nw-NXEnSKELHk' ) ));
	}

	public static function metabox_video_source_setup() {
		add_action( 'add_meta_boxes', array( 'Niztech_Youtube_Admin', 'metabox_video_source_playlist' ) );
	}

	public static function video_source_get_meta( $value ) {
		global $post;

		$field = get_post_meta( $post->ID, $value, true );
		if ( ! empty( $field ) ) {
			return is_array( $field ) ? stripslashes_deep( $field ) : stripslashes( wp_kses_decode_entities( $field ) );
		} else {
			return false;
		}
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

	public static function video_source_save( $post_id ) {
		print_r( array($youtube_url, $youtube_type, $youtube_foreign_key, $youtube_nonce) );
		$youtube_url   = esc_attr( $_POST['niztech_video_youtube_url'] );
		$youtube_type  = esc_attr( $_POST['niztech_video_youtube_type'] );
		$youtube_nonce = esc_attr( $_POST['niztech_video_source_nonce'] );

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Validate that the input is good.
		if ( ! isset( $_POST['niztech_video_source_nonce'] ) || ! wp_verify_nonce( $youtube_nonce,
				Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $youtube_url ) ) {
			update_post_meta( $post_id, 'niztech_video_youtube_url', $youtube_url );
		}
		if ( isset( $youtube_type ) ) {
			update_post_meta( $post_id, 'niztech_video_youtube_type', $youtube_type );
		}
	}

	public static function metabox_video_source_playlist_html( $post ) {
		$type = self::video_source_get_meta( 'niztech_video_youtube_type' ) ?>
		wp_nonce_field( Niztech_Youtube_Admin::NONCE_SAVE_PLAYLIST_DATA, 'niztech_video_source_nonce' );

        <p>
            <label for="niztech_video_youtube_url"><?php _e( 'Youtube URL', 'video_source' ); ?></label><br>
            <input type="text" name="niztech_video_youtube_url" id="niztech_video_youtube_url"
                   value="<?php echo Niztech_Youtube::get_video_or_playlist_code( $type, $post->ID ); ?>">
        </p>
        <p>
            <label for="niztech_video_youtube_type"><?php _e( 'Type', 'video_source' ); ?></label><br>
            <select name="niztech_video_youtube_type" id="niztech_video_youtube_type">
                <option <?php echo ( $type == self::TYPE_OPTION_PLAYLIST ) ? 'selected' : '' ?>>
                    Playlist
                </option>
                <option <?php echo ( $type == self::TYPE_OPTION_VIDEO ) ? 'selected' : '' ?>>
                    Single Video
                </option>
            </select>
        </p>
	<?php }
}