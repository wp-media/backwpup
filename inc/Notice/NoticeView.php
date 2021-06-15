<?php # -*- coding: utf-8 -*-

namespace Inpsyde\BackWPup\Notice;

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
    public function notice(NoticeMessage $message, $dismiss_action_url, $type = null)
    {
        ?>

        <div
            class="notice<?php echo $type ? ' ' . esc_attr($type) : '' ?> notice-inpsyde"
            id="<?php echo esc_attr($this->id) ?>_notice"
            data-notice-id="<?php echo esc_attr($this->id) ?>"
        >
            <?php if (is_array($message->content())) { ?>
                <div class="notice-inpsyde__content">
                    <?php foreach ($message->content() as $paragraph) { ?>
                        <p><?php echo wp_kses_post($paragraph) ?></p>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <p class="notice-inpsyde__content">
                    <?php echo wp_kses_post($message->content()) ?>
                </p>
            <?php } ?>
            <p class="notice-inpsyde-actions">
                <?php if (!empty($message->cta_url())) { ?>
                    <a
                        class="button button--inpsyde"
                        href="<?php echo esc_url($message->cta_url()) ?>"
                        target="_blank"
                    >
                        <?php echo esc_html($message->button_label()) ?>
                    </a>

                <?php } ?>
                <a
                    class="button dismiss-button"
                    id="<?php echo esc_attr($this->id) ?>_dismiss"
                    href="<?php echo esc_url($dismiss_action_url) ?>"
                >
                    <?php echo esc_html_e('Don\'t show again', 'backwpup') ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Call notice() with the appropriate notice type.
     *
     * @throws BadMethodCallException If 2 arguments not given, or called with invalid notice type
     */
    public function __call($name, $args)
    {
        if (count($args) !== 2) {
            throw new \BadMethodCallException(
                sprintf(
                    __('Method %1$s::%2$s() requires 2 arguments; %3$d given', 'backwpup'),
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
