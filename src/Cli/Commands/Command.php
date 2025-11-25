<?php

namespace WPMedia\BackWPup\Cli\Commands;

interface Command {

	/**
	 * Invokes the object as a function.
	 *
	 * @param array $args Positional arguments passed to the method.
	 * @param array $assoc_args Associative arguments passed to the method.
	 */
	public function __invoke( array $args, array $assoc_args );

	/**
	 * Retrieves the command name.
	 *
	 * This method is to get the name of the command.
	 *
	 * @return string The name as a string.
	 */
	public function get_name();

	/**
	 * Retrieves the arguments for the command.
	 *
	 * This method is used to get the list of arguments associated with the command.
	 * Most will be got from php dock.
	 *
	 * @see https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#annotating-with-phpdoc
	 * @see https://make.wordpress.org/cli/handbook/guides/commands-cookbook/#wp_cliadd_commands-third-args-parameter
	 *
	 * @return array An associative array containing:
	 *               - 'before_invoke': A callback function to be executed before invoking the command.
	 *               - 'after_invoke': A callback function to be executed after invoking the command.
	 *               - 'shortdesc': A short description of the command.
	 *               - 'longdesc': A detailed description of the command.
	 *               - 'when': The execution timing of the command.
	 *               - 'synopsis': An array defining the command's arguments,
	 *                 including their type (positional, assoc, flag), name, description, options, repeating, default and optional.
	 */
	public function get_args(): array;
}
