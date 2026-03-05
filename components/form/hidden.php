<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.
 * @var string  $value          The field label. Default: "".
 * @var string $identifier Optional. The field identifier. Default: null.
 */
#Defaults
$value = $value ?? "";
$id = $identifier ?? '';
?>
<input type="hidden"<?php echo $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?> name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>">
