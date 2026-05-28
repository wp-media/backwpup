<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locked (PRO-only) storage item for the onboarding flow, displayed to free users.
 *
 * @var string $slug       The storage slug. Also the SVG icon file name.
 * @var string $label      The storage label.
 * @var bool   $full_width Optional. Make the item full width. Default: false.
 */

// Defaults.
$slug       = $slug ?? 'GDRIVE';
$label      = $label ?? '';
$full_width = $full_width ?? false;

// Classes.
$base_style       = 'flex items-center gap-2 p-2 pr-4 border rounded';
$contextual_style = 'border-transparent bg-white opacity-60 cursor-not-allowed';
$full_width_class = $full_width ? 'w-full' : '';
$lock_btn_class   = BackWPupHelpers::clsx( 'flex items-center gap-2 p-3 border rounded', 'ml-2 border-transparent bg-white text-grey-500' );

// Validate slug against known storage icons to prevent path traversal.
$allowed_slugs = [ 'GDRIVE', 'GLACIER', 'HIDRIVE', 'ONEDRIVE' ];
if ( ! in_array( $slug, $allowed_slugs, true ) ) {
	$slug = 'GDRIVE';
}
?>
<div class="<?php echo esc_attr( BackWPupHelpers::clsx( $base_style, $contextual_style, $full_width_class ) ); ?>"
	aria-disabled="true"
>
	<span class="p-2 border border-grey-500 rounded">
		<?php require untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/assets/img/storage/$slug.svg"; ?>
	</span>
	<span class="text-base font-title"><?php echo esc_html( $label ); ?></span>
</div>
<div class="<?php echo esc_attr( $lock_btn_class ); ?>">
	<?php
	BackWPupHelpers::component(
		'icon',
		[
			'name' => 'lock',
			'size' => 'medium',
		]
		);
	?>
</div>
