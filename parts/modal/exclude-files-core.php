<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var string $fileJobId Optional. The main plugin file. Default: null.
 *
 */

#Defaults
$fileJobId = $fileJobId ?? null;

# Get the folder path
$abs_folder_up = BackWPup_Option::get($fileJobId, 'backupabsfolderup');
$abs_path = realpath(BackWPup_Path_Fixer::fix_path(ABSPATH));
if ($abs_folder_up) {
    $abs_path = dirname($abs_path);
}

$excludedFiles = BackWPup_Directory::get_folder_list_to_exclude("core",$abs_path, $fileJobId);

BackWPupHelpers::component("closable-heading", [
  'title' => __("WordPress Core", 'backwpup') . " - " . __("Exclusion Settings", 'backwpup'),
  'type' => 'modal'
]);
?>

<div class="flex-auto overflow-y-scroll flex flex-col gap-2 h-[312px]">
    <?php
    foreach ($excludedFiles as $file) {
        BackWPupHelpers::component("file-line", [
            "value" => $file['name'],
            "label" => $file['name'],
            "name" => "backuprootexcludedirs[]",
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