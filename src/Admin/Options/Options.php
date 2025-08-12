<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Admin\Options;

/**
 * Manages options using the WordPress options API.
 */
class Options extends AbstractOptions {
	/**
	 * The prefix used by BackWpUp Options.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Constructor
	 *
	 * @param string $prefix BackWpUp options prefix.
	 */
	public function __construct( string $prefix = '' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Gets the option for the given name. Returns the default value if the value does not exist.
	 *
	 * @param string $name Name of the option to get.
	 * @param mixed  $default Default value to return if the value does not exist.
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null ) {
		$option = get_option( $this->get_option_name( $name ), $default );

		if ( is_array( $default ) && ! is_array( $option ) ) {
			$option = (array) $option;
		}

		return $option;
	}

	/**
	 * Get the option name
	 *
	 * @param string $name The name of the option.
	 *
	 * @return string
	 */
	public function get_option_name( string $name ) {
		return $this->prefix . $name;
	}

	/**
	 * Sets the value of an option. Update the value if the option for the given name already exists.
	 *
	 * @param string $name Name of the option to set.
	 * @param mixed  $value Value to set for the option.
	 * @return void
	 */
	public function set( string $name, $value ) {
		update_option( $this->get_option_name( $name ), $value );
	}

	/**
	 * Deletes the option with the given name.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function delete( string $name ) {
		delete_option( $this->get_option_name( $name ) );
	}
}
