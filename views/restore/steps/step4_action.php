<?php /** @var \stdClass $bind */ ?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>">

	<h3 class="hndle"><span><?php esc_html_e( 'Migration Settings', 'backwpup' ); ?></span></h3>

	<form id="migration-settings-form" action="#">
		<div id="migrate-field">
			<label class="mdl-switch mdl-js-switch" for="do-migrate">
				<input class="mdl-switch__input" type="checkbox" id="do-migrate" />
				<span class="mdl-switch__label"><?php esc_html_e( 'Migrate URL', 'backwpup' ); ?></span>
			</label>
		</div>
		<div id="migration-settings-container" class="hidden">
			<div id="migration-old-url-container" class="mdl-textfield mdl-js-textfield">
				<label class="mdl-textfield--floating-label" for="migration-old-url">
				<?php esc_html_e( 'Old URL', 'backwpup' ); ?>
				</label>
				<input type="text" id="migration-old-url" class="mdl-textfield__input" readonly />
			</div>
			<div id="migration-new-url-container" class="mdl-textfield mdl-js-textfield">
				<label class="mdl-textfield--floating-label" for="migration-new-url">
				<?php esc_html_e( 'New URL', 'backwpup' ); ?>
				</label>
				<input type="text" id="migration-new-url" class="mdl-textfield__input" value="<?php echo esc_attr( home_url() ); ?>" />
			</div>
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
