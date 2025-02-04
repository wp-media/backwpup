<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $title          Title of the accordion. Default: "".
 * @var bool    $open           Optional. True to open the accordion. Default: false. 
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.   
 */

# Defaults
$title = $title ?? "";
$open = $open ?? false;

?>
<details <?php if ($open) : ?>open<?php endif; ?> class="group/accordion flex items-center justify-between flex-col">
  <summary class="cursor-pointer flex items-center gap-4 justify-between">
    <h2 class="text-xl text-primary-darker font-semibold font-title"><?php echo $title; ?></h2>
    <span class="transition-transform rotate-180 group-open/accordion:rotate-0">
      <?php BackWPupHelpers::component("icon", ["name" => "toggle", "size" => "medium"]); ?>
    </span>
  </summary>

  <div class="mt-4">
    <?php isset($children) && BackWPupHelpers::children($children); ?>
  </div>
</details>