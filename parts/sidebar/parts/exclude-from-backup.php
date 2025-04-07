<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 */
?>
<p class="text-base"><?php _e("Add folders, files or extensions you want to exclude", 'backwpup'); ?></p>

<div class="mt-4">
  <?php
  $tags = BackWPup_Option::get( $job_id, 'fileexclude' ) ?? '';
  BackWPupHelpers::component("form/add", [
    "name" => "fileexclude",
    "trigger" => "add-exclude-file",
    "placeholder" => "",
    "tags" => explode( ',', $tags )
  ]);
  ?>
</div>