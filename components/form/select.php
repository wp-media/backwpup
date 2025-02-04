<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.
 * @var string  $label          The field label. Default: "". 
 * @var array   $options        The options to display. Array with keys => value. Default: [].
 * @var bool    $withEmpty      Optional. True to add an empty option. Default: false.
 * @var bool    $required       Optional. True to set the field as required. Default: false.
 * @var string  $value          Optional. The field value. Default: "". 
 * @var string  $trigger        Optional. For JS. The CSS classname for jQuery. Default: null.
 * @var string  $tooltip        Optional. The tooltip content. Default: "".
 * @var string  $tooltip_pos    Optional. The tooltip position. Default: "center".
 * @var string $class Optional. Additional CSS classname . Default: null.
 * @var string $identifier Optional. The field identifier. Default: null.
 */

# Name
if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on Select field");
}

# Defaults
$label = $label ?? "";
$value = $value ?? "";
$options = $options ?? [];
$withEmpty = $withEmpty ?? false;
$required = $required ?? false;
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;
$tooltip_pos = $tooltip_pos ?? "center";

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

# CSS
$class = $class ?? "";

?>
<div class="select block relative border border-grey-500 rounded font-title focus-within:border-secondary-base">
  <label class="select-label flex gap-1 items-center absolute top-2 left-4 text-grey-700 leading-5 text-xs transition-all pointer-events-none" name="<?php echo $name; ?>">
    <?php echo $label; ?>
    <?php isset($tooltip) && BackWPupHelpers::component("tooltip", ["content" => $tooltip, "icon_size" => "small", "position" => $tooltip_pos]); ?>
  </label>
  <select name="<?=esc_attr($name)?>" <?php echo $id; ?> class="<?php echo BackWPupHelpers::clsx("peer select-transparent block min-w-[200px] w-full text-base text-primary-darker", $trigger, $class); ?>" <?php if ($required) : ?>required<?php endif; ?>>
    <?php if ($withEmpty) : ?>
      <option value="" <?php if ($value === "") : ?>selected<?php endif; ?>></option>
    <?php endif; ?>
    <?php foreach ($options as $key => $option) : ?>
      <option value="<?php echo $key; ?>" <?php echo $key === $value ? "selected" : ""; ?>><?php echo $option; ?></option>
    <?php endforeach; ?>
  </select>
  <div class="absolute right-4 top-0 bottom-0 flex items-center pointer-events-none rotate-180">
    <?php BackWPupHelpers::component("icon", ["name" => "toggle", "size" => "small"]); ?>
  </div>
</div>