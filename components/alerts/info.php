<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var string  $type           The type of alert. Values: "pro", "alert", "danger", "default". Default: "default".
 * @var string  $content        The content. Default: "".
 * @var string  $content2       Optional. A second line of content. Default: "".
 * @var string  $font           Optional. The font size. Values: "xs", "small", "medium". Default: null.
 * @var string  $children       Optional. Children component to display. Must fit a /part/ template. Default: null.
 */

# Type
$types = ["pro", "alert", "danger", "default"];
$type = isset($type) && in_array($type, $types) ? $type : "default";
switch ($type) {
  case "pro":
    $block_style = "bg-secondary-lighter text-primary-darker";
    $icon = "pro";
    break;
  case "alert":
    $block_style = "bg-alert-light text-alert";
    $icon = "alert";
    break;
  case "danger":
    $block_style = "bg-danger-light text-danger";
    $icon = "danger";
    break;
  default:
    $block_style = "bg-white text-primary-darker";
    $icon = "info";
    break;
}

# Font
$font = $font ?? "medium";
$font_sizes = [
  "xs" => "text-xs",
  "small" => "text-sm",
  "medium" => "text-base",
];
$font_size = array_key_exists($font, $font_sizes) ? $font_sizes[$font] : $font_sizes['medium'];

?>
<div class="flex items-center gap-2 p-4 rounded <?php echo $block_style; ?>">
  <div class="shrink-0">
    <?php BackWPupHelpers::component("icon", ["name" => $icon, "size" => "large"]); ?>
  </div>
  <div class="flex flex-col gap-1">
    <p class="<?php echo $font_size; ?> font-medium">
      <?php echo $content ?? ''; ?>
    </p>
    <?php if (isset($content2)) : ?>
      <p class="<?php echo BackWPupHelpers::clsx("mt-2", $font_size); ?> font-medium">
        <?php echo $content2; ?>
      </p>
    <?php endif; ?>
    <?php isset($children) && BackWPupHelpers::children($children); ?>
  </div>
</div>