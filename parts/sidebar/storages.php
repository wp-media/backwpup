<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var $job_id ID Of the job
 */
if ( $job_id === null ) {
	return;
}
$is_onboarding = get_site_option( 'backwpup_onboarding', false );
if ( $is_onboarding ) {
  return;
}

BackWPupHelpers::component("closable-heading", [
  'title' => __("Storages Settings", 'backwpup'),
  'type' => 'sidebar'
]);

$selected_destinations = BackWPup_Option::get($job_id, 'destinations', []);
if (! is_array( $selected_destinations ) ) {
  $selected_destinations = [];
}

// Get all the destinations including local.
$all_destinations = BackWPup_Destinations::get_destinations(true);
$dist_storages = [];

foreach ($all_destinations as $destination) {
  $dist_storages[] = [
    "slug" => $destination["slug"],
    "label" => $destination["label"],
    "name" => "storage_destinations[]",
    "active" => in_array($destination["slug"], $selected_destinations),
    "deactivated_message" => $destination["deactivated_message"] ?? "",
  ];
}

?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>

<p class="mt-2 text-base">
  <?php esc_html_e("You can select where to store your backups and configure each storage.", 'backwpup'); ?>
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