<?php

use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restore step action view.
 *
 * @var \stdClass $bind
 */
?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<h3 class="hndle"><span><?php esc_html_e( 'Migration Settings', 'backwpup' ); ?></span></h3>

	<form id="migration-settings-form" action="#">
	<div class="flex flex-col gap-2" id="migrate-field">
		<?php
		BackWPupHelpers::component(
		'form/checkbox',
		[
			'identifier' => 'do-migrate',
			'name'       => 'do-migrate',
			'label'      => __( 'Migrate URL', 'backwpup' ),
			'value'      => '1',
		]
		);
		?>
	</div>
		<div id="migration-settings-container" class="hidden flex flex-col gap-2 py-4">
			<?php
			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'migration-old-url',
					'name'       => 'migration-old-url',
					'type'       => 'url',
					'label'      => __( 'Old URL', 'backwpup' ),
					'value'      => '',
					'readonly'   => true,
				]
			);

			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'migration-new-url',
					'name'       => 'migration-new-url',
					'type'       => 'url',
					'label'      => __( 'New URL', 'backwpup' ),
					'value'      => home_url(),
				]
			);
			?>
		</div>
		<div id="migration-form-btns" style="padding-top:20px;">
			<button id="migration-form-continue-btn"
					class="button button-primary button-primary-bwp step-loader"
					data-next-step="5">
				<?php esc_html_e( 'Continue', 'backwpup' ); ?>
			</button>
		</div>
	</form>

</div>
