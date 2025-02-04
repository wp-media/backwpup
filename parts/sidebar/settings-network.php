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
BackWPupHelpers::component("closable-heading", [
  'title' => __("Network Settings", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<p>
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "link",
    "label" => __("Back to Advanced Settings", 'backwpup'),
    "icon_name" => "arrow-left",
    "icon_position" => "before",
    "trigger" => "open-sidebar",
    "display" => "advanced-settings",
  ]);
  ?>
</p>

<?php
if (isset($is_in_form) && false === $is_in_form) {
    BackWPupHelpers::component("containers/form-start");
}
?>

<div>
  <p class="font-semibold text-base text-primary-darker">
    <?=__("Authentication for", 'backwpup') ?>
  </p>
  <p class="text-base text-primary-darker">
    <?=site_url('wp-cron.php') ?>
  </p>
</div>

<?php
BackWPupHelpers::component("form/select", [
  "name" => "authentication_method",
  "label" => __("Authentication method", 'backwpup'),
  "value" => $authentication['method'],
  "trigger" => "network-authentication-method",
  "options" => [
    "" => "",
    "basic" => "Basic auth",
    "user" => "Wordpress User",
    "query_arg" => "Query argument",
  ],
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "font" => "xs",
  "content" => __("If you protected your blog with HTTP basic authentication (.htaccess), or you use a plugin to secure wp-cron.php, then use the authentication methods above.", 'backwpup'),
]);
?>
<div id="network-authentications">
<?php
BackWPupHelpers::component("containers/grey-box", [
	"padding_size" => "large",
  "children" => "sidebar/parts/network-basic-authentication",
  "identifier" => "network-basic-authentication",
  "display" => false,
]);
?>

<?php
BackWPupHelpers::component("containers/grey-box", [
	"padding_size" => "large",
  "children" => "sidebar/parts/network-user-authentication",
  "identifier" => "network-user-authentication",
  "display" => false,
]);
?>

<?php
BackWPupHelpers::component("containers/grey-box", [
	"padding_size" => "large",
  "children" => "sidebar/parts/network-query-argument-authentication",
  "identifier" => "network-query_arg-authentication",
  "display" => false,
]);
?>
</div>

<?php
if (isset($is_in_form) && false === $is_in_form) {
    BackWPupHelpers::component("containers/form-end");
}
?>

<?php
BackWPupHelpers::component("form/button", [
	"type" => "primary",
	"label" => __("Save", 'backwpup'),
	"full_width" => true,
	"trigger" => "sidebar-submit-form",
]);
?>