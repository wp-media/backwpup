<?php
use BackWPup\Utils\BackWPupHelpers;
use Inpsyde\BackWPup\MsAzureDestinationConfiguration;
$job_id = $job_id ?? null;
$msazure = BackWPup::get_destination("msazure");
$msazure->edit_inline_js();
BackWPupHelpers::component("closable-heading", [
  'title' => __("Microsoft Azure Settings", 'backwpup'),
  'type' => 'sidebar'
]);
if (null === $job_id) {
  $msazuredir = trailingslashit(sanitize_title_with_dashes(get_bloginfo('name')));
  $msazuremaxbackups = 15;
} else {
  $msazuredir = esc_attr(BackWPup_Option::get(
    $job_id,
    "msazuredir",
    trailingslashit(sanitize_title_with_dashes(get_bloginfo('name')))));
  $msazuremaxbackups = esc_attr(BackWPup_Option::get(
    $job_id,
    "msazuremaxbackups",
    3));
}
?>

<?php if (isset($is_in_form) && ( false === $is_in_form || 'false' === $is_in_form )) : ?>
  <p>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "label" => __("Back to Storages", 'backwpup'),
      "icon_name" => "arrow-left",
      "icon_position" => "before",
      "trigger" => "load-and-open-sidebar",
      "display" => "storages",
      "data"		=> ['job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/storages',  ]
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
      "value" => esc_attr(BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_ACCNAME)),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "msazurekey",
      "identifier" => "msazurekey",
      "label" => __("Access key", 'backwpup'),
      "value" => esc_attr(BackWPup_Encryption::decrypt(BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_KEY))),
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
    <?php if (BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_ACCNAME) && BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_KEY)) : ?>
      <?php
        $msazure->edit_ajax([
          MsAzureDestinationConfiguration::MSAZURE_ACCNAME => BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_ACCNAME),
          MsAzureDestinationConfiguration::MSAZURE_KEY => BackWPup_Encryption::decrypt(BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_KEY)),
          'msazureselected' => BackWPup_Option::get($job_id, MsAzureDestinationConfiguration::MSAZURE_CONTAINER),
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
      "value" => $msazuredir,
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
      "value" => $msazuremaxbackups,
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
  "trigger" => "test-MSAZURE-storage",
  "data" => [
    "storage" => "microsoft-azure",
    "job-id" => $job_id,
  ],
]);
?>