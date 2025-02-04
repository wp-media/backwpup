<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string $fileJobId Optional. The main plugin file. Default: null.
 *
 */

#Defaults
$fileJobId = $fileJobId ?? null;

$excludedFiles = BackWPup_Directory::get_folder_list_to_exclude('plugins',WP_PLUGIN_DIR, $fileJobId);


BackWPupHelpers::component("closable-heading", [
  'title' => __("Plugins", 'backwpup') . " - " . __("Exclusion Settings", 'backwpup'),
  'type' => 'modal'
]);
?>

<div class="flex-auto overflow-y-scroll flex flex-col gap-2 h-[312px]">
    <?php
    foreach ($excludedFiles as $file) {
        BackWPupHelpers::component("file-line", [
            "value" => $file['name'],
            "label" => $file['name'],
            "name" => "backuppluginsexcludedirs[]",
            "icon" => "folder",
            "included" => !$file['excluded'],
        ]);
    }
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