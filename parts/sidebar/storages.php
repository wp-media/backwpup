<?php
use BackWPup\Utils\BackWPupHelpers;
$job_id = $job_id ?? null;
BackWPupHelpers::component("closable-heading", [
  'title' => __("Storages Settings", 'backwpup'),
  'type' => 'sidebar'
]);
if (null === $job_id) {
  $job_id = get_site_option( 'backwpup_backup_files_job_id', false );
}
$destinations = [];
if (false !== $job_id) {
	$destinations = BackWPup_Option::get($job_id, 'destinations');
}

// Get all the destinations including local.
$cloud_destinations = BackWPup_Destinations::get_destinations(true);
$dist_storages = [];
foreach ($cloud_destinations as $a_cloud_destination) {
  $dist_storages[] = [
    "slug" => $a_cloud_destination["slug"],
    "label" => $a_cloud_destination["label"],
    "name" => "storage_destinations[]",
    "active" => in_array($a_cloud_destination["slug"], $destinations),
  ];
}

?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<p class="mt-2 text-base">
  <?php _e("You can select where to store your backups and configure each storage.", 'backwpup'); ?>
</p>

<div class="rounded-lg p-6 bg-grey-100">

  <?php
  BackWPupHelpers::component("storage-list", [
    "full_width" => true,
    "prev" => "storages",
    "storages" => $dist_storages,
    "job_id" => $job_id,
  ]);
  ?>
</div>

<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/button", [
  "type" => "secondary",
  "label" => __("Close", 'backwpup'),
  "full_width" => true,
  "trigger" => "close-sidebar",
]);
?>