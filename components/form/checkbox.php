<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.  
 * @var string  $label          The label for the toggle. Default: null 
 * @var bool    $checked        Optional. True to check the toggle. Default: false.    
 * @var string  $tooltip        Optional. The tooltip content. Default: "".
 * @var string  $tooltip_pos    Optional. The tooltip position. Default: "center".
 * @var bool    $multiline      Optional. If true, aligns checkbox on top. Default: false.
 * @var string  $style          Optional. Style for the checkbox. Values: "default", "light". Default: "default".
 * @var string  $trigger        Optional. For JS. The CSS classname for jQuery. Default: null.
 * @var string $value           Optional. The value of the checkbox. Default: null.
 * @var string $identifier Optional. The field identifier. Default: null.
 * @var bool $disabled Optional. True to disable the checkbox. Default: false.
 * @var array $data            Optional. The data to be used in the component. Default: [].
 */

# Name
if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on Checkbox field");
}
# Disabled
$disabled = $disabled ?? false;
# ID
$id = $identifier ?? '';

# Label
$label = $label ?? "";
$value = $value ?? $label;
$hidden  = ! empty( $hidden ) ? 'hidden' : '';

# Checked
$checked_attr = isset($checked) && $checked ? "checked" : "";

# Multiline
$items_align = isset($multiline) && $multiline ? "items-start" : "items-center";

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

# Style
$style = $style ?? "default";
$checkbox_style = BackWPupHelpers::clsx(
  "relative shrink-0 h-6 w-6 rounded-sm border peer-checked/checkbox:border-secondary-base after:hidden peer-checked/checkbox:after:block after:absolute after:h-4 after:w-4 after:m-[3px] after:rounded-[1px] after:bg-secondary-base",
  ($style === "light" ? "border-grey-500" : false),
  ($style === "default" ? "border-primary-darker " : false)
);

# Tooltip position
$tooltip_pos = $tooltip_pos ?? "top";

$input_style = BackWPupHelpers::clsx(
  "peer/checkbox sr-only",
  (isset($checked) && $checked ? "checked" : false),
  $trigger
);

?>
<label class="<?php echo esc_attr( BackWPupHelpers::clsx( "cursor-pointer flex", $items_align, $hidden, "gap-2 text-base leading-5 font-title" ) ); ?>">
  <input value="<?php echo esc_attr( $value ); ?>"<?php echo $id ? " id='" . esc_attr( $id ) . "'" : ''; ?><?php if ( $disabled ) : ?> disabled="disabled"<?php endif; ?> type="checkbox" name="<?php echo esc_attr( $name ); ?>" class="<?php echo esc_attr( $input_style ); ?>"<?php echo $checked_attr ? ' ' . esc_attr( $checked_attr ) : ''; ?><?php if ( isset( $data ) && ! empty( $data ) ) : ?><?php foreach ( $data as $key => $value ) : ?><?php echo ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"'; ?><?php endforeach; ?><?php endif; ?>>
  <div class="<?php echo esc_attr( $checkbox_style ); ?>"></div>
  <?php echo wp_kses_post( $label ); ?>
  <?php isset($tooltip) && BackWPupHelpers::component("tooltip", ["content" => $tooltip, "icon_size" => "small", "position" => $tooltip_pos]); ?>
</label>
