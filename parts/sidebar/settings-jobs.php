<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Jobs Settings", 'backwpup'),
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

<?php
BackWPupHelpers::component("form/text", [
  "name" => "jobstepretry",
  "type" => "number",
  "label" => __("Maximum number of retries for job steps", 'backwpup'),
  "value" => get_site_option('backwpup_cfg_jobstepretry'),
  "min" => 0,
  "required" => true,
]);
?>

<?php
BackWPupHelpers::component("form/text", [
  "name" => "jobmaxexecutiontime",
  "type" => "number",
  "label" => __("Maximum number script execution time (in seconds)", 'backwpup'),
  "value" => get_site_option('backwpup_cfg_jobmaxexecutiontime'),
  "min" => 0,
  "required" => true,
  "tooltip" => __("Job will restart before hitting maximum execution time. Restarts will be disabled on CLI usage. if <strong>ALTERNATE_WP_CRON</strong> has been defined, WordPress Cron will be used for restarts, so it can take a while. 0 means no maximum.", 'backwpup'),
  "tooltip_pos" => "left",
]);
?>

<?php
BackWPupHelpers::component("form/text", [
  "name" => "jobrunauthkey",
  "type" => "text",
  "label" => __("Key to start a job externally with an URL", 'backwpup'),
  "value" => get_site_option('backwpup_cfg_jobrunauthkey'),
  "min" => 0,
  "required" => true,
  "tooltip" => __("Will be used to protect job starts from unauthorized person.", 'backwpup'),
  "tooltip_pos" => "left",
]);
?>

<?php
BackWPupHelpers::component("form/select", [
  "name" => "jobwaittimems",
  "label" => __("Reduce server load", 'backwpup'),
  "withEmpty" => false,
  "value" => (int)get_site_option('backwpup_cfg_jobwaittimems'),
  "options" => [
    "0" => "disabled",
    "10000" => "minimum",
    "30000" => "medium",
    "90000" => "maximum",
  ],
  "tooltip" => __("This adds short pauses to the process. Can be used to reduce the CPU load.", 'backwpup'),
  "tooltip_pos" => "center",
]);
?>

<?php
BackWPupHelpers::component("form/checkbox", [
  "name" => "jobdooutput",
  "checked" => (bool)get_site_option('backwpup_cfg_jobdooutput'),
  "label" => __("Enable an empty output on backup working", 'backwpup'),
  "tooltip" => __("Allow the backup to complete successfully even if the backup results in an empty output (i.e., no files or data are backed up)", 'backwpup'),
  "tooltip_pos" => "left",
]);
?>

<?php
BackWPupHelpers::component("form/checkbox", [
  "name" => "windows",
  "checked" => (bool)get_site_option('backwpup_cfg_windows'),
  "label" => __("Enable compatibility with IIS on Windows", 'backwpup'),
  "tooltip" => __("This ensures the backup will run smoothly on IIS without errors.", 'backwpup'),
  "tooltip_pos" => "left",
]);
?>

<?php
  BackWPupHelpers::component("navigation/link", [
    "type" => "secondary",
    "content" => __("Access to Jobs", 'backwpup'),
    "url" => network_admin_url('admin.php?page=backwpupjobs'),
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
