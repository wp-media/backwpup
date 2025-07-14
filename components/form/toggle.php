<?php
use BackWPup\Utils\BackWPupHelpers;
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
$data_attrs = "";
if (isset($data)) {
	foreach ($data as $key => $value) {
		$data_attrs .= " data-$key=\"$value\"";
	}
}

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
  <input type="checkbox" class="<?php echo BackWPupHelpers::clsx("sr-only peer group", $trigger); ?>" aria-hidden="true" id="<?php echo $name; ?>" name="<?php echo $name; ?>" <?php echo $checked_attr; ?> <?php echo $data_attrs; ?>>
  <label for="<?php echo $name; ?>" class="bg-grey-500 peer-checked:bg-secondary-base relative inline-flex h-6 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none"></label>
  <span aria-hidden="true" class="absolute top-1 left-1 translate-x-0 peer-checked:translate-x-4 pointer-events-none inline-block h-4 w-4 transform rounded-full bg-primary-darker ring-0 transition duration-200 ease-in-out"></span>
  <?php if ($label) : ?>
    <label for="<?php echo $name; ?>" class="text-base cursor-pointer"><?php echo $label; ?></label>
  <?php endif; ?>
<?php if (!$remove_div) : ?>
</div>
<?php endif; ?>