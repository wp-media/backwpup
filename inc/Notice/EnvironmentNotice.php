<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\EnvironmentChecker;
use Inpsyde\EnvironmentChecker\Exception\ConstraintFailedException;

/**
 * Class EnvironmentNotice
 *
 * @package Inpsyde\BackWPup\Notice
 */
abstract class EnvironmentNotice extends Notice
{
    /**
     * {@inheritdoc}
     */
    protected function render(NoticeMessage $message)
    {
        $this->view->warning($message, $this->get_dismiss_action_url());
    }

/**
 * {@inheritdoc}
 */
    protected function is_screen_allowed()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function should_display()
    {
        $option = new DismissibleNoticeOption(true);

        if ((bool)$option->is_dismissed(static::ID) === false) {
            $checker = new EnvironmentChecker($this->get_constraints());

            try {
                $checker->check();

                // Passed constraints, so do not display
                return false;
            } catch (ConstraintFailedException $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns list of constraints to check
     *
     * @return array The list of constraints
     */
    abstract protected function get_constraints();
}
