<?php

namespace Inpsyde\BackWPup\Notice;

use Inpsyde\EnvironmentChecker\Constraints\AbstractVersionConstraint;
use Inpsyde\EnvironmentChecker\EnvironmentChecker;
use Inpsyde\EnvironmentChecker\Exception\ConstraintFailedExceptionInterface;

abstract class EnvironmentNotice extends Notice {

	/**
	 * Renders the notice as a warning.
	 *
	 * {@inheritdoc}
	 *
	 * @param NoticeMessage $message The message to render.
	 */
	protected function render( NoticeMessage $message ): void {
		$this->view->warning( $message, $this->get_dismiss_action_url() );
	}

	/**
	 * {@inheritdoc}
	 */
	protected function is_screen_allowed(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function should_display(): bool {
		if ( parent::should_display() ) {
			$checker = new EnvironmentChecker( $this->get_constraints() );

			try {
				$checker->check();

				// Passed constraints, so do not display.
				return false;
			} catch ( ConstraintFailedExceptionInterface | \RuntimeException $e ) {
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
	abstract protected function get_constraints(): array;
}
