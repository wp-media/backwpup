<?php
/**
 * Generic view renderer — loads a named template and executes it with a
 * `$data` array in scope.
 *
 * Usage:
 *   $render->view( 'consent-screen', [ 'state' => $state ] );
 */

declare(strict_types=1);

namespace WPMedia\MCP\OAuth\Views;

/**
 * Render.
 *
 * Never uses `extract()` — templates read `$data['key']` directly.
 */
class Render {

	/**
	 * Directory templates are loaded from.
	 *
	 * @var string
	 */
	private string $templates_dir;

	/**
	 * Constructor.
	 *
	 * @param string|null $templates_dir Defaults to this class's own `templates/` directory.
	 */
	public function __construct( ?string $templates_dir = null ) {
		$this->templates_dir = $templates_dir ?? __DIR__ . '/templates/';
	}

	/**
	 * Render a named template, making `$data` available in its scope.
	 *
	 * Echoes directly (no internal `ob_start()`) so an exception thrown
	 * mid-template doesn't leave a dangling output buffer behind.
	 *
	 * @param string               $view Template name, without the `.php` extension.
	 * @param array<string, mixed> $data Data made available to the template as `$data`.
	 * @return void
	 *
	 * @throws \RuntimeException When the named template file does not exist.
	 */
	public function view( string $view, array $data = [] ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $data is used via the required template's scope below.
		$path = $this->templates_dir . basename( $view ) . '.php';

		if ( ! is_file( $path ) ) {
			throw new \RuntimeException( sprintf( 'View template not found: %s', $view ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- exception message, not HTML output; Render has no WordPress dependency.
		}

		require $path;
	}
}
