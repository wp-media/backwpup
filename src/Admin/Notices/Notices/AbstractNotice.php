<?php
namespace WPMedia\BackWPup\Admin\Notices\Notices;

use Inpsyde\BackWPup\Notice\NoticeView;
use Inpsyde\BackWPup\Notice\NoticeMessage;
use Inpsyde\BackWPup\Notice\DismissibleNoticeOption;

/**
 * Abstract base class for admin notices.
 */
abstract class AbstractNotice {
	/**
	 * The view renderer for the notice.
	 *
	 * @var NoticeView
	 */
	protected NoticeView $view;

	/**
	 * Whether the notice is dismissible.
	 *
	 * @var bool
	 */
	protected bool $dismissible;

	/**
	 * Unique notice ID.
	 *
	 * @var string
	 */
	public const ID = 'notice';

	/**
	 * Allowed admin page IDs for displaying notices.
	 *
	 * @var array
	 */
	protected static $main_admin_page_ids = [
		'toplevel_page_backwpup',
		'toplevel_page_backwpup-network',
	];

	/**
	 * Initialize the notice, registering dismiss actions if needed.
	 *
	 * @return void
	 */
	protected function init(): void {
		if ( ! $this->dismissible ) {
			return;
		}
		// Register dismiss actions for this notice (per-user, not sitewide by default).
		DismissibleNoticeOption::setup_actions( false, static::ID, 'read' );
	}

	/**
	 * Constructor.
	 *
	 * @param NoticeView $view The view renderer for the notice.
	 * @param bool       $dismissible Whether the notice is dismissible.
	 */
	public function __construct( NoticeView $view, bool $dismissible = true ) {
		$this->view        = $view;
		$this->dismissible = $dismissible;
		$this->init();
	}

	/**
	 * Returns whether the current screen should show the notice.
	 *
	 * @return bool
	 */
	protected function is_screen_allowed(): bool {
		$screen = get_current_screen();
		if ( ! $screen instanceof \WP_Screen ) {
			return false;
		}
		return in_array( $screen->id, static::$main_admin_page_ids, true );
	}

	/**
	 * Render the notice if it should be displayed and is on an allowed screen.
	 *
	 * @return void
	 */
	public function maybe_render(): void {
		if ( ! $this->is_screen_allowed() ) {
			return;
		}
		if ( ! $this->should_display() ) {
			return;
		}
		$this->render( $this->message() );
	}

	/**
	 * Build the message for the notice.
	 *
	 * @return NoticeMessage
	 */
	abstract protected function message(): NoticeMessage;

	/**
	 * Determine if the notice should be displayed.
	 *
	 * @return bool
	 */
	protected function should_display(): bool {
		if ( ! $this->dismissible ) {
			return true;
		}
		$option = new DismissibleNoticeOption( true );
		return ! $option->is_dismissed( static::ID );
	}

	/**
	 * Render the notice.
	 *
	 * @param NoticeMessage $message The message to render.
	 * @return void
	 */
	abstract protected function render( NoticeMessage $message ): void;
}
