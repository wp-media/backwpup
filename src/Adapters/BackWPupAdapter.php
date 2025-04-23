<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

class BackWPupAdapter {
	/**
	 * Get information about the Plugin.
	 *
	 * @param string $name Name of info to get or NULL to get all.
	 *
	 * @return string|array
	 */
	public function get_plugin_data( ?string $name = null ) {
		return \BackWPup::get_plugin_data( $name );
	}
}
