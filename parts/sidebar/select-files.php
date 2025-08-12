<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 * @var int $first_job_id ID of the first job we are retrieving the frequency settings for. (Only available during onboarding)
 */
if ( ! isset( $job_id ) && get_site_option( 'backwpup_onboarding', false ) ) {
	$job_id = $first_job_id;
}
if ( ! isset($job_id)) {
	return;
}
BackWPupHelpers::component("navigation-header", [
    'title' => __("Select Files", 'backwpup'),
    'type' => 'sidebar',
    'navigation' => 'files'
]);
?>

<?php BackWPupHelpers::component("containers/scrollable-start"); ?>
<div class="rounded-lg p-6 bg-grey-100">
  <?php
  BackWPupHelpers::component("containers/accordion", [
	"title" => __("Content Selector", 'backwpup'),
	"open" => true,
	"children" => "sidebar/parts/files-content-selector-pro",
	"children_return" => false,
	"children_data" => ['job_id' => $job_id],
  ]);
  ?>
</div>

<div class="rounded-lg p-6 bg-grey-100">
  <?php
  BackWPupHelpers::component("containers/accordion", [
	"title" => __("Exclude from backup", 'backwpup'),
	"open" => true,
	"children" => "sidebar/parts/exclude-from-backup",
	"children_return" => false,
	"children_data" => ['job_id' => $job_id],
  ]);
  ?>
</div>
<?php BackWPupHelpers::component("containers/scrollable-end"); ?>

<?php
BackWPupHelpers::component("form/hidden", [
	"name" => "job_id",
	"value" => $job_id,
]);
BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __("Save settings", 'backwpup'),
    "full_width" => true,
    "trigger" => "close-sidebar",
    "identifier" => "file-exclusions-submit",
    "class" => "file-exclusions-submit",
]);
?>