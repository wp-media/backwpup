<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common\Interfaces;

interface RenderInterface {
	/**
	 * Renders the given template if it's readable.
	 *
	 * @param string $template Template slug.
	 * @param array  $data     Data to pass to the template.
	 */
	public function generate( $template, $data );
}
