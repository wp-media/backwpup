<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 */
?>
<p class="text-base"><?php _e("Add folders you want to include", 'backwpup'); ?></p>

<div class="mt-4">
  <?php
  $tags = BackWPup_Option::get( $job_id, 'dirinclude' ) ?? '';
  BackWPupHelpers::component("form/add", [
    "name" => "dirinclude",
    "trigger" => "add-include-folder",
    "placeholder" => __("Enter absolute folder path", 'backwpup'),
    "tags" => explode( ',', $tags ? : '' )
  ]);
  ?>
</div>