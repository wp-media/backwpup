<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Textarea field component.
 *
 * @var string  $name           Unique name of the field to handle value when form is submitted to PHP.
 * @var string  $label          The field label. Default: "".
 * @var bool    $required       Optional. True to set the field as required. Default: false.
 * @var string  $value          Optional. The field value. Default: "".
 * @var bool    $invalid        Optional. True to set the field as invalid. Default: false.
 * @var string  $tooltip        Optional. The tooltip content. Default: "".
 * @var string  $tooltip_pos    Optional. The tooltip position. Default: "center".
 * @var int     $min            Optional. The minimum value. Only for type number fields.
 * @var int     $max            Optional. The maximum value. Only for type number fields.
 * @var string  $trigger        Optional. The javascript class. Default: "".
 * @var string  $identifier     Optional. The field identifier. Default: null.
 */

// Name.
if ( ! isset( $name ) ) {
	throw new Exception( "Attribute 'name' is required on Text field" );
}

// Defaults.
$label       = $label ?? '';
$value       = $value ?? '';
$required    = $required ?? false;
$invalid     = $invalid ?? false;
$field_id    = $identifier ?? '';
$tooltip_pos = $tooltip_pos ?? 'center';

// JS actions.
$trigger = isset( $trigger ) ? 'js-backwpup-' . $trigger : '';
$hidden  = ! empty( $hidden ) ? 'hidden' : '';

// Classes.
$container_classes            = 'block relative border rounded font-title focus-within:border-secondary-base';
$container_contextual_classes = $invalid ? 'border-danger' : 'border-grey-500';

$text_classes            = 'input-base-label absolute left-4 flex items-center gap-2 transition-all top-2 text-xs';
$text_contextual_classes = $invalid ? 'text-danger' : 'text-grey-700';

?>
<label class="<?php echo esc_attr( BackWPupHelpers::clsx( $container_classes, $container_contextual_classes, $hidden ) ); ?>">
	<textarea name="<?php echo esc_attr( $name ); ?>"<?php echo $field_id ? " id='" . esc_attr( $field_id ) . "'" : ''; ?> class="<?php echo esc_attr( BackWPupHelpers::clsx( 'input-base text-lg w-full h-40', $trigger ) ); ?>" placeholder=""
	<?php
	if ( $required ) :
		?>
	required<?php endif; ?>><?php echo esc_textarea( $value ); ?></textarea>
	<p class="<?php echo esc_attr( $text_classes . ' ' . $text_contextual_classes ); ?>">
		<?php echo esc_html( $label ); ?>
		<?php
		if ( isset( $tooltip ) ) {
			BackWPupHelpers::component(
				'tooltip',
				[
					'content'   => $tooltip,
					'icon_size' => 'small',
					'position'  => $tooltip_pos,
				]
			);
		}
		?>
	</p>
</label>
