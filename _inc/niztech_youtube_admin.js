// Using Vanilla JavaScript (Fetch API)
document.addEventListener('DOMContentLoaded', function () {
	const checkmarks = document.querySelectorAll('.niztech-youtube-thumbnails input[type="checkbox"]');
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

				fetch(`${myAjax.ajaxurl}?action=niztech_youtube_admin_hide_video`, {
					body: form,
					method: 'POST',
					headers: {
						Accept: 'application/json'
					},
				})
					.then(response => response.json())
					.then(data => {
						if (data.success) {
							console.log('Success:', data.data);
						} else {
							console.error('Error:', data.data);
						}
					})
					.catch(error => {
						console.error('Fetch Error:', error);
					});
			});
		});
	}
});
