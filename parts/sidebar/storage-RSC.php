<?php
use BackWPup\Utils\BackWPupHelpers;
$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$regions = [
  "DFW" => __('Dallas (DFW)', 'backwpup'),
  "ORD" => __('Chicago (ORD)', 'backwpup'),
  "SYD" => __('Sydney (SYD)', 'backwpup'),
  "LON" => __('London (LON)', 'backwpup'),
  "IAD" => __('Northern Virginia (IAD)', 'backwpup'),
  "HKG" => __('Hong Kong (HKG)', 'backwpup'),
];
$rsc = BackWPup::get_destination("rsc");
$rsc->edit_inline_js();

BackWPupHelpers::component("closable-heading", [
  'title' => __("Rackspace Cloud Settings", 'backwpup'),
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
    "title" => __("Rackspace Cloud Keys", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "rscusername",
      "identifier" => "rscusername",
      "label" => __("Username", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'rscusername')),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "rscapikey",
      "identifier" => "rscapikey",
      "label" => __("API Key", 'backwpup'),
      "value" => esc_attr(BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, 'rscapikey'))),
      "required" => true,
    ]);
    ?>
  </div>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Select Region", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "rackspace_cloud_region",
      "identifier" => "rackspace_cloud_region",
      "label" => __("Rackspace Cloud Files Region", 'backwpup'),
      "value" => BackWPup_Option::get($jobid, 'rscregion', ''),
      "options" => $regions,
    ]);
    ?>

    <div id="rscbucketContainer">
      <?php
        $rsc->edit_ajax([
          'rscusername' => BackWPup_Option::get($jobid, 'rscusername'),
          'rscregion' => BackWPup_Option::get($jobid, 'rscregion'),
          'rscapikey' => BackWPup_Encryption::decrypt(BackWPup_Option::get($jobid, 'rscapikey')),
          'rscselected' => BackWPup_Option::get($jobid, 'rsccontainer'),
        ]);
      ?>
    </div>

    <p class="my-2 text-center text-sm"><?php _e("OR", 'backwpup'); ?></p>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "newrsccontainer",
      "identifier" => "newrsccontainer",
      "label" => __("Create a new container", 'backwpup'),
      "value" => "",
      "tooltip" => __('The bucket name must be alphanumeric characters only and in lowercase.', 'backwpup'),
      "required" => false,
    ]);
    ?>

  </div>
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
      "name" => "rscdir",
      "identifier" => "rscdir",
      "label" => __("Folder to store files in", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'rscdir',"/folder")),
      "required" => true,
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "rscmaxbackups",
      "name" => "rscmaxbackups",
      "type" => "number",
      "min" => 1,
      "label" => __("Max backups to retain", 'backwpup'),
      "value" => esc_attr(BackWPup_Option::get($jobid, 'rscmaxbackups', 3)),
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
  "trigger" => "test-rackspace-cloud-storage",
  "data" => [
    "storage" => "rackspace-cloud",
  ],
]);
?>