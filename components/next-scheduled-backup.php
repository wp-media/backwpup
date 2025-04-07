<?php
use BackWPup\Utils\BackWPupHelpers;
?>

<div class="flex-1 p-8 border border-grey-250 justify-center hover:bg-grey-200 rounded-lg flex flex-col cursor-pointer backwpup-add-new-backup-card min-h-[173px]" id="js_backwpup_add_new_backup">
    <div class="mb-2 flex justify-center">
      <button class="text-primary-darker hover:text-secondary-darker flex items-center justify-center gap-3 font-title font-bold">
        <?php BackWPupHelpers::component("icon", ["name" => "plus", "size" => "medium-2x"]); ?>
        <div class="text-base"><?php esc_html_e( 'Add a new backup', 'backwpup' ); ?></div>
      </button>
    </div>  
</div>

<div class="flex-1 p-8 bg-white rounded-lg flex-col hidden backwpup-dynamic-backup-card">
    <div class="mb-2 flex items-center gap-2">
		<div class="flex-auto">
	        <?php
	        BackWPupHelpers::component("heading", [
	          "level" => 2,
	          "title" => __('Add a new backup', 'backwpup'),
	        ]);
	        ?>
        </div>

        <button class="text-primary-darker text-xl hover:text-secondary-darker" id="js_backwpup_close_dynamic_backup_card">✕</button>
    </div>

	<div class="mt-2 mb-4 flex-auto">
		<p class="text-base label-scheduled"><?php esc_html_e( 'What do you want to backup?', 'backwpup' ); ?></p>
	</div>

     
    <?php
    BackWPupHelpers::component("containers/form-start", [
      "action" => "add-new-backup",
      "identifier" => "js-backwpup-add-new-backup-form",
      "scrollable" => false
    ]);
    ?>
	<div class="flex items-center gap-1 backwpup-dynamic-input">
        <label for="new_files" class="flex w-1/2 items-center gap-2 border rounded-md p-[2px] cursor-pointer bg-secondary-lighter border-secondary-base">
	        <input type="radio" name="type" value="files" id="new_files" class="sr-only backwpup-dynamic-backup-type" checked>
	        <div class="border bg-white p-1 rounded border-secondary-base">
	            <?php BackWPupHelpers::component("icon", ["name" => "file-alt", "size" => "large"]); ?>
	        </div>
	        <p><?php esc_html_e( 'Files', 'backwpup' ); ?></p>
        </label>

		<label for="new_db" class="flex w-1/2 items-center gap-2 border rounded-md p-[2px] cursor-pointer">
			<input type="radio" name="type" value="database" id="new_db" class="sr-only backwpup-dynamic-backup-type">
			<div class="border bg-white p-1 rounded">
				<?php BackWPupHelpers::component("icon", ["name" => "database", "size" => "large"]); ?>
			</div>
			<p><?php esc_html_e( 'Database', 'backwpup' ); ?></p>
		</label>

            <div class="w-1/6">
              <?php
              BackWPupHelpers::component("form/button", [
                "type" => "primary",
                "label" => __("Add", 'backwpup'),
                'identifier' => 'js-backwpup-add-new-backup',
                'font' => 'semi_large',
              ]);
              ?>
            </div>
          </div>
        <?php
        BackWPupHelpers::component("containers/form-end");
        ?>
  </div>