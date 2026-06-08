<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$upgrade_url = wpm_apply_filters_typed(
	'string',
	'backwpup_url_add_hash',
	'https://backwpup.com/pricing/?utm_source=backwpup_plugin&utm_medium=plugin&utm_campaign=in_product&utm_content=backup_success_nudge'
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
<div class="flex items-center justify-between gap-4 p-4 rounded bg-alert-light w-full">
	<div class="flex items-center gap-4">
		<span class="text-2xl shrink-0" aria-hidden="true">⚡</span>
		<div class="flex flex-col gap-1">
			<p class="text-base font-bold text-primary-darker">
				<?php esc_html_e( 'Backup completed successfully!', 'backwpup' ); ?>
			</p>
			<p class="text-sm font-normal text-primary-darker">
				<?php esc_html_e( "Don't risk losing it\xe2\x80\x94store your backup safely in the cloud and restore anytime.", 'backwpup' ); ?>
			</p>
		</div>
	</div>
	<?php
	BackWPupHelpers::component(
		'navigation/link',
		[
			'type'    => 'primary',
			'class'   => 'text-white',
			'url'     => $cta_url,
			'content' => __( 'Upgrade to Pro', 'backwpup' ),
			'font'    => 'medium',
		]
	);
	?>
</div>
