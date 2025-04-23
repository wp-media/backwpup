<?php
use BackWPup\Utils\BackWPupHelpers;

$jobs = BackWPup_Job::get_jobs();

BackWPupHelpers::component("form/button", [
  "identifier" => "backwpup-backup-now",
  "type" => "secondary",
  "label" => __("Backup Now", 'backwpup'),
  "icon_name" => "download",
  "icon_position" => "after",
  "trigger" => "open-modal",
  "display" => "backup-now",
  "disabled" => false,
  "class" => "whitespace-nowrap backwpup-button-backup",
]);
?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "link",
  "label" => __("Advanced settings", 'backwpup'),
  "trigger" => "open-sidebar",
  "display" => "advanced-settings",
  "class" => "max-md:hidden",
]);
?>

<button class="md:hidden js-backwpup-open-sidebar" data-content="advanced-settings">
  <?php BackWPupHelpers::component("icon", ["name" => "settings"]); ?>
</button>