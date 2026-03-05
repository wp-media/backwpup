<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * @var string  $padding_size   The padding sizes. Values: "small", "medium", "large". Default: "small".
 * @var string  $children       Children component to display. Must fit a /part/ template. Default: null.
 * @var string  $identifier     The identifier for the component. Default: null.
 * @var bool    $display        Whether to display the component. Default: true.
 */

# Padding 
$padding_sizes = [
  'small' => 'p-4',
  'medium' => 'p-6',
  'large' => 'p-8',
];
$padding_size = $padding_size ?? 'small';
$display = $display ?? true;
$padding = array_key_exists($padding_size, $padding_sizes) ? $padding_sizes[$padding_size] : $padding_sizes['small'];
$id = $identifier ?? '';
$toShow = ! $display;
?>
<div class="<?php echo esc_attr( BackWPupHelpers::clsx( $padding, "rounded-lg bg-grey-100" ) ); ?>"<?php echo $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?><?php echo $toShow ? ' style="display: none;"' : ''; ?>>
  <?php isset($children) && BackWPupHelpers::children($children); ?>
</div>
