<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints\AbstractVersionConstraint;
use Inpsyde\EnvironmentChecker\EnvironmentChecker;
use Inpsyde\EnvironmentChecker\Exception\ConstraintFailedExceptionInterface;

abstract class EnvironmentNotice extends Notice
{
    /**
     * {@inheritdoc}
     */
    protected function render(NoticeMessage $message): void
    {
        $this->view->warning($message, $this->getDismissActionUrl());
    }

    /**
     * {@inheritdoc}
     */
    protected function isScreenAllowed(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function shouldDisplay(): bool
    {
        if (parent::shouldDisplay()) {
            $checker = new EnvironmentChecker($this->getConstraints());

            try {
                $checker->check();

                // Passed constraints, so do not display
                return false;
            } catch (ConstraintFailedExceptionInterface|\RuntimeException $e) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns list of constraints to check.
     *
     * @return AbstractVersionConstraint[] The list of constraints
     */
    abstract protected function getConstraints(): array;
}
