<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

BackWPupHelpers::component(
	'heading',
	[
		'level' => 1,
		'title' => __( 'Restore backup from: March 11, 2024 11:03', 'backwpup' ),
	]
	);
?>

<div class="mt-6 flex flex-col gap-2">
	<div class="p-4 bg-white rounded">
	<h2 class="flex items-center gap-2 text-base font-semibold">
		<span class="w-4 text-secondary-base">
		<?php BackWPupHelpers::component( 'icon', [ 'name' => 'check' ] ); ?>
		</span>
		<?php esc_html_e( 'Archive downloaded', 'backwpup' ); ?>
	</h2>
	</div>

	<div class="p-4 bg-white rounded">
	<h2 class="flex items-center gap-2 text-base font-semibold">
		<span class="w-4 text-secondary-base">
		<?php BackWPupHelpers::component( 'icon', [ 'name' => 'check' ] ); ?>
		</span>
		<?php esc_html_e( 'Archive extracted…', 'backwpup' ); ?>
	</h2>
	</div>

	<div class="p-4 bg-white rounded">
	<h2 class="flex items-center gap-2 text-base font-semibold">
		<span class="w-4 text-secondary-base">
		<?php BackWPupHelpers::component( 'icon', [ 'name' => 'check' ] ); ?>
		</span>
		<?php esc_html_e( 'Backup restored', 'backwpup' ); ?>
	</h2>
	</div>

	<?php
	BackWPupHelpers::component(
		'containers/white-box',
		[
			'padding_size' => 'large',
			'children'     => 'restore/info-congratulations',
		]
		);
	?>

	<?php
	BackWPupHelpers::component(
		'containers/green-box',
		[
			'children' => 'restore/rate-us',
		]
		);
	?>

</div>