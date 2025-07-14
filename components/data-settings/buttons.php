<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information.
 * @var string $type The configured job selected.
 * @var string $icon The icon to display.
 * @var bool $status the status of the data type.
 */

$main_btn_class =  BackWPupHelpers::clsx( 'js-backwpup-mixed-data-settings', 'flex items-center gap-2 p-2 pr-4 border rounded',
    'has-[:checked]:border-secondary-base has-[:checked]:bg-secondary-lighter border-transparent bg-white 
    hover:bg-grey-200', 'w-full'
);
$configure_btn_class = BackWPupHelpers::clsx('js-data-settings-' . $type,
	'flex items-center gap-2 border rounded',  'ml-2 border-transparent bg-white hover:bg-grey-200'
);

?>
<li class="flex flex-row">
	<button data-job-id="<?php echo esc_attr( $job_id ); ?>"  class="<?php echo esc_attr( $main_btn_class ); ?>">
		<input id='<?php echo esc_attr( $type ); ?>' value="<?php echo esc_attr( $type); ?>"
		       type="checkbox" name="<?php echo esc_attr( $type ); ?>" class="sr-only js-backwpup-mixed-data-settings-checkbox"
			    <?php checked( $status ); ?>
		>
		<span class="p-2 border border-grey-500 rounded cursor-pointer">
            <?php
            BackWPupHelpers::component( 'icon', [
                'name' => $icon,
                'size' => 'large',
            ]);
            ?>
        </span>
		<label class="text-base font-title cursor-pointer"><?php echo esc_html( ucfirst( $type ) ); ?></label>
	</button>

	<button data-mixed-data-content="<?php echo esc_attr( $type );?>"
	        data-job-id="<?php echo esc_attr( $job_id ); ?>"
	        data-toggle-setting-panel="<?php echo esc_attr( $type ); ?>"
		      class="<?php echo esc_attr( $configure_btn_class ); ?> " >
		<?php
		BackWPupHelpers::component( 'tooltip', [
			"content" => __( 'Configure', 'backwpup'),
			"icon_name" => 'settings',
			"icon_size" => 'large',
			"position" => 'left',
			'parent_classes' => 'p-3'
		]);
		?>
	</button>
</li>
