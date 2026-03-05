<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

BackWPupHelpers::component("heading", [
  "level" => 1,
  "title" => __("We are creating a backup of your site…", 'backwpup'),
  "align" => "center",
]);
?>

<?php BackWPupHelpers::component("progress-box", []); ?>

<?php BackWPupHelpers::component("containers/white-box", [
  "padding_size" => "large",
  "children" => "now/info-few-minutes",
  "class" => "mt-6",
]); ?>
