document.addEventListener('DOMContentLoaded', function() {
	const buttons = document.querySelectorAll('.bwu-onboarding-optin');

	buttons.forEach((button) => {
		button.addEventListener('click', function(e) {
			e.preventDefault();

			const notice = document.getElementById('backwpup_optin_notice');
			if (notice) {
				notice.style.display = 'none';
			}

			const optinValue = this.getAttribute('data-optin');
			fetch(ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'backwpup_notice_optin',
					value: optinValue,
					_ajax_nonce: bwuAnalyticsOptin._ajax_nonce
				})
			})
			.then(response => response.json())
			.then(data => {})
			.catch(error => {
				console.error('Error:', error);
			});
		});
	});
});