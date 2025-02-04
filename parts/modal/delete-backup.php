<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Delete Backup", 'backwpup'),
  'type' => 'modal'
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "content" => __("Deleting a backup is permanent and cannot be undone.", 'backwpup'),
  "content2" => __("This means you will lose the ability to restore your site to the state captured in this backup.", 'backwpup'),
]);
?>

<footer class="flex flex-col gap-2">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Delete Backup", 'backwpup'),
    "full_width" => true,
    "trigger" => "open-url",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "secondary",
    "label" => __("Cancel", 'backwpup'),
    "full_width" => true,
    "trigger" => "close-modal",
  ]);
  ?>
</footer>