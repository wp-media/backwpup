<?php

use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BackWPupHelpers::component("closable-heading", [
  'title' => __( 'Bulk Delete Backups', 'backwpup' ),
  'type' => 'modal'
]);
?>

<?php
BackWPupHelpers::component("alerts/info", [
  "type" => "alert",
  "content" => __( "Deleting a backup is permanent and cannot be undone.", 'backwpup' ),
  "content2" => __( "This means you will lose the ability to restore your site to the state captured in this backup.", 'backwpup' ),
]);
?>

<footer class="flex flex-col gap-2">
  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "primary",
    "label" => __( 'Delete Backups', 'backwpup' ),
    "full_width" => true,
    "trigger" => "bulk-delete-backups",
    "button_type" => "button",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("form/button", [
    "type" => "secondary",
    "label" => __( 'Cancel', 'backwpup' ),
    "full_width" => true,
    "trigger" => "close-modal",
    "button_type" => "button",
  ]);
  ?>
</footer>
