<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $title          Title of the accordion. Default: "".
 * @var bool    $open           Optional. True to open the accordion. Default: false. 
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.
 * @var array   $children_data  Optional. Data to pass to the children component. Default: [].
 * @var bool    $children_return Optional. True to return the children component. Default: false.
 * @var bool    $remove_item_center_class Optional. False to remove items-center to the details class. Default: false.
 */

# Defaults
$title = $title ?? "";
$open = $open ?? false;
$children = $children ?? null;
$children_data = $children_data ?? [];
$children_return = $children_return ?? false;
$item_center_class = isset( $remove_item_center_class ) ? '' : 'items-center';

$detail_class = BackWPupHelpers::clsx( 'group/accordion flex justify-between flex-col' , $item_center_class ) ;
?>
<details <?php if ($open) : ?>open<?php endif; ?> class="<?php echo $detail_class; ?>">
  <summary class="cursor-pointer flex items-center gap-4 justify-between self-center">
    <h2 class="text-xl text-primary-darker font-semibold font-title"><?php echo $title; ?></h2>
    <span class="transition-transform rotate-180 group-open/accordion:rotate-0">
      <?php BackWPupHelpers::component("icon", ["name" => "toggle", "size" => "medium"]); ?>
    </span>
  </summary>

  <div class="mt-4">
    <?php isset($children) && BackWPupHelpers::children($children, $children_return, $children_data); ?>
  </div>
</details>