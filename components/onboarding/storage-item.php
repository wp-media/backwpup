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
$contextual_style = "has-[:checked]:border-secondary-base has-[:checked]:bg-secondary-lighter border-transparent bg-white hover:bg-grey-200";
$full_width_class = isset($full_width) && $full_width ? "w-full" : "";
$js_trigger_class = "js-backwpup-toggle-storage";

# JS
$content = "storage-$slug";
$configure_btn_class = BackWPupHelpers::clsx( 'flex items-center gap-2 p-3 border rounded', 'ml-2 border-transparent bg-white hover:bg-grey-200' ) ;
?>
<button class="<?php echo BackWPupHelpers::clsx( $js_trigger_class, $base_style, $contextual_style, $full_width_class ); ?>"
        data-content="<?php echo esc_attr( $content ); ?>"
        data-job-id="<?php echo esc_attr( $job_id ); ?>"
        data-storage="<?php echo esc_attr( $slug ); ?>"
>
	<input id='<?php echo esc_attr( $identifier ); ?>' value="<?php echo esc_attr( $slug); ?>"
	       type="checkbox" name="<?php echo esc_attr( $name ); ?>" class="sr-only" <?php checked( $active ); ?>>
	<span class="p-2 border border-grey-500 rounded">
    <?php include untrailingslashit(BackWPup::get_plugin_data('plugindir'))."/assets/img/storage/$slug.svg"; ?>
  </span>
	<label class="text-base font-title"><?php echo esc_html( $label ); ?></label>
</button>