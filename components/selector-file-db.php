<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $first_job_id ID of the first job we are retrieving the frequency settings for.
 * @var int $second_job_id ID of the second job we are retrieving the frequency settings for.
 */

?>
<div class="flex-auto">

  <div class="flex items-center gap-4 border-b border-grey-200 py-6">
    <div class="shrink-0">
      <?php BackWPupHelpers::component("icon", ["name" => "file-alt", "size" => "xl"]); ?>
    </div>
    <div class="flex-auto">
      <?php
      BackWPupHelpers::component("heading-desc", [
        "title" => __("Files", 'backwpup'),
        "description" => __("Include your WordPress files in the backup", 'backwpup'),
      ]);
      ?>
      <p>
        <?php
        BackWPupHelpers::component("form/button", [
			"type" => "link",
			"label" => __("Advanced settings", 'backwpup'),
			"trigger" => "load-and-open-sidebar",
			"class" => "onboarding-advanced-files-settings",
			"display" => "select-files",
	        "data" => [ 'job-id' => $first_job_id, 'block-type' => 'children', 'block-name' => 'sidebar/select-files',  ],
        ]);
        ?>
      </p>
    </div>
    <div>
      <?php
      BackWPupHelpers::component("form/toggle", [
        "name" => "backup_files",
        "checked" => true,
        "trigger" => "onboarding-toggle-files",
      ]);
      ?>
    </div>
      <?php
      BackWPupHelpers::component("form/hidden", [
        "name" => "backupexcludethumbs",
        "value" => false,
      ]);
      BackWPupHelpers::component("form/hidden", [
        "name" => "backupspecialfiles",
        "value" => true,
      ]);
      BackWPupHelpers::component("form/hidden", [
        "name" => "backupsyncnodelete",
        "value" => false,
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
        "title" => __("Database", 'backwpup'),
        "description" => __("Include your WordPress database in the backup", 'backwpup'),
      ]);
      ?>
      <p>
        <?php
        BackWPupHelpers::component("form/button", [
			"type" => "link",
			"label" => __("Advanced settings", 'backwpup'),
			"class" => "onboarding-advanced-database-setings",
			"trigger" => "load-and-open-sidebar",
			"display" => "select-tables",
	        "data" => [ 'job-id' => $second_job_id, 'block-type' => 'children', 'block-name' => 'sidebar/select-tables',  ],
        ]);
        ?>
      </p>
    </div>
    <div>
      <?php
      BackWPupHelpers::component("form/toggle", [
        "name" => "backup_database",
        "trigger" => "onboarding-toggle-database",
        "checked" => true,
      ]);
      ?>
    </div>
  </div>
</div>