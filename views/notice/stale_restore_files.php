<?php
/**
 * Admin notice template: stale restore files warning.
 *
 * Variables available via $bind (NoticeMessage):
 *   - $bind->delete_nonce  string  Nonce for the delete AJAX action.
 *   - $bind->dismissurl    string  URL to permanently dismiss the notice for this user.
 *
 * @package BackWPup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="notice-titre">
	<?php esc_html_e( 'Security Warning: Sensitive restore files detected', 'backwpup' ); ?>
</p>
<?php if ( isset( $bind->dismissurl ) && $bind->dismissurl ) : ?>
	<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); ?>" data-bwpu-hide="stale_restore_files_notice">
		<span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span>
	</a>
<?php endif; ?>
<p>
	<?php
	esc_html_e(
		'BackWPup detected leftover files from a previous restore operation in the restore working directory. These files may contain your database credentials in plain text. It is strongly recommended to delete them immediately.',
		'backwpup'
	);
	?>
</p>
<p>
	<button
		type="button"
		id="backwpup-delete-restore-files"
		class="button button--inpsyde delete-restore-files"
		data-nonce="<?php echo esc_attr( $bind->delete_nonce ); ?>"
		data-action="backwpup_delete_restore_files"
	>
		<?php esc_html_e( 'Delete restore files', 'backwpup' ); ?>
	</button>
</p>
<span
	id="backwpup-delete-restore-files-error"
	class="stale-restore-files-error"
	role="alert"
	aria-live="assertive"
	style="display:none; margin-left: 1em; color: red;"
></span>
