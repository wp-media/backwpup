<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<p>
  <?php
  BackWPupHelpers::component("navigation/link", [
    "url" => "#",
    "newtab" => true,
    "font" => "small",
    "content" => __("More infos", 'backwpup'),
    "icon_name" => "arrow-right",
    "icon_position" => "after",
  ]);
  ?>
</p>