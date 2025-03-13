<?php

/**
 * Calls the callback functions that have been added to a filter hook in a typesafe manner.
 *
 * @param string $hook_name The name of the filter hook.
 * @param mixed  $value     The value to filter.
 * @param mixed  ...$args   Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function wpm_apply_filters_typesafe( $hook_name, $value, ...$args ) {
	return wpm_apply_filters_typed( gettype( $value ), $hook_name, $value, ...$args );
}

/**
 * Calls the callback functions that have been added to a filter hook in a typed manner.
 *
 * @param string $type      The type the return value should have.
 * @param string $hook_name The name of the filter hook.
 * @param mixed  $value     The value to filter.
 * @param mixed  ...$args   Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function wpm_apply_filters_typed( $type, $hook_name, $value, ...$args ) {
	$next_value = apply_filters( $hook_name, $value, ...$args ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound

	$types = [ $type ];

	// Union types are separated by a pipe character.
	if ( preg_match( '/\|/i', $type ) ) {
		$types = explode( '|', $type );
	}

	foreach ( $types as $type ) {
		if ( wpm_is_type( $type, $next_value ) ) {
			return $next_value;
		}
	}

	// None of the types matched.
	_doing_it_wrong( __FUNCTION__, sprintf( 'Return value of "%1$s" filter must be of the type "%2$s", "%3$s" returned.', esc_attr( $hook_name ), esc_attr( $type ), esc_attr( gettype( $next_value ) ) ), '1.0.1' );

	return $value;
}

/**
 * Checks whether the given variable is a certain type.
 *
 * Returns whether `$value` is certain type.
 *
 * @since 1.0
 *
 * @param string $type  The type to check.
 * @param mixed  $value The variable to check.
 *
 * @return bool Whether the variable is of the type.
 */
function wpm_is_type( $type, $value ) {
	$type = strtolower( $type );

	// Check if the type is nullable.
	if ( '?' === substr( $type, 0, 1 ) ) {
		$type = substr( $type, 1 );

		if ( is_null( $value ) ) {
			return true;
		}
	}

	// Check if the type is an array of a certain type.
	if ( '[]' === substr( $type, -2 ) ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		$type = substr( $type, 0, -2 );

		foreach ( $value as $item ) {
			if ( ! wpm_is_type( $type, $item ) ) {
				return false;
			}
		}

		return true;
	}

	switch ( $type ) {
		case 'boolean':
			return is_bool( $value );
		case 'integer':
			return is_int( $value );
		case 'double':
			return is_float( $value );
		case 'string':
			return is_string( $value );
		case 'array':
			return is_array( $value );
		case 'object':
			return is_object( $value );
		case 'resource':
		case 'resource (closed)':
			return is_resource( $value );
		case 'null':
			return is_null( $value );
		case 'false':
			return false === $value;
		case 'true':
			return true === $value;
		case 'unknown_type':
			return false;
		default:
			/**
			 * Filters whether the variable is of the type.
			 * The dynamic portion of the hook name, `$type`, refers to the type of the variable.
			 *
			 * @since 1.0
			 *
			 * @param bool  $is_type Is the variable of the type. Default false.
			 * @param mixed $value The variable to check.
			 */
			return apply_filters( "wpm_is_type_{$type}", false, $value );
	}
}
