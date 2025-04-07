<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Backup Now", 'backwpup'),
  'type' => 'modal'
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "info",
  "content" => __("Backup all your files and database in one click. <br />Your backup will be stored on your website’s server and you will be able to download it on your computer.", 'backwpup'),
]);
?>

<footer class="flex flex-col gap-2">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Start", 'backwpup'),
    "full_width" => true,
    "trigger" => "start-backup-now",
    "class" => "backwpup-button-backup",
  ]);
  ?>
</footer>