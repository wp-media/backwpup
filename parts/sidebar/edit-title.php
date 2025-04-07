<?php
use BackWPup\Utils\BackWPupHelpers;
$job_id = $job_id ?? null;
BackWPupHelpers::component("closable-heading", [
    'title' => __("Edit Title", 'backwpup'),
    'type' => 'sidebar'
  ]);
?>
<?php BackWPupHelpers::component("containers/scrollable-start", ["gap_size" => "small"]); ?>
<?php
BackWPupHelpers::component("form/text", [
"name" => "title",
"identifier" => "backwpup-job-title",
"label" => __("Edit the title of your scheduled backup", 'backwpup'),
"value" => esc_attr(BackWPup_Option::get($job_id, 'name')),
"required" => true,
]);
 ?>

<div id="js-backwpup-edit-title-warning" class="hidden">
<?php
BackWPupHelpers::component( 'alerts/info', [
  'type'    => 'alert',
  'font'    => 'small',
  'content' => __( 'Your scheduled backup needs a title.', 'backwpup' ),
]);
?>
</div>

<?php
BackWPupHelpers::component("form/hidden", [
'identifier' => 'backwpup-job-id',
"name" => "job_id",
"value" => $job_id,
]);

?>
<?php BackWPupHelpers::component("containers/scrollable-end"); ?>
<?php
BackWPupHelpers::component("form/button", [
  "type" => "primary",
  "label" => __("Save", 'backwpup'),
  "full_width" => true,
  "class" => "mt-4",
  "identifier" => 'js-backwpup-save-title'
]);
?>
