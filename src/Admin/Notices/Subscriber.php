<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

use Inpsyde\Restore\Infrastructure\Restore\RestoreCleaner;
use Psr\Log\NullLogger;
use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Admin\Notices\Notices\AbstractNotice;

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;

/**
 * Subscriber class responsible for rendering admin notices.
 */
class Subscriber implements SubscriberInterface {
	/**
	 * Array of notice instances to be rendered.
	 *
	 * @var AbstractNotice[]
	 */
	private array $notices;

	/**
	 * Notices instance.
	 *
	 * @var Notices
	 */
	private Notices $admin_notices;

	/**
	 * Array of banner instances to be rendered.
	 *
	 * @var AbstractNotice[]
	 */
	private array $banners;

	/**
	 * Constructor.
	 *
	 * @param Notices          $admin_notices The Notices instance.
	 * @param AbstractNotice[] $notices Array of notice instances.
	 * @param AbstractNotice[] $banners Array of banner instances.
	 */
	public function __construct( Notices $admin_notices, $notices, $banners ) {
		$this->notices       = $notices;
		$this->banners       = $banners;
		$this->admin_notices = $admin_notices;
	}

	/**
	 * Returns the events this subscriber wants to listen to.
	 *
	 * @return array
	 */
	public static function get_subscribed_events() {
		return [
			'all_admin_notices'                     => [
				[ 'render_all_notices' ],
				[ 'display_update_notice' ],
				[ 'display_license_notice' ],
			],
			'backwpup_banners'                      => 'render_banners',
			'wp_ajax_backwpup_dismiss_notice'       => 'backwpup_dismiss_notices',
			'admin_post_backwpup_dismiss_notice'    => 'backwpup_dismiss_notices',
			'wp_ajax_backwpup_delete_restore_files' => 'delete_restore_files',
		];
	}

	/**
	 * Renders all registered notices on the admin_notices hook.
	 *
	 * @return void
	 */
	public function render_all_notices() {
		foreach ( $this->notices as $notice ) {
			$notice->maybe_render();
		}
	}

	/**
	 * Renders banners on the backwpup_custom_notices hook.
	 *
	 * @return void
	 */
	public function render_banners() {
		foreach ( $this->banners as $banner ) {
			$banner->maybe_render();
		}
	}

	/**
	 * Display updates notices.
	 *
	 * @return void
	 */
	public function display_update_notice(): void {
		$this->admin_notices->display_update_notices();
	}

	/**
	 * Display license notice.
	 *
	 * @return void
	 */
	public function display_license_notice(): void {
		$this->admin_notices->display_license_notice();
	}

	/**
	 * Dismiss notice update.
	 *
	 * @return void
	 */
	public function backwpup_dismiss_notices(): void {
		$this->admin_notices->backwpup_dismiss_notices();
	}

	/**
	 * AJAX handler: delete stale restore working-directory files.
	 *
	 * Verifies nonce and capability, then delegates deletion to RestoreCleaner.
	 * Returns wp_send_json_success() when files are gone, wp_send_json_error()
	 * when files remain or an exception is thrown.
	 *
	 * @return void
	 */
	public function delete_restore_files(): void {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! check_ajax_referer( 'backwpup_delete_restore_files', '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'Security check failed.', 'backwpup' ) ],
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				[ 'message' => esc_html__( 'You do not have permission to perform this action.', 'backwpup' ) ],
				403
			);
		}

		try {
			$project_temp = (string) restore_container( 'project_temp' );
			$cleaner      = new RestoreCleaner( $project_temp, new NullLogger() );
			$cleaner->cleanup();
		} catch ( \Throwable $e ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Failed to delete restore files. Please delete them manually.', 'backwpup' ),
				],
				500
			);
		}

		$detector = new StaleRestoreFilesDetector();
		if ( $detector->has_files() ) {
			wp_send_json_error(
				[
					'message' => esc_html__( 'Some restore files could not be deleted. Please delete them manually.', 'backwpup' ),
				],
				500
			);
		}

		wp_send_json_success();
	}
}
