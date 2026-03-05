<?php

namespace Inpsyde\EnvironmentChecker\Constraints;

use Inpsyde\EnvironmentChecker\Exception\ConstraintFailedException;

class ExtensionConstraint extends AbstractVersionConstraint
{

	/**
	 * PhpAbstractVersionConstraint constructor.
	 *
	 * @param string $requiredVersion
	 */
	public function __construct($requiredVersion)
	{
		parent::__construct($requiredVersion);
		$this->error = 'Required Extension not loaded';
	}

	/**
	 * @inheritDoc
	 */
	public function check()
	{
		$this->message = $this->requiredVersion
			. ' extension is required. Enable it in your server or ask your webhoster to enable it for you.';
		if (function_exists('extension_loaded')
			&& !extension_loaded(
				$this->requiredVersion
			)
		) {
			throw new ConstraintFailedException(
				$this, // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Constraint instance is stored on the exception, not output.
				esc_html($this->requiredVersion),
				[esc_html($this->error)],
				esc_html($this->message)
			);
		}
		return true;
	}
}
