<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Advanced Settings", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="flex flex-col gap-2">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "settings",
    "label" => __("Jobs", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "full_width" => true,
    "trigger" => "open-sidebar",
    "display" => "settings-jobs",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "settings",
    "label" => __("Logs", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "full_width" => true,
    "trigger" => "open-sidebar",
    "display" => "settings-logs",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "settings",
    "label" => __("Network", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "full_width" => true,
    "trigger" => "open-sidebar",
    "display" => "settings-network",
  ]);
  ?>

  <?php 
    if (  \BackWPup::is_pro() ):
      BackWPupHelpers::component("form/button", [
        "type" => "settings",
        "label" => __("License", 'backwpup'),
        "icon_name" => "arrow-right",
        "icon_position" => "after",
        "full_width" => true,
        "trigger" => "open-sidebar",
        "display" => "settings-license",
      ]);
      BackWPupHelpers::component("form/button", [
        "type" => "settings",
        "label" => __("Encryption", 'backwpup'),
        "icon_name" => "arrow-right",
        "icon_position" => "after",
        "full_width" => true,
        "trigger" => "open-sidebar",
        "display" => "settings-encryption",
      ]);
    endif;
  ?>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "secondary",
  "label" => __("Close", 'backwpup'),
  "full_width" => true,
  "trigger" => "close-sidebar",
]);
?>