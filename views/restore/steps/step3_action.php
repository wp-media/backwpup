<?php /** @var \stdClass $bind */ ?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<h3 class="hndle"><span><?php esc_html_e( 'Database Connection Settings.', 'backwpup' ); ?></span></h3>

	<form id="db-settings-form" action="#">
		<div id="db-host-field" class="mdl-textfield mdl-js-textfield textfield-demo">
			<input class="mdl-textfield__input" type="text" id="db_host" value="<?php echo esc_attr( DB_HOST ); ?>" readonly />
			<label class="mdl-textfield__label" for="db_host">
				<?php esc_html_e( 'Database Host', 'backwpup' ); ?>
			</label>
		</div>
		<div id="db-name-field" class="mdl-textfield mdl-js-textfield">
			<input class="mdl-textfield__input" type="text" id="db_name" value="<?php echo esc_attr( DB_NAME ); ?>" readonly />
			<label class="mdl-textfield__label" for="db_name">
				<?php esc_html_e( 'Database Name', 'backwpup' ); ?>
			</label>
		</div>
		<div id="db-user-field" class="mdl-textfield mdl-js-textfield">
			<input class="mdl-textfield__input" type="text" id="db_user" value="<?php echo esc_attr( DB_USER ); ?>" readonly />
			<label class="mdl-textfield__label" for="db_user">
				<?php esc_html_e( 'Database User', 'backwpup' ); ?>
			</label>
		</div>
		<div id="db-pw-field" class="mdl-textfield mdl-js-textfield">
			<input class="mdl-textfield__input" type="password" id="db_pw" value="<?php echo esc_attr( DB_PASSWORD ); ?>" readonly />
			<label class="mdl-textfield__label" for="db_pw">
				<?php esc_html_e( 'Database Password', 'backwpup' ); ?>
			</label>
		</div>
		<!-- Hide Charset field by default. We assume we can find the charset automatically.
			Only show field if charset cannot determined automatically -->
		<div id="db-charset-field" class="hidden mdl-textfield mdl-js-textfield">
			<input class="mdl-textfield__input" type="text" id="db_charset" value="<?php echo esc_attr( DB_CHARSET ); ?>" readonly />
			<label class="mdl-textfield__label" for="db_charset">
				<?php esc_html_e( 'Database Charset', 'backwpup' ); ?>
			</label>
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
