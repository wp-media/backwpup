<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Restore Backup", 'backwpup'),
  'type' => 'modal'
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "content" => __("Restoring your site from a backup reverts it to its state at that time, losing any changes made afterwards.", 'backwpup'),
  "content2" => __("Useful for undoing changes or fixing problems.", 'backwpup'),
]);
?>

<footer class="flex flex-col gap-2">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Start Restoration", 'backwpup'),
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