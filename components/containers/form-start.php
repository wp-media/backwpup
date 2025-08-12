<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var string  $gap_size   The gap size. Values: "small", "medium". Default: "medium".
 * @var string $method     The form method. Default: "post".
 * @var string $page     The form action. Default: "backwpup"
 * @var string $action     The form action. Default: "backwpup"
 * @var bool  $scrollable Whether the container is scrollable. Default: true.
 * @var string $identifier The identifier for the form. Default: ""
 * @var string  $class        Optional. Custom class to add to the container. Default: "".
 */

# Defaults
$class = isset($class) ? " " . $class : "";

# Padding
$gap_sizes = [
	'small' => 'gap-2',
	'medium' => 'gap-4',
];
$gap_size = $gap_size ?? 'medium';
$identifier = $identifier ?? '';
$scrollable = $scrollable ?? true;
$gap = array_key_exists($gap_size, $gap_sizes) ? $gap_sizes[$gap_size] : $gap_sizes['medium'];
$method = $method ?? 'post';
$page = $page ?? 'backwpup';
$action = $action ?? 'backwpup';
$overflow = $scrollable ?? true ? 'overflow-y-scroll' : '';
$absolute = $scrollable ?? true ? 'absolute' : '';
$idForm = $identifier ? 'id="'.$identifier.'"' : '';
?>
<div class="<?php echo BackWPupHelpers::clsx("relative flex-auto", $overflow); ?>">
	<form
		class="<?php echo BackWPupHelpers::clsx("w-full flex flex-col", $absolute, $gap, $class); ?>"
		action="<?php echo admin_url('admin-post.php'); ?>"
		method="<?php echo esc_attr($method); ?>"
    <?=$idForm?>
	>
  <?php wp_nonce_field($page.'_page'); ?>
  <?php wp_nonce_field('backwpup_ajax_nonce', 'backwpupajaxnonce', false); ?>
  <input type="hidden" name="page" value="<?=$page?>"/>
  <input type="hidden" name="action" value="<?=$action?>"/>
