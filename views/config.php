<?php
/**
 * Created by PhpStorm.
 * User: nazario
 * Date: 6/10/18
 * Time: 5:11 PM
 */
?>

<main class="niztech-youtube-container">
    <div class="niztech-youtube-mast">
        <img class="niztech-youtube-masthead__logo"
             src="<?php echo esc_url( plugins_url( '../_inc/niztech_logo.svg', __FILE__ ) ); ?>" alt="Niztech"/>
    </div>

    <section class="niztech-youtube-section">
        <div class="niztech-youtube-row">
            <label class="niztech-youtube-label" for="niztech-youtube-api">YouTube V3 API Key</label>
            <input class="niztech-youtube-input" id="niztech-youtube-api"
                   value="<?php echo Niztech_Youtube::get_youtube_api_key(); ?>"/>
        </div>
    </section>

</main>
