<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.
 * @var string  $label          The field label. Default: "". 
 * @var string  $type           The type of the input. Values: "text", "number", "email", "password"… Default: "text".
 * @var bool    $required       Optional. True to set the field as required. Default: false.
 * @var string  $value          Optional. The field value. Default: "". 
 * @var string  $tooltip        Optional. The tooltip content. Default: "".
 * @var string  $tooltip_pos    Optional. The tooltip position. Default: "center".
 * @var bool    $invalid        Optional. True to set the field as invalid. Default: false.
 * @var int     $min            Optional. The minimum value. Only for type number fields.  
 * @var int     $max            Optional. The maximum value. Only for type number fields.
 * @var string  $trigger        Optional. The javascript class. Default: "".
 * @var string $identifier Optional. The field identifier. Default: null.
 */

# Name
if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on Text field");
}

# Defaults
$label = $label ?? "";
$type = $type ?? "text";
$value = $value ?? "";
$required = $required ?? false;
$invalid = $invalid ?? false;
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;
$tooltip_pos = $tooltip_pos ?? "center";

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

# Classes
$container_classes = "block relative border rounded font-title focus-within:border-secondary-base";
$container_contextual_classes = $invalid ? "border-danger" : "border-grey-500";

$text_classes = "input-base-label absolute left-4 flex items-center gap-2 transition-all top-2 text-xs";
$text_contextual_classes = $invalid ? "text-danger" : "text-grey-700";

?>
<label class="<?php echo BackWPupHelpers::clsx($container_classes, $container_contextual_classes); ?>">
  <input name="<?=esc_attr($name)?>" type="<?php echo esc_attr($type); ?>" <?php echo $id; ?> class="<?php echo BackWPupHelpers::clsx("input-base text-lg w-full", $trigger); ?>" placeholder="" <?php if ($required) : ?>required<?php endif; ?> value="<?php echo esc_attr($value); ?>" <?php if (isset($min) && $type === "number") : ?>min="<?php echo esc_attr($min); ?>" <?php endif; ?> <?php if (isset($max) && $type === "number") : ?>max="<?php echo esc_attr($max); ?>" <?php endif; ?>>
  <p class="<?php echo $text_classes; ?> <?php echo $text_contextual_classes; ?>">
    <?php echo $label; ?>
    <?php isset($tooltip) && BackWPupHelpers::component("tooltip", ["content" => $tooltip, "icon_size" => "small", "position" => $tooltip_pos]); ?>
  </p>
</label>