<?php
use BackWPup\Utils\BackWPupHelpers;
//Get the current network settings.
$authentication = get_site_option(
	'backwpup_cfg_authentication',
	[
		'method' => '',
		'basic_user' => '',
		'basic_password' => '',
		'user_id' => 0,
		'query_arg' => '',
	]
);
	BackWPupHelpers::component("form/text", [
	  "name" => "authentication_basic_user",
	  "label" => __("Basic Auth Username:", 'backwpup'),
	  "value" => $authentication['basic_user'],
	]);
?>
<br />
<?php
	BackWPupHelpers::component("form/text", [
	  "type" => "password",
	  "name" => "authentication_basic_password",
	  "label" => __("Basic Auth Password:", 'backwpup'),
	  "value" => $authentication['basic_password'],
	]);
?>