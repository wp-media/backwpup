<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("navigation/link", [
  "url" => "#",
  "newtab" => true,
  "font" => "medium",
  "content" => __("Back to Backups", 'backwpup'),
  "icon_position" => "before",
  "icon_name" => "arrow-left",
]);
?>

<?php
BackWPupHelpers::component("heading", [
  "level" => 1,
  "font" => "large",
  "class" => "mt-10 mb-6",
  "title" => __("What do you want to save in this backup?", 'backwpup'),
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "default",
  "font" => "xs",
  "content" => __("The settings configured on this page for a one-time backup do not alter the settings of your scheduled backups.<br />They apply to this backup only", 'backwpup'),
]);
?>


<?php BackWPupHelpers::component("selector-file-db"); ?>

<div class="my-10">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Backup will be stored on:", 'backwpup'),
    "class" => "mb-4",
  ]);
  ?>

  <?php BackWPupHelpers::component("storage-list-compact", ["storages" => ['DROPBOX', 'GDRIVE', 'GLACIER']]); ?>
</div>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Backup My Website", 'backwpup'),
]);
?>