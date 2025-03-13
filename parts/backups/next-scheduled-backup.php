<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("heading", [
  "level" => 1,
  "title" => __("Next Scheduled Backups", 'backwpup'),
  "class" => "max-md:justify-center"
]);

$file_job_id = get_site_option('backwpup_backup_files_job_id', false);
$database_job_id = get_site_option('backwpup_backup_database_job_id', false);

$file_cron_active = BackWPup_Option::get($file_job_id, 'activetype');
$database_cron_active = BackWPup_Option::get($database_job_id, 'activetype');

// Default values
$file_activate = false;
$database_activate = false;
$file_next_backup = __("No backup scheduled", 'backwpup');
$database_next_backup = __("No backup scheduled", 'backwpup');

$file_cron_type = BackWPup_Option::get($file_job_id, 'type');
$database_cron_type = BackWPup_Option::get($database_job_id, 'type');

if (!empty($file_cron_active) && (in_array($file_cron_type, [BackWPup_JobTypes::$type_job_both, BackWPup_JobTypes::$type_job_files], true))) {
    $file_activate = true;
    $cron_next_file = BackWPup_Cron::cron_next(BackWPup_Option::get($file_job_id, 'cron'));
    $file_next_backup = sprintf(
        __('%1$s at %2$s by WP-Cron', 'backwpup'),
        date_i18n(get_option('date_format'), $cron_next_file, true),
        date_i18n('H:i', $cron_next_file, true)
    );
}

if (!empty($database_cron_active) && (in_array($database_cron_type, [BackWPup_JobTypes::$type_job_both, BackWPup_JobTypes::$type_job_database], true))) {
    $database_activate = true;
    $cron_next_database = BackWPup_Cron::cron_next(BackWPup_Option::get($database_job_id, 'cron'));
    $database_next_backup = sprintf(
        __('%1$s at %2$s by WP-Cron', 'backwpup'),
        date_i18n(get_option('date_format'), $cron_next_database, true),
        date_i18n('H:i', $cron_next_database, true)
    );
}

$storage_destination = BackWPup_Option::get($file_job_id, 'destinations', []);

$select_files = 'select-files';
$frequency_files = 'frequency-files';
$frequency_database = 'frequency-tables';
if (BackWPup::is_pro()) {
  $select_files .= '-pro';
}
?>

<div class="mt-2 flex max-md:flex-col gap-4">

  <div class="flex-1 p-8 bg-white rounded-lg flex flex-col" id="backwpup-files-options">

    <div class="mb-2 flex items-center gap-2">
      <?php BackWPupHelpers::component("icon", ["name" => "wp", "size" => "large"]); ?>

      <div class="flex-auto">
        <?php
        BackWPupHelpers::component("heading", [
          "level" => 2,
          "title" => __("Files", 'backwpup'),
        ]);
        ?>
      </div>

      <?php
      BackWPupHelpers::component("form/toggle", [
        "name" => "next_backup_files",
        "trigger" => "toggle-files",
        "checked" => $file_activate,
				"data"		=> ['job-id' => $file_job_id],
      ]);
      ?>
    </div>

    <div class="mt-2 mb-4 flex-auto">
      <p class="text-base label-scheduled"><?php echo $file_next_backup; ?></p>
    </div>

    <p class="flex items-center gap-4">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("View settings", 'backwpup'),
        "trigger" => "open-sidebar",
        "display" => $frequency_files,
        "disabled" => !$file_activate,
      ]);
      ?>

      <span class="h-5 w-0 border-r border-primary-darker"></span>

      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Select files", 'backwpup'),
        "trigger" => "open-sidebar",
        "display" => $select_files,
        "disabled" => !$file_activate,
      ]);
      ?>
    </p>
  </div>

  <div class="flex-1 p-8 bg-white rounded-lg flex flex-col" id="backwpup-database-options">
    <div class="mb-2 flex items-center gap-2">
      <?php BackWPupHelpers::component("icon", ["name" => "database", "size" => "large"]); ?>

      <div class="flex-auto">
        <?php
        BackWPupHelpers::component("heading", [
          "level" => 2,
          "title" => __("Database", 'backwpup'),
        ]);
        ?>
      </div>

      <?php
      BackWPupHelpers::component("form/toggle", [
        "name" => "next_backup_database",
        "trigger" => "toggle-database",
        "checked" => $database_activate,
        "data"		=> ['job-id' => $database_job_id],
      ]);
      ?>
    </div>

    <div class="mt-2 mb-4 flex-auto">
      <p class="text-base label-scheduled"><?= $database_next_backup; ?></p>
    </div>

    <p class="flex items-center gap-4">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("View settings", 'backwpup'),
        "trigger" => "open-sidebar",
        "display" => $frequency_database,
      ]);
      ?>

      <span class="h-5 w-0 border-r border-primary-darker"></span>

      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Select tables", 'backwpup'),
        "trigger" => "open-sidebar",
        "display" => "select-tables",
      ]);
      ?>
    </p>
  </div>

  <div class="flex-1 p-8 bg-white rounded-lg flex flex-col">
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 2,
      "title" => __("Backup will be stored on:", 'backwpup'),
    ]);
    ?>

    <div class="mt-2 mb-4 flex-auto" id="backwpup-storage-list-compact-container">
      <?php BackWPupHelpers::component("storage-list-compact", ["storages" => $storage_destination]); ?>
    </div>

    <p class="flex items-center gap-4">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("View settings", 'backwpup'),
        "trigger" => "open-sidebar",
        "display" => "storages",
      ]);
      ?>
    </p>
  </div>
</div>