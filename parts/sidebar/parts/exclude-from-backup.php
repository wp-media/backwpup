<?php
use BackWPup\Utils\BackWPupHelpers;
?>
<p class="text-base"><?php _e("Add folders, files or extensions you want to exclude", 'backwpup'); ?></p>

<div class="mt-4">
  <?php
  $tags = BackWPup_Option::get( get_site_option( 'backwpup_backup_files_job_id', 1), 'fileexclude' );
  BackWPupHelpers::component("form/add", [
    "name" => "fileexclude",
    "trigger" => "add-exclude-file",
    "placeholder" => "",
    "tags" => explode( ',', $tags )
  ]);
  ?>
</div>