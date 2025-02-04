<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.
 * @var string  $value          The field label. Default: "".
 * @var string $identifier Optional. The field identifier. Default: null.
 */
#Defaults
$value = $value ?? "";
$id = isset($identifier) ? " id='".esc_attr($identifier)."'" : null;
?>
<input type="hidden" <?php echo $id; ?>  name="<?= esc_attr($name) ?>" value="<?= esc_attr($value) ?>">
