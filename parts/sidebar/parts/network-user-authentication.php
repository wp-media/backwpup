<?php
use BackWPup\Utils\BackWPupHelpers;
$users = get_users(
	[
		'role' => 'administrator',
		'number' => 99,
		'orderby' => 'display_name',
	]
);
$users_list = [];
foreach ( $users as $user ) {
	$users_list[ $user->ID ] = $user->display_name;
}
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
BackWPupHelpers::component("form/select", [
	"name" => "authentication_user_id",
	"label" => __("Select WordPress User", 'backwpup'),
	"withEmpty" => true,
	"value" => $authentication['user_id'],
	"options" => $users_list,
]);
