<?php
use BackWPup\Utils\BackWPupHelpers;
use WPMedia\BackWPup\Plugin\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Because on the onboarding we don't have the job_id.
if (empty($job_id)) {
    $job_id = get_site_option( Plugin::FIRST_JOB_ID, false );
}
$token = BackWPup_Option::get($job_id, 'sugarrefreshtoken', false);
?>
<?php if ($token) : ?>
  <div class="p-4 bg-grey-100">
    <?php
      try {
        $sugar_sync = new BackWPup_Destination_SugarSync_API($token);
        $user = $sugar_sync->user();
        $sync_folders = $sugar_sync->get($user->syncfolders);
        $folders = [];
        if ( isset( $sync_folders ) && is_object( $sync_folders ) ) {
            foreach ( $sync_folders->collection as $roots ) {
              $folders[(string)$roots->ref] = (string)$roots->displayName; //phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            }
        }
        BackWPupHelpers::component( 'heading', [
          'level' => 2,
          'title' => __( 'Sugar Sync Root', 'backwpup' ),
          'font'  => 'small',
          'class' => 'mb-4',
        ]);

        BackWPupHelpers::component( 'form/select', [
            'name'       => 'sugarroot',
            'identifier' => 'sugarroot',
            'label'      => __( 'Bucket selection', 'backwpup' ),
            'withEmpty'  => false,
            'value'      => BackWPup_Option::get( $job_id, 'sugarroot','' ),
            'options'    => $folders,
        ]);
      } catch ( BackWPup_Destination_SugarSync_API_Exception $e ) {
        BackWPupHelpers::component( 'alerts/error', [
          'type'    => 'alert',
          'font'    => 'small',
          'content' => __( 'Could not connect to SugarSync. Please re-authenticate.', 'backwpup' ),
        ]);
      }
    ?>
  </div>
<?php endif; ?>
