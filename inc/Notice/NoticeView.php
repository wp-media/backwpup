<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

use function backwpup_template;

/**
 * Class NoticeView
 */
class NoticeView
{

    const SUCCESS = 'notice-success';
    const ERROR = 'notice-error';
    const WARNING = 'notice-warning';
    const INFO = 'notice-info';

    /**
     * @var string The ID of the notice
     */
    private $id;

    /**
     * NoticeView constructor
     *
     * @param string $id The ID of the notice
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param \Inpsyde\BackWPup\Notice\NoticeMessage $message The contents of the notice
     * @param string $dismiss_action_url The URL for dismissing the notice
     * @param string $type The type of notice: one of NoticeView::SUCCESS,
     *                     NoticeView::ERROR, NoticeView::WARNING, or NoticeView::INFO
     *
     * @return false|string
     */
    public function notice(NoticeMessage $message, $dismissActionUrl = null, $type = null)
    {
        $message->id = $this->id;
        $message->dismissActionUrl = $dismissActionUrl;
        $message->type = $type;

        backwpup_template($message, '/notice/notice.php');
    }

    /**
     * Call notice() with the appropriate notice type.
     *
     * @throws BadMethodCallException If 2 arguments not given, or called with invalid notice type
     */
    public function __call($name, $args)
    {
        if (count($args) === 0) {
            throw new \BadMethodCallException(
                sprintf(
                    __('Method %1$s::%2$s() requires at least 1 argument; %3$d given', 'backwpup'),
                    __CLASS__,
                    $name,
                    count($args)
                )
            );
        } elseif (count($args) > 2) {
            throw new \BadMethodCallException(
                sprintf(
                    __('Method %1$s::%2$s() takes at most 2 arguments; %3$d given', 'backwpup'),
                    __CLASS__,
                    $name,
                    count($args)
                )
            );
        }

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
                        __CLASS__,
                        $name
                    )
                );
        }

        $this->notice(...$args);
    }
}
