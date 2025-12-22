# Niztech YouTube — README

Brief plugin for syncing YouTube playlists / single videos into WordPress posts and rendering them.

## Development

1. Requirements:
    - PHP >= 8.2
    - Composer
    - WordPress installation (plugin active)
    - Google API key for YouTube Data API v3

2. Install Dependencies:

    ```bash
    composer install
    ```

3. Coding / quality: To maintain code quality run the linter using PHPCS via composer scripts

    ```bash
    composer run-script lint
    ```

    ```bash
    composer run-script lint-fix
    ```

4. Deployment:
    1. The code needs to be packaged up in order to deploy it on a Wordpress instance.
        ```bash
        composer run-script build-prod
        ```
    2. A zip file will be output. Upload that to the Wordpress instance
    3. TODO: Package it up and deeploy to github or Wordpress.com

### Development Documentation

- Google client is initialized in `Niztech_Youtube::setup_youtube_google_client()`.
- API key is stored as option `niztech_youtube__youtube_v3_api_key` (entered via admin page).
- Database helper methods are in `class-niztech-youtube.php`; inspect them before changing schema.

1. File Structure
    - `niztech-youtube.php` — plugin bootstrap (loads `vendor` or `vendor_committed`, registers activation/deactivation,
      registers init hooks).
    - `class-niztech-youtube.php` — core logic: Google client setup, DB migrations, YouTube queries, local
      commit/delete/hide helpers, helpers for extracting codes and metadata.
    - `class-niztech-youtube-admin.php` — admin UI: settings page (`views/config.php`), metabox for post type
      `video_series`, admin AJAX (hide video), featured image generation.
    - `class-niztech-youtube-client.php` — front-end: retrieves visible videos and renders HTML list; registers
      shortcode.
    - `views/config.php` — admin settings view (API key, behavior, DB version).
    - `_inc/niztech_youtube_admin.js` — admin JS that posts AJAX requests to hide/show videos.
    - `composer.json` — PHP deps and dev tools.
    - `composer_commited/` — Allows easy deployment of the plugin.

2. Database Structure
   The plugin will create and update the database schemas a needed. The version of the database can be seen in the
   admin-settings
   for the plugin.

The plugin uses two tables to store the information downloaded from Youtube

- `wp_niztech_youtube_playlists`
- `wp_niztech_youtube_videos`

## Usage

### Setup

**Admin:**

- Activate plugin in WP admin to run DB migrations (creates playlist/video tables).
- Settings → Niztech YouTube — enter YouTube V3 API key and save.

**Add Content:**

- Edit a post of type `video_series`:
- `Youtube URL` — paste a full YouTube playlist or video URL.
    - `Type` — choose `Playlist` or `Single Video` - Select this in the event that the URL pasted contains both video
      and playlist id.
    - `Use Youtube Featured Image` — when checked plugin will download the best available thumbnail and set it as post
      thumbnail.
    - Save post — plugin fetches and commits video(s) into the local DB.
    - In the metabox the admin sees thumbnails with checkboxes to hide/show individual videos. Changes are sent via AJAX
      to action `niztech_youtube_admin_hide_video` and updated in the `hidden` column on the `*_niztech_youtube_videos`
      table.

### Usage with themes and content

**Within a theme**

In your theme you can make calls to `class-niztech-youtube-client` to render content

```
    if (class_exists('Niztech_Youtube') ) {
      inlude_once WP_PLUGIN_DIR . '/niztech-youtube/class-niztech-youtube-client.php';
	}
```

**Short Code**

(Still under development) Use the shortcode to render visible videos:

```html
[ntyt_videos class="my-class" id="my-id" post="123"]
```

Parameters:

- `class` — optional CSS class to add
- `id` — optional container id
- `post` — optional post ID (defaults to current post)

---

All file paths referenced above use plugin root, e.g. `niztech-youtube.php`, `class-niztech-youtube.php`,
`views/config.php`.
