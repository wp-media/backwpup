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

	<h3 class="hndle"><span><?php esc_html_e( 'Database Connection Settings.', 'backwpup' ); ?></span></h3>

	<form id="db-settings-form" action="#">
		<div class="flex flex-col gap-2">
			<?php
			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'db_host',
					'name'       => 'db_host',
					'type'       => 'text',
					'label'      => __( 'Database Host', 'backwpup' ),
					'value'      => DB_HOST,
					'readonly'   => true,
				]
				);

			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'db_name',
					'name'       => 'db_name',
					'type'       => 'text',
					'label'      => __( 'Database Name', 'backwpup' ),
					'value'      => DB_NAME,
					'readonly'   => true,
				]
				);

			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'db_user',
					'name'       => 'db_user',
					'type'       => 'text',
					'label'      => __( 'Database User', 'backwpup' ),
					'value'      => DB_USER,
					'readonly'   => true,
				]
				);

			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'db_pw',
					'name'       => 'db_pw',
					'type'       => 'password',
					'label'      => __( 'Database Password', 'backwpup' ),
					'value'      => DB_PASSWORD,
					'readonly'   => true,
				]
				);
			?>
		<!-- Hide Charset field by default. We assume we can find the charset automatically.
			Only show field if charset cannot determined automatically -->
		<div id="db-charset-field" class="hidden mdl-textfield mdl-js-textfield">
			<?php
			BackWPupHelpers::component(
				'form/text',
				[
					'identifier' => 'db_charset',
					'name'       => 'db_charset',
					'type'       => 'text',
					'label'      => __( 'Database Charset', 'backwpup' ),
					'value'      => DB_CHARSET,
					'readonly'   => true,
				]
				);
			?>
		</div>
	</div>
		<div id="db-form-btns" style="padding-top:20px;">
			<button id="db_edit_btn"
					class="button button-primary button-primary-bwp">
				<?php esc_html_e( 'Edit', 'backwpup' ); ?>
			</button>
			<button id="db_test_btn"
					class="button button-primary button-primary-bwp">
				<?php esc_html_e( 'Test Connection', 'backwpup' ); ?>
			</button>
			<button id="db_form_continue_btn"
					class="button button-primary button-primary-bwp step-loader"
					data-next-step="<?php echo $bind->migrate_allowed ? 4 : 5; ?>">
				<?php esc_html_e( 'Continue', 'backwpup' ); ?>
			</button>
		</div>
	</form>

</div>
