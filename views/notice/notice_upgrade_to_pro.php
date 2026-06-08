<?php
use BackWPup\Utils\BackWPupHelpers;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$upgrade_url = wpm_apply_filters_typed(
	'string',
	'backwpup_url_add_hash',
	'https://backwpup.com/pricing/?utm_source=backwpup_plugin&utm_medium=plugin&utm_campaign=upgrade_banner'
);

$cta_url = add_query_arg(
	[
		'bwu_redirect'                      => rawurlencode( $upgrade_url ),
		'bwu_event'                         => 'Upgrade nudge banner clicked',
		'bwu_event_property_nudge_location' => 'backup_complete',
		'_wpnonce'                          => wp_create_nonce( 'backwpup_redirect_nonce' ),
	],
	admin_url( 'admin.php' )
);

do_action( 'backwpup_track_nudge_impression', 'backup_complete' );
?>
<div class="notice-titre">
	<div class="inline-block p-1 bg-secondary-lighter text-primary-base rounded">
		<?php
		BackWPupHelpers::component(
			'icon',
			[
				'name' => 'check',
				'size' => 'medium',
			]
			);
		?>
	</div>
	<div class="inline-block p-1 ml-3">
		<?php esc_html_e( 'Backup Scheduled!', 'backwpup' ); ?>
	</div>
</div>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); // phpcs:ignore ?>" data-bwpu-hide="notice_upgrade_to_pro_notice"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span></a>
<div class="notice-content">
	<p>
	<span class="font-bold">
	<?php
		echo esc_html__(
			'Your backups run automatically, but restoring them manually can be slow.',
			'backwpup'
		);
		?>
	</span> <br />
	<?php
		echo esc_html__(
			'Upgrade to Pro for one-click restores, more cloud destinations, and priority support.',
			'backwpup'
		);
		?>
	</p>
	<?php
		BackWPupHelpers::component(
			'navigation/link',
			[
				'url'        => $cta_url,
				'newtab'     => true,
				'content'    => __( 'Upgrade to PRO', 'backwpup' ),
				'full_width' => false,
				'type'       => 'upgrade',
				'class'      => 'actionbutton',
			]
		);
		?>
</div>

