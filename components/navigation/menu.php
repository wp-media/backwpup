<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var array   $actions        An array of actions.  
 */

# Actions 
$actions = $actions ?? [];

# CSS
$class = $class ?? "";

?>
<div class="<?php echo BackWPupHelpers::clsx("group relative flex justify-end js-backwpup-menu", $class); ?>">
  <div class="h-11 w-10 flex items-center justify-center border border-primary-darker rounded cursor-pointer group-hover:border-secondary-darker group-hover:text-secondary-darker">
    <?php BackWPupHelpers::component("icon", ["name" => "dots", "size" => "small"]); ?>
  </div>
  <div class="hidden absolute z-10 top-full right-0 rounded bg-white p-1 shadow-md js-backwpup-menu-content">
    <?php foreach ($actions as $action) : ?>
      <?php BackWPupHelpers::component("navigation/menu-item", $action); ?>
    <?php endforeach; ?>
  </div>
</div>