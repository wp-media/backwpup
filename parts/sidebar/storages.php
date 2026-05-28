<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @var $job_id ID Of the job
 */
if ( ! isset( $job_id ) || $job_id === null ) {
	return;
}
$is_onboarding = get_site_option( 'backwpup_onboarding', false );
if ( $is_onboarding ) {
	return;
}

BackWPupHelpers::component(
	'closable-heading',
	[
		'title' => __( 'Storages Settings', 'backwpup' ),
		'type'  => 'sidebar',
	]
	);

$selected_destinations = BackWPup_Option::get( $job_id, 'destinations', [] );
if ( ! is_array( $selected_destinations ) ) {
	$selected_destinations = [];
}

// Get all the destinations including local.
$all_destinations = BackWPup_Destinations::get_destinations( true );
$dist_storages    = [];

foreach ( $all_destinations as $destination ) {
	$dist_storages[] = [
		'slug'                => $destination['slug'],
		'label'               => $destination['label'],
		'name'                => 'storage_destinations[]',
		'active'              => in_array( $destination['slug'], $selected_destinations ),
		'deactivated_message' => $destination['deactivated_message'] ?? '',
	];
}

?>

<?php BackWPupHelpers::component( 'containers/scrollable-start' ); ?>

<p class="mt-2 text-base">
	<?php esc_html_e( 'You can select where to store your backups and configure each storage.', 'backwpup' ); ?>
</p>

<div class="rounded-lg p-6 bg-grey-100">

	<?php
	BackWPupHelpers::component(
		'storage-list',
		[
			'full_width' => true,
			'prev'       => 'storages',
			'storages'   => $dist_storages,
			'job_id'     => $job_id,
		]
		);
	?>
</div>

<?php BackWPupHelpers::component( 'containers/scrollable-end' ); ?>

<?php if ( ! BackWPup::is_pro() ) : ?>
	<div class="flex flex-row rounded-lg bg-alert-light p-4 mt-4 gap-4 items-center">
		<div class="flex-1 min-w-0">
            <div class="font-title font-bold text-base text-primary-base leading-tight mb-1 ">
                <?php esc_html_e( 'Unlock more storage options', 'backwpup' ); ?>
            </div>
		</div>
		<div class="flex-shrink-0 flex items-center h-full">
				<?php
				BackWPupHelpers::component(
					'navigation/link',
					[
						'url'        => wpm_apply_filters_typed( 'string', 'backwpup_url_add_hash', 'https://backwpup.com/pricing/?utm_source=backwpup_plugin&utm_medium=plugin&utm_campaign=storage_settings' ),
						'newtab'     => true,
						'content'    => __( 'Upgrade to PRO', 'backwpup' ),
						'full_width' => false,
						'type'       => 'upgrade',
						'class'      => 'js-backwpup-nudge-cta',
					]
				);
				?>
		</div>
	</div>
<?php endif; ?>
<?php
	BackWPupHelpers::component(
	'form/button',
	[
		'type'       => 'secondary',
		'label'      => __( 'Close', 'backwpup' ),
		'full_width' => true,
		'trigger'    => 'close-sidebar',
	]
	);
?>
