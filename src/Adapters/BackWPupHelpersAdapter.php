<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

use BackWPup\Utils\BackWPupHelpers;

class BackWPupHelpersAdapter {
	/**
	 * Render or return a component's HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param array  $args      Variables to pass to the component.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public function component( string $component, array $args = [], bool $return = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		return BackWPupHelpers::component( $component, $args, $return );
	}

	/**
	 * Render or return a component's children HTML.
	 *
	 * @param string $component The name of the component file to include.
	 * @param bool   $return    Whether to return the HTML instead of echoing it.
	 * @param array  $args      Variables to pass to the children.
	 *
	 * @return string|null HTML content of the component if `$return` is true; otherwise, null.
	 */
	public function children( string $component, bool $return = false, array $args = [] ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.returnFound
		return BackWPupHelpers::children( $component, $return, $args );
	}

	/**
	 * Process backup items.
	 *
	 * @param array  $items    The list of backup items.
	 * @param array  $job_data The job data to merge with each item.
	 * @param string $dest     The destination of the backup.
	 * @param int    $page     The current page for pagination.
	 *
	 * @return array The processed items.
	 */
	public function process_backup_items( array $items, array $job_data, string $dest, int $page ): array {
		return BackWPupHelpers::process_backup_items( $items, $job_data, $dest, $page );
	}
}
