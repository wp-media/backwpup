<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name       The input name.
 * @var string  $value      File or folder name.
 * @var string  $label      The input label
 * @var string  $icon       The icon to display. Default : "".
 * @var bool    $includes   True if the file is included. default: true.       
 */

if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on File block");
}

# Defaults
$icon = $icon ?? false;
$included = $included ?? true;
$value = $value ?? "";

?>
<div class="group rounded-lg p-4 flex items-center gap-2 bg-grey-100 cursor-pointer js-backwpup-toggle-include">
  <input type="checkbox" value="<?php echo $value; ?>" name="<?php echo $name; ?>" class="peer sr-only" <?php if (!$included) : ?>checked<?php endif; ?>>
  <span class="flex-auto flex items-center gap-2 peer-checked:text-danger peer-checked:line-through">
    <?php if ($icon) {
      BackWPupHelpers::component("icon", ["name" => $icon]);
    } ?>
    <span class="text-base"><?php echo $label ?? ''; ?></span>
  </span>
  <div class="hidden group-hover:block">
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "font" => "small",
      "label" => __("Exclude from backup", 'backwpup'),
      "icon_name" => "remove",
      "icon_position" => "before",
      "trigger" => "toggle-include-add",
      "class" => $included ? "" : "hidden",
    ]);
    ?>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "font" => "small",
      "label" => __("Include to backup", 'backwpup'),
      "icon_name" => "add",
      "icon_position" => "before",
      "trigger" => "toggle-include-remove",
      "class" => $included ? "hidden" : "",
    ]);
    ?>
  </div>
</div>