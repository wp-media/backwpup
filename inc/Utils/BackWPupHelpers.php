<?php

namespace BackWPup\Utils;

use BackWPup;

class BackWPupHelpers {

	/**
	 * Render or return a component's HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param array  $args      Variables to pass to the component.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public static function component( string $component, array $args = [], bool $return = false ) { // @phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/components/{$component}.php";
		// Check if Pro version is active and try pro path if file not found.
		if ( ! file_exists( $path ) && BackWPup::is_pro() ) {
			$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/pro/components/{$component}.php";
		}
		if ( ! file_exists( $path ) ) {
			error_log( "Component file not found: {$path}" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return null;
		}

		// Extract the arguments for the component context.
		// Avoid overwriting built-in variables.
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		// Start output buffering.
		ob_start();
		include $path; // Execute the PHP file in the local context of extracted arguments.
		$output = ob_get_clean();

		if ( $return ) {
			return $output; // Return the output content for further processing.
		}
		// Directly echo the content if $return is false (default behavior).
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; //phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Render or return a component's children HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public static function children( string $component, bool $return = false ) { // @phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/parts/{$component}.php";
		// Check if Pro version is active and try pro path if file not found.
		if ( ! file_exists( $path ) && BackWPup::is_pro() ) {
			$path = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/pro/parts/{$component}.php";
		}

		if ( ! file_exists( $path ) ) {
			return;
		}
		// Start output buffering.
		ob_start();
		include $path; // Execute the PHP file in the local context of extracted arguments.
		$output = ob_get_clean();

		if ( $return ) {
			return $output; // Return the output content for further processing.
		}
		// Directly echo the content if $return is false (default behavior).
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return; //phpcs:ignore Squiz.PHP.NonExecutableCode.ReturnNotRequired
	}

	/**
	 * Combines class names into a single string.
	 *
	 * @param mixed ...$classes Variable list of class names which can be strings or arrays.
	 * @return string A space-separated string of class names.
	 */
	public static function clsx( ...$classes ) {
		return implode( ' ', array_filter( $classes ) );
	}
}
