<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Tables Scheduled Settings", 'backwpup'),
  'type' => 'sidebar'
]);
$database_job_id = get_site_option('backwpup_backup_database_job_id', false);
$database_job_cron = BackWPup_Option::get($database_job_id, 'cron');

?>

<?php BackWPupHelpers::component("containers/scrollable-start", ["gap_size" => "small"]); ?>

<?php
try {
  $current = BackWPup_Cron::parse_cron_expression($database_job_cron);
} catch (Exception $e) {
  BackWPupHelpers::component("alerts/error", [
    "type" => "info",
    "font" => "small",
    "content" => __("Current cron expression is not supported by this UI.", 'backwpup'). ' ' . esc_html($files_job_cron),
  ]);
  $current = [
    'frequency' => '',
  ];
}
?>

<?php
BackWPupHelpers::component("form/select", [
  "name" => "frequency",
  "label" => __("Frequency", 'backwpup'),
  "value" => $current['frequency'],
  "trigger" => "frequency-tables",
  "options" => [
    "daily" => __("Daily", 'backwpup'),
    "weekly" => __("Weekly", 'backwpup'),
    "monthly" => __("Monthly", 'backwpup'),
  ],
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "font" => "small",
  "content" => __("Making a copy of your website can slow down your site a bit. We recommend doing this at night to avoid any inconvenience.", 'backwpup'),
]);
?>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save settings", 'backwpup'),
  "identifier" => "save_database_settings",
  "full_width" => true,
  "class" => "mt-4 save_database_settings",
]);
?>