<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $label          The link label. Default: "".
 * @var string  $type           The button variation type. Values: "primary", "secondary", "link". Default: "link".
 * @var bool    $disabled       Optional. True to disable button. Default: false.
 * @var bool    $full_width     Optional. True to make the button full width. Default: false.
 * @var string  $font           Optional. The font size. Values: "small", "medium". Default: "medium".
 * @var string  $icon_name      Optional. The name of the icon or false. Must match a file in components/icons/. Default: false.     
 * @var string  $icon_position  Optional. The position of the icon. Values: "before", "after", "alone". Default: "after".    
 * @var string  $trigger        Optional. For JS. The CSS classname for jQuery. Default: null.
 * @var string  $display        Optional. For JS. The content to display in modal or sidebar. Default: null. 
 * @var string  $class          Optional. Additional CSS classname . Default: null.
 * @var array   $data           Optional. Additional data attributes. Default: [].
 * @var string  $identifier     Optional. The identifier for the component. Default: null.
 */

# Disabled
$disabled = $disabled ?? false;

# Full width
$full_width = isset($full_width) && $full_width ? "w-full" : "";
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;

# Type
$button_base = "flex items-center";
$types = ["primary", "secondary", "link", "settings"];
$type = isset($type) && in_array($type, $types) ? $type : "link";
switch ($type) {
  case "primary":
    $button_style = "justify-center rounded border border-transparent disabled:opacity-40 bg-secondary-base text-base font-medium text-primary-darker enabled:hover:bg-secondary-darker";
    break;
  case "secondary":
    $button_style = "justify-center rounded border border-primary-darker disabled:opacity-40 text-base font-medium text-primary-darker enabled:hover:bg-grey-200";
    break;
  case "settings":
    $button_style = "justify-between rounded bg-grey-100 text-xl font-medium text-primary-darker hover:bg-grey-200";
    break;
  default:
    $button_style = "justify-center leading-5 text-primary-darker border-b border-primary-darker font-title disabled:opacity-40 enabled:hover:text-primary-lighter enabled:hover:border-primary-lighter";
    break;
}

# Font
$font = $font ?? "medium";
$font_key = $type === "link" ? $font . "_link" : $font;
$font_sizes = [
  "small_link" => "text-xs gap-2",
  "medium_link" => "text-base gap-4",
  "small" => "px-4 py-[11px] text-xs gap-2",
  "medium" => "px-6 py-[14px] text-base gap-4",
];
$font_size = array_key_exists($font_key, $font_sizes) ? $font_sizes[$font_key] : $font_sizes['medium'];

# Icon
$icon_position = isset($icon_name) ? $icon_position ?? "after" : false;
$icon = [
  "name" => $icon_name ?? false,
  "size" => $font === "small" ? "xs" : $font,
];

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";
$display = isset($display) ? " data-content=\"$display\"" : "";

# CSS
$class = $class ?? "";

# Data
$data_attrs = "";
if (isset($data)) {
  foreach ($data as $key => $value) {
    $data_attrs .= " data-$key=\"$value\"";
  }
}

?>
<button <?php echo $id ?> <?php if ($disabled) : ?>disabled<?php endif; ?> class="<?php echo BackWPupHelpers::clsx($font_size, $full_width, $button_base, $button_style, $class, $trigger); ?>" <?php echo $display; ?><?php echo $data_attrs; ?>>
  <?= $icon_position === "before" || $icon_position === "alone" && BackWPupHelpers::component("icon", $icon) ? BackWPupHelpers::component("icon", $icon) : '' ; ?>
  <?php if ($icon_position !== "alone" && $label !== "") : ?>
    <span><?php echo $label ?? ""; ?></span>
  <?php endif; ?>
  <?= $icon_position === "after" && BackWPupHelpers::component("icon", $icon) ? BackWPupHelpers::component("icon", $icon) : ''; ?>
</button>