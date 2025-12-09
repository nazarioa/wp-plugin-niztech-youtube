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
			<h2><?php esc_attr_e( 'Google API', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?></h2>
			<div class="niztech-youtube-row">
				<label class="niztech-youtube-label" for="niztech-youtube-api">
				<?php
				esc_attr_e(
					'YouTube V3 API Key',
					Niztech_Youtube::PLUGIN_TEXT_DOMAIN
				);
				?>
						</label>
				<input class="niztech-youtube-input" id="niztech-youtube-api" name="niztech-youtube-api"
						value="<?php echo esc_attr( Niztech_Youtube::get_youtube_api_key() ); ?>"/>
				<input type="hidden" name="action" value="<?php echo( Niztech_Youtube_Form_Actions::Enter_Youtube_Api_Key ); ?>">
				<?php wp_nonce_field( Niztech_Youtube_Admin::NONCE_UPDATE_KEY ); ?>
				<input type="submit"
						name="submit"
						id="submit"
						class="niztech-youtube-btn niztech-youtube-btn-primary"
						value="<?php esc_attr_e( 'Save', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?>">
			</div>
		</form>

		<form action="<?php echo esc_url( Niztech_Youtube_Admin::get_page_url() ); ?>" method="POST">
			<h2>Content Behavior</h2>
			<div class="niztech-youtube-row">
				<label class="niztech-youtube-label" for="niztech-youtube-hide-video">Deleted Video Behavior</label>

				<div>

					<select name="niztech-content-behavior" id="niztech-content-behavior">
						<option value="hide">Hide (default)</option>
						<option value="show">Show</option>
					</select>
					<p>When videos are removed from YouTube, how should the content be rendered by default? <br/>This
						behavior can be over-ridden per playlist</p>
				</div>

				<input type="hidden" name="action" value="<?php echo( Niztech_Youtube_Form_Actions::Enter_Default_Content_Behavior ); ?>">

				<?php wp_nonce_field( Niztech_Youtube_Admin::NONCE_CONTENT_BEHAVIOR ); ?>

				<input type="submit"
						name="submit"
						id="submit"
						class="niztech-youtube-btn niztech-youtube-btn-primary"
						value="<?php esc_attr_e( 'Save', Niztech_Youtube::PLUGIN_TEXT_DOMAIN ); ?>">
			</div>
		</form>
	</section>

	<section class="niztech-youtube-section">
		<div class="niztech-youtube-row">
			<div class="niztech-youtube-label">
			<?php
			esc_attr_e(
				'Database Version',
				Niztech_Youtube::PLUGIN_TEXT_DOMAIN
			);
			?>
					</div>
			<div
				class="niztech-youtube-input">
				<?php
				echo( intval(
					get_option( Niztech_Youtube::PLUGIN_PREFIX . 'db_version' ),
					10
				) );
				?>
					</div>
		</div>
	</section>

</main>
