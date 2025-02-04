<?php
use BackWPup\Utils\BackWPupHelpers;

BackWPupHelpers::component("alerts/info", [
	"type" => "alert",
	"content" => __("Please complete the authentication to continue.", 'backwpup'),
	"content2" => __("To ensure the proper functioning of the plugin, it is essential to complete the authentication process before proceeding. Please make sure that all required steps have been followed. Once done, click the 'Authentication Complete' button to continue.", 'backwpup'),
]);
?>

<footer class="flex flex-col gap-2">
	<?php
	BackWPupHelpers::component("form/button", [
		"type" => "primary",
		"label" => __("Authentication Complete", 'backwpup'),
		"full_width" => true,
		"trigger" => "refresh-authentification",
	]);
	?>
</footer>