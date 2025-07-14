<?php
use BackWPup\Utils\BackWPupHelpers;
use WPMedia\BackWPup\Plugin\Plugin;
$job_id = $job_id ?? null;
//The way SugarSync works requires a job_id here to prevent bugs during onboarding.
// @todo Refactor it.
if (null === $job_id || empty($job_id) ) {
	$is_in_form    = true;
	$job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
}
$sugardir        = esc_attr( BackWPup_Option::get( $job_id, 'sugardir', trailingslashit( sanitize_title_with_dashes( get_bloginfo( 'name' ) ) ) ) );
$sugarmaxbackups = esc_attr(BackWPup_Option::get($job_id, 'sugarmaxbackups', 3));
$token           = BackWPup_Option::get( $job_id, 'sugarrefreshtoken', false );

BackWPupHelpers::component( 'closable-heading', [
  'title' => __( 'Sugar Sync Settings', 'backwpup' ),
  'type'  => 'sidebar'
]);


if (isset($is_in_form) && ( false === $is_in_form || 'false' === $is_in_form )) : ?>
  <p>
    <?php
    BackWPupHelpers::component( 'form/button', [
      'type'          => 'link',
      'label'         => __( 'Back to Storages', 'backwpup'),
      'icon_name'     => 'arrow-left',
      'icon_position' => 'before',
      'trigger'       => 'load-and-open-sidebar',
      'display'       => 'storages',
      'data'		  => [ 'job-id' => $job_id, 'block-type' => 'children', 'block-name' => 'sidebar/storages', ]
    ]);
    ?>
  </p>
<?php endif; ?>
	<div class="mt-2 text-base text-danger" id="sugarsync_authenticate_infos"></div>
<?php BackWPupHelpers::component( 'containers/scrollable-start' ); ?>

<div class="rounded-lg p-4 bg-grey-100" id="sugarsynclogin">
    <?php BackWPupHelpers::children("sidebar/sugar-sync-parts/api-connexion", false, [ 'job_id' => $job_id ] ); ?>
</div>

<div class="rounded-lg" id="sugarsyncroot">
    <?php BackWPupHelpers::children( 'sidebar/sugar-sync-parts/root-folder',false, [ 'job_id' => $job_id ] ); ?>
</div>

<div class="rounded-lg p-4 bg-grey-100">
  <?php
      BackWPupHelpers::component( 'heading', [
        'level' => 2,
        'title' => __( 'Backup Settings', 'backwpup' ),
        'font'  => 'small',
        'class' => 'mb-4',
      ]);
  ?>

  <div class="flex flex-col gap-2">
    <?php
        BackWPupHelpers::component( 'form/text', [
          'name'       => 'sugardir',
          'identifier' => 'sugardir',
          'label'      => __( 'Folder to store files in', 'backwpup' ),
          'value'      => $sugardir,
          'required'   => true,
        ]);

        BackWPupHelpers::component( 'form/text', [
          'name'       => 'sugarmaxbackups',
          'identifier' => 'sugarmaxbackups',
          'type'       => 'number',
          'min'        => 1,
          'label'      => __( 'Max backups to retain', 'backwpup'),
          'value'      => $sugarmaxbackups,
          'required'   => true,
        ]);

        BackWPupHelpers::component( 'alerts/info', [
          'type'    => 'alert',
          'font'    => 'xs',
          'content' => __( 'When this limit is exceeded, the oldest backup will be deleted.', 'backwpup'),
        ]);
    ?>
  </div>
</div>

<?php

BackWPupHelpers::component( 'containers/scrollable-end' );

BackWPupHelpers::component( 'form/button', [
  'type'       => 'primary',
  'label'      => __( 'Save & Test connection', 'backwpup' ),
  'full_width' => true,
  'trigger'    => 'test-SUGARSYNC-storage',
  'data'       => [
      'storage' => 'sugar-sync',
      'job-id'  => $job_id,
  ],
]);
?>