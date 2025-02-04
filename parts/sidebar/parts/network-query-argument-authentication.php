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
	"name" => "authentication_query_arg",
	"label" => __("Query arg key=value:", 'backwpup'),
	"value" => $authentication['query_arg'],
]);
?>