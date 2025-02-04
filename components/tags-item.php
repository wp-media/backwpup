<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $label          Label of the tag. Default: "".   
 */

# Defaults
$label = $label ?? "";

?>
<button class="px-[6px] py-1 flex items-center gap-1 rounded bg-primary-darker text-base text-white font-title hover:bg-primary-base js-backwpup-remove-tag" data-tag="<?php echo $label; ?>">
  <span><?php echo $label; ?></span>
  <?php BackWPupHelpers::component("icon", ["name" => "trash", "size" => "small"]); ?>
</button>