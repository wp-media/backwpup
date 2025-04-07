<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string $job_id Optional. The main plugin file. Default: null.
 *
 */

if ( ! isset($job_id) ) {
	return;
}
$excludedFiles = BackWPup_Directory::get_folder_list_to_exclude('uploads',BackWPup_File::get_upload_dir(), $job_id);

BackWPupHelpers::component("closable-heading", [
  'title' => __("Uploads", 'backwpup') . " - " . __("Exclusion Settings", 'backwpup'),
  'type' => 'modal'
]);
?>

<div class="flex-auto overflow-y-scroll flex flex-col gap-2 h-[312px]">

    <?php
    foreach ($excludedFiles as $file) {
        BackWPupHelpers::component("file-line", [
            "value" => $file['name'],
            "label" => $file['name'],
            "name" => "backupuploadsexcludedirs[]",
            "icon" => "folder",
            "included" => !$file['excluded'],
        ]);
    }
    ?>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("form/toggle", [
    "name" => "backupexcludethumbs",
    "checked" => BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backupexcludethumbs'),
    "label" => __("Exclude thumbnails from the site's uploads folder.", 'backwpup'),
  ]);
  ?>
</div>

<footer>
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save", 'backwpup'),
    "full_width" => true,
    "trigger" => "close-modal",
  ]);
  ?>
</footer>