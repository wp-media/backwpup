<?php
use BackWPup\Utils\BackWPupHelpers;
// Get all the cloud destinations.
$destinations = BackWPup_Destinations::get_destinations();
$dist_storages = [];
foreach ($destinations as $destination) {
  $dist_storages[] = [
    "slug" => $destination["slug"],
    "label" => $destination["label"],
    "name" => "onboarding_storage[]",
    "active" => false,
  ];
}
BackWPupHelpers::component("heading", [
  "level" => 2,
  "title" => __("Where to store your backup?", 'backwpup'),
]);
?>

<div class="flex-auto">

  <div class="flex items-center gap-4 border-b border-grey-200 py-6">
    <div class="flex-auto">
      <?php
      BackWPupHelpers::component("heading-desc", [
        "title" => __("Website storage", 'backwpup'),
        "description" => __("Store your backup on your website's server", 'backwpup'),
      ]);
      ?>

      <?php
      BackWPupHelpers::component("storage-list", [
        "storages" => [
          [
            "slug" => "FOLDER",
            "label" => "Website Server",
            "name" => "onboarding_storage[]",
            "active" => false,
          ],
        ],
      ]);
      ?>
    </div>
  </div>

  <div class="flex items-center gap-4 py-6">
    <div class="flex-auto">
      <?php
      BackWPupHelpers::component("heading-desc", [
        "title" => __("Cloud Storages", 'backwpup'),
        "description" => __("Store your backup on your favorite cloud storage platforms", 'backwpup'),
      ]);
      ?>

      <?php
      BackWPupHelpers::component("storage-list", [
        "storages" => $dist_storages
      ]);
      ?>

    </div>
  </div>
</div>

<footer class="mt-6 flex justify-between items-center gap-4">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "secondary",
    "label" => __("Back to When", 'backwpup'),
    "icon_name" => "arrow-left",
    "icon_position" => "before",
    "trigger" => "onboarding-step-2",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save & Continue", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
    "trigger" => "onboarding-submit-form",
    "disabled" => true,
  ]);
  ?>
</footer>