// Using Vanilla JavaScript (Fetch API)
document.addEventListener('DOMContentLoaded', function () {
	const youtubeUrlInput = document.querySelector('#niztech_youtube_url');
	const youtubeUrlInputValue = youtubeUrlInput.getAttribute('value');
	validatType(youtubeUrlInputValue);

	const checkmarks = document.querySelectorAll(
		'.niztech-youtube-thumbnails input[type="checkbox"]'
	);
	if (checkmarks.length > 0) {
		checkmarks.forEach((checkmark) => {
			checkmark.addEventListener('click', (evt) => {
				const isChecked = evt.target.checked;
				const postId = evt.target.name;
				const videoId = evt.target.value;

				const form = new FormData();
				form.append('nonce', myAjax.nonce);
				form.append('is_hidden', isChecked);
				form.append('post_id', postId);
				form.append('video_id', videoId);

				fetch(
					`${myAjax.ajaxurl}?action=niztech_youtube_admin_hide_video`,
					{
						body: form,
						method: 'POST',
						headers: {
							Accept: 'application/json',
						},
					}
				)
					.then((response) => response.json())
					.then((data) => {
						if (data.success) {
							console.log('Success:', data.data);
						} else {
							console.error('Error:', data.data);
						}
					})
					.catch((error) => {
						console.error('Fetch Error:', error);
					});
			});
		});
	}
});

document.addEventListener('DOMContentLoaded', () => {
	const youtubeUrlInput = document.querySelector('#niztech_youtube_url');
	youtubeUrlInput.addEventListener('blur', (evt) =>
		validatType(evt?.target?.value)
	);
});

/**
 * For the video_series post.php, upfated the UI so that the user can only select the available options
 * (Playlist, Single Video)
 * @param value
 */
function validatType(value) {
	const playlistRadio = document.querySelector(
		'#niztech_youtube_type_playlist'
	);
	const singleVideoRadio = document.querySelector(
		'#niztech_youtube_type_single_video'
	);
	const validPlaylist = !value
		? false
		: new RegExp(/list=([\w-]+?)&|list=([\w-]+?)$/, 'i').test(value);
	const validSingleVideo = !value
		? false
		: new RegExp(/v=([\w-]+?)&|v=([\w-]+?)$/, 'i').test(value);

	// What buttons should be disabled
	// should playlist be disabled
	if (!validPlaylist) {
		playlistRadio.setAttribute('disabled', 'disabled');
	} else {
		playlistRadio.removeAttribute('disabled');
	}
	// should single video be disabled
	if (!validSingleVideo) {
		singleVideoRadio.setAttribute('disabled', 'disabled');
	} else {
		singleVideoRadio.removeAttribute('disabled');
	}

	// What should be checked
	if (!validPlaylist && validSingleVideo) {
		singleVideoRadio.setAttribute('checked', 'checked');
	} else if (validPlaylist && !validSingleVideo) {
		playlistRadio.setAttribute('checked', 'checked');
	}
}
