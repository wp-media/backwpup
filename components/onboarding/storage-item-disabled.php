<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name         The name of the service.
 * @var string  $slug         The slug of the service, also icon file name.
 * @var string  $identifier   Optional. The identifier for the input.
 * @var bool    $active       Optional. True to set the service as active. Default: false.
 * @var bool    $full_width   Optional. True to make the button full width. Default: false.
 * @var string  $prefix       Optional. The prefix for the input name. Default: "".
 * @var string  $label        Optional. The label for the input. Default: $name.
 * @var string  $job_id       Optional. The job ID. Default: null.
 * @var string  $deactivated_message Optional. The message to display when the storage is deactivated.
 */

# Defaults
$name = $name ?? "";
$prefix = $prefix ?? "";
$slug = $slug ?? "FOLDER";
$active = $active ?? false;
$label = $label ?? $name;
$full_width = $full_width ?? false;
$with_back = $with_back ?? false;
$identifier = $identifier ?? 'destination-'.$slug;

# Classes
$base_style = "flex items-center gap-2 p-2 pr-4 border rounded";
$contextual_style = "border-transparent bg-white";
$full_width_class = isset($full_width) && $full_width ? "w-full" : "";

# JS
$content = "storage-$slug";
?>
<div class="<?php echo BackWPupHelpers::clsx( $base_style, $contextual_style, $full_width_class ); ?>">
    <?php
    BackWPupHelpers::component( 'tooltip', [
      "content" => $deactivated_message,
      "icon_name" => 'remove',
      "icon_size" => 'medium',
      "position" => 'left',
      'parent_classes' => 'p-2 border border-grey-500 rounded',
    ]);
    ?>
	<span class="text-base font-title"><?php echo esc_html( $label ); ?></span>
</div>