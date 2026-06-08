<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Inline upgrade banner shown on the restore screen Step 1 for free users.
 *
 * Renders a non-blocking banner prompting free users to upgrade to PRO for
 * one-click restore functionality. Fires a nudge impression tracking event.
 */

$upgrade_url = wpm_apply_filters_typed(
	'string',
	'backwpup_url_add_hash',
	'https://backwpup.com/pricing/?utm_source=backwpup_plugin&utm_medium=plugin&utm_campaign=restore_banner'
);

$cta_url = add_query_arg(
	[
		'bwu_redirect'                      => rawurlencode( $upgrade_url ),
		'bwu_event'                         => 'Upgrade nudge banner clicked',
		'bwu_event_property_nudge_location' => 'restore_upgrade',
		'_wpnonce'                          => wp_create_nonce( 'backwpup_redirect_nonce' ),
	],
	admin_url( 'admin.php' )
);

do_action( 'backwpup_track_nudge_impression', 'restore_upgrade' );
?>
<div class="backwpup-typography">
	<div class="flex items-center justify-between gap-4 p-5 rounded-lg bg-white border border-[#c3c4c7] my-2">
		<div class="flex items-center gap-4">
			<span class="bg-[#E5E7E6] text-black shrink-0 p-3 rounded">
				<?php
				BackWPupHelpers::component(
					'icon',
					[
						'name' => 'restore',
						'size' => 'medium',
					]
				);
				?>
			</span>
			<div>
				<p class="text-base font-bold text-primary-darker">
					<?php esc_html_e( 'Restore backups directly with 1 click', 'backwpup' ); ?>
				</p>
				<p class="text-sm text-primary-darker">
					<?php esc_html_e( 'No manual uploads needed.', 'backwpup' ); ?>
				</p>
			</div>
		</div>
		
		<?php
		BackWPupHelpers::component(
			'navigation/link',
			[
				'url'        => $cta_url,
				'newtab'     => true,
				'content'    => __( 'Upgrade to PRO', 'backwpup' ),
				'full_width' => false,
				'type'       => 'upgrade',
			]
		);
		?>
	</div>
</div>
