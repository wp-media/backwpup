<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.  
 * @var string  $value          Optional. The field value. Default: "". 
 * @var string  $placeholder    Optional. The field placeholder. Default: "". 
 * @var string  $trigger        Optional. The javascript class. Default: "".   
 */

# Name
if (!isset($name)) {
  throw new Exception("Attribute 'name' is required on Search field");
}

# Defaults
$placeholder = $placeholder ?? "";
$value = $value ?? "";

# JS actions
$trigger = isset($trigger) ? "js-backwpup-$trigger" : "";

?>
<label class="mb-4 px-4 flex items-center border border-grey-500 rounded font-title focus-within:border-secondary-base">
  <div class="text-grey-700">
    <?php BackWPupHelpers::component("icon", ["name" => "search", "size" => "small"]); ?>
  </div>
  <input type="text" class="<?php echo esc_attr( BackWPupHelpers::clsx( "input-special flex-auto", $trigger ) ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
  <button class="text-grey-700 text-base js-backwpup-clear-search hover:text-secondary-darker">✕</button>
</label>
