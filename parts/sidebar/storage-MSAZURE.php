<?php
use BackWPup\Utils\BackWPupHelpers;
use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$msazure = BackWPup::get_destination("msazure");
$msazure->edit_inline_js();
BackWPupHelpers::component("closable-heading", [
  'title' => __("Microsoft Azure Settings", 'backwpup'),
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

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "font" => "small",
    "title" => __("MS Azure Access Keys", 'backwpup'),
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "msazureaccname",
      "identifier" => "msazureaccname",
      "label" => __("Account name", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME)),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "msazurekey",
      "identifier" => "msazurekey",
      "label" => __("Access key", 'backwpup'),
      "value" => esc_attr(BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY))),
      "required" => true,
    ]);
    ?>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Blob Container", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>
  <div id="msazureBucketContainer">
    <?php if (BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME) && BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)) : ?>
      <?php
        $msazure->edit_ajax([
          MsAzureDestinationConfiguration::MSAZURE_ACCNAME => BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_ACCNAME),
          MsAzureDestinationConfiguration::MSAZURE_KEY => BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_KEY)),
          'msazureselected' => BackWPup_Option::get($jobid, MsAzureDestinationConfiguration::MSAZURE_CONTAINER),
        ]);
      ?>
    <?php else : ?>
      <?php
      BackWPupHelpers::component("alerts/info", [
        "type" => "alert",
        "font" => "xs",
        "content" => __("Please enter your Azure Access Keys", 'backwpup'),
      ]);
      ?>
    <?php endif; ?>
  </div>
  <p class="my-2 text-center text-sm"><?php _e("OR", 'backwpup'); ?></p>

  <?php
  BackWPupHelpers::component("form/text", [
    "name" => "newmsazurecontainer",
    "identifier" => "newmsazurecontainer",
    "label" => __("Create a new container", 'backwpup'),
    "value" => "",
	  "tooltip" => __('The container name must be alphanumeric characters only and in lowercase.', 'backwpup'),
    "required" => false,
  ]);
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
      "name" => "msazuredir",
      "identifier" => "msazuredir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get(
        $jobid,
        "msazuredir",
        "/folder")),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "msazuremaxbackups",
      "identifier" => "msazuremaxbackups",
      "type" => "number",
      "min" => 1,
      "label" => __("Max backups to retain", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get(
        $jobid,
        "msazuremaxbackups",
        3)),
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
  "trigger" => "test-msazure-storage",
  "data" => [
    "storage" => "microsoft-azure",
  ],
]);
?>