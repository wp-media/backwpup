<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices;

use WPMedia\BackWPup\EventManagement\SubscriberInterface;
use WPMedia\BackWPup\Admin\Notices\Notices\AbstractNotice;

use function Inpsyde\BackWPup\Infrastructure\Restore\cleanup_restore_dir;
use function Inpsyde\BackWPup\Infrastructure\Restore\restore_dir_candidates;

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
	 * Verifies nonce and capability, then cleans up every restore working directory
	 * (current, legacy and orphaned ones) unconditionally — the admin explicitly asked
	 * for it, so no abandonment threshold applies. Returns wp_send_json_success() when
	 * files are gone, wp_send_json_error() when files remain.
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

		foreach ( restore_dir_candidates() as $project_temp ) {
			cleanup_restore_dir( $project_temp, false );
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
