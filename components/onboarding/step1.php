<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("heading", [
  "level" => 2,
  "title" => __("What do you want to backup?", 'backwpup'),
]);
?>

<?php BackWPupHelpers::component("selector-file-db"); ?>

<footer class="mt-6 flex justify-end items-center gap-4">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save & Continue", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "trigger" => "onboarding-step-2",
  ]);
  ?>
</footer>