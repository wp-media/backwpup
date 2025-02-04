<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Dropbox Settings", 'backwpup'),
  'type' => 'sidebar'
]);
$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$dropbox = new BackWPup_Destination_Dropbox_API('dropbox');
$dropbox_auth_url = $dropbox->oAuthAuthorize();
$dropbox = new BackWPup_Destination_Dropbox_API('sandbox');
$sandbox_auth_url = $dropbox->oAuthAuthorize();

$dropboxtoken = BackWPup_Option::get($jobid, 'dropboxtoken', []);
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

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-4 bg-grey-100" id="drobox_authenticate_infos">
  <?php
		BackWPupHelpers::children("sidebar/dropbox-parts/api-connexion");
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
      "name" => "dropboxdir",
      "identifier" => "dropboxdir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'dropboxdir','/folder')),
      "required" => true,
    ]);
    ?>

    <p class="px-2 font-light text-xs">
      <?php _e("Specify a subfolder where your backup archives will be stored. If you use the App option from above, this folder will be created inside of Apps/BackWPup. Otherwise it will be created at the root of your Dropbox. Already exisiting folders with the same name will not be overriden.", 'backwpup'); ?>
    </p>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "dropboxmaxbackups",
      "identifier" => "dropboxmaxbackups",
      "type" => "number",
      "min" => 1,
      "label" => __("Max backups to retain", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'dropboxmaxbackups', 15)),
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
  "trigger" => "test-dropbox-storage",
]);
?>