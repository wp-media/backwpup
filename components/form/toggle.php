<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.  
 * @var string  $label          Optional. The label for the toggle. Default: null 
 * @var bool    $checked        Optional. True to check the toggle. Default: false.  
 * @var string  $trigger        Optional. The javascript class. Default: "".
 * @var array $data Optional. Additional data attributes. Default: [].
 */

# Name
if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on Toggle field");
}

# Data
$data_attrs = isset( $data ) && is_array( $data ) ? $data : [];

# Label
$label = $label ?? null;

# Checked
$checked_attr = isset($checked) && $checked ? "checked" : "";

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

#remove div
$remove_div = isset($remove_div) && $remove_div;

?>
<?php if (!$remove_div) : ?>
<div class="relative flex gap-2">
  <?php endif; ?>
  <input type="checkbox" class="<?php echo esc_attr( BackWPupHelpers::clsx( "sr-only peer group", $trigger ) ); ?>" aria-hidden="true" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" <?php echo esc_attr( $checked_attr ); ?>
  <?php
  if ( ! empty( $data_attrs ) ) {
  	foreach ( $data_attrs as $key => $value ) {
  		printf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
  	}
  }
  ?>>
  <label for="<?php echo esc_attr( $name ); ?>" class="bg-grey-500 peer-checked:bg-secondary-base relative inline-flex h-6 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none"></label>
  <span aria-hidden="true" class="absolute top-1 left-1 translate-x-0 peer-checked:translate-x-4 pointer-events-none inline-block h-4 w-4 transform rounded-full bg-primary-darker ring-0 transition duration-200 ease-in-out"></span>
  <?php if ($label) : ?>
    <label for="<?php echo esc_attr( $name ); ?>" class="text-base cursor-pointer"><?php echo esc_html( $label ); ?></label>
  <?php endif; ?>
<?php if (!$remove_div) : ?>
</div>
<?php endif; ?>
