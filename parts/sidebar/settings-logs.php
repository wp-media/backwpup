<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Logs Settings", 'backwpup'),
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

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Logs settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>
  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "logfolder",
      "label" => __("Log file folder", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_logfolder'),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "maxlogs",
      "type" => "number",
      "trigger" => "intonly",
      "label" => __("Maximum log files in folder", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_maxlogs'),
      "min" => 0,
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "loglevel",
      "label" => __("Logging level", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_loglevel'),
      "options" => [
        "normal_translated" => "Normal (translated)",
        "normal" => "Normal",
        "debug_translated" => "Debug (translated)",
        "debug" => "Debug (not translated)",
      ],
    ]);
    ?>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Log notification", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "mailaddresslog",
      "type" => "email",
      "label" => __("Send log to email address", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_mailaddresslog'),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "mailaddresssenderlog",
      "type" => "email",
      "label" => __("Email from field", 'backwpup'),
      "value" => get_site_option('backwpup_cfg_mailaddresssenderlog'),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "idmailerroronly",
      "label" => __("Send email with log only when errors occur during job execution", 'backwpup'),
      "checked" => (bool)get_site_option('backwpup_cfg_idmailerroronly'),
    ]);
    ?>
  </div>
</div>

<?php
  BackWPupHelpers::component("navigation/link", [
    "type" => "secondary",
    "content" => __("Access to Logs", 'backwpup'),
    "url" => network_admin_url('admin.php?page=backwpuplogs'),
    "full_width" => true,
  ]);
?>

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