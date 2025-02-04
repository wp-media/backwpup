<?php
  use BackWPup\Utils\BackWPupHelpers;
?>
<div class="max-w-[800px] flex flex-col gap-4">

  <?php BackWPupHelpers::component("app/header", []); ?>

  <?php
  BackWPupHelpers::component("containers/grey-box", [
    "padding_size" => "large",
    "children" => "restore/start",
  ]);
  ?>

  <?php
  BackWPupHelpers::component("alerts/info", [
    "type" => "alert",
    "font" => "medium",
    "content" => __("Please do not leave this screen while restoration is in progress. Exiting prematurely could cause an error and potentially break your site.", 'backwpup'),
    "content2" => __("Stay on this page until the process is complete.", 'backwpup'),
  ]);
  ?>
</div>

<?php
BackWPupHelpers::component("containers/sidebar");
BackWPupHelpers::component("containers/modal");
?>