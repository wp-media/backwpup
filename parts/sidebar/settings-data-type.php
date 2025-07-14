<?php
use BackWPup\Utils\BackWPupHelpers;

/**
 * @var int $job_id Job ID information
 * @var string $job_type The configured job selected
 */
if ( ! isset( $job_id ) || ! isset( $job_type ) ) {
	return;
}
?>
<section class="flex flex-col flex-auto gap-4 js-data-main-setting">
    <?php
    BackWPupHelpers::component( 'closable-heading', [
        'title' => __( 'Data Settings', 'backwpup' ),
        'type' => 'sidebar'
    ]);

    BackWPupHelpers::component( 'containers/scrollable-start', [ 'gap_size' => 'small' ] );

    $configure_btn_class = BackWPupHelpers::clsx( 'js-data-settings' , 'flex items-center gap-2 border rounded', 'ml-2 border-transparent bg-white hover:bg-grey-200' ) ;

    $base_style = 'flex items-center gap-2 p-2 pr-4 border rounded';
    $contextual_style = 'has-[:checked]:border-secondary-base has-[:checked]:bg-secondary-lighter border-transparent bg-white 
    hover:bg-grey-200';
    $js_trigger_class = 'js-backwpup-mixed-data-settings';
    $full_width_class = 'w-full';
    $active = $job_type === 'mixed';
    $types = [
        'file-alt' => 'files',
        'database' => 'database',
    ];
    ?>
    <p class="text-base"><?php _e( 'You can choose what to save in your backup', 'backwpup'); ?></p>
    <div class="rounded-lg p-6 bg-grey-100">
        <ul class="<?php echo esc_attr( 'flex flex-col gap-2 max-w-screen-md' ); ?>">
            <?php foreach ( $types  as $icon => $type ) :
	            $active = $job_type === 'mixed' || $job_type === $type;

	            BackWPupHelpers::component( 'data-settings/buttons', [
                    'icon'   => $icon,
                    'status' => $active,
                    'type'   => $type,
                    'job_id' => $job_id
                ]);
            ?>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php
    BackWPupHelpers::component("containers/scrollable-end");

    BackWPupHelpers::component("form/button", [
        "type" => "secondary",
        "label" => __("Close", 'backwpup'),
        "full_width" => true,
        "trigger" => "close-sidebar",
    ]);
    ?>
</section>

<section class="hidden flex flex-col flex-auto gap-4 js-file-settings-section">
    <?php BackWPupHelpers::children("sidebar/select-files", false, ['job_id' => $job_id]); ?>
</section>

<section class="hidden flex flex-col flex-auto gap-4 js-database-settings-section">
	<?php BackWPupHelpers::children("sidebar/select-tables", false, ['job_id' => $job_id]); ?>
</section>
