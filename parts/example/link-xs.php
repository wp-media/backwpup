<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<p>
  <?php
  BackWPupHelpers::component("navigation/link", [
    "url" => "#",
    "newtab" => true,
    "font" => "xs",
    "content" => "Small link icon",
    "icon_position" => "after",
    "icon_name" => "arrow-right",
  ]);
  ?>
</p>