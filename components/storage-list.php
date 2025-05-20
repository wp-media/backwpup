<?php
use BackWPup\Utils\BackWPupHelpers;
/**
 * @var array   $storages     An array of storage services. Default: [].
 * @var bool    $full_width   Optional. True to make the button full width. Default: false.
 * @var string  $prefix       Optional. The prefix for the input name. Default: "".
 * @var string  $job_id       Optional. The job ID. Default: null.
 */

# Defaults
$storages = $storages ?? [];
$prefix = $prefix ?? "";
$full_width = $full_width ?? false;
$job_id = $job_id ?? null;

$is_onboarding = get_site_option( 'backwpup_onboarding', false );
$css_class = 'flex flex-col gap-2 max-w-screen-md';
$storage_component = 'storage-item';
if( $is_onboarding ) {
	$storage_component = 'onboarding/storage-item';
	$css_class = 'flex flex-wrap gap-2 max-w-screen-md';
}
$total_active = count( array_filter( $storages, fn( $s ) => !empty( $s['active'] ) ) );
?>

<ul class="<?php echo esc_attr( $css_class ); ?>">
  <?php foreach ( $storages as $storage ) : ?>
      <li class="flex flex-row">
        <?php BackWPupHelpers::component( $storage_component, [
            'name' => $storage['name'],
            'slug' => $storage['slug'],
            'active' => $storage['active'],
            'full_width' => $full_width,
            'prefix' => $prefix,
            'label' => $storage['label'],
            'job_id' => $job_id,
            'total_active' => $total_active
        ]); ?>
      </li>
  <?php endforeach; ?>
</ul>