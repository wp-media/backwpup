<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $content      The tooltip content. Default: "".
 * @var string  $position     Optionnal. The tooltip position. Values: "left" or "center". Default: "left".
 * @var string  $icon_name    Optionnal. The tooltip icon name. Must match a file in /components/icons. Default: "info".
 * @var string  $icon_size    Optionnal. The tooltip icon size. Values: see Icon component. Default: "medium".
 */

# Defaults
$icon_name = $icon_name ?? "info";
$icon_size = $icon_size ?? "medium";

# Position
$position = $position ?? "left";
$position_class = $position === "left" ? "right-full" : "left-1/2 -translate-x-1/2";

# CSS
$tooltip_classes = BackWPupHelpers::clsx(
  "absolute z-10 hidden group-hover:block top-full",
  $position_class,
  "p-2 text-xs font-normal font-body bg-white rounded shadow-md text-primary-darker w-max max-w-[200px]"
);

?>
<span class="group relative pointer-events-auto">
  <span class="text-primary-darker cursor-pointer">
    <?php BackWPupHelpers::component("icon", ["name" => $icon_name, "size" => $icon_size]); ?>
  </span>
  <span class="<?php echo $tooltip_classes; ?>">
    <?php echo $content ?? ''; ?>
  </span>
</span>