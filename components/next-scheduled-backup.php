<?php
use BackWPup\Utils\BackWPupHelpers;
?>

<div class="flex-1 p-8 border border-grey-250 justify-center rounded-lg flex flex-col cursor-pointer backwpup-add-new-backup-card min-h-[173px]" id="js_backwpup_add_new_backup">
    <div class="mb-2 flex justify-center">
      <button class="text-primary-darker flex items-center justify-center gap-3 font-title font-bold">
        <?php BackWPupHelpers::component("icon", ["name" => "plus", "size" => "medium-2x"]); ?>
        <div class="text-base"><?php esc_html_e( 'Add a new backup', 'backwpup' ); ?></div>
      </button>
    </div>  
</div>