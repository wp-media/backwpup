<?php

namespace Inpsyde\BackWPup\Notice;

use function backwpup_template;

/**
 * @method void success(NoticeMessage $string, string|null $dismissActionUrl)
 * @method void error(NoticeMessage $string, string|null $dismissActionUrl)
 * @method void warning(NoticeMessage $string, string|null $dismissActionUrl)
 * @method void info(NoticeMessage $string, string|null $dismissActionUrl)
 */
final class NoticeView
{
    /**
     * @var string
     */
    public const SUCCESS = 'notice-success';
    /**
     * @var string
     */
    public const ERROR = 'notice-error';
    /**
     * @var string
     */
    public const WARNING = 'notice-warning';
    /**
     * @var string
     */
    public const INFO = 'notice-info';

    /**
     * @var string The ID of the notice
     */
    private $id;

    /**
     * @param string $id The ID of the notice
     */
    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * @param NoticeMessage $message          The contents of the notice
     * @param string|null   $dismissActionUrl The URL for dismissing the notice
     * @param self::*|null  $type             The type of notice: one of NoticeView::SUCCESS,
     *                                        NoticeView::ERROR, NoticeView::WARNING, or NoticeView::INFO
     */
    public function notice(NoticeMessage $message, ?string $dismissActionUrl = null, ?string $type = null): void
    {
        $message->id = $this->id;
        $message->dismissActionUrl = $dismissActionUrl;
        $message->type = $type;

        backwpup_template($message, '/notice/notice.php');
    }

    /**
     * Call notice() with the appropriate notice type.
     *
     * @param 'success'|'error'|'warning'|'info'       $name
     * @param array{0: NoticeMessage, 1?: string|null} $args
     */
    public function __call(string $name, array $args): void
    {
        switch ($name) {
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
                        __('Call to undefined method %1$s::%2$s()', 'backwpup'),
                        self::class,
                        $name
                    )
                );
        }

        $this->notice(...$args);
    }
}
