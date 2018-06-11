<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 5:11 PM
 */
?>

<main class="niztech-youtube-container">
    <div class="niztech-youtube-section niztech-youtube-mast">
        <img class="niztech-youtube-logo"
             src="<?php echo esc_url( plugins_url( '../_inc/niztech_logo.svg', __FILE__ ) ); ?>" alt="Niztech"/>
        <span class="niztech-title">Niztech YouTube</span>
    </div>

	<?php if ( ! empty( $notices ) ) { ?>
		<?php foreach ( $notices as $notice ) { ?>
            <div class="niztech-youtube-section">
				<?php print_r( $notice ); ?>
            </div>
		<?php } ?>
	<?php } ?>

    <section class="niztech-youtube-section">
        <form action="<?php echo esc_url( Niztech_Youtube_Admin::get_page_url() ); ?>" method="POST">
            <div class="niztech-youtube-row">
                <label class="niztech-youtube-label" for="niztech-youtube-api">YouTube V3 API Key</label>
                <input class="niztech-youtube-input" id="niztech-youtube-api" name="niztech-youtube-api"
                       value="<?php echo esc_attr( Niztech_Youtube::get_youtube_api_key() ); ?>"/>
                <input type="hidden" name="action" value="enter-key">
				<?php wp_nonce_field( Niztech_Youtube_Admin::NONCE ) ?>
                <input type="submit"
                       name="submit"
                       id="submit"
                       class="niztech-youtube-btn niztech-youtube-btn-primary"
                       value="<?php esc_attr_e( 'Save', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?>">
            </div>
        </form>
    </section>

</main>
