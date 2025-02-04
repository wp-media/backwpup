<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Select Files", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-6 bg-grey-100">
  <?php
  BackWPupHelpers::component("containers/accordion", [
    "title" => __("Content Selector", 'backwpup'),
    "open" => true,
    "children" => "sidebar/parts/files-content-selector",
  ]);
  ?>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "pro",
  "font" => "small",
  "content" => __("You can exclude files and folders in each section using the PRO Version.", 'backwpup'),
  "children" => "alerts/pro-more-link",
]);
?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save settings", 'backwpup'),
  "full_width" => true,
  "trigger" => "close-sidebar",
  "identifier" => "file-exclusions-submit",
  "class" => "file-exclusions-submit",
]);
?>