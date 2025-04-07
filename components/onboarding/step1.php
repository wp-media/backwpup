<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $first_job_id ID of the first job we are retrieving the frequency settings for.
 * @var int $second_job_id ID of the second job we are retrieving the frequency settings for.
 */
BackWPupHelpers::component("heading", [
  "level" => 2,
  "title" => __("What do you want to backup?", 'backwpup'),
]);
?>

<?php BackWPupHelpers::component("selector-file-db", ['first_job_id' => $first_job_id, 'second_job_id' => $second_job_id]); ?>

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