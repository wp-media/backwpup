<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;
use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\NoticeView;
use WPMedia\BackWPup\Admin\Notices\StaleRestoreFilesDetector;

/**
 * Admin notice that warns about stale restore files containing sensitive credentials.
 *
 * Displayed on all admin pages when stale restore working-directory files are detected.
 * Only administrators (manage_options) can see or dismiss this notice.
 */
class NoticeStaleRestoreFiles extends AbstractNotice {

	/**
	 * The unique ID for this notice.
	 */
	public const ID = 'stale_restore_files';

	/**
	 * The detector service.
	 *
	 * @var StaleRestoreFilesDetector
	 */
	private StaleRestoreFilesDetector $detector;

	/**
	 * Constructor.
	 *
	 * @param NoticeView                $view     The view renderer for the notice.
	 * @param StaleRestoreFilesDetector $detector The stale files detector.
	 */
	public function __construct( NoticeView $view, StaleRestoreFilesDetector $detector ) {
		$this->detector = $detector;
		parent::__construct( $view, true );
	}

	/**
	 * Initialize the notice with manage_options capability for dismissal.
	 *
	 * Overrides AbstractNotice::init() to require manage_options instead of
	 * the default 'read', preventing non-admins from dismissing this
	 * security-sensitive notice via a crafted admin-post.php request.
	 *
	 * @return void
	 */
	protected function init(): void {
		DismissibleNoticeOption::setup_actions( false, static::ID, 'manage_options' );
	}

	/**
	 * Determine if the notice should be displayed.
	 *
	 * Requires all of: not dismissed, current user has manage_options, and
	 * the detector reports stale files are present.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		if ( ! parent::should_display() ) {
			return false;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return $this->detector->has_files();
	}

	/**
	 * Allow this notice on all admin screens except the restore pages.
	 *
	 * The restore working directory risk is not scoped to BackWPup pages, but
	 * showing it on the restore screens themselves would be redundant/confusing.
	 *
	 * @return bool
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return true;
		}

		return strpos( $screen->id, 'backwpuprestore' ) === false;
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	protected function message(): NoticeMessage {
		$notice_message               = new NoticeMessage( self::ID );
		$notice_message->delete_nonce = wp_create_nonce( 'backwpup_delete_restore_files' );
		$notice_message->dismissurl   = DismissibleNoticeOption::dismiss_action_url(
			self::ID,
			DismissibleNoticeOption::FOR_USER_FOR_GOOD_ACTION
		);
		return $notice_message;
	}

	/**
	 * Render the notice as a warning.
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->warning( $message, null );
	}
}
