<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 */
?>
<p class="text-base text-center"><?php _e("Add folders, files or extensions you want to include", 'backwpup'); ?></p>

<div class="mt-4">
  <?php
  $tags = BackWPup_Option::get( $job_id, 'dirinclude' ) ?? '';
  BackWPupHelpers::component("form/add", [
    "name" => "dirinclude",
    "trigger" => "add-include-folder",
    "placeholder" => '',
    "tags" => explode( ',', $tags ? : '' )
  ]);
  ?>
</div>