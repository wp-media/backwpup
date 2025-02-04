<?php
use BackWPup\Utils\BackWPupHelpers;
BackWPupHelpers::component("heading", [
  "level" => 1,
  "title" => __("We are creating a backup of your site…", 'backwpup'),
  "align" => "center",
]);
?>

<?php BackWPupHelpers::component("progress-box", []); ?>

<?php BackWPupHelpers::component("containers/white-box", [
  "padding_size" => "large",
  "children" => "now/info-congratulations",
  "class" => "mt-6",
]); ?>
