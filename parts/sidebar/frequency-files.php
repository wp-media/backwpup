<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Files Scheduled Settings", 'backwpup'),
  'type' => 'sidebar'
]);
$files_job_id = get_site_option('backwpup_backup_files_job_id', false);
$files_job_cron = BackWPup_Option::get($files_job_id, 'cron');

?>

<?php BackWPupHelpers::component("containers/scrollable-start", ["gap_size" => "small"]); ?>

<?php
try {
  $current = BackWPup_Cron::parse_cron_expression($files_job_cron);
} catch (Exception $e) {
  BackWPupHelpers::component("alerts/error", [
    "type" => "info",
    "font" => "small",
    "content" => __("Current cron expression is not supported by this UI.", 'backwpup'). ' ' . esc_html($files_job_cron),
  ]);
  $current = [
    'frequency' => '',
    'start_time' => '00:00',
  ];
}
?>

<?php
BackWPupHelpers::component("form/select", [
  "name" => "frequency",
  "label" => __("Frequency", 'backwpup'),
  "trigger" => "frequency-files",
  "value" => $current['frequency'], // daily, weekly, monthly   
  "options" => [
    "daily" => __("Daily", 'backwpup'),
    "weekly" => __("Weekly", 'backwpup'),
    "monthly" => __("Monthly", 'backwpup'),
  ],
]);
?>

<div class="js-backwpup-frequency-file-show-if-monthly">
  <?php
  BackWPupHelpers::component("form/select", [
    "name" => "day_of_month",
    "label" => __("Start day", 'backwpup'),
    "options" => [
      "first-day" => __("1st day of the month", 'backwpup'),
      "first-monday" => __("1st Monday of the month", 'backwpup'),
      "first-sunday" => __("1st Sunday of the month", 'backwpup'),
    ],
  ]);
  ?>
</div>

<div class="js-backwpup-frequency-file-show-if-weekly">
  <?php
  BackWPupHelpers::component("form/select", [
    "name" => "day_of_week",
    "label" => __("Start day", 'backwpup'),
    "options" => [
      "monday"    => __("Monday", 'backwpup'),
      "tuesday"   => __("Tuesday", 'backwpup'),
      "wednesday" => __("Wednesday", 'backwpup'),
      "thursday"  => __("Thursday", 'backwpup'),
      "friday"    => __("Friday", 'backwpup'),
      "saturday"  => __("Saturday", 'backwpup'),
      "sunday"    => __("Sunday", 'backwpup'),
    ],
  ]);
  ?>
</div>

<?php
BackWPupHelpers::component("form/text", [
  "type" => "time",
  "name" => "start_time",
  "label" => __("Start time", 'backwpup'),
  "value" => $current['start_time'],
  "required" => true,
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
  "full_width" => true,
  "class" => "mt-4 save_files_settings",
  "identifier" => 'save_files_settings'
]);
?>