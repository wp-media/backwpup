<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\API\Capability;

/**
 * Dismissible admin notice telling existing MCP users to install the standalone MCP
 * Adapter plugin, since BackWPup no longer bundles it via Composer.
 *
 * Shown only to users who can install plugins and who show evidence of previous MCP
 * usage (default-server session meta, or OAuth refresh-token meta), only while the
 * `backwpup_mcp_server_enabled` filter keeps MCP enabled, and only while no
 * `WP\MCP\Core\McpAdapter` class is loaded (i.e. MCP is actually broken for them).
 */
class NoticeMcpAdapterMigration extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'notice_mcp_adapter_migration';

	/**
	 * Capability required, in addition to `install_plugins`, to see/dismiss this notice.
	 *
	 * Reuses `Capability::GENERAL` rather than a separate constant — matches the capability
	 * BackWPup's own MCP abilities integration test grants admins for MCP access (see
	 * `tests/php/Integration/src/MCP/OAuthServerAbilitiesTest.php`), and every MCP ability's
	 * own permission check (`src/Abilities/Jobs/*`, `src/Abilities/MCP/DocsOverview.php`).
	 */
	public const CAPABILITY = Capability::GENERAL;

	/**
	 * User meta key written by the bundled/standalone MCP Adapter's default-server HTTP
	 * transport sessions (not blog-scoped).
	 */
	private const SESSION_META_KEY = 'mcp_adapter_sessions';

	/**
	 * Prefix of the user meta keys written by `wp-media/mcp-oauth`'s TokenEndpoint for
	 * each OAuth refresh token issued to the user.
	 */
	private const OAUTH_REFRESH_META_PREFIX = 'mcp_refresh_jti_';

	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 */
	public function __construct( NoticeView $view ) {
		parent::__construct( $view, true );
	}

	/**
	 * Initialize the notice with the `backwpup` capability for dismissal, matching the
	 * same gate used in should_display() rather than the AbstractNotice default ('read').
	 *
	 * @return void
	 */
	protected function init(): void {
		DismissibleNoticeOption::setup_actions( false, static::ID, self::CAPABILITY );
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		if ( ! parent::should_display() ) {
			return false;
		}

		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		if ( ! wpm_apply_filters_typed( 'boolean', 'backwpup_mcp_server_enabled', true ) ) {
			return false;
		}

		// No autoload: by the time this runs (on `all_admin_notices`), any active
		// adapter plugin has already declared its own classes via its own bootstrap.
		// Not autoloading here also avoids BackWPup's own `require-dev`-only copy
		// (kept for PHPStan/tests, see composer.json) ever being loaded in a real
		// request, since it is excluded from `composer install --no-dev` builds.
		if ( class_exists( 'WP\MCP\Core\McpAdapter', false ) ) {
			return false;
		}

		return $this->current_user_has_mcp_usage();
	}

	/**
	 * Returns whether the current user shows evidence of previous MCP usage, via either
	 * the default-server session meta or any OAuth refresh-token meta key.
	 *
	 * @return bool
	 */
	private function current_user_has_mcp_usage(): bool {
		$user_id = get_current_user_id();

		if ( get_user_meta( $user_id, self::SESSION_META_KEY, true ) ) {
			return true;
		}

		foreach ( array_keys( get_user_meta( $user_id ) ) as $meta_key ) {
			if ( 0 === strpos( $meta_key, self::OAUTH_REFRESH_META_PREFIX ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the dismiss action URL for the notice.
	 *
	 * @return string|null
	 */
	protected function get_dismiss_action_url(): ?string {
		if ( ! $this->dismissible ) {
			return null;
		}

		return DismissibleNoticeOption::dismiss_action_url(
			self::ID,
			DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
		);
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$notice_message              = new NoticeMessage( self::ID );
		$notice_message->dismissurl  = $this->get_dismiss_action_url();
		$notice_message->install_url = admin_url( 'plugin-install.php?s=mcp-adapter&tab=search&type=term' );
		return $notice_message;
	}

	/**
	 * Render the notice using the view.
	 *
	 * @param NoticeMessage $message The message to render.
	 *
	 * @return void
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->warning( $message, null );
	}
}
