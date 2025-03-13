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
    'hourly_start_time'  => 0,
    'monthly_start_day' => "",
    'weekly_start_day' => "",
  ];
}
?>

<?php
BackWPupHelpers::component("form/select", [
  "name" => "frequency",
  "label" => __("Frequency", 'backwpup'),
  "trigger" => "frequency-files",
  "value" => $current['frequency'], // hourly, daily, weekly, monthly   
  "options" => [
    'hourly' => __('Hourly', 'backwpup'),
    "daily" => __("Daily", 'backwpup'),
    "weekly" => __("Weekly", 'backwpup'),
    "monthly" => __("Monthly", 'backwpup'),
  ],
]);
?>

<div class="js-backwpup-frequency-file-show-if-hourly">
  <?php
  BackWPupHelpers::component( 'form/select', [
    'name' => 'hourly_start_time',
    'label' => __( 'Minutes', 'backwpup' ),
    'value' => $current['hourly_start_time'],
    'options' => [
      '0' => 0,
      '5' => 5,
      '10' => 10,
      '15' => 15,
      '20' => 20,
      '25' => 25,
      '30' => 30,
      '35' => 35,
      '40' => 40,
      '45' => 45,
      '50' => 50,
      '55' => 55,
    ],
  ]);
  ?>
</div>

<div class="js-backwpup-frequency-file-show-if-monthly">
  <?php
  BackWPupHelpers::component("form/select", [
    "name" => "day_of_month",
    "label" => __("Start day", 'backwpup'),
    "value" => $current['monthly_start_day'],
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
    "value" => $current['weekly_start_day'],
    "options" => [
      "1"    => __("Monday", 'backwpup'),
      "2"   => __("Tuesday", 'backwpup'),
      "3" => __("Wednesday", 'backwpup'),
      "4"  => __("Thursday", 'backwpup'),
      "5"    => __("Friday", 'backwpup'),
      "6"  => __("Saturday", 'backwpup'),
      "0"    => __("Sunday", 'backwpup'),
    ],
  ]);
  ?>
</div>

<div class="js-backwpup-frequency-file-hide-if-hourly">
<?php
BackWPupHelpers::component("form/text", [
  "type" => "time",
  "name" => "start_time",
  "label" => __("Start time", 'backwpup'),
  "value" => $current['start_time'],
  "required" => true,
]);
?>
</div>

<div class="js-backwpup-frequency-file-hide-if-hourly">
<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "font" => "small",
  "content" => __("Making a copy of your website can slow down your site a bit. We recommend doing this at night to avoid any inconvenience.", 'backwpup'),
]);
?>
</div>

<div class="js-backwpup-frequency-file-show-if-hourly">
<?php
BackWPupHelpers::component( 'alerts/info', [
  'type'    => 'alert',
  'font'    => 'small',
  'content' => __( 'Enable "Reduced server load" in “Advanced Settings > Jobs” to reduce website load and keep your site running smoothly during hourly backups.', 'backwpup' ),
]);
?>
</div>

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