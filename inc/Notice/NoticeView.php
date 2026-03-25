<?php

namespace Inpsyde\BackWPup\Notice;

use function backwpup_template;

/**
 * Notice view helpers.
 *
 * @method void success(NoticeMessage $message, string|null $dismiss_action_url)
 * @method void error(NoticeMessage $message, string|null $dismiss_action_url)
 * @method void warning(NoticeMessage $message, string|null $dismiss_action_url)
 * @method void info(NoticeMessage $message, string|null $dismiss_action_url)
 */
final class NoticeView {

	/**
	 * Success notice class.
	 *
	 * @var string
	 */
	public const SUCCESS = 'notice-success';
	/**
	 * Error notice class.
	 *
	 * @var string
	 */
	public const ERROR = 'notice-error';
	/**
	 * Warning notice class.
	 *
	 * @var string
	 */
	public const WARNING = 'notice-warning';
	/**
	 * Info notice class.
	 *
	 * @var string
	 */
	public const INFO = 'notice-info';

	/**
	 * Notice identifier.
	 *
	 * @var string The ID of the notice.
	 */
	private $id;

	/**
	 * Creates a notice view helper.
	 *
	 * @param string $id The ID of the notice.
	 */
	public function __construct( string $id ) {
		$this->id = $id;
	}

	/**
	 * Renders a notice.
	 *
	 * @param NoticeMessage $message            The contents of the notice.
	 * @param string|null   $dismiss_action_url The URL for dismissing the notice.
	 * @param string|null   $type               The type of notice: one of NoticeView::SUCCESS,
	 *                                          NoticeView::ERROR, NoticeView::WARNING, or NoticeView::INFO.
	 */
	public function notice( NoticeMessage $message, ?string $dismiss_action_url = null, ?string $type = null ): void {
		$message->id                 = $this->id;
		$message->dismiss_action_url = $dismiss_action_url;
		$message->type               = $type;

		backwpup_template( $message, '/notice/notice.php' );
	}

	/**
	 * Call notice() with the appropriate notice type.
	 *
	 * @param 'success'|'error'|'warning'|'info'       $name The notice type method name.
	 * @param array{0: NoticeMessage, 1?: string|null} $args Notice arguments.
	 *
	 * @throws \BadMethodCallException When calling an unsupported notice type.
	 */
	public function __call( string $name, array $args ): void {
		switch ( $name ) {
			case 'success':
				$args[] = self::SUCCESS;
				break;

			case 'error':
				$args[] = self::ERROR;
				break;

			case 'warning':
				$args[] = self::WARNING;
				break;

			case 'info':
				$args[] = self::INFO;
				break;

			default:
				throw new \BadMethodCallException(
					sprintf(
						// translators: %1$s: Class name, %2$s: Method name.
						esc_html__( 'Call to undefined method %1$s::%2$s()', 'backwpup' ),
						esc_html( self::class ),
						esc_html( $name )
					)
				);
		}

		$this->notice( ...$args );
	}
}
