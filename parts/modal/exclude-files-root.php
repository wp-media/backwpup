<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Others in root", 'backwpup') . " - " . __("Exclusion Settings", 'backwpup'),
  'type' => 'modal'
]);
?>

<div class="flex-auto overflow-y-scroll flex flex-col gap-2 h-[312px]">

  <?php
  BackWPupHelpers::component("file-line", [
    "name" => "file",
    "icon" => "file",
    "included" => true,
  ]);
  ?>

  <?php
  BackWPupHelpers::component("file-line", [
    "name" => "file",
    "icon" => "file",
    "included" => true,
  ]);
  ?>
</div>

<footer>
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save", 'backwpup'),
    "full_width" => true,
  ]);
  ?>
</footer>