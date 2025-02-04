<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("form/button", [
  "type" => "secondary",
  "label" => __("Backup Now", 'backwpup'),
  "icon_name" => "download",
  "icon_position" => "after",
  "trigger" => "start-backup",
  "class" => "whitespace-nowrap",
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