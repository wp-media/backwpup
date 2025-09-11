<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var string $label The label of the select.
 * @var string $archiveformat The archive format selected
 */

BackWPupHelpers::component("form/select", [
  "name" => "archiveformat",
  "label" => $label,
  "value" => $archiveformat,
  "trigger" => "format-job",
  "options" => [
    ".tar" => __("TAR (.tar)", 'backwpup'),
    ".tar.gz" => __('TAR GZIP (.tar.gz)', 'backwpup'),
    ".zip" => __("ZIP (.zip)", 'backwpup'),
  ],
]);
?>

<div class="js-backwpup-format-job-show-if-zip">
  <?php
  BackWPupHelpers::component( 'alerts/info', [
    'type'    => 'alert',
    'font'    => 'small',
    'content' => __( 'ZIP format may increase server load (higher CUP & RAM usage) during backup.', 'backwpup' ),
  ]);
  ?>
</div>
