<?php
use BackWPup\Utils\BackWPupHelpers;
$select_files = 'select-files';
if (BackWPup::is_pro()) {
  $select_files .= '-pro';
}
?>
<div class="flex-auto">

  <div class="flex items-center gap-4 border-b border-grey-200 py-6">
    <div class="shrink-0">
      <?php BackWPupHelpers::component("icon", ["name" => "wp", "size" => "xl"]); ?>
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
          "trigger" => "open-sidebar",
          "class" => "onboarding-advanced-files-setings",
          "display" => $select_files,
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
          "trigger" => "open-sidebar",
          "display" => "select-tables",
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