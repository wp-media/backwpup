<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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