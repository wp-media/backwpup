<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $first_job_id ID of the first job we are retrieving the frequency settings for.
 * @var int $second_job_id ID of the second job we are retrieving the frequency settings for.
 */

BackWPupHelpers::component("heading", [
  "level" => 2,
  "title" => __("When to automatically create your backup?", 'backwpup') . "<sup>*</sup>",
]);
?>

<div class="flex-auto">
  <div class="flex items-center gap-4 border-b border-grey-200 py-6 test-files">
    <div class="shrink-0">
      <?php BackWPupHelpers::component("icon", ["name" => "file-alt", "size" => "xl"]); ?>
    </div>
    <div class="flex-auto">
      <?php
      BackWPupHelpers::component("heading-desc", [
        "title" => __("Files backup scheduled", 'backwpup'),
      ]);
      ?>
      <p>
        <?php
	        BackWPupHelpers::component("form/button", [
		        "type" => "link",
		        "label" => __("Advanced settings", 'backwpup'),
		        "trigger" => "load-and-open-sidebar",
		        "display" => 'frequency',
		        "data"    => [ 'job-id' => $first_job_id, 'block-type' => 'children', 'block-name' => 'sidebar/frequency',  ],
	        ]);
        ?>
      </p>
    </div>

    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "job_".$first_job_id."_frequency",
        "label" => __("Frequency", 'backwpup'),
      "class" => "onboarding-files-frequency",
      "value" => "monthly",
      "trigger" => "onboarding-files-frequency",
      "options" => [
        'hourly' => __( 'Hourly', 'backwpup' ),
        "daily" => __("Daily", 'backwpup'),
        "weekly" => __("Weekly", 'backwpup'),
        "monthly" => __("Monthly", 'backwpup'),
      ],
    ]);
    ?>
  </div>

  <div class="flex items-center gap-4 py-6">
    <div class="shrink-0">
      <?php BackWPupHelpers::component("icon", ["name" => "database", "size" => "xl"]); ?>
    </div>
    <div class="flex-auto">
      <?php
      BackWPupHelpers::component("heading-desc", [
        "title" => __("Database backup scheduled", 'backwpup'),
      ]);
      ?>
      <p>
        <?php
        BackWPupHelpers::component("form/button", [
	        "type" => "link",
	        "label" => __("Advanced settings", 'backwpup'),
	        "trigger" => "load-and-open-sidebar",
	        "display" => 'frequency',
	        "data"    => [ 'job-id' => $second_job_id, 'block-type' => 'children', 'block-name' => 'sidebar/frequency',  ],
        ]);
        ?>
      </p>
    </div>
    <?php
    BackWPupHelpers::component("form/select", [
	    "name" => "job_".$second_job_id."_frequency",
      "label" => __("Frequency", 'backwpup'),
      "value" => "monthly",
      "class" => "onboarding-database-frequency",
      "trigger" => "onboarding-database-frequency",
      "options" => [
        'hourly' => __( 'Hourly', 'backwpup' ),
        "daily" => __("Daily", 'backwpup'),
        "weekly" => __("Weekly", 'backwpup'),
        "monthly" => __("Monthly", 'backwpup'),
      ],
    ]);
    ?>
  </div>
</div>

<div>
  <p class="text-xs"><sup>*</sup><?php _e("The first backup will be created right after saving the options", 'backwpup'); ?></p>
</div>

<footer class="mt-6 flex justify-between items-center gap-4">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "secondary",
    "label" => __("Back to What", 'backwpup'),
    "icon_name" => "arrow-left",
    "icon_position" => "before",
    "trigger" => "onboarding-step-1",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save & Continue", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "trigger" => "onboarding-step-3",
    'button_type' => 'button',
  ]);
  ?>
</footer>