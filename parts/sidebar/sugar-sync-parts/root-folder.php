<?php
use BackWPup\Utils\BackWPupHelpers;

$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$token = BackWPup_Option::get($jobid, 'sugarrefreshtoken', false);
?>
<?php if (!$token) : ?>
<?php
  BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "font" => "xs",
      "content" => __("Not authenticated!", 'backwpup'),
  ]);
  ?>
<?php else: ?>
<?php
  $sugar_sync = new BackWPup_Destination_SugarSync_API($token);
  $user = $sugar_sync->user();
  $sync_folders = $sugar_sync->get($user->syncfolders);
  $folders = [];
  if ( isset( $sync_folders ) && is_object( $sync_folders ) ) {
      foreach ( $sync_folders->collection as $roots ) {
        $folders[(string)$roots->ref] = (string)$roots->displayName; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
      }
  }
 ?>
<?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Sugar Sync Root", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/select", [
      "name" => "sugarroot",
      "identifier" => "sugarroot",
      "label" => __("Bucket selection", 'backwpup'),
      "withEmpty" => false,
      "value" => BackWPup_Option::get( $jobid, 'sugarroot','' ),
      "options" => $folders,
  ]);
  ?>
<?php endif; ?>
