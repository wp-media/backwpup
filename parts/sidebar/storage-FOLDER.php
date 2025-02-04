<?php
use BackWPup\Utils\BackWPupHelpers;
# Form Values
$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$dest_object = BackWPup::get_destination( "FOLDER" );
$values = $dest_object->option_defaults();
$backupdir = BackWPup_Option::get($jobid, 'backupdir', $values['backupdir']);
$maxbackups = BackWPup_Option::get($jobid, 'maxbackups', $values['maxbackups']);

//ToDo add the values from the database
BackWPupHelpers::component("closable-heading", [
  'title' => __("Folder Settings", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<?php if (isset($is_in_form) && false === $is_in_form) : ?>
  <p>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "label" => __("Back to Storages", 'backwpup'),
      "icon_name" => "arrow-left",
      "icon_position" => "before",
      "trigger" => "open-sidebar",
      "display" => "storages",
    ]);
    ?>
  </p>
<?php endif; ?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Backup Settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "backupdir",
      "identifier" => "backupdir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => $backupdir,
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "maxbackups",
      "identifier" => "maxbackups",
      "type" => "number",
      "min" => 1,
      "label" => __("Max backups to retain", 'backwpup'),
      "value" => $maxbackups,
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "font" => "xs",
      "content" => __("When this limit is exceeded, the oldest backup will be deleted.", 'backwpup'),
    ]);
    ?>
  </div>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save & Test connection", 'backwpup'),
  "full_width" => true,
  "trigger" => "test-folder-storage",
  "data" => [
    "storage" => "local",
  ],
]);
?>