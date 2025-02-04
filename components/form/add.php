<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.  
 * @var bool    $placeholder    Optional. True to check the toggle. Default: "". 
 * @var array   $tags           List of tags. Default: [].   
 */

# Defaults
$name = $name ?? "search";
$placeholder = $placeholder ?? "";
$tags = $tags ?? [];

# Value
$tags_values = implode(",", $tags);

?>
<div class="<?php echo BackWPupHelpers::clsx("js-backwpup-add-input", $trigger); ?>">
  <label class="mb-2 p-1 flex border border-grey-500 rounded font-title focus-within:border-secondary-base">
    <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $tags_values; ?>" class="js-backwpup-add-input-values">
    <input type="text" name="add" class="flex-auto input-special" placeholder="<?php echo $placeholder; ?>">
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "font" => "small",
      "label" => __("Add", 'backwpup'),
      "icon_name" => "plus",
      "icon_position" => "before",
      "trigger" => "add-input-button",
    ]);
    ?>
  </label>

  <div class="flex flex-wrap gap-2 js-backwpup-add-input-tags">
    <?php 
      if(count($tags)>0) {
        foreach ($tags as $tag) {
          if (empty($tag)) continue;
          BackWPupHelpers::component("tags-item", ["label" => $tag]);
        }
      }
    ?>
  </div>

  <div class="hidden js-backwpup-add-input-tag-template">
    <?php BackWPupHelpers::component("tags-item", ["label" => "PROUT"]); ?>
  </div>
</div>