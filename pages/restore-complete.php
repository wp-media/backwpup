<?php
  use BackWPup\Utils\BackWPupHelpers;
?>
<div class="max-w-[800px] flex flex-col gap-4">

  <?php BackWPupHelpers::component("app/header", []); ?>

  <?php
  BackWPupHelpers::component("containers/grey-box", [
    "padding_size" => "large",
    "children" => "restore/complete",
  ]);
  ?>
</div>

<?php
BackWPupHelpers::component("containers/sidebar");
BackWPupHelpers::component("containers/modal");
?>