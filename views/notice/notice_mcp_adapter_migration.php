<?php
/**
 * Admin notice template: MCP Adapter migration notice.
 *
 * Variables available via $bind (NoticeMessage):
 *   - $bind->dismissurl  string  URL to dismiss the notice for this user.
 *   - $bind->install_url string  URL to the MCP Adapter plugin install screen.
 *
 * @package BackWPup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p class="notice-titre"><?php esc_html_e( 'MCP Adapter plugin required', 'backwpup' ); ?></p>
<a class="closeIt bwpup-ajax-close" href="<?php echo esc_url( $bind->dismissurl ); ?>" data-bwpu-hide="notice_mcp_adapter_migration_notice">
	<span class="screen-reader-text"><?php esc_html_e( 'Dismiss', 'backwpup' ); ?></span>
</a>
<p>
	<?php
	printf(
		// Translators: %1$s = opening link tag, %2$s = closing link tag.
		esc_html__(
			'BackWPup no longer bundles the MCP Adapter, which is now available as a standalone plugin on WordPress.org. To keep managing BackWPup via MCP, please install and activate the %1$sMCP Adapter plugin%2$s.',
			'backwpup'
		),
		'<a href="' . esc_url( $bind->install_url ) . '">',
		'</a>'
	);
	?>
</p>
