<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage list component.
 *
 * @var array  $storages             An array of storage services. Default: [].
 * @var bool   $full_width           Optional. True to make the button full width. Default: false.
 * @var string $prefix               Optional. The prefix for the input name. Default: "".
 * @var string $job_id               Optional. The job ID. Default: null.
 * @var string $deactivated_message  Optional. The message to display when the storage is deactivated.
 */

// Defaults.
$storages   = $storages ?? [];
$prefix     = $prefix ?? '';
$full_width = $full_width ?? false;
$job_id     = $job_id ?? null;

$is_onboarding     = get_site_option( 'backwpup_onboarding', false );
$css_class         = 'flex flex-col gap-2 max-w-screen-md';
$storage_component = 'storage-item';
if ( $is_onboarding ) {
	$storage_component = 'onboarding/storage-item';
	$css_class         = 'flex flex-wrap gap-2 max-w-screen-md';
}
$total_active = count( array_filter( $storages, fn( $s ) => ! empty( $s['active'] ) ) );

// Whether to render the locked PRO section at the bottom of this list.
$show_locked = $show_locked ?? true;

// Slugs that are rendered in the locked PRO section -- skip them in the main loop for free users.
$locked_pro_slugs = ( ! BackWPup::is_pro() && $show_locked ) ? [ 'GDRIVE', 'GLACIER', 'HIDRIVE', 'ONEDRIVE' ] : [];
?>

<ul class="<?php echo esc_attr( $css_class ); ?>">
	<?php
	foreach ( $storages as $storage ) :
		if ( in_array( $storage['slug'], $locked_pro_slugs, true ) ) {
			continue;
		}
		$storage_component_ident = $storage_component;
		if ( $storage['deactivated_message'] ) {
			$storage_component_ident .= '-disabled';
		}
		?>
		<li class="flex flex-row">
		<?php
		BackWPupHelpers::component(
			$storage_component_ident,
			[
				'name'                => $storage['name'],
				'slug'                => $storage['slug'],
				'active'              => $storage['active'],
				'full_width'          => $full_width,
				'prefix'              => $prefix,
				'label'               => $storage['label'],
				'job_id'              => $job_id,
				'total_active'        => $total_active,
				'deactivated_message' => $storage['deactivated_message'],
			]
		);
		?>
		</li>
	<?php endforeach; ?>
</ul>
<?php
if ( ! BackWPup::is_pro() && $show_locked ) :
	$locked_component    = $is_onboarding ? 'onboarding/storage-item-locked' : 'storage-item-locked';
	$locked_pro_storages = [
		[
			'slug'  => 'GDRIVE',
			'label' => 'Google Drive',
		],
		[
			'slug'  => 'GLACIER',
			'label' => 'Amazon Glacier',
		],
		[
			'slug'  => 'HIDRIVE',
			'label' => 'HiDrive',
		],
		[
			'slug'  => 'ONEDRIVE',
			'label' => 'Microsoft OneDrive',
		],
	];
	do_action( 'backwpup_track_nudge_impression', 'storage_selection' );
	?>
<ul class="<?php echo esc_attr( $css_class ); ?> mt-2">
	<?php foreach ( $locked_pro_storages as $locked_storage ) : ?>
		<li class="flex flex-row js-backwpup-locked-storage" data-storage="<?php echo esc_attr( $locked_storage['slug'] ); ?>">
		<?php
		BackWPupHelpers::component(
			$locked_component,
			[
				'slug'       => $locked_storage['slug'],
				'label'      => $locked_storage['label'],
				'full_width' => $full_width,
			]
		);
		?>
		</li>
	<?php endforeach; ?>
</ul>
<?php endif; ?>