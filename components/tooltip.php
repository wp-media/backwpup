<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $content      The tooltip content. Default: "".
 * @var string  $position     Optionnal. The tooltip position. Values: "left" or "center". Default: "left".
 * @var string  $icon_name    Optionnal. The tooltip icon name. Must match a file in /components/icons. Default: "info".
 * @var string  $icon_size    Optionnal. The tooltip icon size. Values: see Icon component. Default: "medium".
 * @var string  $parent_classes  Optionnal. The tooltip parent classes. Values: any class. Default: null.
 */

# Defaults
$icon_name = $icon_name ?? "info";
$icon_size = $icon_size ?? "medium";
$data = $data ?? [];

# Position
$position = $position ?? "bottom";

$tooltip_class = 'left-1/2 -translate-x-1/2 bottom-full mb-2';
$tooltip_arrow = 'left-1/2 transform -translate-x-1/2 top-full border-t-gray-900 dark:border-t-gray-700';

if ( $position === 'right' ) {
    $tooltip_class = 'left-full top-1/2 -translate-y-1/2 ml-2';
    $tooltip_arrow = 'top-1/2 transform -translate-y-1/2 right-full border-r-gray-900 dark:border-r-gray-700';
}
if ( $position === 'left' ) {
    $tooltip_class = 'right-full top-1/2 -translate-y-1/2 mr-2';
    $tooltip_arrow = 'top-1/2 transform -translate-y-1/2 left-full border-l-gray-900 dark:border-l-gray-700';
}
if ( $position === 'bottom' ) {
    $tooltip_class = 'left-1/2 -translate-x-1/2 top-full mt-2';
    $tooltip_arrow = 'left-1/2 transform -translate-x-1/2 bottom-full border-b-gray-900 dark:border-b-gray-700';
}

$classes = isset($class) ? $class : '';
$parent_classes = $parent_classes ?? '';

# CSS
$tooltip_classes = BackWPupHelpers::clsx(
    $tooltip_class,
    'absolute invisible inline-block group-hover:opacity-100 group-hover:visible bg-gray-800 text-white text-xs rounded z-50 transform',
    'p-2 text-xs font-normal opacity-0 font-body bg-gray-900 rounded-lg shadow-md tooltip dark:bg-gray-700 min-w-[130px] text-center max-w-[200px]'
);

$tooltip_arrow_classes = BackWPupHelpers::clsx(
    $tooltip_arrow,
    'absolute w-0 h-0 border-6 border-solid border-transparent'
);

$parent_classes = BackWPupHelpers::clsx(
  "group relative pointer-events-auto",
  $parent_classes
);
$tooltip_surrounding_element = $tooltip_surrounding_element ?? 'span';

?>
<<?php echo $tooltip_surrounding_element; ?> class="<?php echo $parent_classes; ?>">
  <?php if (isset($tooltip_component)) : ?>
    <?php BackWPupHelpers::component($tooltip_component['component'], $tooltip_component['args']); ?>
  <?php elseif (isset($icon_name)) : ?>
  <span class="text-primary-darker">
    <?php BackWPupHelpers::component("icon", ["name" => $icon_name, "size" => $icon_size, "data" => $data, "class" => $classes]); ?>
  </span>
  <?php endif; ?>
  <span data-tooltip-position="<?php echo esc_attr( $position ); ?>" class="<?php echo esc_attr( $tooltip_classes ); ?>">
    <?php echo $content ?? ''; ?>
      <span class="<?php echo esc_attr( $tooltip_arrow_classes ); ?>"></span>
  </span>
</<?php echo $tooltip_surrounding_element; ?>>