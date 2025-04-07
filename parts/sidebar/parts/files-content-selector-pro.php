<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 */
?>
<div class="mt-4 flex flex-col gap-2">

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get($job_id, 'backuproot');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backuproot",
      "checked" => $checked,
      "label" => __("WordPress Core", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>

    <div class="mt-4 flex gap-4 items-center justify-between">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Exclude files", 'backwpup'),
        "trigger" => "open-modal",
        "display" => "exclude-files-core",
        "disabled" => !$checked,
	      "data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'modal/exclude-files-core',  ],

      ]);
      ?>
    </div>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get($job_id, 'backupplugins');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupplugins",
      "checked" => $checked,
      "label" => __("Plugins", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>

    <div class="mt-4 flex gap-4 items-center justify-between">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Exclude files", 'backwpup'),
        "trigger" => "open-modal",
        "display" => "exclude-files-plugins",
        "disabled" => !$checked,
	      "data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'modal/exclude-files-plugins',  ],
      ]);
      ?>
    </div>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get($job_id, 'backupthemes');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupthemes",
      "checked" => $checked,
      "label" => __("Themes", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>

    <div class="mt-4 flex gap-4 items-center justify-between">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Exclude files", 'backwpup'),
        "trigger" => "open-modal",
        "display" => "exclude-files-themes",
        "disabled" => !$checked,
	      "data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'modal/exclude-files-themes',  ],

      ]);
      ?>
    </div>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get($job_id, 'backupuploads');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupuploads",
      "checked" => $checked,
      "label" => __("Uploads", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>

    <div class="mt-4 flex gap-4 items-center justify-between">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Exclude files", 'backwpup'),
        "trigger" => "open-modal",
        "display" => "exclude-files-uploads",
        "disabled" => !$checked,
	      "data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'modal/exclude-files-uploads',  ],

      ]);
      ?>
    </div>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get($job_id, 'backupcontent');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupcontent",
      "checked" => $checked,
      "label" => __("Other in wp-content", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>

    <div class="mt-4 flex gap-4 items-center justify-between">
      <?php
      BackWPupHelpers::component("form/button", [
        "type" => "link",
        "label" => __("Exclude files", 'backwpup'),
        "trigger" => "open-modal",
        "display" => "exclude-files-wp-content",
        "disabled" => !$checked,
	      "data" => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'modal/exclude-files-wp-content',  ],

      ]);
      ?>
    </div>
  </div>
</div>