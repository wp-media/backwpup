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
        $this->view->warning($message, $this->getDismissActionUrl());
    }

/**
 * {@inheritdoc}
 */
    protected function isScreenAllowed()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function shouldDisplay()
    {
        if (parent::shouldDisplay()) {
            $checker = new EnvironmentChecker($this->getConstraints());

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
    abstract protected function getConstraints();
}
