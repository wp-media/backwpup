<?php
/**
 * Class BackWPup_Sanitize_Path
 */
class BackWPup_Sanitize_Path {

	/**
	 * Slug Sanitizer Pattern
	 *
	 * @var string The pattern for array keys
	 */
	const SLUG_SANITIZE_PATTERN = '/[^a-z0-9\-\_]*/';
	/**
	 * Path Sanitizer Pattern
	 *
	 * @var string The pattern to sanitize the paths
	 */
	const PATH_SANITIZE_PATTERN = '/[^a-zA-Z0-9\/\-\_\.]+/';

	/**
	 * Sanitize path
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string The sanitized path.
	 */
	public static function sanitize_path( $path ) {

		while ( false !== strpos( $path, '..' ) ) {
			$path = str_replace( '..', '', $path );
		}
		$path = ( '/' !== $path ) ? $path : '';

		return $path;
	}

	/**
	 * Sanitize Slug By RegExp
	 *
	 * @param string $slug The slug to sanitize.
	 *
	 * @return string The sanitize slug. May be empty.
	 */
	public static function sanitize_slug_reg_exp( $slug ) {

		return preg_replace( static::SLUG_SANITIZE_PATTERN, '', $slug );
	}

	/**
	 * Sanitize file path By RegExp
	 *
	 * @param string $path The path to sanitize.
	 *
	 * @return string The sanitized path
	 */
	public static function sanitize_path_regexp( $path ) {

		// Sanitize template path and remove the path separator.
		// locate_template build the path in this way {STYLESHEET|TEMPLATE}PATH . '/' . $template_name.
		return self::sanitize_path(
			preg_replace( static::PATH_SANITIZE_PATTERN, '', $path )
		);
	}
}
