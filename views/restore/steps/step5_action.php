<?php
/**
 * The StdClass from TemplateLoader::createBindFromStep
 *
 * @var \stdClass $bind
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div
	id="restore_step"
	class="metabox-holder postbox backwpup-cleared-postbox backwpup-full-width action"
	data-nonce="<?php echo esc_attr( $bind->nonce ); ?>"
	data-has-db="<?php echo esc_attr( $bind->has_db ? '1' : '0' ); ?>"
	data-has-files="<?php echo esc_attr( $bind->has_files ? '1' : '0' ); ?>"
>

	<h3 class="hndle">
		<span>
			<?php esc_html_e( 'Restore Progress.', 'backwpup' ); ?>
		</span>
	</h3>

	<div class="restore-progress-container">
		<div id="restore_progress"></div>
	</div>

	<button
		id="start-restore"
		class="button button-primary button-primary-bwp">
		<?php esc_html_e( 'Start', 'backwpup' ); ?>
	</button>

</div>
