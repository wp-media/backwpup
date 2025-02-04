<?php
use BackWPup\Utils\BackWPupHelpers;
$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$token = BackWPup_Option::get($jobid, 'sugarrefreshtoken', false);

BackWPupHelpers::component("closable-heading", [
  'title' => __("Sugar Sync Settings", 'backwpup'),
  'type' => 'sidebar'
]);
?>

<?php if (isset($is_in_form) && false === $is_in_form) : ?>
  <p>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "label" => __("Back to Storages", 'backwpup'),
      "icon_name" => "arrow-left",
      "icon_position" => "before",
      "trigger" => "open-sidebar",
      "display" => "storages",
    ]);
    ?>
  </p>
<?php endif; ?>
	<div class="mt-2 text-base text-danger" id="sugarsync_authenticate_infos"></div>
<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-4 bg-grey-100" id="sugarsynclogin">
		<?php
		BackWPupHelpers::children("sidebar/sugar-sync-parts/api-connexion");
		?>
</div>

<div class="rounded-lg p-4 bg-grey-100" id="sugarsyncroot">
		<?php
		BackWPupHelpers::children("sidebar/sugar-sync-parts/root-folder");
		?>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Backup Settings", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "sugardir",
      "identifier" => "sugardir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'sugardir', "/folder")),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "sugarmaxbackups",
      "identifier" => "sugarmaxbackups",
      "type" => "number",
      "min" => 1,
      "label" => __("Max backups to retain", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'sugarmaxbackups', 3)),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "font" => "xs",
      "content" => __("When this limit is exceeded, the oldest backup will be deleted.", 'backwpup'),
    ]);
    ?>
  </div>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save & Test connection", 'backwpup'),
  "full_width" => true,
  "trigger" => "test-sugar-sync-storage",
  "data" => [
    "storage" => "sugar-sync",
  ],
]);
?>