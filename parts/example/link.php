<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<p>
  <?php
  BackWPupHelpers::component("navigation/link", [
    "url" => "#",
    "newtab" => true,
    "font" => "small",
    "content" => "External link",
  ]);
  ?>
</p>