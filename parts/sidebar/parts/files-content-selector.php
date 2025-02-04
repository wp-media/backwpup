<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<div class="mt-4 flex flex-col gap-2">

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backuproot');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backuproot",
      "checked" => $checked,
      "label" => __("WordPress Core", 'backwpup'),
      "value" => 1,
    ]);
    ?>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backupplugins');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupplugins",
      "checked" => $checked,
      "label" => __("Plugins", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backupthemes');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupthemes",
      "checked" => $checked,
      "label" => __("Themes", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backupuploads');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupuploads",
      "checked" => $checked,
      "label" => __("Uploads", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>
  </div>

  <div class="p-4 rounded-lg bg-white">
    <?php
    $checked = BackWPup_Option::get(get_site_option('backwpup_backup_files_job_id', false), 'backupcontent');
    BackWPupHelpers::component("form/checkbox", [
      "name" => "backupcontent",
      "checked" => $checked,
      "label" => __("Other in wp-content", 'backwpup'),
      "trigger" => "toggle-exclude",
        "value" => 1,
    ]);
    ?>
  </div>

</div>