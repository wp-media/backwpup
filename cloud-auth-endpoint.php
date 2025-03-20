<?php
/**
 * Template Name: Cloud Auth Endpoint
 * Description: A bare page for Cloud Auth endpoint response.
 */

header('Content-Type: text/html; charset=utf-8');
?>
	<!DOCTYPE html>
	<html lang="en">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>BackWpUP Auth Endpoint</title>
		<style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f9f9f9;
                color: #333;
            }
            .message {
                text-align: center;
            }
		</style>
	</head>
	<body>
	<div class="message">
		<h1>You can close this page.</h1>
		<p>This tab will close automatically in <span id="countdown">20</span> seconds.</p>
	</div>
	<script>
		let countdownElement = document.getElementById('countdown');
		let countdown = 20;

		const interval = setInterval(() => {
			countdown--;
			countdownElement.textContent = countdown;
			if (countdown <= 0) {
				clearInterval(interval);
				window.close();
			}
		}, 1000);
	</script>
	</body>
	</html>
<?php
exit;