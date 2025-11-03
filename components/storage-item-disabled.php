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
 * @var int     $total_active Optional. The total number of active storage.s
 * @var string $deactivated_message Optional. The message to display when the storage is deactivated.
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
$total_active = $total_active ?? 1;

# Classes
$base_style = "flex items-center gap-2 p-2 pr-4 border rounded";
$contextual_style = "has-[:checked]:border-secondary-base has-[:checked]:bg-secondary-lighter border-transparent bg-white";
$full_width_class = isset($full_width) && $full_width ? "w-full" : "";
$js_trigger_class = '';
$configure_btn_class = BackWPupHelpers::clsx( 'flex items-center gap-2 border rounded', 'ml-2 border-transparent bg-white' );
$select_btn_class = BackWPupHelpers::clsx( $base_style, $contextual_style, $full_width_class );

# JS
$configure_btn_content = "storage-$slug";
?>
<div class="<?php echo $select_btn_class; ?>">
  <span class="p-2 border border-grey-500 rounded">
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/assets/img/storage/$slug.svg"; ?>
  </span>
  <span class="text-base font-title"><?php echo esc_html( $label ); ?></span>
</div>
<div class="<?php echo $configure_btn_class ?>">
    <?php
        BackWPupHelpers::component( 'tooltip', [
            "content" => $deactivated_message,
            "icon_name" => 'remove',
            "icon_size" => 'medium',
            "position" => 'left',
            'parent_classes' => 'p-4',
        ]);
    ?>
</div>
